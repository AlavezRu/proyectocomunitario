<?php
require_once __DIR__ . '/../../Shared/Infrastructure/Database/Connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_asamblea = (int)$_POST['id_asamblea'];
    $asistencias = isset($_POST['asistencia']) ? $_POST['asistencia'] : [];

    if ($id_asamblea > 0) {
        // Crear tabla si no existe
        $create_table = "
        CREATE TABLE IF NOT EXISTS asistencia_asamblea (
            id_asistencia SERIAL PRIMARY KEY,
            id_asamblea INTEGER NOT NULL,
            id_comunero INTEGER NOT NULL,
            asistio BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(id_asamblea, id_comunero),
            FOREIGN KEY (id_asamblea) REFERENCES asamblea(id_asamblea) ON DELETE CASCADE,
            FOREIGN KEY (id_comunero) REFERENCES comunero(id_comunero) ON DELETE CASCADE
        );
        ";
        pg_query($conexion, $create_table);

        pg_query($conexion, "BEGIN");

        try {
            $q_comuneros = pg_query($conexion, "SELECT id_comunero FROM comunero WHERE activo = TRUE");
            
            while ($row = pg_fetch_assoc($q_comuneros)) {
                $id_comunero = $row['id_comunero'];
                // Convertir a booleano PostgreSQL: 'true' o 'false'
                $asistio = isset($asistencias[$id_comunero]) ? 'true' : 'false';

                $sql = "INSERT INTO asistencia_asamblea (id_asamblea, id_comunero, asistio) 
                        VALUES ($1, $2, $3::boolean)
                        ON CONFLICT (id_asamblea, id_comunero)
                        DO UPDATE SET asistio = $3::boolean";

                $result = pg_query_params($conexion, $sql, [$id_asamblea, $id_comunero, $asistio]);
                if (!$result) {
                    throw new Exception(pg_last_error($conexion));
                }
            }

            pg_query($conexion, "COMMIT");
            header("Location: /proyectocomunitario/public/index.php?page=asambleas&msg=list_saved");
            exit;

        } catch (Exception $e) {
            pg_query($conexion, "ROLLBACK");
            die("Error guardando pase de lista: " . $e->getMessage());
        }
    }
}
header("Location: /proyectocomunitario/public/index.php?page=asambleas");
exit;
?>
