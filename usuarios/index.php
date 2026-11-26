<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';
verificarSesion();
soloAdmin();
$paginaTitulo = 'Usuarios';
$conn = conectar();

if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $uid = (int)$_GET['toggle'];
    if ($uid !== (int)$_SESSION['usuario_id']) {
        $conn->query("UPDATE usuarios SET activo = 1 - activo WHERE id=$uid");
        registrarLog($conn, 'Cambió estado usuario', 'usuarios', $uid);
    }
    header('Location: index.php');
    exit;
}

$usuarios = $conn->query("SELECT u.*, r.nombre AS rol FROM usuarios u JOIN roles r ON u.rol_id=r.id ORDER BY u.activo DESC, u.nombres");

include '../includes/header.php';
?>

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between py-3">
        <span><i class="bi bi-people me-2"></i>Usuarios del sistema</span>
        <a href="crear.php" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i>Nuevo usuario</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>#</th><th>Nombre</th><th>Usuario</th><th>Email</th><th>Rol</th><th>Estado</th><th>Acciones</th></tr></thead>
            <tbody>
            <?php while ($u = $usuarios->fetch_assoc()): ?>
                <tr class="<?= !$u['activo'] ? 'table-secondary text-muted' : '' ?>">
                    <td><?= $u['id'] ?></td>
                    <td><?= limpiar($u['nombres'] . ' ' . $u['apellidos']) ?></td>
                    <td><code><?= limpiar($u['username']) ?></code></td>
                    <td><?= limpiar($u['email']) ?></td>
                    <td><span class="badge bg-primary text-capitalize"><?= limpiar($u['rol']) ?></span></td>
                    <td>
                        <?php if ($u['activo']): ?>
                            <span class="badge bg-success">Activo</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Inactivo</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="editar.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            <?php if ($u['id'] !== (int)$_SESSION['usuario_id']): ?>
                                <a href="?toggle=<?= $u['id'] ?>" class="btn btn-sm <?= $u['activo'] ? 'btn-outline-warning' : 'btn-outline-success' ?>"
                                   data-confirm="<?= $u['activo'] ? 'Desactivar' : 'Activar' ?> a <?= limpiar($u['nombres']) ?>?">
                                    <i class="bi bi-<?= $u['activo'] ? 'pause' : 'play' ?>"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php $conn->close(); include '../includes/footer.php'; ?>
