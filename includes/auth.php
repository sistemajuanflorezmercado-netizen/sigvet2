<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function verificarSesion() {
    if (!isset($_SESSION['usuario_id'])) {
        header('Location: /sigvet/index.php');
        exit;
    }
}

function tieneRol(...$roles) {
    return isset($_SESSION['rol']) && in_array($_SESSION['rol'], $roles);
}

function soloAdmin() {
    if (!tieneRol('administrador')) {
        header('Location: /sigvet/dashboard.php?error=acceso');
        exit;
    }
}

function soloClinico() {
    if (!tieneRol('administrador', 'veterinario', 'auxiliar')) {
        header('Location: /sigvet/dashboard.php?error=acceso');
        exit;
    }
}

function registrarLog($conn, $accion, $tabla = null, $id = null) {
    $uid = $_SESSION['usuario_id'] ?? null;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $stmt = $conn->prepare("INSERT INTO log_acciones (usuario_id, accion, tabla_afectada, registro_id, ip) VALUES (?,?,?,?,?)");
    $stmt->bind_param('issis', $uid, $accion, $tabla, $id, $ip);
    $stmt->execute();
    $stmt->close();
}

function limpiar($dato) {
    return htmlspecialchars(strip_tags(trim($dato)), ENT_QUOTES, 'UTF-8');
}

function alerta($tipo, $mensaje) {
    $iconos = ['success' => 'check-circle', 'danger' => 'x-circle', 'warning' => 'exclamation-triangle', 'info' => 'info-circle'];
    $icono = $iconos[$tipo] ?? 'info-circle';
    return "<div class=\"alert alert-{$tipo} alert-dismissible fade show d-flex align-items-center gap-2\" role=\"alert\">
        <i class=\"bi bi-{$icono}-fill\"></i><span>{$mensaje}</span>
        <button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\"></button>
    </div>";
}
