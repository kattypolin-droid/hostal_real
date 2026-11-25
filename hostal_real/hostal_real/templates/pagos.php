<?php $title = $heading = 'Pagos'; ?>
<form class="filters" method="get" action="index.php"><input type="hidden" name="r" value="pagos">
  <label class="inline">Desde<input class="form-control" type="date" name="desde"
      value="<?= h($desde) ?>"></label><label class="inline">Hasta<input class="form-control" type="date" name="hasta"
      value="<?= h($hasta) ?>"></label>
  <label class="inline">Método<select class="form-select" name="metodo">
      <option value="">Todos</option><?php foreach ($metodos as $method): ?>
        <option value="<?= (int) $method['id_metodo'] ?>" <?= (int) $metodo === (int) $method['id_metodo'] ? 'selected' : '' ?>>
          <?= h($method['nombre_metodo']) ?></option><?php endforeach; ?>
    </select></label>
  <input class="form-control" name="q" value="<?= h($texto) ?>" placeholder="Huésped o N° de comprobante..."><button
    class="btn outline">Buscar</button>
</form>
<?php if ($pendientes): ?>
  <div class="card pad" style="margin-bottom:14px">
    <div class="toolbar">
      <div><b>Pagos pendientes por estadía</b><br><span class="muted small">Seleccione una estadía y registre el siguiente
          pago.</span></div>
    </div>
    <div class="table-wrap table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead>
          <tr>
            <th>Huésped</th>
            <th>Hab.</th>
            <th>Total</th>
            <th>Pagado</th>
            <th>Saldo</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($pendientes as $pending): ?>
            <tr>
              <td><b><?= h($pending['nombres_apellidos']) ?></b></td>
              <td>Hab. <?= h($pending['numero']) ?></td>
              <td><?= h(money($pending['total_acordado'])) ?></td>
              <td><?= h(money($pending['pagado'])) ?></td>
              <td><b><?= h(money($pending['saldo'])) ?></b></td>
              <td><button type="button" class="btn gold xs" data-open="m-pago"
                  data-obj="<?= h(json_encode(['id_estadia' => $pending['id_estadia'], 'saldo' => $pending['saldo']], JSON_HEX_APOS | JSON_HEX_QUOT)) ?>">Registrar
                  pago</button></td>
            </tr><?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div><?php endif; ?>
<div class="card table-wrap table-responsive">
  <table class="table table-hover align-middle mb-0">
    <thead>
      <tr>
        <th>Fecha y hora</th>
        <th>Huésped</th>
        <th>Hab.</th>
        <th>Monto</th>
        <th>Método</th>
        <th>N° comprobante</th>
        <th>Estado</th>
        <th>Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($filas as $payment): ?>
        <tr>
          <td><?= h(fdt($payment['fecha_pago'])) ?></td>
          <td><b><?= h($payment['nombres_apellidos']) ?></b></td>
          <td><?= h($payment['numero']) ?></td>
          <td><?= h(money($payment['monto'])) ?></td>
          <td><span
              class="badge <?= $payment['nombre_metodo'] === 'Yape' ? 'by' : 'b1' ?>"><?= h($payment['nombre_metodo']) ?></span>
          </td>
          <td><?= h($payment['numero_comprobante']) ?></td>
          <td><span class="badge <?= $payment['estado'] === 'Pagado' ? 'b1' : 'b2' ?>"><?= h($payment['estado']) ?></span>
          </td>
          <td class="acc">
            <a class="btn outline xs" href="<?= h(url('pagos/comprobante', ['id' => $payment['id_pago']])) ?>">Ver</a>
            <?php if ($_SESSION['rol'] === ADMIN && $payment['estado'] === 'Pagado'): ?><button class="btn danger xs"
                data-open="m-anular"
                data-obj="<?= h(json_encode(['idp' => $payment['id_pago'], 'numero' => $payment['numero_comprobante']], JSON_HEX_APOS | JSON_HEX_QUOT)) ?>">Anular</button><?php endif; ?>
          </td>
        </tr><?php endforeach;
      if (!$filas): ?>
        <tr>
          <td colspan="8" class="center muted">Pago no registrado.</td>
        </tr><?php endif; ?>
    </tbody>
  </table>
</div>
<div class="card pad total">Total del periodo (pagos vigentes): <b><?= h(money($total)) ?></b></div>
<?php if ($pendientes): ?>
  <div class="modal" id="m-pago">
    <form class="card pad" method="post" action="<?= h(url('pagos/registrar')) ?>">
      <h3>Registrar pago adicional <button type="button" class="x" data-close>×</button></h3><input type="hidden"
        name="id_estadia" id="pago-estadia">
      <p class="muted">Saldo pendiente: <b id="pago-saldo"></b></p>
      <div class="grid2"><label>Monto (S/) <input class="form-control" type="number" name="monto" id="pago-monto"
            min="0.01" step="0.01" required></label>
        <label>Método de pago<select class="form-select" name="id_metodo" required><?php foreach ($metodos as $method): ?>
              <option value="<?= (int) $method['id_metodo'] ?>"><?= h($method['nombre_metodo']) ?></option>
            <?php endforeach; ?>
          </select></label>
      </div>
      <div class="actions"><button type="button" class="btn gray" data-close>Cancelar</button><button
          class="btn gold">Registrar pago</button></div>
    </form>
  </div><?php $scripts = <<<'HTML'
<script>
document.querySelectorAll('[data-open="m-pago"]').forEach(function(b){b.addEventListener('click',function(){var o=JSON.parse(b.dataset.obj);document.getElementById('pago-estadia').value=o.id_estadia;document.getElementById('pago-saldo').textContent='S/ '+Number(o.saldo).toFixed(2);document.getElementById('pago-monto').max=o.saldo})});
</script>
HTML;
endif; ?>
<?php if ($_SESSION['rol'] === ADMIN): ?>
  <div class="modal" id="m-anular">
    <form class="card pad" method="post" id="f-anular">
      <h3>Anular pago <button type="button" class="x" data-close>×</button></h3>
      <p class="muted">Comprobante <b id="an-num"></b>. Esta acción pide un motivo y no se puede deshacer.</p>
      <label>Motivo de la anulación<textarea class="form-control" name="motivo" rows="3" required></textarea></label>
      <div class="actions"><button type="button" class="btn gray" data-close>Cancelar</button><button
          class="btn danger">Anular pago</button></div>
    </form>
  </div>
  <?php $scripts = ($scripts ?? '') . '<script>document.querySelectorAll(\'[data-open="m-anular"]\').forEach(function(b){b.addEventListener("click",function(){var o=JSON.parse(b.dataset.obj);document.getElementById("an-num").textContent=o.numero;document.getElementById("f-anular").action=' . json_encode(url('pagos/anular')) . '+ "&id="+o.idp})});</script>'; endif; ?>