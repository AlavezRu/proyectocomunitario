<?php
require_once __DIR__ . '/../../Shared/Infrastructure/Database/Connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_tequio = (int)$_POST['id_tequio'];
    $asistencias = isset($_POST['asistencia']) ? $_POST['asistencia'] : [];

    if ($id_tequio > 0) {
        // Crear tabla si no existe
        $create_table = "
        CREATE TABLE IF NOT EXISTS cumplimiento_tequio (
            id_cumplimiento SERIAL PRIMARY KEY,
            id_tequio INTEGER NOT NULL,
            id_comunero INTEGER NOT NULL,
            cumplio BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(id_tequio, id_comunero),
            FOREIGN KEY (id_tequio) REFERENCES tequio(id_tequio) ON DELETE CASCADE,
            FOREIGN KEY (id_comunero) REFERENCES comunero(id_comunero) ON DELETE CASCADE
        );
        ";
        pg_query($conexion, $create_table);

        pg_query($conexion, "BEGIN");

        try {
            // Obtener todos los comuneros activos
            $q_comuneros = pg_query($conexion, "SELECT id_comunero FROM comunero WHERE activo = TRUE");
            
            while ($row = pg_fetch_assoc($q_comuneros)) {
                $id_comunero = $row['id_comunero'];
                // Convertir a booleano PostgreSQL: 'true' o 'false'
                $cumplio = isset($asistencias[$id_comunero]) ? 'true' : 'false';

                // Usar ON CONFLICT para insertar o actualizar según corresponda
                $sql = "INSERT INTO cumplimiento_tequio (id_tequio, id_comunero, cumplio) 
                        VALUES ($1, $2, $3::boolean)
                        ON CONFLICT (id_tequio, id_comunero)
                        DO UPDATE SET cumplio = $3::boolean";

                $result = pg_query_params($conexion, $sql, [$id_tequio, $id_comunero, $cumplio]);
                if (!$result) {
                    throw new Exception(pg_last_error($conexion));
                }
            }

            pg_query($conexion, "COMMIT");
            header("Location: /proyectocomunitario/public/index.php?page=tequios&msg=list_saved");
            exit;

        } catch (Exception $e) {
            pg_query($conexion, "ROLLBACK");
            die("Error guardando pase de lista: " . $e->getMessage());
        }
    }
}
header("Location: /proyectocomunitario/public/index.php?page=tequios");
exit;
?>
