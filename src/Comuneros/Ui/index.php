<?php
require_once '../../Shared/Infrastructure/Database/Connection.php';
require_once '../../Shared/Infrastructure/Auth/Session.php';

Session::iniciar();
$esAdmin = Session::esAdmin();

// Ayuda a Intelephense a reconocer la variable definida en el archivo de conexión.
$conexion = $conexion ?? null;
if ($conexion === null) {
    throw new RuntimeException('No se inicializo la conexion a la base de datos.');
}

$pageTitle = "Comuneros y Avecindados";
$activePage = "comuneros";

// Obtener tab actual (activos por defecto)
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'activos';

// Obtener comuneros activos
$query_activos = "
    SELECT c.id_comunero, c.numero_progresivo, c.nombre_completo, c.telefono, s.descripcion as situacion, l.nombre as localidad, c.activo,
           c.numero_ran, c.numero_certificado, c.lugar_residencia, c.observaciones, c.color_mapa
    FROM comunero c
    JOIN situacion s ON c.id_situacion = s.id_situacion
    JOIN localidad l ON c.id_localidad = l.id_localidad
    WHERE c.activo = TRUE
    ORDER BY c.numero_progresivo ASC
";
$resultado_activos = pg_query($conexion, $query_activos);

// Obtener comuneros inactivos
$query_inactivos = "
    SELECT c.id_comunero, c.numero_progresivo, c.nombre_completo, c.telefono, s.descripcion as situacion, l.nombre as localidad, c.activo,
           c.numero_ran, c.numero_certificado, c.lugar_residencia, c.observaciones, c.color_mapa
    FROM comunero c
    JOIN situacion s ON c.id_situacion = s.id_situacion
    JOIN localidad l ON c.id_localidad = l.id_localidad
    WHERE c.activo = FALSE
    ORDER BY c.numero_progresivo ASC
";
$resultado_inactivos = pg_query($conexion, $query_inactivos);

