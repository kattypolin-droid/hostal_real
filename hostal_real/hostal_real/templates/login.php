<?php $flashes = $_SESSION['flashes'] ?? [];
unset($_SESSION['flashes']); ?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Iniciar sesión · Hostal Real</title>
  <link rel="stylesheet" href="static/bootstrap.min.css">
  <link rel="stylesheet" href="static/style.css">
</head>
<body class="login-page">
  <div class="login-wrap row g-0">
    <div class="login-left col-12 col-lg-6">
      <img class="login-logo" src="static/hostal-real-logo.png" alt="Hostal Real">
      <p class="lema">Tu descanso, nuestro compromiso</p>
      <ul>
        <li>Habitaciones cómodas</li>
        <li>Wi-Fi gratis</li>
        <li>Seguridad total</li>
      </ul>
    </div>
    <form class="login-form col-12 col-lg-6" method="post" action="<?= h(url('login')) ?>">
      <h2>Iniciar sesión</h2>
      <p class="muted">Ingresa tus credenciales para continuar</p>
      <?php foreach ($flashes as [$category, $message]): ?>
        <div class="flash alert alert-<?= $category === 'err' ? 'danger' : 'success' ?> <?= h($category) ?>" role="alert">
          <?= h($message) ?></div><?php endforeach; ?>
      <label>Usuario<input class="form-control" name="usuario" placeholder="Ej.: mrojas" required autofocus
          autocomplete="username"></label>
      <label>Contraseña<input class="form-control" type="password" name="contrasena" placeholder="••••••••" required
          autocomplete="current-password"></label>
      <button class="btn gold block" type="submit">Ingresar</button>
      <p class="muted center small">Acceso solo para personal autorizado</p>
    </form>
  </div>
</body>
</html>