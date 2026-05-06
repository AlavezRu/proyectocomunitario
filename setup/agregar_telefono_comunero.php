<?php
// setup/agregar_telefono_comunero.php
// Script para agregar el campo de teléfono a la tabla de comuneros

require_once __DIR__ . '/../db/conexion.php';

echo "=== Agregando campo de teléfono a comuneros ===\n\n";

// Verificar si la columna ya existe
echo "1. Verificando tabla 'comunero'...\n";
$check_telefono = pg_query($conexion, "
    SELECT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_name='comunero' AND column_name='telefono'
    ) as telefono_existe;
");
$result = pg_fetch_assoc($check_telefono);

if ($result['telefono_existe'] === 't') {
    echo "   ✓ Campo 'telefono' ya existe en tabla comunero\n\n";
} else {
    echo "   - Agregando campo 'telefono' a tabla comunero...\n";
    $sql = "ALTER TABLE comunero ADD COLUMN telefono VARCHAR(20) DEFAULT NULL;";
    if (pg_query($conexion, $sql)) {
        echo "   ✓ Campo agregado correctamente\n\n";
    } else {
        echo "   ✗ Error al agregar campo: " . pg_last_error() . "\n\n";
    }
}

echo "✓ Proceso completado\n";
echo "\nAhora puedes usar el nuevo campo de teléfono en el formulario de comuneros.\n";
?>
