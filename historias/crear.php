<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';
verificarSesion();
soloClinico();
$paginaTitulo = 'Nueva historia clínica';
$conn = conectar();

$pacienteId = (int)($_GET['paciente'] ?? 0);
$pacientes = $conn->query("SELECT p.id, CONCAT(p.nombre,' (',e.nombre,')') AS nombre, p.propietario_id FROM pacientes p JOIN especies e ON p.especie_id=e.id WHERE p.activo=1 ORDER BY p.nombre");
$veterinarios = $conn->query("SELECT id, CONCAT(nombres,' ',apellidos) AS nombre FROM usuarios WHERE rol_id IN(1,2) AND activo=1");
$errores = [];
$datos = ['paciente_id'=>$pacienteId,'veterinario_id'=>$_SESSION['usuario_id'],'fecha_hora'=>date('Y-m-d\TH:i'),'motivo'=>'','antecedentes'=>'','sintomas'=>'','peso'=>'','temperatura'=>'','frecuencia_cardiaca'=>'','frecuencia_respiratoria'=>'','examen_fisico'=>'','diagnostico'=>'','tratamiento'=>'','medicamentos'=>'','recomendaciones'=>'','observaciones'=>''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($datos as $k => $_) $datos[$k] = trim($_POST[$k] ?? '');
    if (!$datos['paciente_id']) $errores[] = 'El paciente es requerido.';
    if (!$datos['motivo']) $errores[] = 'El motivo de consulta es requerido.';
    if (!$datos['veterinario_id']) $errores[] = 'El veterinario es requerido.';

    if (empty($errores)) {
        $pid = (int)$datos['paciente_id'];
        $vid = (int)$datos['veterinario_id'];
        $fh = $conn->real_escape_string(str_replace('T',' ',$datos['fecha_hora']));
        $mot = $conn->real_escape_string($datos['motivo']);
        $ant = $conn->real_escape_string($datos['antecedentes']);
        $sin = $conn->real_escape_string($datos['sintomas']);
        $pes = $datos['peso'] !== '' ? (float)$datos['peso'] : 'NULL';
        $tmp = $datos['temperatura'] !== '' ? (float)$datos['temperatura'] : 'NULL';
        $fc = $datos['frecuencia_cardiaca'] !== '' ? (int)$datos['frecuencia_cardiaca'] : 'NULL';
        $fr = $datos['frecuencia_respiratoria'] !== '' ? (int)$datos['frecuencia_respiratoria'] : 'NULL';
        $ef = $conn->real_escape_string($datos['examen_fisico']);
        $dx = $conn->real_escape_string($datos['diagnostico']);
        $tx = $conn->real_escape_string($datos['tratamiento']);
        $med = $conn->real_escape_string($datos['medicamentos']);
        $rec = $conn->real_escape_string($datos['recomendaciones']);
        $obs = $conn->real_escape_string($datos['observaciones']);

        $conn->query("INSERT INTO historias_clinicas (paciente_id,veterinario_id,fecha_hora,motivo,antecedentes,sintomas,peso,temperatura,frecuencia_cardiaca,frecuencia_respiratoria,examen_fisico,diagnostico,tratamiento,medicamentos,recomendaciones,observaciones) VALUES ($pid,$vid,'$fh','$mot','$ant','$sin',$pes,$tmp,$fc,$fr,'$ef','$dx','$tx','$med','$rec','$obs')");
        $hid = $conn->insert_id;

        // Actualizar peso del paciente
        if ($datos['peso']) $conn->query("UPDATE pacientes SET peso=".(float)$datos['peso']." WHERE id=$pid");

        registrarLog($conn, 'Creó historia clínica', 'historias_clinicas', $hid);
        $conn->close();
        header('Location: ver.php?id=' . $hid . '&ok=1');
        exit;
    }
}

include '../includes/header.php';
?>

<?php if ($errores): ?>
    <?= alerta('danger', implode('<br>', array_map('limpiar', $errores))) ?>
