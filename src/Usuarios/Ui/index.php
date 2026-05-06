<?php
require_once __DIR__ . '/../../Shared/Infrastructure/Auth/require_admin.php';
require_once __DIR__ . '/../../Shared/Infrastructure/Database/Connection.php';

$pageTitle = "Gestión de Usuarios";
$activePage = "usuarios";

// Cargar usuarios
$sql = "SELECT u.id_usuario, u.nombre, u.usuario, u.password_hash, u.activo, u.created_at, r.nombre as nombre_rol 
        FROM usuario u 
        LEFT JOIN rol r ON u.id_rol = r.id_rol 
        ORDER BY u.created_at DESC";

$resultado = pg_query($conexion, $sql);

if (!$resultado) {
    die("Error en la consulta: " . pg_last_error($conexion));
}

$usuarios = [];
while ($row = pg_fetch_assoc($resultado)) {
    $usuarios[] = $row;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SysComunal | Usuarios</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= $GLOBALS['ASSETS_URL'] ?>/Css/style.css">
    <style>
        .modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); display: none; align-items: center; justify-content: center; z-index: 10000; }
        .modal-overlay.active { display: flex; }
        .modal-content { background: white; border-radius: var(--radius-lg); max-width: 500px; width: 90%; box-shadow: var(--shadow-xl); }
        .modal-header { padding: 1.5rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
        .modal-header h2 { margin: 0; font-size: 1.25rem; }
        .modal-body { padding: 1.5rem; }
        .modal-footer { padding: 1.5rem; border-top: 1px solid var(--border); display: flex; gap: 1rem; justify-content: flex-end; }
        .close-btn { background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-muted); }
    </style>
