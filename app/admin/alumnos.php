<?php
require_once dirname(__FILE__) . '/../config/conexion.php';
requiere_rol(array('admin'));

$destino = 'admin/alumnos.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigir_csrf($destino);
    $accion = campo_post('accion');
    $id     = (int) campo_post('id');

    if ($accion === 'crear') {
        $error = '';
        $ok = crear_usuario_con_perfil($pdo, array(
            'nombre'    => campo_post('nombre'),
            'apellidos' => campo_post('apellidos'),
            'correo'    => campo_post('correo'),
            'password'  => isset($_POST['password']) ? $_POST['password'] : '',
            'rol'       => 'alumno',
            'telefono'  => campo_post('telefono'),
            'matricula' => campo_post('matricula'),
            'grupo_id'  => (int) campo_post('grupo_id')
        ), $error);
        if ($ok) { flash('success', 'Alumno creado correctamente.'); } else { flash('danger', $error); }

    } elseif ($accion === 'editar') {
        $nombre    = campo_post('nombre');
        $apellidos = campo_post('apellidos');
        $matricula = campo_post('matricula');
        $fecha     = campo_post('fecha_nacimiento');
        $telefono  = campo_post('telefono');
        $grupo_id  = (int) campo_post('grupo_id');

        $st = $pdo->prepare('SELECT COUNT(*) FROM alumnos WHERE matricula = :m AND id <> :id');
        $st->execute(array(':m' => $matricula, ':id' => $id));

        if ($nombre === '' || $apellidos === '' || $matricula === '') {
            flash('danger', 'Nombre, apellidos y matrícula son obligatorios.');
        } elseif ($fecha !== '' && !fecha_valida($fecha)) {
            flash('danger', 'La fecha de nacimiento no es válida.');
        } elseif ($st->fetchColumn() > 0) {
            flash('danger', 'Esa matrícula ya pertenece a otro alumno.');
        } else {
            $pdo->prepare(
                'UPDATE alumnos SET nombre = :n, apellidos = :a, matricula = :m,
                        fecha_nacimiento = :f, telefono = :t, grupo_id = :g
                 WHERE id = :id'
            )->execute(array(
                ':n' => $nombre, ':a' => $apellidos, ':m' => $matricula,
                ':f' => ($fecha !== '' ? $fecha : null),
                ':t' => ($telefono !== '' ? $telefono : null),
                ':g' => ($grupo_id > 0 ? $grupo_id : null),
                ':id' => $id
            ));
            $pdo->prepare(
                'UPDATE usuarios u JOIN alumnos a ON a.usuario_id = u.id
                 SET u.nombre = :n WHERE a.id = :id'
            )->execute(array(':n' => $nombre . ' ' . $apellidos, ':id' => $id));
            flash('success', 'Alumno actualizado.');
        }

    } elseif ($accion === 'eliminar') {
        try {
            $pdo->beginTransaction();
            $st = $pdo->prepare('SELECT usuario_id FROM alumnos WHERE id = :id');
            $st->execute(array(':id' => $id));
            $uid = $st->fetchColumn();
            $pdo->prepare('DELETE FROM alumnos WHERE id = :id')->execute(array(':id' => $id));
            if ($uid) {
                $pdo->prepare('DELETE FROM usuarios WHERE id = :id')->execute(array(':id' => $uid));
            }
            $pdo->commit();
            flash('success', 'Alumno eliminado.');
        } catch (PDOException $ex) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            flash('danger', 'No se pudo eliminar el alumno.');
        }
    }
    redirigir($destino);
}

$grupos = $pdo->query('SELECT id, nombre, grado, ciclo_escolar FROM grupos ORDER BY grado, nombre')->fetchAll();

$editar = null;
if (get_int('editar')) {
    $st = $pdo->prepare('SELECT * FROM alumnos WHERE id = :id');
    $st->execute(array(':id' => get_int('editar')));
    $editar = $st->fetch();
}

$alumnos = $pdo->query(
    'SELECT a.*, u.correo, g.nombre AS grupo_nombre, g.grado
     FROM alumnos a
     LEFT JOIN usuarios u ON u.id = a.usuario_id
     LEFT JOIN grupos g ON g.id = a.grupo_id
     ORDER BY a.apellidos, a.nombre'
)->fetchAll();

$titulo = 'Alumnos';
include dirname(__FILE__) . '/../includes/header.php';
?>

<h3 class="mb-3">Alumnos</h3>

