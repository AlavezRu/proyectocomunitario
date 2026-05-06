<?php
// setup/agregar_observaciones.php
// Script para agregar el campo de observación a las tablas de asambleas y tequios

require_once __DIR__ . '/../db/conexion.php';

echo "=== Agregando campo de observaciones ===\n\n";

// 1. Verificar y agregar columna observacion a la tabla asamblea
echo "1. Verificando tabla 'asamblea'...\n";
$check_asamblea = pg_query($conexion, "
    SELECT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_name='asamblea' AND column_name='observacion'
    ) as observacion_existe;
");
$result = pg_fetch_assoc($check_asamblea);

if ($result['observacion_existe'] === 't') {
    echo "   ✓ Campo 'observacion' ya existe en tabla asamblea\n\n";
} else {
    echo "   - Agregando campo 'observacion' a tabla asamblea...\n";
    $sql = "ALTER TABLE asamblea ADD COLUMN observacion TEXT DEFAULT NULL;";
    if (pg_query($conexion, $sql)) {
        echo "   ✓ Campo agregado correctamente\n\n";
    } else {
        echo "   ✗ Error al agregar campo: " . pg_last_error() . "\n\n";
    }
}

// 2. Verificar y agregar columna observacion a la tabla tequio
echo "2. Verificando tabla 'tequio'...\n";
$check_tequio = pg_query($conexion, "
    SELECT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_name='tequio' AND column_name='observacion'
    ) as observacion_existe;
");
$result = pg_fetch_assoc($check_tequio);

if ($result['observacion_existe'] === 't') {
    echo "   ✓ Campo 'observacion' ya existe en tabla tequio\n\n";
} else {
    echo "   - Agregando campo 'observacion' a tabla tequio...\n";
    $sql = "ALTER TABLE tequio ADD COLUMN observacion TEXT DEFAULT NULL;";
    if (pg_query($conexion, $sql)) {
        echo "   ✓ Campo agregado correctamente\n\n";
    } else {
        echo "   ✗ Error al agregar campo: " . pg_last_error() . "\n\n";
    }
}

echo "✓ Proceso completado\n";
echo "\nLos campos de observación han sido añadidos exitosamente.\n";
echo "Ahora puedes usar los nuevos campos en los formularios de asambleas y tequios.\n";
?>
