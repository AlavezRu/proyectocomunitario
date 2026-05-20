<?php
require_once __DIR__ . '/../../Shared/Infrastructure/Database/Connection.php';
require_once __DIR__ . '/../../Shared/Infrastructure/Auth/require_admin.php';

$id_tequio = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_tequio <= 0) {
    header('Location: /proyectocomunitario/public/index.php?page=tequios&error=invalid_id');
    exit;
}

pg_query($conexion, 'BEGIN');

try {
    $res_lista = pg_query_params($conexion, 'DELETE FROM cumplimiento_tequio WHERE id_tequio = $1', [$id_tequio]);
    if ($res_lista === false) {
        throw new Exception('No se pudo eliminar la lista de asistencia del tequio.');
    }

    $res_tequio = pg_query_params($conexion, 'DELETE FROM tequio WHERE id_tequio = $1', [$id_tequio]);
    if ($res_tequio === false || pg_affected_rows($res_tequio) === 0) {
        throw new Exception('No se pudo eliminar el tequio.');
    }

    pg_query($conexion, 'COMMIT');
    header('Location: /proyectocomunitario/public/index.php?page=tequios&msg=deleted');
    exit;
} catch (Exception $e) {
    pg_query($conexion, 'ROLLBACK');
    header('Location: /proyectocomunitario/public/index.php?page=tequios&error=' . urlencode($e->getMessage()));
    exit;
}
?>