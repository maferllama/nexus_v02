<?php
require_once dirname(__FILE__) . '/../config/conexion.php';
requiere_rol(array('admin'));

$destino = 'admin/usuarios.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigir_csrf($destino);
    $accion = campo_post('accion');
    $id     = (int) campo_post('id');
    $soy_yo = ($id === (int) $_SESSION['usuario_id']);

    if ($accion === 'crear') {
        $error = '';
        $ok = crear_usuario_con_perfil($pdo, array(
            'nombre'    => campo_post('nombre'),
            'apellidos' => campo_post('apellidos'),
            'correo'    => campo_post('correo'),
            'password'  => isset($_POST['password']) ? $_POST['password'] : '',
            'rol'       => campo_post('rol')
        ), $error);
        if ($ok) {
            flash('success', 'Usuario creado correctamente.');
        } else {
            flash('danger', $error);
        }
    } elseif ($accion === 'toggle') {
        if ($soy_yo) {
            flash('warning', 'No puedes desactivar tu propia cuenta.');
        } else {
            $pdo->prepare('UPDATE usuarios SET activo = 1 - activo WHERE id = :id')->execute(array(':id' => $id));
            flash('success', 'Estado del usuario actualizado.');
        }
    } elseif ($accion === 'password') {
        $nueva = isset($_POST['nueva']) ? $_POST['nueva'] : '';
        if (strlen($nueva) < 6) {
            flash('danger', 'La nueva contraseña debe tener al menos 6 caracteres.');
        } else {
            $pdo->prepare('UPDATE usuarios SET password = :p WHERE id = :id')
                ->execute(array(':p' => password_hash($nueva, PASSWORD_DEFAULT), ':id' => $id));
            flash('success', 'Contraseña actualizada.');
        }
    } elseif ($accion === 'eliminar') {
        if ($soy_yo) {
            flash('warning', 'No puedes eliminar tu propia cuenta.');
        } else {
            try {
                $pdo->beginTransaction();
                $pdo->prepare('DELETE FROM alumnos WHERE usuario_id = :id')->execute(array(':id' => $id));
                $pdo->prepare('DELETE FROM profesores WHERE usuario_id = :id')->execute(array(':id' => $id));
                $pdo->prepare('DELETE FROM usuarios WHERE id = :id')->execute(array(':id' => $id));
                $pdo->commit();
                flash('success', 'Usuario eliminado.');
            } catch (PDOException $ex) {
                if ($pdo->inTransaction()) { $pdo->rollBack(); }
                flash('danger', 'No se pudo eliminar el usuario.');
            }
        }
    }
    redirigir($destino);
}

$usuarios = $pdo->query('SELECT id, nombre, correo, rol, activo, created_at FROM usuarios ORDER BY id')->fetchAll();
$colores  = array('admin' => 'danger', 'profesor' => 'info', 'alumno' => 'success');

$titulo = 'Usuarios';
include dirname(__FILE__) . '/../includes/header.php';
?>

<h3 class="mb-3">Usuarios</h3>

<div class="card mb-4">
    <div class="card-header">Nuevo usuario</div>
    <div class="card-body">
        <form method="post" action="usuarios.php" autocomplete="off">
            <?php echo csrf_campo(); ?>
            <input type="hidden" name="accion" value="crear">
            <div class="form-row">
                <div class="form-group col-md-3">
                    <input type="text" name="nombre" class="form-control" placeholder="Nombre(s)" required>
                </div>
                <div class="form-group col-md-3">
                    <input type="text" name="apellidos" class="form-control" placeholder="Apellidos" required>
                </div>
                <div class="form-group col-md-3">
                    <input type="email" name="correo" class="form-control" placeholder="Correo" required>
                </div>
                <div class="form-group col-md-3">
                    <input type="password" name="password" class="form-control" placeholder="Contraseña (mín. 6)" minlength="6" required>
                </div>
            </div>
            <div class="form-row align-items-center">
                <div class="form-group col-md-3 mb-0">
                    <select name="rol" class="form-control">
                        <option value="alumno">Alumno</option>
                        <option value="profesor">Profesor</option>
                        <option value="admin">Administrador</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary">Crear usuario</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="table-responsive">
<table class="table table-sm table-bordered bg-white">
    <thead class="thead-light">
        <tr><th>Nombre</th><th>Correo</th><th>Rol</th><th>Estado</th><th style="min-width:330px">Acciones</th></tr>
    </thead>
    <tbody>
    <?php foreach ($usuarios as $u): $es_yo = ((int) $u['id'] === (int) $_SESSION['usuario_id']); ?>
        <tr>
            <td><?php echo e($u['nombre']); ?><?php echo $es_yo ? ' <small class="text-muted">(tú)</small>' : ''; ?></td>
            <td><?php echo e($u['correo']); ?></td>
            <td><span class="badge badge-<?php echo $colores[$u['rol']]; ?>"><?php echo e(ucfirst($u['rol'])); ?></span></td>
            <td><?php echo $u['activo']
                ? '<span class="badge badge-success">Activo</span>'
                : '<span class="badge badge-secondary">Inactivo</span>'; ?></td>
            <td>
                <form method="post" action="usuarios.php" class="form-inline mb-1">
                    <?php echo csrf_campo(); ?>
                    <input type="hidden" name="accion" value="password">
                    <input type="hidden" name="id" value="<?php echo (int) $u['id']; ?>">
                    <input type="password" name="nueva" class="form-control form-control-sm mr-1"
                           placeholder="Nueva contraseña" minlength="6" required>
                    <button class="btn btn-sm btn-outline-primary">Cambiar</button>
                </form>
                <?php if (!$es_yo): ?>
                <form method="post" action="usuarios.php" class="d-inline">
                    <?php echo csrf_campo(); ?>
                    <input type="hidden" name="accion" value="toggle">
                    <input type="hidden" name="id" value="<?php echo (int) $u['id']; ?>">
                    <button class="btn btn-sm btn-outline-warning"><?php echo $u['activo'] ? 'Desactivar' : 'Activar'; ?></button>
                </form>
                <form method="post" action="usuarios.php" class="d-inline"
                      onsubmit="return confirm('¿Eliminar este usuario y su perfil?');">
                    <?php echo csrf_campo(); ?>
                    <input type="hidden" name="accion" value="eliminar">
                    <input type="hidden" name="id" value="<?php echo (int) $u['id']; ?>">
                    <button class="btn btn-sm btn-outline-danger">Eliminar</button>
                </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>

<?php include dirname(__FILE__) . '/../includes/footer.php'; ?>
