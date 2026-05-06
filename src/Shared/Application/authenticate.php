<?php
/**
 * Script de autenticación
 * Procesa el login del usuario
 */

// Registrar errores en JSON
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error PHP: ' . $errstr . ' en ' . basename($errfile) . ':' . $errline
    ]);
    exit;
});

try {
    require_once __DIR__ . '/../Infrastructure/Database/Connection.php';
    require_once __DIR__ . '/../Infrastructure/Auth/Session.php';

    header('Content-Type: application/json');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Método no permitido']);
        exit;
    }

    $nombre_usuario = isset($_POST['nombre_usuario']) ? trim($_POST['nombre_usuario']) : '';
    $contrasena = isset($_POST['contrasena']) ? $_POST['contrasena'] : '';

    // Validación básica
    if (empty($nombre_usuario) || empty($contrasena)) {
        http_response_code(400);
        echo json_encode(['error' => 'Usuario y contraseña requeridos']);
        exit;
    }

    // Buscar usuario en la base de datos por nombre de usuario
    $query = "
        SELECT u.id_usuario, u.usuario, u.nombre, u.password_hash, 
               u.activo, u.id_rol, r.nombre as nombre_rol
        FROM usuario u
        JOIN rol r ON u.id_rol = r.id_rol
        WHERE u.usuario = $1
    ";

    $result = pg_query_params($conexion, $query, [$nombre_usuario]);

    if (!$result) {
        throw new Exception('Error en consulta: ' . pg_last_error($conexion));
    }

    if (pg_num_rows($result) === 0) {
        http_response_code(401);
        echo json_encode(['error' => 'Usuario o contraseña incorrectos']);
        exit;
    }

    $usuario = pg_fetch_assoc($result);

    // Verificar si el usuario está activo
    if ($usuario['activo'] !== 't') {
        http_response_code(401);
        echo json_encode(['error' => 'Usuario desactivado. Contacte al administrador']);
        exit;
    }

    // Verificar la contraseña (comparación directa)
    if ($usuario['password_hash'] !== $contrasena) {
        http_response_code(401);
        echo json_encode(['error' => 'Usuario o contraseña incorrectos']);
        exit;
    }

    // Actualizar último login
    // Nota: La tabla usuario no tiene columna updated_at, solo created_at
    // Por ahora no actualizamos, se puede agregar una columna ultimo_login si se desea
    // pg_query_params(
    //     $conexion,
    //     "UPDATE usuario SET updated_at = CURRENT_TIMESTAMP WHERE id_usuario = $1",
    //     [$usuario['id_usuario']]
    // );

    // Crear sesión
    Session::crearSesion(
        $usuario['id_usuario'],
        $usuario['usuario'],
        $usuario['nombre'],
        $usuario['id_rol'],
        $usuario['nombre_rol']
    );

    // Retornar éxito
    echo json_encode([
        'success' => true,
        'message' => 'Login exitoso',
        'redirect' => '/proyectocomunitariov3/index.php'
    ]);

} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error: ' . $e->getMessage()
    ]);
}
