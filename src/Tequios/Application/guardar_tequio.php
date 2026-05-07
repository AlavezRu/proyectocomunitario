<?php
require_once __DIR__ . '/../../Shared/Infrastructure/Database/Connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fecha = $_POST['fecha'];
    $descripcion = trim($_POST['descripcion']);
    $observacion = trim($_POST['observacion'] ?? '');

    if (!empty($fecha) && !empty($descripcion)) {
        $sql = "INSERT INTO tequio (fecha, descripcion, observacion) VALUES ($1, $2, $3)";
        pg_query_params($conexion, $sql, [$fecha, $descripcion, $observacion ?: null]);
    }
}
header("Location: /proyectocomunitario/public/index.php?page=tequios&msg=success");
exit;
?>
