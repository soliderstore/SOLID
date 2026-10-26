<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Lax']);
require_once __DIR__ . '/../lib/newsletter.php';

function newsletterRespond(array $data, int $status): never
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    newsletterRespond(['ok' => false, 'message' => 'Método inválido.'], 405);
}
$data = json_decode(file_get_contents('php://input'), true);
$email = is_array($data) && is_string($data['email'] ?? null) ? strtolower(trim($data['email'])) : '';
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254 || ($data['consent'] ?? false) !== true) {
    newsletterRespond(['ok' => false, 'message' => 'Informe um e-mail válido e autorize o recebimento de novidades.'], 422);
}
if (time() - (int)($_SESSION['newsletter_last_attempt'] ?? 0) < 60) {
    newsletterRespond(['ok' => false, 'message' => 'Aguarde um minuto antes de tentar novamente.'], 429);
}
$_SESSION['newsletter_last_attempt'] = time();
try {
    newsletterSubscribe($email);
    newsletterRespond(['ok' => true, 'message' => 'Confira seu e-mail e confirme a inscrição para receber roupas novas e descontos.'], 200);
} catch (RuntimeException $error) {
    newsletterRespond(['ok' => false, 'message' => $error->getMessage()], 503);
}
