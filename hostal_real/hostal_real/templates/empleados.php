<?php $title = 'Empleados';
$heading = 'Empleados y usuarios'; ?>
<div class="toolbar">
  <form class="filters" method="get" action="index.php"><input type="hidden" name="r" value="empleados"><input
      class="form-control" name="q" value="<?= h($texto) ?>" placeholder="Buscar por nombre o DNI..."><button
      class="btn outline">Buscar</button></form>
  <button class="btn gold" data-open="m-emp" data-reset>+ Registrar empleado</button>
</div>
<div class="card table-wrap table-responsive">
  <table class="table table-hover align-middle mb-0">
    <thead>
      <tr>
        <th>Nombre completo</th>
        <th>DNI</th>
        <th>Cargo</th>
        <th>Usuario</th>
        <th>Rol</th>
        <th>Estado</th>
        <th>Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($filas as $employee): ?>
        <tr>
          <td><b><?= h($employee['nombres'] . ' ' . $employee['apellidos']) ?></b></td>
          <td><?= h($employee['dni']) ?></td>
          <td><?= h($employee['cargo']) ?></td>
          <td><?= h($employee['usuario']) ?></td>
          <td><span class="badge bt"><?= h($employee['nombre_rol']) ?></span></td>
          <td><span
              class="badge <?= $employee['u_estado'] ? 'b1' : 'b2' ?>"><?= $employee['u_estado'] ? 'Activo' : 'Inactivo' ?></span>
          </td>
          <td class="acc">
            <button class="btn outline xs" data-open="m-emp"
              data-obj="<?= h(json_encode(['id_empleado' => $employee['id_empleado'], 'nombres' => $employee['nombres'], 'apellidos' => $employee['apellidos'], 'dni' => $employee['dni'], 'telefono' => $employee['telefono'] ?? '', 'cargo' => $employee['cargo'], 'id_rol' => $employee['id_rol'], 'usuario' => $employee['usuario'], 'estado' => $employee['u_estado']], JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT)) ?>">Editar</button>
            <?php if ((int) $employee['id_empleado'] !== (int) $_SESSION['id_empleado']): ?>
              <form method="post" action="<?= h(url('empleados/eliminar', ['id' => $employee['id_empleado']])) ?>"
                data-confirm="¿Eliminar a <?= h($employee['nombres'] . ' ' . $employee['apellidos']) ?>?"><button
                  class="btn danger xs">Eliminar</button></form><?php endif; ?>
          </td>
        </tr><?php endforeach;
      if (!$filas): ?>
        <tr>
          <td colspan="7" class="center muted">Empleado no registrado.</td>
        </tr><?php endif; ?>
    </tbody>
  </table>
</div>
<div class="modal" id="m-emp">
  <form class="card pad wide" method="post" action="<?= h(url('empleados/guardar')) ?>">
    <h3>Empleado y usuario <button type="button" class="x" data-close>×</button></h3><input type="hidden"
      name="id_empleado">
    <div class="grid2"><label>Nombres<input class="form-control" name="nombres" required></label><label>Apellidos<input
          class="form-control" name="apellidos" required></label><label>DNI<input class="form-control" name="dni"
          maxlength="8" required></label><label>Teléfono<input class="form-control" name="telefono"></label>
      <label>Cargo<select class="form-select" name="cargo">
          <option>Propietaria</option>
          <option selected>Recepcionista</option>
          <option>Limpieza</option>
        </select></label>
      <label>Rol de acceso<select class="form-select" name="id_rol"><?php foreach ($roles as $role): ?>
            <option value="<?= (int) $role['id_rol'] ?>"><?= h($role['nombre_rol']) ?></option><?php endforeach; ?>
        </select></label>
      <label>Usuario<input class="form-control" name="usuario" required
          autocomplete="off"></label><label>Contraseña<input class="form-control" type="password" name="contrasena"
          placeholder="Mínimo 6 caracteres" autocomplete="new-password"></label>
    </div><label class="check"><input type="checkbox" name="estado" value="1" checked> Activo</label>
    <p class="muted small">Al editar, deje la contraseña vacía para mantener la actual.</p>
    <div class="actions"><button type="button" class="btn gray" data-close>Cancelar</button><button
        class="btn gold">Guardar empleado</button></div>
  </form>
</div>