<?php
require_once 'config/conexion.php';
requiere_login();

$rol = $_SESSION['rol'];
$totales = array();
$num_asignaciones = 0;
$alumno_info = null;

if ($rol === 'admin') {
    $tablas = array('usuarios', 'alumnos', 'profesores', 'materias', 'grupos');
    foreach ($tablas as $tabla) {
        // Los nombres de tabla vienen de una lista fija, no del usuario
        $totales[$tabla] = (int) $pdo->query('SELECT COUNT(*) FROM ' . $tabla)->fetchColumn();
    }
} elseif ($rol === 'profesor') {
    $pid = obtener_profesor_id($pdo);
    if ($pid) {
        $st = $pdo->prepare('SELECT COUNT(*) FROM grupo_materias WHERE profesor_id = :p');
        $st->execute(array(':p' => $pid));
        $num_asignaciones = (int) $st->fetchColumn();
    }
} else {
    $st = $pdo->prepare(
        'SELECT a.matricula, g.nombre AS grupo, g.grado, g.ciclo_escolar
         FROM alumnos a LEFT JOIN grupos g ON g.id = a.grupo_id
         WHERE a.usuario_id = :u LIMIT 1'
    );
    $st->execute(array(':u' => $_SESSION['usuario_id']));
    $alumno_info = $st->fetch();
}

$titulo = 'Panel principal';
include 'includes/header.php';
?>

<div class="jumbotron py-4">
    <h2>¡Bienvenido, <?php echo e($_SESSION['nombre']); ?>!</h2>
    <p class="lead mb-0">
        Has iniciado sesión como <strong><?php echo e(ucfirst($rol)); ?></strong>.
    </p>
</div>

<?php if ($rol === 'admin'): ?>
    <div class="row">
        <?php
        $tarjetas = array(
            'usuarios'   => array('Usuarios',   'admin/usuarios.php',   'primary'),
            'alumnos'    => array('Alumnos',    'admin/alumnos.php',    'success'),
            'profesores' => array('Profesores', 'admin/profesores.php', 'info'),
            'materias'   => array('Materias',   'admin/materias.php',   'warning'),
            'grupos'     => array('Grupos',     'admin/grupos.php',     'secondary')
        );
        foreach ($tarjetas as $clave => $datos):
        ?>
            <div class="col-sm-6 col-lg-4 mb-4">
                <div class="card border-<?php echo $datos[2]; ?> h-100">
                    <div class="card-body text-center">
                        <h6 class="text-muted text-uppercase"><?php echo $datos[0]; ?></h6>
                        <p class="display-4 mb-2"><?php echo $totales[$clave]; ?></p>
                        <a href="<?php echo BASE_URL . '/' . $datos[1]; ?>"
                           class="btn btn-outline-<?php echo $datos[2]; ?> btn-sm">Administrar</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

<?php elseif ($rol === 'profesor'): ?>
    <?php if ($num_asignaciones === 0): ?>
        <div class="alert alert-warning">
            Aún no tienes grupos ni materias asignados. Pide al administrador que te asigne desde
            <em>Grupos</em>.
        </div>
    <?php endif; ?>
    <div class="row">
        <div class="col-md-6 mb-3">
            <div class="card h-100"><div class="card-body">
                <h5 class="card-title">Mis grupos <span class="badge badge-primary"><?php echo $num_asignaciones; ?></span></h5>
                <p class="card-text">Consulta los grupos y materias que tienes asignados.</p>
                <a href="<?php echo BASE_URL; ?>/profesor/mis_grupos.php" class="btn btn-primary">Ver grupos</a>
            </div></div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card h-100"><div class="card-body">
                <h5 class="card-title">Calificaciones</h5>
                <p class="card-text">Captura y actualiza las calificaciones de tus alumnos.</p>
                <a href="<?php echo BASE_URL; ?>/profesor/calificaciones.php" class="btn btn-success">Capturar</a>
            </div></div>
        </div>
    </div>

<?php else: ?>
    <?php if ($alumno_info && !$alumno_info['grupo']): ?>
        <div class="alert alert-warning">
            Todavía no tienes un grupo asignado. El administrador lo hará pronto.
        </div>
    <?php endif; ?>
    <div class="card"><div class="card-body">
        <h5 class="card-title">Mi boleta</h5>
        <?php if ($alumno_info): ?>
            <p class="mb-1"><strong>Matrícula:</strong> <?php echo e($alumno_info['matricula']); ?></p>
            <?php if ($alumno_info['grupo']): ?>
                <p class="mb-2"><strong>Grupo:</strong>
                    <?php echo e($alumno_info['grado'] . '° ' . $alumno_info['grupo'] . ' (' . $alumno_info['ciclo_escolar'] . ')'); ?></p>
            <?php endif; ?>
        <?php endif; ?>
        <p class="card-text">Consulta tus calificaciones por materia y parcial.</p>
        <a href="<?php echo BASE_URL; ?>/alumno/boleta.php" class="btn btn-primary">Ver boleta</a>
    </div></div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
