<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';
verificarSesion();
$paginaTitulo = 'Nuevo paciente';
$conn = conectar();

$especies = $conn->query("SELECT * FROM especies ORDER BY nombre");
$propietarios = $conn->query("SELECT id, CONCAT(nombres,' ',apellidos) AS nombre FROM propietarios WHERE activo=1 ORDER BY apellidos");
$errores = [];
$datos = ['nombre'=>'','especie_id'=>'','raza_id'=>'','sexo'=>'Desconocido','fecha_nacimiento'=>'','color'=>'','peso'=>'','estado_reproductivo'=>'Desconocido','microchip'=>'','propietario_id'=>$_GET['propietario']??'','observaciones'=>''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($datos as $k => $_) $datos[$k] = trim($_POST[$k] ?? '');
    if (!$datos['nombre']) $errores[] = 'El nombre del paciente es requerido.';
    if (!$datos['especie_id']) $errores[] = 'La especie es requerida.';
    if (!$datos['propietario_id']) $errores[] = 'El propietario es requerido.';

    $foto = null;
    if (!empty($_FILES['foto']['name'])) {
        $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
            $foto = uniqid('pac_') . '.' . $ext;
            move_uploaded_file($_FILES['foto']['tmp_name'], UPLOAD_DIR . $foto);
        } else {
            $errores[] = 'Formato de imagen no permitido.';
        }
    }

    if (empty($errores)) {
        $stmt = $conn->prepare("INSERT INTO pacientes (nombre,especie_id,raza_id,sexo,fecha_nacimiento,color,peso,estado_reproductivo,microchip,foto,propietario_id,observaciones) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
        $espId = $datos['especie_id'] ?: null;
        $razId = $datos['raza_id'] ?: null;
        $propId = $datos['propietario_id'];
        $fn = $datos['fecha_nacimiento'] ?: null;
        $peso = $datos['peso'] !== '' ? (float)$datos['peso'] : null;
        $stmt->bind_param('siissdssssss', $datos['nombre'],$espId,$razId,$datos['sexo'],$fn,$datos['color'],$peso,$datos['estado_reproductivo'],$datos['microchip'],$foto,$propId,$datos['observaciones']);

        // Fix: bind_param types
        $stmt->close();

        $eid = (int)$datos['especie_id'];
        $rid = $datos['raza_id'] ? (int)$datos['raza_id'] : 'NULL';
        $pid = (int)$datos['propietario_id'];
        $nom = $conn->real_escape_string($datos['nombre']);
        $sex = $conn->real_escape_string($datos['sexo']);
        $fnb = $datos['fecha_nacimiento'] ? "'".$conn->real_escape_string($datos['fecha_nacimiento'])."'" : 'NULL';
        $col = $conn->real_escape_string($datos['color']);
        $pes = $datos['peso'] !== '' ? (float)$datos['peso'] : 'NULL';
        $est = $conn->real_escape_string($datos['estado_reproductivo']);
        $mic = $conn->real_escape_string($datos['microchip']);
        $fot = $foto ? "'".$conn->real_escape_string($foto)."'" : 'NULL';
        $obs = $conn->real_escape_string($datos['observaciones']);

        $sql = "INSERT INTO pacientes (nombre,especie_id,raza_id,sexo,fecha_nacimiento,color,peso,estado_reproductivo,microchip,foto,propietario_id,observaciones) VALUES ('$nom',$eid,$rid,'$sex',$fnb,'$col',$pes,'$est','$mic',$fot,$pid,'$obs')";
        $conn->query($sql);
        $newId = $conn->insert_id;
        registrarLog($conn, 'Creó paciente', 'pacientes', $newId);
        $conn->close();
        header('Location: perfil.php?id=' . $newId);
        exit;
    }
}

// Razas por especie (para JS)
$todasRazas = $conn->query("SELECT * FROM razas ORDER BY nombre");
$razasPorEspecie = [];
while ($r = $todasRazas->fetch_assoc()) {
    $razasPorEspecie[$r['especie_id']][] = $r;
}

include '../includes/header.php';
?>

<?php if ($errores): ?>
    <?= alerta('danger', implode('<br>', array_map('limpiar', $errores))) ?>
<?php endif; ?>

<div class="card" style="max-width:800px">
    <div class="card-header py-3"><i class="bi bi-paw me-2"></i>Datos del paciente</div>
    <div class="card-body">
        <form method="POST" enctype="multipart/form-data">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nombre <span class="text-danger">*</span></label>
                    <input type="text" name="nombre" class="form-control" value="<?= limpiar($datos['nombre']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Propietario <span class="text-danger">*</span></label>
                    <select name="propietario_id" class="form-select" required>
                        <option value="">Seleccionar...</option>
                        <?php while ($p = $propietarios->fetch_assoc()): ?>
                            <option value="<?= $p['id'] ?>" <?= $datos['propietario_id']==$p['id']?'selected':'' ?>><?= limpiar($p['nombre']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Especie <span class="text-danger">*</span></label>
                    <select name="especie_id" id="especie" class="form-select" required onchange="cargarRazas(this.value)">
                        <option value="">Seleccionar...</option>
                        <?php while ($e = $especies->fetch_assoc()): ?>
                            <option value="<?= $e['id'] ?>" <?= $datos['especie_id']==$e['id']?'selected':'' ?>><?= limpiar($e['nombre']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Raza</label>
                    <select name="raza_id" id="raza" class="form-select">
                        <option value="">Seleccionar especie primero</option>
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
                    <label class="form-label">Color / pelaje</label>
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
                    <label class="form-label">Fotografía</label>
                    <input type="file" name="foto" class="form-control" accept="image/*">
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
