<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';
verificarSesion();
soloAdmin();
$conn = conectar();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: index.php'); exit; }

$user = $conn->query("SELECT * FROM usuarios WHERE id=$id")->fetch_assoc();
if (!$user) { header('Location: index.php'); exit; }

$paginaTitulo = 'Editar usuario';
$roles = $conn->query("SELECT * FROM roles ORDER BY id");
$errores = [];
$datos = $user;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (['nombres','apellidos','email','username','rol_id'] as $k) $datos[$k] = trim($_POST[$k] ?? '');
    $newPass = $_POST['password'] ?? '';
    if (!$datos['nombres']) $errores[] = 'El nombre es requerido.';
    if (!$datos['email']) $errores[] = 'El email es requerido.';
    if ($newPass && strlen($newPass) < 6) $errores[] = 'La contraseña debe tener al menos 6 caracteres.';
    if ($newPass && $newPass !== ($_POST['password2'] ?? '')) $errores[] = 'Las contraseñas no coinciden.';

    if (empty($errores)) {
        $nom = $conn->real_escape_string($datos['nombres']);
        $ape = $conn->real_escape_string($datos['apellidos']);
        $em = $conn->real_escape_string($datos['email']);
        $un = $conn->real_escape_string($datos['username']);
        $rid = (int)$datos['rol_id'];
        $conn->query("UPDATE usuarios SET nombres='$nom',apellidos='$ape',email='$em',username='$un',rol_id=$rid WHERE id=$id");
        if ($newPass) {
            $hash = password_hash($newPass, PASSWORD_DEFAULT);
            $conn->query("UPDATE usuarios SET password='$hash' WHERE id=$id");
        }
        registrarLog($conn, 'Editó usuario', 'usuarios', $id);
        $conn->close();
        header('Location: index.php?ok=2');
        exit;
    }
}

include '../includes/header.php';
?>

<div class="card" style="max-width:600px">
    <div class="card-header py-3"><i class="bi bi-pencil me-2"></i>Editar usuario</div>
    <div class="card-body">
        <form method="POST">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nombres</label>
                    <input type="text" name="nombres" class="form-control" value="<?= limpiar($datos['nombres']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Apellidos</label>
                    <input type="text" name="apellidos" class="form-control" value="<?= limpiar($datos['apellidos']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="<?= limpiar($datos['email']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Usuario</label>
                    <input type="text" name="username" class="form-control" value="<?= limpiar($datos['username']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Nueva contraseña <small class="text-muted">(dejar vacío para no cambiar)</small></label>
                    <input type="password" name="password" class="form-control" minlength="6">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Confirmar contraseña</label>
                    <input type="password" name="password2" class="form-control">
                </div>
                <div class="col-12">
                    <label class="form-label">Rol</label>
                    <select name="rol_id" class="form-select">
                        <?php while ($r = $roles->fetch_assoc()): ?>
                            <option value="<?= $r['id'] ?>" <?= $datos['rol_id']==$r['id']?'selected':'' ?>><?= limpiar(ucfirst($r['nombre'])) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Actualizar</button>
                <a href="index.php" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php $conn->close(); include '../includes/footer.php'; ?>
