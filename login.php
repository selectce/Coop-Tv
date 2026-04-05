<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';

sessionStart();
if (isLoggedIn()) { header('Location: ' . BASE_URL . '/admin/index.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (login(trim($_POST['username']??''), trim($_POST['password']??''))) {
        header('Location: ' . BASE_URL . '/admin/index.php'); exit;
    }
    $error = 'Usuário ou senha incorretos.';
}

$logoDark  = BASE_URL . '/assets/' . BRAND_LOGO_DARK;
$logoLight = BASE_URL . '/assets/' . BRAND_LOGO_LIGHT;
$iconDark  = BASE_URL . '/assets/' . BRAND_ICON_DARK;
$iconLight = BASE_URL . '/assets/' . BRAND_ICON_LIGHT;
$devLogo   = BASE_URL . '/assets/' . DEV_LOGO;
?>
<!DOCTYPE html>
<html lang="pt-BR" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — <?= BRAND_NAME ?></title>
<link id="favicon" rel="icon" type="image/png" href="<?= $iconDark ?>">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script>
(function(){
  var t = localStorage.getItem('cooptv_theme') || 'dark';
  document.documentElement.setAttribute('data-theme', t);
})();
var LOGOS = {
  brand: { dark: <?= json_encode($logoDark) ?>, light: <?= json_encode($logoLight) ?> },
  icon:  { dark: <?= json_encode($iconDark) ?>,  light: <?= json_encode($iconLight) ?> }
};
</script>
</head>
<body class="login-page">

<button class="login-theme-toggle" onclick="toggleTheme()" title="Alternar tema">
  <i class="fa-solid fa-moon" id="iconDark"></i>
  <i class="fa-solid fa-sun"  id="iconLight" style="display:none"></i>
</button>

<div class="login-box">
  <div class="login-logo">
    <img id="brandLogo" src="<?= $logoDark ?>" alt="<?= BRAND_NAME ?>">
    <p class="brand-tagline"><?= BRAND_TAGLINE ?></p>
  </div>

  <?php if ($error): ?>
    <div class="alert alert-error"><i class="fa-solid fa-circle-xmark"></i> <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST" class="login-form">
    <div class="field">
      <label>Usuário</label>
      <div class="input-icon">
        <i class="fa-solid fa-user"></i>
        <input type="text" name="username" required autofocus placeholder="seu usuário">
      </div>
    </div>
    <div class="field">
      <label>Senha</label>
      <div class="input-icon">
        <i class="fa-solid fa-lock"></i>
        <input type="password" name="password" required placeholder="••••••••">
      </div>
    </div>
    <button type="submit" class="btn btn-primary btn-full">
      <i class="fa-solid fa-right-to-bracket"></i> Entrar
    </button>
  </form>

  <div class="login-dev">
    <span>Desenvolvido por</span>
    <a href="<?= DEV_URL ?>" target="_blank">
      <img src="<?= $devLogo ?>" alt="<?= DEV_NAME ?>" class="dev-logo-login">
    </a>
  </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/admin.js"></script>
</body>
</html>
