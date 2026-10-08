<?php
require_once dirname(__FILE__) . '/../config/conexion.php';
requiere_rol(array('profesor'));

$pid   = obtener_profesor_id($pdo);
$gm_id = get_int('gm');
$parcial = get_int('parcial');
if ($parcial < 1 || $parcial > NUM_PARCIALES) {
    $parcial = 1;
}

$asignacion = null;
if ($pid && $gm_id) {
    // Solo puede capturar en materias que le pertenecen
    $st = $pdo->prepare(
        'SELECT gm.id, gm.grupo_id, g.nombre AS grupo, g.grado, g.ciclo_escolar,
                m.clave, m.nombre AS materia
         FROM grupo_materias gm
         JOIN grupos g ON g.id = gm.grupo_id
         JOIN materias m ON m.id = gm.materia_id
         WHERE gm.id = :gm AND gm.profesor_id = :p'
    );
    $st->execute(array(':gm' => $gm_id, ':p' => $pid));
    $asignacion = $st->fetch();
    if (!$asignacion) {
        flash('danger', 'No tienes acceso a esa materia.');
        redirigir('profesor/calificaciones.php');
    }
}

// ---------- Guardar ----------
if ($asignacion && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $volver = 'profesor/calificaciones.php?gm=' . $gm_id . '&parcial=' . $parcial;
    exigir_csrf($volver);

    $st = $pdo->prepare('SELECT id, nombre, apellidos FROM alumnos WHERE grupo_id = :g');
    $st->execute(array(':g' => $asignacion['grupo_id']));
    $alumnos_g = $st->fetchAll();

    $guardadas = 0;
    $invalidas = array();

    try {
        $pdo->beginTransaction();
        $up = $pdo->prepare(
            'INSERT INTO calificaciones (alumno_id, grupo_materia_id, parcial, calificacion, observaciones)
             VALUES (:a, :gm, :p, :c, :o)
             ON DUPLICATE KEY UPDATE calificacion = VALUES(calificacion), observaciones = VALUES(observaciones)'
        );
        foreach ($alumnos_g as $al) {
            $aid = (int) $al['id'];
            $c = (isset($_POST['cal'][$aid]) && !is_array($_POST['cal'][$aid])) ? trim($_POST['cal'][$aid]) : '';
            $o = (isset($_POST['obs'][$aid]) && !is_array($_POST['obs'][$aid])) ? trim($_POST['obs'][$aid]) : '';
            if ($c === '') {
                continue;
            }
            $c = str_replace(',', '.', $c);
            if (!is_numeric($c) || $c < 0 || $c > 10) {
                $invalidas[] = $al['apellidos'] . ' ' . $al['nombre'];
                continue;
            }
            $up->execute(array(
                ':a'  => $aid,
                ':gm' => $gm_id,
                ':p'  => $parcial,
                ':c'  => round($c, 2),
                ':o'  => ($o === '' ? null : mb_substr($o, 0, 255, 'UTF-8'))
            ));
            $guardadas++;
        }
        $pdo->commit();
    } catch (PDOException $ex) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        flash('danger', 'No se pudieron guardar las calificaciones.');
        redirigir($volver);
    }

    if ($invalidas) {
        flash('warning', 'Se guardaron ' . $guardadas . ' calificaciones. Valores fuera de 0–10 (no guardados): ' . implode(', ', $invalidas) . '.');
    } else {
        flash('success', 'Calificaciones guardadas (' . $guardadas . ').');
    }
    redirigir($volver);
}

// ---------- Datos para mostrar ----------
$lista_asignaciones = array();
$alumnos = array();
$existentes = array();

if ($pid && !$asignacion) {
    $st = $pdo->prepare(
        'SELECT gm.id, g.nombre AS grupo, g.grado, m.clave, m.nombre AS materia
         FROM grupo_materias gm
         JOIN grupos g ON g.id = gm.grupo_id
         JOIN materias m ON m.id = gm.materia_id
         WHERE gm.profesor_id = :p ORDER BY g.grado, g.nombre, m.nombre'
    );
    $st->execute(array(':p' => $pid));
    $lista_asignaciones = $st->fetchAll();
} elseif ($asignacion) {
    $st = $pdo->prepare('SELECT id, matricula, nombre, apellidos FROM alumnos WHERE grupo_id = :g ORDER BY apellidos, nombre');
    $st->execute(array(':g' => $asignacion['grupo_id']));
    $alumnos = $st->fetchAll();

    $st = $pdo->prepare('SELECT alumno_id, calificacion, observaciones FROM calificaciones WHERE grupo_materia_id = :gm AND parcial = :p');
    $st->execute(array(':gm' => $gm_id, ':p' => $parcial));
    foreach ($st->fetchAll() as $fila) {
        $existentes[(int) $fila['alumno_id']] = $fila;
    }
}

