<?php
require_once '../../Shared/Infrastructure/Database/Connection.php';

/** @var \PgSql\Connection|false $conexion */

$pageTitle = "Formulario de Comunero";
$activePage = "comuneros";

// Validar si es edición
$id_comunero = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$modo_edicion = $id_comunero > 0;

$comunero = [
    'numero_progresivo' => '',
    'nombre_completo' => '',
    'id_situacion' => '',
    'id_localidad' => '',
    'numero_ran' => '',
    'numero_certificado' => '',
    'lugar_residencia' => '',
    'telefono' => '',
    'observaciones' => '',
    'color_mapa' => '#3b82f6'
];
$sucesores = [];

if ($modo_edicion) {
    // Obtener datos del comunero
    $q_comunero = pg_query_params($conexion, "SELECT * FROM comunero WHERE id_comunero = $1", [$id_comunero]);
    if (pg_num_rows($q_comunero) > 0) {
        $comunero = pg_fetch_assoc($q_comunero);
        
        // Obtener sucesores
        $q_sucesores = pg_query_params($conexion, "SELECT * FROM sucesor WHERE id_comunero = $1", [$id_comunero]);
        while ($s = pg_fetch_assoc($q_sucesores)) {
            $sucesores[] = $s;
        }
    } else {
        header("Location: index.php");
        exit;
    }
} else {
    // Para registro nuevo, sugerir el siguiente número progresivo
    $q_max = pg_query($conexion, "SELECT COALESCE(MAX(numero_progresivo), 0) + 1 FROM comunero");
    $comunero['numero_progresivo'] = pg_fetch_result($q_max, 0, 0);
}

// Catalogos
$q_situaciones = pg_query($conexion, "SELECT * FROM situacion");
$q_localidades = pg_query($conexion, "SELECT * FROM localidad");

// Definir colores de referencia (pero permitir cualquier color)
$colores_referencia = [
    '#3b82f6' => 'Azul',
    '#10b981' => 'Verde',
    '#f59e0b' => 'Ámbar',
    '#ec4899' => 'Rosa',
    '#8b5cf6' => 'Púrpura',
    '#06b6d4' => 'Cyan',
    '#ef4444' => 'Rojo',
    '#6366f1' => 'Índigo',
    '#22d3ee' => 'Cielo',
    '#84cc16' => 'Lima',
    '#f97316' => 'Naranja',
    '#d946ef' => 'Magenta'
];

// Obtener colores en uso (excluyendo el comunero actual si está en edición)
$query_colores = "SELECT color_mapa, nombre_completo FROM comunero WHERE color_mapa IS NOT NULL";
if ($modo_edicion) {
    $query_colores .= " AND id_comunero != " . $id_comunero;
}
$resultado_colores = pg_query($conexion, $query_colores);
$comuneros_con_color = [];
while ($row = pg_fetch_assoc($resultado_colores)) {
    if ($row['color_mapa']) {
        $comuneros_con_color[$row['color_mapa']] = $row['nombre_completo'];
    }
}

