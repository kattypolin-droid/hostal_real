<?php $title = $heading = 'Reportes'; ?>
<div class="tabs">
  <?php foreach (['ocupacion' => 'Ocupación', 'ingresos' => 'Ingresos', 'huespedes' => 'Huéspedes'] as $key => $label): ?>
    <a class="chip <?= $tipo === $key ? 'on' : '' ?>"
      href="<?= h(url('reportes', ['tipo' => $key])) ?>"><?= h($label) ?></a><?php endforeach; ?>
</div>
<form class="filters noprint" method="get" action="index.php"><input type="hidden" name="r" value="reportes"><input
    type="hidden" name="tipo" value="<?= h($tipo) ?>"><input type="hidden" name="generar" value="1">
  <label class="inline">Desde<input class="form-control" type="date" name="desde" value="<?= h($desde) ?>"
      required></label><label class="inline">Hasta<input class="form-control" type="date" name="hasta"
      value="<?= h($hasta) ?>" required></label>
  <?php if ($tipo === 'ingresos'): ?><label class="inline">Método<select class="form-select" name="metodo">
        <option value="">Todos</option><?php foreach ($metodos as $method): ?>
          <option value="<?= (int) $method['id_metodo'] ?>" <?= $metodo === (int) $method['id_metodo'] ? 'selected' : '' ?>>
            <?= h($method['nombre_metodo']) ?></option><?php endforeach; ?>
      </select></label><?php endif; ?>
  <button class="btn teal">Generar reporte</button><?php if ($generado): ?><button type="button" class="btn danger"
      onclick="window.print()">Exportar PDF</button>
    <a class="btn ok"
      href="<?= h(url('reportes/exportar', ['tipo' => $tipo, 'desde' => $desde, 'hasta' => $hasta, 'metodo' => $metodo])) ?>">Descargar
      CSV</a><?php endif; ?>
</form>
<?php if ($generado): ?>
  <div class="card table-wrap table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead>
        <tr><?php foreach ($cols as $column): ?>
            <th><?= h($column) ?></th><?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($filas as $row): ?>
          <tr><?php foreach ($row as $cell): ?>
              <td><?= h($cell) ?></td><?php endforeach; ?>
          </tr><?php endforeach;
        if (!$filas): ?>
          <tr>
            <td colspan="<?= max(count($cols), 1) ?>" class="center muted">No se encontraron resultados.</td>
          </tr><?php endif; ?>
      </tbody>
    </table>
  </div>
  <div class="card pad total"><b><?= h($resumen) ?></b></div>
<?php else: ?>
  <div class="card pad center muted">Seleccione el periodo y presione «Generar reporte».</div><?php endif; ?>