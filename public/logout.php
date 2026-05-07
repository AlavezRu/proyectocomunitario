<?php
/**
 * Script de logout
 * Cierra la sesión del usuario
 */
require_once '../src/Shared/Infrastructure/Auth/Session.php';

Session::iniciar();
Session::destruir();

header("Location: /proyectocomunitario/public/login.php?msg=logout");
exit;
