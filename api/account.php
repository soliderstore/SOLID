<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
]);

require_once __DIR__ . '/config.php';

function respond(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function input(): array
{
    $data = json_decode(file_get_contents('php://input'), true);
    return is_array($data) ? $data : [];
}

function text(array $data, string $key, bool $required = false): string
{
    $value = trim((string)($data[$key] ?? ''));
    if ($required && $value === '') {
        respond(['ok' => false, 'message' => 'Preencha todos os campos obrigatórios.'], 422);
    }
    return $value;
}

function userData(array $user): array
{
    return [
        'id' => (int)$user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'phone' => $user['phone'],
        'cpf' => $user['cpf'] ?? '',
        'zip' => $user['zip'],
        'address' => $user['address'],
        'number' => $user['address_number'],
        'complement' => $user['complement'] ?? '',
        'neighborhood' => $user['neighborhood'],
        'city' => $user['city'],
        'newsletter' => (bool)$user['newsletter'],
        'couponUsed' => (bool)$user['coupon_used'],
        'profileImage' => $user['profile_image'] ?? '',
    ];
}

function currentUser(PDO $db): array
{
    $id = (int)($_SESSION['user_id'] ?? 0);
    if ($id === 0) {
        respond(['ok' => false, 'message' => 'Faça login para continuar.'], 401);
    }

    $query = $db->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $query->execute([$id]);
    $user = $query->fetch();
    if (!$user) {
        session_destroy();
        respond(['ok' => false, 'message' => 'Sessão inválida.'], 401);
    }
    return $user;
}

function imageValue(array $data, string $current = ''): string
{
    $image = (string)($data['profileImage'] ?? $current);
    if ($image === '') return '';
    if (!str_starts_with($image, 'data:image/') || strlen($image) > 2100000) {
        respond(['ok' => false, 'message' => 'A foto de perfil é inválida ou muito grande.'], 422);
    }
    return $image;
}

try {
    $db = database();
    $data = input();
    $action = (string)($data['action'] ?? '');

    if ($action === 'register') {
        $name = text($data, 'name', true);
        $email = strtolower(text($data, 'email', true));
        $password = (string)($data['password'] ?? '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) {
            respond(['ok' => false, 'message' => 'Informe um e-mail válido e uma senha com pelo menos 6 caracteres.'], 422);
        }

        $check = $db->prepare('SELECT id FROM users WHERE email = ?');
        $check->execute([$email]);
        if ($check->fetch()) respond(['ok' => false, 'message' => 'Já existe uma conta com este e-mail.'], 409);

        $insert = $db->prepare('INSERT INTO users (name, email, password_hash, phone, cpf, zip, address, address_number, complement, neighborhood, city, newsletter, profile_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $insert->execute([
            $name, $email, password_hash($password, PASSWORD_DEFAULT), text($data, 'phone', true), text($data, 'cpf'),
            text($data, 'zip', true), text($data, 'address', true), text($data, 'number', true), text($data, 'complement'),
            text($data, 'neighborhood', true), text($data, 'city', true), !empty($data['newsletter']) ? 1 : 0, imageValue($data)
        ]);
        $_SESSION['user_id'] = (int)$db->lastInsertId();
        $user = currentUser($db);
        respond(['ok' => true, 'user' => userData($user)]);
    }

    if ($action === 'login') {
        $email = strtolower(text($data, 'email', true));
        $password = (string)($data['password'] ?? '');
        $query = $db->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $query->execute([$email]);
        $user = $query->fetch();
        if (!$user || !password_verify($password, $user['password_hash'])) {
            respond(['ok' => false, 'message' => 'E-mail ou senha incorretos.'], 401);
        }
        $_SESSION['user_id'] = (int)$user['id'];
        respond(['ok' => true, 'user' => userData($user)]);
    }

    if ($action === 'session') {
        $user = currentUser($db);
        respond(['ok' => true, 'user' => userData($user)]);
    }

    if ($action === 'coupon_status') {
        $user = currentUser($db);
        respond(['ok' => true, 'couponUsed' => (bool)$user['coupon_used']]);
    }

    if ($action === 'redeem_coupon') {
        $user = currentUser($db);
        if ((bool)$user['coupon_used']) {
            respond(['ok' => false, 'message' => 'Este cupom já foi usado nesta conta.'], 409);
        }

        $redeem = $db->prepare('UPDATE users SET coupon_used = 1 WHERE id = ? AND coupon_used = 0');
        $redeem->execute([$user['id']]);
        if ($redeem->rowCount() !== 1) {
            respond(['ok' => false, 'message' => 'Este cupom já foi usado nesta conta.'], 409);
        }
        respond(['ok' => true]);
    }

    if ($action === 'update') {
        $current = currentUser($db);
        $name = text($data, 'name', true);
        $email = strtolower(text($data, 'email', true));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) respond(['ok' => false, 'message' => 'Informe um e-mail válido.'], 422);

        $check = $db->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
        $check->execute([$email, $current['id']]);
        if ($check->fetch()) respond(['ok' => false, 'message' => 'Este e-mail já está em uso.'], 409);

        $update = $db->prepare('UPDATE users SET name = ?, email = ?, phone = ?, cpf = ?, zip = ?, address = ?, address_number = ?, complement = ?, neighborhood = ?, city = ?, newsletter = ?, profile_image = ? WHERE id = ?');
        $update->execute([
            $name, $email, text($data, 'phone', true), text($data, 'cpf'), text($data, 'zip', true), text($data, 'address', true), text($data, 'number', true), text($data, 'complement'), text($data, 'neighborhood', true), text($data, 'city', true), !empty($data['newsletter']) ? 1 : 0, imageValue($data, $current['profile_image'] ?? ''), $current['id']
        ]);
        respond(['ok' => true, 'user' => userData(currentUser($db))]);
    }

    if ($action === 'logout') {
        $_SESSION = [];
        session_destroy();
        respond(['ok' => true]);
    }

    respond(['ok' => false, 'message' => 'Ação inválida.'], 400);
} catch (PDOException $error) {
    respond(['ok' => false, 'message' => 'Não foi possível conectar ao banco de dados. Confira config/conexao.php e importe o arquivo SQL em database/sql.'], 500);
}
