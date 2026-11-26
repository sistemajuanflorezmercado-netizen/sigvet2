<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';
verificarSesion();
$conn = conectar();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: index.php'); exit; }

$pac = $conn->query("SELECT p.*, e.nombre AS especie, r.nombre AS raza,
    CONCAT(pr.nombres,' ',pr.apellidos) AS propietario, pr.telefono AS prop_tel, pr.id AS prop_id
    FROM pacientes p
    JOIN especies e ON p.especie_id=e.id
    LEFT JOIN razas r ON p.raza_id=r.id
    JOIN propietarios pr ON p.propietario_id=pr.id
    WHERE p.id=$id AND p.activo=1")->fetch_assoc();
if (!$pac) { header('Location: index.php'); exit; }

$paginaTitulo = 'Perfil: ' . $pac['nombre'];

$historias = $conn->query("SELECT h.*, CONCAT(u.nombres,' ',u.apellidos) AS veterinario FROM historias_clinicas h JOIN usuarios u ON h.veterinario_id=u.id WHERE h.paciente_id=$id ORDER BY h.fecha_hora DESC");
$vacunas = $conn->query("SELECT v.*, CONCAT(u.nombres,' ',u.apellidos) AS veterinario FROM vacunas v LEFT JOIN usuarios u ON v.veterinario_id=u.id WHERE v.paciente_id=$id ORDER BY v.fecha_aplicacion DESC");
$desparasitaciones = $conn->query("SELECT * FROM desparasitaciones WHERE paciente_id=$id ORDER BY fecha DESC");
$tratamientos = $conn->query("SELECT t.*, CONCAT(u.nombres,' ',u.apellidos) AS veterinario FROM tratamientos t LEFT JOIN usuarios u ON t.veterinario_id=u.id WHERE t.paciente_id=$id ORDER BY t.created_at DESC LIMIT 10");
$citas = $conn->query("SELECT c.*, CONCAT(u.nombres,' ',u.apellidos) AS veterinario FROM citas c LEFT JOIN usuarios u ON c.veterinario_id=u.id WHERE c.paciente_id=$id ORDER BY c.fecha_hora DESC LIMIT 5");

$edad = '';
if ($pac['fecha_nacimiento']) {
    $diff = (new DateTime())->diff(new DateTime($pac['fecha_nacimiento']));
    $edad = $diff->y ? $diff->y . ' año(s)' : $diff->m . ' mes(es)';
}

include '../includes/header.php';
?>

<div class="row g-3">
    <!-- Info del paciente -->
    <div class="col-lg-4">
        <div class="card text-center mb-3">
            <div class="card-body py-4">
                <?php if ($pac['foto']): ?>
                    <img src="/sigvet/pacientes/uploads/<?= limpiar($pac['foto']) ?>" class="rounded-circle mb-3" width="100" height="100" style="object-fit:cover;border:4px solid #e9ecef">
                <?php else: ?>
                    <div class="rounded-circle bg-success mx-auto mb-3 d-flex align-items-center justify-content-center text-white" style="width:100px;height:100px;font-size:2.5rem">
                        <i class="bi bi-paw"></i>
                    </div>
                <?php endif; ?>
                <h5 class="fw-bold mb-0"><?= limpiar($pac['nombre']) ?></h5>
                <p class="text-muted mb-2"><?= limpiar($pac['especie']) ?><?= $pac['raza'] ? ' · ' . limpiar($pac['raza']) : '' ?></p>
                <div class="d-flex gap-2 justify-content-center flex-wrap mb-3">
                    <span class="badge bg-primary"><?= limpiar($pac['sexo']) ?></span>
                    <span class="badge bg-secondary"><?= limpiar($pac['estado_reproductivo']) ?></span>
                </div>
                <div class="text-start">
                    <?php $info = [
                        ['bi-calendar3','Edad',$edad ?: '-'],
                        ['bi-droplet','Color',limpiar($pac['color']) ?: '-'],
                        ['bi-speedometer2','Peso',$pac['peso'] ? $pac['peso'].' kg' : '-'],
                        ['bi-cpu','Microchip',limpiar($pac['microchip']) ?: 'No registrado'],
                        ['bi-person','Propietario','<a href="/sigvet/propietarios/editar.php?id='.$pac['prop_id'].'">'.limpiar($pac['propietario']).'</a>'],
                        ['bi-telephone','Teléfono',limpiar($pac['prop_tel']) ?: '-'],
                    ];
                    foreach ($info as [$ico,$lbl,$val]): ?>
                        <div class="d-flex align-items-center gap-2 py-1 border-bottom">
                            <i class="bi bi-<?= $ico ?> text-muted" style="width:18px"></i>
                            <small class="text-muted"><?= $lbl ?>:</small>
                            <small class="ms-auto text-end"><?= $val ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="mt-3 d-flex gap-2 justify-content-center">
                    <a href="editar.php?id=<?= $id ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil me-1"></i>Editar</a>
                    <a href="/sigvet/historias/crear.php?paciente=<?= $id ?>" class="btn btn-sm btn-success"><i class="bi bi-journal-plus me-1"></i>Historia</a>
                </div>
            </div>
        </div>

        <!-- Próximas vacunas -->
        <div class="card">
            <div class="card-header py-2"><i class="bi bi-droplet me-2 text-danger"></i>Próximas vacunas</div>
            <div class="card-body p-0">
                <?php
                $prox = $conn->query("SELECT * FROM vacunas WHERE paciente_id=$id AND proxima_dosis >= CURDATE() ORDER BY proxima_dosis ASC LIMIT 3");
                if ($prox->num_rows === 0): ?>
                    <div class="text-center text-muted py-3"><small>Sin vacunas próximas</small></div>
                <?php else: while ($v = $prox->fetch_assoc()):
                    $dias = (int)ceil((strtotime($v['proxima_dosis']) - time()) / 86400);
                ?>
                    <div class="d-flex align-items-center gap-2 p-2 border-bottom">
                        <div class="flex-grow-1">
                            <div style="font-size:.85rem;font-weight:600"><?= limpiar($v['tipo_vacuna']) ?></div>
                            <small class="text-muted"><?= date('d/m/Y', strtotime($v['proxima_dosis'])) ?></small>
                        </div>
                        <span class="badge <?= $dias<=7?'bg-danger':'bg-warning text-dark' ?>">en <?= $dias ?> d.</span>
                    </div>
                <?php endwhile; endif; ?>
            </div>
        </div>
    </div>

    <!-- Tabs historial -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <ul class="nav nav-tabs card-header-tabs" id="pacTabs">
                    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tab-historias">Historias clínicas</a></li>
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-vacunas">Vacunas</a></li>
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-despar">Desparasitación</a></li>
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-tratamientos">Tratamientos</a></li>
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-citas">Citas</a></li>
                </ul>
            </div>
            <div class="card-body tab-content p-0">
                <!-- Historias -->
                <div class="tab-pane active" id="tab-historias">
                    <div class="p-3 border-bottom d-flex justify-content-end">
                        <a href="/sigvet/historias/crear.php?paciente=<?= $id ?>" class="btn btn-sm btn-success"><i class="bi bi-plus me-1"></i>Nueva historia</a>
                    </div>
                    <?php if ($historias->num_rows === 0): ?>
                        <div class="text-center text-muted py-4">Sin historias clínicas</div>
                    <?php else: while ($h = $historias->fetch_assoc()): ?>
                        <div class="p-3 border-bottom">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <strong><?= date('d/m/Y H:i', strtotime($h['fecha_hora'])) ?></strong>
                                    <small class="text-muted ms-2">Dr. <?= limpiar($h['veterinario']) ?></small>
                                </div>
                                <a href="/sigvet/historias/ver.php?id=<?= $h['id'] ?>" class="btn btn-xs btn-outline-info btn-sm"><i class="bi bi-eye"></i></a>
                            </div>
                            <p class="mb-1"><strong>Motivo:</strong> <?= limpiar($h['motivo']) ?></p>
                            <?php if ($h['diagnostico']): ?>
                                <p class="mb-0 text-muted"><strong>Diagnóstico:</strong> <?= limpiar(substr($h['diagnostico'],0,120)) ?>...</p>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; endif; ?>
                </div>

                <!-- Vacunas -->
                <div class="tab-pane" id="tab-vacunas">
                    <div class="p-3 border-bottom d-flex gap-2 justify-content-end">
                        <a href="/sigvet/vacunas/certificado.php?paciente=<?= $id ?>" target="_blank" class="btn btn-sm btn-outline-warning"><i class="bi bi-award me-1"></i>Certificado</a>
                        <a href="/sigvet/vacunas/crear.php?paciente=<?= $id ?>" class="btn btn-sm btn-success"><i class="bi bi-plus me-1"></i>Registrar</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead><tr><th>Vacuna</th><th>Fecha</th><th>Próxima</th><th>Lote</th></tr></thead>
                            <tbody>
                            <?php if ($vacunas->num_rows === 0): ?>
                                <tr><td colspan="4" class="text-center text-muted py-3">Sin vacunas</td></tr>
                            <?php else: while ($v = $vacunas->fetch_assoc()): ?>
                                <tr>
                                    <td><?= limpiar($v['tipo_vacuna']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($v['fecha_aplicacion'])) ?></td>
                                    <td><?= $v['proxima_dosis'] ? date('d/m/Y', strtotime($v['proxima_dosis'])) : '-' ?></td>
                                    <td><?= limpiar($v['lote']) ?></td>
                                </tr>
                            <?php endwhile; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Desparasitación -->
                <div class="tab-pane" id="tab-despar">
                    <div class="p-3 border-bottom d-flex justify-content-end">
                        <a href="/sigvet/desparasitacion/crear.php?paciente=<?= $id ?>" class="btn btn-sm btn-success"><i class="bi bi-plus me-1"></i>Registrar</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead><tr><th>Producto</th><th>Tipo</th><th>Fecha</th><th>Dosis</th><th>Próxima</th></tr></thead>
                            <tbody>
                            <?php if ($desparasitaciones->num_rows === 0): ?>
                                <tr><td colspan="5" class="text-center text-muted py-3">Sin registros</td></tr>
                            <?php else: while ($d = $desparasitaciones->fetch_assoc()): ?>
                                <tr>
                                    <td><?= limpiar($d['producto']) ?></td>
                                    <td><?= limpiar($d['tipo']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($d['fecha'])) ?></td>
                                    <td><?= limpiar($d['dosis']) ?></td>
                                    <td><?= $d['proxima_fecha'] ? date('d/m/Y', strtotime($d['proxima_fecha'])) : '-' ?></td>
                                </tr>
                            <?php endwhile; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tratamientos -->
                <div class="tab-pane" id="tab-tratamientos">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead><tr><th>Medicamento</th><th>Dosis</th><th>Frecuencia</th><th>Vía</th><th>Duración</th></tr></thead>
                            <tbody>
                            <?php if ($tratamientos->num_rows === 0): ?>
                                <tr><td colspan="5" class="text-center text-muted py-3">Sin tratamientos</td></tr>
                            <?php else: while ($t = $tratamientos->fetch_assoc()): ?>
                                <tr>
                                    <td><?= limpiar($t['medicamento']) ?></td>
                                    <td><?= limpiar($t['dosis']) ?></td>
                                    <td><?= limpiar($t['frecuencia']) ?></td>
                                    <td><?= limpiar($t['via_administracion']) ?></td>
                                    <td><?= limpiar($t['duracion']) ?></td>
                                </tr>
                            <?php endwhile; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Citas -->
                <div class="tab-pane" id="tab-citas">
                    <div class="p-3 border-bottom d-flex justify-content-end">
                        <a href="/sigvet/citas/crear.php?paciente=<?= $id ?>" class="btn btn-sm btn-success"><i class="bi bi-plus me-1"></i>Nueva cita</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead><tr><th>Fecha</th><th>Motivo</th><th>Veterinario</th><th>Estado</th></tr></thead>
                            <tbody>
                            <?php if ($citas->num_rows === 0): ?>
                                <tr><td colspan="4" class="text-center text-muted py-3">Sin citas</td></tr>
                            <?php else: while ($c = $citas->fetch_assoc()): ?>
                                <tr>
                                    <td><?= date('d/m/Y H:i', strtotime($c['fecha_hora'])) ?></td>
                                    <td><?= limpiar($c['motivo']) ?></td>
                                    <td><?= limpiar($c['veterinario']) ?></td>
                                    <td><span class="badge badge-<?= $c['estado'] ?>"><?= ucfirst(str_replace('_',' ',$c['estado'])) ?></span></td>
                                </tr>
                            <?php endwhile; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php $conn->close(); include '../includes/footer.php'; ?>
