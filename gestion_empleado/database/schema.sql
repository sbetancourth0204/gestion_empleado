-- ============================================================
-- SISTEMA DE GESTIÓN DE EMPLEADOS
-- Compatible con XAMPP / MySQL 5.7+
-- ============================================================

CREATE DATABASE IF NOT EXISTS gestion_empleados CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gestion_empleados;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS notificaciones;
DROP TABLE IF EXISTS vacaciones;
DROP TABLE IF EXISTS empleados;
SET FOREIGN_KEY_CHECKS = 1;

-- Tabla principal de empleados
CREATE TABLE empleados (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    nombre         VARCHAR(100)  NOT NULL,
    tipo           ENUM('tiempo_completo','tiempo_parcial','contratista') NOT NULL DEFAULT 'tiempo_completo',
    cargo          VARCHAR(100)  NOT NULL,
    salario_base   DECIMAL(10,2) NOT NULL,
    horas_semana   INT           NOT NULL DEFAULT 40,
    fecha_ingreso  DATE          NOT NULL,
    email          VARCHAR(150)  NOT NULL DEFAULT '',
    activo         TINYINT(1)    DEFAULT 1,
    created_at     TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
);

-- Vacaciones
CREATE TABLE vacaciones (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    empleado_id   INT  NOT NULL,
    fecha_inicio  DATE NOT NULL,
    fecha_fin     DATE NOT NULL,
    dias          INT  NOT NULL,
    estado        ENUM('pendiente','aprobada','rechazada') DEFAULT 'pendiente',
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (empleado_id) REFERENCES empleados(id) ON DELETE CASCADE
);

-- Notificaciones / log de eventos
CREATE TABLE notificaciones (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    empleado_id  INT  NULL,
    mensaje      TEXT NOT NULL,
    tipo         VARCHAR(50)  DEFAULT 'info',
    leida        TINYINT(1)   DEFAULT 0,
    created_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (empleado_id) REFERENCES empleados(id) ON DELETE SET NULL
);

-- Datos de prueba
INSERT INTO empleados (nombre, tipo, cargo, salario_base, horas_semana, fecha_ingreso, email) VALUES
('Ana García',     'tiempo_completo', 'Gerente General',     5000.00, 40, '2022-01-15', 'ana@empresa.com'),
('Luis Pérez',     'tiempo_parcial',  'Asistente Contable',  2000.00, 20, '2023-03-10', 'luis@empresa.com'),
('María López',    'contratista',     'Desarrolladora Senior',  55.00, 40, '2024-01-01', 'maria@empresa.com'),
('Carlos Ruiz',    'tiempo_completo', 'Diseñador UX',        3500.00, 40, '2021-07-20', 'carlos@empresa.com'),
('Sofía Martínez', 'tiempo_parcial',  'Marketing Digital',   1800.00, 25, '2023-09-01', 'sofia@empresa.com');

INSERT INTO vacaciones (empleado_id, fecha_inicio, fecha_fin, dias, estado) VALUES
(1, '2025-07-01', '2025-07-15', 14, 'aprobada'),
(4, '2025-08-05', '2025-08-12', 7,  'pendiente');

INSERT INTO notificaciones (empleado_id, mensaje, tipo) VALUES
(1, '✅ Empleado Ana García registrado como tiempo_completo', 'registro'),
(1, '✔️ Vacaciones APROBADAS para Ana García',              'vacaciones');
