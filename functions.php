<?php
function formatBytes(int $bytes): string {
    if ($bytes >= 1073741824) return round($bytes / 1073741824, 2) . ' GB';
    if ($bytes >= 1048576)    return round($bytes / 1048576, 2)    . ' MB';
    if ($bytes >= 1024)       return round($bytes / 1024, 2)       . ' KB';
    return $bytes . ' B';
}

function formatDuration(float $seconds): string {
    $h = floor($seconds / 3600);
    $m = floor(($seconds % 3600) / 60);
    $s = floor($seconds % 60);
    if ($h > 0) return sprintf('%d:%02d:%02d', $h, $m, $s);
    return sprintf('%d:%02d', $m, $s);
}

function slug(string $text): string {
    $text = mb_strtolower($text, 'UTF-8');
    $text = preg_replace('/[áàãâä]/u', 'a', $text);
    $text = preg_replace('/[éèêë]/u', 'e', $text);
    $text = preg_replace('/[íìîï]/u', 'i', $text);
    $text = preg_replace('/[óòõôö]/u', 'o', $text);
    $text = preg_replace('/[úùûü]/u', 'u', $text);
    $text = preg_replace('/[ç]/u', 'c', $text);
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', trim($text));
    return $text;
}

function jsonResponse(array $data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function getMediaType(string $mime): string {
    $video = ['video/mp4','video/webm','video/ogg','video/avi','video/quicktime',
              'video/x-msvideo','video/x-matroska','video/x-flv','video/3gpp'];
    $image = ['image/jpeg','image/png','image/gif','image/webp','image/bmp'];
    if (in_array($mime, $video)) return 'video';
    if (in_array($mime, $image)) return 'image';
    return 'unknown';
}

function getAllowedExtensions(): array {
    return ['mp4','webm','ogv','avi','mov','mkv','flv','3gp',
            'jpg','jpeg','png','gif','webp','bmp'];
}

function sanitize(string $str): string {
    return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
}

function activeNav(string $page, string $current): string {
    return $page === $current ? 'active' : '';
}

function storeUrl(array $store): string {
    return BASE_URL . '/player/index.php?store=' . $store['slug'];
}

// Fallbacks de brand caso brand.php não seja carregado
if (!defined('BRAND_NAME'))      define('BRAND_NAME',      'Coop TV');
if (!defined('BRAND_TAGLINE'))   define('BRAND_TAGLINE',   'Sistema de Mídia');
if (!defined('BRAND_LOGO_DARK')) define('BRAND_LOGO_DARK', 'coop-logo.png');
if (!defined('BRAND_LOGO_LIGHT'))define('BRAND_LOGO_LIGHT','coop-logo-blue.png');
if (!defined('BRAND_ICON_DARK')) define('BRAND_ICON_DARK', 'coop-logo.png');
if (!defined('BRAND_ICON_LIGHT'))define('BRAND_ICON_LIGHT','coop-logo-blue.png');
if (!defined('DEV_NAME'))        define('DEV_NAME',        'Coop TV');
if (!defined('DEV_URL'))         define('DEV_URL',         'https://coopdigital.com.br');
if (!defined('DEV_LOGO'))        define('DEV_LOGO',        'coop-logo.png');
