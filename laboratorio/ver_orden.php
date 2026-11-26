<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';
verificarSesion();
$conn = conectar();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: index.php'); exit; }

$orden = $conn->query("SELECT o.*,
    p.nombre AS paciente, p.id AS pac_id, e.nombre AS especie, p.fecha_nacimiento, p.sexo,
    CONCAT(pr.nombres,' ',pr.apellidos) AS propietario, pr.telefono AS prop_tel,
    CONCAT(u.nombres,' ',u.apellidos) AS veterinario
    FROM ordenes_laboratorio o
    JOIN pacientes p ON o.paciente_id=p.id
    JOIN especies e ON p.especie_id=e.id
    JOIN propietarios pr ON p.propietario_id=pr.id
    JOIN usuarios u ON o.veterinario_id=u.id
    WHERE o.id=$id")->fetch_assoc();
if (!$orden) { header('Location: index.php'); exit; }

$paginaTitulo = 'Orden ' . $orden['codigo'];

// Cambiar estado
if (isset($_GET['estado'])) {
    $est = $conn->real_escape_string($_GET['estado']);
    $validos = ['pendiente','en_proceso','completada','cancelada'];
    if (in_array($est, $validos)) {
        $conn->query("UPDATE ordenes_laboratorio SET estado='$est' WHERE id=$id");
        header("Location: ver_orden.php?id=$id");
        exit;
    }
}

// Subir resultado PDF
$msgSubida = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['resultado_pdf'])) {
    $file = $_FILES['resultado_pdf'];
    if ($file['error'] === 0) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext === 'pdf') {
            $nombreArchivo = 'lab_' . $id . '_' . uniqid() . '.pdf';
            $destino = LAB_UPLOAD_DIR . $nombreArchivo;
            if (move_uploaded_file($file['tmp_name'], $destino)) {
                $lab = $conn->real_escape_string(trim($_POST['laboratorio'] ?? ''));
                $obs = $conn->real_escape_string(trim($_POST['observaciones_resultado'] ?? ''));
                $fres = $conn->real_escape_string($_POST['fecha_resultado'] ?? date('Y-m-d'));
                $orig = $conn->real_escape_string($file['name']);
                $uid = (int)$_SESSION['usuario_id'];
                $conn->query("INSERT INTO resultados_laboratorio (orden_id,nombre_archivo,archivo_original,fecha_resultado,laboratorio,observaciones,uploaded_by) VALUES ($id,'$nombreArchivo','$orig','$fres','$lab','$obs',$uid)");
                // Marcar como en proceso si estaba pendiente
                if ($orden['estado'] === 'pendiente') {
                    $conn->query("UPDATE ordenes_laboratorio SET estado='en_proceso' WHERE id=$id");
                }
                registrarLog($conn, "Subió resultado PDF a orden $id", 'resultados_laboratorio', $conn->insert_id);
                $msgSubida = alerta('success', 'Resultado adjuntado correctamente.');
                $orden['estado'] = 'en_proceso';
            } else {
                $msgSubida = alerta('danger', 'Error al guardar el archivo. Verifica permisos de la carpeta /laboratorio/uploads/');
            }
        } else {
            $msgSubida = alerta('danger', 'Solo se permiten archivos PDF.');
        }
    } else {
        $msgSubida = alerta('danger', 'Error al subir el archivo.');
    }
}

$examenes = $conn->query("SELECT oe.*, te.nombre AS nombre_tipo, te.categoria FROM orden_examenes oe JOIN tipos_examen te ON oe.tipo_examen_id=te.id WHERE oe.orden_id=$id ORDER BY te.categoria");
$resultados = $conn->query("SELECT r.*, CONCAT(u.nombres,' ',u.apellidos) AS subido_por FROM resultados_laboratorio r LEFT JOIN usuarios u ON r.uploaded_by=u.id WHERE r.orden_id=$id ORDER BY r.created_at DESC");

$colores = ['pendiente'=>'warning','en_proceso'=>'info','completada'=>'success','cancelada'=>'secondary'];
$priorColors = ['rutina'=>'secondary','urgente'=>'warning','stat'=>'danger'];

include '../includes/header.php';
?>

<?= $msgSubida ?>

<div class="d-flex gap-2 mb-3 align-items-center flex-wrap">
    <a href="index.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Órdenes</a>
    <a href="imprimir_orden.php?id=<?= $id ?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-printer me-1"></i>Imprimir orden</a>
    <div class="dropdown ms-auto">
        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
            Cambiar estado: <strong><?= ucfirst(str_replace('_',' ',$orden['estado'])) ?></strong>
        </button>
        <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="?id=<?= $id ?>&estado=pendiente">Pendiente</a></li>
            <li><a class="dropdown-item" href="?id=<?= $id ?>&estado=en_proceso">En proceso</a></li>
            <li><a class="dropdown-item" href="?id=<?= $id ?>&estado=completada">Completada</a></li>
            <li><a class="dropdown-item" href="?id=<?= $id ?>&estado=cancelada">Cancelada</a></li>
        </ul>
    </div>
</div>

