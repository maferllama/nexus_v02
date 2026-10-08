<?php
require_once 'config/conexion.php';

// Si ya hay sesión, ir al panel
if (isset($_SESSION['usuario_id'])) {
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

$error  = '';
$correo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo   = isset($_POST['correo']) ? trim($_POST['correo']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $token    = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';

    if (!validar_token_csrf($token)) {
        $error = 'Solicitud no válida. Recarga la página e inténtalo de nuevo.';
    } elseif ($correo === '' || $password === '') {
        $error = 'Ingresa tu correo y contraseña.';
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $error = 'El formato del correo no es válido.';
    } else {
        $stmt = $pdo->prepare(
            'SELECT id, nombre, correo, password, rol
             FROM usuarios
             WHERE correo = :correo AND activo = 1
             LIMIT 1'
        );
        $stmt->execute(array(':correo' => $correo));
        $usuario = $stmt->fetch();

        if ($usuario && password_verify($password, $usuario['password'])) {
            // Evita fijación de sesión
            session_regenerate_id(true);

            $_SESSION['usuario_id'] = (int) $usuario['id'];
            $_SESSION['nombre']     = $usuario['nombre'];
            $_SESSION['correo']     = $usuario['correo'];
            $_SESSION['rol']        = $usuario['rol'];

            header('Location: ' . BASE_URL . '/dashboard.php');
            exit;
        } else {
            $error = 'Correo o contraseña incorrectos.';
        }
    }
}

$titulo = 'Iniciar sesión';
include 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-5 col-lg-4">
        <div class="card shadow-sm mt-5">
            <div class="card-body p-4">
                <h4 class="text-center mb-4">Sistema Escolar</h4>

                <?php if ($error !== ''): ?>
                    <div class="alert alert-danger"><?php echo e($error); ?></div>
                <?php endif; ?>

                <form method="post" action="index.php" autocomplete="off">
                    <input type="hidden" name="csrf_token" value="<?php echo e(generar_token_csrf()); ?>">

                    <div class="form-group">
                        <label for="correo">Correo electrónico</label>
                        <input type="email" class="form-control" id="correo" name="correo"
                               value="<?php echo e($correo); ?>" required autofocus>
                    </div>

                    <div class="form-group">
                        <label for="password">Contraseña</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">Entrar</button>
                </form>

                <hr>
                <p class="text-center mb-0 small">
                    ¿No tienes cuenta? <a href="register.php">Regístrate como alumno o profesor</a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
