<?php
require_once '../../Shared/Infrastructure/Database/Connection.php';

$id_asamblea = isset($_GET['id_asamblea']) ? (int)$_GET['id_asamblea'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);

$pageTitle = "Pase de Lista - Asamblea";
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

// Obtener datos de la asamblea
$q_asamblea = pg_query_params($conexion, "SELECT * FROM asamblea WHERE id_asamblea = $1", [$id_asamblea]);
if (pg_num_rows($q_asamblea) == 0) {
    header("Location: /proyectocomunitariov3/public/index.php?page=asambleas");
    exit;
}
$asamblea = pg_fetch_assoc($q_asamblea);

// Obtener comuneros y su asitencia
$query = "
    SELECT c.id_comunero, c.numero_progresivo, c.nombre_completo, COALESCE(aa.asistio, FALSE) as asistio
    FROM comunero c
    LEFT JOIN asistencia_asamblea aa ON c.id_comunero = aa.id_comunero AND aa.id_asamblea = $id_asamblea
    WHERE c.activo = TRUE
    ORDER BY c.numero_progresivo ASC
";
$resultado = pg_query($conexion, $query);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SysComunal | Pase Lista Asamblea</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= $GLOBALS['ASSETS_URL'] ?>/Css/style.css">
    <style>
        .switch { position: relative; display: inline-block; width: 44px; height: 24px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #cbd5e1; transition: .4s; border-radius: 24px; }
        .slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: var(--secondary); }
        input:checked + .slider:before { transform: translateX(20px); }
        
        .search-bar { 
            display: flex; 
            gap: 0.5rem; 
            margin-bottom: 1.5rem; 
            padding: 1rem; 
            background: white; 
            border-radius: 8px; 
            border: 1px solid var(--border);
        }
        .search-bar input { 
            flex: 1; 
            padding: 0.6rem; 
            border: 1px solid var(--border); 
            border-radius: 6px; 
            font-size: 0.95rem;
        }
        .btn-action { 
            background-color: var(--secondary); 
            color: white; 
            border: none; 
            padding: 0.6rem 1rem; 
            border-radius: 6px; 
            cursor: pointer; 
            font-weight: 500;
            transition: var(--transition-fast);
        }
        .btn-action:hover { 
            opacity: 0.9; 
        }
        .row-hidden { 
            display: none; 
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
                    <h1 style="font-size: 1.75rem; color: var(--text-main); margin-bottom: 0.5rem;">Pase de Lista Asamblea</h1>
                    <p style="color: var(--text-muted); font-weight: 500;"><?= date('d/m/Y', strtotime($asamblea['fecha'])) ?> - <?= htmlspecialchars($asamblea['descripcion']) ?></p>
                </div>
                <div>
                    <button type="submit" form="frmLista" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar Lista
                    </button>
                    <a href="/proyectocomunitariov3/public/index.php?page=asambleas" class="btn" style="background: white; border: 1px solid var(--border); margin-left: 0.5rem;">Volver</a>
                </div>
            </div>

            <form id="frmLista" action="/proyectocomunitariov3/src/Asambleas/Application/guardar_lista.php" method="POST">
                <input type="hidden" name="id_asamblea" value="<?= $id_asamblea ?>">
                
                <!-- Buscador y Botones de Acción -->
                <div class="search-bar">
                    <input type="text" id="searchInput" placeholder="🔍 Buscar comunero por nombre o número..." style="color: var(--text-main);">
                    <button type="button" class="btn-action" onclick="marcarTodos()">
                        <i class="fas fa-check-double"></i> Marcar Todos
                    </button>
                    <button type="button" class="btn-action" onclick="desmarcarTodos()" style="background-color: #e74c3c;">
                        <i class="fas fa-times"></i> Desmarcar Todos
                    </button>
                </div>
                
                <div class="glass-panel" style="padding: 1.5rem;">
                    <div style="overflow-x: auto; max-height: 600px;">
                        <table style="width: 100%; border-collapse: collapse; text-align: left;" id="tablaComuneros">
                            <thead style="position: sticky; top: 0; background: var(--surface-glass); backdrop-filter: blur(10px); z-index: 10;">
                                <tr style="border-bottom: 2px solid var(--border); color: var(--text-muted); font-weight: 600; font-size: 0.875rem;">
                                    <th style="padding: 1rem 0.5rem;">N°</th>
                                    <th style="padding: 1rem 0.5rem;">Nombre del Comunero</th>
                                    <th style="padding: 1rem 0.5rem; text-align: center;">Asistencia (Obligatoria)</th>
                                </tr>
                            </thead>
                            <tbody id="tablaCuerpo">
                                <?php while ($row = pg_fetch_assoc($resultado)): ?>
                                    <tr class="fila-comunero" data-numero="<?= str_pad($row['numero_progresivo'], 4, '0', STR_PAD_LEFT) ?>" data-nombre="<?= strtolower(htmlspecialchars($row['nombre_completo'])) ?>" style="border-bottom: 1px solid var(--border); transition: var(--transition-fast);" onmouseover="this.style.backgroundColor='#f1f5f9'" onmouseout="this.style.backgroundColor='transparent'">
                                        <td style="padding: 0.8rem 0.5rem; font-weight: 500;"><?= str_pad($row['numero_progresivo'], 4, '0', STR_PAD_LEFT) ?></td>
                                        <td style="padding: 0.8rem 0.5rem;"><?= htmlspecialchars($row['nombre_completo']) ?></td>
                                        <td style="padding: 0.8rem 0.5rem; text-align: center;">
                                            <label class="switch">
                                                <input type="checkbox" name="asistencia[<?= $row['id_comunero'] ?>]" value="1" <?= $row['asistio'] == 't' ? 'checked' : '' ?>>
                                                <span class="slider"></span>
                                            </label>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </form>
        </div>
    </main>
    
    <script>
        // Función para buscar comuneros
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const busqueda = this.value.toLowerCase();
            const filas = document.querySelectorAll('.fila-comunero');
            
            filas.forEach(fila => {
                const numero = fila.getAttribute('data-numero').toLowerCase();
                const nombre = fila.getAttribute('data-nombre').toLowerCase();
                
                if (numero.includes(busqueda) || nombre.includes(busqueda)) {
                    fila.style.display = '';
                } else {
                    fila.style.display = 'none';
                }
            });
        });
        
        // Función para marcar todos
        function marcarTodos() {
            const checkboxes = document.querySelectorAll('.fila-comunero:not([style*="display: none"]) input[type="checkbox"]');
            checkboxes.forEach(checkbox => {
                checkbox.checked = true;
            });
        }
        
        // Función para desmarcar todos
        function desmarcarTodos() {
            const checkboxes = document.querySelectorAll('.fila-comunero:not([style*="display: none"]) input[type="checkbox"]');
            checkboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
        }
    </script>
</body>
</html>
