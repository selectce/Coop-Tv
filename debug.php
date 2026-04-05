<?php
// ARQUIVO TEMPORÁRIO DE DIAGNÓSTICO
// Acesse: http://seusite.com/cooptv/debug.php
// DELETE após resolver o problema!

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<pre style='font-family:monospace;font-size:13px;padding:20px'>";
echo "<h2>🔍 Diagnóstico Coop TV</h2>";

// 1. Teste config.php
echo "\n--- config.php ---\n";
try {
    require_once __DIR__ . '/config.php';
    echo "✅ config.php carregado\n";
    echo "BASE_URL:    " . BASE_URL . "\n";
    echo "DB_HOST:     " . DB_HOST . "\n";
    echo "DB_NAME:     " . DB_NAME . "\n";
    echo "BRAND_NAME:  " . (defined('BRAND_NAME') ? BRAND_NAME : '❌ NÃO DEFINIDO') . "\n";
} catch(Throwable $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . " linha " . $e->getLine() . "\n";
}

// 2. Teste banco
echo "\n--- Banco de Dados ---\n";
try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4", DB_USER, DB_PASS);
    echo "✅ Conexão OK\n";
    $t = $pdo->query("SELECT COUNT(*) FROM stores")->fetchColumn();
    echo "Pontos cadastrados: $t\n";
} catch(Throwable $e) {
    echo "❌ ERRO BD: " . $e->getMessage() . "\n";
}

// 3. Pastas uploads
echo "\n--- Pastas ---\n";
$dirs = ['uploads','uploads/videos','uploads/images','uploads/thumbs'];
foreach ($dirs as $d) {
    $path = __DIR__ . '/' . $d;
    $ok   = is_dir($path);
    $wr   = $ok && is_writable($path);
    echo ($ok ? "✅" : "❌") . " $d " . ($wr ? "(gravável)" : "(SEM PERMISSÃO DE ESCRITA)") . "\n";
}

// 4. Arquivos de marca
echo "\n--- Assets de Marca ---\n";
$assets = ['ms-logo-white.png','ms-logo-color.png','ms-icon-white.png','ms-icon-color.png','coop-logo.png'];
foreach ($assets as $a) {
    $exists = file_exists(__DIR__ . '/assets/' . $a);
    echo ($exists ? "✅" : "❌") . " assets/$a\n";
}

echo "\n--- PHP ---\n";
echo "Versão: " . PHP_VERSION . "\n";
echo "PDO MySQL: " . (extension_loaded('pdo_mysql') ? '✅' : '❌') . "\n";
echo "finfo: "     . (class_exists('finfo') ? '✅' : '❌') . "\n";
echo "GD: "        . (extension_loaded('gd') ? '✅' : '❌') . "\n";
echo "ffmpeg: "    . (shell_exec('which ffmpeg 2>/dev/null') ? '✅' : 'não instalado (ok)') . "\n";

echo "</pre>";
echo "<p style='color:red;font-weight:bold;padding:20px'>⚠️ DELETE este arquivo (debug.php) após o diagnóstico!</p>";
