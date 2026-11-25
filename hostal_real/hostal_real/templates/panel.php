<?php $title = $heading = 'Panel principal'; ?>
<div class="stats row g-3">
  <div class="col-12 col-md-4">
    <div class="card stat s1"><b><?= (int) $cont[1] ?></b><span>Habitaciones disponibles</span></div>
  </div>
  <div class="col-12 col-md-4">
    <div class="card stat s2"><b><?= (int) $cont[2] ?></b><span>Habitaciones ocupadas</span></div>
  </div>
  <div class="col-12 col-md-4">
    <div class="card stat s3"><b><?= (int) $cont[3] ?></b><span>En limpieza</span></div>
  </div>
</div>
<div class="chips">
  <button class="chip on" data-f="todas">Todas (<?= count($habs) ?>)</button>
  <button class="chip" data-f="1">Disponibles (<?= (int) $cont[1] ?>)</button>
  <button class="chip" data-f="2">Ocupadas (<?= (int) $cont[2] ?>)</button>
  <button class="chip" data-f="3">En limpieza (<?= (int) $cont[3] ?>)</button>
</div>
<div class="rooms row row-cols-1 row-cols-sm-2 row-cols-xl-5 g-3">
  <?php foreach ($habs as $room): ?>
    <div class="card room e<?= (int) $room['id_estado'] ?>" data-estado="<?= (int) $room['id_estado'] ?>">
      <h3>Hab. <?= h($room['numero']) ?></h3>
      <?php if ((int) $room['id_estado'] === OCUPADA): ?>
        <?php if ($room['huesped']): ?>
          <div class="rn"><?= h($room['huesped']) ?></div><small class="muted">Desde
            <?= h(fdt($room['fecha_ingreso'])) ?></small>
          <small class="muted">Salida: <?= h($room['fecha_salida_prevista'] ? fd($room['fecha_salida_prevista']) : '—') ?> ·
            <?= (int) ($room['noches'] ?: 1) ?>       <?= (int) ($room['noches'] ?: 1) === 1 ? 'noche' : 'noches' ?></small>
        <?php else: ?>
          <div class="rn">Estado ocupado sin estadía activa</div><?php endif; ?>
      <?php else: ?>
        <div class="rn">Tarifa <?= h(money($room['tarifa'])) ?></div><?php endif; ?>
      <span class="badge b<?= (int) $room['id_estado'] ?>"><?= h($room['nombre_estado']) ?></span>
      <?php if ((int) $room['id_estado'] === DISPONIBLE && in_array($_SESSION['rol'], [ADMIN, RECEP], true)): ?>
        <a class="btn gold sm block" href="<?= h(url('estadias/nueva', ['hab' => $room['id_habitacion']])) ?>">Registrar
          ingreso</a>
      <?php elseif ((int) $room['id_estado'] === OCUPADA && $room['id_estadia'] && in_array($_SESSION['rol'], [ADMIN, RECEP], true)): ?>
        <form method="post" action="<?= h(url('estadias/salida', ['id' => $room['id_estadia']])) ?>"
          data-confirm="¿Registrar la salida de la habitación <?= h($room['numero']) ?>?"><button
            class="btn danger sm block">Registrar salida</button></form>
      <?php elseif ((int) $room['id_estado'] === EN_LIMPIEZA): ?>
        <form method="post" action="<?= h(url('habitaciones/disponible', ['id' => $room['id_habitacion']])) ?>"><button
            class="btn warn sm block">Marcar disponible</button></form>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
</div>