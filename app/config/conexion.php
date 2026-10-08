<?php
// ---------------------------------------------------------
// Configuración (vive fuera de app/, en <repo>/config/)
// Si no existe config.php se usan los valores de config.example.php
// (pensados para XAMPP: root sin contraseña).
// ---------------------------------------------------------
$dir_config = dirname(__FILE__) . '/../../config/';
if (is_file($dir_config . 'config.php')) {
    require_once $dir_config . 'config.php';
} elseif (is_file($dir_config . 'config.example.php')) {
    require_once $dir_config . 'config.example.php';
} else {
    die('No se encontró la configuración. Crea config/config.php a partir de config/config.example.php');
}

date_default_timezone_set(defined('ZONA_HORARIA') ? ZONA_HORARIA : 'America/Mexico_City');

// ---------------------------------------------------------
// BASE_URL: se detecta sola (funciona en XAMPP, Alias de Apache, subcarpetas...).
// Para forzarla, define BASE_URL en config/config.php.
// ---------------------------------------------------------
if (!defined('BASE_URL')) {
    $raiz   = str_replace('\\', '/', (string) realpath(dirname(__FILE__) . '/..'));
    $script = isset($_SERVER['SCRIPT_FILENAME']) ? str_replace('\\', '/', (string) realpath($_SERVER['SCRIPT_FILENAME'])) : '';
    $url    = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
    $base   = '';
    if ($raiz !== '' && $script !== '' && stripos($script, $raiz . '/') === 0) {
        $relativa = substr($script, strlen($raiz));            // ej. /admin/usuarios.php
        $largo    = strlen($relativa);
        if ($largo > 0 && strtolower(substr($url, -$largo)) === strtolower($relativa)) {
            $base = substr($url, 0, strlen($url) - $largo);
        }
    }
    define('BASE_URL', rtrim($base, '/'));
}

// ---------------------------------------------------------
// Sesión
// ---------------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    session_start();
}

// ---------------------------------------------------------
// Conexión PDO
// ---------------------------------------------------------
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        array(
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false
        )
    );
} catch (PDOException $e) {
    die('Error de conexión a la base de datos: ' . $e->getMessage());
}

// ---------------------------------------------------------
// Funciones auxiliares
// ---------------------------------------------------------

// Escapar salida HTML
function e($valor)
{
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}

// Exige sesión iniciada
function requiere_login()
{
    if (!isset($_SESSION['usuario_id'])) {
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
}

// Exige sesión iniciada y que el rol esté en la lista permitida
function requiere_rol($roles_permitidos)
{
    requiere_login();
    if (!in_array($_SESSION['rol'], $roles_permitidos)) {
        header('Location: ' . BASE_URL . '/dashboard.php');
        exit;
    }
}

// Token CSRF
function generar_token_csrf()
{
    if (empty($_SESSION['csrf_token'])) {
        if (function_exists('openssl_random_pseudo_bytes')) {
            $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
        } else {
            $_SESSION['csrf_token'] = md5(uniqid(mt_rand(), true)) . md5(uniqid(mt_rand(), true));
        }
    }
    return $_SESSION['csrf_token'];
}

function validar_token_csrf($token)
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Funciones compartidas
require_once dirname(__FILE__) . '/../includes/funciones.php';
