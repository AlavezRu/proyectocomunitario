<?php
require_once '../../Shared/Infrastructure/Database/Connection.php';

$pageTitle = "Editar Asamblea";
$activePage = "asambleas";

$id_asamblea = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_asamblea <= 0) {
    header("Location: /proyectocomunitario/public/index.php?page=asambleas&error=invalid_id");
    exit;
}

// Obtener datos de la asamblea
$sql = "SELECT * FROM asamblea WHERE id_asamblea = $1";
$result = pg_query_params($conexion, $sql, [$id_asamblea]);
$asamblea = pg_fetch_assoc($result);

if (!$asamblea) {
    header("Location: /proyectocomunitario/public/index.php?page=asambleas&error=not_found");
    exit;
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SysComunal | Editar Asamblea</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= $GLOBALS['ASSETS_URL'] ?>/Css/style.css">
    <style>
        .form-group { margin-bottom: 1.25rem; }
        .form-label { display: block; margin-bottom: 0.5rem; font-weight: 500; font-size: 0.875rem; color: var(--text-main); }
        .form-control { width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--border); border-radius: var(--radius-md); font-family: inherit; font-size: 0.9rem; transition: var(--transition-fast); background: var(--surface); }
        .form-control:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1); }
        .info-box { background: rgba(37, 99, 235, 0.1); padding: 1rem; border-radius: var(--radius-md); border-left: 4px solid var(--primary); margin-bottom: 1.5rem; }
    </style>
</head>
<body>

    <?php include '../../Shared/Ui/Layout/sidebar.php'; ?>

    <main class="main-wrapper">
        <?php include '../../Shared/Ui/Layout/header.php'; ?>

        <div class="content-container animate-fade-in">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <div>
                    <h1 style="font-size: 1.75rem; color: var(--text-main); margin-bottom: 0.5rem;">Editar Asamblea</h1>
                    <p style="color: var(--text-muted);">Actualice la descripción y observaciones de la asamblea</p>
                </div>
                <div>
                    <button type="submit" form="frmEditarAsamblea" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
                    <a href="/proyectocomunitario/public/index.php?page=asambleas" class="btn" style="background: white; border: 1px solid var(--border); margin-left: 0.5rem;">Cancelar</a>
                </div>
            </div>

            <form id="frmEditarAsamblea" action="/proyectocomunitario/src/Asambleas/Application/editar_asamblea.php" method="POST">
                <input type="hidden" name="id_asamblea" value="<?= $id_asamblea ?>">
                
                <div class="glass-panel" style="padding: 2rem; max-width: 600px;">
                    <div class="info-box">
                        <strong><i class="fas fa-info-circle"></i> Información General</strong>
                        <p style="margin-top: 0.5rem; font-size: 0.9rem;">
                            Asamblea ID: <strong>ASM-<?= str_pad($asamblea['id_asamblea'], 4, '0', STR_PAD_LEFT) ?></strong> | 
                            Fecha: <strong><?= date('d/m/Y', strtotime($asamblea['fecha'])) ?></strong>
                        </p>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Descripción / Motivo / Orden del Día</label>
                        <textarea name="descripcion" class="form-control" rows="5" required><?= htmlspecialchars($asamblea['descripcion']) ?></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Observaciones (qué se hizo bien)</label>
                        <textarea name="observacion" class="form-control" rows="4" placeholder="Ej. Buena participación, decisiones importantes tomadas..."><?= htmlspecialchars($asamblea['observacion'] ?? '') ?></textarea>
                    </div>
                </div>
            </form>
        </div>
    </main>
</body>
</html>
