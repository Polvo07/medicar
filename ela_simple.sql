CREATE DATABASE IF NOT EXISTS ela_simple CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE ela_simple;

CREATE TABLE IF NOT EXISTS pacientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    cedula VARCHAR(50) UNIQUE NOT NULL,
    fecha_nacimiento DATE,
    fecha_diagnostico DATE
);

CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    contrasena VARCHAR(255) NOT NULL,
    role ENUM('paciente', 'medico') NOT NULL,
    paciente_id INT NULL,
    FOREIGN KEY (paciente_id) REFERENCES pacientes(id)
);

CREATE TABLE IF NOT EXISTS farmacos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100),
    presentacion VARCHAR(100),
    descripcion TEXT
);

CREATE TABLE IF NOT EXISTS prescripciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    paciente_id INT,
    farmaco_id INT,
    dosis VARCHAR(100),
    frecuencia_horas INT,
    hora_inicio TIME,
    fecha_inicio DATE,
    duracion_dias INT NULL,
    instrucciones TEXT,
    activo BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (paciente_id) REFERENCES pacientes(id),
    FOREIGN KEY (farmaco_id) REFERENCES farmacos(id)
);

CREATE TABLE IF NOT EXISTS registro_tomas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    prescripcion_id INT,
    fecha_hora_programada DATETIME,
    fecha_hora_real DATETIME NULL,
    tomado BOOLEAN DEFAULT FALSE,
    notas TEXT,
    FOREIGN KEY (prescripcion_id) REFERENCES prescripciones(id)
);

CREATE TABLE IF NOT EXISTS evaluaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    paciente_id INT,
    fecha DATE,
    fuerza_muscular INT,
    capacidad_caminar INT,
    capacidad_hablar INT,
    notas TEXT,
    FOREIGN KEY (paciente_id) REFERENCES pacientes(id)
);

CREATE TABLE IF NOT EXISTS terapias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    paciente_id INT,
    tipo ENUM('fisioterapia', 'ocupacional', 'fonoaudiologia'),
    fecha DATE,
    hora TIME,
    notas TEXT,
    FOREIGN KEY (paciente_id) REFERENCES pacientes(id)
);

