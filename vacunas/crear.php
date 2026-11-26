<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';
verificarSesion();
soloClinico();
$paginaTitulo = 'Registrar vacuna';
$conn = conectar();

$pacienteId = (int)($_GET['paciente'] ?? 0);
$pacientes = $conn->query("SELECT p.id, CONCAT(p.nombre,' (',e.nombre,')') AS nombre FROM pacientes p JOIN especies e ON p.especie_id=e.id WHERE p.activo=1 ORDER BY p.nombre");
$veterinarios = $conn->query("SELECT id, CONCAT(nombres,' ',apellidos) AS nombre FROM usuarios WHERE rol_id IN(1,2) AND activo=1");
$errores = [];
$datos = ['paciente_id'=>$pacienteId,'tipo_vacuna'=>'','fecha_aplicacion'=>date('Y-m-d'),'proxima_dosis'=>'','lote'=>'','veterinario_id'=>$_SESSION['usuario_id'],'observaciones'=>''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($datos as $k => $_) $datos[$k] = trim($_POST[$k] ?? '');
    if (!$datos['paciente_id']) $errores[] = 'Seleccione un paciente.';
    if (!$datos['tipo_vacuna']) $errores[] = 'El tipo de vacuna es requerido.';
    if (!$datos['fecha_aplicacion']) $errores[] = 'La fecha es requerida.';

    if (empty($errores)) {
        $pid = (int)$datos['paciente_id'];
        $vid = $datos['veterinario_id'] ? (int)$datos['veterinario_id'] : 'NULL';
        $tv = $conn->real_escape_string($datos['tipo_vacuna']);
        $fa = $conn->real_escape_string($datos['fecha_aplicacion']);
        $pd = $datos['proxima_dosis'] ? "'".$conn->real_escape_string($datos['proxima_dosis'])."'" : 'NULL';
        $lot = $conn->real_escape_string($datos['lote']);
        $obs = $conn->real_escape_string($datos['observaciones']);
        $conn->query("INSERT INTO vacunas (paciente_id,tipo_vacuna,fecha_aplicacion,proxima_dosis,lote,veterinario_id,observaciones) VALUES ($pid,'$tv','$fa',$pd,'$lot',$vid,'$obs')");
        $newId = $conn->insert_id;
        registrarLog($conn, 'Registró vacuna', 'vacunas', $newId);
        $conn->close();
        if ($pacienteId) header('Location: /sigvet/pacientes/perfil.php?id='.$pacienteId.'#tab-vacunas');
        else header('Location: index.php?ok=1');
        exit;
    }
}

include '../includes/header.php';
?>

<?php if ($errores): ?>
    <?= alerta('danger', implode('<br>', array_map('limpiar', $errores))) ?>
<?php endif; ?>

<div class="card" style="max-width:640px">
    <div class="card-header py-3"><i class="bi bi-droplet me-2"></i>Registro de vacuna</div>
    <div class="card-body">
        <form method="POST">
            <div class="row g-3">
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
                    <label class="form-label">Veterinario</label>
                    <select name="veterinario_id" class="form-select">
                        <?php while ($v = $veterinarios->fetch_assoc()): ?>
                            <option value="<?= $v['id'] ?>" <?= $datos['veterinario_id']==$v['id']?'selected':'' ?>><?= limpiar($v['nombre']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Tipo / nombre de vacuna <span class="text-danger">*</span></label>
                    <input type="text" name="tipo_vacuna" class="form-control" value="<?= limpiar($datos['tipo_vacuna']) ?>" placeholder="Ej: Sextuple, Rabia, Parvovirus..." required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Fecha aplicación</label>
                    <input type="date" name="fecha_aplicacion" class="form-control" value="<?= limpiar($datos['fecha_aplicacion']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Próxima dosis</label>
                    <input type="date" name="proxima_dosis" class="form-control" value="<?= limpiar($datos['proxima_dosis']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Lote</label>
                    <input type="text" name="lote" class="form-control" value="<?= limpiar($datos['lote']) ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">Observaciones</label>
                    <textarea name="observaciones" class="form-control" rows="2"><?= limpiar($datos['observaciones']) ?></textarea>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Guardar</button>
                <a href="index.php" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<?php $conn->close(); include '../includes/footer.php'; ?>
