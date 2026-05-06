<?php
require_once __DIR__ . '/../../Shared/Infrastructure/Database/Connection.php';

// Crear tabla si no existe
$sql = "
CREATE TABLE IF NOT EXISTS cumplimiento_tequio (
    id_cumplimiento SERIAL PRIMARY KEY,
    id_tequio INTEGER NOT NULL,
    id_comunero INTEGER NOT NULL,
    cumplio BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(id_tequio, id_comunero),
    FOREIGN KEY (id_tequio) REFERENCES tequio(id_tequio) ON DELETE CASCADE,
    FOREIGN KEY (id_comunero) REFERENCES comunero(id_comunero) ON DELETE CASCADE
);

-- Crear índices para mejorar rendimiento
CREATE INDEX IF NOT EXISTS idx_cumplimiento_tequio ON cumplimiento_tequio(id_tequio);
CREATE INDEX IF NOT EXISTS idx_cumplimiento_comunero ON cumplimiento_tequio(id_comunero);
CREATE INDEX IF NOT EXISTS idx_cumplimiento_cumplio ON cumplimiento_tequio(cumplio);
";

$result = pg_query($conexion, $sql);
if ($result) {
    echo "✓ Tabla cumplimiento_tequio verificada/creada correctamente\n";
} else {
    echo "✗ Error: " . pg_last_error($conexion) . "\n";
}
?>
