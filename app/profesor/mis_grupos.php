<?php
require_once dirname(__FILE__) . '/../config/conexion.php';
requiere_rol(array('profesor'));

$pid = obtener_profesor_id($pdo);
$asignaciones = array();

if ($pid) {
    $st = $pdo->prepare(
        'SELECT gm.id, g.nombre AS grupo, g.grado, g.ciclo_escolar,
                m.clave, m.nombre AS materia,
                (SELECT COUNT(*) FROM alumnos a WHERE a.grupo_id = g.id) AS total_alumnos
         FROM grupo_materias gm
         JOIN grupos g ON g.id = gm.grupo_id
         JOIN materias m ON m.id = gm.materia_id
         WHERE gm.profesor_id = :p
         ORDER BY g.grado, g.nombre, m.nombre'
    );
    $st->execute(array(':p' => $pid));
    $asignaciones = $st->fetchAll();
}

$titulo = 'Mis grupos';
include dirname(__FILE__) . '/../includes/header.php';
?>

<h3 class="mb-3">Mis grupos</h3>

<?php if (!$pid): ?>
    <div class="alert alert-danger">Tu cuenta no tiene un perfil de profesor. Contacta al administrador.</div>
<?php elseif (!$asignaciones): ?>
    <div class="alert alert-warning">
        Aún no tienes materias asignadas. El administrador debe asignarte desde <em>Grupos</em>.
    </div>
<?php else: ?>
    <div class="table-responsive">
    <table class="table table-bordered bg-white">
        <thead class="thead-light">
            <tr><th>Grupo</th><th>Ciclo</th><th>Materia</th><th>Alumnos</th><th>Calificaciones</th></tr>
        </thead>
        <tbody>
        <?php foreach ($asignaciones as $a): ?>
            <tr>
                <td><?php echo e($a['grado'] . '° ' . $a['grupo']); ?></td>
                <td><?php echo e($a['ciclo_escolar']); ?></td>
                <td><?php echo e($a['clave'] . ' · ' . $a['materia']); ?></td>
                <td><?php echo (int) $a['total_alumnos']; ?></td>
                <td>
                    <?php for ($p = 1; $p <= NUM_PARCIALES; $p++): ?>
                        <a class="btn btn-sm btn-outline-success"
                           href="calificaciones.php?gm=<?php echo (int) $a['id']; ?>&amp;parcial=<?php echo $p; ?>">Parcial <?php echo $p; ?></a>
                    <?php endfor; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
<?php endif; ?>

<?php include dirname(__FILE__) . '/../includes/footer.php'; ?>
