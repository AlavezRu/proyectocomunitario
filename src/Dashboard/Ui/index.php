<?php
require_once '../../Shared/Infrastructure/Database/Connection.php';

/** @var \PgSql\Connection $conexion */

$pageTitle = "Dashboard Principal";
$activePage = "dashboard";
$anio_actual = date('Y');

// ESTADÍSTICAS DE COMUNEROS
// 1. Total Comuneros (situación = Comunero)
$q_comuneros = pg_query($conexion, "SELECT COUNT(*) FROM comunero c JOIN situacion s ON c.id_situacion = s.id_situacion WHERE c.activo = TRUE AND LOWER(s.descripcion) = 'comunero'");
$total_comuneros = pg_fetch_result($q_comuneros, 0, 0) ?: 0;

// 2. Total Avecindados (situación = Avecindado)
$q_avecindados = pg_query($conexion, "SELECT COUNT(*) FROM comunero c JOIN situacion s ON c.id_situacion = s.id_situacion WHERE c.activo = TRUE AND LOWER(s.descripcion) = 'avecindado'");
$total_avecindados = pg_fetch_result($q_avecindados, 0, 0) ?: 0;

// 3. Total general (todos los registros activos)
$q_total = pg_query($conexion, "SELECT COUNT(*) FROM comunero WHERE activo = TRUE");
$total_general = pg_fetch_result($q_total, 0, 0) ?: 0;

// 4. Total de actas de posesión
$q_actas = pg_query($conexion, "SELECT COUNT(*) FROM acta_posesion");
$total_actas = pg_fetch_result($q_actas, 0, 0) ?: 0;

// ESTADÍSTICAS DE ASAMBLEAS
// 5. Total de Asambleas
$q_asambleas = pg_query($conexion, "SELECT COUNT(*) FROM asamblea");
$total_asambleas = pg_fetch_result($q_asambleas, 0, 0) ?: 0;

// 6. Asambleas este año
$q_asambleas_anio = pg_query_params($conexion, "SELECT COUNT(*) FROM asamblea WHERE EXTRACT(YEAR FROM fecha) = $1", array($anio_actual));
$asambleas_anio = pg_fetch_result($q_asambleas_anio, 0, 0) ?: 0;

// ESTADÍSTICAS DE TEQUIOS
// 7. Total de Tequios
$q_tequios = pg_query($conexion, "SELECT COUNT(*) FROM tequio");
$total_tequios = pg_fetch_result($q_tequios, 0, 0) ?: 0;

// 8. Tequios este año
$q_tequios_anio = pg_query_params($conexion, "SELECT COUNT(*) FROM tequio WHERE EXTRACT(YEAR FROM fecha) = $1", array($anio_actual));
$tequios_anio = pg_fetch_result($q_tequios_anio, 0, 0) ?: 0;



// INFORMACIÓN ADICIONAL
// 13. Últimas asambleas
$q_ultimas_asambleas = pg_query($conexion, "SELECT fecha, descripcion FROM asamblea ORDER BY fecha DESC LIMIT 3");

// 14. Últimos tequios
$q_ultimos_tequios = pg_query($conexion, "SELECT fecha, descripcion FROM tequio ORDER BY fecha DESC LIMIT 3");

// 15. Participación en asambleas (registros en asistencia)
$q_participacion = pg_query_params($conexion, "SELECT COUNT(*) FROM asistencia_asamblea aa JOIN asamblea a ON aa.id_asamblea = a.id_asamblea WHERE EXTRACT(YEAR FROM a.fecha) = $1", array($anio_actual));
$participacion_asambleas = pg_fetch_result($q_participacion, 0, 0) ?: 0;

// 16. Participación en tequios
$q_participacion_tequios = pg_query_params($conexion, "SELECT COUNT(*) FROM cumplimiento_tequio ct JOIN tequio t ON ct.id_tequio = t.id_tequio WHERE EXTRACT(YEAR FROM t.fecha) = $1", array($anio_actual));
$participacion_tequios = pg_fetch_result($q_participacion_tequios, 0, 0) ?: 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SysComunal | Dashboard</title>
    <!-- FontAwesome para íconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-size/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= $GLOBALS['ASSETS_URL'] ?>/Css/style.css">
