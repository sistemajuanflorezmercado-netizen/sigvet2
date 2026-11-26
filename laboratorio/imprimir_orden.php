<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';
verificarSesion();
$conn = conectar();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: index.php'); exit; }

$orden = $conn->query("SELECT o.*,
    p.nombre AS paciente, p.fecha_nacimiento, p.sexo, p.peso AS peso_pac, p.microchip,
    e.nombre AS especie, COALESCE(r.nombre,'') AS raza,
    CONCAT(pr.nombres,' ',pr.apellidos) AS propietario, pr.telefono, pr.numero_identificacion, pr.tipo_identificacion,
    CONCAT(u.nombres,' ',u.apellidos) AS veterinario
    FROM ordenes_laboratorio o
    JOIN pacientes p ON o.paciente_id=p.id
    JOIN especies e ON p.especie_id=e.id
    LEFT JOIN razas r ON p.raza_id=r.id
    JOIN propietarios pr ON p.propietario_id=pr.id
    JOIN usuarios u ON o.veterinario_id=u.id
    WHERE o.id=$id")->fetch_assoc();
if (!$orden) die('Orden no encontrada.');

$examenes = $conn->query("SELECT oe.*, te.nombre AS nombre_tipo, te.categoria FROM orden_examenes oe JOIN tipos_examen te ON oe.tipo_examen_id=te.id WHERE oe.orden_id=$id ORDER BY te.categoria, te.nombre");
$examenesArr = [];
while ($ex = $examenes->fetch_assoc()) $examenesArr[] = $ex;

$edad = '';
if ($orden['fecha_nacimiento']) {
    $diff = (new DateTime())->diff(new DateTime($orden['fecha_nacimiento']));
    $edad = $diff->y ? $diff->y . ' año(s) ' . $diff->m . ' mes(es)' : $diff->m . ' mes(es)';
}

$priorColor = ['rutina'=>'#6c757d','urgente'=>'#fd7e14','stat'=>'#dc3545'];
$nuevo = isset($_GET['nuevo']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orden <?= htmlspecialchars($orden['codigo']) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 13px; color: #1a1a2e; background: #f5f5f5; }
        .page { max-width: 800px; margin: 20px auto; background: #fff; padding: 0; box-shadow: 0 2px 20px rgba(0,0,0,.1); }

        /* Header */
        .doc-header { background: linear-gradient(135deg, #1a1f2e 0%, #0d1117 100%); color: white; padding: 24px 32px; display: flex; justify-content: space-between; align-items: flex-start; }
        .clinic-name { font-size: 22px; font-weight: 700; letter-spacing: 1px; }
        .clinic-sub { font-size: 11px; opacity: .7; margin-top: 2px; letter-spacing: .5px; text-transform: uppercase; }
        .orden-badge { text-align: right; }
        .orden-code { font-size: 18px; font-weight: 700; font-family: monospace; letter-spacing: 2px; }
        .orden-fecha { font-size: 11px; opacity: .7; margin-top: 2px; }

        /* Prioridad banner */
        .prioridad-banner { padding: 8px 32px; font-size: 12px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; color: white; background: <?= $priorColor[$orden['prioridad']] ?>; }

        /* Secciones */
        .doc-body { padding: 24px 32px; }
        .section-title { font-size: 10px; text-transform: uppercase; letter-spacing: 1.5px; color: #6c757d; font-weight: 700; margin-bottom: 8px; padding-bottom: 4px; border-bottom: 2px solid #e9ecef; }
        .section { margin-bottom: 20px; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 4px 24px; }
        .info-item { display: flex; gap: 6px; padding: 4px 0; border-bottom: 1px dashed #f0f0f0; }
        .info-label { color: #6c757d; min-width: 110px; font-size: 11px; }
        .info-value { font-weight: 600; }

        /* Tabla exámenes */
        .examenes-table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        .examenes-table th { background: #f8f9fa; padding: 8px 12px; text-align: left; font-size: 10px; text-transform: uppercase; letter-spacing: .5px; color: #495057; border-bottom: 2px solid #dee2e6; }
        .examenes-table td { padding: 9px 12px; border-bottom: 1px solid #f0f2f5; vertical-align: top; }
        .examenes-table tr:last-child td { border-bottom: none; }
        .num-col { width: 36px; text-align: center; color: #6c757d; }
        .check-col { width: 48px; text-align: center; }
        .check-box { width: 16px; height: 16px; border: 2px solid #adb5bd; display: inline-block; border-radius: 3px; }
        .cat-badge { font-size: 10px; background: #e9ecef; color: #495057; padding: 2px 6px; border-radius: 10px; }

        /* Firma */
        .firma-section { display: flex; justify-content: flex-end; margin-top: 32px; }
        .firma-box { text-align: center; border-top: 1px solid #adb5bd; padding-top: 8px; min-width: 220px; }
        .firma-nombre { font-weight: 700; font-size: 12px; }
        .firma-cargo { font-size: 10px; color: #6c757d; }

        /* Footer */
        .doc-footer { background: #f8f9fa; padding: 12px 32px; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #e9ecef; }
        .doc-footer small { color: #6c757d; font-size: 10px; }
        .qr-placeholder { width: 48px; height: 48px; background: #e9ecef; border-radius: 4px; display: flex; align-items: center; justify-content: center; color: #adb5bd; font-size: 10px; }

        /* Print */
        .screen-only { padding: 16px 32px 0; display: flex; gap: 8px; background: #f5f5f5; }
        .screen-only button, .screen-only a { padding: 8px 16px; border-radius: 6px; border: 1px solid #dee2e6; cursor: pointer; font-size: 13px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
        .btn-print { background: #0d6efd; color: white; border-color: #0d6efd; }
        .btn-back { background: white; color: #495057; }

        @media print {
            body { background: white; }
            .page { box-shadow: none; margin: 0; max-width: 100%; }
            .screen-only { display: none !important; }
        }
    </style>
</head>
<body>

<div class="screen-only">
    <button class="btn-print" onclick="window.print()">🖨️ Imprimir / Guardar PDF</button>
    <a href="ver_orden.php?id=<?= $id ?>" class="btn-back">← Volver a la orden</a>
    <?php if ($nuevo): ?>
        <span style="color:#198754;font-weight:600;margin-left:8px">✓ Orden creada exitosamente</span>
    <?php endif; ?>
</div>

<div class="page">
    <!-- Header -->
    <div class="doc-header">
        <div>
            <div class="clinic-name">🩺 <?= APP_NAME ?></div>
            <div class="clinic-sub">Sistema Integral de Gestión Veterinaria</div>
        </div>
        <div class="orden-badge">
            <div class="orden-code"><?= htmlspecialchars($orden['codigo']) ?></div>
            <div class="orden-fecha">Fecha: <?= date('d/m/Y', strtotime($orden['fecha'])) ?></div>
        </div>
    </div>

    <!-- Prioridad -->
    <div class="prioridad-banner">
        ⚡ Prioridad: <?= strtoupper($orden['prioridad']) ?>
        &nbsp;·&nbsp; ORDEN DE EXÁMENES DE LABORATORIO CLÍNICO
    </div>

    <div class="doc-body">
        <!-- Info paciente y propietario -->
        <div class="section">
            <div class="section-title">Información del paciente</div>
            <div class="info-grid">
                <div class="info-item"><span class="info-label">Paciente:</span><span class="info-value"><?= htmlspecialchars($orden['paciente']) ?></span></div>
                <div class="info-item"><span class="info-label">Especie / Raza:</span><span class="info-value"><?= htmlspecialchars($orden['especie']) ?><?= $orden['raza'] ? ' / '.$orden['raza'] : '' ?></span></div>
                <div class="info-item"><span class="info-label">Sexo:</span><span class="info-value"><?= htmlspecialchars($orden['sexo']) ?></span></div>
                <div class="info-item"><span class="info-label">Edad:</span><span class="info-value"><?= $edad ?: 'No registrada' ?></span></div>
                <div class="info-item"><span class="info-label">Peso:</span><span class="info-value"><?= $orden['peso_pac'] ? $orden['peso_pac'].' kg' : 'No registrado' ?></span></div>
                <div class="info-item"><span class="info-label">Microchip:</span><span class="info-value"><?= htmlspecialchars($orden['microchip'] ?: 'No registrado') ?></span></div>
            </div>
        </div>

        <div class="section">
            <div class="section-title">Propietario / Responsable</div>
            <div class="info-grid">
                <div class="info-item"><span class="info-label">Nombre:</span><span class="info-value"><?= htmlspecialchars($orden['propietario']) ?></span></div>
                <div class="info-item"><span class="info-label">Identificación:</span><span class="info-value"><?= htmlspecialchars($orden['tipo_identificacion'].' '.$orden['numero_identificacion']) ?></span></div>
                <div class="info-item"><span class="info-label">Teléfono:</span><span class="info-value"><?= htmlspecialchars($orden['telefono'] ?: '-') ?></span></div>
                <div class="info-item"><span class="info-label">Veterinario:</span><span class="info-value">Dr. <?= htmlspecialchars($orden['veterinario']) ?></span></div>
            </div>
        </div>

        <?php if ($orden['diagnostico_presuntivo']): ?>
        <div class="section">
            <div class="section-title">Justificación clínica / Diagnóstico presuntivo</div>
            <p style="background:#f8f9fa;padding:10px 14px;border-left:3px solid #0d6efd;border-radius:4px;margin:0"><?= htmlspecialchars($orden['diagnostico_presuntivo']) ?></p>
        </div>
        <?php endif; ?>

        <!-- Tabla de exámenes -->
        <div class="section">
            <div class="section-title">Exámenes solicitados (<?= count($examenesArr) ?>)</div>
            <table class="examenes-table">
                <thead>
                    <tr>
                        <th class="num-col">#</th>
                        <th>Examen</th>
                        <th>Categoría</th>
                        <th>Indicaciones</th>
                        <th class="check-col">✓ Lab.</th>
                    </tr>
                </thead>
                <tbody>
                <?php $nomCat = ['hematologia'=>'Hematología','bioquimica'=>'Bioquímica','urinalisis'=>'Uroanálisis','coprologia'=>'Coprología','microbiologia'=>'Microbiología','imagenologia'=>'Imagenología','citologia'=>'Citología','otro'=>'Otro'];
                foreach ($examenesArr as $i => $ex): ?>
                    <tr>
                        <td class="num-col" style="color:#6c757d"><?= $i+1 ?></td>
                        <td style="font-weight:600"><?= htmlspecialchars($ex['nombre_personalizado'] ?: $ex['nombre_tipo']) ?></td>
                        <td><span class="cat-badge"><?= $nomCat[$ex['categoria']] ?? ucfirst($ex['categoria']) ?></span></td>
                        <td style="color:#6c757d;font-size:12px"><?= htmlspecialchars($ex['indicaciones'] ?? '') ?></td>
                        <td class="check-col"><span class="check-box"></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($orden['observaciones']): ?>
        <div class="section">
            <div class="section-title">Observaciones para el laboratorio</div>
            <p style="font-style:italic;color:#495057"><?= htmlspecialchars($orden['observaciones']) ?></p>
        </div>
        <?php endif; ?>

        <!-- Espacio para datos del laboratorio -->
        <div class="section" style="background:#f8f9fa;padding:14px;border-radius:8px;border:1px dashed #dee2e6">
            <div class="section-title" style="margin-bottom:12px">Para uso del laboratorio</div>
            <div class="info-grid">
                <div class="info-item"><span class="info-label">Recibido por:</span><span style="border-bottom:1px solid #adb5bd;flex:1">&nbsp;</span></div>
                <div class="info-item"><span class="info-label">Fecha/Hora recibo:</span><span style="border-bottom:1px solid #adb5bd;flex:1">&nbsp;</span></div>
                <div class="info-item"><span class="info-label">Muestra recibida:</span><span style="border-bottom:1px solid #adb5bd;flex:1">&nbsp;</span></div>
                <div class="info-item"><span class="info-label">Condición muestra:</span><span style="border-bottom:1px solid #adb5bd;flex:1">&nbsp;</span></div>
            </div>
        </div>

        <!-- Firma -->
        <div class="firma-section">
            <div class="firma-box">
                <div class="firma-nombre">Dr. <?= htmlspecialchars($orden['veterinario']) ?></div>
                <div class="firma-cargo">Médico Veterinario – SIGVET</div>
                <div class="firma-cargo" style="margin-top:2px"><?= date('d/m/Y') ?></div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="doc-footer">
        <div>
            <small style="font-weight:600"><?= APP_NAME ?> – Sistema Integral de Gestión Veterinaria</small><br>
            <small>Generado el <?= date('d/m/Y H:i') ?> · Orden <?= htmlspecialchars($orden['codigo']) ?></small>
        </div>
        <div class="qr-placeholder" style="font-size:8px;text-align:center">QR<br>Orden</div>
    </div>
</div>

<?php if ($nuevo): ?>
<script>
    setTimeout(function() { window.print(); }, 600);
</script>
<?php endif; ?>

</body>
</html>
<?php $conn->close(); ?>
