<?php
require_once dirname(__FILE__) . '/../config/conexion.php';
requiere_rol(array('admin'));

$destino = 'admin/materias.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigir_csrf($destino);
    $accion = campo_post('accion');
    $id     = (int) campo_post('id');

    if ($accion === 'crear' || $accion === 'editar') {
        $clave  = strtoupper(campo_post('clave'));
        $nombre = campo_post('nombre');
        $desc   = campo_post('descripcion');

        $st = $pdo->prepare('SELECT COUNT(*) FROM materias WHERE clave = :c AND id <> :id');
        $st->execute(array(':c' => $clave, ':id' => ($accion === 'editar' ? $id : 0)));

        if ($clave === '' || $nombre === '') {
            flash('danger', 'La clave y el nombre son obligatorios.');
        } elseif ($st->fetchColumn() > 0) {
            flash('danger', 'Ya existe una materia con esa clave.');
        } elseif ($accion === 'crear') {
            $pdo->prepare('INSERT INTO materias (clave, nombre, descripcion) VALUES (:c, :n, :d)')
                ->execute(array(':c' => $clave, ':n' => $nombre, ':d' => ($desc !== '' ? $desc : null)));
            flash('success', 'Materia creada.');
        } else {
            $pdo->prepare('UPDATE materias SET clave = :c, nombre = :n, descripcion = :d WHERE id = :id')
                ->execute(array(':c' => $clave, ':n' => $nombre, ':d' => ($desc !== '' ? $desc : null), ':id' => $id));
            flash('success', 'Materia actualizada.');
        }
    } elseif ($accion === 'eliminar') {
        $pdo->prepare('DELETE FROM materias WHERE id = :id')->execute(array(':id' => $id));
        flash('success', 'Materia eliminada.');
    }
    redirigir($destino);
}

$editar = null;
if (get_int('editar')) {
    $st = $pdo->prepare('SELECT * FROM materias WHERE id = :id');
    $st->execute(array(':id' => get_int('editar')));
    $editar = $st->fetch();
}

$materias = $pdo->query('SELECT * FROM materias ORDER BY nombre')->fetchAll();

$titulo = 'Materias';
include dirname(__FILE__) . '/../includes/header.php';
?>

<h3 class="mb-3">Materias</h3>

<div class="card mb-4">
    <div class="card-header"><?php echo $editar ? 'Editar materia' : 'Nueva materia'; ?></div>
    <div class="card-body">
        <form method="post" action="materias.php" autocomplete="off">
            <?php echo csrf_campo(); ?>
            <input type="hidden" name="accion" value="<?php echo $editar ? 'editar' : 'crear'; ?>">
            <?php if ($editar): ?><input type="hidden" name="id" value="<?php echo (int) $editar['id']; ?>"><?php endif; ?>

            <div class="form-row">
                <div class="form-group col-md-2">
                    <label>Clave</label>
                    <input type="text" name="clave" class="form-control" maxlength="20" required
                           value="<?php echo e($editar ? $editar['clave'] : ''); ?>">
                </div>
                <div class="form-group col-md-4">
                    <label>Nombre</label>
                    <input type="text" name="nombre" class="form-control" maxlength="100" required
                           value="<?php echo e($editar ? $editar['nombre'] : ''); ?>">
                </div>
                <div class="form-group col-md-6">
                    <label>Descripción</label>
                    <input type="text" name="descripcion" class="form-control"
                           value="<?php echo e($editar ? $editar['descripcion'] : ''); ?>">
                </div>
            </div>
            <button type="submit" class="btn btn-primary"><?php echo $editar ? 'Guardar cambios' : 'Crear materia'; ?></button>
            <?php if ($editar): ?><a href="materias.php" class="btn btn-secondary">Cancelar</a><?php endif; ?>
        </form>
    </div>
</div>

<div class="table-responsive">
<table class="table table-sm table-bordered bg-white">
    <thead class="thead-light">
        <tr><th>Clave</th><th>Nombre</th><th>Descripción</th><th>Acciones</th></tr>
    </thead>
    <tbody>
    <?php if (!$materias): ?>
        <tr><td colspan="4" class="text-center text-muted">No hay materias registradas.</td></tr>
    <?php endif; ?>
    <?php foreach ($materias as $m): ?>
        <tr>
            <td><?php echo e($m['clave']); ?></td>
            <td><?php echo e($m['nombre']); ?></td>
            <td><?php echo e($m['descripcion']); ?></td>
            <td>
                <a href="materias.php?editar=<?php echo (int) $m['id']; ?>" class="btn btn-sm btn-outline-primary">Editar</a>
                <form method="post" action="materias.php" class="d-inline"
                      onsubmit="return confirm('¿Eliminar la materia? También se quitará de los grupos y se borrarán sus calificaciones.');">
                    <?php echo csrf_campo(); ?>
                    <input type="hidden" name="accion" value="eliminar">
                    <input type="hidden" name="id" value="<?php echo (int) $m['id']; ?>">
                    <button class="btn btn-sm btn-outline-danger">Eliminar</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>

<?php include dirname(__FILE__) . '/../includes/footer.php'; ?>
