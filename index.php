<?php
/**
 * Punto de entrada principal
 * Redirige a public/index.php
 */
header("Location: /proyectocomunitario/public/index.php");
exit;
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
    <!-- Estilos base -->
    <link rel="stylesheet" href="shared/css/style.css">
</head>
<body>

    <!-- Incluir Sidebar -->
    <?php include 'shared/layout/sidebar.php'; ?>

    <main class="main-wrapper">
        <!-- Incluir Header -->
        <?php include 'shared/layout/header.php'; ?>

        <div class="content-container animate-fade-in">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <div>
                    <h1 style="font-size: 1.75rem; color: var(--text-main); margin-bottom: 0.5rem;">Bienvenido al Sistema Comunal</h1>
                    <p style="color: var(--text-muted);">Municipio de San Bartolo Soyaltepec</p>
                </div>
                <div>
                    <a href="comuneros/formulario.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Nuevo Comunero
                    </a>
                </div>
            </div>

            <div class="dashboard-grid">
                <!-- Tarjeta 1 -->
                <div class="glass-panel stat-card">
                    <div class="stat-icon primary">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Comuneros</h3>
                        <div class="stat-value"><?= number_format($total_comuneros) ?></div>
                    </div>
                </div>

                <!-- Tarjeta 2 -->
                <div class="glass-panel stat-card">
                    <div class="stat-icon success">
                        <i class="fas fa-file-contract"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Actas Emitidas</h3>
                        <div class="stat-value"><?= number_format($total_actas) ?></div>
                    </div>
                </div>

                <!-- Tarjeta 3 -->
                <div class="glass-panel stat-card">
                    <div class="stat-icon warning">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Recaudado (<?= $anio_actual ?>)</h3>
                        <div class="stat-value">$<?= number_format($total_recaudado, 2) ?></div>
                    </div>
                </div>
                
                <!-- Tarjeta 4 -->
                <div class="glass-panel stat-card">
                    <div class="stat-icon danger">
                        <i class="fas fa-hands-helping"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Próximo Tequio</h3>
                        <div class="stat-value" style="font-size: 1.25rem; margin-top: 0.2rem;">Sin programar</div>
                    </div>
                </div>
            </div>

            <!-- Sección inferior con diseño tipo glassmorphism -->
            <div class="glass-panel" style="padding: 1.5rem;">
                <h3 style="margin-bottom: 1rem; color: var(--text-main); font-weight: 600;">Accesos Rápidos</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                    <a href="mapa/index.php" style="text-decoration: none; color: inherit;">
                        <div style="padding: 1rem; border: 1px solid var(--border); border-radius: var(--radius-md); display: flex; align-items: center; gap: 1rem; transition: var(--transition-fast);" onmouseover="this.style.borderColor='var(--primary)'" onmouseout="this.style.borderColor='var(--border)'">
                            <i class="fas fa-map text-muted" style="color: var(--primary); font-size: 1.5rem;"></i>
                            <span style="font-weight: 500;">Ver Mapa Parcelario</span>
                        </div>
                    </a>
                    
                    <a href="asambleas/index.php" style="text-decoration: none; color: inherit;">
                        <div style="padding: 1rem; border: 1px solid var(--border); border-radius: var(--radius-md); display: flex; align-items: center; gap: 1rem; transition: var(--transition-fast);" onmouseover="this.style.borderColor='var(--primary)'" onmouseout="this.style.borderColor='var(--border)'">
                            <i class="fas fa-users-cog text-muted" style="color: var(--secondary); font-size: 1.5rem;"></i>
                            <span style="font-weight: 500;">Pase de Lista Asamblea</span>
                        </div>
                    </a>
                </div>
            </div>
            
        </div>
    </main>
</body>
</html>
