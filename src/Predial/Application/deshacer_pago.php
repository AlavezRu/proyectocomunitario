<?php
require_once __DIR__ . '/../../Shared/Infrastructure/Database/Connection.php';

$id_pago = isset($_GET['id_pago']) ? (int)$_GET['id_pago'] : 0;
$anio = isset($_GET['anio']) ? (int)$_GET['anio'] : date('Y');

if ($id_pago > 0) {
    pg_query_params($conexion, "DELETE FROM pago_predial WHERE id_pago = $1", [$id_pago]);
}

header("Location: /proyectocomunitario/public/index.php?page=predial&anio=" . $anio . "&msg=undone");
exit;
?>
