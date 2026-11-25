<?php $title = $heading = 'Estadías'; ?>
<div class="tabs"><a class="chip" href="<?= h(url('estadias/nueva')) ?>">Registrar ingreso</a>
  <a class="chip <?= $tab === 'activas' ? 'on' : '' ?>" href="<?= h(url('estadias', ['tab' => 'activas'])) ?>">Estadías
    activas (<?= (int) $n_act ?>)</a>
  <a class="chip <?= $tab === 'historial' ? 'on' : '' ?>"
    href="<?= h(url('estadias', ['tab' => 'historial'])) ?>">Historial</a>
</div>
<form class="filters" method="get" action="index.php"><input type="hidden" name="r" value="estadias"><input
    type="hidden" name="tab" value="<?= h($tab) ?>">
  <input class="form-control" name="q" value="<?= h($texto) ?>" placeholder="Buscar huésped o habitación...">
  <?php if ($tab === 'historial'): ?><input class="form-control" type="date" name="desde" value="<?= h($desde) ?>"
      title="Desde"><input class="form-control" type="date" name="hasta" value="<?= h($hasta) ?>"
      title="Hasta"><?php endif; ?>
  <button class="btn outline">Buscar</button>
</form>
<div class="card table-wrap table-responsive">
  <table class="table table-hover align-middle mb-0">
    <thead>
      <tr>
        <th>Huésped</th>
        <th>Habitación</th>
        <th>Ingreso</th>
        <th>Salida estimada</th>
        <th>Noches</th>
        <th>Total</th>
        <th>Pago</th>
        <th>Acción</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($filas as $stay): ?>
        <tr>
          <td><b><?= h($stay['nombres_apellidos']) ?></b></td>
          <td>Hab. <?= h($stay['numero']) ?></td>
          <td><?= h(fdt($stay['fecha_ingreso'])) ?></td>
          <td>
            <?= h($stay['fecha_salida'] ? fdt($stay['fecha_salida']) : ($stay['fecha_salida_prevista'] ? fd($stay['fecha_salida_prevista']) : '—')) ?>
          </td>
          <td><?= (int) ($stay['noches'] ?: 1) ?></td>
          <td><?= h(money($stay['total_acordado'])) ?></td>
          <td><span
              class="badge <?= ($stay['pago_estado'] ?? '') === 'Pagado' ? 'b1' : 'b2' ?>"><?= h($stay['pago_estado'] ?: '—') ?></span>
            <?= h($stay['nombre_metodo'] ?? '') ?>
          </td>
          <td class="acc">
            <?php if (!$stay['fecha_salida']): ?>
              <form method="post" action="<?= h(url('estadias/salida', ['id' => $stay['id_estadia']])) ?>"
                data-confirm="¿Registrar la salida?"><button class="btn danger xs">Registrar salida</button></form>
            <?php endif; ?>
            <?php if ($stay['id_pago']): ?><a class="btn outline xs"
                href="<?= h(url('pagos/comprobante', ['id' => $stay['id_pago']])) ?>">Comprobante</a><?php endif; ?>
          </td>
        </tr>
      <?php endforeach;
      if (!$filas): ?>
        <tr>
          <td colspan="8" class="center muted">Estadía no registrada.</td>
        </tr><?php endif; ?>
    </tbody>
  </table>
</div>