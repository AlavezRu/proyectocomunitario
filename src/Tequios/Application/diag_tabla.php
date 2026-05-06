<?php
require_once __DIR__ . '/../../Shared/Infrastructure/Database/Connection.php';

// Verificar estructura de la tabla
$resultado = pg_query($conexion, "
    SELECT column_name, data_type, is_nullable 
    FROM information_schema.columns 
    WHERE table_name = 'cumplimiento_tequio'
");

echo "<pre>";
echo "Estructura de cumplimiento_tequio:\n";
echo "=====================================\n";
while ($row = pg_fetch_assoc($resultado)) {
    echo "Columna: {$row['column_name']} | Tipo: {$row['data_type']} | Nullable: {$row['is_nullable']}\n";
}

// Verificar algunos datos
echo "\nDatos en cumplimiento_tequio:\n";
echo "=====================================\n";
$datos = pg_query($conexion, "SELECT * FROM cumplimiento_tequio LIMIT 10");
while ($row = pg_fetch_assoc($datos)) {
    echo "ID Tequio: {$row['id_tequio']} | ID Comunero: {$row['id_comunero']} | Cumplio: {$row['cumplio']} (tipo: " . gettype($row['cumplio']) . ")\n";
}

// Verificar constraint
echo "\nConstraints:\n";
echo "=====================================\n";
$constraints = pg_query($conexion, "
    SELECT constraint_name, constraint_type 
    FROM information_schema.table_constraints 
    WHERE table_name = 'cumplimiento_tequio'
");
while ($row = pg_fetch_assoc($constraints)) {
    echo "Constraint: {$row['constraint_name']} | Tipo: {$row['constraint_type']}\n";
}

echo "</pre>";
?>
