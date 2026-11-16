<?php
require_once __DIR__ . '/../lib/catalog.php';
header('Content-Type: application/javascript; charset=utf-8');
header('Cache-Control: no-cache');
try { $products = catalogProducts(); } catch (PDOException $e) { error_log($e->getMessage()); http_response_code(503); $products = []; }
echo 'const PRODUCTS = ' . json_encode($products, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) . ';';
