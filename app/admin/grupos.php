<?php
require_once dirname(__FILE__) . '/../config/conexion.php';
requiere_rol(array('admin'));

$destino = 'admin/grupos.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigir_csrf($destino);
    $accion = campo_post('accion');
    $id     = (int) campo_post('id');

    if ($accion === 'crear' || $accion === 'editar') {
        $nombre = campo_post('nombre');
        $grado  = (int) campo_post('grado');
        $ciclo  = campo_post('ciclo_escolar');

        if ($nombre === '' || $grado < 1 || $ciclo === '') {
            flash('danger', 'Completa el nombre, el grado (número) y el ciclo escolar.');
        } elseif ($accion === 'crear') {
            $pdo->prepare('INSERT INTO grupos (nombre, grado, ciclo_escolar) VALUES (:n, :g, :c)')
                ->execute(array(':n' => $nombre, ':g' => $grado, ':c' => $ciclo));
            flash('success', 'Grupo creado.');
        } else {
            $pdo->prepare('UPDATE grupos SET nombre = :n, grado = :g, ciclo_escolar = :c WHERE id = :id')
                ->execute(array(':n' => $nombre, ':g' => $grado, ':c' => $ciclo, ':id' => $id));
            flash('success', 'Grupo actualizado.');
        }
    } elseif ($accion === 'eliminar') {
        $pdo->prepare('DELETE FROM grupos WHERE id = :id')->execute(array(':id' => $id));
        flash('success', 'Grupo eliminado. Sus alumnos quedaron sin grupo.');

    } elseif ($accion === 'asignar') {
        $gid = (int) campo_post('grupo_id');
        $mid = (int) campo_post('materia_id');
        $pid = (int) campo_post('profesor_id');
        if ($gid > 0 && $mid > 0) {
            $pdo->prepare(
                'INSERT INTO grupo_materias (grupo_id, materia_id, profesor_id)
                 VALUES (:g, :m, :p)
                 ON DUPLICATE KEY UPDATE profesor_id = VALUES(profesor_id)'
            )->execute(array(':g' => $gid, ':m' => $mid, ':p' => ($pid > 0 ? $pid : null)));
            flash('success', 'Materia asignada al grupo.');
        }
        redirigir($destino . '?ver=' . $gid);

    } elseif ($accion === 'quitar') {
        $gid = (int) campo_post('grupo_id');
        $pdo->prepare('DELETE FROM grupo_materias WHERE id = :id AND grupo_id = :g')
            ->execute(array(':id' => $id, ':g' => $gid));
        flash('success', 'Materia retirada del grupo.');
        redirigir($destino . '?ver=' . $gid);
    }
    redirigir($destino);
}

// --- Vista de detalle de un grupo ---
$grupo_ver = null;
if (get_int('ver')) {
    $st = $pdo->prepare('SELECT * FROM grupos WHERE id = :id');
    $st->execute(array(':id' => get_int('ver')));
    $grupo_ver = $st->fetch();
    if (!$grupo_ver) {
        flash('danger', 'El grupo no existe.');
        redirigir($destino);
    }
}

$titulo = 'Grupos';
include dirname(__FILE__) . '/../includes/header.php';

if ($grupo_ver):
    $gid = (int) $grupo_ver['id'];

    $st = $pdo->prepare(
        'SELECT gm.id, m.clave, m.nombre AS materia, CONCAT(p.nombre, " ", p.apellidos) AS profesor
         FROM grupo_materias gm
         JOIN materias m ON m.id = gm.materia_id
         LEFT JOIN profesores p ON p.id = gm.profesor_id
         WHERE gm.grupo_id = :g ORDER BY m.nombre'
    );
    $st->execute(array(':g' => $gid));
    $asignadas = $st->fetchAll();

    $st = $pdo->prepare('SELECT id, matricula, nombre, apellidos FROM alumnos WHERE grupo_id = :g ORDER BY apellidos, nombre');
    $st->execute(array(':g' => $gid));
    $alumnos_grupo = $st->fetchAll();

    $materias   = $pdo->query('SELECT id, clave, nombre FROM materias ORDER BY nombre')->fetchAll();
    $profesores = $pdo->query('SELECT id, nombre, apellidos FROM profesores ORDER BY apellidos, nombre')->fetchAll();
