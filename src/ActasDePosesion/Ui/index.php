<?php
/**
 * SysComunal - Sistema de Gestión Comunal
 * Desarrollado por: Mario Ruiz Alavez
 * Correo: ruizmario04574@gmail.com
 */
require_once '../../Shared/Infrastructure/Database/Connection.php';

/** @var \PgSql\Connection $conexion */

$pageTitle = "Actas de Posesión";
$activePage = "actas";

// Obtener todas las actas (sin paginación para permitir búsqueda en tiempo real)
$query = "
    SELECT a.id_acta, a.fecha_acta, c.nombre_completo, c.numero_progresivo,
           (SELECT COUNT(*) FROM archivo ar WHERE ar.id_acta = a.id_acta) as num_archivos
    FROM acta_posesion a
    JOIN comunero c ON a.id_comunero = c.id_comunero
    WHERE c.activo = TRUE
    ORDER BY a.fecha_acta DESC
";
$resultado = pg_query($conexion, $query);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SysComunal | Actas</title>
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
                    <h1 style="font-size: 1.75rem; color: var(--text-main); margin-bottom: 0.5rem;">Actas de Posesión</h1>
                    <p style="color: var(--text-muted);">Registro de actas y documentos digitalizados</p>
                </div>
                <div>
                    <a href="/proyectocomunitario/public/index.php?page=actas_formulario" class="btn btn-primary">
                        <i class="fas fa-file-signature"></i> Registrar Acta
                    </a>
                </div>
            </div>

            <div class="glass-panel" style="padding: 1.5rem;">
                <div style="margin-bottom: 1.5rem;">
                    <input type="text" id="busquedaInput" placeholder="Buscar por nombre de comunero o número progresivo..." style="width: 100%; padding: 0.75rem; border: 1px solid var(--border); border-radius: var(--radius-md); font-size: 0.875rem;">
                </div>
                <div style="overflow-x: auto; max-height: 600px;">
                    <table style="width: 100%; border-collapse: collapse; text-align: left;">
                        <thead>
                            <tr style="border-bottom: 2px solid var(--border); color: var(--text-muted); font-weight: 600; font-size: 0.875rem;">
                                <th style="padding: 1rem 0.5rem;">Folio / COM</th>
                                <th style="padding: 1rem 0.5rem;">Comunero</th>
                                <th style="padding: 1rem 0.5rem;">Fecha de Acta</th>
                                <th style="padding: 1rem 0.5rem;">Archivos</th>
                                <th style="padding: 1rem 0.5rem; text-align: center;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (pg_num_rows($resultado) > 0): ?>
                                <?php while ($row = pg_fetch_assoc($resultado)): ?>
                                    <tr class="fila-acta" data-nombre="<?= strtolower(htmlspecialchars($row['nombre_completo'])) ?>" data-numero="<?= str_pad($row['numero_progresivo'], 4, '0', STR_PAD_LEFT) ?>" style="border-bottom: 1px solid var(--border); transition: var(--transition-fast);" onmouseover="this.style.backgroundColor='#f1f5f9'" onmouseout="this.style.backgroundColor='transparent'">
                                        <td style="padding: 1rem 0.5rem; font-weight: 500;"><?= str_pad($row['numero_progresivo'], 4, '0', STR_PAD_LEFT) ?></td>
                                        <td style="padding: 1rem 0.5rem;"><?= htmlspecialchars($row['nombre_completo']) ?></td>
                                        <td style="padding: 1rem 0.5rem;"><?= date('d/m/Y', strtotime($row['fecha_acta'])) ?></td>
                                        <td style="padding: 1rem 0.5rem;">
                                            <span style="background: rgba(16, 185, 129, 0.1); color: var(--secondary); padding: 0.25rem 0.75rem; border-radius: 999px; font-size: 0.75rem; font-weight: 600;">
                                                <?= $row['num_archivos'] ?> Documento(s)
                                            </span>
                                        </td>
                                        <td style="padding: 1rem 0.5rem; text-align: center;">
                                            <div style="display: flex; gap: 0.5rem; justify-content: center;">
                                                <a href="/proyectocomunitario/public/index.php?page=actas_ver&id=<?= $row['id_acta'] ?>" class="btn" style="padding: 0.4rem 0.6rem; background: rgba(16, 185, 129, 0.1); color: var(--secondary); border-radius: var(--radius-md);" title="Ver Información">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="/proyectocomunitario/public/index.php?page=actas_formulario&id=<?= $row['id_acta'] ?>" class="btn" style="padding: 0.4rem 0.6rem; background: rgba(245, 158, 11, 0.1); color: var(--warning); border-radius: var(--radius-md);" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" onclick="abrirModalEliminar('<?= $row['id_acta'] ?>', '<?= htmlspecialchars($row['nombre_completo']) ?>', '<?= date('d/m/Y', strtotime($row['fecha_acta'])) ?>');" class="btn" style="padding: 0.4rem 0.6rem; background: rgba(239, 68, 68, 0.1); color: var(--danger); border-radius: var(--radius-md);" title="Eliminar">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" style="padding: 2rem; text-align: center; color: var(--text-muted);">
                                        No hay actas registradas.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>


            </div>
        </div>
    </main>

    <!-- Modal de Confirmación para Eliminar -->
    <div id="modalEliminar" class="modal-delete">
        <div class="modal-delete-content">
            <div class="modal-delete-header">
                <div class="modal-delete-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h2 class="modal-delete-title">¿Eliminar Acta?</h2>
            </div>
            <p class="modal-delete-description">
                Está a punto de eliminar el acta del comunero <strong id="nombreComunero"></strong> (Fecha: <strong id="fechaActa"></strong>). Esta acción no se puede deshacer.
            </p>
            <div class="modal-delete-footer">
                <button type="button" onclick="cerrarModalEliminar();" class="btn-modal btn-modal-cancel">Cancelar</button>
                <button type="button" onclick="confirmarEliminar();" class="btn-modal btn-modal-delete">Eliminar</button>
            </div>
        </div>
    </div>

    <script>
        let idActaEliminar = null;

        function abrirModalEliminar(id, nombre, fecha) {
            idActaEliminar = id;
            document.getElementById('nombreComunero').textContent = nombre;
            document.getElementById('fechaActa').textContent = fecha;
            document.getElementById('modalEliminar').classList.add('active');
        }

        function cerrarModalEliminar() {
            document.getElementById('modalEliminar').classList.remove('active');
            idActaEliminar = null;
        }

        function confirmarEliminar() {
            if (!idActaEliminar) return;

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '/proyectocomunitario/src/ActasDePosesion/Application/acciones.php';

            const inputAccion = document.createElement('input');
            inputAccion.type = 'hidden';
            inputAccion.name = 'accion';
            inputAccion.value = 'eliminar';

            const inputId = document.createElement('input');
            inputId.type = 'hidden';
            inputId.name = 'id_acta';
            inputId.value = idActaEliminar;

            form.appendChild(inputAccion);
            form.appendChild(inputId);
            document.body.appendChild(form);
            form.submit();
        }

        // Cerrar modal al hacer clic en el fondo
        document.getElementById('modalEliminar').addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModalEliminar();
            }
        });

        // Búsqueda en tiempo real
        document.getElementById('busquedaInput').addEventListener('keyup', function() {
            const termino = this.value.toLowerCase();
            const filas = document.querySelectorAll('.fila-acta');

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
