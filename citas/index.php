<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';
verificarSesion();
$paginaTitulo = 'Citas';
$conn = conectar();

// Cambiar estado
if (isset($_GET['estado']) && isset($_GET['id'])) {
    $cid = (int)$_GET['id'];
    $est = $conn->real_escape_string($_GET['estado']);
    $validos = ['programada','confirmada','atendida','cancelada','no_asistio'];
    if (in_array($est, $validos)) {
        $conn->query("UPDATE citas SET estado='$est' WHERE id=$cid");
        registrarLog($conn, "Cambió estado cita a $est", 'citas', $cid);
    }
    header('Location: index.php');
    exit;
}

$filtroFecha = $_GET['fecha'] ?? date('Y-m-d');
$citas = $conn->query("SELECT c.*, p.nombre AS paciente, e.nombre AS especie,
    CONCAT(pr.nombres,' ',pr.apellidos) AS propietario, pr.telefono AS prop_tel,
    CONCAT(u.nombres,' ',u.apellidos) AS veterinario
    FROM citas c
    JOIN pacientes p ON c.paciente_id=p.id
    JOIN especies e ON p.especie_id=e.id
    JOIN propietarios pr ON c.propietario_id=pr.id
    LEFT JOIN usuarios u ON c.veterinario_id=u.id
    WHERE DATE(c.fecha_hora)='".($filtroFecha ?: date('Y-m-d'))."'
    ORDER BY c.fecha_hora ASC");

$estados = ['programada','confirmada','atendida','cancelada','no_asistio'];

include '../includes/header.php';
?>

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between py-3 flex-wrap gap-2">
        <span><i class="bi bi-calendar-check me-2 text-warning"></i>Citas del día</span>
        <div class="d-flex gap-2 align-items-center">
            <form class="d-flex gap-2" method="GET">
                <input type="date" name="fecha" class="form-control form-control-sm" value="<?= limpiar($filtroFecha) ?>">
                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-search"></i></button>
            </form>
            <a href="crear.php" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i>Nueva cita</a>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr><th>Hora</th><th>Paciente</th><th>Propietario</th><th>Motivo</th><th>Veterinario</th><th>Estado</th><th>Acciones</th></tr>
            </thead>
            <tbody>
            <?php if ($citas->num_rows === 0): ?>
                <tr><td colspan="7" class="text-center text-muted py-4"><i class="bi bi-calendar-x d-block fs-3 mb-2"></i>No hay citas para esta fecha</td></tr>
            <?php else: while ($c = $citas->fetch_assoc()): ?>
                <tr>
                    <td class="fw-semibold"><?= date('H:i', strtotime($c['fecha_hora'])) ?></td>
                    <td>
                        <a href="/sigvet/pacientes/perfil.php?id=<?= $c['paciente_id'] ?>"><?= limpiar($c['paciente']) ?></a>
                        <small class="text-muted d-block"><?= limpiar($c['especie']) ?></small>
                    </td>
                    <td><?= limpiar($c['propietario']) ?><br><small class="text-muted"><?= limpiar($c['prop_tel']) ?></small></td>
                    <td class="text-truncate" style="max-width:160px"><?= limpiar($c['motivo']) ?></td>
                    <td><?= limpiar($c['veterinario'] ?? 'Sin asignar') ?></td>
                    <td>
                        <div class="dropdown">
                            <button class="btn btn-sm badge-<?= $c['estado'] ?> border-0 dropdown-toggle" data-bs-toggle="dropdown">
                                <?= ucfirst(str_replace('_',' ',$c['estado'])) ?>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <?php foreach ($estados as $est): ?>
                                    <li><a class="dropdown-item" href="?id=<?= $c['id'] ?>&estado=<?= $est ?>">
                                        <?= ucfirst(str_replace('_',' ',$est)) ?>
                                    </a></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="editar.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            <a href="/sigvet/historias/crear.php?paciente=<?= $c['paciente_id'] ?>" class="btn btn-sm btn-outline-success" title="Historia"><i class="bi bi-journal-plus"></i></a>
                        </div>
                    </td>
                </tr>
            <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php $conn->close(); include '../includes/footer.php'; ?>
