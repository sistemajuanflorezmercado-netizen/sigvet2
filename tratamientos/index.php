<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';
verificarSesion();
$paginaTitulo = 'Tratamientos';
$conn = conectar();

if (isset($_GET['del']) && is_numeric($_GET['del'])) {
    $id = (int)$_GET['del'];
    $conn->query("DELETE FROM tratamientos WHERE id=$id");
    $msg = alerta('success', 'Tratamiento eliminado.');
}

$tratamientos = $conn->query("SELECT t.*, p.nombre AS paciente, e.nombre AS especie,
    CONCAT(pr.nombres,' ',pr.apellidos) AS propietario,
    CONCAT(u.nombres,' ',u.apellidos) AS veterinario
    FROM tratamientos t
    JOIN pacientes p ON t.paciente_id=p.id
    JOIN especies e ON p.especie_id=e.id
    JOIN propietarios pr ON p.propietario_id=pr.id
    LEFT JOIN usuarios u ON t.veterinario_id=u.id
    ORDER BY t.created_at DESC LIMIT 60");

include '../includes/header.php';
?>

<?= $msg ?? '' ?>

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between py-3">
        <span><i class="bi bi-capsule me-2 text-primary"></i>Tratamientos registrados</span>
        <a href="crear.php" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i>Registrar</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Paciente</th><th>Medicamento</th><th>Dosis</th><th>Frecuencia</th><th>Vía</th><th>Duración</th><th>Veterinario</th><th></th></tr></thead>
            <tbody>
            <?php if ($tratamientos->num_rows === 0): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">Sin tratamientos registrados</td></tr>
            <?php else: while ($t = $tratamientos->fetch_assoc()): ?>
                <tr>
                    <td><a href="/sigvet/pacientes/perfil.php?id=<?= $t['paciente_id'] ?>"><?= limpiar($t['paciente']) ?></a></td>
                    <td class="fw-semibold"><?= limpiar($t['medicamento']) ?> <small class="text-muted"><?= limpiar($t['presentacion']) ?></small></td>
                    <td><?= limpiar($t['dosis']) ?></td>
                    <td><?= limpiar($t['frecuencia']) ?></td>
                    <td><?= limpiar($t['via_administracion']) ?></td>
                    <td><?= limpiar($t['duracion']) ?></td>
                    <td><?= limpiar($t['veterinario'] ?? '-') ?></td>
                    <td>
                        <a href="?del=<?= $t['id'] ?>" class="btn btn-sm btn-outline-danger"
                           data-confirm="¿Eliminar este tratamiento?"><i class="bi bi-trash"></i></a>
                    </td>
                </tr>
            <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php $conn->close(); include '../includes/footer.php'; ?>
