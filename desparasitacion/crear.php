<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';
verificarSesion();
soloClinico();
$paginaTitulo = 'Registrar desparasitación';
$conn = conectar();

$pacienteId = (int)($_GET['paciente'] ?? 0);
$pacientes = $conn->query("SELECT p.id, CONCAT(p.nombre,' (',e.nombre,')') AS nombre FROM pacientes p JOIN especies e ON p.especie_id=e.id WHERE p.activo=1 ORDER BY p.nombre");
$errores = [];
$datos = ['paciente_id'=>$pacienteId,'tipo'=>'interna','producto'=>'','fecha'=>date('Y-m-d'),'dosis'=>'','proxima_fecha'=>'','observaciones'=>''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($datos as $k => $_) $datos[$k] = trim($_POST[$k] ?? '');
    if (!$datos['paciente_id']) $errores[] = 'Seleccione un paciente.';
    if (!$datos['producto']) $errores[] = 'El producto es requerido.';

    if (empty($errores)) {
        $pid = (int)$datos['paciente_id'];
        $tip = $conn->real_escape_string($datos['tipo']);
        $pro = $conn->real_escape_string($datos['producto']);
        $fec = $conn->real_escape_string($datos['fecha']);
        $dos = $conn->real_escape_string($datos['dosis']);
        $pf = $datos['proxima_fecha'] ? "'".$conn->real_escape_string($datos['proxima_fecha'])."'" : 'NULL';
        $obs = $conn->real_escape_string($datos['observaciones']);
        $conn->query("INSERT INTO desparasitaciones (paciente_id,tipo,producto,fecha,dosis,proxima_fecha,observaciones) VALUES ($pid,'$tip','$pro','$fec','$dos',$pf,'$obs')");
        $newId = $conn->insert_id;
        registrarLog($conn, 'Registró desparasitación', 'desparasitaciones', $newId);
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

<div class="card" style="max-width:580px">
    <div class="card-header py-3"><i class="bi bi-shield-plus me-2"></i>Registro de desparasitación</div>
    <div class="card-body">
        <form method="POST">
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">Paciente <span class="text-danger">*</span></label>
                    <select name="paciente_id" class="form-select" required>
                        <option value="">Seleccionar...</option>
                        <?php while ($p = $pacientes->fetch_assoc()): ?>
                            <option value="<?= $p['id'] ?>" <?= $datos['paciente_id']==$p['id']?'selected':'' ?>><?= limpiar($p['nombre']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label">Tipo</label>
                    <select name="tipo" class="form-select">
                        <?php foreach (['interna'=>'Interna','externa'=>'Externa','ambas'=>'Ambas'] as $v => $l): ?>
                            <option value="<?= $v ?>" <?= $datos['tipo']===$v?'selected':'' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-7">
                    <label class="form-label">Producto <span class="text-danger">*</span></label>
                    <input type="text" name="producto" class="form-control" value="<?= limpiar($datos['producto']) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Fecha</label>
                    <input type="date" name="fecha" class="form-control" value="<?= limpiar($datos['fecha']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Dosis</label>
                    <input type="text" name="dosis" class="form-control" value="<?= limpiar($datos['dosis']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Próxima fecha</label>
                    <input type="date" name="proxima_fecha" class="form-control" value="<?= limpiar($datos['proxima_fecha']) ?>">
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
