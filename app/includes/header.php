<?php
require_once dirname(__FILE__) . '/../config/conexion.php';

$titulo_pagina = isset($titulo) ? $titulo : 'Sistema Escolar';
$sesion_activa = isset($_SESSION['usuario_id']);
$rol_actual    = isset($_SESSION['rol']) ? $_SESSION['rol'] : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?php echo e($titulo_pagina); ?> | Sistema Escolar</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">

<?php if ($sesion_activa): ?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <a class="navbar-brand" href="<?php echo BASE_URL; ?>/dashboard.php">Sistema Escolar</a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#menuPrincipal"
            aria-controls="menuPrincipal" aria-expanded="false" aria-label="Menú">
        <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="menuPrincipal">
        <ul class="navbar-nav mr-auto">
            <li class="nav-item">
                <a class="nav-link" href="<?php echo BASE_URL; ?>/dashboard.php">Inicio</a>
            </li>

            <?php if ($rol_actual === 'admin'): ?>
                <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>/admin/usuarios.php">Usuarios</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>/admin/alumnos.php">Alumnos</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>/admin/profesores.php">Profesores</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>/admin/materias.php">Materias</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>/admin/grupos.php">Grupos</a></li>
            <?php elseif ($rol_actual === 'profesor'): ?>
                <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>/profesor/mis_grupos.php">Mis grupos</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>/profesor/calificaciones.php">Calificaciones</a></li>
            <?php elseif ($rol_actual === 'alumno'): ?>
                <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>/alumno/boleta.php">Mi boleta</a></li>
            <?php endif; ?>
        </ul>

        <span class="navbar-text mr-3">
            <?php echo e($_SESSION['nombre']); ?>
            <span class="badge badge-light"><?php echo e(ucfirst($rol_actual)); ?></span>
        </span>
        <a class="btn btn-outline-light btn-sm" href="<?php echo BASE_URL; ?>/logout.php">Cerrar sesión</a>
    </div>
</nav>
<?php endif; ?>

<main class="container py-4">
<?php
// Mensajes flash (opcional): $_SESSION['flash'] = array('tipo' => 'success', 'texto' => '...');
if (isset($_SESSION['flash'])):
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
?>
    <div class="alert alert-<?php echo e($flash['tipo']); ?> alert-dismissible fade show" role="alert">
        <?php echo e($flash['texto']); ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
<?php endif; ?>
