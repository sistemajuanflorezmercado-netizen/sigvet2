<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';
verificarSesion();
soloClinico();
$paginaTitulo = 'Registrar tratamiento';
$conn = conectar();

$pacienteId = (int)($_GET['paciente'] ?? 0);
$pacientes = $conn->query("SELECT p.id, CONCAT(p.nombre,' (',e.nombre,')') AS nombre FROM pacientes p JOIN especies e ON p.especie_id=e.id WHERE p.activo=1 ORDER BY p.nombre");
$veterinarios = $conn->query("SELECT id, CONCAT(nombres,' ',apellidos) AS nombre FROM usuarios WHERE rol_id IN(1,2) AND activo=1");
$errores = [];
$datos = ['paciente_id'=>$pacienteId,'medicamento'=>'','presentacion'=>'','dosis'=>'','frecuencia'=>'','duracion'=>'','via_administracion'=>'','indicaciones'=>'','fecha_inicio'=>date('Y-m-d'),'fecha_fin'=>'','veterinario_id'=>$_SESSION['usuario_id']];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($datos as $k => $_) $datos[$k] = trim($_POST[$k] ?? '');
    if (!$datos['paciente_id']) $errores[] = 'Seleccione un paciente.';
    if (!$datos['medicamento']) $errores[] = 'El medicamento es requerido.';

    if (empty($errores)) {
        $pid = (int)$datos['paciente_id'];
        $vid = $datos['veterinario_id'] ? (int)$datos['veterinario_id'] : 'NULL';
        $med = $conn->real_escape_string($datos['medicamento']);
        $pre = $conn->real_escape_string($datos['presentacion']);
        $dos = $conn->real_escape_string($datos['dosis']);
        $fre = $conn->real_escape_string($datos['frecuencia']);
        $dur = $conn->real_escape_string($datos['duracion']);
        $via = $conn->real_escape_string($datos['via_administracion']);
        $ind = $conn->real_escape_string($datos['indicaciones']);
        $fi = $datos['fecha_inicio'] ? "'".$conn->real_escape_string($datos['fecha_inicio'])."'" : 'NULL';
        $ff = $datos['fecha_fin'] ? "'".$conn->real_escape_string($datos['fecha_fin'])."'" : 'NULL';
        $conn->query("INSERT INTO tratamientos (paciente_id,medicamento,presentacion,dosis,frecuencia,duracion,via_administracion,indicaciones,fecha_inicio,fecha_fin,veterinario_id) VALUES ($pid,'$med','$pre','$dos','$fre','$dur','$via','$ind',$fi,$ff,$vid)");
        $newId = $conn->insert_id;
        registrarLog($conn, 'Registró tratamiento', 'tratamientos', $newId);
        $conn->close();
        if ($pacienteId) header('Location: /sigvet/pacientes/perfil.php?id='.$pacienteId);
        else header('Location: index.php?ok=1');
        exit;
    }
}

include '../includes/header.php';
?>

<?php if ($errores): ?>
    <?= alerta('danger', implode('<br>', array_map('limpiar', $errores))) ?>
<?php endif; ?>

<div class="card" style="max-width:680px">
    <div class="card-header py-3"><i class="bi bi-capsule me-2"></i>Registrar tratamiento</div>
    <div class="card-body">
        <form method="POST">
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label">Paciente <span class="text-danger">*</span></label>
                    <select name="paciente_id" class="form-select" required>
                        <option value="">Seleccionar...</option>
                        <?php while ($p = $pacientes->fetch_assoc()): ?>
                            <option value="<?= $p['id'] ?>" <?= $datos['paciente_id']==$p['id']?'selected':'' ?>><?= limpiar($p['nombre']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Veterinario</label>
                    <select name="veterinario_id" class="form-select">
                        <?php while ($v = $veterinarios->fetch_assoc()): ?>
                            <option value="<?= $v['id'] ?>" <?= $datos['veterinario_id']==$v['id']?'selected':'' ?>><?= limpiar($v['nombre']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-7">
                    <label class="form-label">Medicamento <span class="text-danger">*</span></label>
                    <input type="text" name="medicamento" class="form-control" value="<?= limpiar($datos['medicamento']) ?>" required>
                </div>
                <div class="col-md-5">
                    <label class="form-label">Presentación</label>
                    <input type="text" name="presentacion" class="form-control" placeholder="Ej: Tabletas 250mg" value="<?= limpiar($datos['presentacion']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Dosis</label>
                    <input type="text" name="dosis" class="form-control" value="<?= limpiar($datos['dosis']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Frecuencia</label>
                    <input type="text" name="frecuencia" class="form-control" placeholder="Ej: Cada 12 horas" value="<?= limpiar($datos['frecuencia']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Duración</label>
                    <input type="text" name="duracion" class="form-control" placeholder="Ej: 7 días" value="<?= limpiar($datos['duracion']) ?>">
                </div>
                <div class="col-md-5">
                    <label class="form-label">Vía de administración</label>
                    <input type="text" name="via_administracion" class="form-control" placeholder="Ej: Oral, IM, SC" value="<?= limpiar($datos['via_administracion']) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Fecha inicio</label>
                    <input type="date" name="fecha_inicio" class="form-control" value="<?= limpiar($datos['fecha_inicio']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Fecha fin</label>
                    <input type="date" name="fecha_fin" class="form-control" value="<?= limpiar($datos['fecha_fin']) ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">Indicaciones</label>
                    <textarea name="indicaciones" class="form-control" rows="2"><?= limpiar($datos['indicaciones']) ?></textarea>
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