<div class="card mb-4">
    <div class="card-header"><?php echo $editar ? 'Editar alumno' : 'Nuevo alumno'; ?></div>
    <div class="card-body">
        <form method="post" action="alumnos.php" autocomplete="off">
            <?php echo csrf_campo(); ?>
            <input type="hidden" name="accion" value="<?php echo $editar ? 'editar' : 'crear'; ?>">
            <?php if ($editar): ?><input type="hidden" name="id" value="<?php echo (int) $editar['id']; ?>"><?php endif; ?>

            <div class="form-row">
                <div class="form-group col-md-3">
                    <label>Nombre(s)</label>
                    <input type="text" name="nombre" class="form-control" required
                           value="<?php echo e($editar ? $editar['nombre'] : ''); ?>">
                </div>
                <div class="form-group col-md-3">
                    <label>Apellidos</label>
                    <input type="text" name="apellidos" class="form-control" required
                           value="<?php echo e($editar ? $editar['apellidos'] : ''); ?>">
                </div>
                <div class="form-group col-md-3">
                    <label>Matrícula <?php echo $editar ? '' : '<small class="text-muted">(vacía = automática)</small>'; ?></label>
                    <input type="text" name="matricula" class="form-control" maxlength="30"
                           <?php echo $editar ? 'required' : ''; ?>
                           value="<?php echo e($editar ? $editar['matricula'] : ''); ?>">
                </div>
                <div class="form-group col-md-3">
                    <label>Teléfono</label>
                    <input type="text" name="telefono" class="form-control" maxlength="20"
                           value="<?php echo e($editar ? $editar['telefono'] : ''); ?>">
                </div>
            </div>

            <div class="form-row">
                <?php if ($editar): ?>
                    <div class="form-group col-md-3">
                        <label>Fecha de nacimiento</label>
                        <input type="date" name="fecha_nacimiento" class="form-control"
                               value="<?php echo e($editar['fecha_nacimiento']); ?>">
                    </div>
                <?php else: ?>
                    <div class="form-group col-md-3">
                        <label>Correo</label>
                        <input type="email" name="correo" class="form-control" required>
                    </div>
                    <div class="form-group col-md-3">
                        <label>Contraseña</label>
                        <input type="password" name="password" class="form-control" minlength="6" required>
                    </div>
                <?php endif; ?>
                <div class="form-group col-md-3">
                    <label>Grupo</label>
                    <select name="grupo_id" class="form-control">
                        <option value="0">— Sin grupo —</option>
                        <?php foreach ($grupos as $g): ?>
                            <option value="<?php echo (int) $g['id']; ?>"
                                <?php echo ($editar && (int) $editar['grupo_id'] === (int) $g['id']) ? 'selected' : ''; ?>>
                                <?php echo e($g['grado'] . '° ' . $g['nombre'] . ' (' . $g['ciclo_escolar'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <button type="submit" class="btn btn-primary"><?php echo $editar ? 'Guardar cambios' : 'Crear alumno'; ?></button>
            <?php if ($editar): ?><a href="alumnos.php" class="btn btn-secondary">Cancelar</a><?php endif; ?>
        </form>
    </div>
</div>

<div class="table-responsive">
<table class="table table-sm table-bordered bg-white">
    <thead class="thead-light">
        <tr><th>Matrícula</th><th>Alumno</th><th>Correo</th><th>Grupo</th><th>Acciones</th></tr>
    </thead>
    <tbody>
    <?php if (!$alumnos): ?>
        <tr><td colspan="5" class="text-center text-muted">No hay alumnos registrados.</td></tr>
    <?php endif; ?>
    <?php foreach ($alumnos as $a): ?>
        <tr>
            <td><?php echo e($a['matricula']); ?></td>
            <td><?php echo e($a['apellidos'] . ', ' . $a['nombre']); ?></td>
            <td><?php echo e($a['correo']); ?></td>
            <td><?php echo $a['grupo_nombre']
                ? e($a['grado'] . '° ' . $a['grupo_nombre'])
                : '<span class="text-muted">Sin grupo</span>'; ?></td>
            <td>
                <a href="alumnos.php?editar=<?php echo (int) $a['id']; ?>" class="btn btn-sm btn-outline-primary">Editar</a>
                <form method="post" action="alumnos.php" class="d-inline"
                      onsubmit="return confirm('¿Eliminar al alumno, su cuenta y sus calificaciones?');">
                    <?php echo csrf_campo(); ?>
                    <input type="hidden" name="accion" value="eliminar">
                    <input type="hidden" name="id" value="<?php echo (int) $a['id']; ?>">
                    <button class="btn btn-sm btn-outline-danger">Eliminar</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>

<?php include dirname(__FILE__) . '/../includes/footer.php'; ?>
