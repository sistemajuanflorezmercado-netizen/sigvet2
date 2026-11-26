<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';
verificarSesion();
$paginaTitulo = 'Nueva cita';
$conn = conectar();

$pacienteId = (int)($_GET['paciente'] ?? 0);
$pacientes = $conn->query("SELECT p.id, CONCAT(p.nombre,' – ',pr.nombres,' ',pr.apellidos) AS nombre, p.propietario_id FROM pacientes p JOIN propietarios pr ON p.propietario_id=pr.id WHERE p.activo=1 ORDER BY p.nombre");
$veterinarios = $conn->query("SELECT id, CONCAT(nombres,' ',apellidos) AS nombre FROM usuarios WHERE rol_id IN(1,2) AND activo=1");
$errores = [];
$datos = ['paciente_id'=>$pacienteId,'propietario_id'=>'','veterinario_id'=>'','fecha_hora'=>date('Y-m-d\TH:i'),'motivo'=>'','observaciones'=>''];

// Obtener propietario desde paciente seleccionado
$pacientesArr = [];
while ($p = $pacientes->fetch_assoc()) $pacientesArr[] = $p;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($datos as $k => $_) $datos[$k] = trim($_POST[$k] ?? '');
    if (!$datos['paciente_id']) $errores[] = 'Seleccione un paciente.';
    if (!$datos['fecha_hora']) $errores[] = 'La fecha y hora son requeridas.';
    if (!$datos['motivo']) $errores[] = 'El motivo es requerido.';

    if (empty($errores)) {
        $pid = (int)$datos['paciente_id'];
        $vid = $datos['veterinario_id'] ? (int)$datos['veterinario_id'] : 'NULL';
        $prid = (int)$datos['propietario_id'];
        $fh = $conn->real_escape_string(str_replace('T',' ',$datos['fecha_hora']));
        $mot = $conn->real_escape_string($datos['motivo']);
        $obs = $conn->real_escape_string($datos['observaciones']);
        $cby = (int)$_SESSION['usuario_id'];
        $conn->query("INSERT INTO citas (paciente_id,propietario_id,veterinario_id,fecha_hora,motivo,observaciones,created_by) VALUES ($pid,$prid,$vid,'$fh','$mot','$obs',$cby)");
        $newId = $conn->insert_id;
        registrarLog($conn, 'Creó cita', 'citas', $newId);
        $conn->close();
        header('Location: index.php?fecha='.substr($datos['fecha_hora'],0,10));
        exit;
    }
}

include '../includes/header.php';
?>

<?php if ($errores): ?>
    <?= alerta('danger', implode('<br>', array_map('limpiar', $errores))) ?>
<?php endif; ?>

<div class="card" style="max-width:680px">
    <div class="card-header py-3"><i class="bi bi-calendar-plus me-2"></i>Programar cita</div>
    <div class="card-body">
        <form method="POST" id="formCita">
            <div class="row g-3">
                <div class="col-md-7">
                    <label class="form-label">Paciente <span class="text-danger">*</span></label>
                    <select name="paciente_id" id="selPaciente" class="form-select" required onchange="setPropietario(this)">
                        <option value="">Seleccionar...</option>
                        <?php foreach ($pacientesArr as $p): ?>
                            <option value="<?= $p['id'] ?>" data-prop="<?= $p['propietario_id'] ?>" <?= $datos['paciente_id']==$p['id']?'selected':'' ?>><?= limpiar($p['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="hidden" name="propietario_id" id="propietarioId" value="<?= (int)$datos['propietario_id'] ?>">
                </div>
                <div class="col-md-5">
                    <label class="form-label">Veterinario</label>
                    <select name="veterinario_id" class="form-select">
                        <option value="">Sin asignar</option>
                        <?php while ($v = $veterinarios->fetch_assoc()): ?>
                            <option value="<?= $v['id'] ?>" <?= $datos['veterinario_id']==$v['id']?'selected':'' ?>><?= limpiar($v['nombre']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Fecha y hora <span class="text-danger">*</span></label>
                    <input type="datetime-local" name="fecha_hora" class="form-control" value="<?= limpiar($datos['fecha_hora']) ?>" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Motivo <span class="text-danger">*</span></label>
                    <input type="text" name="motivo" class="form-control" value="<?= limpiar($datos['motivo']) ?>" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Observaciones</label>
                    <textarea name="observaciones" class="form-control" rows="2"><?= limpiar($datos['observaciones']) ?></textarea>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Guardar cita</button>
                <a href="index.php" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<script>
function setPropietario(sel) {
    var opt = sel.options[sel.selectedIndex];
    document.getElementById('propietarioId').value = opt.dataset.prop || '';
}
</script>

<?php $conn->close(); include '../includes/footer.php'; ?>
