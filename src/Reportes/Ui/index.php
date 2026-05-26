<?php
/**
 * SysComunal - Sistema de Gestión Comunal
 * Desarrollado por: Mario Ruiz Alavez
 * Correo: ruizmario04574@gmail.com
 */
require_once '../../Shared/Infrastructure/Database/Connection.php';

/** @var \PgSql\Connection $conexion */

$pageTitle = "Centro de Reportes";
$activePage = "reportes";

// Obtener opciones para filtros
$localidades = pg_query($conexion, "SELECT id_localidad, nombre FROM localidad ORDER BY nombre ASC");
$situaciones = pg_query($conexion, "SELECT id_situacion, descripcion FROM situacion ORDER BY descripcion ASC");
$anios = range(date('Y') - 10, date('Y'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SysComunal | Reportes</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= $GLOBALS['ASSETS_URL'] ?>/Css/style.css">
    <style>
        .stat-card { cursor: pointer; }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
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
            <div id="notificacionReporte" class="notification-card" aria-live="polite" aria-atomic="true"></div>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <div>
                    <h1 style="font-size: 1.75rem; color: var(--text-main); margin-bottom: 0.5rem;">Generación de Reportes</h1>
                    <p style="color: var(--text-muted);">Consultas especializadas y resúmenes</p>
                </div>
            </div>

            <!-- SECCIÓN DE REPORTES PERSONALIZADOS -->
            <div class="glass-panel" style="padding: 2rem; margin-bottom: 2rem; border: 1px solid var(--border); border-radius: 12px;">
                <h2 style="font-size: 1.25rem; color: var(--text-main); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem;">
                    <i class="fas fa-sliders-h" style="color: var(--primary);"></i> Reportes con Filtros
                </h2>

                <form id="frmReporteFiltro" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem;">

                    <!-- Tipo de Reporte -->
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: var(--text-main);">
                            <i class="fas fa-chart-bar" style="color: var(--primary); margin-right: 0.5rem;"></i> Tipo de Reporte
                        </label>
                        <select name="tipo_filtro" id="tipoFiltro" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border); border-radius: 8px; background: white; font-size: 0.95rem; cursor: pointer;" onchange="actualizarFiltros()">
                            <option value="">-- Seleccionar reporte --</option>
                            <option value="comuneros_filtro">Comuneros (por localidad/situación)</option>
                            <option value="pagos_filtro">Pagos Prediales (por año/localidad)</option>
                            <option value="asambleas_filtro">Asambleas (por año)</option>
                            <option value="tequios_filtro">Tequios (por año)</option>
                        </select>
                    </div>

                    <!-- Localidad -->
                    <div id="divLocalidad" style="display: none;">
                        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: var(--text-main);">
                            <i class="fas fa-map-marker-alt" style="color: var(--secondary); margin-right: 0.5rem;"></i> Localidad (Opcional)
                        </label>
                        <select name="localidad" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border); border-radius: 8px; background: white; font-size: 0.95rem; cursor: pointer;">
                            <option value="">-- Todas las localidades --</option>
                            <?php while($row = pg_fetch_assoc($localidades)): ?>
                                <option value="<?= $row['id_localidad'] ?>"><?= htmlspecialchars($row['nombre']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <!-- Situación -->
                    <div id="divSituacion" style="display: none;">
                        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: var(--text-main);">
                            <i class="fas fa-user-tag" style="color: var(--warning); margin-right: 0.5rem;"></i> Situación (Opcional)
                        </label>
                        <select name="situacion" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border); border-radius: 8px; background: white; font-size: 0.95rem; cursor: pointer;">
                            <option value="">-- Todas las situaciones --</option>
                            <?php
                            pg_result_seek($situaciones, 0);
                            while($row = pg_fetch_assoc($situaciones)):
                            ?>
                                <option value="<?= $row['id_situacion'] ?>"><?= htmlspecialchars($row['descripcion']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <!-- Año -->
                    <div id="divAnio" style="display: none;">
                        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: var(--text-main);">
                            <i class="fas fa-calendar" style="color: var(--danger); margin-right: 0.5rem;"></i> Año
                        </label>
                        <select name="anio" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border); border-radius: 8px; background: white; font-size: 0.95rem; cursor: pointer;">
                            <?php foreach($anios as $anio): ?>
                                <option value="<?= $anio ?>" <?= $anio == date('Y') ? 'selected' : '' ?>><?= $anio ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Botón -->
                    <div style="display: flex; align-items: flex-end;">
                        <button type="submit" style="width: 100%; padding: 0.75rem; background: var(--primary); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: var(--transition-fast); font-size: 0.95rem;" onmouseover="this.style.background='#1d4ed8'" onmouseout="this.style.background='var(--primary)'">
                            <i class="fas fa-file-pdf" style="margin-right: 0.5rem;"></i> Generar Reporte
                        </button>
                    </div>
                </form>
            </div>

            <h2 style="font-size: 1.25rem; color: var(--text-main); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem;">
                <i class="fas fa-file-alt" style="color: var(--secondary);"></i> Reportes Predefinidos
            </h2>

            <div class="dashboard-grid">
                <!-- 1. Padron completo -->
                <a href="/proyectocomunitario/public/reportes_generar.php?tipo=padron" target="_blank" style="text-decoration: none; color: inherit;">
                    <div class="glass-panel stat-card" style="border: 1px solid var(--border); transition: var(--transition-fast);" onmouseover="this.style.borderColor='var(--primary)'" onmouseout="this.style.borderColor='var(--border)'">
                        <div class="stat-icon primary">
                            <i class="fas fa-list-ul"></i>
                        </div>
                        <div class="stat-info">
                            <h3 style="color: var(--text-main); font-size: 1rem; font-weight: 600;">Padrón de Comuneros</h3>
                            <p style="font-size: 0.8rem; color: var(--text-muted); text-transform: none; margin-top: 0.25rem;">Listado completo de comuneros activos</p>
                        </div>
                    </div>
                </a>

                <!-- 2. Adeudo Predial -->
                <div class="glass-panel stat-card" style="border: 1px solid var(--border); transition: var(--transition-fast); cursor: pointer;" onmouseover="this.style.borderColor='var(--danger)'" onmouseout="this.style.borderColor='var(--border)'" onclick="abrirModalAnio('adeudo_predial', 'Adeudo Predial')">
                    <div class="stat-icon danger">
                        <i class="fas fa-file-invoice-dollar"></i>
                    </div>
                    <div class="stat-info">
                        <h3 style="color: var(--text-main); font-size: 1rem; font-weight: 600;">Adeudo Predial</h3>
                        <p style="font-size: 0.8rem; color: var(--text-muted); text-transform: none; margin-top: 0.25rem;">Comuneros con pagos pendientes</p>
                    </div>
                </div>

                <!-- 3. Sin Sucesores -->
                <a href="/proyectocomunitario/public/reportes_generar.php?tipo=sin_sucesores" target="_blank" style="text-decoration: none; color: inherit;">
                    <div class="glass-panel stat-card" style="border: 1px solid var(--border); transition: var(--transition-fast);" onmouseover="this.style.borderColor='var(--warning)'" onmouseout="this.style.borderColor='var(--border)'">
                        <div class="stat-icon warning">
                            <i class="fas fa-user-slash"></i>
                        </div>
                        <div class="stat-info">
                            <h3 style="color: var(--text-main); font-size: 1rem; font-weight: 600;">Sin Sucesores</h3>
                            <p style="font-size: 0.8rem; color: var(--text-muted); text-transform: none; margin-top: 0.25rem;">Comuneros sin lista de sucesores</p>
                        </div>
                    </div>
                </a>

                <!-- 4. Por Localidad -->
                <a href="/proyectocomunitario/public/reportes_generar.php?tipo=por_localidad" target="_blank" style="text-decoration: none; color: inherit;">
                    <div class="glass-panel stat-card" style="border: 1px solid var(--border); transition: var(--transition-fast);" onmouseover="this.style.borderColor='var(--secondary)'" onmouseout="this.style.borderColor='var(--border)'">
                        <div class="stat-icon secondary">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div class="stat-info">
                            <h3 style="color: var(--text-main); font-size: 1rem; font-weight: 600;">Por Localidad</h3>
                            <p style="font-size: 0.8rem; color: var(--text-muted); text-transform: none; margin-top: 0.25rem;">Resumen por localidades</p>
                        </div>
                    </div>
                </a>

                <!-- 5. Actas de Posesión -->
                <a href="/proyectocomunitario/public/reportes_generar.php?tipo=actas" target="_blank" style="text-decoration: none; color: inherit;">
                    <div class="glass-panel stat-card" style="border: 1px solid var(--border); transition: var(--transition-fast);" onmouseover="this.style.borderColor='#8b5cf6'" onmouseout="this.style.borderColor='var(--border)'">
                        <div class="stat-icon" style="background: rgba(139, 92, 246, 0.1); color: #8b5cf6;">
                            <i class="fas fa-file-contract"></i>
                        </div>
                        <div class="stat-info">
                            <h3 style="color: var(--text-main); font-size: 1rem; font-weight: 600;">Actas de Posesión</h3>
                            <p style="font-size: 0.8rem; color: var(--text-muted); text-transform: none; margin-top: 0.25rem;">Registro de actas registradas</p>
                        </div>
                    </div>
                </a>

                <!-- 6. Asambleas -->
                <a href="/proyectocomunitario/public/reportes_generar.php?tipo=asambleas" target="_blank" style="text-decoration: none; color: inherit;">
                    <div class="glass-panel stat-card" style="border: 1px solid var(--border); transition: var(--transition-fast);" onmouseover="this.style.borderColor='#06b6d4'" onmouseout="this.style.borderColor='var(--border)'">
                        <div class="stat-icon" style="background: rgba(6, 182, 212, 0.1); color: #06b6d4;">
                            <i class="fas fa-users-cog"></i>
                        </div>
                        <div class="stat-info">
                            <h3 style="color: var(--text-main); font-size: 1rem; font-weight: 600;">Asambleas</h3>
                            <p style="font-size: 0.8rem; color: var(--text-muted); text-transform: none; margin-top: 0.25rem;">Historial de asambleas y asistencia</p>
                        </div>
                    </div>
                </a>

                <!-- 7. Tequios -->
                <a href="/proyectocomunitario/public/reportes_generar.php?tipo=tequios" target="_blank" style="text-decoration: none; color: inherit;">
                    <div class="glass-panel stat-card" style="border: 1px solid var(--border); transition: var(--transition-fast);" onmouseover="this.style.borderColor='#ec4899'" onmouseout="this.style.borderColor='var(--border)'">
                        <div class="stat-icon" style="background: rgba(236, 72, 153, 0.1); color: #ec4899;">
                            <i class="fas fa-hands-helping"></i>
                        </div>
                        <div class="stat-info">
                            <h3 style="color: var(--text-main); font-size: 1rem; font-weight: 600;">Tequios</h3>
                            <p style="font-size: 0.8rem; color: var(--text-muted); text-transform: none; margin-top: 0.25rem;">Participantes en tequios realizados</p>
                        </div>
                    </div>
                </a>

                <!-- 8. Pagos Prediales -->
                <div class="glass-panel stat-card" style="border: 1px solid var(--border); transition: var(--transition-fast); cursor: pointer;" onmouseover="this.style.borderColor='#f59e0b'" onmouseout="this.style.borderColor='var(--border)'" onclick="abrirModalAnio('pagos', 'Pagos Prediales')">
                    <div class="stat-icon" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="stat-info">
                        <h3 style="color: var(--text-main); font-size: 1rem; font-weight: 600;">Pagos Prediales</h3>
                        <p style="font-size: 0.8rem; color: var(--text-muted); text-transform: none; margin-top: 0.25rem;">Recaudación de pago predial</p>
                    </div>
                </div>

                <!-- 9. Situación de Comuneros -->
                <a href="/proyectocomunitario/public/reportes_generar.php?tipo=situacion_comuneros" target="_blank" style="text-decoration: none; color: inherit;">
                    <div class="glass-panel stat-card" style="border: 1px solid var(--border); transition: var(--transition-fast);" onmouseover="this.style.borderColor='#14b8a6'" onmouseout="this.style.borderColor='var(--border)'">
                        <div class="stat-icon" style="background: rgba(20, 184, 166, 0.1); color: #14b8a6;">
                            <i class="fas fa-chart-pie"></i>
                        </div>
                        <div class="stat-info">
                            <h3 style="color: var(--text-main); font-size: 1rem; font-weight: 600;">Estatus de Comuneros</h3>
                            <p style="font-size: 0.8rem; color: var(--text-muted); text-transform: none; margin-top: 0.25rem;">Distribución por situación</p>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </main>

    <!-- Modal Selector de Año -->
    <div id="modalAnio" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center; animation: fadeIn 0.2s ease-in-out;">
        <div style="background:white; border-radius:var(--radius-lg); box-shadow:var(--shadow-lg); padding:2rem; max-width:360px; width:90%; animation: slideUp 0.3s ease-out;">
            <div style="display:flex; align-items:center; gap:0.75rem; margin-bottom:1.25rem;">
                <div style="width:42px; height:42px; background:rgba(245,158,11,0.12); border-radius:50%; display:flex; align-items:center; justify-content:center; color:var(--warning); font-size:1.2rem; flex-shrink:0;">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div>
                    <h3 style="margin:0; font-size:1.1rem; font-weight:700; color:var(--text-main);" id="modalAnioTitulo"></h3>
                    <p style="margin:0; font-size:0.82rem; color:var(--text-muted);">Selecciona el año del reporte</p>
                </div>
            </div>
            <select id="selectAnioModal" style="width:100%; padding:0.75rem; border:1px solid var(--border); border-radius:8px; font-size:1rem; margin-bottom:1.5rem; cursor:pointer;">
                <?php foreach (array_reverse($anios) as $anio): ?>
                    <option value="<?= $anio ?>" <?= $anio == date('Y') ? 'selected' : '' ?>><?= $anio ?></option>
                <?php endforeach; ?>
            </select>
            <div style="display:flex; gap:0.75rem; justify-content:flex-end;">
                <button onclick="cerrarModalAnio()" style="padding:0.65rem 1.25rem; border:1px solid var(--border); background:white; border-radius:8px; cursor:pointer; font-weight:500; color:var(--text-main);">
                    Cancelar
                </button>
                <button onclick="generarReporteAnio()" style="padding:0.65rem 1.25rem; background:var(--primary); color:white; border:none; border-radius:8px; cursor:pointer; font-weight:600;">
                    <i class="fas fa-file-pdf"></i> Generar
                </button>
            </div>
        </div>
    </div>

    <script>
    function escaparHtml(texto) {
        return String(texto)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/\"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function mostrarNotificacion(tipo, mensaje) {
        const notificacion = document.getElementById('notificacionReporte');
        const config = {
            warning: { icon: 'fa-triangle-exclamation', title: 'Selecciona un reporte' }
        }[tipo] || { icon: 'fa-circle-info', title: 'Aviso' };

        notificacion.className = `notification-card show ${tipo}`;
        notificacion.innerHTML = `
            <div class="notification-card__icon"><i class="fa-solid ${config.icon}"></i></div>
            <div class="notification-card__content">
                <p class="notification-card__title">${escaparHtml(config.title)}</p>
                <p class="notification-card__message">${escaparHtml(mensaje)}</p>
            </div>
        `;

        notificacion.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function ocultarNotificacion() {
        document.getElementById('notificacionReporte').className = 'notification-card';
    }

    function actualizarFiltros() {
        const tipo = document.getElementById('tipoFiltro').value;
        const divLocalidad = document.getElementById('divLocalidad');
        const divSituacion = document.getElementById('divSituacion');
        const divAnio = document.getElementById('divAnio');

        ocultarNotificacion();

        divLocalidad.style.display = 'none';
        divSituacion.style.display = 'none';
        divAnio.style.display = 'none';

        if (tipo === 'comuneros_filtro') {
            divLocalidad.style.display = 'block';
            divSituacion.style.display = 'block';
        } else if (tipo === 'pagos_filtro') {
            divLocalidad.style.display = 'block';
            divAnio.style.display = 'block';
        } else if (tipo === 'asambleas_filtro' || tipo === 'tequios_filtro') {
            divAnio.style.display = 'block';
        }
    }

    let _tipoReporteAnio = '';

    function abrirModalAnio(tipo, titulo) {
        _tipoReporteAnio = tipo;
        document.getElementById('modalAnioTitulo').textContent = titulo;
        document.getElementById('modalAnio').style.display = 'flex';
    }

    function cerrarModalAnio() {
        document.getElementById('modalAnio').style.display = 'none';
        _tipoReporteAnio = '';
    }

    function generarReporteAnio() {
        const anio = document.getElementById('selectAnioModal').value;
        window.open('/proyectocomunitario/public/reportes_generar.php?tipo=' + _tipoReporteAnio + '&anio=' + anio, '_blank');
        cerrarModalAnio();
    }

    document.getElementById('modalAnio').addEventListener('click', function(e) {
        if (e.target === this) cerrarModalAnio();
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') cerrarModalAnio();
    });

    // ── Envío del formulario de filtros ───────────────────────────────────────
    document.getElementById('frmReporteFiltro').addEventListener('submit', function(e) {
        e.preventDefault();

        const tipo = document.getElementById('tipoFiltro').value;
        if (!tipo) {
            mostrarNotificacion('warning', 'Por favor selecciona un tipo de reporte antes de generarlo.');
            return;
        }

        ocultarNotificacion();

        const params = new URLSearchParams();
        params.append('tipo', tipo);

        const localidad = document.querySelector('select[name="localidad"]').value;
        const situacion = document.querySelector('select[name="situacion"]').value;
        const anio      = document.querySelector('select[name="anio"]').value;

        if (localidad) params.append('localidad', localidad);
        if (situacion) params.append('situacion', situacion);
        if (anio)      params.append('anio', anio);

        window.open('/proyectocomunitario/public/reportes_generar.php?' + params.toString(), '_blank');
    });
    </script>

</body>
</html>
