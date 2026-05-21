<?php
require_once __DIR__ . '/../../Shared/Infrastructure/Auth/require_admin.php';
require_once __DIR__ . '/../../Shared/Infrastructure/Database/Connection.php';

$pageTitle = "Formulario de Usuario";
$activePage = "usuarios";

$usuarioEditar = null;
$modo = 'nuevo';

// Cargar usuario si es edición
if (isset($_GET['editar'])) {
    $id_usuario = (int)$_GET['editar'];
    $sql = "SELECT u.id_usuario, u.nombre, u.usuario, u.id_rol, u.activo
            FROM usuario u 
            WHERE u.id_usuario = $1";
    
    $resultado = pg_query_params($conexion, $sql, [$id_usuario]);
    
    if ($resultado && pg_num_rows($resultado) > 0) {
        $usuarioEditar = pg_fetch_assoc($resultado);
        $modo = 'editar';
    }
}

// Cargar roles disponibles, excluyendo el rol de consulta
$sql_roles = "SELECT id_rol, nombre FROM rol WHERE LOWER(nombre) NOT IN ('consulta', 'consultor') ORDER BY nombre";
$resultado_roles = pg_query($conexion, $sql_roles);

if (!$resultado_roles) {
    die("Error en la consulta: " . pg_last_error($conexion));
}

