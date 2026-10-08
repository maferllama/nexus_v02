<?php
require_once dirname(__FILE__) . '/../config/conexion.php';
requiere_rol(array('admin'));

$destino = 'admin/profesores.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigir_csrf($destino);
    $accion = campo_post('accion');
    $id     = (int) campo_post('id');

    if ($accion === 'crear') {
        $error = '';
        $ok = crear_usuario_con_perfil($pdo, array(
            'nombre'       => campo_post('nombre'),
            'apellidos'    => campo_post('apellidos'),
            'correo'       => campo_post('correo'),
            'password'     => isset($_POST['password']) ? $_POST['password'] : '',
            'rol'          => 'profesor',
            'telefono'     => campo_post('telefono'),
            'especialidad' => campo_post('especialidad')
        ), $error);
        if ($ok) { flash('success', 'Profesor creado correctamente.'); } else { flash('danger', $error); }

    } elseif ($accion === 'editar') {
        $nombre    = campo_post('nombre');
        $apellidos = campo_post('apellidos');
        $espec     = campo_post('especialidad');
        $telefono  = campo_post('telefono');

        if ($nombre === '' || $apellidos === '') {
            flash('danger', 'Nombre y apellidos son obligatorios.');
        } else {
            $pdo->prepare(
                'UPDATE profesores SET nombre = :n, apellidos = :a, especialidad = :e, telefono = :t WHERE id = :id'
            )->execute(array(
                ':n' => $nombre, ':a' => $apellidos,
                ':e' => ($espec !== '' ? $espec : null),
                ':t' => ($telefono !== '' ? $telefono : null),
                ':id' => $id
            ));
            $pdo->prepare(
                'UPDATE usuarios u JOIN profesores p ON p.usuario_id = u.id
                 SET u.nombre = :n WHERE p.id = :id'
            )->execute(array(':n' => $nombre . ' ' . $apellidos, ':id' => $id));
            flash('success', 'Profesor actualizado.');
        }

    } elseif ($accion === 'eliminar') {
        try {
            $pdo->beginTransaction();
            $st = $pdo->prepare('SELECT usuario_id FROM profesores WHERE id = :id');
            $st->execute(array(':id' => $id));
            $uid = $st->fetchColumn();
            $pdo->prepare('DELETE FROM profesores WHERE id = :id')->execute(array(':id' => $id));
            if ($uid) {
                $pdo->prepare('DELETE FROM usuarios WHERE id = :id')->execute(array(':id' => $uid));
            }
            $pdo->commit();
            flash('success', 'Profesor eliminado.');
        } catch (PDOException $ex) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            flash('danger', 'No se pudo eliminar al profesor.');
        }
    }
    redirigir($destino);
}

$editar = null;
if (get_int('editar')) {
    $st = $pdo->prepare('SELECT * FROM profesores WHERE id = :id');
    $st->execute(array(':id' => get_int('editar')));
    $editar = $st->fetch();
}

$profesores = $pdo->query(
    'SELECT p.*, u.correo,
            (SELECT COUNT(*) FROM grupo_materias gm WHERE gm.profesor_id = p.id) AS asignaciones
     FROM profesores p
     LEFT JOIN usuarios u ON u.id = p.usuario_id
     ORDER BY p.apellidos, p.nombre'
)->fetchAll();

$titulo = 'Profesores';
include dirname(__FILE__) . '/../includes/header.php';
?>

<h3 class="mb-3">Profesores</h3>

<div class="card mb-4">
    <div class="card-header"><?php echo $editar ? 'Editar profesor' : 'Nuevo profesor'; ?></div>
    <div class="card-body">
        <form method="post" action="profesores.php" autocomplete="off">
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
                    <label>Especialidad</label>
                    <input type="text" name="especialidad" class="form-control" maxlength="100"
                           value="<?php echo e($editar ? $editar['especialidad'] : ''); ?>">
                </div>
                <div class="form-group col-md-3">
                    <label>Teléfono</label>
                    <input type="text" name="telefono" class="form-control" maxlength="20"
                           value="<?php echo e($editar ? $editar['telefono'] : ''); ?>">
                </div>
            </div>

            <?php if (!$editar): ?>
            <div class="form-row">
                <div class="form-group col-md-4">
                    <label>Correo</label>
                    <input type="email" name="correo" class="form-control" required>
                </div>
                <div class="form-group col-md-4">
                    <label>Contraseña</label>
                    <input type="password" name="password" class="form-control" minlength="6" required>
                </div>
            </div>
            <?php endif; ?>

            <button type="submit" class="btn btn-primary"><?php echo $editar ? 'Guardar cambios' : 'Crear profesor'; ?></button>
            <?php if ($editar): ?><a href="profesores.php" class="btn btn-secondary">Cancelar</a><?php endif; ?>
        </form>
    </div>
</div>

<div class="table-responsive">
<table class="table table-sm table-bordered bg-white">
    <thead class="thead-light">
        <tr><th>Profesor</th><th>Correo</th><th>Especialidad</th><th>Materias asignadas</th><th>Acciones</th></tr>
    </thead>
    <tbody>
    <?php if (!$profesores): ?>
        <tr><td colspan="5" class="text-center text-muted">No hay profesores registrados.</td></tr>
    <?php endif; ?>
    <?php foreach ($profesores as $p): ?>
        <tr>
            <td><?php echo e($p['apellidos'] . ', ' . $p['nombre']); ?></td>
            <td><?php echo e($p['correo']); ?></td>
            <td><?php echo e($p['especialidad']); ?></td>
            <td><?php echo (int) $p['asignaciones']; ?></td>
            <td>
                <a href="profesores.php?editar=<?php echo (int) $p['id']; ?>" class="btn btn-sm btn-outline-primary">Editar</a>
                <form method="post" action="profesores.php" class="d-inline"
                      onsubmit="return confirm('¿Eliminar al profesor y su cuenta?');">
                    <?php echo csrf_campo(); ?>
                    <input type="hidden" name="accion" value="eliminar">
                    <input type="hidden" name="id" value="<?php echo (int) $p['id']; ?>">
                    <button class="btn btn-sm btn-outline-danger">Eliminar</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>

<?php include dirname(__FILE__) . '/../includes/footer.php'; ?>
