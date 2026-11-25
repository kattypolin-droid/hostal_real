<?php $title = $heading = 'Registrar ingreso'; ?>
<form method="post" action="<?= h(url('estadias/guardar')) ?>" id="f-ingreso">
  <div class="stay-grid row g-3">
    <div class="col-12 col-xl-4"><div class="card pad h-100"><h3>1. Huésped</h3><label>Buscar por DNI o nombre<input class="form-control" name="huesped" list="lista-huespedes" placeholder="Escriba y elija de la lista" required autocomplete="off"></label>
      <datalist id="lista-huespedes"><?php foreach ($hus as $guest): ?><option value="<?= h($guest['numero_documento'] . ' - ' . $guest['nombres_apellidos']) ?>"><?= h($guest['nombre_tipo']) ?></option><?php endforeach; ?></datalist>
      <p class="muted small">¿No está registrado? <a href="<?= h(url('huespedes', ['nuevo' => 1])) ?>"><b>+ Registrar nuevo huésped</b></a></p></div></div>
    <div class="col-12 col-xl-4"><div class="card pad h-100"><h3>2. Habitación</h3><label>Habitación disponible<select class="form-select" name="id_habitacion" id="sel-hab" required><option value="">Seleccionar</option>
      <?php foreach ($disp as $room): ?><option value="<?= (int)$room['id_habitacion'] ?>" data-tarifa="<?= h($room['tarifa']) ?>" <?= $sel === (int)$room['id_habitacion'] ? 'selected' : '' ?>>Hab. <?= h($room['numero']) ?> – Disponible</option><?php endforeach; ?></select></label><p class="muted small">Tarifa</p><div class="tarifa" id="tarifa">S/ 0.00</div></div></div>
    <div class="col-12 col-xl-4"><div class="card pad h-100"><h3>3. Pago al ingresar</h3><div class="grid2">
      <label>Noches de hospedaje<input class="form-control" type="number" name="noches" id="noches" min="1" max="365" value="1" required></label>
      <label>Forma de pago<select class="form-select" name="modalidad_pago" id="modalidad"><option value="Pago único">Pago único</option><option value="Pagos separados">Pagos separados</option></select></label></div>
      <label>Método de pago<select class="form-select" name="id_metodo" id="sel-metodo" required><?php foreach ($metodos as $method): ?><option value="<?= (int)$method['id_metodo'] ?>"><?= h($method['nombre_metodo']) ?></option><?php endforeach; ?></select></label>
      <label><span id="etiqueta-monto">Monto recibido (S/)</span><input class="form-control" id="recibido" name="monto_inicial" value="" inputmode="decimal" placeholder="0.00"></label>
      <div class="resumen"><span>Estadía acordada</span><b id="noches-resumen">1 noche</b><span>Salida estimada</span><b id="salida-estimada">—</b><span>Total a pagar</span><b id="total">S/ 0.00</b><span id="etiqueta-saldo">Vuelto</span><b id="vuelto">S/ 0.00</b></div>
      <button type="button" class="btn teal block" id="btn-pago">Registrar pago y emitir comprobante</button><p class="muted small" id="msg-pago"></p>
    </div></div>
  </div>
  <div class="actions bar"><span class="muted small">El ingreso se habilita cuando el pago está registrado.</span><a class="btn gray" href="<?= h(url('panel')) ?>">Cancelar</a><button class="btn gold" id="btn-guardar" disabled>Guardar ingreso</button></div>
</form>
<?php $scripts = <<<'HTML'
<script>
(function () {
  var sel=document.getElementById('sel-hab'), tarifa=document.getElementById('tarifa'), total=document.getElementById('total'), vuelto=document.getElementById('vuelto'), rec=document.getElementById('recibido'), msg=document.getElementById('msg-pago'), guardar=document.getElementById('btn-guardar'), noches=document.getElementById('noches'), modalidad=document.getElementById('modalidad');
  function soles(n){return 'S/ '+(n||0).toFixed(2)}
  function t(){var o=sel.options[sel.selectedIndex];return o&&o.dataset.tarifa?parseFloat(o.dataset.tarifa):0}
  function n(){return Math.max(parseInt(noches.value,10)||1,1)}
  function reset(){guardar.disabled=true;msg.textContent=''}
  function actualizar(){var nights=n(),sum=t()*nights,separated=modalidad.value==='Pagos separados';tarifa.textContent=soles(t());total.textContent=soles(sum);document.getElementById('noches-resumen').textContent=nights+(nights===1?' noche':' noches');var departure=new Date();departure.setDate(departure.getDate()+nights);document.getElementById('salida-estimada').textContent=departure.toLocaleDateString('es-PE');document.getElementById('etiqueta-monto').textContent=separated?'Primer pago (S/)':'Monto recibido (S/)';document.getElementById('etiqueta-saldo').textContent=separated?'Saldo pendiente':'Vuelto';if(separated){if(parseFloat((rec.value||'0').replace(',','.'))>=sum)rec.value='';rec.placeholder='Monto inicial'}else{rec.value=sum?sum.toFixed(2):'';rec.placeholder='0.00'}var received=parseFloat((rec.value||'0').replace(',','.'))||0;vuelto.textContent=soles(separated?Math.max(sum-received,0):Math.max(received-sum,0));reset()}
  sel.addEventListener('change',actualizar);rec.addEventListener('input',actualizar);noches.addEventListener('input',actualizar);modalidad.addEventListener('change',actualizar);document.getElementById('sel-metodo').addEventListener('change',reset);
  document.getElementById('btn-pago').addEventListener('click',function(){var received=parseFloat((rec.value||'0').replace(',','.'))||0,sum=t()*n(),separated=modalidad.value==='Pagos separados';if(!sel.value){msg.textContent='Seleccione una habitación.';return}if(received<=0){msg.textContent='Ingrese un monto válido.';return}if(separated&&received>=sum){msg.textContent='Para pagos separados, el primer pago debe ser menor al total.';return}if(!separated&&received<sum){msg.textContent='El monto recibido es menor al total de la estadía.';return}guardar.disabled=false;msg.textContent='✔ Pago registrado. Ya puede guardar el ingreso.'});
  actualizar();
})();
</script>
HTML;
?>