$roles = [];
while ($row = pg_fetch_assoc($resultado_roles)) {
    $roles[] = $row;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SysComunal | <?= $modo == 'nuevo' ? 'Nuevo Usuario' : 'Editar Usuario' ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= $GLOBALS['ASSETS_URL'] ?>/Css/style.css">
    <style>
        .form-group { margin-bottom: 1.25rem; }
        .form-label { display: block; margin-bottom: 0.5rem; font-weight: 500; font-size: 0.875rem; color: var(--text-main); }
        .form-control {
            width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--border); border-radius: var(--radius-md); font-family: inherit; font-size: 0.9rem; transition: var(--transition-fast); background: var(--surface);
        }
        .form-control:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1); }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
        .checkbox-group { display: flex; align-items: center; gap: 0.75rem; }
        .checkbox-group input[type="checkbox"] { width: 18px; height: 18px; cursor: pointer; }
        @media (max-width: 768px) {
            .grid-2 { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <?php include '../../Shared/Ui/Layout/sidebar.php'; ?>

    <main class="main-wrapper">
        <?php include '../../Shared/Ui/Layout/header.php'; ?>

        <div class="content-container animate-fade-in">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <div>
                    <h1 style="font-size: 1.75rem; color: var(--text-main); margin-bottom: 0.5rem;">
                        <?= $modo == 'nuevo' ? 'Crear Nuevo Usuario' : 'Editar Usuario' ?>
                    </h1>
                    <p style="color: var(--text-muted);">
                        <?= $modo == 'nuevo' ? 'Registre un nuevo usuario en el sistema' : 'Modifique la información del usuario' ?>
                    </p>
                </div>
                <div>
                    <button type="submit" form="frmUsuario" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar Usuario
                    </button>
                    <a href="/proyectocomunitario/public/index.php?page=usuarios" class="btn" style="background: white; border: 1px solid var(--border); margin-left: 0.5rem;">Cancelar</a>
                </div>
            </div>

            <div class="glass-panel" style="padding: 2rem; max-width: 600px;">
                <form id="frmUsuario" action="/proyectocomunitario/src/Usuarios/Application/acciones.php" method="POST">
                    <input type="hidden" name="accion" value="<?= $modo ?>">
                    <input type="hidden" name="id_usuario" value="<?= $modo === 'editar' ? (int)$usuarioEditar['id_usuario'] : '' ?>">

                    <div class="form-group">
                        <label class="form-label">Nombre Completo *</label>
                           <input type="text" name="nombre" class="form-control" required maxlength="100"
                               pattern="[A-Za-zÁÉÍÓÚÜÑáéíóúüñ ]+"
                               oninput="this.value = this.value.replace(/[^A-Za-zÁÉÍÓÚÜÑáéíóúüñ ]/g, '')"
                               title="Solo se permiten letras y espacios."
                               value="<?= $modo === 'editar' ? htmlspecialchars($usuarioEditar['nombre']) : '' ?>"
                               placeholder="Ej. Juan Pérez López">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Usuario *</label>
                           <input type="text" name="usuario" class="form-control" required maxlength="100" pattern="[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]+"
                               oninput="this.value = this.value.replace(/[^A-Za-zÁÉÍÓÚÜÑáéíóúüñ]/g, '')"
                               title="Solo se permiten letras."
                               value="<?= $modo === 'editar' ? htmlspecialchars($usuarioEditar['usuario']) : '' ?>"
                               placeholder="Ej. jperez">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Rol *</label>
                        <select name="id_rol" class="form-control" required>
                            <option value="">Seleccione un rol</option>
                            <?php foreach ($roles as $rol): ?>
                                <?php $seleccionado = ($modo === 'editar' && (string)$usuarioEditar['id_rol'] === (string)$rol['id_rol']) ? 'selected' : ''; ?>
                                <option value="<?= $rol['id_rol'] ?>" <?= $seleccionado ?>>
                                    <?= htmlspecialchars($rol['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <div class="checkbox-group">
                            <?php
                            // PostgreSQL devuelve 't'/'f' para booleanos
                            // Para nuevo usuario activo por defecto; para edición leer el valor real
                            $valorActivo = $usuarioEditar['activo'] ?? 'f';
                            $estaActivo  = $modo === 'nuevo' || in_array($valorActivo, ['t', true, '1', 1], true);
                        ?>
                            <input type="checkbox" id="chkActivo" name="activo" value="1" <?= $estaActivo ? 'checked' : '' ?>>
                            <label for="chkActivo" class="form-label" style="margin-bottom: 0;">Usuario Activo</label>
                        </div>
                        <p style="color: var(--text-muted); font-size: 0.875rem; margin-top: 0.5rem;">Marque para habilitar este usuario en el sistema</p>
                    </div>

                    <?php if ($modo == 'nuevo'): ?>
                        <div class="form-group">
                            <label class="form-label">Contraseña *</label>
                            <input type="password" name="password_nuevo" id="passwordNuevo" class="form-control" required minlength="1" placeholder="Contraseña">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Confirmar Contraseña *</label>
                            <input type="password" name="password_confirmar" id="passwordConfirmar" class="form-control" required minlength="1" placeholder="Repita la contraseña">
                            <p id="passwordError" style="color: var(--danger); font-size: 0.8rem; margin-top: 0.35rem; display: none;">Las contraseñas no coinciden.</p>
                        </div>
                    <?php else: ?>
                        <div style="background: rgba(37, 99, 235, 0.08); border: 1px solid rgba(37, 99, 235, 0.2); border-radius: 8px; padding: 1rem; margin-top: 1rem;">
                            <p style="color: var(--text-muted); font-size: 0.875rem; margin: 0;">
                                <i class="fas fa-lock" style="color: var(--primary); margin-right: 0.5rem;"></i>
                                Para cambiar la contraseña, use el botón <strong>Contraseña</strong> en la lista de usuarios.
                            </p>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </main>

    <script>
        function esCaracterPermitido(caracter, permitirEspacios) {
            return permitirEspacios ? /^[\p{L} ]$/u.test(caracter) : /^[\p{L}]$/u.test(caracter);
        }

        function soloLetras(valor, permitirEspacios) {
            var patron = permitirEspacios ? /[^A-Za-zÁÉÍÓÚÜÑáéíóúüñ ]/g : /[^A-Za-zÁÉÍÓÚÜÑáéíóúüñ]/g;
            return valor.replace(patron, '');
        }

        function aplicarFiltroSoloLetras(selector, permitirEspacios) {
            var input = document.querySelector(selector);
            if (!input) {
                return;
            }

            input.addEventListener('keydown', function(e) {
                if (e.ctrlKey || e.metaKey || e.altKey) {
                    return;
                }

                var teclasPermitidas = [
                    'Backspace', 'Delete', 'Tab', 'ArrowLeft', 'ArrowRight',
                    'ArrowUp', 'ArrowDown', 'Home', 'End', 'Enter', 'Escape'
                ];

                if (teclasPermitidas.indexOf(e.key) !== -1) {
                    return;
                }

                if (e.key.length === 1 && !esCaracterPermitido(e.key, permitirEspacios)) {
                    e.preventDefault();
                }
            });

            input.addEventListener('beforeinput', function(e) {
                if (!e.data) {
                    return;
                }

                if (e.inputType && e.inputType.indexOf('insert') === 0 && !esCaracterPermitido(e.data, permitirEspacios) && !/^[\p{L} ]+$/u.test(e.data)) {
                    e.preventDefault();
                }
            });

            input.addEventListener('input', function() {
                this.value = soloLetras(this.value, permitirEspacios);
            });

            input.addEventListener('blur', function() {
                this.value = soloLetras(this.value.trim(), permitirEspacios);
            });

            input.addEventListener('paste', function(e) {
                e.preventDefault();
                var texto = (e.clipboardData || window.clipboardData).getData('text');
                var limpio = soloLetras(texto, permitirEspacios);
                document.execCommand('insertText', false, limpio);
            });
        }

        aplicarFiltroSoloLetras('[name="nombre"]', true);
        aplicarFiltroSoloLetras('[name="usuario"]', false);

        document.getElementById('frmUsuario').addEventListener('submit', function(e) {
            var nombre = document.querySelector('[name="nombre"]').value.trim();
            if (nombre.length === 0) {
                e.preventDefault();
                alert('El nombre es requerido.');
                return;
            }
            document.querySelector('[name="nombre"]').value = nombre.replace(/[^A-Za-zÁÉÍÓÚÜÑáéíóúüñ ]/g, '');
            var nombreValido = /^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ ]+$/.test(nombre);
            if (!nombreValido) {
                e.preventDefault();
                alert('El nombre solo debe contener letras y espacios.');
                return;
            }

            var usuarioField = document.querySelector('[name="usuario"]');
            usuarioField.value = usuarioField.value.replace(/[^A-Za-zÁÉÍÓÚÜÑáéíóúüñ]/g, '');
            if (usuarioField.value.trim().length === 0) {
                e.preventDefault();
                alert('El nombre de usuario es requerido.');
                usuarioField.focus();
                return;
            }
            if (!/^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]+$/.test(usuarioField.value.trim())) {
                e.preventDefault();
                alert('El nombre de usuario solo debe contener letras.');
                usuarioField.focus();
                return;
            }

            <?php if ($modo == 'nuevo'): ?>
            var p1 = document.getElementById('passwordNuevo').value;
            var p2 = document.getElementById('passwordConfirmar').value;
            var err = document.getElementById('passwordError');
            if (p1.length < 1) {
                e.preventDefault();
                err.textContent = 'La contraseña es requerida.';
                err.style.display = 'block';
                document.getElementById('passwordNuevo').focus();
                return;
            }
            if (p1 !== p2) {
                e.preventDefault();
                err.textContent = 'Las contraseñas no coinciden.';
                err.style.display = 'block';
                document.getElementById('passwordConfirmar').focus();
                return;
            }
            err.style.display = 'none';
            <?php endif; ?>
        });
    </script>

</body>
</html>
