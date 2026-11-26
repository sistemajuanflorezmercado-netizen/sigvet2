<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';
verificarSesion();
$paginaTitulo = 'Reportes';
$conn = conectar();

$reporte = $_GET['r'] ?? '';
$desde = $_GET['desde'] ?? date('Y-m-01');
$hasta = $_GET['hasta'] ?? date('Y-m-t');

include '../includes/header.php';
?>

<div class="row g-3">
    <!-- Panel filtros -->
    <div class="col-lg-3">
        <div class="card">
            <div class="card-header py-3"><i class="bi bi-bar-chart-line me-2"></i>Reportes disponibles</div>
            <div class="list-group list-group-flush">
                <?php $reportes = [
                    'pacientes' => ['Pacientes registrados','bi-paw'],
                    'propietarios' => ['Propietarios','bi-person-lines-fill'],
                    'citas' => ['Citas por período','bi-calendar-check'],
                    'consultas' => ['Consultas realizadas','bi-journal-medical'],
                    'vacunas' => ['Próximas vacunas','bi-droplet'],
                    'tratamientos' => ['Tratamientos','bi-capsule'],
                    'actividad' => ['Actividad por veterinario','bi-person-badge'],
                ];
                foreach ($reportes as $key => [$label,$ico]): ?>
                    <a href="?r=<?= $key ?>&desde=<?= $desde ?>&hasta=<?= $hasta ?>"
                       class="list-group-item list-group-item-action <?= $reporte===$key?'active':'' ?> d-flex align-items-center gap-2 py-2">
                        <i class="bi <?= $ico ?>"></i> <?= $label ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Filtro fechas -->
        <div class="card mt-3">
            <div class="card-header py-2">Filtrar por período</div>
            <div class="card-body">
                <form method="GET">
                    <input type="hidden" name="r" value="<?= limpiar($reporte) ?>">
                    <div class="mb-2">
                        <label class="form-label small">Desde</label>
                        <input type="date" name="desde" class="form-control form-control-sm" value="<?= limpiar($desde) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Hasta</label>
                        <input type="date" name="hasta" class="form-control form-control-sm" value="<?= limpiar($hasta) ?>">
                    </div>
                    <button class="btn btn-sm btn-primary w-100">Filtrar</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Contenido reporte -->
    <div class="col-lg-9">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center py-3">
                <span>
                    <?php if ($reporte): ?>
                        <i class="bi bi-file-earmark-text me-2"></i><?= $reportes[$reporte][0] ?? '' ?>
                        <small class="text-muted ms-2"><?= date('d/m/Y', strtotime($desde)) ?> – <?= date('d/m/Y', strtotime($hasta)) ?></small>
                    <?php else: ?>
                        <i class="bi bi-bar-chart me-2"></i>Selecciona un reporte
                    <?php endif; ?>
                </span>
                <?php if ($reporte): ?>
                    <button onclick="window.print()" class="btn btn-sm btn-outline-secondary"><i class="bi bi-printer me-1"></i>Imprimir</button>
                <?php endif; ?>
            </div>
            <div class="card-body">

            <?php if (!$reporte): ?>
                <div class="text-center text-muted py-5">
                    <i class="bi bi-bar-chart-line fs-1 d-block mb-3"></i>
                    <p>Selecciona un reporte del panel izquierdo para visualizarlo.</p>
                </div>

            <?php elseif ($reporte === 'pacientes'): ?>
                <?php
                $rows = $conn->query("SELECT p.nombre, e.nombre AS especie, r.nombre AS raza, p.sexo, p.estado_reproductivo,
                    CONCAT(pr.nombres,' ',pr.apellidos) AS propietario, pr.telefono, p.created_at
                    FROM pacientes p JOIN especies e ON p.especie_id=e.id LEFT JOIN razas r ON p.raza_id=r.id
                    JOIN propietarios pr ON p.propietario_id=pr.id
                    WHERE p.activo=1 AND DATE(p.created_at) BETWEEN '$desde' AND '$hasta'
                    ORDER BY p.created_at DESC");
                echo "<p class='text-muted small'>Total: <strong>" . $rows->num_rows . "</strong> pacientes</p>";
                ?>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead><tr><th>Paciente</th><th>Especie</th><th>Raza</th><th>Sexo</th><th>E. Repro.</th><th>Propietario</th><th>Teléfono</th><th>Registro</th></tr></thead>
                        <tbody>
                        <?php while ($r = $rows->fetch_assoc()): ?>
                            <tr><td><?= limpiar($r['nombre']) ?></td><td><?= limpiar($r['especie']) ?></td><td><?= limpiar($r['raza']??'-') ?></td><td><?= limpiar($r['sexo']) ?></td><td><?= limpiar($r['estado_reproductivo']) ?></td><td><?= limpiar($r['propietario']) ?></td><td><?= limpiar($r['telefono']) ?></td><td><?= date('d/m/Y', strtotime($r['created_at'])) ?></td></tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($reporte === 'propietarios'): ?>
                <?php
                $rows = $conn->query("SELECT p.nombres, p.apellidos, p.tipo_identificacion, p.numero_identificacion, p.telefono, p.email, p.ciudad,
                    (SELECT COUNT(*) FROM pacientes WHERE propietario_id=p.id AND activo=1) AS num_pacientes
                    FROM propietarios p WHERE p.activo=1 AND DATE(p.created_at) BETWEEN '$desde' AND '$hasta' ORDER BY p.apellidos");
                echo "<p class='text-muted small'>Total: <strong>" . $rows->num_rows . "</strong> propietarios</p>";
                ?>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead><tr><th>Nombre</th><th>Identificación</th><th>Teléfono</th><th>Email</th><th>Ciudad</th><th>Pacientes</th></tr></thead>
                        <tbody>
                        <?php while ($r = $rows->fetch_assoc()): ?>
                            <tr><td><?= limpiar($r['nombres'].' '.$r['apellidos']) ?></td><td><?= limpiar($r['tipo_identificacion']) ?> <?= limpiar($r['numero_identificacion']) ?></td><td><?= limpiar($r['telefono']) ?></td><td><?= limpiar($r['email']) ?></td><td><?= limpiar($r['ciudad']) ?></td><td><?= $r['num_pacientes'] ?></td></tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($reporte === 'citas'): ?>
                <?php
                $rows = $conn->query("SELECT c.fecha_hora, p.nombre AS paciente, CONCAT(pr.nombres,' ',pr.apellidos) AS propietario,
                    CONCAT(u.nombres,' ',u.apellidos) AS veterinario, c.motivo, c.estado
                    FROM citas c JOIN pacientes p ON c.paciente_id=p.id
                    JOIN propietarios pr ON c.propietario_id=pr.id
                    LEFT JOIN usuarios u ON c.veterinario_id=u.id
                    WHERE DATE(c.fecha_hora) BETWEEN '$desde' AND '$hasta' ORDER BY c.fecha_hora DESC");
                // Totales por estado
                $totEstados = $conn->query("SELECT estado, COUNT(*) c FROM citas WHERE DATE(fecha_hora) BETWEEN '$desde' AND '$hasta' GROUP BY estado");
                echo "<div class='d-flex gap-2 mb-3 flex-wrap'>";
                while ($te = $totEstados->fetch_assoc()) {
                    echo "<span class='badge badge-".$te['estado']." border'>".ucfirst(str_replace('_',' ',$te['estado'])).": <strong>".$te['c']."</strong></span>";
                }
                echo "</div>";
                ?>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead><tr><th>Fecha/Hora</th><th>Paciente</th><th>Propietario</th><th>Veterinario</th><th>Motivo</th><th>Estado</th></tr></thead>
                        <tbody>
                        <?php while ($r = $rows->fetch_assoc()): ?>
                            <tr><td><?= date('d/m/Y H:i', strtotime($r['fecha_hora'])) ?></td><td><?= limpiar($r['paciente']) ?></td><td><?= limpiar($r['propietario']) ?></td><td><?= limpiar($r['veterinario']??'-') ?></td><td><?= limpiar($r['motivo']) ?></td><td><?= ucfirst(str_replace('_',' ',$r['estado'])) ?></td></tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($reporte === 'consultas'): ?>
                <?php
                $rows = $conn->query("SELECT h.fecha_hora, p.nombre AS paciente, e.nombre AS especie,
                    CONCAT(pr.nombres,' ',pr.apellidos) AS propietario,
                    CONCAT(u.nombres,' ',u.apellidos) AS veterinario,
                    h.motivo, h.diagnostico
                    FROM historias_clinicas h JOIN pacientes p ON h.paciente_id=p.id
                    JOIN especies e ON p.especie_id=e.id
                    JOIN propietarios pr ON p.propietario_id=pr.id
                    JOIN usuarios u ON h.veterinario_id=u.id
                    WHERE DATE(h.fecha_hora) BETWEEN '$desde' AND '$hasta'
                    ORDER BY h.fecha_hora DESC");
                echo "<p class='text-muted small'>Total: <strong>" . $rows->num_rows . "</strong> consultas</p>";
                ?>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead><tr><th>Fecha</th><th>Paciente</th><th>Propietario</th><th>Veterinario</th><th>Motivo</th><th>Diagnóstico</th></tr></thead>
                        <tbody>
                        <?php while ($r = $rows->fetch_assoc()): ?>
                            <tr><td><?= date('d/m/Y', strtotime($r['fecha_hora'])) ?></td><td><?= limpiar($r['paciente']) ?> <small class='text-muted'>(<?= limpiar($r['especie']) ?>)</small></td><td><?= limpiar($r['propietario']) ?></td><td><?= limpiar($r['veterinario']) ?></td><td><?= limpiar(substr($r['motivo'],0,80)) ?></td><td><?= limpiar(substr($r['diagnostico']??'',0,80)) ?></td></tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($reporte === 'vacunas'): ?>
                <?php
                $rows = $conn->query("SELECT v.tipo_vacuna, v.proxima_dosis, p.nombre AS paciente, e.nombre AS especie,
                    CONCAT(pr.nombres,' ',pr.apellidos) AS propietario, pr.telefono
                    FROM vacunas v JOIN pacientes p ON v.paciente_id=p.id
                    JOIN especies e ON p.especie_id=e.id
                    JOIN propietarios pr ON p.propietario_id=pr.id
                    WHERE v.proxima_dosis BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 60 DAY)
                    ORDER BY v.proxima_dosis ASC");
                echo "<p class='text-muted small'>Vacunas próximas (60 días): <strong>" . $rows->num_rows . "</strong></p>";
                ?>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead><tr><th>Próxima dosis</th><th>Vacuna</th><th>Paciente</th><th>Especie</th><th>Propietario</th><th>Teléfono</th><th>Días restantes</th></tr></thead>
                        <tbody>
                        <?php while ($r = $rows->fetch_assoc()):
                            $dias = (int)ceil((strtotime($r['proxima_dosis'])-time())/86400);
                        ?>
                            <tr class="<?= $dias<=7?'table-danger':($dias<=15?'table-warning':'') ?>">
                                <td><?= date('d/m/Y', strtotime($r['proxima_dosis'])) ?></td><td><?= limpiar($r['tipo_vacuna']) ?></td><td><?= limpiar($r['paciente']) ?></td><td><?= limpiar($r['especie']) ?></td><td><?= limpiar($r['propietario']) ?></td><td><?= limpiar($r['telefono']) ?></td><td><?= $dias ?> días</td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($reporte === 'tratamientos'): ?>
                <?php
                $rows = $conn->query("SELECT t.medicamento, t.dosis, t.frecuencia, t.duracion, t.via_administracion, t.fecha_inicio, t.fecha_fin,
                    p.nombre AS paciente, CONCAT(pr.nombres,' ',pr.apellidos) AS propietario,
                    CONCAT(u.nombres,' ',u.apellidos) AS veterinario
                    FROM tratamientos t JOIN pacientes p ON t.paciente_id=p.id
                    JOIN propietarios pr ON p.propietario_id=pr.id
                    LEFT JOIN usuarios u ON t.veterinario_id=u.id
                    WHERE DATE(t.created_at) BETWEEN '$desde' AND '$hasta'
                    ORDER BY t.created_at DESC");
                echo "<p class='text-muted small'>Total: <strong>" . $rows->num_rows . "</strong> tratamientos</p>";
                ?>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead><tr><th>Paciente</th><th>Propietario</th><th>Medicamento</th><th>Dosis</th><th>Frecuencia</th><th>Duración</th><th>Veterinario</th></tr></thead>
                        <tbody>
                        <?php while ($r = $rows->fetch_assoc()): ?>
                            <tr><td><?= limpiar($r['paciente']) ?></td><td><?= limpiar($r['propietario']) ?></td><td><?= limpiar($r['medicamento']) ?></td><td><?= limpiar($r['dosis']) ?></td><td><?= limpiar($r['frecuencia']) ?></td><td><?= limpiar($r['duracion']) ?></td><td><?= limpiar($r['veterinario']??'-') ?></td></tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($reporte === 'actividad'): ?>
                <?php
                $rows = $conn->query("SELECT CONCAT(u.nombres,' ',u.apellidos) AS veterinario,
                    COUNT(DISTINCT h.id) AS consultas,
                    COUNT(DISTINCT c.id) AS citas_atendidas,
                    COUNT(DISTINCT v.id) AS vacunas
                    FROM usuarios u
                    LEFT JOIN historias_clinicas h ON h.veterinario_id=u.id AND DATE(h.fecha_hora) BETWEEN '$desde' AND '$hasta'
                    LEFT JOIN citas c ON c.veterinario_id=u.id AND DATE(c.fecha_hora) BETWEEN '$desde' AND '$hasta' AND c.estado='atendida'
                    LEFT JOIN vacunas v ON v.veterinario_id=u.id AND DATE(v.fecha_aplicacion) BETWEEN '$desde' AND '$hasta'
                    WHERE u.rol_id IN(1,2) AND u.activo=1
                    GROUP BY u.id ORDER BY consultas DESC");
                ?>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead><tr><th>Veterinario</th><th>Consultas</th><th>Citas atendidas</th><th>Vacunas aplicadas</th></tr></thead>
                        <tbody>
                        <?php while ($r = $rows->fetch_assoc()): ?>
                            <tr><td><?= limpiar($r['veterinario']) ?></td><td><?= $r['consultas'] ?></td><td><?= $r['citas_atendidas'] ?></td><td><?= $r['vacunas'] ?></td></tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<style>
@media print {
    #sidebar, .topbar, .col-lg-3, .btn { display: none !important; }
    #main { margin: 0 !important; }
    .col-lg-9 { width: 100% !important; max-width: 100% !important; flex: 0 0 100% !important; }
}
</style>

<?php $conn->close(); include '../includes/footer.php'; ?>