</head>
<body>

    <!-- Marca de agua institucional (hijo directo de body para que position:fixed funcione correctamente) -->
    <div aria-hidden="true" style="pointer-events: none; position: fixed; top: 50%; left: calc(140px + 50vw); transform: translate(-50%, -50%); z-index: 0; opacity: 0.05;">
        <img src="/proyectocomunitario/shared/img/logosinfond.png" alt="" style="width: 600px; height: 600px; object-fit: contain; display: block;">
    </div>

    <?php include '../../Shared/Ui/Layout/sidebar.php'; ?>

    <main class="main-wrapper">
        <?php include '../../Shared/Ui/Layout/header.php'; ?>

        <div class="content-container animate-fade-in" style="position: relative;">
            <div style="position: relative; z-index: 1;">
            <h1 style="font-size: 1.75rem; color: var(--text-main); margin-bottom: 0.5rem;">Bienvenido al Sistema Comunal</h1>
            <p style="color: var(--text-secondary); margin-bottom: 2rem;">Resumen general del estado de la comunidad - Año <?= $anio_actual ?></p>

            <!-- SECCIÓN: GESTIÓN DE PERSONAS -->
            <h2 style="font-size: 1.3rem; color: var(--text-main); margin-top: 2rem; margin-bottom: 1rem;">Gestión de Personas</h2>
            <div class="dashboard-grid">
                <div class="glass-panel stat-card">
                    <div class="stat-icon primary">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Comuneros Activos</h3>
                        <div class="stat-value"><?= $total_comuneros ?></div>
                    </div>
                </div>

                <div class="glass-panel stat-card">
                    <div class="stat-icon info">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Avecindados</h3>
                        <div class="stat-value"><?= $total_avecindados ?></div>
                    </div>
                </div>

                <div class="glass-panel stat-card">
                    <div class="stat-icon success">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total de Habitantes</h3>
                        <div class="stat-value"><?= $total_general ?></div>
                    </div>
                </div>

                <div class="glass-panel stat-card">
                    <div class="stat-icon warning">
                        <i class="fas fa-file-contract"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Actas de Posesión</h3>
                        <div class="stat-value"><?= $total_actas ?></div>
                    </div>
                </div>
            </div>

            <!-- SECCIÓN: ASAMBLEAS -->
            <h2 style="font-size: 1.3rem; color: var(--text-main); margin-top: 2rem; margin-bottom: 1rem;">Asambleas</h2>
            <div class="dashboard-grid">
                <div class="glass-panel stat-card">
                    <div class="stat-icon primary">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Asambleas</h3>
                        <div class="stat-value"><?= $total_asambleas ?></div>
                    </div>
                </div>

                <div class="glass-panel stat-card">
                    <div class="stat-icon info">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Asambleas Este Año</h3>
                        <div class="stat-value"><?= $asambleas_anio ?></div>
                    </div>
                </div>

                <div class="glass-panel stat-card">
                    <div class="stat-icon success">
                        <i class="fas fa-handshake"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Participaciones Registradas</h3>
                        <div class="stat-value"><?= $participacion_asambleas ?></div>
                        <small style="color: var(--text-secondary); margin-top: 0.5rem;">Este año</small>
                    </div>
                </div>
            </div>

            <!-- SECCIÓN: TEQUIOS -->
            <h2 style="font-size: 1.3rem; color: var(--text-main); margin-top: 2rem; margin-bottom: 1rem;">Tequios (Trabajos Comunitarios)</h2>
            <div class="dashboard-grid">
                <div class="glass-panel stat-card">
                    <div class="stat-icon warning">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Tequios</h3>
                        <div class="stat-value"><?= $total_tequios ?></div>
                    </div>
                </div>

                <div class="glass-panel stat-card">
                    <div class="stat-icon info">
                        <i class="fas fa-hammer"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Tequios Este Año</h3>
                        <div class="stat-value"><?= $tequios_anio ?></div>
                    </div>
                </div>

                <div class="glass-panel stat-card">
                    <div class="stat-icon success">
                        <i class="fas fa-people-group"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Participaciones en Tequios</h3>
                        <div class="stat-value"><?= $participacion_tequios ?></div>
                        <small style="color: var(--text-secondary); margin-top: 0.5rem;">Este año</small>
                    </div>
                </div>
            </div>

            <!-- SECCIÓN: INFORMACIÓN RECIENTE -->
            <h2 style="font-size: 1.3rem; color: var(--text-main); margin-top: 2rem; margin-bottom: 1rem;">Actividades Recientes</h2>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
                <!-- Últimas Asambleas -->
                <div class="glass-panel">
                    <h3 style="color: var(--text-main); margin-bottom: 1rem; font-size: 1.1rem;">Últimas Asambleas</h3>
                    <div style="max-height: 250px; overflow-y: auto;">
                        <?php if ($q_ultimas_asambleas && pg_num_rows($q_ultimas_asambleas) > 0): ?>
                            <?php while ($row = pg_fetch_assoc($q_ultimas_asambleas)): ?>
                                <div style="padding: 0.75rem; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; align-items: start; gap: 0.75rem;">
                                    <i class="fas fa-calendar" style="color: var(--primary); margin-top: 0.25rem;"></i>
                                    <div>
                                        <p style="font-size: 0.9rem; color: var(--text-secondary); margin: 0;"><?= date('d/m/Y', strtotime($row['fecha'])) ?></p>
                                        <p style="font-size: 0.95rem; color: var(--text-main); margin: 0.25rem 0 0 0;"><?= substr($row['descripcion'], 0, 50) ?>...</p>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p style="color: var(--text-secondary); text-align: center; padding: 1rem;">No hay asambleas registradas</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Últimos Tequios -->
                <div class="glass-panel">
                    <h3 style="color: var(--text-main); margin-bottom: 1rem; font-size: 1.1rem;">Últimos Tequios</h3>
                    <div style="max-height: 250px; overflow-y: auto;">
                        <?php if ($q_ultimos_tequios && pg_num_rows($q_ultimos_tequios) > 0): ?>
                            <?php while ($row = pg_fetch_assoc($q_ultimos_tequios)): ?>
                                <div style="padding: 0.75rem; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; align-items: start; gap: 0.75rem;">
                                    <i class="fas fa-hammer" style="color: var(--warning); margin-top: 0.25rem;"></i>
                                    <div>
                                        <p style="font-size: 0.9rem; color: var(--text-secondary); margin: 0;"><?= date('d/m/Y', strtotime($row['fecha'])) ?></p>
                                        <p style="font-size: 0.95rem; color: var(--text-main); margin: 0.25rem 0 0 0;"><?= substr($row['descripcion'], 0, 50) ?>...</p>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p style="color: var(--text-secondary); text-align: center; padding: 1rem;">No hay tequios registrados</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            </div><!-- /z-index wrapper -->
        </div>
    </main>
</body>
</html>
