<?php
require_once __DIR__ . '/../../Shared/Infrastructure/Database/Connection.php';
require_once __DIR__ . '/../../Shared/Infrastructure/Auth/require_admin.php';

$id_asamblea = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_asamblea <= 0) {
    header('Location: /proyectocomunitario/public/index.php?page=asambleas&error=invalid_id');
    exit;
}

pg_query($conexion, 'BEGIN');

try {
    $res_lista = pg_query_params($conexion, 'DELETE FROM asistencia_asamblea WHERE id_asamblea = $1', [$id_asamblea]);
    if ($res_lista === false) {
        throw new Exception('No se pudo eliminar la lista de asistencia de la asamblea.');
    }

    $res_asamblea = pg_query_params($conexion, 'DELETE FROM asamblea WHERE id_asamblea = $1', [$id_asamblea]);
    if ($res_asamblea === false || pg_affected_rows($res_asamblea) === 0) {
        throw new Exception('No se pudo eliminar la asamblea.');
    }

    pg_query($conexion, 'COMMIT');
    header('Location: /proyectocomunitario/public/index.php?page=asambleas&msg=deleted');
    exit;
} catch (Exception $e) {
    pg_query($conexion, 'ROLLBACK');
    header('Location: /proyectocomunitario/public/index.php?page=asambleas&error=' . urlencode($e->getMessage()));
    exit;
}
?>