/* Hostal Real - comportamiento de la interfaz */
(function () {
  // --- ventanas modales (registrar / editar) ---
  function abrir(modal, obj, reset) {
    var form = modal.querySelector('form');
    if (reset) { form.reset(); form.querySelectorAll('input[type=hidden]').forEach(function (h) { h.value = ''; }); }
    if (obj) {
      Object.keys(obj).forEach(function (k) {
        var campo = form.elements[k];
        if (!campo) return;
        if (campo.type === 'checkbox') campo.checked = String(obj[k]) === '1';
        else campo.value = obj[k] === null ? '' : obj[k];
      });
      var pass = form.elements['contrasena'];
      if (pass) pass.value = '';
    }
    modal.classList.add('open');
  }
  document.querySelectorAll('[data-open]').forEach(function (b) {
    b.addEventListener('click', function () {
      var modal = document.getElementById(b.dataset.open);
      if (modal) abrir(modal, b.dataset.obj ? JSON.parse(b.dataset.obj) : null, b.hasAttribute('data-reset'));
    });
  });
  document.querySelectorAll('[data-close]').forEach(function (b) {
    b.addEventListener('click', function () { b.closest('.modal').classList.remove('open'); });
  });
  document.querySelectorAll('.modal').forEach(function (m) {
    m.addEventListener('mousedown', function (e) { if (e.target === m) m.classList.remove('open'); });
  });

  // --- confirmación antes de eliminar / registrar salida ---
  document.querySelectorAll('form[data-confirm]').forEach(function (f) {
    f.addEventListener('submit', function (e) { if (!confirm(f.dataset.confirm)) e.preventDefault(); });
  });

  // --- filtros del panel de habitaciones ---
  document.querySelectorAll('.chip[data-f]').forEach(function (chip) {
    chip.addEventListener('click', function () {
      document.querySelectorAll('.chip[data-f]').forEach(function (c) { c.classList.remove('on'); });
      chip.classList.add('on');
      document.querySelectorAll('.room').forEach(function (r) {
        r.style.display = (chip.dataset.f === 'todas' || r.dataset.estado === chip.dataset.f) ? '' : 'none';
      });
    });
  });
})();
