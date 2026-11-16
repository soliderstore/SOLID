<?php
declare(strict_types=1);
require_once __DIR__ . '/../api/config.php';
function catalogProducts(): array {
    $rows = database()->query('SELECT id, data FROM products WHERE active = 1 ORDER BY id')->fetchAll();
    return array_map(function ($row) { $item = json_decode($row['data'], true); $item['id'] = (int)$row['id']; return $item; }, $rows);
}
