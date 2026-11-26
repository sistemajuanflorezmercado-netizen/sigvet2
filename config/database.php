<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sigvet');
define('APP_NAME', 'SIGVET');
define('APP_VERSION', '1.0');
define('UPLOAD_DIR', __DIR__ . '/../pacientes/uploads/');
define('UPLOAD_URL', '/sigvet/pacientes/uploads');
define('LAB_UPLOAD_DIR', __DIR__ . '/../laboratorio/uploads/');
define('LAB_UPLOAD_URL', '/sigvet/laboratorio/uploads/');

function conectar() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die('<div class="alert alert-danger m-3">Error de conexión a la base de datos: ' . $conn->connect_error . '</div>');
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}
