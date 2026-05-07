<?php
require_once '../../Shared/Infrastructure/Database/Connection.php';

$pageTitle = "Pago Predial";
$activePage = "predial";

$anio_filtro = isset($_GET['anio']) ? (int)$_GET['anio'] : date('Y');

// Obtener lista completa de comuneros y su estatus de pago en el año seleccionado
$query = "
    SELECT c.id_comunero, c.numero_progresivo, c.nombre_completo, 
           p.id_pago, p.monto, p.fecha_pago, p.pagado
    FROM comunero c
    LEFT JOIN pago_predial p ON c.id_comunero = p.id_comunero AND p.anio = $anio_filtro
    WHERE c.activo = TRUE
    ORDER BY c.numero_progresivo ASC
";
$resultado = pg_query($conexion, $query);

// Totales rápidos para dashboard
$q_totales = pg_query($conexion, "
    SELECT 
        COUNT(*) as total_comuneros,
        SUM(CASE WHEN p.pagado = TRUE THEN 1 ELSE 0 END) as total_pagados,
        COALESCE(SUM(CASE WHEN p.pagado = TRUE THEN p.monto ELSE 0 END), 0) as total_recaudado
    FROM comunero c
    LEFT JOIN pago_predial p ON c.id_comunero = p.id_comunero AND p.anio = $anio_filtro
    WHERE c.activo = TRUE
");
$totales = pg_fetch_assoc($q_totales);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SysComunal | Predial</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= $GLOBALS['ASSETS_URL'] ?>/Css/style.css">
    <style>
        .badge-success { background: rgba(16, 185, 129, 0.1); color: var(--secondary); padding: 0.25rem 0.75rem; border-radius: 999px; font-size: 0.75rem; font-weight: 600; }
        .badge-danger { background: rgba(239, 68, 68, 0.1); color: var(--danger); padding: 0.25rem 0.75rem; border-radius: 999px; font-size: 0.75rem; font-weight: 600; }
    </style>
</head>
<body>

    <?php include '../../Shared/Ui/Layout/sidebar.php'; ?>

    <main class="main-wrapper">
        <?php include '../../Shared/Ui/Layout/header.php'; ?>

        <div class="content-container animate-fade-in">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <div>
                    <h1 style="font-size: 1.75rem; color: var(--text-main); margin-bottom: 0.5rem;">Control de Predial - Año <?= $anio_filtro ?></h1>
                    <p style="color: var(--text-muted);">Registro de pagos y adeudos de comuneros</p>
                </div>
                <div>
                    <!-- Filtro de año con JS -->
                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                        <span style="font-weight: 500; font-size: 0.875rem;">Año:</span>
                        <select id="anioSelect" class="form-control" style="width: 120px; padding: 0.5rem; text-align: center; border: 1px solid var(--border); border-radius: var(--radius-md);" onchange="cambiarAnio(this.value)">
                            <?php 
                            $anio_actual = date('Y');
                            for ($anio = $anio_actual; $anio >= 2000; $anio--) {
                                $selected = ($anio == $anio_filtro) ? 'selected' : '';
                                echo "<option value='$anio' $selected>$anio</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
                
                <script>
                    function cambiarAnio(anio) {
                        // Mantener la URL actual y solo cambiar parámetro anio
                        const params = new URLSearchParams(window.location.search);
                        params.set('anio', anio);
                        window.location.search = '?' + params.toString();
                    }
                </script>
            </div>

            <!-- Dashboard Predial -->
            <div class="dashboard-grid" style="margin-bottom: 2rem;">
                <div class="glass-panel stat-card" style="padding: 1.25rem;">
                    <div class="stat-icon primary" style="width: 50px; height: 50px; font-size: 1.25rem;"><i class="fas fa-users"></i></div>
                    <div class="stat-info">
                        <h3 style="font-size: 0.75rem;">Total Padron</h3>
                        <div class="stat-value" style="font-size: 1.5rem;"><?= number_format($totales['total_comuneros']) ?></div>
                    </div>
                </div>
                <div class="glass-panel stat-card" style="padding: 1.25rem;">
                    <div class="stat-icon success" style="width: 50px; height: 50px; font-size: 1.25rem;"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-info">
                        <h3 style="font-size: 0.75rem;">Han Pagado</h3>
                        <div class="stat-value" style="font-size: 1.5rem;"><?= number_format($totales['total_pagados']) ?> <span style="font-size: 0.8rem; color: var(--text-muted); font-weight: normal;">(<?= round(($totales['total_pagados']/$totales['total_comuneros'])*100) ?>%)</span></div>
                    </div>
                </div>
                <div class="glass-panel stat-card" style="padding: 1.25rem;">
                    <div class="stat-icon danger" style="width: 50px; height: 50px; font-size: 1.25rem;"><i class="fas fa-exclamation-circle"></i></div>
                    <div class="stat-info">
                        <h3 style="font-size: 0.75rem;">Con Adeudo</h3>
                        <div class="stat-value" style="font-size: 1.5rem;"><?= number_format($totales['total_comuneros'] - $totales['total_pagados']) ?></div>
                    </div>
                </div>
                <div class="glass-panel stat-card" style="padding: 1.25rem;">
                    <div class="stat-icon warning" style="width: 50px; height: 50px; font-size: 1.25rem;"><i class="fas fa-money-bill-wave"></i></div>
                    <div class="stat-info">
                        <h3 style="font-size: 0.75rem;">Recaudado</h3>
                        <div class="stat-value" style="font-size: 1.5rem;">$<?= number_format($totales['total_recaudado'], 2) ?></div>
                    </div>
                </div>
            </div>

            <div class="glass-panel" style="padding: 1.5rem;">
                <div style="margin-bottom: 1.5rem;">
                    <input type="text" id="busquedaInput" placeholder="Buscar por nombre o número progresivo..." style="width: 100%; padding: 0.75rem; border: 1px solid var(--border); border-radius: var(--radius-md); font-size: 0.875rem;">
                </div>
                <div style="overflow-x: auto; max-height: 600px;">
                    <table id="tablaPredial" style="width: 100%; border-collapse: collapse; text-align: left;">
                        <thead style="position: sticky; top: 0; background: var(--surface-glass); backdrop-filter: blur(10px); z-index: 10;">
                            <tr style="border-bottom: 2px solid var(--border); color: var(--text-muted); font-weight: 600; font-size: 0.875rem;">
                                <th style="padding: 1rem 0.5rem;">N°</th>
                                <th style="padding: 1rem 0.5rem;">Comunero</th>
                                <th style="padding: 1rem 0.5rem;">Monto Aportación</th>
                                <th style="padding: 1rem 0.5rem;">Estatus</th>
                                <th style="padding: 1rem 0.5rem;">Fecha de Pago</th>
                                <th style="padding: 1rem 0.5rem; text-align: center;">Acción</th>
                            </tr>
                        </thead>
                        <tbody id="filasPredial">
                            <?php while ($row = pg_fetch_assoc($resultado)): ?>
                                <tr class="fila-predial" data-nombre="<?= strtolower(htmlspecialchars($row['nombre_completo'])) ?>" data-numero="<?= str_pad($row['numero_progresivo'], 4, '0', STR_PAD_LEFT) ?>" style="border-bottom: 1px solid var(--border); transition: var(--transition-fast);" onmouseover="this.style.backgroundColor='#f1f5f9'" onmouseout="this.style.backgroundColor='transparent'">
                                    <td style="padding: 1rem 0.5rem; font-weight: 500;"><?= str_pad($row['numero_progresivo'], 4, '0', STR_PAD_LEFT) ?></td>
                                    <td style="padding: 1rem 0.5rem;"><?= htmlspecialchars($row['nombre_completo']) ?></td>
                                    
                                    <form action="/proyectocomunitario/src/Predial/Application/guardar_pago.php" method="POST">
                                        <input type="hidden" name="id_comunero" value="<?= $row['id_comunero'] ?>">
                                        <input type="hidden" name="anio" value="<?= $anio_filtro ?>">
                                        <td style="padding: 1rem 0.5rem;">
                                            $ <input type="number" step="0.01" name="monto" value="<?= $row['monto'] ? htmlspecialchars($row['monto']) : '0' ?>" style="width: 80px; padding: 0.25rem; border: 1px solid var(--border); border-radius: var(--radius-md);" <?= $row['pagado'] == 't' ? 'readonly' : '' ?>>
                                        </td>
                                        <td style="padding: 1rem 0.5rem;">
                                            <?php if($row['pagado'] == 't'): ?>
                                                <span class="badge-success"><i class="fas fa-check"></i> Pagado</span>
                                            <?php else: ?>
                                                <span class="badge-danger"><i class="fas fa-times"></i> Adeudo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="padding: 1rem 0.5rem;">
                                            <?= $row['fecha_pago'] ? date('d/m/Y', strtotime($row['fecha_pago'])) : '-' ?>
                                        </td>
                                        <td style="padding: 1rem 0.5rem; text-align: center;">
                                            <?php if($row['pagado'] == 't'): ?>
                                                <!-- Deshacer pago -->
                                                <button type="button" onclick="if(confirm('¿Deshacer pago?')) window.location.href='/proyectocomunitario/src/Predial/Application/deshacer_pago.php?id_pago=<?= $row['id_pago'] ?>&anio=<?= $anio_filtro ?>';" class="btn" style="padding: 0.4rem 0.6rem; background: rgba(239, 68, 68, 0.1); color: var(--danger); border-radius: var(--radius-md);" title="Eliminar Pago">
                                                    <i class="fas fa-undo"></i>
                                                </button>
                                            <?php else: ?>
                                                <!-- Registrar Pago -->
                                                <button type="submit" class="btn" style="padding: 0.4rem 0.6rem; background: rgba(16, 185, 129, 0.1); color: var(--secondary); border-radius: var(--radius-md);" title="Registrar Pago">
                                                    <i class="fas fa-save"></i> Cobrar
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </form>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script>
        document.getElementById('busquedaInput').addEventListener('keyup', function() {
            const termino = this.value.toLowerCase();
            const filas = document.querySelectorAll('.fila-predial');
            
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
    </script>
</body>
</html>
