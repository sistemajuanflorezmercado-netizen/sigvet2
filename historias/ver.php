<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';
verificarSesion();
$conn = conectar();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: index.php'); exit; }

$h = $conn->query("SELECT h.*, p.nombre AS paciente, p.id AS pac_id, p.peso AS peso_actual,
    e.nombre AS especie,
    CONCAT(pr.nombres,' ',pr.apellidos) AS propietario,
    CONCAT(u.nombres,' ',u.apellidos) AS veterinario
    FROM historias_clinicas h
    JOIN pacientes p ON h.paciente_id=p.id
    JOIN especies e ON p.especie_id=e.id
    JOIN propietarios pr ON p.propietario_id=pr.id
    JOIN usuarios u ON h.veterinario_id=u.id
    WHERE h.id=$id")->fetch_assoc();
if (!$h) { header('Location: index.php'); exit; }

$paginaTitulo = 'Historia clínica – ' . $h['paciente'];
include '../includes/header.php';
?>

<?php if (isset($_GET['ok'])): ?>
    <?= alerta('success', 'Historia clínica registrada correctamente.') ?>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <a href="/sigvet/pacientes/perfil.php?id=<?= $h['pac_id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Volver al paciente</a>
    <div class="d-flex gap-2">
        <a href="pdf.php?id=<?= $h['id'] ?>" target="_blank" class="btn btn-sm btn-danger"><i class="bi bi-file-earmark-pdf me-1"></i>Descargar PDF</a>
        <button onclick="window.print()" class="btn btn-sm btn-outline-secondary"><i class="bi bi-printer me-1"></i>Imprimir</button>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center py-3">
        <div>
            <h6 class="mb-0"><i class="bi bi-journal-medical me-2 text-info"></i>Historia Clínica</h6>
            <small class="text-muted"><?= date('d/m/Y H:i', strtotime($h['fecha_hora'])) ?></small>
        </div>
        <span class="badge bg-info text-dark">ID #<?= $h['id'] ?></span>
    </div>
    <div class="card-body">
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="p-3 bg-light rounded">
                    <div class="text-muted mb-1" style="font-size:.75rem;text-transform:uppercase">Paciente</div>
                    <strong><?= limpiar($h['paciente']) ?></strong>
                    <div class="text-muted"><?= limpiar($h['especie']) ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-3 bg-light rounded">
                    <div class="text-muted mb-1" style="font-size:.75rem;text-transform:uppercase">Propietario</div>
                    <strong><?= limpiar($h['propietario']) ?></strong>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-3 bg-light rounded">
                    <div class="text-muted mb-1" style="font-size:.75rem;text-transform:uppercase">Veterinario</div>
                    <strong>Dr. <?= limpiar($h['veterinario']) ?></strong>
                </div>
            </div>
        </div>

        <?php
        // Signos vitales
        $vitales = array_filter(['Peso' => $h['peso'] ? $h['peso'].' kg' : null, 'Temperatura' => $h['temperatura'] ? $h['temperatura'].'°C' : null, 'Freq. cardíaca' => $h['frecuencia_cardiaca'] ? $h['frecuencia_cardiaca'].' lpm' : null, 'Freq. respiratoria' => $h['frecuencia_respiratoria'] ? $h['frecuencia_respiratoria'].' rpm' : null]);
        if ($vitales): ?>
        <div class="mb-4">
            <h6 class="fw-semibold border-bottom pb-2 mb-3">Signos vitales</h6>
            <div class="d-flex gap-3 flex-wrap">
                <?php foreach ($vitales as $lbl => $val): ?>
                    <div class="p-3 border rounded text-center" style="min-width:120px">
                        <div class="fw-bold fs-5"><?= limpiar($val) ?></div>
                        <small class="text-muted"><?= $lbl ?></small>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php
        $secciones = [
            'Motivo de consulta' => $h['motivo'],
            'Antecedentes' => $h['antecedentes'],
            'Síntomas / signos clínicos' => $h['sintomas'],
            'Examen físico' => $h['examen_fisico'],
            'Diagnóstico' => $h['diagnostico'],
            'Tratamiento' => $h['tratamiento'],
            'Medicamentos formulados' => $h['medicamentos'],
            'Recomendaciones' => $h['recomendaciones'],
            'Observaciones' => $h['observaciones'],
        ];
        foreach ($secciones as $titulo => $contenido):
            if (!trim($contenido ?? '')) continue;
        ?>
            <div class="mb-3">
                <h6 class="fw-semibold text-muted border-bottom pb-1 mb-2" style="font-size:.85rem;text-transform:uppercase"><?= $titulo ?></h6>
                <p class="mb-0" style="white-space:pre-line"><?= limpiar($contenido) ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<style>
@media print {
    #sidebar, .topbar, .btn, nav { display: none !important; }
    #main { margin: 0 !important; }
    .page-content { padding: 0 !important; }
}
</style>

<?php $conn->close(); include '../includes/footer.php'; ?>
