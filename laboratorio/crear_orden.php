<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';
verificarSesion();
soloClinico();
$paginaTitulo = 'Nueva orden de laboratorio';
$conn = conectar();

$pacienteId = (int)($_GET['paciente'] ?? 0);
$pacientes = $conn->query("SELECT p.id, CONCAT(p.nombre,' (',e.nombre,') – ',pr.nombres,' ',pr.apellidos) AS nombre, p.propietario_id FROM pacientes p JOIN especies e ON p.especie_id=e.id JOIN propietarios pr ON p.propietario_id=pr.id WHERE p.activo=1 ORDER BY p.nombre");
$veterinarios = $conn->query("SELECT id, CONCAT(nombres,' ',apellidos) AS nombre FROM usuarios WHERE rol_id IN(1,2) AND activo=1");
$tiposExamen = $conn->query("SELECT * FROM tipos_examen ORDER BY categoria, nombre");

$errores = [];
$datos = ['paciente_id'=>$pacienteId,'veterinario_id'=>$_SESSION['usuario_id'],'fecha'=>date('Y-m-d'),'prioridad'=>'rutina','diagnostico_presuntivo'=>'','observaciones'=>''];
$examenesSeleccionados = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($datos as $k => $_) $datos[$k] = trim($_POST[$k] ?? '');
    $examenesSeleccionados = array_map('intval', $_POST['examenes'] ?? []);
    $examenesPersonalizados = $_POST['examenes_extra'] ?? '';

    if (!$datos['paciente_id']) $errores[] = 'Seleccione un paciente.';
    if (!$datos['veterinario_id']) $errores[] = 'Seleccione un veterinario.';
    if (empty($examenesSeleccionados) && !trim($examenesPersonalizados)) $errores[] = 'Seleccione al menos un examen.';

    if (empty($errores)) {
        // Generar código único
        $codigo = 'LAB-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));
        $pid = (int)$datos['paciente_id'];
        $vid = (int)$datos['veterinario_id'];
        $fec = $conn->real_escape_string($datos['fecha']);
        $pri = $conn->real_escape_string($datos['prioridad']);
        $dx = $conn->real_escape_string($datos['diagnostico_presuntivo']);
        $obs = $conn->real_escape_string($datos['observaciones']);

        $conn->query("INSERT INTO ordenes_laboratorio (codigo,paciente_id,veterinario_id,fecha,prioridad,diagnostico_presuntivo,observaciones) VALUES ('$codigo',$pid,$vid,'$fec','$pri','$dx','$obs')");
        $ordenId = $conn->insert_id;

        // Insertar exámenes seleccionados
        foreach ($examenesSeleccionados as $eid) {
            $conn->query("INSERT INTO orden_examenes (orden_id,tipo_examen_id) VALUES ($ordenId,$eid)");
        }

        // Exámenes adicionales personalizados (uno por línea)
        if (trim($examenesPersonalizados)) {
            // Buscar un tipo genérico "otro"
            $tipoOtro = $conn->query("SELECT id FROM tipos_examen WHERE categoria='otro' LIMIT 1")->fetch_assoc();
            $tidOtro = $tipoOtro ? $tipoOtro['id'] : 1;
            foreach (explode("\n", trim($examenesPersonalizados)) as $linea) {
                $linea = trim($linea);
                if ($linea) {
                    $ln = $conn->real_escape_string($linea);
                    $conn->query("INSERT INTO orden_examenes (orden_id,tipo_examen_id,nombre_personalizado) VALUES ($ordenId,$tidOtro,'$ln')");
                }
            }
        }

        registrarLog($conn, "Creó orden laboratorio $codigo", 'ordenes_laboratorio', $ordenId);
        $conn->close();
        header("Location: imprimir_orden.php?id=$ordenId&nuevo=1");
        exit;
    }
}

// Agrupar tipos por categoría
$categorias = [];
while ($t = $tiposExamen->fetch_assoc()) {
    $categorias[$t['categoria']][] = $t;
}
$nomCategorias = ['hematologia'=>'Hematología','bioquimica'=>'Bioquímica','urinalisis'=>'Uroanálisis','coprologia'=>'Coprología','microbiologia'=>'Microbiología','imagenologia'=>'Imagenología','citologia'=>'Citología','otro'=>'Otros'];

include '../includes/header.php';
?>

<?php if ($errores): ?>
    <?= alerta('danger', implode('<br>', array_map('limpiar', $errores))) ?>