?>
    <a href="grupos.php" class="btn btn-sm btn-outline-secondary mb-3">&larr; Volver a grupos</a>
    <h3 class="mb-3">Grupo <?php echo e($grupo_ver['grado'] . '° ' . $grupo_ver['nombre']); ?>
        <small class="text-muted"><?php echo e($grupo_ver['ciclo_escolar']); ?></small></h3>

    <div class="card mb-4">
        <div class="card-header">Materias y profesores del grupo</div>
        <div class="card-body">
            <form method="post" action="grupos.php" class="form-row mb-3">
                <?php echo csrf_campo(); ?>
                <input type="hidden" name="accion" value="asignar">
                <input type="hidden" name="grupo_id" value="<?php echo $gid; ?>">
                <div class="col-md-4 mb-2">
                    <select name="materia_id" class="form-control" required>
                        <option value="">— Materia —</option>
                        <?php foreach ($materias as $m): ?>
                            <option value="<?php echo (int) $m['id']; ?>"><?php echo e($m['clave'] . ' · ' . $m['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 mb-2">
                    <select name="profesor_id" class="form-control">
                        <option value="0">— Sin profesor —</option>
                        <?php foreach ($profesores as $p): ?>
                            <option value="<?php echo (int) $p['id']; ?>"><?php echo e($p['apellidos'] . ', ' . $p['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 mb-2">
                    <button class="btn btn-primary">Asignar / actualizar profesor</button>
                </div>
            </form>
            <?php if (!$materias): ?>
                <p class="text-muted small">Primero crea materias en el módulo <a href="materias.php">Materias</a>.</p>
            <?php endif; ?>

            <table class="table table-sm table-bordered mb-0">
                <thead class="thead-light"><tr><th>Clave</th><th>Materia</th><th>Profesor</th><th></th></tr></thead>
                <tbody>
                <?php if (!$asignadas): ?>
                    <tr><td colspan="4" class="text-center text-muted">Este grupo aún no tiene materias.</td></tr>
                <?php endif; ?>
                <?php foreach ($asignadas as $a): ?>
                    <tr>
                        <td><?php echo e($a['clave']); ?></td>
                        <td><?php echo e($a['materia']); ?></td>
                        <td><?php echo $a['profesor'] ? e($a['profesor']) : '<span class="text-muted">Sin asignar</span>'; ?></td>
                        <td>
                            <form method="post" action="grupos.php" class="d-inline"
                                  onsubmit="return confirm('¿Quitar la materia del grupo? Se borrarán sus calificaciones.');">
                                <?php echo csrf_campo(); ?>
                                <input type="hidden" name="accion" value="quitar">
                                <input type="hidden" name="id" value="<?php echo (int) $a['id']; ?>">
                                <input type="hidden" name="grupo_id" value="<?php echo $gid; ?>">
                                <button class="btn btn-sm btn-outline-danger">Quitar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Alumnos del grupo (<?php echo count($alumnos_grupo); ?>)</div>
        <div class="card-body">
            <?php if (!$alumnos_grupo): ?>
                <p class="text-muted mb-0">No hay alumnos. Asigna el grupo desde
                    <a href="alumnos.php">Alumnos</a> (Editar).</p>
            <?php else: ?>
                <ul class="list-group list-group-flush">
                <?php foreach ($alumnos_grupo as $al): ?>
                    <li class="list-group-item py-1">
                        <?php echo e($al['matricula'] . ' — ' . $al['apellidos'] . ', ' . $al['nombre']); ?>
                        <a class="small ml-2" href="alumnos.php?editar=<?php echo (int) $al['id']; ?>">cambiar grupo</a>
                    </li>
                <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>

<?php
else:
    $editar = null;
    if (get_int('editar')) {
        $st = $pdo->prepare('SELECT * FROM grupos WHERE id = :id');
        $st->execute(array(':id' => get_int('editar')));
        $editar = $st->fetch();
    }
    $grupos = $pdo->query(
        'SELECT g.*,
                (SELECT COUNT(*) FROM alumnos a WHERE a.grupo_id = g.id) AS total_alumnos,
                (SELECT COUNT(*) FROM grupo_materias gm WHERE gm.grupo_id = g.id) AS total_materias
         FROM grupos g ORDER BY g.grado, g.nombre'
    )->fetchAll();
?>
    <h3 class="mb-3">Grupos</h3>

    <div class="card mb-4">
        <div class="card-header"><?php echo $editar ? 'Editar grupo' : 'Nuevo grupo'; ?></div>
        <div class="card-body">
            <form method="post" action="grupos.php" autocomplete="off">
                <?php echo csrf_campo(); ?>
                <input type="hidden" name="accion" value="<?php echo $editar ? 'editar' : 'crear'; ?>">
                <?php if ($editar): ?><input type="hidden" name="id" value="<?php echo (int) $editar['id']; ?>"><?php endif; ?>
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>Nombre del grupo</label>
                        <input type="text" name="nombre" class="form-control" maxlength="50" placeholder="Ej. A" required
                               value="<?php echo e($editar ? $editar['nombre'] : ''); ?>">
                    </div>
                    <div class="form-group col-md-3">
                        <label>Grado (número)</label>
                        <input type="number" name="grado" class="form-control" min="1" max="12" required
                               value="<?php echo e($editar ? $editar['grado'] : ''); ?>">
                    </div>
                    <div class="form-group col-md-5">
                        <label>Ciclo escolar</label>
                        <input type="text" name="ciclo_escolar" class="form-control" maxlength="20" placeholder="2026-2027" required
                               value="<?php echo e($editar ? $editar['ciclo_escolar'] : ''); ?>">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary"><?php echo $editar ? 'Guardar cambios' : 'Crear grupo'; ?></button>
                <?php if ($editar): ?><a href="grupos.php" class="btn btn-secondary">Cancelar</a><?php endif; ?>
            </form>
        </div>
    </div>

    <div class="table-responsive">
    <table class="table table-sm table-bordered bg-white">
        <thead class="thead-light">
            <tr><th>Grupo</th><th>Ciclo</th><th>Alumnos</th><th>Materias</th><th>Acciones</th></tr>
        </thead>
        <tbody>
        <?php if (!$grupos): ?>
            <tr><td colspan="5" class="text-center text-muted">No hay grupos registrados.</td></tr>
        <?php endif; ?>
        <?php foreach ($grupos as $g): ?>
            <tr>
                <td><?php echo e($g['grado'] . '° ' . $g['nombre']); ?></td>
                <td><?php echo e($g['ciclo_escolar']); ?></td>
                <td><?php echo (int) $g['total_alumnos']; ?></td>
                <td><?php echo (int) $g['total_materias']; ?></td>
                <td>
                    <a href="grupos.php?ver=<?php echo (int) $g['id']; ?>" class="btn btn-sm btn-outline-success">Materias y alumnos</a>
                    <a href="grupos.php?editar=<?php echo (int) $g['id']; ?>" class="btn btn-sm btn-outline-primary">Editar</a>
                    <form method="post" action="grupos.php" class="d-inline"
                          onsubmit="return confirm('¿Eliminar el grupo? Sus alumnos quedarán sin grupo y se borrarán las calificaciones de sus materias.');">
                        <?php echo csrf_campo(); ?>
                        <input type="hidden" name="accion" value="eliminar">
                        <input type="hidden" name="id" value="<?php echo (int) $g['id']; ?>">
                        <button class="btn btn-sm btn-outline-danger">Eliminar</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
<?php endif; ?>

<?php include dirname(__FILE__) . '/../includes/footer.php'; ?>