CREATE TABLE IF NOT EXISTS als_data (
    ID INT PRIMARY KEY,
    Sex VARCHAR(1),
    Age INT,
    J1_a FLOAT, J3_a FLOAT, J5_a FLOAT, J55_a FLOAT,
    S1_a FLOAT, S3_a FLOAT, S5_a FLOAT, S11_a FLOAT, S55_a FLOAT,
    DPF_a FLOAT, PFR_a FLOAT, PPE_a FLOAT, PVI_a FLOAT, HNR_a FLOAT,
    GNEa_mu FLOAT, GNEa_sigma FLOAT,
    Ha_1_mu FLOAT, Ha_2_mu FLOAT, Ha_3_mu FLOAT, Ha_4_mu FLOAT, Ha_5_mu FLOAT, Ha_6_mu FLOAT, Ha_7_mu FLOAT, Ha_8_mu FLOAT,
    Ha_1_sd FLOAT, Ha_2_sd FLOAT, Ha_3_sd FLOAT, Ha_4_sd FLOAT, Ha_5_sd FLOAT, Ha_6_sd FLOAT, Ha_7_sd FLOAT, Ha_8_sd FLOAT,
    Ha_1_rel FLOAT, Ha_2_rel FLOAT, Ha_3_rel FLOAT, Ha_4_rel FLOAT, Ha_5_rel FLOAT, Ha_6_rel FLOAT, Ha_7_rel FLOAT, Ha_8_rel FLOAT,
    CCa_1 FLOAT, CCa_2 FLOAT, CCa_3 FLOAT, CCa_4 FLOAT, CCa_5 FLOAT, CCa_6 FLOAT, CCa_7 FLOAT, CCa_8 FLOAT, CCa_9 FLOAT, CCa_10 FLOAT, CCa_11 FLOAT, CCa_12 FLOAT,
    dCCa_1 FLOAT, dCCa_2 FLOAT, dCCa_3 FLOAT, dCCa_4 FLOAT, dCCa_5 FLOAT, dCCa_6 FLOAT, dCCa_7 FLOAT, dCCa_8 FLOAT, dCCa_9 FLOAT, dCCa_10 FLOAT, dCCa_11 FLOAT, dCCa_12 FLOAT,
    J1_i FLOAT, J3_i FLOAT, J5_i FLOAT, J55_i FLOAT,
    S1_i FLOAT, S3_i FLOAT, S5_i FLOAT, S11_i FLOAT, S55_i FLOAT,
    DPF_i FLOAT, PFR_i FLOAT, PPE_i FLOAT, PVI_i FLOAT, HNR_i FLOAT,
    GNEi_mu FLOAT, GNEi_sigma FLOAT,
    Hi_1_mu FLOAT, Hi_2_mu FLOAT, Hi_3_mu FLOAT, Hi_4_mu FLOAT, Hi_5_mu FLOAT, Hi_6_mu FLOAT, Hi_7_mu FLOAT, Hi_8_mu FLOAT,
    Hi_1_sd FLOAT, Hi_2_sd FLOAT, Hi_3_sd FLOAT, Hi_4_sd FLOAT, Hi_5_sd FLOAT, Hi_6_sd FLOAT, Hi_7_sd FLOAT, Hi_8_sd FLOAT,
    Hi_1_rel FLOAT, Hi_2_rel FLOAT, Hi_3_rel FLOAT, Hi_4_rel FLOAT, Hi_5_rel FLOAT, Hi_6_rel FLOAT, Hi_7_rel FLOAT, Hi_8_rel FLOAT,
    CCi_1 FLOAT, CCi_2 FLOAT, CCi_3 FLOAT, CCi_4 FLOAT, CCi_5 FLOAT, CCi_6 FLOAT, CCi_7 FLOAT, CCi_8 FLOAT, CCi_9 FLOAT, CCi_10 FLOAT, CCi_11 FLOAT, CCi_12 FLOAT,
    dCCi_1 FLOAT, dCCi_2 FLOAT, dCCi_3 FLOAT, dCCi_4 FLOAT, dCCi_5 FLOAT, dCCi_6 FLOAT, dCCi_7 FLOAT, dCCi_8 FLOAT, dCCi_9 FLOAT, dCCi_10 FLOAT, dCCi_11 FLOAT, dCCi_12 FLOAT,
    d_1 FLOAT, F2_i FLOAT, F2_conv FLOAT
);

-- Sample data
INSERT IGNORE INTO pacientes (nombre, apellidos, cedula, fecha_nacimiento, fecha_diagnostico) VALUES 
('Juan Carlos', 'Rodríguez López', '11', '1967-10-01', '2025-06-20');

-- Contraseña de demo para ambos usuarios: Medicar2026!  (cámbiala antes de usar en producción)
INSERT IGNORE INTO usuarios (username, contrasena, role, paciente_id) VALUES
('medico1', '$2y$10$pOG8zEWAaSt6Boitt48l3ev/A5TgPpXx0oi4LZfZFCPmsjwwvoKAi', 'medico', NULL),
('11', '$2y$10$pOG8zEWAaSt6Boitt48l3ev/A5TgPpXx0oi4LZfZFCPmsjwwvoKAi', 'paciente', 1);

INSERT IGNORE INTO farmacos (nombre, presentacion, descripcion) VALUES 
('Riluzole', '50 mg tabletas', 'Medicamento para ELA, reduce progresión.'),
('Edaravone', 'IV 60 mg', 'Antioxidante para ELA.');

INSERT IGNORE INTO prescripciones (paciente_id, farmaco_id, dosis, frecuencia_horas, hora_inicio, fecha_inicio, duracion_dias, instrucciones) VALUES 
(1, 1, '50 mg cada 12 horas', 12, '08:00:00', '2025-10-01', 30, 'Tomar con agua.');

INSERT IGNORE INTO registro_tomas (prescripcion_id, fecha_hora_programada, tomado) VALUES 
(1, '2025-10-01 08:00:00', FALSE);

INSERT IGNORE INTO evaluaciones (paciente_id, fecha, fuerza_muscular, capacidad_caminar, capacidad_hablar, notas) VALUES 
(1, '2025-10-01', 3, 2, 3, 'Paciente muestra leve mejoría en fuerza.');

INSERT IGNORE INTO terapias (paciente_id, tipo, fecha, hora, notas) VALUES 
(1, 'fonoaudiologia', '2025-10-02', '10:00:00', 'Sesión para mejorar articulación.');