<?php endif; ?>

<div class="row g-3">
<div class="col-lg-8">
<div class="card">
    <div class="card-header py-3"><i class="bi bi-clipboard2-pulse me-2"></i>Datos de la orden</div>
    <div class="card-body">
        <form method="POST" id="formOrden">
            <div class="row g-3 mb-4">
                <div class="col-md-7">
                    <label class="form-label">Paciente <span class="text-danger">*</span></label>
                    <select name="paciente_id" class="form-select" required>
                        <option value="">Seleccionar...</option>
                        <?php while ($p = $pacientes->fetch_assoc()): ?>
                            <option value="<?= $p['id'] ?>" <?= $datos['paciente_id']==$p['id']?'selected':'' ?>><?= limpiar($p['nombre']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label">Veterinario solicitante</label>
                    <select name="veterinario_id" class="form-select">
                        <?php while ($v = $veterinarios->fetch_assoc()): ?>
                            <option value="<?= $v['id'] ?>" <?= $datos['veterinario_id']==$v['id']?'selected':'' ?>><?= limpiar($v['nombre']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Fecha</label>
                    <input type="date" name="fecha" class="form-control" value="<?= limpiar($datos['fecha']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Prioridad</label>
                    <select name="prioridad" class="form-select">
                        <option value="rutina" <?= $datos['prioridad']==='rutina'?'selected':'' ?>>Rutina</option>
                        <option value="urgente" <?= $datos['prioridad']==='urgente'?'selected':'' ?>>⚡ Urgente</option>
                        <option value="stat" <?= $datos['prioridad']==='stat'?'selected':'' ?>>🔴 STAT</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Diagnóstico presuntivo / justificación clínica</label>
                    <textarea name="diagnostico_presuntivo" class="form-control" rows="2"><?= limpiar($datos['diagnostico_presuntivo']) ?></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label">Observaciones para el laboratorio</label>
                    <textarea name="observaciones" class="form-control" rows="2"><?= limpiar($datos['observaciones']) ?></textarea>
                </div>
            </div>

            <h6 class="fw-semibold border-bottom pb-2 mb-3"><i class="bi bi-list-check me-2"></i>Exámenes solicitados <span class="text-danger">*</span></h6>

            <?php foreach ($categorias as $cat => $examenes): ?>
            <div class="mb-3">
                <p class="text-muted mb-2 fw-semibold" style="font-size:.8rem;text-transform:uppercase;letter-spacing:.5px"><?= $nomCategorias[$cat] ?? ucfirst($cat) ?></p>
                <div class="row g-2">
                    <?php foreach ($examenes as $ex): ?>
                    <div class="col-md-6">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="examenes[]"
                                   value="<?= $ex['id'] ?>" id="ex<?= $ex['id'] ?>"
                                   <?= in_array($ex['id'], $examenesSeleccionados)?'checked':'' ?>>
                            <label class="form-check-label" for="ex<?= $ex['id'] ?>"><?= limpiar($ex['nombre']) ?></label>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>

            <div class="mb-4">
                <label class="form-label fw-semibold">Exámenes adicionales / personalizados</label>
                <small class="text-muted d-block mb-1">Un examen por línea</small>
                <textarea name="examenes_extra" class="form-control font-monospace" rows="3" placeholder="Ej: Electroforesis de proteínas&#10;Panel tiroideo T4"></textarea>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-clipboard2-check me-1"></i>Crear orden e imprimir</button>
                <a href="index.php" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
</div>

<div class="col-lg-4">
    <div class="card border-info">
        <div class="card-header bg-info bg-opacity-10 py-2 text-info fw-semibold">
            <i class="bi bi-info-circle me-2"></i>Información
        </div>
        <div class="card-body">
            <p class="small text-muted mb-2">Al crear la orden se generará automáticamente un código único y se abrirá la vista de impresión.</p>
            <p class="small text-muted mb-2">Luego puede adjuntar los resultados en PDF desde la vista detallada de la orden.</p>
            <hr>
            <p class="small fw-semibold mb-1">Prioridades:</p>
            <ul class="small text-muted ps-3 mb-0">
                <li><strong>Rutina:</strong> resultado en 24-48h</li>
                <li><strong>Urgente:</strong> resultado en 4-6h</li>
                <li><strong>STAT:</strong> resultado inmediato</li>
            </ul>
        </div>
    </div>
</div>
</div>

<?php $conn->close(); include '../includes/footer.php'; ?>
