<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$currentPage = $currentPage ?? 'dashboard';
$pageTitle   = $pageTitle   ?? BRAND_NAME;
$logoDark    = BASE_URL . '/assets/' . BRAND_LOGO_DARK;
$logoLight   = BASE_URL . '/assets/' . BRAND_LOGO_LIGHT;
$iconDarkUrl = BASE_URL . '/assets/' . BRAND_ICON_DARK;
$iconLightUrl= BASE_URL . '/assets/' . BRAND_ICON_LIGHT;
$devLogoUrl  = BASE_URL . '/assets/' . DEV_LOGO;
?>
<!DOCTYPE html>
<html lang="pt-BR" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= sanitize($pageTitle) ?> — <?= BRAND_NAME ?></title>
<link id="favicon" rel="icon" type="image/png" href="<?= $iconDarkUrl ?>">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script>
// Aplicar tema ANTES de renderizar (sem flash)
(function(){
  var t = localStorage.getItem('cooptv_theme') || 'dark';
  document.documentElement.setAttribute('data-theme', t);
})();
// Dados das logos para o JS
var LOGOS = {
  brand: { dark: <?= json_encode($logoDark) ?>, light: <?= json_encode($logoLight) ?> },
  icon:  { dark: <?= json_encode($iconDarkUrl) ?>, light: <?= json_encode($iconLightUrl) ?> }
};
</script>
</head>
<body>

<div class="layout">
  <aside class="sidebar">
    <div class="sidebar-brand">
      <!-- UMA só imagem — JS troca o src conforme o tema -->
      <img id="brandLogo" src="<?= $logoDark ?>" alt="<?= BRAND_NAME ?>">
    </div>

    <nav class="sidebar-nav">
      <a href="<?= BASE_URL ?>/admin/index.php"    class="nav-item <?= activeNav('dashboard',$currentPage) ?>">
        <i class="fa-solid fa-gauge"></i><span>Dashboard</span>
      </a>
      <a href="<?= BASE_URL ?>/admin/stores.php"   class="nav-item <?= activeNav('stores',$currentPage) ?>">
        <i class="fa-solid fa-tv"></i><span>Pontos</span>
      </a>
      <a href="<?= BASE_URL ?>/admin/media.php"    class="nav-item <?= activeNav('media',$currentPage) ?>">
        <i class="fa-solid fa-photo-film"></i><span>Mídias</span>
      </a>
      <a href="<?= BASE_URL ?>/admin/timeline.php" class="nav-item <?= activeNav('timeline',$currentPage) ?>">
        <i class="fa-solid fa-film"></i><span>Timeline</span>
      </a>
      <a href="<?= BASE_URL ?>/admin/reports.php"  class="nav-item <?= activeNav('reports',$currentPage) ?>">
        <i class="fa-solid fa-chart-bar"></i><span>Relatórios</span>
      </a>
      <a href="<?= BASE_URL ?>/admin/users.php"    class="nav-item <?= activeNav('users',$currentPage) ?>">
        <i class="fa-solid fa-users"></i><span>Usuários</span>
      </a>
    </nav>

    <div class="sidebar-footer">
      <div class="footer-user">
        <i class="fa-solid fa-circle-user"></i>
        <span><?= sanitize($_SESSION['username']) ?></span>
      </div>
      <a href="<?= BASE_URL ?>/logout.php" title="Sair" class="footer-logout">
        <i class="fa-solid fa-right-from-bracket"></i>
      </a>
    </div>

    <div class="sidebar-dev">
      <a href="<?= DEV_URL ?>" target="_blank" title="<?= DEV_NAME ?>">
        <img src="<?= $devLogoUrl ?>" alt="<?= DEV_NAME ?>" class="dev-logo">
        <span><?= DEV_NAME ?></span>
      </a>
    </div>
  </aside>

  <main class="main">
    <div class="topbar">
      <h1 class="page-title"><?= sanitize($pageTitle) ?></h1>
      <div class="topbar-right">
        <button class="theme-toggle" title="Alternar tema claro/escuro" onclick="toggleTheme()">
          <i class="fa-solid fa-moon"  id="iconDark"></i>
          <i class="fa-solid fa-sun"   id="iconLight" style="display:none"></i>
        </button>
        <span class="topbar-version">v<?= APP_VERSION ?></span>
      </div>
    </div>
    <div class="content">
