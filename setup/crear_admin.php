<?php
require_once __DIR__ . '/../db/conexion.php';

// Crear usuario admin si no existe
$check = pg_query($conexion, "SELECT COUNT(*) FROM usuario WHERE email = 'admin@test.local';");
$count = (int)pg_fetch_result($check, 0, 0);

if ($count == 0) {
    $password_hash = password_hash('admin123', PASSWORD_DEFAULT);
    $result = pg_query_params(
        $conexion,
        "INSERT INTO usuario (nombre, email, password_hash, id_rol, activo) VALUES ($1, $2, $3, $4, $5)",
        ['Administrador', 'admin@test.local', $password_hash, 1, true]
    );
    
    if ($result) {
        echo "✓ Usuario admin creado exitosamente\n";
        echo "  Email: admin@test.local\n";
        echo "  Contraseña: admin123\n";
    } else {
        echo "Error: " . pg_last_error($conexion);
    }
} else {
    echo "El usuario admin ya existe\n";
}

// Mostrar usuarios
echo "\nUsuarios existentes:\n\n";
$result = pg_query($conexion, "SELECT id_usuario, nombre, email, activo FROM usuario;");
while ($row = pg_fetch_assoc($result)) {
    echo "ID: " . $row['id_usuario'] . " | Nombre: " . $row['nombre'] . " | Email: " . $row['email'] . " | Activo: " . ($row['activo'] ? 'Sí' : 'No') . "\n";
}

?>
