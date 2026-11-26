<?php
if (!isset($paginaTitulo)) $paginaTitulo = APP_NAME;
$rolUsuario = $_SESSION['rol'] ?? '';
$nombreUsuario = ($_SESSION['nombres'] ?? '') . ' ' . ($_SESSION['apellidos'] ?? '');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= limpiar($paginaTitulo) ?> – <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --sg-primary: #0d6efd;
            --sg-sidebar-bg: #1a1f2e;
            --sg-sidebar-text: #adb5bd;
            --sg-sidebar-active: #0d6efd;
            --sg-sidebar-hover: rgba(255,255,255,0.08);
        }
        body { background: #f0f2f5; font-family: 'Segoe UI', sans-serif; }
        /* Sidebar */
        #sidebar {
            width: 260px; min-height: 100vh; background: var(--sg-sidebar-bg);
            position: fixed; top: 0; left: 0; z-index: 100;
            display: flex; flex-direction: column;
            transition: transform .3s;
        }
        #sidebar .sidebar-brand {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }
        #sidebar .sidebar-brand span { color: #fff; font-size: 1.25rem; font-weight: 700; letter-spacing: .5px; }
        #sidebar .sidebar-brand small { color: var(--sg-sidebar-text); font-size: .7rem; }
        #sidebar nav { flex: 1; padding: .75rem 0; overflow-y: auto; }
        #sidebar nav .nav-section {
            padding: .5rem 1.5rem .25rem;
            font-size: .65rem; text-transform: uppercase;
            letter-spacing: 1px; color: #6c757d; font-weight: 600;
        }
        #sidebar nav a {
            display: flex; align-items: center; gap: .65rem;
            padding: .55rem 1.5rem; color: var(--sg-sidebar-text);
            text-decoration: none; font-size: .875rem;
            border-radius: 0; transition: all .2s;
        }
        #sidebar nav a:hover { background: var(--sg-sidebar-hover); color: #fff; }
        #sidebar nav a.active { background: var(--sg-sidebar-active); color: #fff; }
        #sidebar nav a i { font-size: 1rem; width: 1.25rem; }
        #sidebar .sidebar-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid rgba(255,255,255,0.08);
        }
        /* Main */
        #main { margin-left: 260px; min-height: 100vh; }
        .topbar {
            background: #fff; border-bottom: 1px solid #e9ecef;
            padding: .75rem 1.5rem; display: flex;
            align-items: center; justify-content: space-between;
            position: sticky; top: 0; z-index: 99;
        }
        .page-content { padding: 1.5rem; }
        /* Cards */
        .card { border: none; box-shadow: 0 1px 4px rgba(0,0,0,.06); border-radius: .75rem; }
        .card-header { background: #fff; border-bottom: 1px solid #f0f2f5; font-weight: 600; }
        /* Tables */
        .table thead th { background: #f8f9fa; font-size: .8rem; text-transform: uppercase; letter-spacing: .5px; color: #6c757d; }
        .table td { vertical-align: middle; font-size: .9rem; }
        /* Stat cards */
        .stat-card { border-radius: .75rem; padding: 1.25rem 1.5rem; color: #fff; }
        .stat-card i { font-size: 2rem; opacity: .85; }
        /* Badge estados */
        .badge-programada { background: #e9ecef; color: #495057; }
        .badge-confirmada { background: #d1ecf1; color: #0c5460; }
        .badge-atendida { background: #d4edda; color: #155724; }
        .badge-cancelada { background: #f8d7da; color: #721c24; }
        .badge-no_asistio { background: #fff3cd; color: #856404; }
        @media (max-width: 992px) {
            #sidebar { transform: translateX(-100%); }
            #sidebar.show { transform: translateX(0); }
            #main { margin-left: 0; }
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<div id="sidebar">
    <div class="sidebar-brand">
        <div><i class="bi bi-heart-pulse-fill text-primary me-2"></i><span><?= APP_NAME ?></span></div>
        <small>Gestión Veterinaria</small>
    </div>
    <nav>
        <span class="nav-section">Principal</span>
        <a href="/sigvet/dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>">
            <i class="bi bi-speedometer2"></i> Panel principal
        </a>

        <span class="nav-section">Gestión</span>
        <a href="/sigvet/propietarios/" class="<?= strpos($_SERVER['PHP_SELF'], '/propietarios/') !== false ? 'active' : '' ?>">
            <i class="bi bi-person-lines-fill"></i> Propietarios
        </a>
        <a href="/sigvet/pacientes/" class="<?= strpos($_SERVER['PHP_SELF'], '/pacientes/') !== false ? 'active' : '' ?>">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 24 24">
            <path d="M7.5 8.5C6.12 8.5 5 7.16 5 5.5S6.12 2.5 7.5 2.5 10 3.84 10 5.5 8.88 8.5 7.5 8.5Zm9 0C15.12 8.5 14 7.16 14 5.5s1.12-3 2.5-3S19 3.84 19 5.5s-1.12 3-2.5 3ZM4.5 13C3.12 13 2 11.66 2 10s1.12-3 2.5-3S7 8.34 7 10s-1.12 3-2.5 3Zm15 0c-1.38 0-2.5-1.34-2.5-3s1.12-3 2.5-3S22 8.34 22 10s-1.12 3-2.5 3ZM12 21.5c-3.04 0-6-2.06-6-5.12 0-2.52 2.17-4.38 4.1-5.73.75-.52 1.25-1.1 1.9-1.1s1.15.58 1.9 1.1c1.93 1.35 4.1 3.21 4.1 5.73 0 3.06-2.96 5.12-6 5.12Z"/>
          </svg>
          Pacientes
        </a>
        <a href="/sigvet/citas/" class="<?= strpos($_SERVER['PHP_SELF'], '/citas/') !== false ? 'active' : '' ?>">
            <i class="bi bi-calendar-check"></i> Citas
        </a>

        <span class="nav-section">Clínico</span>
        <a href="/sigvet/historias/" class="<?= strpos($_SERVER['PHP_SELF'], '/historias/') !== false ? 'active' : '' ?>">
            <i class="bi bi-journal-medical"></i> Historias clínicas
        </a>
        <a href="/sigvet/vacunas/" class="<?= strpos($_SERVER['PHP_SELF'], '/vacunas/') !== false ? 'active' : '' ?>">
            <i class="bi bi-droplet-fill"></i> Vacunación
        </a>
        <a href="/sigvet/desparasitacion/" class="<?= strpos($_SERVER['PHP_SELF'], '/desparasitacion/') !== false ? 'active' : '' ?>">
            <i class="bi bi-shield-plus"></i> Desparasitación
        </a>
        <a href="/sigvet/tratamientos/" class="<?= strpos($_SERVER['PHP_SELF'], '/tratamientos/') !== false ? 'active' : '' ?>">
            <i class="bi bi-capsule"></i> Tratamientos
        </a>
        <a href="/sigvet/laboratorio/" class="<?= strpos($_SERVER['PHP_SELF'], '/laboratorio/') !== false ? 'active' : '' ?>">
            <i class="bi bi-eyedropper"></i> Laboratorio clínico
        </a>

        <span class="nav-section">Reportes</span>
        <a href="/sigvet/reportes/" class="<?= strpos($_SERVER['PHP_SELF'], '/reportes/') !== false ? 'active' : '' ?>">
            <i class="bi bi-bar-chart-line"></i> Reportes
        </a>

        <?php if (tieneRol('administrador')): ?>
        <span class="nav-section">Administración</span>
        <a href="/sigvet/usuarios/" class="<?= strpos($_SERVER['PHP_SELF'], '/usuarios/') !== false ? 'active' : '' ?>">
            <i class="bi bi-people"></i> Usuarios
        </a>
        <?php endif; ?>
    </nav>
    <div class="sidebar-footer">
        <div class="d-flex align-items-center gap-2">
            <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center text-white" style="width:36px;height:36px;font-size:.85rem">
                <?= strtoupper(substr($_SESSION['nombres'] ?? 'U', 0, 1)) ?>
            </div>
            <div class="flex-1 lh-sm" style="max-width:140px">
                <div class="text-white text-truncate" style="font-size:.82rem"><?= limpiar($nombreUsuario) ?></div>
                <small class="text-muted text-capitalize"><?= limpiar($rolUsuario) ?></small>
            </div>
            <a href="/sigvet/logout.php" class="text-muted ms-auto" title="Cerrar sesión"><i class="bi bi-box-arrow-right"></i></a>
        </div>
    </div>
</div>

<!-- Main -->
<div id="main">
    <div class="topbar">
        <button class="btn btn-sm btn-outline-secondary d-lg-none" onclick="document.getElementById('sidebar').classList.toggle('show')">
            <i class="bi bi-list"></i>
        </button>
        <h6 class="mb-0 fw-semibold"><?= limpiar($paginaTitulo) ?></h6>
        <div class="d-flex align-items-center gap-3">
            <span class="badge bg-primary-subtle text-primary text-capitalize"><?= limpiar($rolUsuario) ?></span>
            <a href="/sigvet/logout.php" class="btn btn-sm btn-outline-danger"><i class="bi bi-box-arrow-right me-1"></i>Salir</a>
        </div>
    </div>
    <div class="page-content">
