<?php
// Script mejorado para crear/verificar tablas de usuario
require_once __DIR__ . '/../db/conexion.php';

echo "Verificando y creando tablas necesarias...\n\n";

// 1. Verificar y crear tabla rol si no existe
echo "1. Verificando tabla 'rol'...\n";
$check_rol = pg_query($conexion, "SELECT to_regclass('rol');");
$rol_exists = pg_fetch_result($check_rol, 0, 0) !== null;

if (!$rol_exists) {
    echo "   - Creando tabla rol...\n";
    $sql_rol = "CREATE TABLE rol (
        id_rol SERIAL PRIMARY KEY,
        nombre VARCHAR(50) NOT NULL UNIQUE,
        descripcion TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );";
    pg_query($conexion, $sql_rol);
    
    echo "   - Insertando roles base...\n";
    pg_query_params($conexion, "INSERT INTO rol (nombre, descripcion) VALUES ($1, $2)", 
        ['Admin', 'Administrador del sistema - acceso total']);
    pg_query_params($conexion, "INSERT INTO rol (nombre, descripcion) VALUES ($1, $2)", 
        ['Operador', 'Operador de datos - puede crear y editar registros']);
    pg_query_params($conexion, "INSERT INTO rol (nombre, descripcion) VALUES ($1, $2)", 
        ['Consultor', 'Consultor - solo lectura']);
    echo "   ✓ Tabla rol creada\n\n";
} else {
    echo "   ✓ Tabla rol ya existe\n\n";
}

// 2. Verificar y crear tabla usuario si no existe
echo "2. Verificando tabla 'usuario'...\n";
$check_usuario = pg_query($conexion, "SELECT to_regclass('usuario');");
$usuario_exists = pg_fetch_result($check_usuario, 0, 0) !== null;

if (!$usuario_exists) {
    echo "   - Creando tabla usuario...\n";
    $sql_usuario = "CREATE TABLE usuario (
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
    );";
    pg_query($conexion, $sql_usuario);
    
    echo "   - Creando índices...\n";
    pg_query($conexion, "CREATE INDEX idx_usuario_nombre ON usuario(nombre_usuario);");
    pg_query($conexion, "CREATE INDEX idx_usuario_email ON usuario(email);");
    pg_query($conexion, "CREATE INDEX idx_usuario_rol ON usuario(id_rol);");
    
    echo "   ✓ Tabla usuario creada\n\n";
} else {
    echo "   ✓ Tabla usuario ya existe\n\n";
}

echo "✓ Verificación completada\n";

// Verificar si existe el usuario admin
$check_admin = pg_query($conexion, "SELECT COUNT(*) FROM usuario WHERE nombre_usuario = 'admin';");
$admin_count = (int)pg_fetch_result($check_admin, 0, 0);

if ($admin_count == 0) {
    echo "3. Creando usuario admin por defecto...\n";
    $password_hash = password_hash('admin123', PASSWORD_DEFAULT);
    pg_query_params($conexion, 
        "INSERT INTO usuario (nombre_usuario, nombre_completo, email, contrasena, id_rol, activo) 
         VALUES ($1, $2, $3, $4, $5, $6)",
        ['admin', 'Administrador del Sistema', 'admin@syscomunal.local', $password_hash, 1, true]);
    echo "   ✓ Usuario admin creado (contraseña: admin123)\n\n";
} else {
    echo "3. Usuario admin ya existe\n\n";
}

?>
