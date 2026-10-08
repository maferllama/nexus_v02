<?php
require_once dirname(__FILE__) . '/../config/conexion.php';
requiere_rol(array('alumno'));

$st = $pdo->prepare(
    'SELECT a.id, a.matricula, a.nombre, a.apellidos, a.grupo_id,
            g.nombre AS grupo, g.grado, g.ciclo_escolar
     FROM alumnos a LEFT JOIN grupos g ON g.id = a.grupo_id
     WHERE a.usuario_id = :u LIMIT 1'
);
$st->execute(array(':u' => $_SESSION['usuario_id']));
$alumno = $st->fetch();

$materias = array();
$cals = array();

if ($alumno && $alumno['grupo_id']) {
    $st = $pdo->prepare(
        'SELECT gm.id, m.clave, m.nombre AS materia, CONCAT(p.nombre, " ", p.apellidos) AS profesor
         FROM grupo_materias gm
         JOIN materias m ON m.id = gm.materia_id
         LEFT JOIN profesores p ON p.id = gm.profesor_id
         WHERE gm.grupo_id = :g ORDER BY m.nombre'
    );
    $st->execute(array(':g' => $alumno['grupo_id']));
    $materias = $st->fetchAll();

    $st = $pdo->prepare('SELECT grupo_materia_id, parcial, calificacion FROM calificaciones WHERE alumno_id = :a');
    $st->execute(array(':a' => $alumno['id']));
    foreach ($st->fetchAll() as $c) {
        $cals[(int) $c['grupo_materia_id']][(int) $c['parcial']] = (float) $c['calificacion'];
    }
}

$suma_general = 0;
$cuenta_general = 0;

$titulo = 'Mi boleta';
include dirname(__FILE__) . '/../includes/header.php';
?>

<h3 class="mb-3">Mi boleta</h3>

<?php if (!$alumno): ?>
    <div class="alert alert-danger">Tu cuenta no tiene un perfil de alumno. Contacta al administrador.</div>

<?php else: ?>
    <div class="card mb-3"><div class="card-body py-3">
        <div class="row">
            <div class="col-md-4"><strong>Alumno:</strong> <?php echo e($alumno['apellidos'] . ', ' . $alumno['nombre']); ?></div>
            <div class="col-md-3"><strong>Matrícula:</strong> <?php echo e($alumno['matricula']); ?></div>
            <div class="col-md-5"><strong>Grupo:</strong>
                <?php echo $alumno['grupo']
                    ? e($alumno['grado'] . '° ' . $alumno['grupo'] . ' (' . $alumno['ciclo_escolar'] . ')')
                    : 'Sin asignar'; ?></div>
        </div>
    </div></div>

    <?php if (!$alumno['grupo_id']): ?>
        <div class="alert alert-warning">Aún no tienes un grupo asignado. El administrador lo hará pronto.</div>
    <?php elseif (!$materias): ?>
        <div class="alert alert-info">Tu grupo todavía no tiene materias registradas.</div>
    <?php else: ?>
        <div class="table-responsive">
        <table class="table table-bordered bg-white text-center">
            <thead class="thead-light">
                <tr>
                    <th class="text-left">Materia</th>
                    <th class="text-left">Profesor</th>
                    <?php for ($p = 1; $p <= NUM_PARCIALES; $p++): ?><th>Parcial <?php echo $p; ?></th><?php endfor; ?>
                    <th>Promedio</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($materias as $m):
                $mis = isset($cals[(int) $m['id']]) ? $cals[(int) $m['id']] : array();
                $prom = count($mis) ? array_sum($mis) / count($mis) : null;
                if ($prom !== null) { $suma_general += $prom; $cuenta_general++; }
            ?>
                <tr>
                    <td class="text-left"><?php echo e($m['clave'] . ' · ' . $m['materia']); ?></td>
                    <td class="text-left"><?php echo $m['profesor'] ? e($m['profesor']) : '<span class="text-muted">—</span>'; ?></td>
                    <?php for ($p = 1; $p <= NUM_PARCIALES; $p++): ?>
                        <td>
                        <?php if (isset($mis[$p])): ?>
                            <span class="<?php echo $mis[$p] < CAL_APROBATORIA ? 'text-danger font-weight-bold' : ''; ?>">
                                <?php echo number_format($mis[$p], 1); ?></span>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                        </td>
                    <?php endfor; ?>
                    <td>
                    <?php if ($prom !== null): ?>
                        <strong class="<?php echo $prom < CAL_APROBATORIA ? 'text-danger' : 'text-success'; ?>">
                            <?php echo number_format($prom, 1); ?></strong>
                    <?php else: ?>
                        <span class="text-muted">—</span>
                    <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="table-secondary">
                    <th colspan="<?php echo 2 + NUM_PARCIALES; ?>" class="text-right">Promedio general</th>
                    <th><?php echo $cuenta_general ? number_format($suma_general / $cuenta_general, 1) : '—'; ?></th>
                </tr>
            </tfoot>
        </table>
        </div>
        <p class="small text-muted">En rojo: calificaciones menores a <?php echo CAL_APROBATORIA; ?>.</p>
    <?php endif; ?>
<?php endif; ?>

<?php include dirname(__FILE__) . '/../includes/footer.php'; ?>
