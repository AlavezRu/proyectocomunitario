<?php
/**
 * Router principal del sistema
 * Maneja las rutas y carga los módulos correspondientes
 */

// Establecer variables globales de rutas
$GLOBALS['BASE_URL'] = '/proyectocomunitario';
$GLOBALS['ASSETS_URL'] = '/proyectocomunitario/src/Shared/Ui';
$GLOBALS['PUBLIC_URL'] = '/proyectocomunitario/public';

require_once '../src/Shared/Infrastructure/Auth/check_auth.php';
require_once '../db/conexion.php';

$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

// Mapeo de páginas a archivos
$pages = [
    'dashboard' => ['file' => '../src/Dashboard/Ui/index.php', 'page' => 'dashboard'],
    'comuneros' => ['file' => '../src/Comuneros/Ui/index.php', 'page' => 'comuneros'],
    'comuneros_formulario' => ['file' => '../src/Comuneros/Ui/formulario.php', 'page' => 'comuneros'],
    'actas' => ['file' => '../src/ActasDePosesion/Ui/index.php', 'page' => 'actas'],
    'actas_formulario' => ['file' => '../src/ActasDePosesion/Ui/formulario.php', 'page' => 'actas'],
    'predial' => ['file' => '../src/Predial/Ui/index.php', 'page' => 'predial'],
    'tequios' => ['file' => '../src/Tequios/Ui/index.php', 'page' => 'tequios'],
    'tequios_formulario' => ['file' => '../src/Tequios/Ui/formulario_tequio.php', 'page' => 'tequios'],
    'tequios_editar' => ['file' => '../src/Tequios/Ui/editar_tequio.php', 'page' => 'tequios'],
    'tequios_pase_lista' => ['file' => '../src/Tequios/Ui/pase_lista.php', 'page' => 'tequios'],
    'asambleas' => ['file' => '../src/Asambleas/Ui/index.php', 'page' => 'asambleas'],
    'asambleas_formulario' => ['file' => '../src/Asambleas/Ui/formulario_asamblea.php', 'page' => 'asambleas'],
    'asambleas_editar' => ['file' => '../src/Asambleas/Ui/editar_asamblea.php', 'page' => 'asambleas'],
    'asambleas_pase_lista' => ['file' => '../src/Asambleas/Ui/pase_lista.php', 'page' => 'asambleas'],
    'mapa' => ['file' => '../src/Mapa/Ui/index.php', 'page' => 'mapa'],
    'reportes' => ['file' => '../src/Reportes/Ui/index.php', 'page' => 'reportes'],
    'usuarios' => ['file' => '../src/Usuarios/Ui/index.php', 'page' => 'usuarios'],
    'usuarios_formulario' => ['file' => '../src/Usuarios/Ui/formulario.php', 'page' => 'usuarios'],
];

// Establecer página y archivo
if (!isset($pages[$page])) {
    $page = 'dashboard';
}

// --- RBAC: módulo de usuarios exclusivo para Administradores ---
$paginasRestringidasAdmin = ['usuarios', 'usuarios_formulario'];
if (in_array($page, $paginasRestringidasAdmin, true) && !Session::esAdmin()) {
    header('Location: /proyectocomunitario/public/index.php?page=dashboard&error=' . urlencode('Acceso denegado. No tienes permisos para ver esa sección.'));
    exit;
}

$activePage = $pages[$page]['page'];
$file_to_load = $pages[$page]['file'];
$full_path = __DIR__ . '/' . $file_to_load;

// Verificar que el archivo existe
if (!file_exists($full_path)) {
    die("Página no encontrada: " . htmlspecialchars($file_to_load));
}

// Cambiar directorio de trabajo al directorio del archivo a cargar
chdir(dirname($full_path));

// Incluir el archivo
include $full_path;
?>

