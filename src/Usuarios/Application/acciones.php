<?php
/**
 * Acciones CRUD del módulo de Usuarios.
 * Acceso exclusivo para Administradores.
 */
require_once __DIR__ . '/../../Shared/Infrastructure/Auth/require_admin.php';
require_once __DIR__ . '/../../Shared/Infrastructure/Database/Connection.php';

function redirigir(string $page, string $paramNombre, string $mensaje): void {
    header('Location: /proyectocomunitario/public/index.php?page=' . $page . '&' . $paramNombre . '=' . urlencode($mensaje));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir('usuarios', 'error', 'Método no permitido.');
}

$accion = trim($_POST['accion'] ?? '');

// CREAR / EDITAR USUARIO
if ($accion === 'nuevo' || $accion === 'editar') {
    $id_usuario = isset($_POST['id_usuario']) ? (int)$_POST['id_usuario'] : 0;
    $nombre = trim($_POST['nombre'] ?? '');
    $usuario_login = trim($_POST['usuario'] ?? '');
    $id_rol = (int)($_POST['id_rol'] ?? 0);
    $activo = isset($_POST['activo']) ? true : false;

    if ($nombre === '') {
        redirigir($accion === 'nuevo' ? 'usuarios_formulario' : 'usuarios_formulario&editar=' . $id_usuario, 'error', 'El nombre es requerido.');
    }
    if (strlen($nombre) > 100) {
        redirigir($accion === 'nuevo' ? 'usuarios_formulario' : 'usuarios_formulario&editar=' . $id_usuario, 'error', 'El nombre no puede superar 100 caracteres.');
    }
    if ($usuario_login === '') {
        redirigir($accion === 'nuevo' ? 'usuarios_formulario' : 'usuarios_formulario&editar=' . $id_usuario, 'error', 'El nombre de usuario es requerido.');
    }
    if (strlen($usuario_login) > 100) {
        redirigir($accion === 'nuevo' ? 'usuarios_formulario' : 'usuarios_formulario&editar=' . $id_usuario, 'error', 'El usuario no puede superar 100 caracteres.');
    }
    if ($id_rol <= 0) {
        redirigir($accion === 'nuevo' ? 'usuarios_formulario' : 'usuarios_formulario&editar=' . $id_usuario, 'error', 'Debe seleccionar un rol válido.');
    }

    $res_rol = pg_query_params($conexion, 'SELECT id_rol FROM rol WHERE id_rol = $1', [$id_rol]);
    if (!$res_rol || pg_num_rows($res_rol) === 0) {
        redirigir($accion === 'nuevo' ? 'usuarios_formulario' : 'usuarios_formulario&editar=' . $id_usuario, 'error', 'El rol seleccionado no existe.');
    }

    pg_query($conexion, 'BEGIN');

    try {
        if ($accion === 'nuevo') {
            $password_nuevo = $_POST['password_nuevo'] ?? '';
            $password_confirmar = $_POST['password_confirmar'] ?? '';

            if ($password_nuevo === '') {
                throw new Exception('La contraseña es requerida.');
            }
            if ($password_nuevo !== $password_confirmar) {
                throw new Exception('Las contraseñas no coinciden.');
            }

            $res_existe = pg_query_params($conexion, 'SELECT id_usuario FROM usuario WHERE usuario = $1', [$usuario_login]);
            if (pg_num_rows($res_existe) > 0) {
                throw new Exception('Ya existe un usuario con ese nombre de usuario.');
            }

            $sql = 'INSERT INTO usuario (nombre, usuario, password_hash, id_rol, activo)
                    VALUES ($1, $2, $3, $4, $5)';
            $res = pg_query_params($conexion, $sql, [$nombre, $usuario_login, $password_nuevo, $id_rol, $activo ? 'true' : 'false']);
            if (!$res) {
                throw new Exception('Error al crear el usuario.');
            }

            $mensaje = 'Usuario creado exitosamente.';
        } else {
            if ($id_usuario <= 0) {
                throw new Exception('ID de usuario inválido.');
            }

            $res_existe = pg_query_params($conexion, 'SELECT id_usuario FROM usuario WHERE usuario = $1 AND id_usuario <> $2', [$usuario_login, $id_usuario]);
            if (pg_num_rows($res_existe) > 0) {
                throw new Exception('Ya existe otro usuario con ese nombre de usuario.');
            }

            $sql = 'UPDATE usuario SET nombre = $1, usuario = $2, id_rol = $3, activo = $4 WHERE id_usuario = $5';
            $res = pg_query_params($conexion, $sql, [$nombre, $usuario_login, $id_rol, $activo ? 'true' : 'false', $id_usuario]);
            if (!$res) {
                throw new Exception('Error al actualizar el usuario.');
            }

            $mensaje = 'Usuario actualizado exitosamente.';
        }

        pg_query($conexion, 'COMMIT');
        redirigir('usuarios', 'mensaje', $mensaje);
    } catch (Exception $e) {
        pg_query($conexion, 'ROLLBACK');
        $paginaRetorno = $accion === 'nuevo' ? 'usuarios_formulario' : 'usuarios_formulario&editar=' . $id_usuario;
        redirigir($paginaRetorno, 'error', $e->getMessage());
    }
}

// ELIMINAR USUARIO
if ($accion === 'eliminar') {
    $id_usuario = (int)($_POST['id_usuario'] ?? 0);

    if ($id_usuario <= 0) {
        redirigir('usuarios', 'error', 'ID de usuario inválido.');
    }

    if (Session::obtenerUsuario()['id_usuario'] == $id_usuario) {
        redirigir('usuarios', 'error', 'No puede eliminar su propia cuenta.');
    }

    $res = pg_query_params($conexion, 'DELETE FROM usuario WHERE id_usuario = $1', [$id_usuario]);
    if (!$res) {
        redirigir('usuarios', 'error', 'Error al eliminar el usuario.');
    }

    redirigir('usuarios', 'mensaje', 'Usuario eliminado exitosamente.');
}

// CAMBIAR CONTRASEÑA
if ($accion === 'cambiar_password') {
    $id_usuario = (int)($_POST['id_usuario'] ?? 0);
    $password_nuevo = $_POST['password_nuevo'] ?? '';
    $password_confirmar = $_POST['password_confirmar'] ?? '';

    if ($id_usuario <= 0) {
        redirigir('usuarios', 'error', 'ID de usuario inválido.');
    }
    if ($password_nuevo === '') {
        redirigir('usuarios', 'error', 'La contraseña es requerida.');
    }
    if ($password_nuevo !== $password_confirmar) {
        redirigir('usuarios', 'error', 'Las contraseñas no coinciden.');
    }

    $res = pg_query_params($conexion, 'UPDATE usuario SET password_hash = $1 WHERE id_usuario = $2', [$password_nuevo, $id_usuario]);
    if (!$res) {
        redirigir('usuarios', 'error', 'Error al cambiar la contraseña.');
    }

    redirigir('usuarios', 'mensaje', 'Contraseña actualizada exitosamente.');
}

redirigir('usuarios', 'error', 'Acción no reconocida.');
