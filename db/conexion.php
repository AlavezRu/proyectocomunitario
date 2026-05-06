<?php
// db/conexion.php

$host = "localhost";
$port = "5432";
// Nota: en el script SQL la BD se llama soyaltepec_db, ajustado de acuerdo al script.
$dbname = "soyaltepecdb";
$user = "postgres";
$password = "1234";

$conn_string = "host=$host port=$port dbname=$dbname user=$user password=$password";
$conexion = pg_connect($conn_string);

if ($conexion === false) {
    die(json_encode([
        "data" => [],
        "error" => "Error de conexión a la base de datos: " . pg_last_error(null)
    ]));
}
?>
