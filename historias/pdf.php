<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';
verificarSesion();
$conn = conectar();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: index.php'); exit; }

$h = $conn->query("SELECT h.*,
    p.nombre AS paciente, p.id AS pac_id, p.fecha_nacimiento, p.sexo, p.color, p.microchip,
    p.estado_reproductivo, p.foto AS pac_foto,
    e.nombre AS especie, COALESCE(r.nombre,'') AS raza,
    CONCAT(pr.nombres,' ',pr.apellidos) AS propietario,
    pr.tipo_identificacion, pr.numero_identificacion, pr.telefono, pr.email,
    CONCAT(u.nombres,' ',u.apellidos) AS veterinario
    FROM historias_clinicas h
    JOIN pacientes p ON h.paciente_id=p.id
    JOIN especies e ON p.especie_id=e.id
    LEFT JOIN razas r ON p.raza_id=r.id
    JOIN propietarios pr ON p.propietario_id=pr.id
    JOIN usuarios u ON h.veterinario_id=u.id
    WHERE h.id=$id")->fetch_assoc();
if (!$h) { header('Location: index.php'); exit; }

// Tratamientos de esta historia
$tratamientos = $conn->query("SELECT * FROM tratamientos WHERE historia_id=$id ORDER BY id");

$edad = '';
if ($h['fecha_nacimiento']) {
    $diff = (new DateTime())->diff(new DateTime($h['fecha_nacimiento']));
    $edad = $diff->y ? $diff->y . ' año(s) ' . $diff->m . ' mes(es)' : $diff->m . ' mes(es)';
}

$docNum = 'HC-' . date('Y', strtotime($h['fecha_hora'])) . '-' . str_pad($id, 6, '0', STR_PAD_LEFT);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historia Clínica <?= $docNum ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 12.5px; color: #1a1f2e; background: #e8edf2; line-height: 1.5; }

        /* Controls barra superior */
        .controls {
            background: #1a1f2e; padding: 12px 24px;
            display: flex; gap: 10px; align-items: center;
        }
        .controls button, .controls a {
            padding: 8px 18px; border-radius: 6px; border: none; cursor: pointer;
            font-size: 13px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;
        }
        .btn-pdf { background: #dc3545; color: white; font-weight: 600; }
        .btn-back { background: rgba(255,255,255,.15); color: white; }
        .controls .doc-id { color: rgba(255,255,255,.5); font-family: monospace; font-size: 12px; margin-left: auto; }

        /* Página */
        .page {
            max-width: 860px; margin: 20px auto;
            background: white; box-shadow: 0 4px 24px rgba(0,0,0,.12);
        }

        /* ===== HEADER ===== */
        .doc-header {
            background: linear-gradient(135deg, #0d1b3e 0%, #1a3a6b 60%, #0d6efd 100%);
            padding: 0;
            display: flex; align-items: stretch;
        }
        .header-main { flex: 1; padding: 24px 28px; }
        .header-accent { width: 6px; background: linear-gradient(180deg, #c9a227, #f0d060, #c9a227); }
        .clinic-logo { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }
        .logo-icon { width: 48px; height: 48px; background: rgba(255,255,255,.15); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 24px; backdrop-filter: blur(10px); }
        .clinic-name-big { color: #fff; font-size: 20px; font-weight: 700; letter-spacing: .5px; }
        .clinic-tagline { color: rgba(255,255,255,.6); font-size: 10px; letter-spacing: 1.5px; text-transform: uppercase; }
        .doc-type-label {
            display: inline-block;
            background: rgba(201,162,39,.25); border: 1px solid rgba(201,162,39,.5);
            color: #f0d060; font-size: 10px; text-transform: uppercase; letter-spacing: 2px; font-weight: 700;
            padding: 4px 12px; border-radius: 20px;
        }
        .doc-meta { margin-top: 10px; display: flex; gap: 24px; flex-wrap: wrap; }
        .doc-meta-item { color: rgba(255,255,255,.7); font-size: 11px; }
        .doc-meta-item strong { color: #fff; display: block; font-size: 13px; }

        /* ===== INFO GRID ===== */
        .section-wrap { padding: 24px 28px; }
        .section-wrap + .section-wrap { padding-top: 0; }
        .section-divider { height: 1px; background: linear-gradient(90deg, #0d6efd 0%, #e9ecef 60%); margin: 0 28px 24px; }

        .section-label {
            font-size: 9.5px; text-transform: uppercase; letter-spacing: 2px;
            font-weight: 700; color: #0d6efd;
            border-left: 3px solid #0d6efd; padding-left: 8px;
            margin-bottom: 12px;
        }

        .info-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 0; border: 1px solid #e9ecef; border-radius: 8px; overflow: hidden; margin-bottom: 16px; }
        .info-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 0; border: 1px solid #e9ecef; border-radius: 8px; overflow: hidden; margin-bottom: 16px; }
        .info-cell { padding: 9px 14px; border-right: 1px solid #e9ecef; border-bottom: 1px solid #e9ecef; }
        .info-cell:last-child, .info-grid-2 .info-cell:nth-child(2n), .info-grid-3 .info-cell:nth-child(3n) { border-right: none; }
        .info-cell-lbl { font-size: 9px; text-transform: uppercase; letter-spacing: .5px; color: #6c757d; font-weight: 600; margin-bottom: 2px; }
        .info-cell-val { font-weight: 600; font-size: 13px; color: #1a1f2e; }

        /* Signos vitales */
        .vitales-row { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 20px; }
        .vital-card {
            flex: 1; min-width: 100px;
            border: 1px solid #e9ecef; border-radius: 10px; padding: 12px 14px; text-align: center;
            background: linear-gradient(135deg, #f8f9ff, #f0f4ff);
        }
        .vital-val { font-size: 22px; font-weight: 700; color: #0d6efd; }
        .vital-unit { font-size: 10px; color: #6c757d; }
        .vital-lbl { font-size: 10px; color: #6c757d; margin-top: 2px; text-transform: uppercase; letter-spacing: .5px; }

        /* Secciones de texto clínico */
        .clinical-sections { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 16px; }
        .clinical-section { border: 1px solid #e9ecef; border-radius: 8px; overflow: hidden; }
        .clinical-section.full { grid-column: 1 / -1; }
        .cs-header { background: #f8f9fa; padding: 8px 14px; border-bottom: 1px solid #e9ecef; font-size: 9.5px; text-transform: uppercase; letter-spacing: 1px; font-weight: 700; color: #495057; }
        .cs-body { padding: 12px 14px; min-height: 50px; font-size: 12.5px; color: #2c3e50; white-space: pre-line; }
        .cs-body.highlighted {
            background: linear-gradient(135deg, #e8f4f8, #d1ecf1);
            border-left: 3px solid #0d6efd;
            font-weight: 500;
        }
        .cs-body.treatment {
            background: linear-gradient(135deg, #f0fff4, #d4edda);
            border-left: 3px solid #28a745;
        }
        .empty-val { color: #adb5bd; font-style: italic; }

        /* Tabla tratamientos */
        .trat-table { width: 100%; border-collapse: collapse; font-size: 11.5px; }
        .trat-table th { background: #f8f9fa; padding: 7px 10px; text-align: left; font-size: 9.5px; text-transform: uppercase; letter-spacing: .5px; color: #6c757d; border-bottom: 2px solid #dee2e6; }
        .trat-table td { padding: 8px 10px; border-bottom: 1px solid #f5f5f5; }
        .trat-table tr:last-child td { border-bottom: none; }
        .med-name { font-weight: 700; color: #1a1f2e; }

        /* Footer del doc */
        .doc-footer {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-top: 2px solid #0d6efd;
            padding: 16px 28px;
            display: flex; justify-content: space-between; align-items: flex-end;
        }
        .footer-firma { text-align: center; }
        .firma-linea { border-top: 1px solid #495057; width: 200px; padding-top: 8px; margin-top: 32px; }
        .firma-nombre { font-weight: 700; font-size: 12px; }
        .firma-cargo { font-size: 10px; color: #6c757d; }
        .footer-meta { text-align: right; font-size: 10px; color: #6c757d; line-height: 1.8; }
        .footer-meta .num { color: #0d6efd; font-weight: 700; font-size: 13px; font-family: monospace; }

        /* Confidencial ribbon */
        .confidencial { background: #fff3cd; border-top: 1px solid #ffc107; padding: 6px 28px; font-size: 10px; color: #856404; text-align: center; letter-spacing: .5px; }

        @media print {
            body { background: white; font-size: 11px; }
            .controls { display: none !important; }
            .page { box-shadow: none; margin: 0; max-width: 100%; }
            .vital-card { break-inside: avoid; }
            .clinical-section { break-inside: avoid; }
        }
    </style>
</head>
<body>

<div class="controls">
    <button class="btn-pdf" onclick="window.print()">📄 Descargar / Guardar PDF</button>
    <a href="ver.php?id=<?= $id ?>" class="btn-back">← Volver</a>
    <span class="doc-id">Documento <?= $docNum ?></span>
</div>

<div class="page">

    <!-- ===== HEADER ===== -->
    <div class="doc-header">
        <div class="header-accent"></div>
        <div class="header-main">
            <div class="clinic-logo">
                <div class="logo-icon">🩺</div>
                <div>
                    <div class="clinic-name-big"><?= APP_NAME ?></div>
                    <div class="clinic-tagline">Sistema Integral de Gestión Veterinaria</div>
                </div>
            </div>
            <div class="doc-type-label">Historia Clínica Veterinaria</div>
            <div class="doc-meta">
                <div class="doc-meta-item">
                    <strong><?= date('d/m/Y H:i', strtotime($h['fecha_hora'])) ?></strong>
                    Fecha de atención
                </div>
                <div class="doc-meta-item">
                    <strong>Dr. <?= htmlspecialchars($h['veterinario']) ?></strong>
                    Médico Veterinario
                </div>
                <div class="doc-meta-item">
                    <strong><?= $docNum ?></strong>
                    Nº de Historia
                </div>
            </div>
        </div>
    </div>

    <!-- ===== PACIENTE & PROPIETARIO ===== -->
    <div class="section-wrap">
        <div class="section-label">Identificación del paciente</div>
        <div class="info-grid-3">
            <div class="info-cell"><div class="info-cell-lbl">Nombre</div><div class="info-cell-val"><?= htmlspecialchars($h['paciente']) ?></div></div>
            <div class="info-cell"><div class="info-cell-lbl">Especie</div><div class="info-cell-val"><?= htmlspecialchars($h['especie']) ?></div></div>
            <div class="info-cell"><div class="info-cell-lbl">Raza</div><div class="info-cell-val"><?= htmlspecialchars($h['raza'] ?: 'No especificada') ?></div></div>
            <div class="info-cell"><div class="info-cell-lbl">Sexo</div><div class="info-cell-val"><?= htmlspecialchars($h['sexo']) ?></div></div>
            <div class="info-cell"><div class="info-cell-lbl">Edad</div><div class="info-cell-val"><?= $edad ?: 'No registrada' ?></div></div>
            <div class="info-cell"><div class="info-cell-lbl">E. reproductivo</div><div class="info-cell-val"><?= htmlspecialchars($h['estado_reproductivo']) ?></div></div>
            <?php if ($h['color']): ?>
            <div class="info-cell"><div class="info-cell-lbl">Color / pelaje</div><div class="info-cell-val"><?= htmlspecialchars($h['color']) ?></div></div>
            <?php endif; ?>
            <?php if ($h['microchip']): ?>
            <div class="info-cell"><div class="info-cell-lbl">Microchip</div><div class="info-cell-val"><?= htmlspecialchars($h['microchip']) ?></div></div>
            <?php endif; ?>
        </div>

        <div class="section-label">Propietario / Responsable</div>
        <div class="info-grid-3">
            <div class="info-cell"><div class="info-cell-lbl">Nombre completo</div><div class="info-cell-val"><?= htmlspecialchars($h['propietario']) ?></div></div>
            <div class="info-cell"><div class="info-cell-lbl">Identificación</div><div class="info-cell-val"><?= htmlspecialchars($h['tipo_identificacion'].' '.$h['numero_identificacion']) ?></div></div>
            <div class="info-cell"><div class="info-cell-lbl">Teléfono</div><div class="info-cell-val"><?= htmlspecialchars($h['telefono'] ?: '—') ?></div></div>
        </div>
    </div>

    <div class="section-divider"></div>

    <!-- ===== SIGNOS VITALES ===== -->
    <?php $hayVitales = $h['peso'] || $h['temperatura'] || $h['frecuencia_cardiaca'] || $h['frecuencia_respiratoria']; ?>
    <?php if ($hayVitales): ?>
    <div class="section-wrap">
        <div class="section-label">Signos vitales</div>
        <div class="vitales-row">
            <?php if ($h['peso']): ?>
            <div class="vital-card">
                <div class="vital-val"><?= $h['peso'] ?></div>
                <div class="vital-unit">kg</div>
                <div class="vital-lbl">Peso</div>
            </div>
            <?php endif; ?>
            <?php if ($h['temperatura']): ?>
            <div class="vital-card">
                <div class="vital-val"><?= $h['temperatura'] ?></div>
                <div class="vital-unit">°C</div>
                <div class="vital-lbl">Temperatura</div>
            </div>
            <?php endif; ?>
            <?php if ($h['frecuencia_cardiaca']): ?>
            <div class="vital-card">
                <div class="vital-val"><?= $h['frecuencia_cardiaca'] ?></div>
                <div class="vital-unit">lpm</div>
                <div class="vital-lbl">F. Cardíaca</div>
            </div>
            <?php endif; ?>
            <?php if ($h['frecuencia_respiratoria']): ?>
            <div class="vital-card">
                <div class="vital-val"><?= $h['frecuencia_respiratoria'] ?></div>
                <div class="vital-unit">rpm</div>
                <div class="vital-lbl">F. Respiratoria</div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="section-divider"></div>
    <?php endif; ?>

    <!-- ===== ANAMNESIS ===== -->
    <div class="section-wrap">
        <div class="section-label">Anamnesis</div>
        <div class="clinical-sections">
            <div class="clinical-section full">
                <div class="cs-header">Motivo de consulta</div>
                <div class="cs-body highlighted"><?= htmlspecialchars($h['motivo'] ?: '—') ?></div>
            </div>
            <?php if ($h['antecedentes']): ?>
            <div class="clinical-section">
                <div class="cs-header">Antecedentes</div>
                <div class="cs-body"><?= htmlspecialchars($h['antecedentes']) ?></div>
            </div>
            <?php endif; ?>
            <?php if ($h['sintomas']): ?>
            <div class="clinical-section">
                <div class="cs-header">Síntomas / signos clínicos</div>
                <div class="cs-body"><?= htmlspecialchars($h['sintomas']) ?></div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ===== EXAMEN FÍSICO ===== -->
    <?php if ($h['examen_fisico']): ?>
    <div class="section-divider"></div>
    <div class="section-wrap">
        <div class="section-label">Exploración física</div>
        <div class="clinical-section full" style="border-radius:8px">
            <div class="cs-body" style="padding:14px 16px"><?= htmlspecialchars($h['examen_fisico']) ?></div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ===== DIAGNÓSTICO Y TRATAMIENTO ===== -->
    <div class="section-divider"></div>
    <div class="section-wrap">
        <div class="section-label">Diagnóstico y plan terapéutico</div>
        <div class="clinical-sections">
            <?php if ($h['diagnostico']): ?>
            <div class="clinical-section">
                <div class="cs-header">Diagnóstico</div>
                <div class="cs-body highlighted"><?= htmlspecialchars($h['diagnostico']) ?></div>
            </div>
            <?php endif; ?>
            <?php if ($h['tratamiento']): ?>
            <div class="clinical-section">
                <div class="cs-header">Tratamiento indicado</div>
                <div class="cs-body treatment"><?= htmlspecialchars($h['tratamiento']) ?></div>
            </div>
            <?php endif; ?>
            <?php if ($h['medicamentos']): ?>
            <div class="clinical-section full">
                <div class="cs-header">Medicamentos formulados</div>
                <div class="cs-body treatment"><?= htmlspecialchars($h['medicamentos']) ?></div>
            </div>
            <?php endif; ?>
        </div>

        <?php if ($tratamientos->num_rows > 0): ?>
        <div class="section-label" style="margin-top:4px">Medicamentos detallados</div>
        <table class="trat-table">
            <thead>
                <tr><th>#</th><th>Medicamento</th><th>Presentación</th><th>Dosis</th><th>Frecuencia</th><th>Vía</th><th>Duración</th></tr>
            </thead>
            <tbody>
            <?php $ti = 1; while ($t = $tratamientos->fetch_assoc()): ?>
                <tr>
                    <td style="color:#6c757d"><?= $ti++ ?></td>
                    <td><span class="med-name"><?= htmlspecialchars($t['medicamento']) ?></span></td>
                    <td style="color:#6c757d"><?= htmlspecialchars($t['presentacion'] ?: '—') ?></td>
                    <td><?= htmlspecialchars($t['dosis'] ?: '—') ?></td>
                    <td><?= htmlspecialchars($t['frecuencia'] ?: '—') ?></td>
                    <td><?= htmlspecialchars($t['via_administracion'] ?: '—') ?></td>
                    <td><?= htmlspecialchars($t['duracion'] ?: '—') ?></td>
                </tr>
                <?php if ($t['indicaciones']): ?>
                <tr><td></td><td colspan="6" style="color:#6c757d;font-size:11px;font-style:italic;padding-top:0">Indicaciones: <?= htmlspecialchars($t['indicaciones']) ?></td></tr>
                <?php endif; ?>
            <?php endwhile; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <!-- ===== RECOMENDACIONES Y OBSERVACIONES ===== -->
    <?php if ($h['recomendaciones'] || $h['observaciones']): ?>
    <div class="section-divider"></div>
    <div class="section-wrap">
        <div class="section-label">Recomendaciones y observaciones</div>
        <div class="clinical-sections">
            <?php if ($h['recomendaciones']): ?>
            <div class="clinical-section">
                <div class="cs-header">Recomendaciones al propietario</div>
                <div class="cs-body"><?= htmlspecialchars($h['recomendaciones']) ?></div>
            </div>
            <?php endif; ?>
            <?php if ($h['observaciones']): ?>
            <div class="clinical-section">
                <div class="cs-header">Observaciones adicionales</div>
                <div class="cs-body"><?= htmlspecialchars($h['observaciones']) ?></div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- ===== FOOTER CON FIRMA ===== -->
    <div class="doc-footer">
        <div class="footer-firma">
            <div class="firma-linea">
                <div class="firma-nombre">Dr. <?= htmlspecialchars($h['veterinario']) ?></div>
                <div class="firma-cargo">Médico Veterinario</div>
                <div class="firma-cargo"><?= date('d/m/Y', strtotime($h['fecha_hora'])) ?></div>
            </div>
        </div>
        <div class="footer-meta">
            <div class="num"><?= $docNum ?></div>
            <div><?= APP_NAME ?></div>
            <div>Sistema Integral de Gestión Veterinaria</div>
            <div style="margin-top:4px">Generado el <?= date('d/m/Y H:i') ?></div>
        </div>
    </div>

    <div class="confidencial">
        ⚠ DOCUMENTO CONFIDENCIAL – Información clínica del paciente · Solo para uso veterinario y del propietario autorizado
    </div>

</div><!-- .page -->

</body>
</html>
<?php $conn->close(); ?>
