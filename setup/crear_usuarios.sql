-- Crear tabla de roles
CREATE TABLE IF NOT EXISTS rol (
    id_rol SERIAL PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    descripcion TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Crear tabla de usuarios
CREATE TABLE IF NOT EXISTS usuario (
    id_usuario SERIAL PRIMARY KEY,
    nombre_usuario VARCHAR(50) NOT NULL UNIQUE,
    nombre_completo VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE,
    contrasena VARCHAR(255) NOT NULL,
    id_rol INTEGER NOT NULL REFERENCES rol(id_rol),
    activo BOOLEAN DEFAULT TRUE,
    ultimo_login TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insertar roles base
INSERT INTO rol (nombre, descripcion) VALUES 
    ('Admin', 'Administrador del sistema - acceso total'),
    ('Operador', 'Operador de datos - puede crear y editar registros'),
    ('Consultor', 'Consultor - solo lectura');

-- Crear usuario admin inicial (contraseña: admin123)
-- Usar: password_hash('admin123', PASSWORD_BCRYPT) en PHP o generado aquí
INSERT INTO usuario (nombre_usuario, nombre_completo, email, contrasena, id_rol, activo) VALUES
    ('admin', 'Administrador del Sistema', 'admin@syscomunal.local', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86E36P4/TVm', 1, TRUE);

-- Nota: La contraseña de arriba es el hash de 'password' (para pruebas)
-- Para cambiarla, use: UPDATE usuario SET contrasena = '[nuevo_hash]' WHERE id_usuario = 1;

CREATE INDEX idx_usuario_nombre ON usuario(nombre_usuario);
CREATE INDEX idx_usuario_email ON usuario(email);
CREATE INDEX idx_usuario_rol ON usuario(id_rol);