<div class="row g-3">
    <!-- Info orden -->
    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header py-3 d-flex justify-content-between">
                <span class="fw-semibold">Orden <code><?= limpiar($orden['codigo']) ?></code></span>
                <span class="badge bg-<?= $priorColors[$orden['prioridad']] ?>"><?= ucfirst($orden['prioridad']) ?></span>
            </div>
            <div class="card-body">
                <?php $infoItems = [
                    ['bi-calendar3','Fecha',date('d/m/Y', strtotime($orden['fecha']))],
                    ['bi-paw','Paciente',limpiar($orden['paciente']).' <small class="text-muted">('.$orden['especie'].')</small>'],
                    ['bi-person','Propietario',limpiar($orden['propietario'])],
                    ['bi-telephone','Teléfono',limpiar($orden['prop_tel']??'-')],
                    ['bi-person-badge','Veterinario','Dr. '.limpiar($orden['veterinario'])],
                    ['bi-circle-fill','Estado','<span class="badge bg-'.$colores[$orden['estado']].' '.($orden['estado']==='pendiente'?'text-dark':'').'">'.ucfirst(str_replace('_',' ',$orden['estado'])).'</span>'],
                ];
                foreach ($infoItems as [$ico,$lbl,$val]): ?>
                    <div class="d-flex gap-2 py-2 border-bottom align-items-center">
                        <i class="bi bi-<?= $ico ?> text-muted" style="width:18px"></i>
                        <small class="text-muted"><?= $lbl ?>:</small>
                        <span class="ms-auto text-end" style="font-size:.88rem"><?= $val ?></span>
                    </div>
                <?php endforeach; ?>
                <?php if ($orden['diagnostico_presuntivo']): ?>
                <div class="mt-3">
                    <small class="text-muted fw-semibold d-block mb-1">Diagnóstico presuntivo</small>
                    <p class="mb-0 small"><?= limpiar($orden['diagnostico_presuntivo']) ?></p>
                </div>
                <?php endif; ?>
                <?php if ($orden['observaciones']): ?>
                <div class="mt-2">
                    <small class="text-muted fw-semibold d-block mb-1">Observaciones</small>
                    <p class="mb-0 small"><?= limpiar($orden['observaciones']) ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Exámenes solicitados -->
        <div class="card">
            <div class="card-header py-2 fw-semibold"><i class="bi bi-list-check me-2"></i>Exámenes solicitados</div>
            <ul class="list-group list-group-flush">
                <?php while ($ex = $examenes->fetch_assoc()): ?>
                    <li class="list-group-item py-2 d-flex align-items-center gap-2">
                        <i class="bi bi-check2-circle text-success"></i>
                        <span style="font-size:.88rem"><?= limpiar($ex['nombre_personalizado'] ?: $ex['nombre_tipo']) ?></span>
                        <small class="text-muted ms-auto text-capitalize"><?= str_replace(['hematologia','bioquimica'], ['Hemat.','Bioq.'], $ex['categoria']) ?></small>
                    </li>
                <?php endwhile; ?>
            </ul>
        </div>
    </div>

    <!-- Resultados -->
    <div class="col-lg-8">
        <!-- Subir resultado -->
        <div class="card mb-3 border-primary">
            <div class="card-header bg-primary bg-opacity-10 py-3 fw-semibold text-primary">
                <i class="bi bi-upload me-2"></i>Adjuntar resultado (PDF)
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Archivo PDF del resultado <span class="text-danger">*</span></label>
                            <input type="file" name="resultado_pdf" class="form-control" accept=".pdf" required>
                            <small class="text-muted">Solo archivos PDF. Máx. 10MB.</small>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Fecha del resultado</label>
                            <input type="date" name="fecha_resultado" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Laboratorio</label>
                            <input type="text" name="laboratorio" class="form-control" placeholder="Nombre del lab.">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Observaciones del resultado</label>
                            <textarea name="observaciones_resultado" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-upload me-1"></i>Subir resultado</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Lista de resultados adjuntos -->
        <div class="card">
            <div class="card-header py-3 fw-semibold"><i class="bi bi-paperclip me-2"></i>Resultados adjuntos</div>
            <?php
            $totalRes = $resultados->num_rows;
            if ($totalRes === 0): ?>
                <div class="card-body text-center text-muted py-4">
                    <i class="bi bi-file-earmark-x fs-2 d-block mb-2"></i>
                    Aún no se han adjuntado resultados para esta orden.
                </div>
            <?php else: ?>
            <div class="list-group list-group-flush">
            <?php while ($r = $resultados->fetch_assoc()): ?>
                <div class="list-group-item py-3">
                    <div class="d-flex align-items-start gap-3">
                        <div class="rounded bg-danger bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0" style="width:48px;height:48px">
                            <i class="bi bi-file-earmark-pdf-fill text-danger fs-4"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-semibold"><?= limpiar($r['archivo_original']) ?></div>
                            <div class="text-muted" style="font-size:.82rem">
                                <?= date('d/m/Y', strtotime($r['fecha_resultado'])) ?>
                                <?= $r['laboratorio'] ? ' · ' . limpiar($r['laboratorio']) : '' ?>
                                · Subido por <?= limpiar($r['subido_por']) ?>
                            </div>
                            <?php if ($r['observaciones']): ?>
                                <small class="text-muted"><?= limpiar($r['observaciones']) ?></small>
                            <?php endif; ?>
                        </div>
                        <div class="d-flex gap-1 flex-shrink-0">
                            <a href="<?= LAB_UPLOAD_URL . limpiar($r['nombre_archivo']) ?>" target="_blank" class="btn btn-sm btn-outline-danger" title="Ver PDF">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="<?= LAB_UPLOAD_URL . limpiar($r['nombre_archivo']) ?>" download="<?= limpiar($r['archivo_original']) ?>" class="btn btn-sm btn-outline-secondary" title="Descargar">
                                <i class="bi bi-download"></i>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php $conn->close(); include '../includes/footer.php'; ?>
