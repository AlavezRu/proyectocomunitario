<?php
/**
 * Clase para manejar sesiones de usuario
 */
class Session {
    
    /**
     * Inicia una sesión de usuario
     */
    public static function iniciar() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Verifica si el usuario está logueado
     */
    public static function estaLogueado() {
        self::iniciar();
        return isset($_SESSION['id_usuario']) && !empty($_SESSION['id_usuario']);
    }

    /**
     * Obtiene el usuario actual
     */
    public static function obtenerUsuario() {
        self::iniciar();
        if (self::estaLogueado()) {
            return [
                'id_usuario' => $_SESSION['id_usuario'],
                'usuario' => $_SESSION['usuario'] ?? '',
                'nombre' => $_SESSION['nombre'] ?? '',
                'id_rol' => $_SESSION['id_rol'] ?? 0,
                'nombre_rol' => $_SESSION['nombre_rol'] ?? ''
            ];
        }
        return null;
    }

    /**
     * Obtiene el rol del usuario
     */
    public static function obtenerRol() {
        self::iniciar();
        return $_SESSION['nombre_rol'] ?? null;
    }

    /**
     * Verifica si el usuario tiene un rol específico
     */
    public static function tieneRol(string $rol) {
        self::iniciar();
        return isset($_SESSION['nombre_rol']) && $_SESSION['nombre_rol'] === $rol;
    }

    /**
     * Verifica si es administrador (insensible a mayúsculas)
     */
    public static function esAdmin() {
        self::iniciar();
        $rol = strtolower(trim($_SESSION['nombre_rol'] ?? ''));
        return $rol === 'admin' || $rol === 'administrador';
    }

    /**
     * Crea una sesión de usuario
     */
    public static function crearSesion(int $id_usuario, string $usuario, string $nombre, int $id_rol, string $nombre_rol) {
        self::iniciar();
        $_SESSION['id_usuario'] = $id_usuario;
        $_SESSION['usuario'] = $usuario;
        $_SESSION['nombre'] = $nombre;
        $_SESSION['id_rol'] = $id_rol;
        $_SESSION['nombre_rol'] = $nombre_rol;
        $_SESSION['login_time'] = time();
    }

    /**
     * Destruye la sesión
     */
    public static function destruir() {
        self::iniciar();
        $_SESSION = [];
        
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        
        session_destroy();
    }
}
