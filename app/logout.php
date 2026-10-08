<?php
require_once 'config/conexion.php';

// Vaciar variables de sesión
$_SESSION = array();

// Eliminar la cookie de sesión
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}

session_destroy();

header('Location: ' . BASE_URL . '/index.php');
exit;
