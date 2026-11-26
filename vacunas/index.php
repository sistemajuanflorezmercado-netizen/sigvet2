<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';
verificarSesion();
$paginaTitulo = 'Vacunación';
$conn = conectar();

if (isset($_GET['del']) && is_numeric($_GET['del'])) {
    $id = (int)$_GET['del'];
    $conn->query("DELETE FROM vacunas WHERE id=$id");
    registrarLog($conn, 'Eliminó vacuna', 'vacunas', $id);
    $msg = alerta('success', 'Registro eliminado.');
}

$vacunas = $conn->query("SELECT v.*, p.nombre AS paciente, e.nombre AS especie,
    CONCAT(pr.nombres,' ',pr.apellidos) AS propietario,
    CONCAT(u.nombres,' ',u.apellidos) AS veterinario
    FROM vacunas v
    JOIN pacientes p ON v.paciente_id=p.id
    JOIN especies e ON p.especie_id=e.id
    JOIN propietarios pr ON p.propietario_id=pr.id
    LEFT JOIN usuarios u ON v.veterinario_id=u.id
    ORDER BY v.fecha_aplicacion DESC LIMIT 60");

include '../includes/header.php';
?>

<?= $msg ?? '' ?>

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between py-3">
        <span><i class="bi bi-droplet-fill me-2 text-danger"></i>Registro de vacunas</span>
        <div class="d-flex gap-2">
            <a href="crear.php" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i>Registrar</a>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Fecha</th><th>Paciente</th><th>Propietario</th><th>Vacuna</th><th>Lote</th><th>Próxima dosis</th><th>Veterinario</th><th></th></tr></thead>
            <tbody>
            <?php if ($vacunas->num_rows === 0): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">Sin registros</td></tr>
            <?php else: while ($v = $vacunas->fetch_assoc()):
                $diasProx = $v['proxima_dosis'] ? (int)ceil((strtotime($v['proxima_dosis'])-time())/86400) : null;
            ?>
                <tr>
                    <td><?= date('d/m/Y', strtotime($v['fecha_aplicacion'])) ?></td>
                    <td><a href="/sigvet/pacientes/perfil.php?id=<?= $v['paciente_id'] ?>"><?= limpiar($v['paciente']) ?></a><br><small class="text-muted"><?= limpiar($v['especie']) ?></small></td>
                    <td><?= limpiar($v['propietario']) ?></td>
                    <td class="fw-semibold"><?= limpiar($v['tipo_vacuna']) ?></td>
                    <td><?= limpiar($v['lote'] ?? '-') ?></td>
                    <td>
                        <?php if ($v['proxima_dosis']): ?>
                            <?= date('d/m/Y', strtotime($v['proxima_dosis'])) ?>
                            <span class="ms-1 badge <?= $diasProx <= 0 ? 'bg-danger' : ($diasProx <= 15 ? 'bg-warning text-dark' : 'bg-success') ?>">
                                <?= $diasProx <= 0 ? 'Vencida' : "en $diasProx d." ?>
                            </span>
                        <?php else: echo '-'; endif; ?>
                    </td>
                    <td><?= limpiar($v['veterinario'] ?? '-') ?></td>
                    <td>
                        <a href="certificado.php?paciente=<?= $v['paciente_id'] ?>" target="_blank" class="btn btn-sm btn-outline-warning" title="Certificado del paciente"><i class="bi bi-award"></i></a>
                    <a href="?del=<?= $v['id'] ?>" class="btn btn-sm btn-outline-danger"
                           data-confirm="¿Eliminar este registro de vacuna?"><i class="bi bi-trash"></i></a>
                    </td>
                </tr>
            <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php $conn->close(); include '../includes/footer.php'; ?>
