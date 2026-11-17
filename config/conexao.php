<?php
declare(strict_types=1);
// Production configuration belongs in the environment or an ignored local file.
$localFile = __DIR__.'/database.local.php';
$local = is_file($localFile) ? require $localFile : [];
function database(): PDO {
 global $local;
 $host=getenv('DB_HOST') ?: ($local['host'] ?? '127.0.0.1');
 $name=getenv('DB_NAME') ?: ($local['name'] ?? 'solid');
 $user=getenv('DB_USER') ?: ($local['user'] ?? 'root');
 $password=getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : ($local['password'] ?? '');
 return new PDO("mysql:host=$host;dbname=$name;charset=utf8mb4",$user,$password,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
}
