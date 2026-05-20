<?php
require_once '../../Shared/Infrastructure/Database/Connection.php';

/** @var \PgSql\Connection $conexion */

$pageTitle = "Asambleas Comunales";
$activePage = "asambleas";

// Crear tabla si no existe
$create_table = "
CREATE TABLE IF NOT EXISTS asistencia_asamblea (
    id_asistencia SERIAL PRIMARY KEY,
    id_asamblea INTEGER NOT NULL,
    id_comunero INTEGER NOT NULL,
    asistio BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(id_asamblea, id_comunero),
    FOREIGN KEY (id_asamblea) REFERENCES asamblea(id_asamblea) ON DELETE CASCADE,
    FOREIGN KEY (id_comunero) REFERENCES comunero(id_comunero) ON DELETE CASCADE
);
";
pg_query($conexion, $create_table);

$por_pagina = 10;
$pagina_actual = isset($_GET['p']) ? (int)$_GET['p'] : 1;
if ($pagina_actual < 1) $pagina_actual = 1;
$offset = ($pagina_actual - 1) * $por_pagina;

$q_total = pg_query($conexion, "SELECT COUNT(*) FROM asamblea");
$total_asambleas = pg_fetch_result($q_total, 0, 0);
$total_paginas = ceil($total_asambleas / $por_pagina);

$q_asambleas = pg_query($conexion, "SELECT a.*, (SELECT COUNT(*) FROM asistencia_asamblea asis WHERE asis.id_asamblea = a.id_asamblea AND asis.asistio = TRUE) as asistentes FROM asamblea a ORDER BY a.fecha DESC LIMIT $por_pagina OFFSET $offset");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SysComunal | Asambleas</title>
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
    </style>
