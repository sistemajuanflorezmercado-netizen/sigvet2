<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';
verificarSesion();
$paginaTitulo = 'Propietarios';
$conn = conectar();

// Eliminar
if (isset($_GET['del']) && is_numeric($_GET['del'])) {
    $id = (int)$_GET['del'];
    // Verificar que no tenga pacientes activos
    $chk = $conn->query("SELECT COUNT(*) c FROM pacientes WHERE propietario_id=$id AND activo=1")->fetch_assoc()['c'];
    if ($chk > 0) {
        $msg = alerta('warning', 'No se puede eliminar: el propietario tiene pacientes activos.');
    } else {
        $conn->query("UPDATE propietarios SET activo=0 WHERE id=$id");
        registrarLog($conn, 'Eliminó propietario', 'propietarios', $id);
        $msg = alerta('success', 'Propietario eliminado correctamente.');
    }
}

// Búsqueda
$buscar = trim($_GET['q'] ?? '');
$where = "WHERE p.activo=1";
if ($buscar) {
    $b = $conn->real_escape_string($buscar);
    $where .= " AND (p.nombres LIKE '%$b%' OR p.apellidos LIKE '%$b%' OR p.numero_identificacion LIKE '%$b%' OR p.telefono LIKE '%$b%' OR p.email LIKE '%$b%')";
}

$propietarios = $conn->query("SELECT p.*, (SELECT COUNT(*) FROM pacientes WHERE propietario_id=p.id AND activo=1) AS num_pacientes
    FROM propietarios p $where ORDER BY p.apellidos, p.nombres");

include '../includes/header.php';
?>

<?= $msg ?? '' ?>

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between py-3 flex-wrap gap-2">
        <span><i class="bi bi-person-lines-fill me-2 text-primary"></i>Listado de propietarios</span>
        <div class="d-flex gap-2">
            <form class="d-flex gap-2" method="GET">
                <input type="text" name="q" class="form-control form-control-sm" placeholder="Buscar..." value="<?= limpiar($buscar) ?>">
                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-search"></i></button>
            </form>
            <a href="crear.php" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i>Nuevo</a>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>#</th><th>Nombre</th><th>Identificación</th><th>Teléfono</th>
                    <th>Email</th><th>Ciudad</th><th>Pacientes</th><th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($propietarios->num_rows === 0): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">No se encontraron propietarios</td></tr>
            <?php else: ?>
            <?php while ($row = $propietarios->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td class="fw-semibold"><?= limpiar($row['nombres'] . ' ' . $row['apellidos']) ?></td>
                    <td><small class="text-muted"><?= limpiar($row['tipo_identificacion']) ?></small> <?= limpiar($row['numero_identificacion']) ?></td>
                    <td><?= limpiar($row['telefono']) ?></td>
                    <td><?= limpiar($row['email']) ?></td>
                    <td><?= limpiar($row['ciudad']) ?></td>
                    <td><span class="badge bg-primary"><?= $row['num_pacientes'] ?></span></td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="editar.php?id=<?= $row['id'] ?>" class="btn btn-xs btn-outline-primary btn-sm" title="Editar"><i class="bi bi-pencil"></i></a>
                            <a href="/sigvet/pacientes/?propietario=<?= $row['id'] ?>" class="btn btn-xs btn-outline-success btn-sm" title="Ver pacientes"><i class="bi bi-paw"></i></a>
                            <a href="?del=<?= $row['id'] ?>" class="btn btn-xs btn-outline-danger btn-sm"
                               data-confirm="¿Eliminar a <?= limpiar($row['nombres'] . ' ' . $row['apellidos']) ?>?" title="Eliminar"><i class="bi bi-trash"></i></a>
                        </div>
                    </td>
                </tr>
            <?php endwhile; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php $conn->close(); include '../includes/footer.php'; ?>
