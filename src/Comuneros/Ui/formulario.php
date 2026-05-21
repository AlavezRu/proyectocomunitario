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
        .notification-card {
            display: none;
            align-items: flex-start;
            gap: 0.9rem;
            margin-bottom: 1.5rem;
            padding: 1rem 1.1rem;
            border-radius: var(--radius-lg);
            border: 1px solid transparent;
            box-shadow: var(--shadow-sm);
            animation: slideDown 0.2s ease-out;
        }
        .notification-card.show { display: flex; }
        .notification-card__icon {
            width: 2.35rem;
            height: 2.35rem;
            flex: 0 0 auto;
            border-radius: 999px;
            display: grid;
            place-items: center;
            font-size: 1rem;
        }
        .notification-card__title {
            margin: 0 0 0.2rem 0;
            font-size: 0.95rem;
            font-weight: 700;
        }
        .notification-card__message {
            margin: 0;
            font-size: 0.92rem;
            line-height: 1.45;
        }
        .notification-card.error {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.12), rgba(239, 68, 68, 0.06));
            border-color: rgba(239, 68, 68, 0.2);
            color: #7f1d1d;
        }
        .notification-card.error .notification-card__icon {
            background: rgba(239, 68, 68, 0.16);
            color: var(--danger);
        }
        .notification-card.success {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.12), rgba(16, 185, 129, 0.06));
            border-color: rgba(16, 185, 129, 0.2);
            color: #065f46;
        }
        .notification-card.success .notification-card__icon {
            background: rgba(16, 185, 129, 0.16);
            color: var(--secondary);
        }
        .notification-card.warning {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.12), rgba(245, 158, 11, 0.06));
            border-color: rgba(245, 158, 11, 0.22);
            color: #92400e;
        }
        .notification-card.warning .notification-card__icon {
            background: rgba(245, 158, 11, 0.16);
            color: var(--warning);
        }
        @keyframes slideDown {
            from {
                transform: translateY(-8px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
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

            <div id="notificacion" class="notification-card" aria-live="polite" aria-atomic="true"></div>

            <form id="frmComunero" onsubmit="return enviarFormularioAjax(event);">
                <input type="hidden" name="accion" value="<?= $modo_edicion ? 'editar' : 'nuevo' ?>">
                <input type="hidden" name="id_comunero" value="<?= $id_comunero ?>">
                <input type="hidden" name="ajax" value="1">

                <div class="glass-panel" style="padding: 2rem; margin-bottom: 2rem;">
                    <h3 style="margin-bottom: 1.5rem; color: var(--text-main); font-weight: 600; border-bottom: 1px solid var(--border); padding-bottom: 0.5rem;">Información Principal</h3>
                    
                    <div class="grid-2">
                        <div class="form-group">
                            <label class="form-label">Número Progresivo *</label>
                            <input type="number" name="numero_progresivo_display" class="form-control" value="<?= htmlspecialchars($comunero['numero_progresivo']) ?>" readonly style="background-color: var(--surface-muted); cursor: not-allowed; opacity: 0.7;">
                            <input type="hidden" name="numero_progresivo" value="<?= htmlspecialchars($comunero['numero_progresivo']) ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Nombre Completo *</label>
                            <input type="text" name="nombre_completo" class="form-control" value="<?= htmlspecialchars($comunero['nombre_completo']) ?>" required maxlength="100" pattern="[A-Za-zÁÉÍÓÚÜÑáéíóúüñ ]+" oninput="this.value = this.value.replace(/[^A-Za-zÁÉÍÓÚÜÑáéíóúüñ ]/g, '')" title="Solo se permiten letras y espacios.">
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

                        <!-- Preview del color actual y del nuevo color seleccionado -->
                        <div class="color-status" style="margin-top: 1rem;">
                            <div style="display: flex; align-items: center; gap: 1.5rem; margin-bottom: 1rem; flex-wrap: wrap;">
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <div id="color_actual_preview" style="width: 60px; height: 60px; background: <?= $comunero['color_mapa'] ?>; border: 2px solid var(--border); border-radius: var(--radius-md); box-shadow: 0 2px 8px rgba(0,0,0,0.1);"></div>
                                    <div>
                                        <strong style="display: block; color: var(--text-main); margin-bottom: 0.25rem;">Color actual:</strong>
                                        <code style="background: var(--surface); padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.85rem;"><?= strtoupper($comunero['color_mapa']) ?></code>
                                    </div>
                                </div>
                                <div style="font-size: 1.1rem; color: var(--text-muted);">→</div>
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <div id="color_preview_large" style="width: 60px; height: 60px; background: <?= $comunero['color_mapa'] ?>; border: 2px solid var(--border); border-radius: var(--radius-md); box-shadow: 0 2px 8px rgba(0,0,0,0.1);"></div>
                                    <div>
                                        <strong style="display: block; color: var(--text-main); margin-bottom: 0.25rem;">Color nuevo / aleatorio:</strong>
                                        <code style="background: var(--surface); padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.85rem;" id="color_display"><?= strtoupper($comunero['color_mapa']) ?></code>
                                    </div>
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
                                        <input type="text" name="sucesores[<?= $index ?>][nombre]" class="form-control" value="<?= htmlspecialchars($s['nombre_sucesor']) ?>" required maxlength="100" pattern="[A-Za-zÁÉÍÓÚÜÑáéíóúüñ ]+" oninput="this.value = this.value.replace(/[^A-Za-zÁÉÍÓÚÜÑáéíóúüñ ]/g, '')" title="Solo se permiten letras y espacios.">
                                    </div>
                                    <div class="form-group" style="margin: 0;">
                                        <label class="form-label">Parentesco</label>
                                        <div style="display: flex; gap: 0.5rem;">
                                            <select name="sucesores[<?= $index ?>][parentesco]" class="form-control" required>
                                                <option value="">Seleccione parentesco</option>
                                                <option value="Padre" <?= $s['parentesco'] == 'Padre' ? 'selected' : '' ?>>Padre</option>
                                                <option value="Madre" <?= $s['parentesco'] == 'Madre' ? 'selected' : '' ?>>Madre</option>
                                                <option value="Abuelo" <?= $s['parentesco'] == 'Abuelo' ? 'selected' : '' ?>>Abuelo</option>
                                                <option value="Abuela" <?= $s['parentesco'] == 'Abuela' ? 'selected' : '' ?>>Abuela</option>
                                                <option value="Hijo" <?= $s['parentesco'] == 'Hijo' ? 'selected' : '' ?>>Hijo</option>
                                                <option value="Hija" <?= $s['parentesco'] == 'Hija' ? 'selected' : '' ?>>Hija</option>
                                                <option value="Nieto" <?= $s['parentesco'] == 'Nieto' ? 'selected' : '' ?>>Nieto</option>
                                                <option value="Nieta" <?= $s['parentesco'] == 'Nieta' ? 'selected' : '' ?>>Nieta</option>
                                                <option value="Hermano" <?= $s['parentesco'] == 'Hermano' ? 'selected' : '' ?>>Hermano</option>
                                                <option value="Hermana" <?= $s['parentesco'] == 'Hermana' ? 'selected' : '' ?>>Hermana</option>
                                                <option value="Tío" <?= $s['parentesco'] == 'Tío' ? 'selected' : '' ?>>Tío</option>
                                                <option value="Tía" <?= $s['parentesco'] == 'Tía' ? 'selected' : '' ?>>Tía</option>
                                                <option value="Primo" <?= $s['parentesco'] == 'Primo' ? 'selected' : '' ?>>Primo</option>
                                                <option value="Prima" <?= $s['parentesco'] == 'Prima' ? 'selected' : '' ?>>Prima</option>
                                                <option value="Sobrino" <?= $s['parentesco'] == 'Sobrino' ? 'selected' : '' ?>>Sobrino</option>
                                                <option value="Sobrina" <?= $s['parentesco'] == 'Sobrina' ? 'selected' : '' ?>>Sobrina</option>
                                                <option value="Padrastro" <?= $s['parentesco'] == 'Padrastro' ? 'selected' : '' ?>>Padrastro</option>
                                                <option value="Madrastra" <?= $s['parentesco'] == 'Madrastra' ? 'selected' : '' ?>>Madrastra</option>
                                                <option value="Hijastro" <?= $s['parentesco'] == 'Hijastro' ? 'selected' : '' ?>>Hijastro</option>
                                                <option value="Hijastra" <?= $s['parentesco'] == 'Hijastra' ? 'selected' : '' ?>>Hijastra</option>
                                                <option value="Cuñado" <?= $s['parentesco'] == 'Cuñado' ? 'selected' : '' ?>>Cuñado</option>
                                                <option value="Cuñada" <?= $s['parentesco'] == 'Cuñada' ? 'selected' : '' ?>>Cuñada</option>
                                                <option value="Suegro" <?= $s['parentesco'] == 'Suegro' ? 'selected' : '' ?>>Suegro</option>
                                                <option value="Suegra" <?= $s['parentesco'] == 'Suegra' ? 'selected' : '' ?>>Suegra</option>
                                                <option value="Yerno" <?= $s['parentesco'] == 'Yerno' ? 'selected' : '' ?>>Yerno</option>
                                                <option value="Nuera" <?= $s['parentesco'] == 'Nuera' ? 'selected' : '' ?>>Nuera</option>
                                                <option value="Esposo" <?= $s['parentesco'] == 'Esposo' ? 'selected' : '' ?>>Esposo</option>
                                                <option value="Esposa" <?= $s['parentesco'] == 'Esposa' ? 'selected' : '' ?>>Esposa</option>
                                                <option value="Otro" <?= $s['parentesco'] == 'Otro' ? 'selected' : '' ?>>Otro</option>
                                            </select>
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

        function escaparHtml(texto) {
            return String(texto)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        // Mostrar notificación
        function mostrarNotificacion(tipo, mensaje) {
            const notif = document.getElementById('notificacion');
            const config = {
                error: { icon: 'fa-circle-exclamation', title: 'No se pudo guardar' },
                success: { icon: 'fa-circle-check', title: 'Operación exitosa' },
                warning: { icon: 'fa-triangle-exclamation', title: 'Atención' }
            }[tipo] || { icon: 'fa-circle-info', title: 'Aviso' };

            notif.className = `notification-card show ${tipo}`;
            notif.innerHTML = `
                <div class="notification-card__icon"><i class="fa-solid ${config.icon}"></i></div>
                <div class="notification-card__content">
                    <p class="notification-card__title">${escaparHtml(config.title)}</p>
                    <p class="notification-card__message">${escaparHtml(mensaje)}</p>
                </div>
            `;
            
            // Scroll hacia la notificación
            notif.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        // Enviar formulario por AJAX
        async function enviarFormularioAjax(event) {
            event.preventDefault();
            
            const color = document.getElementById('color_mapa').value;
            const telefonoInput = document.getElementById('telefono');
            const telefono = telefonoInput.value.trim();
            const nombreInput = document.querySelector('[name="nombre_completo"]');
            const nombre = nombreInput.value.trim();

            if (nombre === '') {
                mostrarNotificacion('error', '⚠️ El nombre completo es requerido.');
                nombreInput.focus();
                return false;
            }
            if (!/^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ ]+$/.test(nombre)) {
                mostrarNotificacion('error', '⚠️ El nombre completo solo debe contener letras y espacios.');
                nombreInput.focus();
                return false;
            }

            const nombresSucesores = Array.from(document.querySelectorAll('input[name$="[nombre]"]'));
            for (const input of nombresSucesores) {
                const valor = input.value.trim();
                if (valor !== '' && !/^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ ]+$/.test(valor)) {
                    mostrarNotificacion('error', '⚠️ El nombre del sucesor solo debe contener letras y espacios.');
                    input.focus();
                    return false;
                }
            }

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

        const colorPreviewNuevo = document.getElementById('color_preview_large');
        const colorDisplayNuevo = document.getElementById('color_display');
        const colorPickerInput = document.getElementById('color_mapa');
        const colorHexInput = document.getElementById('color_hex');

        function clearColorReferenceSelection() {
            const colorSelect = document.getElementById('color_select');
            if (colorSelect) {
                colorSelect.value = '';
            }
        }

        // Función para generar un color aleatorio de todo el espectro
        function generarColorAleatorio() {
            // Generar un color hexadecimal completamente aleatorio
            const randomColor = '#' + Math.floor(Math.random()*16777215).toString(16).padStart(6, '0').toUpperCase();
            
            // Sincronizar todos los inputs
            colorPickerInput.value = randomColor;
            colorHexInput.value = randomColor;
            clearColorReferenceSelection();
            updateColorPreview(randomColor);
            
            console.log('Color aleatorio generado:', randomColor);
        }

        function updateColorPreview(hexValue) {
            const normalized = hexValue.toUpperCase();
            colorDisplayNuevo.textContent = normalized;
            colorPreviewNuevo.style.backgroundColor = normalized;
            colorPreviewNuevo.style.background = normalized;
            colorPreviewNuevo.setAttribute('data-color', normalized);
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
            colorPickerInput.value = hexValue;
            colorHexInput.value = hexValue;
            updateColorPreview(hexValue);
            clearColorReferenceSelection();
            
            return true;
        }

        // Función para aplicar color desde el dropdown de referencia
        function applyColorFromSelect(colorValue) {
            if (colorValue) {
                colorPickerInput.value = colorValue;
                colorHexInput.value = colorValue;
                updateColorPreview(colorValue);
                clearColorReferenceSelection();
            }
        }

        // Sincronizar color picker con input hexadecimal
        colorPickerInput.addEventListener('input', function() {
            colorHexInput.value = this.value;
            updateColorPreview(this.value);
            clearColorReferenceSelection();
        });

        colorPickerInput.addEventListener('change', function() {
            colorHexInput.value = this.value;
            updateColorPreview(this.value);
            clearColorReferenceSelection();
        });

        colorHexInput.addEventListener('input', function() {
            // Validar mientras el usuario escribe
            if (this.value.match(/^#[0-9A-Fa-f]{6}$/)) {
                colorPickerInput.value = this.value;
                updateColorPreview(this.value);
                clearColorReferenceSelection();
            }
        });

        updateColorPreview(colorPickerInput.value);

        function handleParentescoSelect(select) {
            select.addEventListener('change', function() {
                const opcionDefault = this.querySelector('option[value=""]');
                if (this.value !== '' && opcionDefault) {
                    opcionDefault.disabled = true;
                }
            });
        }

        function addSucesor() {
            const container = document.getElementById('lista-sucesores');
            const msg = document.getElementById('msg-sin-sucesores');
            if(msg) msg.style.display = 'none';

            const html = `
                <div class="sucesor-item grid-2 animate-fade-in" style="background: #f8fafc; padding: 1rem; border-radius: var(--radius-md); border: 1px solid var(--border); margin-bottom: 1rem;">
                    <input type="hidden" name="sucesores[${sucesorCount}][id_sucesor]" value="0">
                    <div class="form-group" style="margin: 0;">
                        <label class="form-label">Nombre del Sucesor</label>
                        <input type="text" name="sucesores[${sucesorCount}][nombre]" class="form-control" required maxlength="100" pattern="[A-Za-zÁÉÍÓÚÜÑáéíóúüñ ]+" oninput="this.value = this.value.replace(/[^A-Za-zÁÉÍÓÚÜÑáéíóúüñ ]/g, '')" title="Solo se permiten letras y espacios.">
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <label class="form-label">Parentesco</label>
                        <div style="display: flex; gap: 0.5rem;">
                            <select name="sucesores[${sucesorCount}][parentesco]" class="form-control parentesco-select" required>
                                <option value="">Seleccione parentesco</option>
                                <option value="Padre">Padre</option>
                                <option value="Madre">Madre</option>
                                <option value="Abuelo">Abuelo</option>
                                <option value="Abuela">Abuela</option>
                                <option value="Hijo">Hijo</option>
                                <option value="Hija">Hija</option>
                                <option value="Nieto">Nieto</option>
                                <option value="Nieta">Nieta</option>
                                <option value="Hermano">Hermano</option>
                                <option value="Hermana">Hermana</option>
                                <option value="Tío">Tío</option>
                                <option value="Tía">Tía</option>
                                <option value="Primo">Primo</option>
                                <option value="Prima">Prima</option>
                                <option value="Sobrino">Sobrino</option>
                                <option value="Sobrina">Sobrina</option>
                                <option value="Padrastro">Padrastro</option>
                                <option value="Madrastra">Madrastra</option>
                                <option value="Hijastro">Hijastro</option>
                                <option value="Hijastra">Hijastra</option>
                                <option value="Cuñado">Cuñado</option>
                                <option value="Cuñada">Cuñada</option>
                                <option value="Suegro">Suegro</option>
                                <option value="Suegra">Suegra</option>
                                <option value="Yerno">Yerno</option>
                                <option value="Nuera">Nuera</option>
                                <option value="Esposo">Esposo</option>
                                <option value="Esposa">Esposa</option>
                                <option value="Otro">Otro</option>
                            </select>
                            <button type="button" class="btn" style="background: rgba(239, 68, 68, 0.1); color: var(--danger); padding: 0 1rem;" onclick="this.parentElement.parentElement.parentElement.remove()">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            container.insertAdjacentHTML('beforeend', html);
            // Aplicar el handler al nuevo select
            const nuevosSelects = container.querySelectorAll('.parentesco-select');
            handleParentescoSelect(nuevosSelects[nuevosSelects.length - 1]);
            sucesorCount++;
        }
        // Al cargar la página, aplicar el handler a los selects existentes
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.parentesco-select').forEach(handleParentescoSelect);
        });
    </script>
</body>
</html>
