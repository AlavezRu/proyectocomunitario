<?php
require_once __DIR__ . '/../../Shared/Infrastructure/Database/Connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_asamblea = (int)$_POST['id_asamblea'];
    $descripcion = trim($_POST['descripcion']);
    $observacion = trim($_POST['observacion'] ?? '');

    if ($id_asamblea > 0 && !empty($descripcion)) {
        $sql = "UPDATE asamblea SET descripcion = $1, observacion = $2 WHERE id_asamblea = $3";
        $result = pg_query_params($conexion, $sql, [$descripcion, $observacion ?: null, $id_asamblea]);
        
        if ($result) {
            header("Location: /proyectocomunitariov3/public/index.php?page=asambleas&msg=updated");
        } else {
            header("Location: /proyectocomunitariov3/public/index.php?page=asambleas&error=update_failed");
        }
    } else {
        header("Location: /proyectocomunitariov3/public/index.php?page=asambleas&error=invalid_data");
    }
} else {
    header("Location: /proyectocomunitariov3/public/index.php?page=asambleas");
}
exit;
?>
