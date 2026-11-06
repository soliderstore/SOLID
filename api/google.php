<?php
declare(strict_types=1);

session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Lax',
    'cookie_secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
header('Cache-Control: no-store');
header('Referrer-Policy: no-referrer');
require_once __DIR__ . '/config.php';

function googleFailure(string $reason): never
{
    unset($_SESSION['google_oauth']);
    header('Location: ../pages/login.php?' . http_build_query(['google_error' => $reason,
        'next' => $_SESSION['google_next'] ?? 'profile']));
    exit;
}

function googleRequest(string $url, ?array $body = null, string $token = ''): array
{
    if (!function_exists('curl_init')) throw new RuntimeException('cURL unavailable');
    $curl = curl_init($url);
    $options = [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20,
        CURLOPT_PROTOCOLS => CURLPROTO_HTTPS, CURLOPT_HTTPHEADER => ['Accept: application/json']];
    if ($body !== null) {
        $options[CURLOPT_POST] = true;
        $options[CURLOPT_POSTFIELDS] = http_build_query($body);
    }
    if ($token !== '') $options[CURLOPT_HTTPHEADER][] = 'Authorization: Bearer ' . $token;
    curl_setopt_array($curl, $options);
    $raw = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    $data = is_string($raw) ? json_decode($raw, true) : null;
    if ($status !== 200 || !is_array($data)) throw new RuntimeException('Google request failed');
    return $data;
}

$file = __DIR__ . '/../config/google.local.php';
$config = is_file($file) ? require $file : [];
if (empty($config['client_id']) || empty($config['client_secret']) || empty($config['redirect_uri'])) {
    googleFailure('configuration');
}

if (!isset($_GET['callback'])) {
    $_SESSION['google_next'] = ($_GET['next'] ?? '') === 'checkout' ? 'checkout' : 'profile';
    $state = bin2hex(random_bytes(32));
    $verifier = bin2hex(random_bytes(32));
    $_SESSION['google_oauth'] = ['state' => $state, 'verifier' => $verifier, 'time' => time()];
    header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
        'client_id' => $config['client_id'], 'redirect_uri' => $config['redirect_uri'],
        'response_type' => 'code', 'scope' => 'openid email profile', 'state' => $state,
        'prompt' => 'select_account', 'hl' => 'pt-BR',
        'code_challenge' => rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '='),
        'code_challenge_method' => 'S256',
    ]));
    exit;
}

$pending = $_SESSION['google_oauth'] ?? [];
unset($_SESSION['google_oauth']);
if (empty($pending['state']) || !is_string($_GET['state'] ?? null)
    || !hash_equals($pending['state'], $_GET['state']) || time() - $pending['time'] > 600) googleFailure('expired');
if (isset($_GET['error'])) googleFailure('cancelled');
if (!is_string($_GET['code'] ?? null) || $_GET['code'] === '') googleFailure('failed');

try {
    $tokens = googleRequest('https://oauth2.googleapis.com/token', [
        'code' => $_GET['code'], 'client_id' => $config['client_id'],
        'client_secret' => $config['client_secret'], 'redirect_uri' => $config['redirect_uri'],
        'grant_type' => 'authorization_code', 'code_verifier' => $pending['verifier'],
    ]);
    if (empty($tokens['access_token'])) throw new RuntimeException('Missing token');
    $identity = googleRequest('https://openidconnect.googleapis.com/v1/userinfo', null, $tokens['access_token']);
    $email = strtolower((string)($identity['email'] ?? ''));
    $sub = (string)($identity['sub'] ?? '');
    if ($sub === '' || ($identity['email_verified'] ?? false) !== true
        || !filter_var($email, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Unverified account');
    $db = database();
    $query = $db->prepare('SELECT user_id FROM google_accounts WHERE google_sub = ?');
    $query->execute([$sub]);
    $userId = $query->fetchColumn();
    if (!$userId) {
        $db->beginTransaction();
        $query = $db->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $query->execute([$email]);
        $userId = $query->fetchColumn();
        // O Google só é autoridade pelo e-mail Gmail ou Workspace verificado.
        if ($userId && !str_ends_with($email, '@gmail.com') && empty($identity['hd'])) {
            $db->rollBack();
            googleFailure('existing');
        }
        if (!$userId) {
            $query = $db->prepare('INSERT INTO users (name, email, password_hash, phone, zip, address, address_number, neighborhood, city) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $name = trim((string)($identity['name'] ?? '')) ?: explode('@', $email)[0];
            $query->execute([mb_substr($name, 0, 120), $email, password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT), '', '', '', '', '', '']);
            $userId = $db->lastInsertId();
        }
        $query = $db->prepare('INSERT INTO google_accounts (google_sub, user_id) VALUES (?, ?)');
        $query->execute([$sub, $userId]);
        $db->commit();
    }
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$userId;
    $destination = ($_SESSION['google_next'] ?? '') === 'checkout' ? 'checkout.php' : 'profile.php';
    unset($_SESSION['google_next']);
    header('Location: ../pages/login.php?google_login=1&next=' . ($destination === 'checkout.php' ? 'checkout' : 'profile'));
    exit;
} catch (Throwable $error) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    error_log('SOLID Google login: ' . $error->getMessage());
    googleFailure('failed');
}
