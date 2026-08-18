<?php
declare(strict_types=1);

/*
 * A conexão é escolhida automaticamente:
 * - XAMPP local: banco solid, usuário root.
 * - InfinityFree: banco publicado da SØLID.
 */
$serverName = strtolower((string)($_SERVER['SERVER_NAME'] ?? 'localhost'));
$isLocal = in_array($serverName, ['localhost', '127.0.0.1', '::1'], true);

define('DB_HOST', $isLocal ? '127.0.0.1' : 'sql201.infinityfree.com');
define('DB_NAME', $isLocal ? 'solid' : 'if0_42686991_solid');
define('DB_USER', $isLocal ? 'root' : 'if0_42686991');
define('DB_PASSWORD', $isLocal ? '' : 'i20CfsMuZVg');

function database(): PDO
{
    return new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASSWORD,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
}
