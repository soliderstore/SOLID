<?php
declare(strict_types=1);
require_once __DIR__ . '/../api/config.php';
require_once __DIR__ . '/product-uploads.php';

function catalogImageUrl(string $reference, string $siteRoot): ?string {
    $path = rawurldecode((string) parse_url($reference, PHP_URL_PATH));
    if (preg_match('~^https?://~i', $reference) && filter_var($reference, FILTER_VALIDATE_URL)) {
        $requestHost = parse_url('http://' . ($_SERVER['HTTP_HOST'] ?? ''), PHP_URL_HOST);
        $imageHost = parse_url($reference, PHP_URL_HOST);
        $uploadPrefix = $siteRoot . '/assets/images/uploads/';
        if (!$requestHost || strcasecmp($requestHost, $imageHost) !== 0 || !str_starts_with($path, $uploadPrefix)) {
            return strtr($reference, ['"' => '%22', "'" => '%27', '<' => '%3C', '>' => '%3E']);
        }
    }

    if (!preg_match('~^(?:(?:\.\.?/)*|/(?:[^/]+/)*)assets/images/(products|uploads)/([^./\\\\\x00-\x1F]+\.(?:jpe?g|png|webp|gif))$~iD', $path, $parts)) {
        return null;
    }

    $directory = strtolower($parts[1]);
    $filename = $parts[2];
    $assetPath = '/assets/images/' . $directory . '/';
    $diskPath = $directory === 'uploads' ? PRODUCT_UPLOAD_DIRECTORY . '/' : __DIR__ . '/..' . $assetPath;

    if (!is_file($diskPath . $filename)) {
        static $legacyFiles = [];
        $imageKey = static fn(string $name): string => preg_replace('/\.jpeg$/', '.jpg', strtolower($name));
        if (!isset($legacyFiles[$directory])) {
            $legacyFiles[$directory] = [];
            $files = is_dir($diskPath) ? @scandir($diskPath) : [];
            foreach ($files ?: [] as $file) {
                if (is_file($diskPath . $file)) {
                    $legacyFiles[$directory][$imageKey($file)] = $file;
                }
            }
        }
        $filename = $legacyFiles[$directory][$imageKey($filename)] ?? null;
        if ($filename === null) {
            return null;
        }
    }

    if ($directory === 'uploads' && isProductUploadFilename($filename)) {
        return productUploadUrl($siteRoot, $filename);
    }

    return $siteRoot . $assetPath . rawurlencode($filename);
}

function catalogBundledProductImages(array $product, string $siteRoot): array {
    $sku = strtoupper(trim((string) ($product['sku'] ?? '')));
    $name = strtolower(trim((string) ($product['name'] ?? '')));
    $originalPhotos = match ($sku) {
        'SOLID001' => ['M1.jpeg', 'M2.jpeg'],
        'SOLID002' => ['F1.jpeg', 'F2.jpeg'],
        default => match ($name) {
            'moletom rei dos reis', 'moletom masculino rei dos reis' => ['M1.jpeg', 'M2.jpeg'],
            'moletom feminino rei dos reis' => ['F1.jpeg', 'F2.jpeg'],
            default => [],
        },
    };

    $images = [];
    foreach ($originalPhotos ? [...$originalPhotos, 'estampa.jpeg'] : [] as $filename) {
        $url = catalogImageUrl('assets/images/products/' . $filename, $siteRoot);
        if ($url !== null) {
            $images[] = $url;
        }
    }
    return $images;
}

function catalogProductImages(array $product, string $siteRoot, array $bundledImages): array {
    $images = [];
    foreach ((array) ($product['images'] ?? []) as $reference) {
        $url = is_string($reference) ? catalogImageUrl(trim($reference), $siteRoot) : null;
        if ($url !== null) {
            $images[] = $url;
        }
    }

    $print = $siteRoot . '/assets/images/products/estampa.jpeg';
    if (!$images || $images === [$print]) {
        $images = $bundledImages ?: $images;
    }

    return $images ?: [$siteRoot . '/assets/images/logo/logo.png'];
}

function catalogProducts(string $siteRoot): array {
    $rows = database()->query('SELECT id, data FROM products WHERE active = 1 ORDER BY id')->fetchAll();
    return array_map(function ($row) use ($siteRoot) {
        $item = json_decode($row['data'], true);
        $item['id'] = (int) $row['id'];
        $item['fallbackImages'] = catalogBundledProductImages($item, $siteRoot);
        $item['images'] = catalogProductImages($item, $siteRoot, $item['fallbackImages']);
        return $item;
    }, $rows);
}
