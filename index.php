<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';

if (isset($_SESSION['usuario_id'])) {
    header('Location: /sigvet/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($login && $password) {
        $conn = conectar();
        $stmt = $conn->prepare("SELECT u.*, r.nombre as rol FROM usuarios u JOIN roles r ON u.rol_id = r.id WHERE (u.email = ? OR u.username = ?) AND u.activo = 1 LIMIT 1");
        $stmt->bind_param('ss', $login, $login);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        // Contraseña demo: "password" funciona con el hash de arriba
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['usuario_id'] = $user['id'];
            $_SESSION['nombres'] = $user['nombres'];
            $_SESSION['apellidos'] = $user['apellidos'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['rol'] = $user['rol'];
            registrarLog($conn, 'Inicio de sesión');
            $conn->close();
            header('Location: /sigvet/dashboard.php');
            exit;
        } else {
            $error = 'Credenciales incorrectas. Verifique su usuario y contraseña.';
        }
        $conn->close();
    } else {
        $error = 'Complete todos los campos.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión – SIGVET</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
    body {
        background-image:
            linear-gradient(rgba(0, 0, 0, 0.25), rgba(0, 0, 0, 0.25)),
            url('includes/assets/img/login.jpg');
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        background-attachment: fixed;
        min-height: 100vh;
        display: flex;
        align-items: center;
    }

    .login-card {
        border: none;
        border-radius: 1rem;
        box-shadow: 0 20px 60px rgba(0, 0, 0, .4);
        max-width: 420px;
        width: 100%;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(5px);
    }

    .login-logo {
        width: 64px;
        height: 64px;
        background: linear-gradient(135deg, #0d6efd, #6f42c1);
        border-radius: 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .form-control:focus {
        border-color: #0d6efd;
        box-shadow: 0 0 0 .2rem rgba(13, 110, 253, .18);
    }

    .btn-login {
        background: linear-gradient(135deg, #0d6efd, #6f42c1);
        border: none;
        padding: .75rem;
        font-weight: 600;
        letter-spacing: .3px;
    }

    .btn-login:hover {
        opacity: .9;
    }

    @media (max-width: 768px) {
        body {
            background-position: left center;
        }

        .login-card {
            max-width: 390px;
        }
    }
</style>
</head>
<body>
<div class="container">
    <div class="d-flex justify-content-end pe-md-5">
        <div class="login-card card p-4 p-md-5">
            <div class="text-center mb-4">
                <div class="login-logo mx-auto mb-3">
                    <i class="bi bi-heart-pulse-fill text-white fs-3"></i>
                </div>
                <h4 class="fw-bold mb-0">SIGVET</h4>
                <small class="text-muted">Sistema Integral de Gestión Veterinaria</small>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger d-flex align-items-center gap-2">
                    <i class="bi bi-exclamation-triangle-fill"></i> <?= limpiar($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" autocomplete="off">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Correo</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" name="login" class="form-control" placeholder="admin" required
                               value="<?= limpiar($_POST['login'] ?? '') ?>">
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label fw-semibold">Contraseña</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" name="password" id="pwField" class="form-control" placeholder="••••••••" required>
                        <button type="button" class="input-group-text" onclick="togglePw()"><i class="bi bi-eye" id="eyeIcon"></i></button>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-login w-100 rounded-pill">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Iniciar sesión
                </button>
            </form>

        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function togglePw() {
    var f = document.getElementById('pwField');
    var i = document.getElementById('eyeIcon');
    f.type = f.type === 'password' ? 'text' : 'password';
    i.className = f.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
}
</script>
</body>
</html>
