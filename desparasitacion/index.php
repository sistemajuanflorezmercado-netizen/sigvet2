<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';
verificarSesion();
$paginaTitulo = 'Desparasitación';
$conn = conectar();

if (isset($_GET['del']) && is_numeric($_GET['del'])) {
    $id = (int)$_GET['del'];
    $conn->query("DELETE FROM desparasitaciones WHERE id=$id");
    registrarLog($conn, 'Eliminó desparasitación', 'desparasitaciones', $id);
    $msg = alerta('success', 'Registro eliminado.');
}

$registros = $conn->query("SELECT d.*, p.nombre AS paciente, e.nombre AS especie, CONCAT(pr.nombres,' ',pr.apellidos) AS propietario
    FROM desparasitaciones d
    JOIN pacientes p ON d.paciente_id=p.id
    JOIN especies e ON p.especie_id=e.id
    JOIN propietarios pr ON p.propietario_id=pr.id
    ORDER BY d.fecha DESC LIMIT 60");

include '../includes/header.php';
?>

<?= $msg ?? '' ?>

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between py-3">
        <span><i class="bi bi-shield-plus me-2 text-success"></i>Registro de desparasitaciones</span>
        <a href="crear.php" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i>Registrar</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Fecha</th><th>Paciente</th><th>Propietario</th><th>Producto</th><th>Tipo</th><th>Dosis</th><th>Próxima</th><th></th></tr></thead>
            <tbody>
            <?php if ($registros->num_rows === 0): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">Sin registros</td></tr>
            <?php else: while ($d = $registros->fetch_assoc()):
                $diasProx = $d['proxima_fecha'] ? (int)ceil((strtotime($d['proxima_fecha'])-time())/86400) : null;
            ?>
                <tr>
                    <td><?= date('d/m/Y', strtotime($d['fecha'])) ?></td>
                    <td><a href="/sigvet/pacientes/perfil.php?id=<?= $d['paciente_id'] ?>"><?= limpiar($d['paciente']) ?></a><br><small class="text-muted"><?= limpiar($d['especie']) ?></small></td>
                    <td><?= limpiar($d['propietario']) ?></td>
                    <td class="fw-semibold"><?= limpiar($d['producto']) ?></td>
                    <td><span class="badge bg-secondary"><?= ucfirst($d['tipo']) ?></span></td>
                    <td><?= limpiar($d['dosis'] ?? '-') ?></td>
                    <td>
                        <?php if ($d['proxima_fecha']): ?>
                            <?= date('d/m/Y', strtotime($d['proxima_fecha'])) ?>
                            <?php if ($diasProx !== null): ?>
                                <span class="badge <?= $diasProx<=0?'bg-danger':($diasProx<=15?'bg-warning text-dark':'bg-success') ?>">
                                    <?= $diasProx<=0?'Vencida':"en $diasProx d." ?>
                                </span>
                            <?php endif; ?>
                        <?php else: echo '-'; endif; ?>
                    </td>
                    <td>
                        <a href="?del=<?= $d['id'] ?>" class="btn btn-sm btn-outline-danger"
                           data-confirm="¿Eliminar este registro?"><i class="bi bi-trash"></i></a>
                    </td>
                </tr>
            <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php $conn->close(); include '../includes/footer.php'; ?>
