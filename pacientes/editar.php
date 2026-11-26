<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';
verificarSesion();
$conn = conectar();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: index.php'); exit; }

$pac = $conn->query("SELECT * FROM pacientes WHERE id=$id AND activo=1")->fetch_assoc();
if (!$pac) { header('Location: index.php'); exit; }

$paginaTitulo = 'Editar: ' . $pac['nombre'];
$especies = $conn->query("SELECT * FROM especies ORDER BY nombre");
$propietarios = $conn->query("SELECT id, CONCAT(nombres,' ',apellidos) AS nombre FROM propietarios WHERE activo=1 ORDER BY apellidos");
$todasRazas = $conn->query("SELECT * FROM razas ORDER BY nombre");
$razasPorEspecie = [];
while ($r = $todasRazas->fetch_assoc()) $razasPorEspecie[$r['especie_id']][] = $r;

$errores = [];
$datos = $pac;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (['nombre','especie_id','raza_id','sexo','fecha_nacimiento','color','peso','estado_reproductivo','microchip','propietario_id','observaciones'] as $k)
        $datos[$k] = trim($_POST[$k] ?? '');
    if (!$datos['nombre']) $errores[] = 'El nombre es requerido.';

    $fotoActual = $pac['foto'];
    if (!empty($_FILES['foto']['name'])) {
        $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
            $fotoActual = uniqid('pac_') . '.' . $ext;
            move_uploaded_file($_FILES['foto']['tmp_name'], UPLOAD_DIR . $fotoActual);
        }
    }

    if (empty($errores)) {
        $nom = $conn->real_escape_string($datos['nombre']);
        $eid = (int)$datos['especie_id'];
        $rid = $datos['raza_id'] ? (int)$datos['raza_id'] : 'NULL';
        $sex = $conn->real_escape_string($datos['sexo']);
        $fn = $datos['fecha_nacimiento'] ? "'".$conn->real_escape_string($datos['fecha_nacimiento'])."'" : 'NULL';
        $col = $conn->real_escape_string($datos['color']);
        $pes = $datos['peso'] !== '' ? (float)$datos['peso'] : 'NULL';
        $est = $conn->real_escape_string($datos['estado_reproductivo']);
        $mic = $conn->real_escape_string($datos['microchip']);
        $fot = $fotoActual ? "'".$conn->real_escape_string($fotoActual)."'" : 'NULL';
        $pid = (int)$datos['propietario_id'];
        $obs = $conn->real_escape_string($datos['observaciones']);
        $conn->query("UPDATE pacientes SET nombre='$nom',especie_id=$eid,raza_id=$rid,sexo='$sex',fecha_nacimiento=$fn,color='$col',peso=$pes,estado_reproductivo='$est',microchip='$mic',foto=$fot,propietario_id=$pid,observaciones='$obs' WHERE id=$id");
        registrarLog($conn, 'Editó paciente', 'pacientes', $id);
        $conn->close();
        header('Location: perfil.php?id=' . $id);
        exit;
    }
}

include '../includes/header.php';
?>

<?php if ($errores): ?>
    <?= alerta('danger', implode('<br>', array_map('limpiar', $errores))) ?>
<?php endif; ?>

<div class="card" style="max-width:800px">
    <div class="card-header py-3"><i class="bi bi-pencil me-2"></i>Editar paciente</div>
    <div class="card-body">
        <form method="POST" enctype="multipart/form-data">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nombre <span class="text-danger">*</span></label>
                    <input type="text" name="nombre" class="form-control" value="<?= limpiar($datos['nombre']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Propietario</label>
                    <select name="propietario_id" class="form-select" required>
                        <?php while ($p = $propietarios->fetch_assoc()): ?>
                            <option value="<?= $p['id'] ?>" <?= $datos['propietario_id']==$p['id']?'selected':'' ?>><?= limpiar($p['nombre']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Especie</label>
                    <select name="especie_id" id="especie" class="form-select" onchange="cargarRazas(this.value)">
                        <?php while ($e = $especies->fetch_assoc()): ?>
                            <option value="<?= $e['id'] ?>" <?= $datos['especie_id']==$e['id']?'selected':'' ?>><?= limpiar($e['nombre']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Raza</label>
                    <select name="raza_id" id="raza" class="form-select">
                        <option value="">Sin raza</option>
                        <?php
                        $razasActuales = $conn->query("SELECT * FROM razas WHERE especie_id=".(int)$datos['especie_id']." ORDER BY nombre");
                        while ($r = $razasActuales->fetch_assoc()): ?>
                            <option value="<?= $r['id'] ?>" <?= $datos['raza_id']==$r['id']?'selected':'' ?>><?= limpiar($r['nombre']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Sexo</label>
                    <select name="sexo" class="form-select">
                        <?php foreach (['Macho','Hembra','Desconocido'] as $s): ?>
                            <option <?= $datos['sexo']===$s?'selected':'' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Fecha nacimiento</label>
                    <input type="date" name="fecha_nacimiento" class="form-control" value="<?= limpiar($datos['fecha_nacimiento']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Color</label>
                    <input type="text" name="color" class="form-control" value="<?= limpiar($datos['color']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Peso (kg)</label>
                    <input type="number" step="0.01" name="peso" class="form-control" value="<?= limpiar($datos['peso']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Estado reproductivo</label>
                    <select name="estado_reproductivo" class="form-select">
                        <?php foreach (['Entero','Castrado','Esterilizado','Desconocido'] as $er): ?>
                            <option <?= $datos['estado_reproductivo']===$er?'selected':'' ?>><?= $er ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Microchip</label>
                    <input type="text" name="microchip" class="form-control" value="<?= limpiar($datos['microchip']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Fotografía <?= $datos['foto'] ? '<small class="text-muted">(tiene foto)</small>' : '' ?></label>
                    <input type="file" name="foto" class="form-control" accept="image/*">
                </div>
                <div class="col-12">
                    <label class="form-label">Observaciones</label>
                    <textarea name="observaciones" class="form-control" rows="2"><?= limpiar($datos['observaciones']) ?></textarea>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Actualizar</button>
                <a href="perfil.php?id=<?= $id ?>" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<script>
var razas = <?= json_encode($razasPorEspecie) ?>;
function cargarRazas(espId) {
    var sel = document.getElementById('raza');
    sel.innerHTML = '<option value="">Sin raza / mestizo</option>';
    if (razas[espId]) {
        razas[espId].forEach(function(r) {
            sel.innerHTML += '<option value="' + r.id + '">' + r.nombre + '</option>';
        });
    }
}
</script>

<?php $conn->close(); include '../includes/footer.php'; ?>