<?php endif; ?>

<div class="card">
    <div class="card-header py-3"><i class="bi bi-journal-plus me-2"></i>Registro de consulta / historia clínica</div>
    <div class="card-body">
        <form method="POST">
            <div class="row g-3">
                <div class="col-md-5">
                    <label class="form-label">Paciente <span class="text-danger">*</span></label>
                    <select name="paciente_id" class="form-select" required>
                        <option value="">Seleccionar...</option>
                        <?php while ($p = $pacientes->fetch_assoc()): ?>
                            <option value="<?= $p['id'] ?>" <?= $datos['paciente_id']==$p['id']?'selected':'' ?>><?= limpiar($p['nombre']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Veterinario <span class="text-danger">*</span></label>
                    <select name="veterinario_id" class="form-select" required>
                        <?php while ($v = $veterinarios->fetch_assoc()): ?>
                            <option value="<?= $v['id'] ?>" <?= $datos['veterinario_id']==$v['id']?'selected':'' ?>><?= limpiar($v['nombre']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Fecha y hora</label>
                    <input type="datetime-local" name="fecha_hora" class="form-control" value="<?= limpiar($datos['fecha_hora']) ?>">
                </div>

                <div class="col-12"><hr class="my-1"><h6 class="text-muted">Anamnesis</h6></div>
                <div class="col-12">
                    <label class="form-label">Motivo de consulta <span class="text-danger">*</span></label>
                    <textarea name="motivo" class="form-control" rows="2" required><?= limpiar($datos['motivo']) ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Antecedentes</label>
                    <textarea name="antecedentes" class="form-control" rows="3"><?= limpiar($datos['antecedentes']) ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Síntomas / signos clínicos</label>
                    <textarea name="sintomas" class="form-control" rows="3"><?= limpiar($datos['sintomas']) ?></textarea>
                </div>

                <div class="col-12"><hr class="my-1"><h6 class="text-muted">Signos vitales</h6></div>
                <div class="col-md-3">
                    <label class="form-label">Peso (kg)</label>
                    <input type="number" step="0.01" name="peso" class="form-control" value="<?= limpiar($datos['peso']) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Temperatura (°C)</label>
                    <input type="number" step="0.1" name="temperatura" class="form-control" value="<?= limpiar($datos['temperatura']) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Freq. cardíaca (lpm)</label>
                    <input type="number" name="frecuencia_cardiaca" class="form-control" value="<?= limpiar($datos['frecuencia_cardiaca']) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Freq. respiratoria (rpm)</label>
                    <input type="number" name="frecuencia_respiratoria" class="form-control" value="<?= limpiar($datos['frecuencia_respiratoria']) ?>">
                </div>

                <div class="col-12"><hr class="my-1"><h6 class="text-muted">Examen y diagnóstico</h6></div>
                <div class="col-12">
                    <label class="form-label">Examen físico</label>
                    <textarea name="examen_fisico" class="form-control" rows="3"><?= limpiar($datos['examen_fisico']) ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Diagnóstico</label>
                    <textarea name="diagnostico" class="form-control" rows="3"><?= limpiar($datos['diagnostico']) ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Tratamiento indicado</label>
                    <textarea name="tratamiento" class="form-control" rows="3"><?= limpiar($datos['tratamiento']) ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Medicamentos formulados</label>
                    <textarea name="medicamentos" class="form-control" rows="3" placeholder="Nombre, dosis, frecuencia, duración..."><?= limpiar($datos['medicamentos']) ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Recomendaciones al propietario</label>
                    <textarea name="recomendaciones" class="form-control" rows="3"><?= limpiar($datos['recomendaciones']) ?></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label">Observaciones adicionales</label>
                    <textarea name="observaciones" class="form-control" rows="2"><?= limpiar($datos['observaciones']) ?></textarea>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Guardar historia</button>
                <a href="index.php" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php $conn->close(); include '../includes/footer.php'; ?>
