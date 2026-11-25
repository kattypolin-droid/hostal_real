<?php $title = $heading = 'Habitaciones'; ?>
<div class="toolbar">
  <form class="filters" method="get" action="index.php"><input type="hidden" name="r" value="habitaciones">
    <input class="form-control" name="q" value="<?= h($texto) ?>" placeholder="Buscar por número...">
    <select class="form-select" name="estado" onchange="this.form.submit()">
      <option value="">Estado: Todos</option>
      <?php foreach ($estados as $state): ?>
        <option value="<?= (int) $state['id_estado'] ?>" <?= (int) $estado === (int) $state['id_estado'] ? 'selected' : '' ?>>
          <?= h($state['nombre_estado']) ?></option><?php endforeach; ?>
    </select><button class="btn outline">Buscar</button>
  </form>
  <?php if ($_SESSION['rol'] === ADMIN): ?><button class="btn gold" data-open="m-hab" data-reset>+ Registrar
      habitación</button><?php endif; ?>
</div>
<div class="card table-wrap table-responsive">
  <table class="table table-hover align-middle mb-0">
    <thead>
      <tr>
        <th>N°</th>
        <th>Descripción</th>
        <th>Tarifa</th>
        <th>Estado</th>
        <th>Acciones</th>
      </tr>
    </thead>
    <tbody><?php foreach ($filas as $room): ?>
        <tr>
          <td><b><?= h($room['numero']) ?></b></td>
          <td><?= h($room['descripcion']) ?></td>
          <td><?= h(money($room['tarifa'])) ?></td>
          <td><span class="badge b<?= (int) $room['id_estado'] ?>"><?= h($room['nombre_estado']) ?></span></td>
          <td class="acc">
            <?php if ($_SESSION['rol'] === ADMIN): ?><button class="btn outline xs" data-open="m-hab"
                data-obj="<?= h(json_encode(['id_habitacion' => $room['id_habitacion'], 'numero' => $room['numero'], 'tarifa' => $room['tarifa'], 'descripcion' => $room['descripcion'], 'id_estado' => $room['id_estado']], JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT)) ?>">Editar</button><?php endif; ?>
            <?php if ((int) $room['id_estado'] === EN_LIMPIEZA): ?>
              <form method="post" action="<?= h(url('habitaciones/disponible', ['id' => $room['id_habitacion']])) ?>">
                <button class="btn warn xs">Marcar disponible</button></form><?php endif; ?>
          </td>
        </tr><?php endforeach;
    if (!$filas): ?>
        <tr>
          <td colspan="5" class="center muted">Habitación no registrada.</td>
        </tr><?php endif; ?>
    </tbody>
  </table>
</div>
<p class="muted small">Mostrando <?= count($filas) ?> habitaciones</p>
<?php if ($_SESSION['rol'] === ADMIN): ?>
  <div class="modal" id="m-hab">
    <form class="card pad" method="post" action="<?= h(url('habitaciones/guardar')) ?>">
      <h3>Habitación <button type="button" class="x" data-close>×</button></h3><input type="hidden" name="id_habitacion">
      <div class="grid2"><label>N° de habitación<input class="form-control" name="numero" required></label><label>Tarifa
          (S/)<input class="form-control" name="tarifa" value="50.00" required></label></div>
      <label>Estado inicial<select class="form-select" name="id_estado"><?php foreach ($estados as $state): ?>
            <option value="<?= (int) $state['id_estado'] ?>"><?= h($state['nombre_estado']) ?></option><?php endforeach; ?>
        </select></label>
      <label>Descripción<textarea class="form-control" name="descripcion"
          rows="3">Baño privado, agua caliente, cable, Wi-Fi e intercomunicador</textarea></label>
      <div class="actions"><button type="button" class="btn gray" data-close>Cancelar</button><button
          class="btn gold">Guardar</button></div>
    </form>
  </div><?php endif; ?>