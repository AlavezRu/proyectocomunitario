<?php
/**
 * Guard de acceso exclusivo para Administradores.
 * Incluir este archivo al inicio de cualquier recurso restringido a Admin.
 *
 * - Si el usuario no está autenticado → redirige al login.
 * - Si está autenticado pero no es Admin → responde 403 o redirige al dashboard.
 */
require_once __DIR__ . '/Session.php';

Session::iniciar();

if (!Session::estaLogueado()) {
    header('Location: /proyectocomunitariov3/public/login.php');
    exit;
}

if (!Session::esAdmin()) {
    // Detectar si la petición espera JSON (llamadas AJAX / POST directo)
    $wantsJson = (
        isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false
    ) || (
        isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false
    );

    if ($wantsJson) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Acceso denegado. Se requiere rol Administrador.']);
        exit;
    }

    // Para peticiones normales (GET/POST de formularios) → redirigir al dashboard con mensaje
    http_response_code(403);
    header('Location: /proyectocomunitariov3/public/index.php?page=dashboard&error=' . urlencode('Acceso denegado. No tienes permisos para ver esa sección.'));
    exit;
}
