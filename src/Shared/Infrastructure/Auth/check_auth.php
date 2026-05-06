<?php
/**
 * Verifica si el usuario está autenticado
 * Si no está logueado, redirige al login
 */
require_once __DIR__ . '/Session.php';

Session::iniciar();

if (!Session::estaLogueado()) {
    // Reedirige al login con referencia a la página anterior
    $referrer = isset($_GET['page']) ? urlencode($_GET['page']) : '';
    header("Location: /proyectocomunitariov3/public/login.php" . ($referrer ? "?referrer=" . $referrer : ""));
    exit;
}
