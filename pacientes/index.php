<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';
verificarSesion();
$paginaTitulo = 'Pacientes';
$conn = conectar();

if (isset($_GET['del']) && is_numeric($_GET['del'])) {
    $id = (int)$_GET['del'];
    $conn->query("UPDATE pacientes SET activo=0 WHERE id=$id");
    registrarLog($conn, 'Eliminó paciente', 'pacientes', $id);
    $msg = alerta('success', 'Paciente eliminado.');
}

$buscar = trim($_GET['q'] ?? '');
$propFiltro = (int)($_GET['propietario'] ?? 0);
$where = "WHERE p.activo=1";
if ($buscar) {
    $b = $conn->real_escape_string($buscar);
    $where .= " AND (p.nombre LIKE '%$b%' OR e.nombre LIKE '%$b%' OR r.nombre LIKE '%$b%' OR CONCAT(pr.nombres,' ',pr.apellidos) LIKE '%$b%')";
}
if ($propFiltro) $where .= " AND p.propietario_id=$propFiltro";

$pacientes = $conn->query("SELECT p.*, e.nombre AS especie, r.nombre AS raza,
    CONCAT(pr.nombres,' ',pr.apellidos) AS propietario, pr.id AS prop_id
    FROM pacientes p
    JOIN especies e ON p.especie_id=e.id
    LEFT JOIN razas r ON p.raza_id=r.id
    JOIN propietarios pr ON p.propietario_id=pr.id
    $where ORDER BY p.nombre");

include '../includes/header.php';
?>

<?= $msg ?? '' ?>

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between py-3 flex-wrap gap-2">
        <span><i class="bi bi-paw me-2 text-success"></i>Listado de pacientes</span>
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
                <tr><th>#</th><th>Nombre</th><th>Especie/Raza</th><th>Sexo</th><th>Propietario</th><th>Peso</th><th>Estado repro.</th><th>Acciones</th></tr>
            </thead>
            <tbody>
            <?php if ($pacientes->num_rows === 0): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">No se encontraron pacientes</td></tr>
            <?php else: ?>
            <?php while ($row = $pacientes->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <?php if ($row['foto']): ?>
                                <img src="/sigvet/pacientes/uploads/<?= limpiar($row['foto']) ?>" class="rounded-circle" width="36" height="36" style="object-fit:cover">
                            <?php else: ?>
                                <div class="rounded-circle bg-success d-flex align-items-center justify-content-center text-white" style="width:36px;height:36px;font-size:.9rem">
                                    <i class="bi bi-paw"></i>
                                </div>
                            <?php endif; ?>
                            <span class="fw-semibold"><?= limpiar($row['nombre']) ?></span>
                        </div>
                    </td>
                    <td><?= limpiar($row['especie']) ?><?= $row['raza'] ? ' / '.limpiar($row['raza']) : '' ?></td>
                    <td><?= limpiar($row['sexo']) ?></td>
                    <td><a href="/sigvet/propietarios/editar.php?id=<?= $row['prop_id'] ?>"><?= limpiar($row['propietario']) ?></a></td>
                    <td><?= $row['peso'] ? $row['peso'].' kg' : '-' ?></td>
                    <td><?= limpiar($row['estado_reproductivo']) ?></td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="perfil.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-info" title="Perfil"><i class="bi bi-eye"></i></a>
                            <a href="editar.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-primary" title="Editar"><i class="bi bi-pencil"></i></a>
                            <a href="/sigvet/historias/crear.php?paciente=<?= $row['id'] ?>" class="btn btn-sm btn-outline-success" title="Nueva historia"><i class="bi bi-journal-plus"></i></a>
                            <a href="?del=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger"
                               data-confirm="¿Eliminar paciente <?= limpiar($row['nombre']) ?>?" title="Eliminar"><i class="bi bi-trash"></i></a>
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
