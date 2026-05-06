<?php
require_once '../../Shared/Infrastructure/Database/Connection.php';

$pageTitle = "Formulario de Asamblea";
$activePage = "asambleas";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SysComunal | Nueva Asamblea</title>
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
                    <h1 style="font-size: 1.75rem; color: var(--text-main); margin-bottom: 0.5rem;">Registrar Asamblea</h1>
                    <p style="color: var(--text-muted);">Programe una asamblea y capture sus datos generales</p>
                </div>
                <div>
                    <button type="submit" form="frmAsamblea" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar Asamblea
                    </button>
                    <a href="/proyectocomunitariov3/public/index.php?page=asambleas" class="btn" style="background: white; border: 1px solid var(--border); margin-left: 0.5rem;">Cancelar</a>
                </div>
            </div>

            <form id="frmAsamblea" action="/proyectocomunitariov3/src/Asambleas/Application/guardar_asamblea.php" method="POST">
                <div class="glass-panel" style="padding: 2rem; max-width: 600px;">
                    <div class="form-group">
                        <label class="form-label">Fecha de Asamblea *</label>
                        <input type="date" name="fecha" class="form-control" required value="<?= date('Y-m-d') ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Descripción / Motivo / Orden del Día *</label>
                        <textarea name="descripcion" class="form-control" rows="5" required placeholder="Ej. Asamblea General Ordinaria..."></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Observaciones (qué se hizo bien)</label>
                        <textarea name="observacion" class="form-control" rows="4" placeholder="Ej. Buena participación, decisiones importantes tomadas..."></textarea>
                    </div>
                </div>
            </form>
        </div>
    </main>
</body>
</html>
