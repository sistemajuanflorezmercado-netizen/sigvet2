<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';
verificarSesion();
soloAdmin();
$paginaTitulo = 'Nuevo usuario';
$conn = conectar();

$roles = $conn->query("SELECT * FROM roles ORDER BY id");
$errores = [];
$datos = ['nombres'=>'','apellidos'=>'','email'=>'','username'=>'','rol_id'=>'4'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($datos as $k => $_) $datos[$k] = trim($_POST[$k] ?? '');
    $pass = $_POST['password'] ?? '';
    $pass2 = $_POST['password2'] ?? '';
    if (!$datos['nombres']) $errores[] = 'El nombre es requerido.';
    if (!$datos['email']) $errores[] = 'El email es requerido.';
    if (!$datos['username']) $errores[] = 'El usuario es requerido.';
    if (strlen($pass) < 6) $errores[] = 'La contraseña debe tener al menos 6 caracteres.';
    if ($pass !== $pass2) $errores[] = 'Las contraseñas no coinciden.';

    // Verificar duplicados
    $em = $conn->real_escape_string($datos['email']);
    $un = $conn->real_escape_string($datos['username']);
    if ($conn->query("SELECT id FROM usuarios WHERE email='$em' OR username='$un'")->num_rows > 0)
        $errores[] = 'El email o usuario ya está registrado.';

    if (empty($errores)) {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $nom = $conn->real_escape_string($datos['nombres']);
        $ape = $conn->real_escape_string($datos['apellidos']);
        $rid = (int)$datos['rol_id'];
        $conn->query("INSERT INTO usuarios (nombres,apellidos,email,username,password,rol_id) VALUES ('$nom','$ape','$em','$un','$hash',$rid)");
        $newId = $conn->insert_id;
        registrarLog($conn, 'Creó usuario', 'usuarios', $newId);
        $conn->close();
        header('Location: index.php?ok=1');
        exit;
    }
}

include '../includes/header.php';
?>

<?php if ($errores): ?>
    <?= alerta('danger', implode('<br>', array_map('limpiar', $errores))) ?>
<?php endif; ?>

<div class="card" style="max-width:600px">
    <div class="card-header py-3"><i class="bi bi-person-plus me-2"></i>Crear usuario</div>
    <div class="card-body">
        <form method="POST">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nombres <span class="text-danger">*</span></label>
                    <input type="text" name="nombres" class="form-control" value="<?= limpiar($datos['nombres']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Apellidos</label>
                    <input type="text" name="apellidos" class="form-control" value="<?= limpiar($datos['apellidos']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control" value="<?= limpiar($datos['email']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Usuario <span class="text-danger">*</span></label>
                    <input type="text" name="username" class="form-control" value="<?= limpiar($datos['username']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Contraseña <span class="text-danger">*</span></label>
                    <input type="password" name="password" class="form-control" required minlength="6">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Confirmar contraseña</label>
                    <input type="password" name="password2" class="form-control" required>
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
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Crear usuario</button>
                <a href="index.php" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php $conn->close(); include '../includes/footer.php'; ?>