$titulo = 'Calificaciones';
include dirname(__FILE__) . '/../includes/header.php';
?>

<h3 class="mb-3">Calificaciones</h3>

<?php if (!$pid): ?>
    <div class="alert alert-danger">Tu cuenta no tiene un perfil de profesor. Contacta al administrador.</div>

<?php elseif (!$asignacion): ?>
    <?php if (!$lista_asignaciones): ?>
        <div class="alert alert-warning">Aún no tienes materias asignadas.</div>
    <?php else: ?>
        <p>Elige la materia y el parcial que quieres capturar:</p>
        <div class="list-group">
        <?php foreach ($lista_asignaciones as $a): ?>
            <div class="list-group-item d-flex justify-content-between align-items-center flex-wrap">
                <span><strong><?php echo e($a['grado'] . '° ' . $a['grupo']); ?></strong> — <?php echo e($a['clave'] . ' · ' . $a['materia']); ?></span>
                <span>
                <?php for ($p = 1; $p <= NUM_PARCIALES; $p++): ?>
                    <a class="btn btn-sm btn-outline-success"
                       href="calificaciones.php?gm=<?php echo (int) $a['id']; ?>&amp;parcial=<?php echo $p; ?>">Parcial <?php echo $p; ?></a>
                <?php endfor; ?>
                </span>
            </div>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>

<?php else: ?>
    <p class="mb-2">
        <strong><?php echo e($asignacion['grado'] . '° ' . $asignacion['grupo']); ?></strong> —
        <?php echo e($asignacion['clave'] . ' · ' . $asignacion['materia']); ?>
        <small class="text-muted">(<?php echo e($asignacion['ciclo_escolar']); ?>)</small>
    </p>

    <ul class="nav nav-pills mb-3">
        <?php for ($p = 1; $p <= NUM_PARCIALES; $p++): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo $p === $parcial ? 'active' : ''; ?>"
                   href="calificaciones.php?gm=<?php echo $gm_id; ?>&amp;parcial=<?php echo $p; ?>">Parcial <?php echo $p; ?></a>
            </li>
        <?php endfor; ?>
        <li class="nav-item ml-auto"><a class="nav-link" href="calificaciones.php">&larr; Cambiar materia</a></li>
    </ul>

    <?php if (!$alumnos): ?>
        <div class="alert alert-warning">Este grupo todavía no tiene alumnos asignados.</div>
    <?php else: ?>
        <form method="post" action="calificaciones.php?gm=<?php echo $gm_id; ?>&amp;parcial=<?php echo $parcial; ?>">
            <?php echo csrf_campo(); ?>
            <div class="table-responsive">
            <table class="table table-sm table-bordered bg-white">
                <thead class="thead-light">
                    <tr><th>Matrícula</th><th>Alumno</th><th style="width:130px">Calificación (0-10)</th><th>Observaciones</th></tr>
                </thead>
                <tbody>
                <?php foreach ($alumnos as $al):
                    $aid = (int) $al['id'];
                    $val = isset($existentes[$aid]) ? $existentes[$aid]['calificacion'] : '';
                    $obs = isset($existentes[$aid]) ? $existentes[$aid]['observaciones'] : '';
                ?>
                    <tr>
                        <td><?php echo e($al['matricula']); ?></td>
                        <td><?php echo e($al['apellidos'] . ', ' . $al['nombre']); ?></td>
                        <td><input type="number" step="0.01" min="0" max="10" class="form-control form-control-sm"
                                   name="cal[<?php echo $aid; ?>]" value="<?php echo e($val); ?>"></td>
                        <td><input type="text" maxlength="255" class="form-control form-control-sm"
                                   name="obs[<?php echo $aid; ?>]" value="<?php echo e($obs); ?>"></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            <p class="small text-muted">Deja en blanco las que aún no vas a capturar. Puedes volver a guardar para corregir.</p>
            <button type="submit" class="btn btn-success">Guardar parcial <?php echo $parcial; ?></button>
        </form>
    <?php endif; ?>
<?php endif; ?>

<?php include dirname(__FILE__) . '/../includes/footer.php'; ?>