</head>
<body>

    <?php include '../../Shared/Ui/Layout/sidebar.php'; ?>

    <main class="main-wrapper">
        <?php include '../../Shared/Ui/Layout/header.php'; ?>

        <div class="content-container animate-fade-in">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <div>
                    <h1 style="font-size: 1.75rem; color: var(--text-main); margin-bottom: 0.5rem;">Registro de Asambleas</h1>
                    <p style="color: var(--text-muted);">Administración de asambleas generales y extraordinarias</p>
                </div>
                <div>
                    <a href="/proyectocomunitario/public/index.php?page=asambleas_formulario" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Nueva Asamblea
                    </a>
                </div>
            </div>

            <div class="glass-panel" style="padding: 1.5rem;">
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; text-align: left;">
                        <thead>
                            <tr style="border-bottom: 2px solid var(--border); color: var(--text-muted); font-weight: 600; font-size: 0.875rem;">
                                <th style="padding: 1rem 0.5rem;">Folio</th>
                                <th style="padding: 1rem 0.5rem;">Descripción / Motivo</th>
                                <th style="padding: 1rem 0.5rem;">Observaciones</th>
                                <th style="padding: 1rem 0.5rem;">Fecha</th>
                                <th style="padding: 1rem 0.5rem;">Quórum (Asistentes)</th>
                                <th style="padding: 1rem 0.5rem; text-align: center;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (pg_num_rows($q_asambleas) > 0): ?>
                                <?php while ($row = pg_fetch_assoc($q_asambleas)): ?>
                                    <tr style="border-bottom: 1px solid var(--border); transition: var(--transition-fast);" onmouseover="this.style.backgroundColor='#f1f5f9'" onmouseout="this.style.backgroundColor='transparent'">
                                        <td style="padding: 1rem 0.5rem; font-weight: 500;">ASM-<?= str_pad($row['id_asamblea'], 4, '0', STR_PAD_LEFT) ?></td>
                                        <td style="padding: 1rem 0.5rem;"><?= htmlspecialchars($row['descripcion']) ?></td>
                                        <td style="padding: 1rem 0.5rem; font-size: 0.875rem; color: var(--text-muted);">
                                            <?php if (!empty($row['observacion'])): ?>
                                                <span title="<?= htmlspecialchars($row['observacion']) ?>" style="cursor: help; border-bottom: 1px dotted var(--primary);">
                                                    <?= htmlspecialchars(substr($row['observacion'], 0, 40)) ?>...
                                                </span>
                                            <?php else: ?>
                                                <span style="color: #cbd5e1;">Sin observaciones</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="padding: 1rem 0.5rem; font-weight: 500;"><?= date('d/m/Y', strtotime($row['fecha'])) ?></td>
                                        <td style="padding: 1rem 0.5rem;">
                                            <span style="background: rgba(16, 185, 129, 0.1); color: var(--secondary); padding: 0.25rem 0.75rem; border-radius: 999px; font-size: 0.75rem; font-weight: 600;">
                                                <?= $row['asistentes'] ?> Comuneros
                                            </span>
                                        </td>
                                        <td style="padding: 1rem 0.5rem; text-align: center; display: flex; gap: 0.5rem; justify-content: center;">
                                            <!-- Editar -->
                                            <a href="/proyectocomunitario/public/index.php?page=asambleas_editar&id=<?= $row['id_asamblea'] ?>" class="btn" style="padding: 0.3rem 0.5rem; font-size: 0.8rem; background: rgba(251, 146, 60, 0.1); color: #ea580c; border-radius: var(--radius-md);" title="Editar Asamblea">
                                                <i class="fas fa-edit"></i> Editar
                                            </a>
                                            <!-- Pase de lista -->
                                            <a href="/proyectocomunitario/public/index.php?page=asambleas_pase_lista&id=<?= $row['id_asamblea'] ?>" class="btn" style="padding: 0.3rem 0.5rem; font-size: 0.8rem; background: rgba(37, 99, 235, 0.1); color: var(--primary); border-radius: var(--radius-md);" title="Pase de Lista">
                                                <i class="fas fa-users-cog"></i> Pase Lista
                                            </a>
                                            <!-- Eliminar -->
                                            <button type="button" onclick="abrirModalEliminarAsamblea('<?= $row['id_asamblea'] ?>', '<?= htmlspecialchars($row['descripcion'], ENT_QUOTES) ?>', '<?= date('d/m/Y', strtotime($row['fecha'])) ?>');" class="btn" style="padding: 0.3rem 0.5rem; font-size: 0.8rem; background: rgba(239, 68, 68, 0.1); color: var(--danger); border-radius: var(--radius-md);" title="Eliminar Asamblea">
                                                <i class="fas fa-trash"></i> Eliminar
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="padding: 2rem; text-align: center; color: var(--text-muted);">
                                        No hay asambleas registradas.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <?php if ($total_paginas > 1): ?>
                <div style="display: flex; justify-content: center; gap: 0.5rem; margin-top: 2rem;">
                    <?php for ($i=1; $i<=$total_paginas; $i++): ?>
                        <a href="/proyectocomunitario/public/index.php?page=asambleas&p=<?= $i ?>" 
                           style="width: 35px; height: 35px; display: flex; align-items: center; justify-content: center; border-radius: var(--radius-md); text-decoration: none; font-weight: 500; font-size: 0.875rem;
                           <?= $i == $pagina_actual ? 'background: var(--primary); color: white; box-shadow: var(--shadow-md);' : 'background: white; border: 1px solid var(--border); color: var(--text-muted);' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>

            </div>
        </div>
    </main>

    <div id="modalEliminarAsamblea" class="modal-delete">
        <div class="modal-delete-content">
            <div class="modal-delete-header">
                <div class="modal-delete-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h2 class="modal-delete-title">¿Eliminar asamblea?</h2>
            </div>

            <p class="modal-delete-description">
                Esta acción eliminará la asamblea y su pase de lista. No se puede deshacer.
            </p>

            <div class="modal-delete-info">
                <strong>Información de la asamblea:</strong>
                <div id="infoAsambleaEliminar" style="margin-top: 0.5rem;"></div>
            </div>

            <div class="modal-delete-footer">
                <button type="button" class="btn-modal btn-modal-cancel" onclick="cerrarModalEliminarAsamblea()">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="button" class="btn-modal btn-modal-delete" onclick="confirmarEliminarAsamblea()">
                    <i class="fas fa-trash"></i> Eliminar
                </button>
            </div>
        </div>
    </div>

    <script>
        let idAsambleaEliminar = null;

        function abrirModalEliminarAsamblea(id, descripcion, fecha) {
            idAsambleaEliminar = id;
            document.getElementById('infoAsambleaEliminar').textContent = descripcion + ' - ' + fecha;
            document.getElementById('modalEliminarAsamblea').classList.add('active');
        }

        function cerrarModalEliminarAsamblea() {
            document.getElementById('modalEliminarAsamblea').classList.remove('active');
            idAsambleaEliminar = null;
        }

        function confirmarEliminarAsamblea() {
            if (idAsambleaEliminar) {
                window.location.href = '/proyectocomunitario/src/Asambleas/Application/eliminar_asamblea.php?id=' + idAsambleaEliminar;
            }
        }

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                cerrarModalEliminarAsamblea();
            }
        });

        document.getElementById('modalEliminarAsamblea').addEventListener('click', function(event) {
            if (event.target === this) {
                cerrarModalEliminarAsamblea();
            }
        });
    </script>
</body>
</html>