// Obtener sucesores agrupados por comunero
$resultado_sucesores_raw = pg_query($conexion, "SELECT id_comunero, nombre_sucesor, parentesco FROM sucesor ORDER BY id_comunero");
$sucesores_map = [];
if ($resultado_sucesores_raw) {
    while ($s = pg_fetch_assoc($resultado_sucesores_raw)) {
        $sucesores_map[(int)$s['id_comunero']][] = ['nombre' => $s['nombre_sucesor'], 'parentesco' => $s['parentesco']];
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SysComunal | Comuneros</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= $GLOBALS['ASSETS_URL'] ?>/Css/style.css">
    <style>
        .modal-delete {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.2s ease-in-out;
        }
        .modal-delete.active {
            display: flex;
        }
        .modal-delete-content {
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            padding: 2rem;
            max-width: 450px;
            width: 90%;
            animation: slideUp 0.3s ease-out;
        }
        .modal-delete-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        .modal-delete-icon {
            width: 50px;
            height: 50px;
            background: rgba(239, 68, 68, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--danger);
            font-size: 1.5rem;
        }
        .modal-delete-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-main);
            margin: 0;
        }
        .modal-delete-description {
            color: var(--text-muted);
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
            line-height: 1.5;
        }
        .modal-delete-info {
            background: rgba(239, 68, 68, 0.05);
            border-left: 4px solid var(--danger);
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            color: var(--text-main);
        }
        .modal-delete-info strong {
            display: block;
            margin-bottom: 0.25rem;
            color: var(--danger);
        }
        .modal-delete-footer {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }
        .btn-modal {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: var(--radius-md);
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s;
            font-size: 0.9rem;
        }
        .btn-modal-cancel {
            background: var(--surface);
            border: 1px solid var(--border);
            color: var(--text-main);
        }
        .btn-modal-cancel:hover {
            background: #f1f5f9;
        }
        .btn-modal-delete {
            background: var(--danger);
            color: white;
        }
        .btn-modal-delete:hover {
            background: #dc2626;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes slideUp {
            from {
                transform: translateY(20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        /* Modal Ver Comunero */
        .modal-ver {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.2s ease-in-out;
        }
        .modal-ver.active { display: flex; }
        .modal-ver-content {
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            padding: 2rem;
            max-width: 600px;
            width: 95%;
            max-height: 90vh;
            overflow-y: auto;
            animation: slideUp 0.3s ease-out;
        }
        .modal-ver-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid var(--border);
            padding-bottom: 1rem;
        }
        .modal-ver-avatar {
            width: 52px; height: 52px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            color: white;
            font-weight: 700;
            flex-shrink: 0;
        }
        .modal-ver-header h2 {
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--text-main);
            margin: 0 0 0.25rem 0;
        }
        .modal-ver-header p {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin: 0;
        }
        .modal-ver-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        .modal-ver-field { display: flex; flex-direction: column; gap: 0.2rem; }
        .modal-ver-field.full { grid-column: 1 / -1; }
        .modal-ver-label {
            font-size: 0.72rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .modal-ver-value { font-size: 0.93rem; color: var(--text-main); font-weight: 500; }
        .modal-ver-value.empty { color: var(--text-muted); font-style: italic; font-weight: 400; }
        .modal-ver-section {
            font-size: 0.78rem;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin: 1.25rem 0 0.6rem;
            padding-bottom: 0.4rem;
            border-bottom: 1px solid var(--border);
        }
        .sucesor-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            background: rgba(37,99,235,0.08);
            color: var(--primary);
            padding: 0.3rem 0.7rem;
            border-radius: 999px;
            font-size: 0.8rem;
            font-weight: 500;
            margin: 0.2rem;
        }
        @media (max-width: 500px) { .modal-ver-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

    <?php include '../../Shared/Ui/Layout/sidebar.php'; ?>

    <main class="main-wrapper">
        <?php include '../../Shared/Ui/Layout/header.php'; ?>

        <div class="content-container animate-fade-in">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <div>
                    <h1 style="font-size: 1.75rem; color: var(--text-main); margin-bottom: 0.5rem;">Gestión de Comuneros</h1>
                    <p style="color: var(--text-muted);">Directorio completo de comuneros y avecindados</p>
                </div>
                <div>
                    <a href="/proyectocomunitario/public/index.php?page=comuneros_formulario" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Registrar Nuevo
                    </a>
                </div>
            </div>

            <?php
                $mensaje = '';
                $tipoMensaje = '';
                if (isset($_GET['msg'])) {
                    $tipoMensaje = 'ok';
                    switch ($_GET['msg']) {
                        case 'success':
                            $mensaje = 'Comunero guardado correctamente.';
                            break;
                        case 'deleted':
                            $mensaje = 'Comunero desactivado correctamente.';
                            break;
                        case 'reactivated':
                            $mensaje = 'Comunero reactivado correctamente.';
                            break;
                        case 'deleted_total':
                            $mensaje = 'Comunero eliminado definitivamente junto con sus actas y registros relacionados.';
                            break;
                        default:
                            $mensaje = 'Operación realizada correctamente.';
                    }
                } elseif (isset($_GET['error'])) {
                    $tipoMensaje = 'error';
                    $mensaje = $_GET['error'];
                }
            ?>

            <?php if (!empty($mensaje)): ?>
                <div style="background: <?= $tipoMensaje === 'ok' ? 'rgba(16, 185, 129, 0.1)' : 'rgba(239, 68, 68, 0.1)' ?>; border: 1px solid <?= $tipoMensaje === 'ok' ? '#10b981' : '#ef4444' ?>; border-radius: 8px; padding: 1rem; margin-bottom: 1.5rem; color: <?= $tipoMensaje === 'ok' ? '#059669' : '#991b1b' ?>;">
                    <i class="fas <?= $tipoMensaje === 'ok' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
                    <?= htmlspecialchars($mensaje) ?>
                </div>
            <?php endif; ?>

            <!-- Pestañas de Estado -->
            <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem; border-bottom: 2px solid var(--border);">
                <button class="tab-button <?= $tab === 'activos' ? 'active' : '' ?>" 
                        onclick="window.location.href='/proyectocomunitario/public/index.php?page=comuneros&tab=activos';"
                        style="padding: 0.75rem 1.5rem; border: none; background: none; cursor: pointer; color: var(--text-muted); font-weight: 600; font-size: 0.95rem; border-bottom: 3px solid transparent; transition: all 0.2s;">
                    <i class="fas fa-check-circle"></i> Comuneros Activos (<?= pg_num_rows($resultado_activos) ?>)
                </button>
                <button class="tab-button <?= $tab === 'inactivos' ? 'active' : '' ?>" 
                        onclick="window.location.href='/proyectocomunitario/public/index.php?page=comuneros&tab=inactivos';"
                        style="padding: 0.75rem 1.5rem; border: none; background: none; cursor: pointer; color: var(--text-muted); font-weight: 600; font-size: 0.95rem; border-bottom: 3px solid transparent; transition: all 0.2s;">
                    <i class="fas fa-ban"></i> Comuneros Inactivos (<?= pg_num_rows($resultado_inactivos) ?>)
                </button>
            </div>

            <div class="glass-panel" style="padding: 1.5rem;">
                <div style="margin-bottom: 1.5rem;">
                    <input type="text" id="busquedaInput" placeholder="Buscar por nombre o número progresivo..." style="width: 100%; padding: 0.75rem; border: 1px solid var(--border); border-radius: var(--radius-md); font-size: 0.875rem;">
                </div>

                <!-- Tab: Comuneros Activos -->
                <?php if ($tab === 'activos'): ?>
                <div style="overflow-x: auto; max-height: 600px;">
                    <table style="width: 100%; border-collapse: collapse; text-align: left;">
                        <thead>
                            <tr style="border-bottom: 2px solid var(--border); color: var(--text-muted); font-weight: 600; font-size: 0.875rem;">
                                <th style="padding: 1rem 0.5rem;">N°</th>
                                <th style="padding: 1rem 0.5rem;">Nombre Completo</th>
                                <th style="padding: 1rem 0.5rem;">Teléfono</th>
                                <th style="padding: 1rem 0.5rem;">Estatus</th>
                                <th style="padding: 1rem 0.5rem;">Localidad</th>
                                <th style="padding: 1rem 0.5rem; text-align: center;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (pg_num_rows($resultado_activos) > 0): ?>
                                <?php while ($row = pg_fetch_assoc($resultado_activos)): ?>
                                    <tr class="fila-comunero" data-nombre="<?= strtolower(htmlspecialchars($row['nombre_completo'])) ?>" data-numero="<?= str_pad($row['numero_progresivo'], 4, '0', STR_PAD_LEFT) ?>" style="border-bottom: 1px solid var(--border); transition: var(--transition-fast);" onmouseover="this.style.backgroundColor='#f1f5f9'" onmouseout="this.style.backgroundColor='transparent'">
                                        <td style="padding: 1rem 0.5rem; font-weight: 500;"><?= str_pad($row['numero_progresivo'], 4, '0', STR_PAD_LEFT) ?></td>
                                        <td style="padding: 1rem 0.5rem;"><?= htmlspecialchars($row['nombre_completo']) ?></td>
                                        <td style="padding: 1rem 0.5rem;">
                                            <?php if (!empty($row['telefono'])): ?>
                                                <?php $telefonoLimpio = preg_replace('/\D/', '', $row['telefono']); ?>
                                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                                    <a href="tel:<?= htmlspecialchars($row['telefono']) ?>" style="color: var(--primary); text-decoration: none; font-weight: 500;">
                                                        <i class="fas fa-phone"></i> <?= htmlspecialchars($row['telefono']) ?>
                                                    </a>
                                                    <a href="https://wa.me/52<?= $telefonoLimpio ?>" target="_blank" rel="noopener noreferrer" title="Enviar mensaje por WhatsApp" style="display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; background: rgba(37, 211, 102, 0.12); color: #25d366; border-radius: 50%; text-decoration: none; font-size: 0.85rem; transition: background 0.2s;" onmouseover="this.style.background='rgba(37,211,102,0.25)'" onmouseout="this.style.background='rgba(37,211,102,0.12)'">
                                                        <i class="fab fa-whatsapp"></i>
                                                    </a>
                                                </div>
                                            <?php else: ?>
                                                <span style="color: var(--text-muted);">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="padding: 1rem 0.5rem;">
                                            <span style="background: <?= $row['situacion'] == 'Comunero' ? 'rgba(16, 185, 129, 0.1)' : 'rgba(37, 99, 235, 0.1)' ?>; color: <?= $row['situacion'] == 'Comunero' ? 'var(--secondary)' : 'var(--primary)' ?>; padding: 0.25rem 0.75rem; border-radius: 999px; font-size: 0.75rem; font-weight: 600;">
                                                <?= htmlspecialchars($row['situacion']) ?>
                                            </span>
                                        </td>
                                        <td style="padding: 1rem 0.5rem;"><?= htmlspecialchars($row['localidad']) ?></td>
                                        <td style="padding: 1rem 0.5rem; text-align: center;">
                                            <div style="display: flex; gap: 0.5rem; justify-content: center;">
                                                <button type="button" onclick="abrirModalVer(<?= $row['id_comunero'] ?>);" class="btn" style="padding: 0.4rem 0.6rem; background: rgba(37, 99, 235, 0.1); color: var(--primary); border-radius: var(--radius-md);" title="Ver información">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <a href="/proyectocomunitario/public/index.php?page=comuneros_formulario&id=<?= $row['id_comunero'] ?>" class="btn" style="padding: 0.4rem 0.6rem; background: rgba(245, 158, 11, 0.1); color: var(--warning); border-radius: var(--radius-md);" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" onclick="abrirModalEliminar('<?= $row['id_comunero'] ?>', '<?= htmlspecialchars($row['nombre_completo']) ?>');" class="btn" style="padding: 0.4rem 0.6rem; background: rgba(239, 68, 68, 0.1); color: var(--danger); border-radius: var(--radius-md);" title="Desactivar">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="padding: 2rem; text-align: center; color: var(--text-muted);">
                                        No se encontraron registros activos.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>

                <!-- Tab: Comuneros Inactivos -->
                <?php if ($tab === 'inactivos'): ?>
                <div style="overflow-x: auto; max-height: 600px;">
                    <table style="width: 100%; border-collapse: collapse; text-align: left;">
                        <thead>
                            <tr style="border-bottom: 2px solid var(--border); color: var(--text-muted); font-weight: 600; font-size: 0.875rem;">
                                <th style="padding: 1rem 0.5rem;">N°</th>
                                <th style="padding: 1rem 0.5rem;">Nombre Completo</th>
                                <th style="padding: 1rem 0.5rem;">Teléfono</th>
                                <th style="padding: 1rem 0.5rem;">Estatus</th>
                                <th style="padding: 1rem 0.5rem;">Localidad</th>
                                <th style="padding: 1rem 0.5rem; text-align: center;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (pg_num_rows($resultado_inactivos) > 0): ?>
                                <?php while ($row = pg_fetch_assoc($resultado_inactivos)): ?>
                                    <tr class="fila-comunero" data-nombre="<?= strtolower(htmlspecialchars($row['nombre_completo'])) ?>" data-numero="<?= str_pad($row['numero_progresivo'], 4, '0', STR_PAD_LEFT) ?>" style="border-bottom: 1px solid var(--border); transition: var(--transition-fast);" onmouseover="this.style.backgroundColor='#f1f5f9'" onmouseout="this.style.backgroundColor='transparent'">
                                        <td style="padding: 1rem 0.5rem; font-weight: 500;"><?= str_pad($row['numero_progresivo'], 4, '0', STR_PAD_LEFT) ?></td>
                                        <td style="padding: 1rem 0.5rem;"><?= htmlspecialchars($row['nombre_completo']) ?></td>
                                        <td style="padding: 1rem 0.5rem;">
                                            <?php if (!empty($row['telefono'])): ?>
                                                <?php $telefonoLimpio = preg_replace('/\D/', '', $row['telefono']); ?>
                                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                                    <a href="tel:<?= htmlspecialchars($row['telefono']) ?>" style="color: var(--primary); text-decoration: none; font-weight: 500;">
                                                        <i class="fas fa-phone"></i> <?= htmlspecialchars($row['telefono']) ?>
                                                    </a>
                                                    <a href="https://wa.me/52<?= $telefonoLimpio ?>" target="_blank" rel="noopener noreferrer" title="Enviar mensaje por WhatsApp" style="display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; background: rgba(37, 211, 102, 0.12); color: #25d366; border-radius: 50%; text-decoration: none; font-size: 0.85rem; transition: background 0.2s;" onmouseover="this.style.background='rgba(37,211,102,0.25)'" onmouseout="this.style.background='rgba(37,211,102,0.12)'">
                                                        <i class="fab fa-whatsapp"></i>
                                                    </a>
                                                </div>
                                            <?php else: ?>
                                                <span style="color: var(--text-muted);">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="padding: 1rem 0.5rem;">
                                            <span style="background: rgba(107, 114, 128, 0.1); color: var(--text-muted); padding: 0.25rem 0.75rem; border-radius: 999px; font-size: 0.75rem; font-weight: 600;">
                                                Inactivo
                                            </span>
                                        </td>
                                        <td style="padding: 1rem 0.5rem;"><?= htmlspecialchars($row['localidad']) ?></td>
                                        <td style="padding: 1rem 0.5rem; text-align: center;">
                                            <div style="display: flex; gap: 0.5rem; justify-content: center;">
                                                <button type="button" onclick="abrirModalVer(<?= $row['id_comunero'] ?>);" class="btn" style="padding: 0.4rem 0.6rem; background: rgba(37, 99, 235, 0.1); color: var(--primary); border-radius: var(--radius-md);" title="Ver información">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button type="button" onclick="abrirModalReactivar('<?= $row['id_comunero'] ?>', '<?= htmlspecialchars($row['nombre_completo']) ?>');" class="btn" style="padding: 0.4rem 0.6rem; background: rgba(16, 185, 129, 0.1); color: var(--secondary); border-radius: var(--radius-md);" title="Reactivar">
                                                    <i class="fas fa-redo"></i>
                                                </button>
                                                <?php if ($esAdmin): ?>
                                                <button type="button" onclick="abrirModalEliminarTotal('<?= $row['id_comunero'] ?>', '<?= htmlspecialchars($row['nombre_completo']) ?>');" class="btn" style="padding: 0.4rem 0.6rem; background: rgba(185, 28, 28, 0.12); color: #991b1b; border-radius: var(--radius-md);" title="Eliminar totalmente">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="padding: 2rem; text-align: center; color: var(--text-muted);">
                                        No hay comuneros inactivos.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Modal Ver Comunero -->
    <div id="modalVer" class="modal-ver">
        <div class="modal-ver-content">
            <div class="modal-ver-header">
                <div class="modal-ver-avatar" id="verAvatar"></div>
                <div>
                    <h2 id="verNombre"></h2>
                    <p id="verSubtitulo"></p>
                </div>
            </div>

            <div class="modal-ver-section">Información General</div>
            <div class="modal-ver-grid">
                <div class="modal-ver-field">
                    <span class="modal-ver-label">N° Progresivo</span>
                    <span class="modal-ver-value" id="verNumero"></span>
                </div>
                <div class="modal-ver-field">
                    <span class="modal-ver-label">Estatus</span>
                    <span class="modal-ver-value" id="verSituacion"></span>
                </div>
                <div class="modal-ver-field">
                    <span class="modal-ver-label">Localidad</span>
                    <span class="modal-ver-value" id="verLocalidad"></span>
                </div>
                <div class="modal-ver-field">
                    <span class="modal-ver-label">Teléfono</span>
                    <span class="modal-ver-value" id="verTelefono"></span>
                </div>
                <div class="modal-ver-field">
                    <span class="modal-ver-label">Lugar de Residencia</span>
                    <span class="modal-ver-value" id="verResidencia"></span>
                </div>
            </div>

            <div class="modal-ver-section">Documentación</div>
            <div class="modal-ver-grid">
                <div class="modal-ver-field">
                    <span class="modal-ver-label">N° RAN</span>
                    <span class="modal-ver-value" id="verRan"></span>
                </div>
                <div class="modal-ver-field">
                    <span class="modal-ver-label">N° Certificado</span>
                    <span class="modal-ver-value" id="verCertificado"></span>
                </div>
            </div>

            <div class="modal-ver-section">Observaciones</div>
            <div class="modal-ver-grid">
                <div class="modal-ver-field full">
                    <span class="modal-ver-value" id="verObservaciones"></span>
                </div>
            </div>

            <div class="modal-ver-section">Sucesores</div>
            <div id="verSucesores"></div>

            <button type="button" class="btn-cerrar-ver" onclick="cerrarModalVer()">
                <i class="fas fa-times"></i> Cerrar
            </button>
        </div>
    </div>

    <!-- Modal de Confirmación para Eliminar -->
    <div id="modalEliminar" class="modal-delete">
        <div class="modal-delete-content">
            <div class="modal-delete-header">
                <div class="modal-delete-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h2 class="modal-delete-title">¿Desactivar comunero?</h2>
            </div>
            
            <p class="modal-delete-description">
                Estás apunto de desactivar este registro. Esta acción marcará el comunero como inactivo.
            </p>

            <div class="modal-delete-info">
                <strong>⚠️ Información del registro:</strong>
                <div id="infoNombre" style="margin-top: 0.5rem;"></div>
            </div>

            <div class="modal-delete-footer">
                <button type="button" class="btn-modal btn-modal-cancel" onclick="cerrarModalEliminar()">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="button" class="btn-modal btn-modal-delete" onclick="confirmarEliminar()">
                    <i class="fas fa-trash"></i> Desactivar Registro
                </button>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmación para Reactivar -->
    <div id="modalReactivar" class="modal-delete">
        <div class="modal-delete-content">
            <div class="modal-delete-header">
                <div class="modal-delete-icon" style="background: rgba(16, 185, 129, 0.1); color: var(--secondary);">
                    <i class="fas fa-check"></i>
                </div>
                <h2 class="modal-delete-title">¿Reactivar comunero?</h2>
            </div>
            
            <p class="modal-delete-description">
                Estás apunto de reactivar este registro. El comunero volverá a aparecer en la lista de activos.
            </p>

            <div class="modal-delete-info" style="background: rgba(16, 185, 129, 0.05); border-left-color: var(--secondary);">
                <strong style="color: var(--secondary);">✓ Información del registro:</strong>
                <div id="infoNombreReactivar" style="margin-top: 0.5rem;"></div>
            </div>

            <div class="modal-delete-footer">
                <button type="button" class="btn-modal btn-modal-cancel" onclick="cerrarModalReactivar()">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="button" class="btn-modal btn-modal-delete" style="background: var(--secondary); color: white;" onclick="confirmarReactivar()" onmouseover="this.style.background='#10b981'" onmouseout="this.style.background='var(--secondary)'">
                    <i class="fas fa-redo"></i> Reactivar Registro
                </button>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmación para Eliminación Total -->
    <div id="modalEliminarTotal" class="modal-delete">
        <div class="modal-delete-content">
            <div class="modal-delete-header">
                <div class="modal-delete-icon" style="background: rgba(153, 27, 27, 0.12); color: #991b1b;">
                    <i class="fas fa-radiation"></i>
                </div>
                <h2 class="modal-delete-title">¿Eliminar registro definitivamente?</h2>
            </div>

            <p class="modal-delete-description">
                Esta acción eliminará de forma permanente el comunero y sus registros relacionados. No se puede deshacer.
            </p>

            <div class="modal-delete-info">
                <strong>⚠️ Registro a eliminar:</strong>
                <div id="infoNombreEliminarTotal" style="margin-top: 0.5rem;"></div>
            </div>

            <div class="modal-delete-footer">
                <button type="button" class="btn-modal btn-modal-cancel" onclick="cerrarModalEliminarTotal()">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="button" class="btn-modal btn-modal-delete" style="background: #991b1b; color: white;" onclick="confirmarEliminarTotal()" onmouseover="this.style.background='#7f1d1d'" onmouseout="this.style.background='#991b1b'">
                    <i class="fas fa-trash-alt"></i> Eliminar Definitivamente
                </button>
            </div>
        </div>
    </div>

    <script>
        // Datos de comuneros para el modal de visualización
        const comunerosData = <?php
            $all_data = [];
            // Re-fetch both result sets (reset pointer)
            pg_result_seek($resultado_activos, 0);
            while ($r = pg_fetch_assoc($resultado_activos)) {
                $id = (int)$r['id_comunero'];
                $all_data[$id] = [
                    'nombre'        => $r['nombre_completo'],
                    'numero'        => str_pad($r['numero_progresivo'], 4, '0', STR_PAD_LEFT),
                    'situacion'     => $r['situacion'],
                    'localidad'     => $r['localidad'],
                    'telefono'      => $r['telefono'] ?? '',
                    'residencia'    => $r['lugar_residencia'] ?? '',
                    'ran'           => $r['numero_ran'] ?? '',
                    'certificado'   => $r['numero_certificado'] ?? '',
                    'observaciones' => $r['observaciones'] ?? '',
                    'color'         => $r['color_mapa'] ?? '#2563eb',
                    'activo'        => true,
                    'sucesores'     => $sucesores_map[$id] ?? [],
                ];
            }
            pg_result_seek($resultado_inactivos, 0);
            while ($r = pg_fetch_assoc($resultado_inactivos)) {
                $id = (int)$r['id_comunero'];
                $all_data[$id] = [
                    'nombre'        => $r['nombre_completo'],
                    'numero'        => str_pad($r['numero_progresivo'], 4, '0', STR_PAD_LEFT),
                    'situacion'     => $r['situacion'],
                    'localidad'     => $r['localidad'],
                    'telefono'      => $r['telefono'] ?? '',
                    'residencia'    => $r['lugar_residencia'] ?? '',
                    'ran'           => $r['numero_ran'] ?? '',
                    'certificado'   => $r['numero_certificado'] ?? '',
                    'observaciones' => $r['observaciones'] ?? '',
                    'color'         => $r['color_mapa'] ?? '#6b7280',
                    'activo'        => false,
                    'sucesores'     => $sucesores_map[$id] ?? [],
                ];
            }
            echo json_encode($all_data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT);
        ?>;

        // ===== Funciones para Ver Comunero =====
        function abrirModalVer(id) {
            const d = comunerosData[id];
            if (!d) return;

            const avatar = document.getElementById('verAvatar');
            avatar.textContent = d.nombre.charAt(0).toUpperCase();
            avatar.style.background = d.color || '#2563eb';

            document.getElementById('verNombre').textContent = d.nombre;
            document.getElementById('verSubtitulo').textContent = 'N° ' + d.numero + ' · ' + d.localidad;

            document.getElementById('verNumero').textContent = d.numero;

            const sit = document.getElementById('verSituacion');
            sit.textContent = d.situacion + (d.activo ? '' : ' (Inactivo)');

            setText('verLocalidad',    d.localidad);
            setText('verTelefono',     d.telefono);
            setText('verResidencia',   d.residencia);
            setText('verRan',          d.ran);
            setText('verCertificado',  d.certificado);
            setText('verObservaciones', d.observaciones);

            const sucEl = document.getElementById('verSucesores');
            if (d.sucesores && d.sucesores.length > 0) {
                sucEl.innerHTML = d.sucesores.map(s =>
                    `<span class="sucesor-pill"><i class="fas fa-user"></i> ${esc(s.nombre)}<span style="opacity:0.6; font-size:0.75rem;"> · ${esc(s.parentesco)}</span></span>`
                ).join('');
            } else {
                sucEl.innerHTML = '<span style="color:var(--text-muted); font-style:italic; font-size:0.9rem;">Sin sucesores registrados</span>';
            }

            document.getElementById('modalVer').classList.add('active');
        }

        function cerrarModalVer() {
            document.getElementById('modalVer').classList.remove('active');
        }

        function setText(id, val) {
            const el = document.getElementById(id);
            if (val && val.trim() !== '') {
                el.textContent = val;
                el.classList.remove('empty');
            } else {
                el.textContent = '—';
                el.classList.add('empty');
            }
        }

        function esc(str) {
            const d = document.createElement('div');
            d.textContent = str;
            return d.innerHTML;
        }

        let idEliminar = null;
        let idReactivar = null;
        let idEliminarTotal = null;

        // ===== Funciones para Desactivar =====
        function abrirModalEliminar(id, nombre) {
            idEliminar = id;
            document.getElementById('infoNombre').textContent = nombre;
            document.getElementById('modalEliminar').classList.add('active');
        }

        function cerrarModalEliminar() {
            document.getElementById('modalEliminar').classList.remove('active');
            idEliminar = null;
        }

        function confirmarEliminar() {
            if (idEliminar) {
                window.location.href = '/proyectocomunitario/src/Comuneros/Application/acciones.php?accion=desactivar&id=' + idEliminar;
            }
        }

        // ===== Funciones para Reactivar =====
        function abrirModalReactivar(id, nombre) {
            idReactivar = id;
            document.getElementById('infoNombreReactivar').textContent = nombre;
            document.getElementById('modalReactivar').classList.add('active');
        }

        function cerrarModalReactivar() {
            document.getElementById('modalReactivar').classList.remove('active');
            idReactivar = null;
        }

        function confirmarReactivar() {
            if (idReactivar) {
                window.location.href = '/proyectocomunitario/src/Comuneros/Application/acciones.php?accion=reactivar&id=' + idReactivar;
            }
        }

        // ===== Funciones para Eliminación Total =====
        function abrirModalEliminarTotal(id, nombre) {
            idEliminarTotal = id;
            document.getElementById('infoNombreEliminarTotal').textContent = nombre;
            document.getElementById('modalEliminarTotal').classList.add('active');
        }

        function cerrarModalEliminarTotal() {
            document.getElementById('modalEliminarTotal').classList.remove('active');
            idEliminarTotal = null;
        }

        function confirmarEliminarTotal() {
            if (idEliminarTotal) {
                window.location.href = '/proyectocomunitario/src/Comuneros/Application/acciones.php?accion=eliminar_total&id=' + idEliminarTotal;
            }
        }

        // Cerrar modal al presionar ESC
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                cerrarModalVer();
                cerrarModalEliminar();
                cerrarModalReactivar();
                cerrarModalEliminarTotal();
            }
        });

        // Cerrar modal si se hace clic fuera del contenido
        document.getElementById('modalVer').addEventListener('click', function(event) {
            if (event.target === this) cerrarModalVer();
        });

        document.getElementById('modalEliminar').addEventListener('click', function(event) {
            if (event.target === this) {
                cerrarModalEliminar();
            }
        });

        document.getElementById('modalReactivar').addEventListener('click', function(event) {
            if (event.target === this) {
                cerrarModalReactivar();
            }
        });

        document.getElementById('modalEliminarTotal').addEventListener('click', function(event) {
            if (event.target === this) {
                cerrarModalEliminarTotal();
            }
        });

        // Búsqueda en tiempo real
        document.getElementById('busquedaInput').addEventListener('keyup', function() {
            const termino = this.value.toLowerCase();
            const filas = document.querySelectorAll('.fila-comunero');
            
            filas.forEach(fila => {
                const nombre = fila.getAttribute('data-nombre');
                const numero = fila.getAttribute('data-numero');
                
                if (nombre.includes(termino) || numero.includes(termino)) {
                    fila.style.display = '';
                } else {
                    fila.style.display = 'none';
                }
            });
        });

        // Estilos dinámicos para las pestañas activas
        document.querySelectorAll('.tab-button').forEach(btn => {
            const isActive = btn.textContent.includes('<?= $tab === "activos" ? "Activos" : "Inactivos" ?>');
            if (isActive) {
                btn.style.color = 'var(--primary)';
                btn.style.borderBottomColor = 'var(--primary)';
            }
        });
    </script>
</body>
</html>
