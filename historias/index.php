<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';
verificarSesion();
$paginaTitulo = 'Historias clínicas';
$conn = conectar();

$buscar = trim($_GET['q'] ?? '');
$where = "WHERE 1=1";
if ($buscar) {
    $b = $conn->real_escape_string($buscar);
    $where .= " AND (p.nombre LIKE '%$b%' OR h.motivo LIKE '%$b%' OR h.diagnostico LIKE '%$b%')";
}

$historias = $conn->query("SELECT h.*, p.nombre AS paciente, e.nombre AS especie,
    CONCAT(pr.nombres,' ',pr.apellidos) AS propietario,
    CONCAT(u.nombres,' ',u.apellidos) AS veterinario
    FROM historias_clinicas h
    JOIN pacientes p ON h.paciente_id=p.id
    JOIN especies e ON p.especie_id=e.id
    JOIN propietarios pr ON p.propietario_id=pr.id
    JOIN usuarios u ON h.veterinario_id=u.id
    $where ORDER BY h.fecha_hora DESC LIMIT 50");

include '../includes/header.php';
?>

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between py-3 flex-wrap gap-2">
        <span><i class="bi bi-journal-medical me-2 text-info"></i>Historial de consultas</span>
        <div class="d-flex gap-2">
            <form class="d-flex gap-2" method="GET">
                <input type="text" name="q" class="form-control form-control-sm" placeholder="Buscar paciente, diagnóstico..." value="<?= limpiar($buscar) ?>">
                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-search"></i></button>
            </form>
            <a href="crear.php" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i>Nueva</a>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Fecha</th><th>Paciente</th><th>Motivo</th><th>Diagnóstico</th><th>Veterinario</th><th>Acciones</th></tr></thead>
            <tbody>
            <?php if ($historias->num_rows === 0): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">No se encontraron historias</td></tr>
            <?php else: while ($h = $historias->fetch_assoc()): ?>
                <tr>
                    <td><?= date('d/m/Y H:i', strtotime($h['fecha_hora'])) ?></td>
                    <td>
                        <a href="/sigvet/pacientes/perfil.php?id=<?= $h['paciente_id'] ?>">
                            <strong><?= limpiar($h['paciente']) ?></strong>
                        </a>
                        <small class="text-muted d-block"><?= limpiar($h['propietario']) ?></small>
                    </td>
                    <td class="text-truncate" style="max-width:160px"><?= limpiar($h['motivo']) ?></td>
                    <td class="text-truncate" style="max-width:160px"><?= limpiar($h['diagnostico'] ?? '-') ?></td>
                    <td><?= limpiar($h['veterinario']) ?></td>
                    <td>
                        <a href="ver.php?id=<?= $h['id'] ?>" class="btn btn-sm btn-outline-info" title="Ver"><i class="bi bi-eye"></i></a>
                    </td>
                </tr>
            <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php $conn->close(); include '../includes/footer.php'; ?>
