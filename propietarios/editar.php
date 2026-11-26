<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';
verificarSesion();
$conn = conectar();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: index.php'); exit; }

$row = $conn->query("SELECT * FROM propietarios WHERE id=$id AND activo=1")->fetch_assoc();
if (!$row) { header('Location: index.php'); exit; }

$paginaTitulo = 'Editar propietario';
$errores = [];
$datos = $row;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (['nombres','apellidos','tipo_identificacion','numero_identificacion','telefono','email','direccion','ciudad','observaciones'] as $k)
        $datos[$k] = trim($_POST[$k] ?? '');
    if (!$datos['nombres']) $errores[] = 'El nombre es requerido.';
    if (!$datos['apellidos']) $errores[] = 'El apellido es requerido.';

    if (empty($errores)) {
        $stmt = $conn->prepare("UPDATE propietarios SET nombres=?,apellidos=?,tipo_identificacion=?,numero_identificacion=?,telefono=?,email=?,direccion=?,ciudad=?,observaciones=? WHERE id=?");
        $stmt->bind_param('sssssssssi', $datos['nombres'],$datos['apellidos'],$datos['tipo_identificacion'],$datos['numero_identificacion'],$datos['telefono'],$datos['email'],$datos['direccion'],$datos['ciudad'],$datos['observaciones'],$id);
        $stmt->execute();
        $stmt->close();
        registrarLog($conn, 'Editó propietario', 'propietarios', $id);
        $conn->close();
        header('Location: index.php?ok=2');
        exit;
    }
}

include '../includes/header.php';
?>

<?php if ($errores): ?>
    <?= alerta('danger', implode('<br>', array_map('limpiar', $errores))) ?>
<?php endif; ?>

<div class="card" style="max-width:720px">
    <div class="card-header py-3"><i class="bi bi-pencil me-2"></i>Editar propietario</div>
    <div class="card-body">
        <form method="POST">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nombres <span class="text-danger">*</span></label>
                    <input type="text" name="nombres" class="form-control" value="<?= limpiar($datos['nombres']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Apellidos <span class="text-danger">*</span></label>
                    <input type="text" name="apellidos" class="form-control" value="<?= limpiar($datos['apellidos']) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Tipo identificación</label>
                    <select name="tipo_identificacion" class="form-select">
                        <?php foreach (['CC','CE','NIT','PP','TI','Otro'] as $t): ?>
                            <option value="<?= $t ?>" <?= $datos['tipo_identificacion']===$t?'selected':'' ?>><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-8">
                    <label class="form-label">Número identificación</label>
                    <input type="text" name="numero_identificacion" class="form-control" value="<?= limpiar($datos['numero_identificacion']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Teléfono</label>
                    <input type="text" name="telefono" class="form-control" value="<?= limpiar($datos['telefono']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Correo electrónico</label>
                    <input type="email" name="email" class="form-control" value="<?= limpiar($datos['email']) ?>">
                </div>
                <div class="col-md-8">
                    <label class="form-label">Dirección</label>
                    <input type="text" name="direccion" class="form-control" value="<?= limpiar($datos['direccion']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Ciudad</label>
                    <input type="text" name="ciudad" class="form-control" value="<?= limpiar($datos['ciudad']) ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">Observaciones</label>
                    <textarea name="observaciones" class="form-control" rows="2"><?= limpiar($datos['observaciones']) ?></textarea>
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
