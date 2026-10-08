<?php
// Funciones compartidas del sistema (compatibles con PHP 5.6)

define('NUM_PARCIALES', 3);
define('CAL_APROBATORIA', 6);

function flash($tipo, $texto)
{
    $_SESSION['flash'] = array('tipo' => $tipo, 'texto' => $texto);
}

function redirigir($ruta)
{
    header('Location: ' . BASE_URL . '/' . ltrim($ruta, '/'));
    exit;
}

function csrf_campo()
{
    return '<input type="hidden" name="csrf_token" value="' . e(generar_token_csrf()) . '">';
}

// Si el token CSRF no es válido, regresa a $destino con un mensaje
function exigir_csrf($destino)
{
    $token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (!validar_token_csrf($token)) {
        flash('danger', 'Solicitud no válida. Inténtalo de nuevo.');
        redirigir($destino);
    }
}

function campo_post($campo)
{
    return (isset($_POST[$campo]) && !is_array($_POST[$campo])) ? trim($_POST[$campo]) : '';
}

function get_int($campo)
{
    return isset($_GET[$campo]) ? (int) $_GET[$campo] : 0;
}

function fecha_valida($fecha)
{
    $d = DateTime::createFromFormat('Y-m-d', $fecha);
    return $d && $d->format('Y-m-d') === $fecha;
}

function correo_existe($pdo, $correo)
{
    $st = $pdo->prepare('SELECT COUNT(*) FROM usuarios WHERE correo = :c');
    $st->execute(array(':c' => $correo));
    return $st->fetchColumn() > 0;
}

/**
 * Crea un usuario y su perfil (alumno / profesor) en una sola transacción.
 * $d: nombre, apellidos, correo, password, rol, [telefono, matricula, grupo_id, especialidad]
 * Devuelve el id del usuario o false (y deja el motivo en $error).
 */
function crear_usuario_con_perfil($pdo, $d, &$error)
{
    $nombre    = isset($d['nombre']) ? $d['nombre'] : '';
    $apellidos = isset($d['apellidos']) ? $d['apellidos'] : '';
    $correo    = isset($d['correo']) ? $d['correo'] : '';
    $password  = isset($d['password']) ? $d['password'] : '';
    $rol       = isset($d['rol']) ? $d['rol'] : '';
    $telefono  = (isset($d['telefono']) && $d['telefono'] !== '') ? $d['telefono'] : null;
    $matricula = isset($d['matricula']) ? $d['matricula'] : '';
    $grupo_id  = (isset($d['grupo_id']) && (int) $d['grupo_id'] > 0) ? (int) $d['grupo_id'] : null;
    $espec     = (isset($d['especialidad']) && $d['especialidad'] !== '') ? $d['especialidad'] : null;

    if ($nombre === '' || $apellidos === '') {
        $error = 'Escribe el nombre y los apellidos.';
        return false;
    }
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $error = 'El correo no tiene un formato válido.';
        return false;
    }
    if (strlen($password) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres.';
        return false;
    }
    if (!in_array($rol, array('admin', 'profesor', 'alumno'))) {
        $error = 'Rol no válido.';
        return false;
    }
    if (correo_existe($pdo, $correo)) {
        $error = 'Ya existe una cuenta con ese correo.';
        return false;
    }
    if ($rol === 'alumno' && $matricula !== '') {
        $st = $pdo->prepare('SELECT COUNT(*) FROM alumnos WHERE matricula = :m');
        $st->execute(array(':m' => $matricula));
        if ($st->fetchColumn() > 0) {
            $error = 'Esa matrícula ya está registrada.';
            return false;
        }
    }

    try {
        $pdo->beginTransaction();

        $st = $pdo->prepare(
            'INSERT INTO usuarios (nombre, correo, password, rol, activo)
             VALUES (:n, :c, :p, :r, 1)'
        );
        $st->execute(array(
            ':n' => trim($nombre . ' ' . $apellidos),
            ':c' => $correo,
            ':p' => password_hash($password, PASSWORD_DEFAULT),
            ':r' => $rol
        ));
        $uid = (int) $pdo->lastInsertId();

        if ($rol === 'alumno') {
            $temporal = ($matricula !== '') ? $matricula : 'TMP' . uniqid();
            $st = $pdo->prepare(
                'INSERT INTO alumnos (usuario_id, grupo_id, matricula, nombre, apellidos, telefono)
                 VALUES (:u, :g, :m, :n, :a, :t)'
            );
            $st->execute(array(':u' => $uid, ':g' => $grupo_id, ':m' => $temporal,
                               ':n' => $nombre, ':a' => $apellidos, ':t' => $telefono));
            if ($matricula === '') {
                $aid = (int) $pdo->lastInsertId();
                $st = $pdo->prepare('UPDATE alumnos SET matricula = :m WHERE id = :id');
                $st->execute(array(':m' => date('Y') . str_pad($aid, 5, '0', STR_PAD_LEFT), ':id' => $aid));
            }
        } elseif ($rol === 'profesor') {
            $st = $pdo->prepare(
                'INSERT INTO profesores (usuario_id, nombre, apellidos, especialidad, telefono)
                 VALUES (:u, :n, :a, :e, :t)'
            );
            $st->execute(array(':u' => $uid, ':n' => $nombre, ':a' => $apellidos,
                               ':e' => $espec, ':t' => $telefono));
        }

        $pdo->commit();
        return $uid;
    } catch (PDOException $ex) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = 'No se pudo crear la cuenta. Verifica que el correo y la matrícula no estén repetidos.';
        return false;
    }
}

// Id del profesor ligado al usuario en sesión (o false)
function obtener_profesor_id($pdo)
{
    $st = $pdo->prepare('SELECT id FROM profesores WHERE usuario_id = :u LIMIT 1');
    $st->execute(array(':u' => $_SESSION['usuario_id']));
    $id = $st->fetchColumn();
    return $id ? (int) $id : false;
}