</head>
<body>

    <?php include '../../Shared/Ui/Layout/sidebar.php'; ?>

    <main class="main-wrapper">
        <?php include '../../Shared/Ui/Layout/header.php'; ?>

        <div class="content-container animate-fade-in">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <div>
                    <h1 style="font-size: 1.75rem; color: var(--text-main); margin-bottom: 0.5rem;">Gestión de Usuarios</h1>
                    <p style="color: var(--text-muted);">Administración de cuentas de usuario del sistema</p>
                </div>
                <div>
                    <a href="/proyectocomunitariov3/public/index.php?page=usuarios_formulario" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> Nuevo Usuario
                    </a>
                </div>
            </div>

            <!-- Mensajes -->
            <?php if (isset($_GET['mensaje'])): ?>
                <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid #10b981; border-radius: 8px; padding: 1rem; margin-bottom: 1.5rem; color: #059669;">
                    <i class="fas fa-check-circle"></i> <?= htmlspecialchars($_GET['mensaje']) ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['error'])): ?>
                <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444; border-radius: 8px; padding: 1rem; margin-bottom: 1.5rem; color: #991b1b;">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($_GET['error']) ?>
                </div>
            <?php endif; ?>

            <div class="glass-panel" style="padding: 1.5rem;">
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; text-align: left;">
                        <thead>
                            <tr style="border-bottom: 2px solid var(--border); color: var(--text-muted); font-weight: 600; font-size: 0.875rem;">
                                <th style="padding: 1rem 0.5rem;">Nombre</th>
                                <th style="padding: 1rem 0.5rem;">Usuario</th>
                                <th style="padding: 1rem 0.5rem;">Contraseña</th>
                                <th style="padding: 1rem 0.5rem;">Rol</th>
                                <th style="padding: 1rem 0.5rem;">Estado</th>
                                <th style="padding: 1rem 0.5rem;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($usuarios) > 0): ?>
                                <?php foreach ($usuarios as $usuario): ?>
                                    <tr style="border-bottom: 1px solid var(--border);" onmouseover="this.style.backgroundColor='#f1f5f9'" onmouseout="this.style.backgroundColor='transparent'">
                                        <td style="padding: 1rem 0.5rem; font-weight: 500;"><?= htmlspecialchars($usuario['nombre']) ?></td>
                                        <td style="padding: 1rem 0.5rem;"><?= htmlspecialchars($usuario['usuario']) ?></td>
                                        <td style="padding: 1rem 0.5rem; font-family: monospace; font-size: 0.85rem;"><?= htmlspecialchars($usuario['password_hash'] ?? '') ?></td>
                                        <td style="padding: 1rem 0.5rem;">
                                            <span style="background: rgba(37, 99, 235, 0.1); color: var(--primary); padding: 0.25rem 0.75rem; border-radius: 999px; font-size: 0.75rem; font-weight: 600;">
                                                <?= htmlspecialchars($usuario['nombre_rol'] ?? 'Sin rol') ?>
                                            </span>
                                        </td>
                                        <td style="padding: 1rem 0.5rem;">
                                            <?php if ($usuario['activo']): ?>
                                                <span style="background: rgba(16, 185, 129, 0.1); color: var(--secondary); padding: 0.25rem 0.75rem; border-radius: 999px; font-size: 0.75rem; font-weight: 600;">
                                                    <i class="fas fa-check"></i> Activo
                                                </span>
                                            <?php else: ?>
                                                <span style="background: rgba(239, 68, 68, 0.1); color: var(--danger); padding: 0.25rem 0.75rem; border-radius: 999px; font-size: 0.75rem; font-weight: 600;">
                                                    <i class="fas fa-times"></i> Inactivo
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="padding: 1rem 0.5rem;">
                                            <div style="display: flex; gap: 0.5rem;">
                                                <a href="/proyectocomunitariov3/public/index.php?page=usuarios_formulario&editar=<?= $usuario['id_usuario'] ?>" class="btn" style="padding: 0.5rem 0.75rem; font-size: 0.875rem; background: white; border: 1px solid var(--border); color: var(--primary);">
                                                    <i class="fas fa-edit"></i> Editar
                                                </a>
                                                <button class="btn" onclick="abrirModalPassword(<?= $usuario['id_usuario'] ?>)" style="padding: 0.5rem 0.75rem; font-size: 0.875rem; background: white; border: 1px solid var(--border); color: var(--warning);">
                                                    <i class="fas fa-key"></i> Contraseña
                                                </button>
                                                <button type="button" class="btn" onclick="abrirModalEliminar(<?= $usuario['id_usuario'] ?>, '<?= htmlspecialchars(addslashes($usuario['nombre']), ENT_QUOTES) ?>', '<?= htmlspecialchars(addslashes($usuario['usuario']), ENT_QUOTES) ?>')" style="padding: 0.5rem 0.75rem; font-size: 0.875rem; background: white; border: 1px solid var(--border); color: var(--danger);">
                                                    <i class="fas fa-trash"></i> Eliminar
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="padding: 2rem; text-align: center; color: var(--text-muted);">
                                        No hay usuarios registrados.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal para eliminar usuario -->
    <div id="modalEliminar" class="modal-overlay">
        <div class="modal-content" style="max-width: 420px;">
            <div class="modal-header" style="border-bottom: 1px solid var(--border); background: rgba(239,68,68,0.05); border-radius: var(--radius-lg) var(--radius-lg) 0 0;">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <div style="width: 40px; height: 40px; border-radius: 50%; background: rgba(239,68,68,0.12); display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-trash" style="color: var(--danger); font-size: 1rem;"></i>
                    </div>
                    <h2 style="color: var(--danger); font-size: 1.1rem; margin: 0;">Eliminar Usuario</h2>
                </div>
                <button type="button" class="close-btn" onclick="cerrarModalEliminar()">&times;</button>
            </div>
            <form method="POST" action="/proyectocomunitariov3/src/Usuarios/Application/acciones.php">
                <div class="modal-body">
                    <input type="hidden" name="accion" value="eliminar">
                    <input type="hidden" name="id_usuario" id="eliminarUserId" value="">
                    <p style="color: var(--text-muted); font-size: 0.95rem; margin: 0 0 1rem 0;">¿Está seguro de que desea eliminar al siguiente usuario?</p>
                    <div style="background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-md); padding: 1rem; display: flex; align-items: center; gap: 0.75rem;">
                        <div style="width: 42px; height: 42px; border-radius: 50%; background: rgba(37,99,235,0.1); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <i class="fas fa-user" style="color: var(--primary);"></i>
                        </div>
                        <div>
                            <p id="eliminarNombre" style="font-weight: 600; color: var(--text-main); margin: 0; font-size: 0.95rem;"></p>
                            <p id="eliminarUsuario" style="color: var(--text-muted); margin: 0; font-size: 0.8rem;"></p>
                        </div>
                    </div>
                    <p style="color: var(--danger); font-size: 0.82rem; margin: 1rem 0 0 0;">
                        <i class="fas fa-exclamation-triangle"></i> Esta acción no se puede deshacer.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn" onclick="cerrarModalEliminar()" style="background: white; border: 1px solid var(--border);">Cancelar</button>
                    <button type="submit" class="btn" style="background: var(--danger); color: white; border: none;"><i class="fas fa-trash"></i> Sí, eliminar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal para cambiar contraseña -->
    <div id="modalPassword" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Cambiar Contraseña</h2>
                <button type="button" class="close-btn" onclick="cerrarModalPassword()">&times;</button>
            </div>
            <form method="POST" action="/proyectocomunitariov3/src/Usuarios/Application/acciones.php">
                <div class="modal-body">
                    <input type="hidden" name="accion" value="cambiar_password">
                    <input type="hidden" name="id_usuario" id="modalUserId" value="">
                    
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; font-size: 0.875rem; color: var(--text-main);">Nueva Contraseña</label>
                        <input type="password" name="password_nuevo" id="nuevaPassword" class="form-control" required minlength="1" placeholder="Nueva contraseña" style="width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--border); border-radius: var(--radius-md); font-family: inherit; font-size: 0.9rem;">
                    </div>
                    <div style="margin-bottom: 0.5rem;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; font-size: 0.875rem; color: var(--text-main);">Confirmar Contraseña</label>
                        <input type="password" name="password_confirmar" id="confirmarPassword" class="form-control" required minlength="1" placeholder="Repita la contraseña" style="width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--border); border-radius: var(--radius-md); font-family: inherit; font-size: 0.9rem;">
                        <p id="passwordError" style="color: var(--danger); font-size: 0.8rem; margin-top: 0.35rem; display: none;">Las contraseñas no coinciden.</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn" onclick="cerrarModalPassword()" style="background: white; border: 1px solid var(--border);">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Cambiar Contraseña</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function abrirModalPassword(idUsuario) {
            document.getElementById('modalUserId').value = idUsuario;
            document.getElementById('nuevaPassword').value = '';
            document.getElementById('confirmarPassword').value = '';
            document.getElementById('passwordError').style.display = 'none';
            document.getElementById('modalPassword').classList.add('active');
        }

        function cerrarModalPassword() {
            document.getElementById('modalPassword').classList.remove('active');
        }

        function abrirModalEliminar(id, nombre, usuario) {
            document.getElementById('eliminarUserId').value = id;
            document.getElementById('eliminarNombre').textContent = nombre;
            document.getElementById('eliminarUsuario').textContent = '@' + usuario;
            document.getElementById('modalEliminar').classList.add('active');
        }

        function cerrarModalEliminar() {
            document.getElementById('modalEliminar').classList.remove('active');
        }

        document.getElementById('modalEliminar').addEventListener('click', function(e) {
            if (e.target === this) cerrarModalEliminar();
        });

        document.getElementById('modalPassword').addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModalPassword();
            }
        });

        // Validar que las contraseñas coincidan antes de enviar
        document.querySelector('#modalPassword form').addEventListener('submit', function(e) {
            var p1 = document.getElementById('nuevaPassword').value;
            var p2 = document.getElementById('confirmarPassword').value;
            var err = document.getElementById('passwordError');
            if (p1 !== p2) {
                e.preventDefault();
                err.style.display = 'block';
                return;
            }
            if (p1.length < 1) {
                e.preventDefault();
                err.textContent = 'La contraseña es requerida.';
                err.style.display = 'block';
                return;
            }
            err.style.display = 'none';
        });
    </script>

</body>
</html>
