<?php
// Iniciar buffer de output para evitar que warnings/notices rompan el JSON
ob_start();

// Definir header antes de cualquier include
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../Shared/Infrastructure/Database/Connection.php';
require_once __DIR__ . '/../../Shared/Infrastructure/Auth/Session.php';

Session::iniciar();

$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';
$isAjax = !empty($_POST['ajax']);

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
    $observaciones = trim($_POST['observaciones'] ?? '');
    $color_mapa = trim($_POST['color_mapa'] ?? '#3b82f6');
    $sucesores = $_POST['sucesores'] ?? [];

    pg_query($conexion, "BEGIN");

    try {
        // Validar telefono: solo numeros y exactamente 10 digitos (si se captura)
        if (!empty($telefono) && !preg_match('/^\d{10}$/', $telefono)) {
            throw new Exception("⚠️ El telefono debe tener exactamente 10 numeros.");
        }

        // Validar que el certificado no sea duplicado
        if (!empty($numero_certificado)) {
            $query_cert = "SELECT id_comunero, nombre_completo FROM comunero WHERE numero_certificado = $1";
            if ($accion == 'editar') {
                $query_cert .= " AND id_comunero != $2";
                $res_cert = pg_query_params($conexion, $query_cert, [$numero_certificado, $id_comunero]);
            } else {
                $res_cert = pg_query_params($conexion, $query_cert, [$numero_certificado]);
            }
            
            if (pg_num_rows($res_cert) > 0) {
                throw new Exception("⚠️ El número de certificado '{$numero_certificado}' ya está registrado en el sistema.");
            }
        }

        // Validar que el número R.A.N. no sea duplicado
        if (!empty($numero_ran)) {
            $query_ran = "SELECT id_comunero, nombre_completo FROM comunero WHERE numero_ran = $1";
            if ($accion == 'editar') {
                $query_ran .= " AND id_comunero != $2";
                $res_ran = pg_query_params($conexion, $query_ran, [$numero_ran, $id_comunero]);
            } else {
                $res_ran = pg_query_params($conexion, $query_ran, [$numero_ran]);
            }
            
            if (pg_num_rows($res_ran) > 0) {
                $row_ran = pg_fetch_assoc($res_ran);
                throw new Exception("⚠️ El número R.A.N. '{$numero_ran}' ya está registrado a nombre de: " . htmlspecialchars($row_ran['nombre_completo']));
            }
        }

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
            throw new Exception("🎨 El color ya está siendo usado por: " . htmlspecialchars($row_color['nombre_completo']));
        }

        if ($accion == 'nuevo') {
            // Insertar comunero
            $sql = "INSERT INTO comunero (numero_progresivo, nombre_completo, id_situacion, id_localidad, numero_ran, numero_certificado, lugar_residencia, telefono, observaciones, color_mapa) 
                    VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10) RETURNING id_comunero";
            $res = pg_query_params($conexion, $sql, [$numero_progresivo, $nombre_completo, $id_situacion, $id_localidad, $numero_ran, $numero_certificado, $lugar_residencia, $telefono, $observaciones, $color_mapa]);
            
            if (!$res) throw new Exception("❌ Error al insertar comunero: " . pg_last_error($conexion));
            $id_comunero = pg_fetch_result($res, 0, 0);

        } else {
            // Actualizar comunero
            $sql = "UPDATE comunero SET 
                    numero_progresivo = $1, nombre_completo = $2, id_situacion = $3, id_localidad = $4, 
                    numero_ran = $5, numero_certificado = $6, lugar_residencia = $7, telefono = $8, observaciones = $9, color_mapa = $10
                    WHERE id_comunero = $11";
            $res = pg_query_params($conexion, $sql, [$numero_progresivo, $nombre_completo, $id_situacion, $id_localidad, $numero_ran, $numero_certificado, $lugar_residencia, $telefono, $observaciones, $color_mapa, $id_comunero]);
            
            if (!$res) throw new Exception("❌ Error al actualizar comunero: " . pg_last_error($conexion));
            
            // Borrar sucesores actuales para insertar los que vienen en el form
            pg_query_params($conexion, "DELETE FROM sucesor WHERE id_comunero = $1", [$id_comunero]);
        }

        // Insertar sucesores
        foreach ($sucesores as $s) {
            $nombre = trim($s['nombre'] ?? '');
            $parentesco = trim($s['parentesco'] ?? '');
            if (!empty($nombre)) {
                $sql_suc = "INSERT INTO sucesor (id_comunero, nombre_sucesor, parentesco) VALUES ($1, $2, $3)";
                pg_query_params($conexion, $sql_suc, [$id_comunero, $nombre, $parentesco]);
            }
        }

        pg_query($conexion, "COMMIT");
        
        if ($isAjax) {
            ob_end_clean();
            echo json_encode([
                'success' => true,
                'message' => $accion == 'nuevo' ? '✅ Comunero registrado exitosamente' : '✅ Comunero actualizado exitosamente'
            ]);
        } else {
            header("Location: /proyectocomunitario/public/index.php?page=comuneros&msg=success");
        }
        exit;

    } catch (Exception $e) {
        pg_query($conexion, "ROLLBACK");
        
        if ($isAjax) {
            ob_end_clean();
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        } else {
            die("Error procesando solicitud: " . $e->getMessage());
        }
        exit;
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
} elseif ($accion == 'eliminar_total') {
    if (!Session::esAdmin()) {
        http_response_code(403);
        header("Location: /proyectocomunitario/public/index.php?page=comuneros&tab=inactivos&error=" . urlencode('Acceso denegado. Solo administradores pueden eliminar definitivamente.'));
        exit;
    }

    $id_comunero = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id_comunero <= 0) {
        header("Location: /proyectocomunitario/public/index.php?page=comuneros&tab=inactivos&error=" . urlencode('ID de comunero no valido.'));
        exit;
    }

    pg_query($conexion, "BEGIN");

    try {
        $res_comunero = pg_query_params(
            $conexion,
            "SELECT id_comunero, activo FROM comunero WHERE id_comunero = $1 FOR UPDATE",
            [$id_comunero]
        );

        if (!$res_comunero || pg_num_rows($res_comunero) === 0) {
            throw new Exception('El comunero no existe.');
        }

        $comunero = pg_fetch_assoc($res_comunero);
        if ($comunero['activo'] === 't') {
            throw new Exception('Solo se puede eliminar definitivamente un comunero inactivo.');
        }

        // Eliminar archivos fisicos vinculados a actas del comunero
        $res_rutas_archivos = pg_query_params(
            $conexion,
            "SELECT ar.ruta_archivo
             FROM archivo ar
             JOIN acta_posesion a ON a.id_acta = ar.id_acta
             WHERE a.id_comunero = $1",
            [$id_comunero]
        );
        if ($res_rutas_archivos === false) {
            throw new Exception('No fue posible obtener archivos de actas del comunero.');
        }

        while ($archivo = pg_fetch_assoc($res_rutas_archivos)) {
            $rutaFisica = __DIR__ . '/../../../' . $archivo['ruta_archivo'];
            if (is_file($rutaFisica)) {
                @unlink($rutaFisica);
            }
        }

        // Eliminar metadatos de archivos relacionados
        $res_delete_archivos = pg_query_params(
            $conexion,
            "DELETE FROM archivo ar
             USING acta_posesion a
             WHERE ar.id_acta = a.id_acta
               AND a.id_comunero = $1",
            [$id_comunero]
        );
        if ($res_delete_archivos === false) {
            throw new Exception('No fue posible eliminar archivos vinculados al comunero.');
        }

        // Eliminar actas de posesion relacionadas al comunero
        $res_delete_actas = pg_query_params($conexion, "DELETE FROM acta_posesion WHERE id_comunero = $1", [$id_comunero]);
        if ($res_delete_actas === false) {
            throw new Exception('No fue posible eliminar actas vinculadas al comunero.');
        }

        // Limpiar datos dependientes
        if (pg_query_params($conexion, "DELETE FROM pago_predial WHERE id_comunero = $1", [$id_comunero]) === false) {
            throw new Exception('No fue posible eliminar pagos de predial del comunero.');
        }
        if (pg_query_params($conexion, "DELETE FROM cumplimiento_tequio WHERE id_comunero = $1", [$id_comunero]) === false) {
            throw new Exception('No fue posible eliminar cumplimiento de tequios del comunero.');
        }
        if (pg_query_params($conexion, "DELETE FROM asistencia_asamblea WHERE id_comunero = $1", [$id_comunero]) === false) {
            throw new Exception('No fue posible eliminar asistencias de asambleas del comunero.');
        }
        if (pg_query_params($conexion, "DELETE FROM sucesor WHERE id_comunero = $1", [$id_comunero]) === false) {
            throw new Exception('No fue posible eliminar sucesores del comunero.');
        }

        $res_delete = pg_query_params($conexion, "DELETE FROM comunero WHERE id_comunero = $1", [$id_comunero]);
        if (!$res_delete) {
            throw new Exception('No fue posible eliminar el comunero.');
        }

        pg_query($conexion, "COMMIT");
        header("Location: /proyectocomunitario/public/index.php?page=comuneros&tab=inactivos&msg=deleted_total");
        exit;
    } catch (Exception $e) {
        pg_query($conexion, "ROLLBACK");
        header("Location: /proyectocomunitario/public/index.php?page=comuneros&tab=inactivos&error=" . urlencode($e->getMessage()));
        exit;
    }
} else {
    header("Location: /proyectocomunitario/public/index.php?page=comuneros");
    exit;
}
?>
