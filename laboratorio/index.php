<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';
verificarSesion();
$paginaTitulo = 'Laboratorio clínico';
$conn = conectar();

$buscar = trim($_GET['q'] ?? '');
$estado = $_GET['estado'] ?? '';
$where = "WHERE 1=1";
if ($buscar) {
    $b = $conn->real_escape_string($buscar);
    $where .= " AND (p.nombre LIKE '%$b%' OR o.codigo LIKE '%$b%')";
}
if ($estado) {
    $e = $conn->real_escape_string($estado);
    $where .= " AND o.estado='$e'";
}

$ordenes = $conn->query("SELECT o.*,
    p.nombre AS paciente, e.nombre AS especie,
    CONCAT(pr.nombres,' ',pr.apellidos) AS propietario,
    CONCAT(u.nombres,' ',u.apellidos) AS veterinario,
    (SELECT COUNT(*) FROM orden_examenes WHERE orden_id=o.id) AS num_examenes,
    (SELECT COUNT(*) FROM resultados_laboratorio WHERE orden_id=o.id) AS num_resultados
    FROM ordenes_laboratorio o
    JOIN pacientes p ON o.paciente_id=p.id
    JOIN especies e ON p.especie_id=e.id
    JOIN propietarios pr ON p.propietario_id=pr.id
    JOIN usuarios u ON o.veterinario_id=u.id
    $where ORDER BY o.created_at DESC LIMIT 60");

$colores = ['pendiente'=>'warning','en_proceso'=>'info','completada'=>'success','cancelada'=>'secondary'];
$priorColors = ['rutina'=>'secondary','urgente'=>'warning','stat'=>'danger'];

include '../includes/header.php';
?>

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between py-3 flex-wrap gap-2">
        <span><i class="bi bi-eyedropper me-2 text-info"></i>Órdenes de laboratorio</span>
        <div class="d-flex gap-2 flex-wrap align-items-center">
            <form class="d-flex gap-2" method="GET">
                <input type="text" name="q" class="form-control form-control-sm" placeholder="Buscar paciente, código..." value="<?= limpiar($buscar) ?>">
                <select name="estado" class="form-select form-select-sm" style="min-width:130px">
                    <option value="">Todos los estados</option>
                    <option value="pendiente" <?= $estado==='pendiente'?'selected':'' ?>>Pendiente</option>
                    <option value="en_proceso" <?= $estado==='en_proceso'?'selected':'' ?>>En proceso</option>
                    <option value="completada" <?= $estado==='completada'?'selected':'' ?>>Completada</option>
                    <option value="cancelada" <?= $estado==='cancelada'?'selected':'' ?>>Cancelada</option>
                </select>
                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-search"></i></button>
            </form>
            <a href="crear_orden.php" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i>Nueva orden</a>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th>Código</th><th>Fecha</th><th>Paciente</th><th>Propietario</th>
                    <th>Veterinario</th><th>Exámenes</th><th>Prioridad</th><th>Estado</th><th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($ordenes->num_rows === 0): ?>
                <tr><td colspan="9" class="text-center text-muted py-4">No se encontraron órdenes de laboratorio</td></tr>
            <?php else: while ($o = $ordenes->fetch_assoc()): ?>
                <tr>
                    <td><code class="fw-bold"><?= limpiar($o['codigo']) ?></code></td>
                    <td><?= date('d/m/Y', strtotime($o['fecha'])) ?></td>
                    <td>
                        <a href="/sigvet/pacientes/perfil.php?id=<?= $o['paciente_id'] ?>"><?= limpiar($o['paciente']) ?></a>
                        <small class="text-muted d-block"><?= limpiar($o['especie']) ?></small>
                    </td>
                    <td><?= limpiar($o['propietario']) ?></td>
                    <td><?= limpiar($o['veterinario']) ?></td>
                    <td class="text-center">
                        <span class="badge bg-primary"><?= $o['num_examenes'] ?></span>
                        <?php if ($o['num_resultados'] > 0): ?>
                            <span class="badge bg-success ms-1" title="Resultados adjuntos"><?= $o['num_resultados'] ?> <i class="bi bi-paperclip"></i></span>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge bg-<?= $priorColors[$o['prioridad']] ?>"><?= ucfirst($o['prioridad']) ?></span></td>
                    <td>
                        <span class="badge bg-<?= $colores[$o['estado']] ?> <?= $o['estado']==='pendiente'?'text-dark':'' ?>">
                            <?= ucfirst(str_replace('_',' ',$o['estado'])) ?>
                        </span>
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="ver_orden.php?id=<?= $o['id'] ?>" class="btn btn-sm btn-outline-info" title="Ver orden"><i class="bi bi-eye"></i></a>
                            <a href="imprimir_orden.php?id=<?= $o['id'] ?>" class="btn btn-sm btn-outline-secondary" target="_blank" title="Imprimir orden"><i class="bi bi-printer"></i></a>
                        </div>
                    </td>
                </tr>
            <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php $conn->close(); include '../includes/footer.php'; ?>
