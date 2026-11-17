<?php
// Run only from a terminal: credentials are read from stdin, never from a web request.
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../api/config.php';
$input = json_decode(stream_get_contents(STDIN), true, 8, JSON_THROW_ON_ERROR);
$email = strtolower(trim((string)($input['email'] ?? '')));
$password = (string)($input['password'] ?? '');
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 12 || strlen($password) > 72) throw new RuntimeException('Informe e-mail e senha de 12 a 72 caracteres.');
$db = database();
$db->exec(file_get_contents(__DIR__.'/../database/sql/admin-panel.sql'));
$db->exec(file_get_contents(__DIR__.'/../database/sql/orders.sql'));
$db->exec(file_get_contents(__DIR__.'/../database/sql/admin-pwa.sql'));
$db->exec(file_get_contents(__DIR__.'/../database/sql/product-reviews.sql'));
$db->beginTransaction();
try {
    if ((int)$db->query('SELECT COUNT(*) FROM products')->fetchColumn() === 0) {
        $insert = $db->prepare('INSERT INTO products (id, data) VALUES (?, ?)');
        foreach (json_decode(file_get_contents(__DIR__.'/../database/data/products.json'), true) as $product) $insert->execute([$product['id'],json_encode($product,JSON_UNESCAPED_UNICODE)]);
    }
    $query = $db->prepare('SELECT id FROM users WHERE email = ?'); $query->execute([$email]); $id = $query->fetchColumn();
    if ($id) {
        $db->prepare('UPDATE users SET is_admin = 1 WHERE id = ?')->execute([$id]);
        echo "Conta existente promovida. A senha foi preservada.\n";
    } else {
        $db->prepare("INSERT INTO users (name,email,password_hash,phone,zip,address,address_number,neighborhood,city,is_admin,must_change_password) VALUES (?,?,?,'','','','','','',1,1)")->execute(['Administrador',$email,password_hash($password,PASSWORD_DEFAULT)]);
        echo "Administrador criado. Troca de senha obrigatória no primeiro acesso.\n";
    }
    $db->commit();
} catch (Throwable $e) { $db->rollBack(); throw $e; }
