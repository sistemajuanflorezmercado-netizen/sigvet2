-- =============================================
-- SIGVET – Sistema Integral de Gestión Veterinaria
-- Base de datos MySQL para XAMPP
-- =============================================

CREATE DATABASE IF NOT EXISTS sigvet CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sigvet;

-- Roles
CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    descripcion TEXT
);

INSERT INTO roles (nombre, descripcion) VALUES
('administrador', 'Acceso completo al sistema'),
('veterinario', 'Gestión clínica y pacientes'),
('auxiliar', 'Registro de pacientes, propietarios y citas'),
('recepcionista', 'Gestión administrativa y citas');

-- Usuarios
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombres VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    username VARCHAR(80) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    rol_id INT NOT NULL DEFAULT 4,
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rol_id) REFERENCES roles(id)
);

-- Contraseña: Admin123! (hash bcrypt)
INSERT INTO usuarios (nombres, apellidos, email, username, password, rol_id) VALUES
('Admin', 'SIGVET', 'admin@sigvet.com', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1);

-- Especies
CREATE TABLE especies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(80) NOT NULL UNIQUE
);

INSERT INTO especies (nombre) VALUES ('Perro'), ('Gato'), ('Ave'), ('Conejo'), ('Reptil'), ('Roedor'), ('Otro');

-- Razas
CREATE TABLE razas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    especie_id INT NOT NULL,
    FOREIGN KEY (especie_id) REFERENCES especies(id)
);

INSERT INTO razas (nombre, especie_id) VALUES
('Labrador', 1), ('Pastor Alemán', 1), ('Bulldog', 1), ('Poodle', 1), ('Chihuahua', 1), ('Golden Retriever', 1), ('Beagle', 1), ('Mestizo', 1),
('Persa', 2), ('Siamés', 2), ('Angora', 2), ('Bengalí', 2), ('Mestizo', 2),
('Canario', 3), ('Loro', 3), ('Periquito', 3),
('Holandés', 4), ('Angora', 4),
('Iguana', 5), ('Gecko', 5),
('Hámster', 6), ('Cobaya', 6),
('Otro', 7);

-- Propietarios
CREATE TABLE propietarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombres VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    tipo_identificacion ENUM('CC','CE','NIT','PP','TI','Otro') DEFAULT 'CC',
    numero_identificacion VARCHAR(30),
    telefono VARCHAR(20),
    email VARCHAR(150),
    direccion TEXT,
    ciudad VARCHAR(100),
    observaciones TEXT,
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Pacientes
CREATE TABLE pacientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    especie_id INT NOT NULL,
    raza_id INT,
    sexo ENUM('Macho','Hembra','Desconocido') DEFAULT 'Desconocido',
    fecha_nacimiento DATE,
    color VARCHAR(80),
    peso DECIMAL(5,2),
    estado_reproductivo ENUM('Entero','Castrado','Esterilizado','Desconocido') DEFAULT 'Desconocido',
    microchip VARCHAR(50),
    foto VARCHAR(255),
    propietario_id INT NOT NULL,
    observaciones TEXT,
    activo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (especie_id) REFERENCES especies(id),
    FOREIGN KEY (raza_id) REFERENCES razas(id),
    FOREIGN KEY (propietario_id) REFERENCES propietarios(id)
);

-- Citas
CREATE TABLE citas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    paciente_id INT NOT NULL,
    propietario_id INT NOT NULL,
    veterinario_id INT,
    fecha_hora DATETIME NOT NULL,
    motivo VARCHAR(255),
    estado ENUM('programada','confirmada','atendida','cancelada','no_asistio') DEFAULT 'programada',
    observaciones TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (paciente_id) REFERENCES pacientes(id),
    FOREIGN KEY (propietario_id) REFERENCES propietarios(id),
    FOREIGN KEY (veterinario_id) REFERENCES usuarios(id),
    FOREIGN KEY (created_by) REFERENCES usuarios(id)
);

-- Historias clínicas
CREATE TABLE historias_clinicas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    paciente_id INT NOT NULL,
    veterinario_id INT NOT NULL,
    cita_id INT,
    fecha_hora DATETIME DEFAULT CURRENT_TIMESTAMP,
    motivo TEXT NOT NULL,
    antecedentes TEXT,
    sintomas TEXT,
    peso DECIMAL(5,2),
    temperatura DECIMAL(4,1),
    frecuencia_cardiaca INT,
    frecuencia_respiratoria INT,
    tllc VARCHAR(20),
    examen_fisico TEXT,
    diagnostico TEXT,
    tratamiento TEXT,
    medicamentos TEXT,
    recomendaciones TEXT,
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (paciente_id) REFERENCES pacientes(id),
    FOREIGN KEY (veterinario_id) REFERENCES usuarios(id),
    FOREIGN KEY (cita_id) REFERENCES citas(id)
);

-- Vacunas
CREATE TABLE vacunas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    paciente_id INT NOT NULL,
    tipo_vacuna VARCHAR(150) NOT NULL,
    fecha_aplicacion DATE NOT NULL,
    proxima_dosis DATE,
    lote VARCHAR(80),
    veterinario_id INT,
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (paciente_id) REFERENCES pacientes(id),
    FOREIGN KEY (veterinario_id) REFERENCES usuarios(id)
);

-- Desparasitaciones
CREATE TABLE desparasitaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    paciente_id INT NOT NULL,
    tipo ENUM('interna','externa','ambas') DEFAULT 'interna',
    producto VARCHAR(150) NOT NULL,
    fecha DATE NOT NULL,
    dosis VARCHAR(100),
    proxima_fecha DATE,
    veterinario_id INT,
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (paciente_id) REFERENCES pacientes(id),
    FOREIGN KEY (veterinario_id) REFERENCES usuarios(id)
);

-- Tratamientos
CREATE TABLE tratamientos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    historia_id INT,
    paciente_id INT NOT NULL,
    medicamento VARCHAR(150) NOT NULL,
    presentacion VARCHAR(100),
    dosis VARCHAR(100),
    frecuencia VARCHAR(100),
    duracion VARCHAR(100),
    via_administracion VARCHAR(80),
    indicaciones TEXT,
    fecha_inicio DATE,
    fecha_fin DATE,
    veterinario_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (historia_id) REFERENCES historias_clinicas(id),
    FOREIGN KEY (paciente_id) REFERENCES pacientes(id),
    FOREIGN KEY (veterinario_id) REFERENCES usuarios(id)
);

-- Log de acciones
CREATE TABLE log_acciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT,
    accion VARCHAR(255) NOT NULL,
    tabla_afectada VARCHAR(80),
    registro_id INT,
    ip VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);
