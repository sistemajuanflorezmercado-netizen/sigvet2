-- =============================================
-- SIGVET v2 – Módulo de Laboratorio Clínico
-- Ejecutar sobre la base de datos sigvet existente
-- =============================================

USE sigvet;

-- Tipos de examen de laboratorio
CREATE TABLE IF NOT EXISTS tipos_examen (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    descripcion TEXT,
    categoria ENUM('hematologia','bioquimica','urinalisis','coprologia','microbiologia','imagenologia','citologia','otro') DEFAULT 'otro'
);

INSERT INTO tipos_examen (nombre, categoria) VALUES
('Hemograma completo', 'hematologia'),
('Perfil bioquímico completo', 'bioquimica'),
('Perfil hepático', 'bioquimica'),
('Perfil renal', 'bioquimica'),
('Glucosa en sangre', 'bioquimica'),
('Uroanálisis completo', 'urinalisis'),
('Coproparasitológico', 'coprologia'),
('Cultivo y antibiograma', 'microbiologia'),
('Radiografía', 'imagenologia'),
('Ecografía abdominal', 'imagenologia'),
('Citología', 'citologia'),
('Frotis sanguíneo', 'hematologia'),
('Proteínas totales', 'bioquimica'),
('Tiempo de coagulación', 'hematologia');

-- Órdenes de laboratorio
CREATE TABLE IF NOT EXISTS ordenes_laboratorio (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(20) NOT NULL UNIQUE,
    paciente_id INT NOT NULL,
    veterinario_id INT NOT NULL,
    historia_id INT,
    fecha DATE NOT NULL DEFAULT (CURDATE()),
    prioridad ENUM('rutina','urgente','stat') DEFAULT 'rutina',
    diagnostico_presuntivo TEXT,
    observaciones TEXT,
    estado ENUM('pendiente','en_proceso','completada','cancelada') DEFAULT 'pendiente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (paciente_id) REFERENCES pacientes(id),
    FOREIGN KEY (veterinario_id) REFERENCES usuarios(id),
    FOREIGN KEY (historia_id) REFERENCES historias_clinicas(id)
);

-- Exámenes incluidos en cada orden
CREATE TABLE IF NOT EXISTS orden_examenes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    orden_id INT NOT NULL,
    tipo_examen_id INT NOT NULL,
    nombre_personalizado VARCHAR(150),
    indicaciones TEXT,
    FOREIGN KEY (orden_id) REFERENCES ordenes_laboratorio(id) ON DELETE CASCADE,
    FOREIGN KEY (tipo_examen_id) REFERENCES tipos_examen(id)
);

-- Resultados de laboratorio (archivos PDF adjuntos)
CREATE TABLE IF NOT EXISTS resultados_laboratorio (
    id INT AUTO_INCREMENT PRIMARY KEY,
    orden_id INT NOT NULL,
    nombre_archivo VARCHAR(255) NOT NULL,
    archivo_original VARCHAR(255) NOT NULL,
    fecha_resultado DATE NOT NULL,
    laboratorio VARCHAR(150),
    observaciones TEXT,
    uploaded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (orden_id) REFERENCES ordenes_laboratorio(id),
    FOREIGN KEY (uploaded_by) REFERENCES usuarios(id)
);

-- Modificar configuración para nuevas rutas de uploads
-- (la carpeta uploads/laboratorio/ se debe crear manualmente en XAMPP)
