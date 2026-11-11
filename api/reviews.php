<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Lax']);
require_once __DIR__ . '/config.php';

function reviewResponse(array $data, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
$method = $_SERVER['REQUEST_METHOD'];
if (!in_array($method, ['GET', 'POST'], true)) {
    header('Allow: GET, POST');
    reviewResponse(['ok' => false, 'message' => 'Método não permitido.'], 405);
}
$input = $method === 'POST' ? json_decode(file_get_contents('php://input'), true) : $_GET;
if (!is_array($input)) reviewResponse(['ok' => false, 'message' => 'Dados inválidos.'], 422);
$productId = filter_var($input['product_id'] ?? null, FILTER_VALIDATE_INT);
// IDs do catálogo atual em assets/js/products.js.
if (!in_array($productId, [1, 2], true)) reviewResponse(['ok' => false, 'message' => 'Produto não encontrado.'], 404);
$userId = (int)($_SESSION['user_id'] ?? 0);
try {
    $db = database();
    $eligible = false;
    $ownReview = null;
    if ($userId) {
        $query = $db->prepare('SELECT 1 FROM product_purchases WHERE user_id = ? AND product_id = ?');
        $query->execute([$userId, $productId]);
        $eligible = (bool)$query->fetchColumn();
    }
    if ($method === 'POST') {
        if (!$userId) reviewResponse(['ok' => false, 'message' => 'Entre na sua conta para avaliar.'], 401);
        if (!is_string($input['csrf'] ?? null) || empty($_SESSION['reviews_csrf']) || !hash_equals($_SESSION['reviews_csrf'], $input['csrf'])) {
            reviewResponse(['ok' => false, 'message' => 'Atualize a página e tente novamente.'], 403);
        }
        if (!$eligible) reviewResponse(['ok' => false, 'message' => 'A avaliação é liberada após a confirmação da compra pela loja.'], 403);
        $rating = filter_var($input['rating'] ?? null, FILTER_VALIDATE_INT);
        $comment = is_string($input['comment'] ?? null) ? trim($input['comment']) : '';
        if ($rating === false || $rating < 1 || $rating > 5 || mb_strlen($comment, 'UTF-8') < 3 || mb_strlen($comment, 'UTF-8') > 1000) {
            reviewResponse(['ok' => false, 'message' => 'Escolha de 1 a 5 estrelas e escreva entre 3 e 1.000 caracteres.'], 422);
        }
        $query = $db->prepare('INSERT INTO product_reviews (user_id, product_id, rating, comment) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE rating = VALUES(rating), comment = VALUES(comment), updated_at = CURRENT_TIMESTAMP');
        $query->execute([$userId, $productId, $rating, $comment]);
    }
    if ($eligible) {
        $query = $db->prepare('SELECT rating, comment FROM product_reviews WHERE user_id = ? AND product_id = ?');
        $query->execute([$userId, $productId]);
        $ownReview = $query->fetch() ?: null;
    }
    $query = $db->prepare('SELECT COUNT(*) AS total, AVG(rating) AS average FROM product_reviews WHERE product_id = ?');
    $query->execute([$productId]);
    $stats = $query->fetch();
    $query = $db->prepare('SELECT r.rating, r.comment, r.updated_at, u.name FROM product_reviews r JOIN users u ON u.id = r.user_id WHERE r.product_id = ? ORDER BY r.updated_at DESC, r.id DESC LIMIT 50');
    $query->execute([$productId]);
    $reviews = array_map(static function (array $review): array {
        return ['rating' => (int)$review['rating'], 'comment' => $review['comment'],
            'name' => explode(' ', trim($review['name']))[0], 'date' => substr($review['updated_at'], 0, 10)];
    }, $query->fetchAll());
    if ($userId && empty($_SESSION['reviews_csrf'])) $_SESSION['reviews_csrf'] = bin2hex(random_bytes(32));
    reviewResponse(['ok' => true, 'total' => (int)$stats['total'],
        'average' => $stats['average'] === null ? null : round((float)$stats['average'], 1),
        'reviews' => $reviews, 'loggedIn' => $userId > 0, 'eligible' => $eligible,
        'ownReview' => $ownReview, 'csrf' => $userId ? $_SESSION['reviews_csrf'] : null]);
} catch (PDOException $error) {
    error_log('SOLID reviews database error: ' . $error->getCode());
    reviewResponse(['ok' => false, 'message' => 'As avaliações estão indisponíveis no momento. Tente novamente mais tarde.'], 503);
}
