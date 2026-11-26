<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';
verificarSesion();
$conn = conectar();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: index.php'); exit; }

$cita = $conn->query("SELECT * FROM citas WHERE id=$id")->fetch_assoc();
if (!$cita) { header('Location: index.php'); exit; }

$paginaTitulo = 'Editar cita';
$pacientes = $conn->query("SELECT p.id, CONCAT(p.nombre,' – ',pr.nombres,' ',pr.apellidos) AS nombre, p.propietario_id FROM pacientes p JOIN propietarios pr ON p.propietario_id=pr.id WHERE p.activo=1 ORDER BY p.nombre");
$veterinarios = $conn->query("SELECT id, CONCAT(nombres,' ',apellidos) AS nombre FROM usuarios WHERE rol_id IN(1,2) AND activo=1");
$errores = [];
$datos = $cita;
$datos['fecha_hora'] = str_replace(' ', 'T', substr($cita['fecha_hora'],0,16));
$estados = ['programada','confirmada','atendida','cancelada','no_asistio'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (['paciente_id','propietario_id','veterinario_id','fecha_hora','motivo','observaciones','estado'] as $k)
        $datos[$k] = trim($_POST[$k] ?? '');
    if (!$datos['paciente_id']) $errores[] = 'Seleccione un paciente.';
    if (!$datos['fecha_hora']) $errores[] = 'La fecha y hora son requeridas.';

    if (empty($errores)) {
        $pid = (int)$datos['paciente_id'];
        $vid = $datos['veterinario_id'] ? (int)$datos['veterinario_id'] : 'NULL';
        $prid = (int)$datos['propietario_id'];
        $fh = $conn->real_escape_string(str_replace('T',' ',$datos['fecha_hora']));
        $mot = $conn->real_escape_string($datos['motivo']);
        $obs = $conn->real_escape_string($datos['observaciones']);
        $est = $conn->real_escape_string($datos['estado']);
        $conn->query("UPDATE citas SET paciente_id=$pid,propietario_id=$prid,veterinario_id=$vid,fecha_hora='$fh',motivo='$mot',observaciones='$obs',estado='$est' WHERE id=$id");
        registrarLog($conn, 'Editó cita', 'citas', $id);
        $conn->close();
        header('Location: index.php');
        exit;
    }
}

include '../includes/header.php';
?>

<div class="card" style="max-width:680px">
    <div class="card-header py-3"><i class="bi bi-calendar-event me-2"></i>Editar cita</div>
    <div class="card-body">
        <form method="POST">
            <div class="row g-3">
                <div class="col-md-7">
                    <label class="form-label">Paciente</label>
                    <select name="paciente_id" class="form-select" required onchange="document.getElementById('propietarioId').value=this.options[this.selectedIndex].dataset.prop">
                        <?php
                        $pacs = $conn->query("SELECT p.id, CONCAT(p.nombre,' – ',pr.nombres,' ',pr.apellidos) AS nombre, p.propietario_id FROM pacientes p JOIN propietarios pr ON p.propietario_id=pr.id WHERE p.activo=1 ORDER BY p.nombre");
                        while ($p = $pacs->fetch_assoc()): ?>
                            <option value="<?= $p['id'] ?>" data-prop="<?= $p['propietario_id'] ?>" <?= $datos['paciente_id']==$p['id']?'selected':'' ?>><?= limpiar($p['nombre']) ?></option>
                        <?php endwhile; ?>
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
                    <label class="form-label">Fecha y hora</label>
                    <input type="datetime-local" name="fecha_hora" class="form-control" value="<?= limpiar($datos['fecha_hora']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Estado</label>
                    <select name="estado" class="form-select">
                        <?php foreach ($estados as $e): ?>
                            <option value="<?= $e ?>" <?= $datos['estado']===$e?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$e)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Motivo</label>
                    <input type="text" name="motivo" class="form-control" value="<?= limpiar($datos['motivo']) ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">Observaciones</label>
                    <textarea name="observaciones" class="form-control" rows="2"><?= limpiar($datos['observaciones']) ?></textarea>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Actualizar</button>
                <a href="index.php" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php $conn->close(); include '../includes/footer.php'; ?>
