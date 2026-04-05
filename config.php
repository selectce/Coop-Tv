<?php
define('DB_HOST',    'localhost');
define('DB_NAME',    'selectce_mstvplayer');
define('DB_USER',    'selectce_mstvplayer');   // <-- preencha
define('DB_PASS',    'MS2026tvplayer?!');     // <-- preencha
define('DB_CHARSET', 'utf8mb4');

define('BASE_URL',    'http://mstvplayer.selectce.com.br/cooptv');
define('UPLOAD_PATH', __DIR__ . '/uploads');
define('MAX_UPLOAD_SIZE', 0);
define('APP_VERSION', '1.1.0');
define('APP_NAME',    'Coop TV');

date_default_timezone_set('America/Sao_Paulo');
ini_set('session.gc_maxlifetime', 86400);
session_set_cookie_params(86400);

// LINHA QUE ESTAVA FALTANDO:
require_once __DIR__ . '/brand.php';
