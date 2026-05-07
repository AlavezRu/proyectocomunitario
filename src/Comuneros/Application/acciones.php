<?php
require_once __DIR__ . '/../../Shared/Infrastructure/Database/Connection.php';

$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';

if ($accion == 'nuevo' || $accion == 'editar') {
    // Recibir datos principales
    $id_comunero = isset($_POST['id_comunero']) ? (int)$_POST['id_comunero'] : 0;
    $numero_progresivo = (int)$_POST['numero_progresivo'];
    $nombre_completo = trim($_POST['nombre_completo']);
    $id_situacion = (int)$_POST['id_situacion'];
    $id_localidad = (int)$_POST['id_localidad'];
    $numero_ran = empty(trim($_POST['numero_ran'])) ? null : trim($_POST['numero_ran']);
    $numero_certificado = empty(trim($_POST['numero_certificado'])) ? null : trim($_POST['numero_certificado']);
    $lugar_residencia = trim($_POST['lugar_residencia']);
    $telefono = empty(trim($_POST['telefono'])) ? null : trim($_POST['telefono']);

    pg_query($conexion, "BEGIN");

    try {
        // Validar que el color no esté en uso por otro comunero
        $query_color = "SELECT id_comunero, nombre_completo FROM comunero WHERE color_mapa = $1";
        if ($accion == 'editar') {
            $query_color .= " AND id_comunero != $2";
            $res_color = pg_query_params($conexion, $query_color, [$color_mapa, $id_comunero]);
        } else {
            $res_color = pg_query_params($conexion, "SELECT id_comunero, nombre_completo FROM comunero WHERE color_mapa = $1", [$color_mapa]);
        }
        
        if (pg_num_rows($res_color) > 0) {
            $row_color = pg_fetch_assoc($res_color);
            throw new Exception("El color ya está siendo usado por: " . htmlspecialchars($row_color['nombre_completo']));
        }
        if ($accion == 'nuevo') {
            // Insertar comunero
            $sql = "INSERT INTO comunero (numero_progresivo, nombre_completo, id_situacion, id_localidad, numero_ran, numero_certificado, lugar_residencia, telefono, observaciones, color_mapa) 
                    VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10) RETURNING id_comunero";
            $res = pg_query_params($conexion, $sql, [$numero_progresivo, $nombre_completo, $id_situacion, $id_localidad, $numero_ran, $numero_certificado, $lugar_residencia, $telefono, $observaciones, $color_mapa]);
            
            if (!$res) throw new Exception("Error al insertar comunero: " . pg_last_error($conexion));
            $id_comunero = pg_fetch_result($res, 0, 0);

        } else {
            // Actualizar comunero
            $sql = "UPDATE comunero SET 
                    numero_progresivo = $1, nombre_completo = $2, id_situacion = $3, id_localidad = $4, 
                    numero_ran = $5, numero_certificado = $6, lugar_residencia = $7, telefono = $8, observaciones = $9, color_mapa = $10
                    WHERE id_comunero = $11";
            $res = pg_query_params($conexion, $sql, [$numero_progresivo, $nombre_completo, $id_situacion, $id_localidad, $numero_ran, $numero_certificado, $lugar_residencia, $telefono, $observaciones, $color_mapa, $id_comunero]);
            
            if (!$res) throw new Exception("Error al actualizar comunero.");
            
            // Borrar sucesores actuales para insertar los que vienen en el form
            // (En un entorno real evaluaríamos updates vs deletes, pero borrado lógico o real de sucesores es más simple para listas dinámicas si no hay FKs sensibles)
            pg_query_params($conexion, "DELETE FROM sucesor WHERE id_comunero = $1", [$id_comunero]);
        }

        // Insertar sucesores
        foreach ($sucesores as $s) {
            $nombre = trim($s['nombre']);
            $parentesco = trim($s['parentesco']);
            if (!empty($nombre)) {
                $sql_suc = "INSERT INTO sucesor (id_comunero, nombre_sucesor, parentesco) VALUES ($1, $2, $3)";
                pg_query_params($conexion, $sql_suc, [$id_comunero, $nombre, $parentesco]);
            }
        }

        pg_query($conexion, "COMMIT");
        header("Location: /proyectocomunitario/public/index.php?page=comuneros&msg=success");
        exit;

    } catch (Exception $e) {
        pg_query($conexion, "ROLLBACK");
        die("Error procesando solicitud: " . $e->getMessage());
    }
} elseif ($accion == 'desactivar') {
    $id_comunero = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id_comunero > 0) {
        pg_query_params($conexion, "UPDATE comunero SET activo = FALSE WHERE id_comunero = $1", [$id_comunero]);
    }
    header("Location: /proyectocomunitario/public/index.php?page=comuneros&msg=deleted");
    exit;
} elseif ($accion == 'reactivar') {
    $id_comunero = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id_comunero > 0) {
        pg_query_params($conexion, "UPDATE comunero SET activo = TRUE WHERE id_comunero = $1", [$id_comunero]);
    }
    header("Location: /proyectocomunitario/public/index.php?page=comuneros&tab=inactivos&msg=reactivated");
    exit;
} else {
    header("Location: /proyectocomunitario/public/index.php?page=comuneros");
    exit;
}
?>
