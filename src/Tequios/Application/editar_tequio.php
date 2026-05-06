<?php
require_once __DIR__ . '/../../Shared/Infrastructure/Database/Connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_tequio = (int)$_POST['id_tequio'];
    $descripcion = trim($_POST['descripcion']);
    $observacion = trim($_POST['observacion'] ?? '');

    if ($id_tequio > 0 && !empty($descripcion)) {
        $sql = "UPDATE tequio SET descripcion = $1, observacion = $2 WHERE id_tequio = $3";
        $result = pg_query_params($conexion, $sql, [$descripcion, $observacion ?: null, $id_tequio]);
        
        if ($result) {
            header("Location: /proyectocomunitariov3/public/index.php?page=tequios&msg=updated");
        } else {
            header("Location: /proyectocomunitariov3/public/index.php?page=tequios&error=update_failed");
        }
    } else {
        header("Location: /proyectocomunitariov3/public/index.php?page=tequios&error=invalid_data");
    }
} else {
    header("Location: /proyectocomunitariov3/public/index.php?page=tequios");
}
exit;
?>
