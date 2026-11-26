<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';
verificarSesion();
$conn = conectar();

$pacId = (int)($_GET['paciente'] ?? 0);
if (!$pacId) { header('Location: index.php'); exit; }

$pac = $conn->query("SELECT p.*, e.nombre AS especie, COALESCE(r.nombre,'') AS raza,
    CONCAT(pr.nombres,' ',pr.apellidos) AS propietario,
    pr.tipo_identificacion, pr.numero_identificacion, pr.telefono, pr.email, pr.ciudad
    FROM pacientes p
    JOIN especies e ON p.especie_id=e.id
    LEFT JOIN razas r ON p.raza_id=r.id
    JOIN propietarios pr ON p.propietario_id=pr.id
    WHERE p.id=$pacId AND p.activo=1")->fetch_assoc();
if (!$pac) { header('Location: index.php'); exit; }

$vacunas = $conn->query("SELECT v.*, CONCAT(u.nombres,' ',u.apellidos) AS veterinario
    FROM vacunas v
    LEFT JOIN usuarios u ON v.veterinario_id=u.id
    WHERE v.paciente_id=$pacId
    ORDER BY v.fecha_aplicacion DESC");
$vacArr = [];
while ($v = $vacunas->fetch_assoc()) $vacArr[] = $v;

$edad = '';
if ($pac['fecha_nacimiento']) {
    $diff = (new DateTime())->diff(new DateTime($pac['fecha_nacimiento']));
    $edad = $diff->y ? $diff->y . ' año(s) ' . $diff->m . ' mes(es)' : $diff->m . ' mes(es)';
}

$certNum = 'VAC-' . date('Y') . '-' . str_pad($pacId, 5, '0', STR_PAD_LEFT);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificado de Vacunación – <?= htmlspecialchars($pac['nombre']) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Georgia, serif; font-size: 13px; color: #1a1a2e; background: #eef2f7; }

        .controls { padding: 14px 24px; background: #1a1f2e; display: flex; gap: 10px; align-items: center; }
        .controls button, .controls a { padding: 8px 18px; border-radius: 6px; border: none; cursor: pointer; font-size: 13px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
        .btn-print { background: #0d6efd; color: white; }
        .btn-back { background: rgba(255,255,255,.15); color: white; }
        .controls span { color: rgba(255,255,255,.6); font-size: 13px; }

        .cert-wrapper { max-width: 860px; margin: 24px auto; }

        /* Certificado exterior (marco decorativo) */
        .cert-outer {
            background: #fff;
            border: 3px solid #1a3a6b;
            border-radius: 4px;
            padding: 8px;
        }
        .cert-inner {
            border: 1.5px solid #c9a227;
            border-radius: 2px;
            padding: 0;
            overflow: hidden;
        }

        /* Encabezado */
        .cert-header {
            background: linear-gradient(135deg, #1a3a6b 0%, #0d1f4a 50%, #1a3a6b 100%);
            padding: 28px 40px 24px;
            text-align: center;
            position: relative;
        }
        .cert-header::after {
            content: '';
            position: absolute;
            bottom: 0; left: 0; right: 0; height: 4px;
            background: linear-gradient(90deg, #c9a227, #f0d060, #c9a227);
        }
        .cert-logo-circle {
            width: 70px; height: 70px;
            background: linear-gradient(135deg, #c9a227, #f0d060);
            border-radius: 50%;
            margin: 0 auto 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 30px;
            box-shadow: 0 4px 16px rgba(201,162,39,.4);
        }
        .cert-title-main { color: #c9a227; font-size: 11px; letter-spacing: 4px; text-transform: uppercase; font-weight: 600; margin-bottom: 6px; }
        .cert-title-big { color: #fff; font-size: 26px; font-weight: 700; letter-spacing: 1px; margin-bottom: 4px; }
        .cert-title-sub { color: rgba(255,255,255,.6); font-size: 11px; letter-spacing: 2px; text-transform: uppercase; }
        .cert-num { position: absolute; top: 16px; right: 24px; color: rgba(255,255,255,.4); font-size: 10px; font-family: monospace; }

        /* Cuerpo */
        .cert-body { padding: 32px 40px; }

        /* Ornamento central */
        .cert-ornament { text-align: center; margin: 0 0 24px; color: #c9a227; font-size: 18px; letter-spacing: 8px; }

        /* Info boxes */
        .info-boxes { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 28px; }
        .info-box {
            border: 1px solid #e8e0d0;
            border-radius: 8px;
            overflow: hidden;
        }
        .info-box-title {
            background: linear-gradient(90deg, #1a3a6b, #2a4a8b);
            color: white; padding: 8px 14px;
            font-size: 10px; text-transform: uppercase; letter-spacing: 1.5px; font-weight: 700;
        }
        .info-box-body { padding: 12px 14px; }
        .info-row { display: flex; gap: 6px; padding: 3px 0; font-size: 12px; }
        .info-lbl { color: #6c757d; min-width: 90px; }
        .info-val { font-weight: 600; color: #1a1f2e; }

        /* Tabla vacunas */
        .vacunas-section { margin-bottom: 28px; }
        .vacunas-section-title {
            background: linear-gradient(90deg, #1a3a6b, #2a4a8b);
            color: white; padding: 10px 16px;
            font-size: 10px; text-transform: uppercase; letter-spacing: 1.5px; font-weight: 700;
            border-radius: 6px 6px 0 0;
            display: flex; justify-content: space-between; align-items: center;
        }
        .vacunas-table { width: 100%; border-collapse: collapse; }
        .vacunas-table th {
            background: #f0ebe0; padding: 9px 12px;
            font-size: 10px; text-transform: uppercase; letter-spacing: .5px;
            color: #495057; border-bottom: 1px solid #ddd; text-align: left;
        }
        .vacunas-table td { padding: 10px 12px; border-bottom: 1px solid #f5f0e8; font-size: 12px; vertical-align: top; }
        .vacunas-table tr:last-child td { border-bottom: none; }
        .vacunas-table tr:nth-child(even) td { background: #fdfbf6; }
        .vacuna-name { font-weight: 700; color: #1a3a6b; }
        .status-vigente { color: #198754; font-weight: 700; font-size: 11px; }
        .status-vencida { color: #dc3545; font-weight: 700; font-size: 11px; }

        /* Sin vacunas */
        .no-vacunas { text-align: center; padding: 32px; color: #adb5bd; border: 1px solid #eee; border-radius: 0 0 6px 6px; }

        /* Certificación texto */
        .cert-text {
            background: linear-gradient(135deg, #fafaf5, #f5f0e0);
            border: 1px solid #e8e0c0;
            border-radius: 8px;
            padding: 16px 20px;
            text-align: center;
            margin-bottom: 28px;
            line-height: 1.7;
            font-size: 12.5px;
            color: #2c2c3e;
        }
        .cert-text strong { color: #1a3a6b; }

        /* Firmas */
        .firmas { display: flex; justify-content: space-around; margin-top: 8px; }
        .firma { text-align: center; min-width: 180px; }
        .firma-line { border-top: 1px solid #adb5bd; padding-top: 8px; margin-top: 32px; }
        .firma-name { font-weight: 700; font-size: 12px; color: #1a1f2e; }
        .firma-role { font-size: 10px; color: #6c757d; margin-top: 2px; }

        /* Footer del cert */
        .cert-footer {
            background: linear-gradient(135deg, #f0ebe0, #e8e0d0);
            border-top: 2px solid #c9a227;
            padding: 14px 40px;
            display: flex; justify-content: space-between; align-items: center;
        }
        .cert-footer-left { font-size: 10px; color: #6c757d; line-height: 1.6; }
        .cert-footer-right { text-align: right; font-size: 10px; color: #6c757d; }
        .cert-valid { color: #1a3a6b; font-weight: 700; }

        /* Corner decorations */
        .corner { position: absolute; width: 30px; height: 30px; border-color: #c9a227; border-style: solid; }
        .cert-inner-wrap { position: relative; }

        @media print {
            body { background: white; }
            .controls { display: none !important; }
            .cert-wrapper { margin: 0; max-width: 100%; }
        }
    </style>
</head>
<body>

<div class="controls">
    <button class="btn-print" onclick="window.print()">🖨️ Imprimir / Descargar PDF</button>
    <a href="/sigvet/vacunas/index.php" class="btn-back">← Volver</a>
    <span><?= htmlspecialchars($pac['nombre']) ?> · <?= count($vacArr) ?> vacuna(s) registrada(s)</span>
</div>

<div class="cert-wrapper">
<div class="cert-outer">
<div class="cert-inner">

    <!-- ENCABEZADO -->
    <div class="cert-header">
        <div class="cert-num"><?= $certNum ?></div>
        <div class="cert-logo-circle">🐾</div>
        <div class="cert-title-main"><?= APP_NAME ?> – Sistema Integral de Gestión Veterinaria</div>
        <div class="cert-title-big">Certificado de Vacunación</div>
        <div class="cert-title-sub">Medicina Veterinaria · Documento Oficial</div>
    </div>

    <!-- CUERPO -->
    <div class="cert-body">
        <div class="cert-ornament">❧ &nbsp; · &nbsp; ❧</div>

        <!-- Info boxes -->
        <div class="info-boxes">
            <div class="info-box">
                <div class="info-box-title">🐾 Datos del paciente</div>
                <div class="info-box-body">
                    <div class="info-row"><span class="info-lbl">Nombre:</span><span class="info-val"><?= htmlspecialchars($pac['nombre']) ?></span></div>
                    <div class="info-row"><span class="info-lbl">Especie:</span><span class="info-val"><?= htmlspecialchars($pac['especie']) ?></span></div>
                    <?php if ($pac['raza']): ?><div class="info-row"><span class="info-lbl">Raza:</span><span class="info-val"><?= htmlspecialchars($pac['raza']) ?></span></div><?php endif; ?>
                    <div class="info-row"><span class="info-lbl">Sexo:</span><span class="info-val"><?= htmlspecialchars($pac['sexo']) ?></span></div>
                    <div class="info-row"><span class="info-lbl">Edad:</span><span class="info-val"><?= $edad ?: 'No registrada' ?></span></div>
                    <div class="info-row"><span class="info-lbl">E. reprod.:</span><span class="info-val"><?= htmlspecialchars($pac['estado_reproductivo']) ?></span></div>
                    <?php if ($pac['microchip']): ?><div class="info-row"><span class="info-lbl">Microchip:</span><span class="info-val"><?= htmlspecialchars($pac['microchip']) ?></span></div><?php endif; ?>
                    <?php if ($pac['color']): ?><div class="info-row"><span class="info-lbl">Color:</span><span class="info-val"><?= htmlspecialchars($pac['color']) ?></span></div><?php endif; ?>
                </div>
            </div>
            <div class="info-box">
                <div class="info-box-title">👤 Datos del propietario</div>
                <div class="info-box-body">
                    <div class="info-row"><span class="info-lbl">Nombre:</span><span class="info-val"><?= htmlspecialchars($pac['propietario']) ?></span></div>
                    <div class="info-row"><span class="info-lbl">Identificación:</span><span class="info-val"><?= htmlspecialchars($pac['tipo_identificacion'].' '.$pac['numero_identificacion']) ?></span></div>
                    <?php if ($pac['telefono']): ?><div class="info-row"><span class="info-lbl">Teléfono:</span><span class="info-val"><?= htmlspecialchars($pac['telefono']) ?></span></div><?php endif; ?>
                    <?php if ($pac['email']): ?><div class="info-row"><span class="info-lbl">Email:</span><span class="info-val"><?= htmlspecialchars($pac['email']) ?></span></div><?php endif; ?>
                    <?php if ($pac['ciudad']): ?><div class="info-row"><span class="info-lbl">Ciudad:</span><span class="info-val"><?= htmlspecialchars($pac['ciudad']) ?></span></div><?php endif; ?>
                    <div class="info-row"><span class="info-lbl">Certificado:</span><span class="info-val" style="color:#1a3a6b"><?= $certNum ?></span></div>
                    <div class="info-row"><span class="info-lbl">Expedición:</span><span class="info-val"><?= date('d/m/Y') ?></span></div>
                </div>
            </div>
        </div>

        <!-- Tabla vacunas -->
        <div class="vacunas-section">
            <div class="vacunas-section-title">
                <span>💉 Registro de vacunas aplicadas</span>
                <span style="font-size:11px;opacity:.8"><?= count($vacArr) ?> registro(s)</span>
            </div>

            <?php if (empty($vacArr)): ?>
                <div class="no-vacunas">
                    <div style="font-size:28px;margin-bottom:8px">💉</div>
                    No se han registrado vacunas para este paciente.
                </div>
            <?php else: ?>
            <table class="vacunas-table">
                <thead>
                    <tr>
                        <th style="width:28px">#</th>
                        <th>Vacuna</th>
                        <th>Fecha aplicación</th>
                        <th>Próxima dosis</th>
                        <th>Lote</th>
                        <th>Veterinario</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($vacArr as $i => $v):
                    $hoy = time();
                    $vigente = $v['proxima_dosis'] && strtotime($v['proxima_dosis']) >= $hoy;
                    $sinProxima = !$v['proxima_dosis'];
                ?>
                    <tr>
                        <td style="text-align:center;color:#6c757d"><?= $i+1 ?></td>
                        <td><span class="vacuna-name"><?= htmlspecialchars($v['tipo_vacuna']) ?></span></td>
                        <td><?= date('d/m/Y', strtotime($v['fecha_aplicacion'])) ?></td>
                        <td><?= $v['proxima_dosis'] ? date('d/m/Y', strtotime($v['proxima_dosis'])) : '<span style="color:#adb5bd">—</span>' ?></td>
                        <td style="color:#6c757d;font-size:11px"><?= htmlspecialchars($v['lote'] ?: '—') ?></td>
                        <td style="font-size:11px">Dr. <?= htmlspecialchars($v['veterinario'] ?: '—') ?></td>
                        <td>
                            <?php if ($sinProxima): ?>
                                <span style="color:#6c757d;font-size:11px">Dosis única</span>
                            <?php elseif ($vigente): ?>
                                <span class="status-vigente">✓ Vigente</span>
                            <?php else: ?>
                                <span class="status-vencida">⚠ Vencida</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php if ($v['observaciones']): ?>
                    <tr>
                        <td></td>
                        <td colspan="6" style="color:#6c757d;font-size:11px;padding-top:0;padding-bottom:8px;font-style:italic">
                            Obs: <?= htmlspecialchars($v['observaciones']) ?>
                        </td>
                    </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <!-- Texto de certificación -->
        <div class="cert-text">
            El abajo firmante, en calidad de <strong>Médico Veterinario</strong>, certifica que el animal identificado como
            <strong><?= htmlspecialchars($pac['nombre']) ?></strong> (<?= htmlspecialchars($pac['especie']) ?>),
            de propiedad de <strong><?= htmlspecialchars($pac['propietario']) ?></strong>,
            ha recibido las vacunas detalladas en el presente certificado, aplicadas bajo supervisión profesional
            y conforme a los protocolos de medicina veterinaria vigentes.<br>
            <small style="color:#6c757d">Certificado generado el <?= date('d \d\e F \d\e Y') ?> · <?= APP_NAME ?></small>
        </div>

        <!-- Firmas -->
        <div class="firmas">
            <div class="firma">
                <div class="firma-line">
                    <div class="firma-name">Médico Veterinario</div>
                    <div class="firma-role">Firma y sello</div>
                </div>
            </div>
            <div class="firma">
                <div class="firma-line">
                    <div class="firma-name"><?= htmlspecialchars($pac['propietario']) ?></div>
                    <div class="firma-role">Propietario – Firma de recibido</div>
                </div>
            </div>
        </div>
    </div><!-- cert-body -->

    <!-- FOOTER -->
    <div class="cert-footer">
        <div class="cert-footer-left">
            <span class="cert-valid"><?= APP_NAME ?></span><br>
            Certificado Nº <?= $certNum ?> · Expedido: <?= date('d/m/Y') ?><br>
            Este documento tiene validez como constancia de inmunización veterinaria.
        </div>
            <?php
