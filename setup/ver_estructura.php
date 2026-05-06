<?php
require_once __DIR__ . '/../db/conexion.php';

echo "Estructura de la tabla usuario:\n\n";

$sql = "SELECT column_name, data_type, is_nullable 
        FROM information_schema.columns 
        WHERE table_name = 'usuario'
        ORDER BY ordinal_position;";

$result = pg_query($conexion, $sql);

if ($result) {
    while ($row = pg_fetch_assoc($result)) {
        echo $row['column_name'] . " | " . $row['data_type'] . " | Nullable: " . $row['is_nullable'] . "\n";
    }
} else {
    echo "Error: " . pg_last_error($conexion);
}

echo "\n\nEstructura de la tabla rol:\n\n";

$sql_rol = "SELECT column_name, data_type, is_nullable 
            FROM information_schema.columns 
            WHERE table_name = 'rol'
            ORDER BY ordinal_position;";

$result_rol = pg_query($conexion, $sql_rol);

if ($result_rol) {
    while ($row = pg_fetch_assoc($result_rol)) {
        echo $row['column_name'] . " | " . $row['data_type'] . " | Nullable: " . $row['is_nullable'] . "\n";
    }
} else {
    echo "Error: " . pg_last_error($conexion);
}

?>