// Si es nuevo registro sin color, generar uno aleatorio
if (!$modo_edicion && !$comunero['color_mapa']) {
    $comunero['color_mapa'] = '#' . substr(md5(time() . rand()), 0, 6);
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SysComunal | Formulario Comunero</title>
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
        @media (max-width: 768px) { .grid-2 { grid-template-columns: 1fr; } }
        .color-picker-group { display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap; }
        .color-picker-group input[type="color"] { width: 60px; height: 40px; cursor: pointer; border: 1px solid var(--border); border-radius: var(--radius-md); }
        .color-picker-group select { flex: 1; min-width: 200px; }
        .color-picker-group button { padding: 0.5rem 1rem; background: var(--primary); color: white; border: none; border-radius: var(--radius-md); cursor: pointer; font-weight: 500; transition: background 0.2s; }
        .color-picker-group button:hover { background: var(--primary-dark, #1e40af); }
        .color-preview-item { display: inline-block; margin-right: 1rem; margin-bottom: 0.5rem; }
        .color-preview-item span { display: inline-block; width: 14px; height: 14px; border-radius: 2px; vertical-align: middle; border: 1px solid var(--border); margin-right: 0.4rem; }
        .color-status { margin-top: 0.75rem; font-size: 0.85rem; color: var(--text-muted); }
    </style>
</head>
<body>

    <?php include '../../Shared/Ui/Layout/sidebar.php'; ?>

    <main class="main-wrapper">
        <?php include '../../Shared/Ui/Layout/header.php'; ?>

        <div class="content-container animate-fade-in">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <div>
                    <h1 style="font-size: 1.75rem; color: var(--text-main); margin-bottom: 0.5rem;"><?= $modo_edicion ? 'Editar' : 'Registrar' ?> Comunero</h1>
                    <p style="color: var(--text-muted);">Complete la información requerida del expediente</p>
                </div>
                <div>
                    <button type="submit" form="frmComunero" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar Registro
                    </button>
                    <a href="/proyectocomunitario/public/index.php?page=comuneros" class="btn" style="background: white; border: 1px solid var(--border); margin-left: 0.5rem;">Cancelar</a>
                </div>
            </div>

            <div id="notificacion" style="display: none; margin-bottom: 1.5rem; padding: 1rem; border-radius: var(--radius-md); border-left: 4px solid; font-weight: 500;"></div>

            <form id="frmComunero" onsubmit="return enviarFormularioAjax(event);">
                <input type="hidden" name="accion" value="<?= $modo_edicion ? 'editar' : 'nuevo' ?>">
                <input type="hidden" name="id_comunero" value="<?= $id_comunero ?>">
                <input type="hidden" name="ajax" value="1">

                <div class="glass-panel" style="padding: 2rem; margin-bottom: 2rem;">
                    <h3 style="margin-bottom: 1.5rem; color: var(--text-main); font-weight: 600; border-bottom: 1px solid var(--border); padding-bottom: 0.5rem;">Información Principal</h3>
                    
                    <div class="grid-2">
                        <div class="form-group">
                            <label class="form-label">Número Progresivo *</label>
                            <input type="number" name="numero_progresivo" class="form-control" value="<?= htmlspecialchars($comunero['numero_progresivo']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Nombre Completo *</label>
                            <input type="text" name="nombre_completo" class="form-control" value="<?= htmlspecialchars($comunero['nombre_completo']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Situación Agraria *</label>
                            <select name="id_situacion" class="form-control" required>
                                <option value="">Seleccione...</option>
                                <?php while ($row = pg_fetch_assoc($q_situaciones)): ?>
                                    <option value="<?= $row['id_situacion'] ?>" <?= $comunero['id_situacion'] == $row['id_situacion'] ? 'selected' : '' ?>><?= htmlspecialchars($row['descripcion']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Localidad / Paraje *</label>
                            <select name="id_localidad" class="form-control" required>
                                <option value="">Seleccione...</option>
                                <?php while ($row = pg_fetch_assoc($q_localidades)): ?>
                                    <option value="<?= $row['id_localidad'] ?>" <?= $comunero['id_localidad'] == $row['id_localidad'] ? 'selected' : '' ?>><?= htmlspecialchars($row['nombre']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Número R.A.N.</label>
                            <input type="text" name="numero_ran" class="form-control" value="<?= htmlspecialchars($comunero['numero_ran']) ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Número de Certificado</label>
                            <input type="text" name="numero_certificado" class="form-control" value="<?= htmlspecialchars($comunero['numero_certificado']) ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Lugar de Residencia Actual</label>
                        <input type="text" name="lugar_residencia" class="form-control" value="<?= htmlspecialchars($comunero['lugar_residencia']) ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Teléfono</label>
                        <input type="tel" name="telefono" id="telefono" class="form-control" value="<?= htmlspecialchars($comunero['telefono'] ?? '') ?>" placeholder="Ej. 1234567890" maxlength="10" pattern="^\d{10}$" inputmode="numeric" title="Ingrese solo 10 numeros">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Observaciones</label>
                        <textarea name="observaciones" class="form-control" rows="3"><?= htmlspecialchars($comunero['observaciones']) ?></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">🎨 Color en Mapa (Código hexadecimal - Único por comunero)</label>
                        <div class="color-picker-group">
                            <!-- Color picker HTML5 -->
                            <input type="color" id="color_mapa" name="color_mapa" value="<?= $comunero['color_mapa'] ?>" title="Haz clic para elegir cualquier color">
                            
                            <!-- Input hexadecimal manual -->
                            <input type="text" id="color_hex" class="form-control" placeholder="#RRGGBB" maxlength="7" style="flex: 0 1 auto; min-width: 120px; font-family: monospace;"
                                value="<?= $comunero['color_mapa'] ?>" 
                                onchange="syncColorInputs(this.value)"
                                pattern="^#[0-9A-Fa-f]{6}$"
                                title="Ingresa un código hexadecimal válido (ej: #FF5733)">
                            
                            <!-- Botón para generar color aleatorio -->
                            <button type="button" class="btn" onclick="generarColorAleatorio()" style="background: linear-gradient(135deg, var(--primary), #7c3aed); white-space: nowrap;">
                                🎲 Aleatorio
                            </button>
                        </div>

                        <!-- Preview del color seleccionado -->
                        <div class="color-status" style="margin-top: 1rem;">
                            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                                <div style="width: 60px; height: 60px; background: <?= $comunero['color_mapa'] ?>; border: 2px solid var(--border); border-radius: var(--radius-md); box-shadow: 0 2px 8px rgba(0,0,0,0.1);"></div>
                                <div>
                                    <strong style="display: block; color: var(--text-main); margin-bottom: 0.25rem;">Color actual:</strong>
                                    <code style="background: var(--surface); padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.85rem;" id="color_display"><?= $comunero['color_mapa'] ?></code>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="glass-panel" style="padding: 2rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 0.5rem; margin-bottom: 1.5rem;">
                        <h3 style="color: var(--text-main); font-weight: 600; margin: 0;">Lista de Sucesores</h3>
                        <button type="button" class="btn" style="background: rgba(16, 185, 129, 0.1); color: var(--secondary); font-size: 0.8rem; padding: 0.4rem 0.8rem;" onclick="addSucesor()">
                            <i class="fas fa-plus"></i> Agregar Sucesor
                        </button>
                    </div>

                    <div id="lista-sucesores">
                        <?php if (empty($sucesores)): ?>
                            <p id="msg-sin-sucesores" style="color: var(--text-muted); text-align: center; margin-bottom: 1rem; font-size: 0.875rem;">No hay sucesores registrados o capturados.</p>
                        <?php else: ?>
                            <?php foreach ($sucesores as $index => $s): ?>
                                <div class="sucesor-item grid-2" style="background: #f8fafc; padding: 1rem; border-radius: var(--radius-md); border: 1px solid var(--border); margin-bottom: 1rem; position: relative;">
                                    <input type="hidden" name="sucesores[<?= $index ?>][id_sucesor]" value="<?= $s['id_sucesor'] ?>">
                                    <div class="form-group" style="margin: 0;">
                                        <label class="form-label">Nombre del Sucesor</label>
                                        <input type="text" name="sucesores[<?= $index ?>][nombre]" class="form-control" value="<?= htmlspecialchars($s['nombre_sucesor']) ?>" required>
                                    </div>
                                    <div class="form-group" style="margin: 0;">
                                        <label class="form-label">Parentesco</label>
                                        <div style="display: flex; gap: 0.5rem;">
                                            <input type="text" name="sucesores[<?= $index ?>][parentesco]" class="form-control" value="<?= htmlspecialchars($s['parentesco']) ?>" required>
                                            <button type="button" class="btn" style="background: rgba(239, 68, 68, 0.1); color: var(--danger); padding: 0 1rem;" onclick="this.parentElement.parentElement.parentElement.remove()">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    </main>

    <script>
        let sucesorCount = <?= count($sucesores) ?>;

        // Mostrar notificación
        function mostrarNotificacion(tipo, mensaje) {
            const notif = document.getElementById('notificacion');
            notif.textContent = mensaje;
            notif.style.display = 'block';
            
            if (tipo === 'error') {
                notif.style.backgroundColor = '#fee2e2';
                notif.style.color = '#991b1b';
                notif.style.borderLeftColor = '#dc2626';
            } else if (tipo === 'success') {
                notif.style.backgroundColor = '#dcfce7';
                notif.style.color = '#15803d';
                notif.style.borderLeftColor = '#16a34a';
            } else if (tipo === 'warning') {
                notif.style.backgroundColor = '#fef3c7';
                notif.style.color = '#92400e';
                notif.style.borderLeftColor = '#f59e0b';
            }
            
            // Scroll hacia la notificación
            notif.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        // Enviar formulario por AJAX
        async function enviarFormularioAjax(event) {
            event.preventDefault();
            
            const color = document.getElementById('color_mapa').value;
            const telefonoInput = document.getElementById('telefono');
            const telefono = telefonoInput.value.trim();

            // Validar telefono: solo numeros y exactamente 10 digitos (si se captura)
            if (telefono !== '' && !/^\d{10}$/.test(telefono)) {
                mostrarNotificacion('error', '⚠️ El telefono debe tener exactamente 10 numeros.');
                telefonoInput.focus();
                return false;
            }
            
            // Validar que el color sea un hexadecimal válido
            if (!color.match(/^#[0-9A-Fa-f]{6}$/)) {
                mostrarNotificacion('error', '❌ El color debe ser un código hexadecimal válido (ej: #FF5733)');
                return false;
            }

            const form = document.getElementById('frmComunero');
            const formData = new FormData(form);

            try {
                const response = await fetch('/proyectocomunitario/src/Comuneros/Application/acciones.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    mostrarNotificacion('success', data.message);
                    
                    // Redirigir después de 2 segundos
                    setTimeout(() => {
                        window.location.href = '/proyectocomunitario/public/index.php?page=comuneros&msg=success';
                    }, 1500);
                } else {
                    mostrarNotificacion('error', data.error || 'Error desconocido al procesar la solicitud');
                }
            } catch (error) {
                mostrarNotificacion('error', '❌ Error de conexión: ' + error.message);
            }

            return false;
        }

        // Función para generar un color aleatorio de todo el espectro
        function generarColorAleatorio() {
            // Generar un color hexadecimal completamente aleatorio
            const randomColor = '#' + Math.floor(Math.random()*16777215).toString(16).padStart(6, '0').toUpperCase();
            
            // Sincronizar todos los inputs
            document.getElementById('color_mapa').value = randomColor;
            document.getElementById('color_hex').value = randomColor;
            document.getElementById('color_select').value = ''; // Desseleccionar referencia
            document.getElementById('color_display').textContent = randomColor;
            
            console.log('Color aleatorio generado:', randomColor);
        }

        // Función para sincronizar los inputs de color
        function syncColorInputs(hexValue) {
            // Validar formato hexadecimal
            if (!hexValue.match(/^#[0-9A-Fa-f]{6}$/)) {
                return false;
            }
            
            // Normalizar a mayúsculas
            hexValue = hexValue.toUpperCase();
            
            // Sincronizar color picker y display
            document.getElementById('color_mapa').value = hexValue;
            document.getElementById('color_hex').value = hexValue;
            document.getElementById('color_display').textContent = hexValue;
            document.getElementById('color_select').value = ''; // Desseleccionar referencia
            
            return true;
        }

        // Función para aplicar color desde el dropdown de referencia
        function applyColorFromSelect(colorValue) {
            if (colorValue) {
                document.getElementById('color_mapa').value = colorValue;
                document.getElementById('color_hex').value = colorValue;
                document.getElementById('color_display').textContent = colorValue;
            }
        }

        // Sincronizar color picker con input hexadecimal
        document.getElementById('color_mapa').addEventListener('change', function() {
            document.getElementById('color_hex').value = this.value;
            document.getElementById('color_display').textContent = this.value.toUpperCase();
            document.getElementById('color_select').value = '';
        });

        document.getElementById('color_hex').addEventListener('input', function() {
            // Validar mientras el usuario escribe
            if (this.value.match(/^#[0-9A-Fa-f]{6}$/)) {
                document.getElementById('color_mapa').value = this.value;
                document.getElementById('color_display').textContent = this.value.toUpperCase();
                document.getElementById('color_select').value = '';
            }
        });

        function addSucesor() {
            const container = document.getElementById('lista-sucesores');
            const msg = document.getElementById('msg-sin-sucesores');
            if(msg) msg.style.display = 'none';

            const html = `
                <div class="sucesor-item grid-2 animate-fade-in" style="background: #f8fafc; padding: 1rem; border-radius: var(--radius-md); border: 1px solid var(--border); margin-bottom: 1rem;">
                    <input type="hidden" name="sucesores[${sucesorCount}][id_sucesor]" value="0">
                    <div class="form-group" style="margin: 0;">
                        <label class="form-label">Nombre del Sucesor</label>
                        <input type="text" name="sucesores[${sucesorCount}][nombre]" class="form-control" required>
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <label class="form-label">Parentesco</label>
                        <div style="display: flex; gap: 0.5rem;">
                            <input type="text" name="sucesores[${sucesorCount}][parentesco]" class="form-control" required>
                            <button type="button" class="btn" style="background: rgba(239, 68, 68, 0.1); color: var(--danger); padding: 0 1rem;" onclick="this.parentElement.parentElement.parentElement.remove()">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            container.insertAdjacentHTML('beforeend', html);
            sucesorCount++;
        }
    </script>
</body>
</html>
