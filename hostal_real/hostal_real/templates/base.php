<?php
$title = $title ?? 'Hostal Real';
$heading = $heading ?? '';
$menu = [
    ['panel', 'Inicio', [ADMIN, RECEP, LIMP]],
    ['habitaciones', 'Habitaciones', [ADMIN, RECEP, LIMP]],
    ['huespedes', 'Huéspedes', [ADMIN, RECEP]],
    ['estadias', 'Estadías', [ADMIN, RECEP]],
    ['pagos', 'Pagos', [ADMIN, RECEP]],
    ['empleados', 'Empleados', [ADMIN]],
    ['reportes', 'Reportes', [ADMIN]]
];
$date = new DateTimeImmutable('today');
$weekdays = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
$months = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
$dateLabel = ucfirst($weekdays[(int)$date->format('w')]) . ', ' . $date->format('j') . ' de ' . $months[(int)$date->format('n') - 1] . ' de ' . $date->format('Y');
$name = (string)($_SESSION['nombre'] ?? '');
$initials = '';
foreach (array_slice(explode(' ', $name), 0, 2) as $part) if ($part !== '') $initials .= mb_substr($part, 0, 1, 'UTF-8');
$flashes = $_SESSION['flashes'] ?? [];
unset($_SESSION['flashes']);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= h($title) ?> · Hostal Real</title>
  <link rel="stylesheet" href="static/bootstrap.min.css">
  <link rel="stylesheet" href="static/style.css">
</head>
<body>
  <aside class="side" id="side">
    <div class="brand"><div class="brand-copy"><img class="brand-logo" src="static/hostal-real-logo.png" alt="Hostal Real"><small>Gestión hotelera</small></div></div>
    <nav>
      <?php foreach ($menu as [$itemRoute, $label, $roles]): if (in_array($_SESSION['rol'] ?? '', $roles, true)): ?>
      <a href="<?= h(url($itemRoute)) ?>" class="<?= ($route === $itemRoute || ($itemRoute === 'estadias' && str_starts_with($route, 'estadias/'))) ? 'on' : '' ?>"><?= h($label) ?></a>
      <?php endif; endforeach; ?>
    </nav>
    <a class="logout" href="<?= h(url('logout')) ?>">Cerrar sesión</a>
  </aside>
  <main>
    <header class="top">
      <button class="burger" type="button" onclick="document.getElementById('side').classList.toggle('open')">☰</button>
      <h1><?= h($heading) ?></h1>
      <div class="who"><span class="fecha"><?= h($dateLabel) ?></span><div class="avatar"><?= h($initials) ?></div><div><b><?= h($name) ?></b><small><?= h($_SESSION['rol'] ?? '') ?></small></div></div>
    </header>
    <section class="content container-fluid">
      <?php foreach ($flashes as [$category, $message]): ?><div class="flash alert alert-<?= $category === 'err' ? 'danger' : 'success' ?> <?= h($category) ?>" role="alert"><?= h($message) ?></div><?php endforeach; ?>
      <?= $content ?>
    </section>
  </main>
  <script src="static/app.js"></script>
  <?php if (!empty($scripts)) echo $scripts; ?>
</body>
</html>
