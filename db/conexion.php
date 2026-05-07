<?php
// db/conexion.php

$host = "localhost";
$port = "5432";
$dbname = "soyaltepecdb";
$user = "postgres";
$password = "1234";

if (!function_exists('pg_connect')) {
    throw new RuntimeException('La extensión PostgreSQL de PHP no está habilitada (pg_connect). Activa extension=pgsql en php.ini y reinicia Apache.');
}

$conn_string = "host=$host port=$port dbname=$dbname user=$user password=$password";
$conexion = pg_connect($conn_string);

if ($conexion === false) {
    throw new RuntimeException('Error de conexión a la base de datos: ' . pg_last_error(null));
}
?>
