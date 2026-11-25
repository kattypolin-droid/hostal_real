<?php $title = $heading = 'Huéspedes'; ?>
<div class="toolbar">
  <form class="filters" method="get" action="index.php"><input type="hidden" name="r" value="huespedes"><input
      class="form-control" name="q" value="<?= h($texto) ?>" placeholder="Buscar por DNI o nombre..."><button
      class="btn outline">Buscar</button></form>
  <button class="btn gold" data-open="m-hue" data-reset id="btn-nuevo">+ Registrar huésped</button>
</div>
<div class="card table-wrap table-responsive">
  <table class="table table-hover align-middle mb-0">
    <thead>
      <tr>
        <th>Nombre completo</th>
        <th>Documento</th>
        <th>Nacionalidad</th>
        <th>Teléfono</th>
        <th>Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($filas as $guest): ?>
        <tr>
          <td><b><?= h($guest['nombres_apellidos']) ?></b></td>
          <td><?= h($guest['nombre_tipo'] . ' ' . $guest['numero_documento']) ?></td>
          <td><?= h($guest['nacionalidad'] ?: '—') ?></td>
          <td><?= h($guest['telefono'] ?: '—') ?></td>
          <td class="acc">
            <button class="btn outline xs" data-open="m-hue"
              data-obj="<?= h(json_encode(['id_huesped' => $guest['id_huesped'], 'id_tipo_doc' => $guest['id_tipo_doc'], 'numero_documento' => $guest['numero_documento'], 'nombres_apellidos' => $guest['nombres_apellidos'], 'nacionalidad' => $guest['nacionalidad'] ?? '', 'estado_civil' => $guest['estado_civil'] ?? '', 'lugar_nacimiento' => $guest['lugar_nacimiento'] ?? '', 'fecha_nacimiento' => $guest['fecha_nacimiento'] ?? '', 'telefono' => $guest['telefono'] ?? ''], JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT)) ?>">Editar</button>
            <form method="post" action="<?= h(url('huespedes/eliminar', ['id' => $guest['id_huesped']])) ?>"
              data-confirm="¿Eliminar a <?= h($guest['nombres_apellidos']) ?>?"><button
                class="btn danger xs">Eliminar</button></form>
          </td>
        </tr><?php endforeach;
      if (!$filas): ?>
        <tr>
          <td colspan="5" class="center muted">Huésped no registrado.</td>
        </tr><?php endif; ?>
    </tbody>
  </table>
</div>
<div class="modal" id="m-hue">
  <form class="card pad wide" method="post" action="<?= h(url('huespedes/guardar')) ?>">
    <h3>Huésped <button type="button" class="x" data-close>×</button></h3><input type="hidden" name="id_huesped">
    <label>Nombres y apellidos<input class="form-control" name="nombres_apellidos" required></label>
    <div class="grid2">
      <label>Tipo de documento<select class="form-select" name="id_tipo_doc"><?php foreach ($tipos as $type): ?>
            <option value="<?= (int) $type['id_tipo_doc'] ?>"><?= h($type['nombre_tipo']) ?></option><?php endforeach; ?>
        </select></label>
      <label>N° de documento<input class="form-control" name="numero_documento"
          required></label><label>Nacionalidad<input class="form-control" name="nacionalidad" value="Peruana"></label>
      <label>Estado civil<select class="form-select" name="estado_civil">
          <option value="">Seleccionar</option>
          <?php foreach (['Soltero(a)', 'Casado(a)', 'Conviviente', 'Divorciado(a)', 'Viudo(a)'] as $status): ?>
            <option><?= h($status) ?></option><?php endforeach; ?>
        </select></label>
      <label>Lugar de nacimiento<input class="form-control" name="lugar_nacimiento"></label><label>Fecha de
        nacimiento<input class="form-control" type="date" name="fecha_nacimiento"></label><label>Teléfono<input
          class="form-control" name="telefono"></label>
    </div>
    <div class="actions"><button type="button" class="btn gray" data-close>Cancelar</button><button
        class="btn gold">Guardar huésped</button></div>
  </form>
</div>
<?php if ($nuevo): ?>
  <script>document.getElementById('btn-nuevo').click();</script><?php endif; ?>