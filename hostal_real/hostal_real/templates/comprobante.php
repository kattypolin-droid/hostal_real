<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Comprobante <?= h($p['numero_comprobante']) ?> · Hostal Real</title>
  <link rel="stylesheet" href="static/bootstrap.min.css">
  <link rel="stylesheet" href="static/style.css">
</head>
<body class="comp-page">
  <main class="receipt">
    <header class="receipt-head">
      <img src="static/hostal-real-logo.png" alt="Hostal Real">
      <p>Av. Los Eucaliptos Mz. Q Lote 5, Chaclacayo</p>
      <p>Tel. (01) 359-0907</p>
    </header>

    <div class="receipt-body">
      <div class="receipt-title">
        <div>
          <p class="receipt-kicker">HOSTAL REAL</p>
          <h1>Comprobante de pago</h1>
        </div>
        <span class="receipt-status <?= $p['estado'] === 'Anulado' ? 'void' : 'paid' ?>">
          <?= $p['estado'] === 'Anulado' ? 'ANULADO' : 'PAGADO' ?>
        </span>
      </div>

      <div class="receipt-number">
        <span>Número de comprobante</span>
        <strong><?= h($p['numero_comprobante']) ?></strong>
      </div>

      <section class="receipt-section">
        <h2>Datos del huésped</h2>
        <dl class="receipt-list">
          <div><dt>Nombre completo</dt><dd><?= h($p['nombres_apellidos']) ?></dd></div>
          <div><dt>Documento</dt><dd><?= h($p['nombre_tipo'] . ' ' . $p['numero_documento']) ?></dd></div>
        </dl>
      </section>

      <section class="receipt-section">
        <h2>Datos de la estadía</h2>
        <dl class="receipt-list">
          <div><dt>Habitación</dt><dd><?= h($p['numero']) ?></dd></div>
          <div><dt>Fecha de ingreso</dt><dd><?= h(fdt($p['fecha_ingreso'])) ?></dd></div>
          <div><dt>Duración</dt><dd><?= (int)$p['noches'] ?> <?= (int)$p['noches'] === 1 ? 'noche' : 'noches' ?></dd></div>
          <div><dt>Salida estimada</dt><dd><?= h($p['fecha_salida_prevista'] ? fd($p['fecha_salida_prevista']) : '—') ?></dd></div>
          <div><dt>Modalidad de pago</dt><dd><?= h($p['modalidad_pago']) ?></dd></div>
        </dl>
      </section>

      <section class="receipt-section">
        <h2>Detalle del pago</h2>
        <dl class="receipt-list">
          <div><dt>Fecha y hora</dt><dd><?= h(fdt($p['fecha_pago'])) ?></dd></div>
          <div><dt>Método de pago</dt><dd><?= h($p['nombre_metodo']) ?></dd></div>
          <div><dt>Atendido por</dt><dd><?= h($p['atendio']) ?></dd></div>
        </dl>
      </section>

      <?php if ($p['motivo_anulacion']): ?>
      <section class="receipt-cancellation">
        <h2>Motivo de anulación</h2>
        <p><?= h($p['motivo_anulacion']) ?></p>
      </section>
      <?php endif; ?>

      <section class="receipt-summary" aria-label="Resumen de importes">
        <div class="receipt-summary-row"><span>Total de la estadía</span><strong><?= h(money($p['total_acordado'])) ?></strong></div>
        <div class="receipt-summary-row"><span>Pagado a la fecha</span><strong><?= h(money($pagado)) ?></strong></div>
        <div class="receipt-summary-row"><span>Saldo pendiente</span><strong><?= h(money($saldo)) ?></strong></div>
        <div class="receipt-current-payment"><span>Monto de este comprobante</span><strong><?= h(money($p['monto'])) ?></strong></div>
      </section>

      <p class="receipt-thanks">Gracias por su preferencia</p>
      <div class="receipt-actions noprint">
        <a class="btn gray" href="<?= h(url('panel')) ?>">Volver</a>
        <button class="btn gold" type="button" onclick="window.print()">Imprimir comprobante</button>
      </div>
    </div>
  </main>
</body>
</html>
