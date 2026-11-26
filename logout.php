<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';
if (isset($_SESSION['usuario_id'])) {
    $conn = conectar();
    registrarLog($conn, 'Cierre de sesión');
    $conn->close();
}
session_destroy();
header('Location: /sigvet/index.php');
exit;
