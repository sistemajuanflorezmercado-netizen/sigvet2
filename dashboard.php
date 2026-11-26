<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';
verificarSesion();

$paginaTitulo = 'Panel principal';
$conn = conectar();

// Stats
$stats = [];
$stats['propietarios'] = $conn->query("SELECT COUNT(*) c FROM propietarios WHERE activo=1")->fetch_assoc()['c'];
$stats['pacientes'] = $conn->query("SELECT COUNT(*) c FROM pacientes WHERE activo=1")->fetch_assoc()['c'];
$stats['citas_hoy'] = $conn->query("SELECT COUNT(*) c FROM citas WHERE DATE(fecha_hora)=CURDATE() AND estado IN('programada','confirmada')")->fetch_assoc()['c'];
$stats['vacunas_proximas'] = $conn->query("SELECT COUNT(*) c FROM vacunas WHERE proxima_dosis BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)")->fetch_assoc()['c'];

// Citas de hoy
$citas_hoy = $conn->query("SELECT c.*, p.nombre AS paciente, CONCAT(pr.nombres,' ',pr.apellidos) AS propietario,
    CONCAT(u.nombres,' ',u.apellidos) AS veterinario
    FROM citas c
    JOIN pacientes p ON c.paciente_id=p.id
    JOIN propietarios pr ON c.propietario_id=pr.id
    LEFT JOIN usuarios u ON c.veterinario_id=u.id
    WHERE DATE(c.fecha_hora)=CURDATE()
    ORDER BY c.fecha_hora ASC LIMIT 10");

// Últimas historias
$ultimas_historias = $conn->query("SELECT h.*, p.nombre AS paciente, CONCAT(u.nombres,' ',u.apellidos) AS veterinario
    FROM historias_clinicas h
    JOIN pacientes p ON h.paciente_id=p.id
    JOIN usuarios u ON h.veterinario_id=u.id
    ORDER BY h.created_at DESC LIMIT 5");

// Vacunas próximas
$vacunas_proximas = $conn->query("SELECT v.*, p.nombre AS paciente FROM vacunas v
    JOIN pacientes p ON v.paciente_id=p.id
    WHERE v.proxima_dosis BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
    ORDER BY v.proxima_dosis ASC LIMIT 5");

include 'includes/header.php';
?>

<?php if (isset($_GET['error']) && $_GET['error'] === 'acceso'): ?>
    <?= alerta('danger', 'No tiene permisos para acceder a esa sección.') ?>
<?php endif; ?>

<!-- Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card" style="background:linear-gradient(135deg,#0d6efd,#6f42c1)">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div style="font-size:.8rem;opacity:.8">Propietarios</div>
                    <div class="fw-bold" style="font-size:2rem"><?= $stats['propietarios'] ?></div>
                </div>
                <i class="bi bi-person-lines-fill"></i>
            </div>
            <a href="/sigvet/propietarios/" class="text-white-50" style="font-size:.8rem">Ver todos →</a>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card" style="background:linear-gradient(135deg,#198754,#20c997)">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div style="font-size:.8rem;opacity:.8">Pacientes</div>
                    <div class="fw-bold" style="font-size:2rem"><?= $stats['pacientes'] ?></div>
                </div>
                <i class="bi bi-paw"></i>
            </div>
            <a href="/sigvet/pacientes/" class="text-white-50" style="font-size:.8rem">Ver todos →</a>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card" style="background:linear-gradient(135deg,#fd7e14,#ffc107)">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div style="font-size:.8rem;opacity:.8">Citas hoy</div>
                    <div class="fw-bold" style="font-size:2rem"><?= $stats['citas_hoy'] ?></div>
                </div>
                <i class="bi bi-calendar-check"></i>
            </div>
            <a href="/sigvet/citas/" class="text-white-50" style="font-size:.8rem">Ver citas →</a>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card" style="background:linear-gradient(135deg,#dc3545,#e91e63)">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div style="font-size:.8rem;opacity:.8">Vacunas próximas</div>
                    <div class="fw-bold" style="font-size:2rem"><?= $stats['vacunas_proximas'] ?></div>
                </div>
                <i class="bi bi-droplet-fill"></i>
            </div>
            <a href="/sigvet/vacunas/" class="text-white-50" style="font-size:.8rem">Ver vacunas →</a>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- Citas de hoy -->
    <div class="col-xl-7">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between py-3">
                <span><i class="bi bi-calendar-day me-2 text-primary"></i>Citas de hoy</span>
                <a href="/sigvet/citas/crear.php" class="btn btn-sm btn-primary"><i class="bi bi-plus me-1"></i>Nueva cita</a>
            </div>
            <div class="card-body p-0">
                <?php if ($citas_hoy->num_rows === 0): ?>
                    <div class="text-center text-muted py-4"><i class="bi bi-calendar-x fs-3 d-block mb-2"></i>No hay citas programadas para hoy</div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Hora</th><th>Paciente</th><th>Propietario</th><th>Motivo</th><th>Estado</th></tr></thead>
                        <tbody>
                        <?php while ($c = $citas_hoy->fetch_assoc()): ?>
                            <tr>
                                <td><?= date('H:i', strtotime($c['fecha_hora'])) ?></td>
                                <td><?= limpiar($c['paciente']) ?></td>
                                <td><?= limpiar($c['propietario']) ?></td>
                                <td class="text-truncate" style="max-width:150px"><?= limpiar($c['motivo']) ?></td>
                                <td><span class="badge badge-<?= $c['estado'] ?>"><?= ucfirst(str_replace('_', ' ', $c['estado'])) ?></span></td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Vacunas próximas -->
    <div class="col-xl-5">
        <div class="card mb-3">
            <div class="card-header py-3">
                <i class="bi bi-droplet me-2 text-danger"></i>Vacunas próximas (30 días)
            </div>
            <div class="card-body p-0">
                <?php if ($vacunas_proximas->num_rows === 0): ?>
                    <div class="text-center text-muted py-3"><small>Sin vacunas próximas</small></div>
                <?php else: ?>
                <ul class="list-group list-group-flush">
                <?php while ($v = $vacunas_proximas->fetch_assoc()):
                    $dias = (int)ceil((strtotime($v['proxima_dosis']) - time()) / 86400);
                ?>
                    <li class="list-group-item d-flex align-items-center gap-2 py-2">
                        <div class="flex-grow-1">
                            <div class="fw-semibold" style="font-size:.88rem"><?= limpiar($v['paciente']) ?></div>
                            <small class="text-muted"><?= limpiar($v['tipo_vacuna']) ?></small>
                        </div>
                        <span class="badge <?= $dias <= 7 ? 'bg-danger' : 'bg-warning text-dark' ?>">
                            <?= $dias <= 0 ? 'Vencida' : "en $dias días" ?>
                        </span>
                    </li>
                <?php endwhile; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>

        <!-- Últimas historias -->
        <div class="card">
            <div class="card-header py-3">
                <i class="bi bi-journal-medical me-2 text-info"></i>Últimas consultas
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                <?php while ($h = $ultimas_historias->fetch_assoc()): ?>
                    <li class="list-group-item py-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-semibold" style="font-size:.88rem"><?= limpiar($h['paciente']) ?></div>
                                <small class="text-muted text-truncate d-block" style="max-width:200px"><?= limpiar($h['motivo']) ?></small>
                            </div>
                            <small class="text-muted"><?= date('d/m', strtotime($h['fecha_hora'])) ?></small>
                        </div>
                    </li>
                <?php endwhile; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php
$conn->close();
include 'includes/footer.php';
?>
