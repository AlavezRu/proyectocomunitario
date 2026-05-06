<?php
require_once '../../Shared/Infrastructure/Database/Connection.php';

$pageTitle = "Formulario de Tequio";
$activePage = "tequios";

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SysComunal | Programar Tequio</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= $GLOBALS['ASSETS_URL'] ?>/Css/style.css">
    <style>
        .form-group { margin-bottom: 1.25rem; }
        .form-label { display: block; margin-bottom: 0.5rem; font-weight: 500; font-size: 0.875rem; color: var(--text-main); }
        .form-control { width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--border); border-radius: var(--radius-md); font-family: inherit; font-size: 0.9rem; transition: var(--transition-fast); background: var(--surface); }
        .form-control:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1); }
    </style>
</head>
<body>

    <?php include '../../Shared/Ui/Layout/sidebar.php'; ?>

    <main class="main-wrapper">
        <?php include '../../Shared/Ui/Layout/header.php'; ?>

        <div class="content-container animate-fade-in">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <div>
                    <h1 style="font-size: 1.75rem; color: var(--text-main); margin-bottom: 0.5rem;">Programar Tequio</h1>
                    <p style="color: var(--text-muted);">Registre una nueva faena comunal</p>
                </div>
                <div>
                    <button type="submit" form="frmTequio" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar Tequio
                    </button>
                    <a href="/proyectocomunitariov3/public/index.php?page=tequios" class="btn" style="background: white; border: 1px solid var(--border); margin-left: 0.5rem;">Cancelar</a>
                </div>
            </div>

            <form id="frmTequio" action="/proyectocomunitariov3/src/Tequios/Application/guardar_tequio.php" method="POST">
                <div class="glass-panel" style="padding: 2rem; max-width: 600px;">
                    <div class="form-group">
                        <label class="form-label">Fecha del Tequio *</label>
                        <input type="date" name="fecha" class="form-control" required value="<?= date('Y-m-d') ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Descripción de la Faena *</label>
                        <textarea name="descripcion" class="form-control" rows="4" required placeholder="Ej. Limpieza del panteón municipal..."></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Observaciones (qué se hizo bien)</label>
                        <textarea name="observacion" class="form-control" rows="4" placeholder="Ej. Se completó en el tiempo estimado, excelente participación..."></textarea>
                    </div>
                </div>
            </form>
        </div>
    </main>
</body>
</html>
