<?php
require_once __DIR__ . '/../../Shared/Infrastructure/Database/Connection.php';

$accion = $_POST['accion'] ?? '';

if ($accion == 'nuevo') {
    $id_comunero = (int)$_POST['id_comunero'];
    $fecha_acta = $_POST['fecha_acta'];
    $descripcion_predio = trim($_POST['descripcion_predio']);
    $colindancias = trim($_POST['colindancias']);
    $ubicacion_geojson = $_POST['ubicacion_geojson'] ?? '';

    pg_query($conexion, "BEGIN");

    try {
        // Preparar la parte GIS desde GeoJSON
        $geometry_json = '';
        
        if (!empty($ubicacion_geojson)) {
            $geojson = json_decode($ubicacion_geojson, true);
            if ($geojson && isset($geojson['features']) && count($geojson['features']) > 0) {
                $feature = $geojson['features'][0];
                $geometry = $feature['geometry'];
                
                // Convertir GeoJSON a JSON string para ST_GeomFromGeoJSON
                $geometry_json = json_encode($geometry);
            }
        }

        // Insertar Acta
        $res_acta = pg_query_params(
            $conexion, 
            "INSERT INTO acta_posesion (id_comunero, fecha_acta, descripcion_predio, colindancias) VALUES ($1, $2, $3, $4) RETURNING id_acta", 
            [$id_comunero, $fecha_acta, $descripcion_predio, $colindancias]
        );
        
        if (!$res_acta) throw new Exception("Error al insertar acta: " . pg_last_error($conexion));
        $id_acta = pg_fetch_result($res_acta, 0, 0);

        // Actualizar ubicacion con PostGIS si hay GeoJSON válido
        if (!empty($geometry_json)) {
            $update_result = pg_query_params(
                $conexion, 
                "UPDATE acta_posesion SET ubicacion = ST_SetSRID(ST_GeomFromGeoJSON($1), 4326) WHERE id_acta = $2", 
                [$geometry_json, $id_acta]
            );
            if (!$update_result) {
                throw new Exception("Error al actualizar ubicación: " . pg_last_error($conexion));
            }
            
            // Verificar que se guardó correctamente
            $verify = pg_query($conexion, "SELECT ST_AsGeoJSON(ubicacion) FROM acta_posesion WHERE id_acta = $id_acta");
            if ($verify) {
                $geom = pg_fetch_result($verify, 0, 0);
                if ($geom) {
                    error_log("✓ Geometría guardada para acta $id_acta: " . substr($geom, 0, 150));
                } else {
                    error_log("✗ ST_AsGeoJSON devolvió NULL para acta $id_acta");
                }
            }
        }

        // Manejar subida de archivo
        if (isset($_FILES['archivo_acta']) && $_FILES['archivo_acta']['error'] == UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['archivo_acta']['tmp_name'];
            $file_name = $_FILES['archivo_acta']['name'];
            $file_size = $_FILES['archivo_acta']['size'];
            
            // Obtener extensión y validar
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed = ['pdf', 'jpg', 'jpeg', 'png'];
            
            if (!in_array($file_ext, $allowed)) {
                throw new Exception("Tipo de archivo no permitido: $file_ext");
            }

            // Crear directorio si no existe
            $upload_dir = '../../../documentos/actas/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $new_file_name = "acta_" . $id_acta . "_" . time() . "." . $file_ext;
            $dest_path = $upload_dir . $new_file_name;

            if (move_uploaded_file($file_tmp, $dest_path)) {
                // Insertar en tabla archivo
                $ruta_db = "documentos/actas/" . $new_file_name;
                $sql_arch = "INSERT INTO archivo (id_acta, nombre_archivo, ruta_archivo, tipo_archivo) VALUES ($1, $2, $3, $4)";
                pg_query_params($conexion, $sql_arch, [$id_acta, $file_name, $ruta_db, $file_ext]);
            } else {
                throw new Exception("Error al mover el archivo subido al servidor.");
            }
        }

        pg_query($conexion, "COMMIT");
        header("Location: /proyectocomunitariov3/public/index.php?page=actas&msg=success");
        exit;

    } catch (Exception $e) {
        pg_query($conexion, "ROLLBACK");
        die("Error procesando solicitud: " . $e->getMessage());
    }
} elseif ($accion == 'editar') {
    $id_acta = (int)$_POST['id_acta'];
    $id_comunero = (int)$_POST['id_comunero'];
    $fecha_acta = $_POST['fecha_acta'];
    $descripcion_predio = trim($_POST['descripcion_predio']);
    $colindancias = trim($_POST['colindancias']);
    $ubicacion_geojson = $_POST['ubicacion_geojson'] ?? '';

    pg_query($conexion, "BEGIN");

    try {
        // Actualizar Acta
        $res_acta = pg_query_params(
            $conexion, 
            "UPDATE acta_posesion SET id_comunero = $1, fecha_acta = $2, descripcion_predio = $3, colindancias = $4 WHERE id_acta = $5", 
            [$id_comunero, $fecha_acta, $descripcion_predio, $colindancias, $id_acta]
        );
        
        if (!$res_acta) throw new Exception("Error al actualizar acta: " . pg_last_error($conexion));

        // Actualizar ubicacion con PostGIS si hay GeoJSON válido
        if (!empty($ubicacion_geojson)) {
            $geojson = json_decode($ubicacion_geojson, true);
            if ($geojson && isset($geojson['features']) && count($geojson['features']) > 0) {
                $feature = $geojson['features'][0];
                $geometry = $feature['geometry'];
                $geometry_json = json_encode($geometry);
                
                $update_result = pg_query_params(
                    $conexion, 
                    "UPDATE acta_posesion SET ubicacion = ST_SetSRID(ST_GeomFromGeoJSON($1), 4326) WHERE id_acta = $2", 
                    [$geometry_json, $id_acta]
                );
                if (!$update_result) {
                    throw new Exception("Error al actualizar ubicación: " . pg_last_error($conexion));
                }
            }
        }

        // Manejar subida de archivo
        if (isset($_FILES['archivo_acta']) && $_FILES['archivo_acta']['error'] == UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['archivo_acta']['tmp_name'];
            $file_name = $_FILES['archivo_acta']['name'];
            $file_size = $_FILES['archivo_acta']['size'];
            
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed = ['pdf', 'jpg', 'jpeg', 'png'];
            
            if (!in_array($file_ext, $allowed)) {
                throw new Exception("Tipo de archivo no permitido: $file_ext");
            }

            $upload_dir = '../../../documentos/actas/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $new_file_name = "acta_" . $id_acta . "_" . time() . "." . $file_ext;
            $dest_path = $upload_dir . $new_file_name;

            if (move_uploaded_file($file_tmp, $dest_path)) {
                $ruta_db = "documentos/actas/" . $new_file_name;
                $sql_arch = "INSERT INTO archivo (id_acta, nombre_archivo, ruta_archivo, tipo_archivo) VALUES ($1, $2, $3, $4)";
                pg_query_params($conexion, $sql_arch, [$id_acta, $file_name, $ruta_db, $file_ext]);
            } else {
                throw new Exception("Error al mover el archivo subido al servidor.");
            }
        }

        pg_query($conexion, "COMMIT");
        header("Location: /proyectocomunitariov3/public/index.php?page=actas&msg=success");
        exit;

    } catch (Exception $e) {
        pg_query($conexion, "ROLLBACK");
        die("Error procesando solicitud: " . $e->getMessage());
    }
} elseif ($accion == 'eliminar') {
    $id_acta = (int)$_POST['id_acta'];

    pg_query($conexion, "BEGIN");

    try {
        // Obtener información de archivos asociados
        $q_archivos = pg_query_params($conexion, "SELECT ruta_archivo FROM archivo WHERE id_acta = $1", [$id_acta]);
        
        while ($row_archivo = pg_fetch_assoc($q_archivos)) {
            $file_path = '../../../' . $row_archivo['ruta_archivo'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }

        // Eliminar registros de la tabla archivo
        pg_query_params($conexion, "DELETE FROM archivo WHERE id_acta = $1", [$id_acta]);

        // Eliminar acta
        $res = pg_query_params($conexion, "DELETE FROM acta_posesion WHERE id_acta = $1", [$id_acta]);
        
        if (!$res) throw new Exception("Error al eliminar acta: " . pg_last_error($conexion));

        pg_query($conexion, "COMMIT");
        header("Location: /proyectocomunitariov3/public/index.php?page=actas&msg=deleted");
        exit;

    } catch (Exception $e) {
        pg_query($conexion, "ROLLBACK");
        die("Error procesando solicitud: " . $e->getMessage());
    }
} elseif ($accion == 'eliminar_archivo') {
    $id_archivo = (int)$_POST['id_archivo'] ?? 0;
    $id_acta = (int)$_POST['id_acta'] ?? 0;

    if (!$id_archivo || !$id_acta) {
        echo "error: parámetros inválidos";
        exit;
    }

    pg_query($conexion, "BEGIN");

    try {
        // Obtener información del archivo
        $q_archivo = pg_query_params($conexion, "SELECT ruta_archivo FROM archivo WHERE id_archivo = $1 AND id_acta = $2", [$id_archivo, $id_acta]);
        
        if (pg_num_rows($q_archivo) === 0) {
            throw new Exception("Archivo no encontrado");
        }

        $row_archivo = pg_fetch_assoc($q_archivo);
        $file_path = '../../../' . $row_archivo['ruta_archivo'];

        // Eliminar archivo físico si existe
        if (file_exists($file_path)) {
            unlink($file_path);
        }

        // Eliminar registro de la base de datos
        $res = pg_query_params($conexion, "DELETE FROM archivo WHERE id_archivo = $1 AND id_acta = $2", [$id_archivo, $id_acta]);
        
        if (!$res) {
            throw new Exception("Error al eliminar registro: " . pg_last_error($conexion));
        }

        pg_query($conexion, "COMMIT");
        echo "success: archivo eliminado";
        exit;

    } catch (Exception $e) {
        pg_query($conexion, "ROLLBACK");
        echo "error: " . $e->getMessage();
        exit;
    }
} else {
    header("Location: /proyectocomunitariov3/public/index.php?page=actas");
    exit;
}
?>
