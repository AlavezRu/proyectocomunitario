<?php
require_once __DIR__ . '/../../Shared/Infrastructure/Database/Connection.php';

$anio = isset($_POST['anio']) ? (int) $_POST['anio'] : (int) date('Y');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_comunero = (int)$_POST['id_comunero'];
    $monto = (float)$_POST['monto'];

    if ($id_comunero > 0 && $anio > 0 && $monto > 0) {
        // En PostgreSQL 12+ se puede usar ON CONFLICT.
        // Asumiendo que existe el constraint unique (id_comunero, anio)
        $sql = "INSERT INTO pago_predial (id_comunero, anio, monto, pagado, fecha_pago)
                VALUES ($1, $2, $3, TRUE, CURRENT_DATE)
                ON CONFLICT (id_comunero, anio)
                DO UPDATE SET monto = EXCLUDED.monto, pagado = TRUE, fecha_pago = CURRENT_DATE";
        
        pg_query_params($conexion, $sql, [$id_comunero, $anio, $monto]);
    }
}
header("Location: /proyectocomunitario/public/index.php?page=predial&anio=" . $anio . "&msg=paid");
exit;
?>
