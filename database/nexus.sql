CREATE DATABASE IF NOT EXISTS nexus
  CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE nexus;

CREATE TABLE IF NOT EXISTS usuarios (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(100) NOT NULL,
  correo VARCHAR(150) NOT NULL,
  password VARCHAR(255) NOT NULL,
  rol ENUM('admin','profesor','alumno') NOT NULL DEFAULT 'alumno',
  activo TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_usuarios_correo (correo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS profesores (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  usuario_id INT UNSIGNED DEFAULT NULL,
  nombre VARCHAR(100) NOT NULL,
  apellidos VARCHAR(100) NOT NULL,
  especialidad VARCHAR(100) DEFAULT NULL,
  telefono VARCHAR(20) DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_profesores_usuario (usuario_id),
  CONSTRAINT fk_profesores_usuario FOREIGN KEY (usuario_id)
    REFERENCES usuarios (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS materias (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  clave VARCHAR(20) NOT NULL,
  nombre VARCHAR(100) NOT NULL,
  descripcion TEXT,
  PRIMARY KEY (id),
  UNIQUE KEY uk_materias_clave (clave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS grupos (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(50) NOT NULL,
  grado TINYINT UNSIGNED NOT NULL,
  ciclo_escolar VARCHAR(20) NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS alumnos (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  usuario_id INT UNSIGNED DEFAULT NULL,
  grupo_id INT UNSIGNED DEFAULT NULL,
  matricula VARCHAR(30) NOT NULL,
  nombre VARCHAR(100) NOT NULL,
  apellidos VARCHAR(100) NOT NULL,
  fecha_nacimiento DATE DEFAULT NULL,
  telefono VARCHAR(20) DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_alumnos_matricula (matricula),
  UNIQUE KEY uk_alumnos_usuario (usuario_id),
  CONSTRAINT fk_alumnos_usuario FOREIGN KEY (usuario_id)
    REFERENCES usuarios (id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_alumnos_grupo FOREIGN KEY (grupo_id)
    REFERENCES grupos (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS grupo_materias (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  grupo_id INT UNSIGNED NOT NULL,
  materia_id INT UNSIGNED NOT NULL,
  profesor_id INT UNSIGNED DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_grupo_materia (grupo_id, materia_id),
  CONSTRAINT fk_gm_grupo FOREIGN KEY (grupo_id)
    REFERENCES grupos (id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_gm_materia FOREIGN KEY (materia_id)
    REFERENCES materias (id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_gm_profesor FOREIGN KEY (profesor_id)
    REFERENCES profesores (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS calificaciones (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  alumno_id INT UNSIGNED NOT NULL,
  grupo_materia_id INT UNSIGNED NOT NULL,
  parcial TINYINT UNSIGNED NOT NULL DEFAULT 1,
  calificacion DECIMAL(4,2) NOT NULL DEFAULT 0.00,
  observaciones VARCHAR(255) DEFAULT NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_calificacion (alumno_id, grupo_materia_id, parcial),
  CONSTRAINT fk_cal_alumno FOREIGN KEY (alumno_id)
    REFERENCES alumnos (id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_cal_gm FOREIGN KEY (grupo_materia_id)
    REFERENCES grupo_materias (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------
-- Administrador por defecto: admin@escuela.com / admin123
-- (hash bcrypt generado para PHP password_verify)
-- ---------------------------------------------------------
INSERT IGNORE INTO usuarios (nombre, correo, password, rol, activo)
VALUES ('Administrador', 'admin@escuela.com',
        '$2y$12$548JXUAwxHDmulk8sRyeseEGtqFHr8UdUXa65gffasXgdgz.7R6hi',
        'admin', 1);
