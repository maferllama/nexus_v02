<?php
require_once 'config/conexion.php';

if (isset($_SESSION['usuario_id'])) {
    redirigir('dashboard.php');
}

$error = '';
$v = array('tipo' => 'alumno', 'nombre' => '', 'apellidos' => '', 'correo' => '', 'telefono' => '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $v['tipo']      = campo_post('tipo');
    $v['nombre']    = campo_post('nombre');
    $v['apellidos'] = campo_post('apellidos');
    $v['correo']    = campo_post('correo');
    $v['telefono']  = campo_post('telefono');
    $password  = isset($_POST['password']) ? $_POST['password'] : '';
    $confirmar = isset($_POST['confirmar']) ? $_POST['confirmar'] : '';
    $token     = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';

    if (!validar_token_csrf($token)) {
        $error = 'Solicitud no válida. Recarga la página e inténtalo de nuevo.';
    } elseif (!in_array($v['tipo'], array('alumno', 'profesor'))) {
        $error = 'Selecciona un tipo de cuenta válido.';
    } elseif ($password !== $confirmar) {
        $error = 'Las contraseñas no coinciden.';
    } else {
        $id = crear_usuario_con_perfil($pdo, array(
            'nombre'    => $v['nombre'],
            'apellidos' => $v['apellidos'],
            'correo'    => $v['correo'],
            'password'  => $password,
            'rol'       => $v['tipo'],
            'telefono'  => $v['telefono']
        ), $error);

        if ($id) {
            flash('success', 'Cuenta creada correctamente. Ya puedes iniciar sesión.');
            redirigir('index.php');
        }
    }
}

$titulo = 'Registro';
include 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-7 col-lg-6">
        <div class="card shadow-sm mt-3">
            <div class="card-body p-4">
                <h4 class="text-center mb-4">Crear cuenta</h4>

                <?php if ($error !== ''): ?>
                    <div class="alert alert-danger"><?php echo e($error); ?></div>
                <?php endif; ?>

                <form method="post" action="register.php" autocomplete="off">
                    <?php echo csrf_campo(); ?>

                    <div class="form-group">
                        <label class="d-block">Soy</label>
                        <div class="custom-control custom-radio custom-control-inline">
                            <input type="radio" id="t_alumno" name="tipo" value="alumno" class="custom-control-input"
                                   <?php echo $v['tipo'] === 'alumno' ? 'checked' : ''; ?>>
                            <label class="custom-control-label" for="t_alumno">Alumno</label>
                        </div>
                        <div class="custom-control custom-radio custom-control-inline">
                            <input type="radio" id="t_prof" name="tipo" value="profesor" class="custom-control-input"
                                   <?php echo $v['tipo'] === 'profesor' ? 'checked' : ''; ?>>
                            <label class="custom-control-label" for="t_prof">Profesor (maestro)</label>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="nombre">Nombre(s)</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" maxlength="100"
                                   value="<?php echo e($v['nombre']); ?>" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="apellidos">Apellidos</label>
                            <input type="text" class="form-control" id="apellidos" name="apellidos" maxlength="100"
                                   value="<?php echo e($v['apellidos']); ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="correo">Correo electrónico</label>
                        <input type="email" class="form-control" id="correo" name="correo" maxlength="150"
                               value="<?php echo e($v['correo']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="telefono">Teléfono <small class="text-muted">(opcional)</small></label>
                        <input type="text" class="form-control" id="telefono" name="telefono" maxlength="20"
                               value="<?php echo e($v['telefono']); ?>">
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="password">Contraseña</label>
                            <input type="password" class="form-control" id="password" name="password" minlength="6" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="confirmar">Confirmar contraseña</label>
                            <input type="password" class="form-control" id="confirmar" name="confirmar" minlength="6" required>
                        </div>
                    </div>

                    <p class="small text-muted">
                        Después de registrarte, el administrador te asignará a un grupo (alumno)
                        o a las materias que impartirás (profesor).
                    </p>

                    <button type="submit" class="btn btn-success btn-block">Registrarme</button>
                    <a href="index.php" class="btn btn-link btn-block">Ya tengo cuenta</a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
