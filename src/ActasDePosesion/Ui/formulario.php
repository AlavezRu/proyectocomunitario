<?php
require_once '../../Shared/Infrastructure/Database/Connection.php';

$pageTitle = "Formulario de Acta";
$activePage = "actas";

// Verificar si estamos editando
$id_acta = isset($_GET['id']) ? (int)$_GET['id'] : null;
$acta_existente = null;
$modo = "nuevo";
$documentos_existentes = [];

if ($id_acta) {
    $q_acta = pg_query_params($conexion, "SELECT a.*, c.nombre_completo, ST_AsGeoJSON(a.ubicacion) as geojson FROM acta_posesion a JOIN comunero c ON a.id_comunero = c.id_comunero WHERE a.id_acta = $1", [$id_acta]);
    if ($q_acta && pg_num_rows($q_acta) > 0) {
        $acta_existente = pg_fetch_assoc($q_acta);
        $modo = "editar";
        $pageTitle = "Editar Acta de Posesión";
        
        // Cargar documentos existentes
        $q_docs = pg_query_params($conexion, "SELECT id_archivo, nombre_archivo, ruta_archivo, tipo_archivo FROM archivo WHERE id_acta = $1 ORDER BY id_archivo DESC", [$id_acta]);
        while ($doc = pg_fetch_assoc($q_docs)) {
            $documentos_existentes[] = $doc;
        }
    }
}

// Listar comuneros activos para el select
$q_comuneros = pg_query($conexion, "SELECT id_comunero, numero_progresivo, nombre_completo FROM comunero WHERE activo = TRUE ORDER BY nombre_completo");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SysComunal | Nueva Acta</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Select2 para una mejor experiencia de búsqueda -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="<?= $GLOBALS['ASSETS_URL'] ?>/Css/style.css">
    <style>
        .form-group { margin-bottom: 1.25rem; }
        .form-label { display: block; margin-bottom: 0.5rem; font-weight: 500; font-size: 0.875rem; color: var(--text-main); }
        .form-control {
            width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--border); border-radius: var(--radius-md); font-family: inherit; font-size: 0.9rem; transition: var(--transition-fast); background: var(--surface);
        }
        .form-control:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1); }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
        .select2-container--default .select2-selection--single { height: 45px; border: 1px solid var(--border); border-radius: var(--radius-md); display: flex; align-items: center; }
        .select2-container--default .select2-selection--single .select2-selection__arrow { height: 43px; }
        #map { 
            height: 500px !important; 
            width: 100% !important; 
            border-radius: var(--radius-md); 
            border: 1px solid var(--border); 
            min-height: 400px;
        }
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; justify-content: center; align-items: center; }
        .modal-overlay.active { display: flex; }
        .modal-content { background: white; border-radius: var(--radius-lg); max-width: 98vw; max-height: 95vh; width: 98vw; display: flex; flex-direction: column; box-shadow: var(--shadow-xl); z-index: 10000; }
        .modal-header { padding: 1.5rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; flex-shrink: 0; }
        .modal-header h2 { margin: 0; font-size: 1.25rem; }
        .modal-body { flex: 1; overflow: auto; padding: 1.5rem; display: flex; flex-direction: column; min-height: 0; }
        .modal-body #map { height: 70vh !important; }
        .modal-footer { padding: 1.5rem; border-top: 1px solid var(--border); display: flex; gap: 1rem; justify-content: flex-end; flex-shrink: 0; }
        .btn-close { background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-muted); }
        .instructions-box { background: #f0f9ff; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1rem; border-left: 4px solid var(--primary); transition: var(--transition-normal); }
        .instructions-header { display: flex; align-items: center; justify-content: space-between; cursor: pointer; user-select: none; }
        .instructions-header:hover { opacity: 0.8; }
        .instructions-header p { margin: 0; font-size: 0.85rem; color: #1e40af; font-weight: 600; display: flex; align-items: center; gap: 0.5rem; }
        .instructions-header i { transition: transform var(--transition-normal); }
        .instructions-header.collapsed i { transform: rotate(-90deg); }
        .instructions-content { max-height: 200px; overflow: hidden; transition: max-height var(--transition-normal); }
        .instructions-content.collapsed { max-height: 0; }
        .instructions-content ul { margin: 0.5rem 0 0 1.5rem; font-size: 0.85rem; color: #1e40af; padding: 0; }
        .coordinates-table { width: 100%; border-collapse: collapse; margin-top: 1rem; max-height: 300px; display: block; overflow-y: auto; }
        .coordinates-table thead { display: table; width: 100%; }
        .coordinates-table tbody { display: block; overflow-y: auto; max-height: 250px; }
        .coordinates-table tr { display: table; width: 100%; }
        .coordinates-table th, .coordinates-table td { padding: 0.75rem; text-align: left; font-size: 0.85rem; border-bottom: 1px solid var(--border); }
        .coordinates-table th { background: #f0f9ff; font-weight: 600; color: #1e40af; position: sticky; top: 0; }
        .coordinates-table tbody tr:hover { background: #f0f9ff; }
        .coordinates-table td:nth-child(1) { width: 10%; color: #64748b; font-weight: 500; }
        .coordinates-table td:nth-child(2), .coordinates-table th:nth-child(2) { width: 40%; }
        .coordinates-table td:nth-child(3), .coordinates-table th:nth-child(3) { width: 40%; }
        .coordinates-table td:nth-child(4), .coordinates-table th:nth-child(4) { width: 10%; }
        .table-wrapper { border: 1px solid var(--border); border-radius: var(--radius-md); overflow: hidden; }
        .coord-cell { cursor: pointer; padding: 0.5rem !important; user-select: text; }
        .coord-cell:hover { background-color: #f0fdf4; }
        .coord-cell:focus { outline: 2px solid var(--primary); outline-offset: -2px; background-color: #e0f2fe; border-radius: 2px; }
        .coord-cell[contenteditable="true"]:empty:before { content: "clic para editar"; color: #cbd5e1; font-style: italic; }
        .btn-delete-point:hover { opacity: 0.7; }
        .leaflet-draw-toolbar { background: white; border: 1px solid var(--border); border-radius: var(--radius-md); }
        .leaflet-draw { z-index: 5 !important; }
        .leaflet-draw-toolbar a { width: 36px; height: 36px; line-height: 36px; }
        .leaflet-draw-actions { display: flex !important; appearance: none; background: #f5f5f5; border: 1px solid #ccc; border-radius: 4px; }
        /* Asegurar que Leaflet Draw sea visible */
        .leaflet-control-draw { position: absolute; top: 10px; left: 50px; padding: 0; }
        .leaflet-control-draw a { background: white; border: 1px solid #ccc; border-radius: 4px; width: 36px; height: 36px; display: line; margin: 2px; cursor: pointer; z-index: 1000; }
        .leaflet-control-draw a:hover { background: #f0f0f0; }
        .leaflet-control-draw a.leaflet-draw-edit-edit { background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 17.25V21h3.75L17.81 9.94m-6.75-6.75l2.5-2.5a2.828 2.828 0 114 0l2.5 2.5m-7.5 7.5L8.4 9.94"/></svg>') center no-repeat; background-size: 20px; }
        .leaflet-control-draw a.leaflet-draw-create-polygon { background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3l6 6m0 0l6-6m-6 6l-6 6m6-6l6 6m-6-6v12"/></svg>') center no-repeat; background-size: 20px; }
        @media (max-width: 768px) { 
            .grid-2 { grid-template-columns: 1fr; } 
            .modal-content { width: 95vw; }
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
                    <h1 style="font-size: 1.75rem; color: var(--text-main); margin-bottom: 0.5rem;">
                        <?= $modo === 'editar' ? 'Editar Acta de Posesión' : 'Registrar Acta de Posesión' ?>
                    </h1>
                    <p style="color: var(--text-muted);">
                        <?= $modo === 'editar' ? 'Actualice la información y documentación' : 'Capture la información y el documento escaneado' ?>
                    </p>
                </div>
                <div>
                    <button type="submit" form="frmActa" class="btn btn-primary">
                        <i class="fas fa-save"></i> <?= $modo === 'editar' ? 'Actualizar Acta' : 'Guardar Acta' ?>
                    </button>
                    <a href="/proyectocomunitariov3/public/index.php?page=actas" class="btn" style="background: white; border: 1px solid var(--border); margin-left: 0.5rem;">Cancelar</a>
                </div>
            </div>

            <form id="frmActa" action="/proyectocomunitariov3/src/ActasDePosesion/Application/acciones.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="accion" value="<?= $modo ?>">
                <?php if ($modo === 'editar'): ?>
                    <input type="hidden" name="id_acta" value="<?= $acta_existente['id_acta'] ?>">
                <?php endif; ?>

                <div class="glass-panel" style="padding: 2rem; margin-bottom: 2rem;">
                    <div class="grid-2">
                        <div class="form-group">
                            <label class="form-label">Comunero *</label>
                            <select name="id_comunero" id="id_comunero" class="form-control select2" required>
                                <option value="">Buscar comunero...</option>
                                <?php 
                                pg_result_seek($q_comuneros, 0);
                                while ($row = pg_fetch_assoc($q_comuneros)): ?>
                                    <option value="<?= $row['id_comunero'] ?>" <?= ($modo === 'editar' && $row['id_comunero'] == $acta_existente['id_comunero']) ? 'selected' : '' ?>>
                                        <?= str_pad($row['numero_progresivo'], 4, '0', STR_PAD_LEFT) ?> - <?= htmlspecialchars($row['nombre_completo']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Fecha del Acta *</label>
                            <input type="date" name="fecha_acta" class="form-control" required value="<?= $modo === 'editar' ? $acta_existente['fecha_acta'] : date('Y-m-d') ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Descripción del Predio *</label>
                        <textarea name="descripcion_predio" class="form-control" rows="3" required placeholder="Ej. Terreno de labor denominado..."><?= $modo === 'editar' ? htmlspecialchars($acta_existente['descripcion_predio']) : '' ?></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Colindancias *</label>
                        <textarea name="colindancias" class="form-control" rows="4" required placeholder="Norte: ...&#10;Sur: ...&#10;Este: ...&#10;Oeste: ..."><?= $modo === 'editar' ? htmlspecialchars($acta_existente['colindancias']) : '' ?></textarea>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="glass-panel" style="padding: 2rem;">
                        <h3 style="margin-bottom: 1rem; color: var(--text-main); font-weight: 600;">Georreferenciación del Terreno</h3>
                        <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 1.5rem;">Dibuja el polígono del terreno en el mapa interactivo. Usa las herramientas para delimitar los límites del predio.</p>
                        
                        <button type="button" id="btnAbrirMapa" class="btn btn-primary" style="width: 100%; justify-content: center; margin-bottom: 1rem;">
                            <i class="fas fa-map"></i> Abrir Mapa para Dibujar Terreno
                        </button>

                        <input type="hidden" name="ubicacion_geojson" id="ubicacion_geojson" value="<?= $modo === 'editar' && !empty($acta_existente['geojson']) ? htmlspecialchars($acta_existente['geojson']) : '' ?>">
                        
                        <div id="resumenUbicacion" style="padding: 1rem; background: #f0fdf4; border: 1px solid #86efac; border-radius: var(--radius-md); display: <?= $modo === 'editar' && !empty($acta_existente['geojson']) ? 'block' : 'none' ?>;">
                            <p style="margin: 0; color: #15803d; font-weight: 500;"><i class="fas fa-check-circle"></i> Terreno dibujado correctamente</p>
                        </div>
                    </div>

                    <div class="glass-panel" style="padding: 2rem;">
                        <h3 style="margin-bottom: 1rem; color: var(--text-main); font-weight: 600;">Documento Digitalizado</h3>
                        <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 1.5rem;">Suba el archivo escaneado del acta (PDF o Imagen).</p>

                        <div class="form-group" style="border: 2px dashed var(--border); padding: 3rem 1rem; text-align: center; border-radius: var(--radius-md); background: #f8fafc; transition: var(--transition-fast);" id="drop-zone">
                            <i class="fas fa-cloud-upload-alt" style="font-size: 3rem; color: var(--primary); margin-bottom: 1rem;"></i>
                            <div style="font-weight: 500; margin-bottom: 0.5rem;">Arrastre su archivo aquí o</div>
                            <input type="file" name="archivo_acta" id="archivo_acta" accept=".pdf,.jpg,.jpeg,.png" style="display: none;">
                            <button type="button" class="btn btn-primary" onclick="document.getElementById('archivo_acta').click()">Explorar Achivos</button>
                            <div id="file-name" style="margin-top: 1rem; font-size: 0.85rem; color: var(--secondary); font-weight: 600;"></div>
                        </div>

                        <?php if (!empty($documentos_existentes)): ?>
                        <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid var(--border);">
                            <h4 style="margin-bottom: 1rem; color: var(--text-main); font-weight: 600; display: flex; align-items: center; gap: 0.5rem;">
                                <i class="fas fa-file-check" style="color: #16a34a;"></i> Documentos Subidos
                            </h4>
                            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                                <?php foreach ($documentos_existentes as $doc): 
                                    $icon = $doc['tipo_archivo'] === 'pdf' ? 'fa-file-pdf' : 'fa-image';
                                    $color = $doc['tipo_archivo'] === 'pdf' ? '#dc2626' : '#3b82f6';
                                ?>
                                <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.75rem 1rem; background: #f8fafc; border-radius: var(--radius-md); border: 1px solid var(--border);">
                                    <div style="display: flex; align-items: center; gap: 0.75rem; flex: 1; min-width: 0;">
                                        <i class="fas <?= $icon ?>" style="color: <?= $color ?>; font-size: 1.25rem; flex-shrink: 0;"></i>
                                        <div style="flex: 1; min-width: 0;">
                                            <p style="margin: 0; color: var(--text-main); font-weight: 500; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                <?= htmlspecialchars($doc['nombre_archivo']) ?>
                                            </p>
                                        </div>
                                    </div>
                                    <div style="display: flex; gap: 0.5rem; flex-shrink: 0; margin-left: 0.75rem;">
                                        <a href="/proyectocomunitariov3/<?= htmlspecialchars($doc['ruta_archivo']) ?>" target="_blank" class="btn" style="padding: 0.5rem 0.75rem; font-size: 0.85rem; background: white; border: 1px solid var(--border); cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 0.25rem;" title="Descargar documento">
                                            <i class="fas fa-download"></i> Ver
                                        </a>
                                        <button type="button" class="btn-delete-doc" data-id="<?= $doc['id_archivo'] ?>" data-name="<?= htmlspecialchars($doc['nombre_archivo']) ?>" style="padding: 0.5rem 0.75rem; font-size: 0.85rem; background: white; border: 1px solid var(--danger); color: var(--danger); cursor: pointer; display: inline-flex; align-items: center; gap: 0.25rem; border-radius: var(--radius-md);" title="Eliminar documento">
                                            <i class="fas fa-trash-alt"></i> Eliminar
                                        </button>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    </main>

    <!-- Modal del Mapa -->
    <div id="mapaModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Dibujar Polígono del Terreno</h2>
                <button type="button" class="btn-close" id="btnCerrarMapa">&times;</button>
            </div>
            <div class="modal-body">
                <div class="instructions-box">
                    <div class="instructions-header collapsed" onclick="toggleInstructions()">
                        <p>
                            <i class="fas fa-chevron-down"></i>
                            <strong>Instrucciones</strong>
                        </p>
                    </div>
                    <div class="instructions-content collapsed">
                        <ul>
                            <li>Haz clic en el mapa para colocar los vértices del polígono</li>
                            <li>Presiona "Cerrar polígono" cuando hayas terminado</li>
                            <li>Usa "Deshacer" para eliminar el último punto</li>
                            <li>Usa "Limpiar" para comenzar de nuevo</li>
                        </ul>
                    </div>
                </div>
                
                <div id="map" style="height: 500px; width: 100%; margin-bottom: 1rem;"></div>

                <div style="margin-top: 1rem;">
                    <h4 style="margin: 0 0 0.75rem 0; font-size: 0.9rem; color: var(--text-main); font-weight: 600;">Coordenadas de los Puntos</h4>
                    <div class="table-wrapper">
                        <table class="coordinates-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Latitud <small style="font-weight: 400; color: #64748b;">(editable)</small></th>
                                    <th>Longitud <small style="font-weight: 400; color: #64748b;">(editable)</small></th>
                                    <th style="text-align: center; width: 40px;">Acción</th>
                                </tr>
                            </thead>
                            <tbody id="coordinatesTableBody">
                                <tr style="text-align: center; color: var(--text-muted);">
                                    <td colspan="4" style="padding: 1rem;">Haz clic en el mapa para agregar puntos</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div style="display: flex; gap: 0.5rem; margin-top: 1rem; flex-wrap: wrap;">
                    <button type="button" id="btnCerrarPoligono" class="btn btn-primary" style="flex: 1; min-width: 150px;">
                        <i class="fas fa-check"></i> Cerrar Polígono
                    </button>
                    <button type="button" id="btnDeshacerPunto" class="btn" style="flex: 1; min-width: 150px; background: white; border: 1px solid var(--border);">
                        <i class="fas fa-undo"></i> Deshacer
                    </button>
                    <button type="button" id="btnLimpiarMapa" class="btn" style="flex: 1; min-width: 150px; background: white; border: 1px solid var(--border);">
                        <i class="fas fa-trash"></i> Limpiar
                    </button>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" id="btnCancelarMapa" class="btn" style="background: white; border: 1px solid var(--border);">Cancelar</button>
                <button type="button" id="btnGuardarMapa" class="btn btn-primary">Guardar Polígono</button>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        let map = null;
        let poligonoPoints = [];
        let poligonoLayer = null;
        let dibujando = false;

        // Función para desplegar/retraer instrucciones
        function toggleInstructions() {
            const header = document.querySelector('.instructions-header');
            const content = document.querySelector('.instructions-content');
            
            header.classList.toggle('collapsed');
            content.classList.toggle('collapsed');
        }

        // Función para actualizar la tabla de coordenadas
        function actualizarTablaCoordinadas() {
            const tbody = document.getElementById('coordinatesTableBody');
            
            if (poligonoPoints.length === 0) {
                tbody.innerHTML = '<tr style="text-align: center; color: var(--text-muted);"><td colspan="4" style="padding: 1rem;">Haz clic en el mapa para agregar puntos</td></tr>';
                return;
            }

            tbody.innerHTML = poligonoPoints.map((point, index) => `
                <tr data-index="${index}">
                    <td>${index + 1}</td>
                    <td class="coord-cell coord-lat" contenteditable="true" data-type="lat">${point.lat.toFixed(6)}</td>
                    <td class="coord-cell coord-lng" contenteditable="true" data-type="lng">${point.lng.toFixed(6)}</td>
                    <td style="text-align: center; padding: 0.5rem;">
                        <button type="button" class="btn-delete-point" title="Eliminar punto" style="background: none; border: none; color: var(--danger); cursor: pointer; font-size: 1rem; padding: 0.25rem; line-height: 1;">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </td>
                </tr>
            `).join('');
            
            // Agregar event listeners a los botones de eliminar
            document.querySelectorAll('.btn-delete-point').forEach(btn => {
                btn.addEventListener('click', function() {
                    const row = this.closest('tr');
                    const index = parseInt(row.dataset.index);
                    
                    console.log('Eliminando punto:', index);
                    
                    // Eliminar del array
                    poligonoPoints.splice(index, 1);
                    
                    // Redibujar
                    actualizarTablaCoordinadas();
                    redibujarMapa();
                    
                    console.log('Punto eliminado. Puntos restantes:', poligonoPoints.length);
                });
            });
            
            // Agregar event listeners a las celdas editables
            document.querySelectorAll('.coord-cell').forEach(cell => {
                cell.addEventListener('blur', function() {
                    const row = this.closest('tr');
                    const index = parseInt(row.dataset.index);
                    const type = this.dataset.type;
                    const value = parseFloat(this.textContent.trim());
                    
                    console.log('Edición de celda - Índice:', index, 'Tipo:', type, 'Valor capturado:', value);
                    
                    // Validar que sea un número válido
                    if (isNaN(value)) {
                        console.error('Valor inválido, revertiendo...');
                        if (type === 'lat') {
                            this.textContent = poligonoPoints[index].lat.toFixed(6);
                        } else {
                            this.textContent = poligonoPoints[index].lng.toFixed(6);
                        }
                        return;
                    }
                    
                    // Validar rangos (latitud: -90 a 90, longitud: -180 a 180)
                    if ((type === 'lat' && (value < -90 || value > 90)) || 
                        (type === 'lng' && (value < -180 || value > 180))) {
                        console.error('Valor fuera de rango válido');
                        alert(type === 'lat' ? 'La latitud debe estar entre -90 y 90' : 'La longitud debe estar entre -180 y 180');
                        if (type === 'lat') {
                            this.textContent = poligonoPoints[index].lat.toFixed(6);
                        } else {
                            this.textContent = poligonoPoints[index].lng.toFixed(6);
                        }
                        return;
                    }
                    
                    // Actualizar el punto en el array
                    if (type === 'lat') {
                        poligonoPoints[index] = L.latLng(value, poligonoPoints[index].lng);
                    } else {
                        poligonoPoints[index] = L.latLng(poligonoPoints[index].lat, value);
                    }
                    
                    // Reformatear la celda a 6 decimales para consistencia
                    this.textContent = (type === 'lat' ? poligonoPoints[index].lat : poligonoPoints[index].lng).toFixed(6);
                    
                    console.log('Coordenada actualizada - Índice:', index, 'Tipo:', type, 'Nuevo valor:', value);
                    
                    // Redibujar el mapa
                    redibujarMapa();
                });
                
                // Permitir editar con Enter y Tab
                cell.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        this.blur();
                    }
                    if (e.key === 'Tab') {
                        e.preventDefault();
                        this.blur();
                        
                        // Mover a la siguiente celda editable
                        const allCells = Array.from(document.querySelectorAll('.coord-cell'));
                        const currentIndex = allCells.indexOf(this);
                        if (currentIndex < allCells.length - 1) {
                            allCells[currentIndex + 1].focus();
                        }
                    }
                });
                
                // Indicar visualmente que la celda es editable
                cell.addEventListener('focus', function() {
                    this.style.backgroundColor = '#e0f2fe';
                });
                
                cell.addEventListener('blur', function() {
                    this.style.backgroundColor = 'transparent';
                });
            });
        }
        
        // Función para redibujar el mapa después de editar coordenadas
        function redibujarMapa() {
            if (!map || !poligonoLayer) return;
            
            console.log('Redibujando mapa con nuevas coordenadas...');
            
            // Limpiar la capa
            poligonoLayer.clearLayers();
            
            // Redibujar puntos
            poligonoPoints.forEach((point, idx) => {
                L.circleMarker(point, {
                    radius: 6,
                    fillColor: '#2563eb',
                    color: '#1e40af',
                    weight: 2,
                    opacity: 1,
                    fillOpacity: 0.8
                }).addTo(poligonoLayer);
            });
            
            // Redibujar líneas entre puntos (si hay más de un punto)
            if (poligonoPoints.length > 1) {
                const coords = poligonoPoints.map(p => [p.lat, p.lng]);
                L.polyline(coords, {
                    color: '#2563eb',
                    weight: 2,
                    opacity: 0.8,
                    dashArray: '5,5'
                }).addTo(poligonoLayer);
            }
            
            // Ajustar el zoom si hay puntos
            if (poligonoPoints.length > 0) {
                const bounds = L.latLngBounds(poligonoPoints);
                try {
                    map.fitBounds(bounds, { padding: [50, 50] });
                } catch(e) {
                    console.error('Error al hacer zoom a los nuevos puntos:', e);
                }
            }
            
            console.log('Mapa redibujado exitosamente');
        }

        $(document).ready(function() {
            $('.select2').select2({
                placeholder: "Seleccione un comunero...",
                allowClear: true
            });
        });

        // Abrir modal del mapa
        document.getElementById('btnAbrirMapa').addEventListener('click', function(e) {
            e.preventDefault();
            console.log('=== Abriendo modal del mapa ===');
            
            const modal = document.getElementById('mapaModal');
            console.log('Modal encontrado:', modal);
            modal.classList.add('active');
            
            // Verificar si hay un GeoJSON guardado
            const ubicacionGeojson = document.getElementById('ubicacion_geojson').value;
            
            // Limpiar puntos si no hay GeoJSON guardado
            if (!ubicacionGeojson) {
                console.log('Sin GeoJSON anterior, limpiando puntos...');
                poligonoPoints = [];
                dibujando = false;
            }
            
            setTimeout(() => {
                console.log('Inicializando modal - puntos actuales:', poligonoPoints.length);
                
                // Destruir mapa anterior si existe
                if (map) {
                    console.log('Destruyendo mapa anterior...');
                    map.remove();
                    map = null;
                }
                
                console.log('Inicializando nuevo mapa...');
                inicializarMapa();
                
                try {
                    if (map) {
                        map.invalidateSize();
                        console.log('invalidateSize() ejecutado');
                    }
                } catch(err) {
                    console.error('Error en invalidateSize():', err);
                }
            }, 100);
        });

        // Cerrar modal
        document.getElementById('btnCerrarMapa').addEventListener('click', () => {
            console.log('Cerrando modal - limpiando mapa');
            if (map) {
                if (mapClickHandler) {
                    map.off('click', mapClickHandler);
                    mapClickHandler = null;
                    console.log('Event handler de clic removido');
                }
                map.remove();
                map = null;
                console.log('Mapa destruido');
            }
            document.getElementById('mapaModal').classList.remove('active');
        });

        document.getElementById('btnCancelarMapa').addEventListener('click', () => {
            console.log('Cancelando modal - limpiando mapa');
            if (map) {
                if (mapClickHandler) {
                    map.off('click', mapClickHandler);
                    mapClickHandler = null;
                    console.log('Event handler de clic removido');
                }
                map.remove();
                map = null;
                console.log('Mapa destruido');
            }
            document.getElementById('mapaModal').classList.remove('active');
        });

        let mapClickHandler = null;

        // Inicializar mapa
        function inicializarMapa() {
            console.log('=== Iniciando Mapa Leaflet ===');
            
            // Verificar que el div existe
            const mapDiv = document.getElementById('map');
            console.log('Div #map encontrado:', mapDiv);
            console.log('Tamaño del div:', mapDiv?.offsetWidth, 'x', mapDiv?.offsetHeight);
            
            if (!mapDiv) {
                console.error('ERROR: El div #map no existe en el DOM');
                return;
            }
            
            // Crear nuevo mapa
            map = L.map('map', { 
                zoomControl: true,
                attributionControl: true
            }).setView([17.5689, -97.3295], 13);
            
            console.log('Mapa creado:', map);
            console.log('Zoom:', map.getZoom(), 'Centro:', map.getCenter());
            
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors',
                maxZoom: 19
            }).addTo(map);
            console.log('Capa base (tiles) agregada');

            // Crear feature group para los puntos y líneas
            poligonoLayer = L.featureGroup().addTo(map);
            console.log('Feature group creado');

            // Crear handler para evento de clic
            mapClickHandler = function(e) {
                console.log('Clic en el mapa:', e.latlng);
                agregarPunto(e.latlng);
            };

            // Registrar evento de clic
            map.on('click', mapClickHandler);
            console.log('Handler de clic registrado');

            // Si estamos editando, cargar el GeoJSON existente
            const ubicacionGeojson = document.getElementById('ubicacion_geojson').value;
            console.log('GeoJSON existente:', ubicacionGeojson ? 'SÍ' : 'NO');
            
            if (ubicacionGeojson) {
                try {
                    const geojson = JSON.parse(ubicacionGeojson);
                    if (geojson && geojson.type === 'FeatureCollection' && geojson.features) {
                        const geometry = geojson.features[0]?.geometry;
                        if (geometry && geometry.type === 'Polygon') {
                            const coords = geometry.coordinates[0];
                            console.log('Coordenadas del GeoJSON:', coords.length);
                            poligonoPoints = coords.slice(0, -1).map(c => L.latLng(c[1], c[0]));
                            console.log('Puntos cargados:', poligonoPoints.length);
                            
                            // Dibujar el polígono existente
                            const polyCoords = poligonoPoints.map(p => [p.lat, p.lng]);
                            L.polygon(polyCoords, {
                                color: '#2563eb',
                                weight: 2,
                                opacity: 0.8,
                                fillColor: '#93c5fd',
                                fillOpacity: 0.3
                            }).addTo(poligonoLayer);

                            // Dibujar los marcadores de puntos
                            poligonoPoints.forEach((latlng, idx) => {
                                L.circleMarker(latlng, {
                                    radius: 6,
                                    fillColor: '#2563eb',
                                    color: '#1e40af',
                                    weight: 2,
                                    opacity: 1,
                                    fillOpacity: 0.8
                                }).addTo(poligonoLayer);
                            });

                            // Hacer zoom al polígono
                            const bounds = L.latLngBounds(poligonoPoints);
                            map.fitBounds(bounds, {padding: [50, 50]});
                            dibujando = true;
                            
                            // Actualizar tabla con los puntos cargados
                            actualizarTablaCoordinadas();
                            
                            console.log('GeoJSON cargado exitosamente. Puntos en array:', poligonoPoints.length);
                        }
                    }
                } catch(e) {
                    console.error('Error al parsear GeoJSON:', e);
                }
            }
            
            console.log('=== Mapa inicializado - Puntos totales:', poligonoPoints.length, '===');
        }

        // Agregar un punto al polígono
        function agregarPunto(latlng) {
            console.log('agregarPunto llamado. dibujando:', dibujando, 'puntos actuales:', poligonoPoints.length);
            
            if (!dibujando) {
                console.log('Primer punto del nuevo polígono, limpiando array...');
                poligonoPoints = [];
                dibujando = true;
            }

            console.log('Agregando punto:', latlng);
            poligonoPoints.push(latlng);
            console.log('Puntos después de agregar:', poligonoPoints.length);
            
            // Mostrar puntos visualmente
            L.circleMarker(latlng, {
                radius: 6,
                fillColor: '#2563eb',
                color: '#1e40af',
                weight: 2,
                opacity: 1,
                fillOpacity: 0.8
            }).addTo(poligonoLayer);

            // Dibujar línea en tiempo real
            if (poligonoPoints.length > 1) {
                const coords = poligonoPoints.map(p => [p.lat, p.lng]);
                
                // Eliminar línea anterior
                poligonoLayer.eachLayer(layer => {
                    if (layer instanceof L.Polyline && !(layer instanceof L.CircleMarker)) {
                        poligonoLayer.removeLayer(layer);
                    }
                });

                // Dibujar nueva línea
                L.polyline(coords, {
                    color: '#2563eb',
                    weight: 2,
                    opacity: 0.8,
                    dashArray: '5,5'
                }).addTo(poligonoLayer);
            }

            console.log('Punto agregado. Total en array:', poligonoPoints.length);
            
            // Actualizar tabla de coordenadas
            actualizarTablaCoordinadas();
        }

        // Cerrar el polígono
        document.getElementById('btnCerrarPoligono').addEventListener('click', function() {
            if (poligonoPoints.length < 3) {
                alert('Necesitas al menos 3 puntos para crear un polígono');
                return;
            }

            // Eliminar la línea punteada
            poligonoLayer.eachLayer(layer => {
                if (layer instanceof L.Polyline && !(layer instanceof L.CircleMarker)) {
                    poligonoLayer.removeLayer(layer);
                }
            });

            // Crear polígono final
            const coords = poligonoPoints.map(p => [p.lat, p.lng]);
            const poligono = L.polygon(coords, {
                color: '#2563eb',
                weight: 2,
                opacity: 0.8,
                fillColor: '#93c5fd',
                fillOpacity: 0.3
            }).addTo(poligonoLayer);

            // Guardar como GeoJSON
            const geojson = {
                type: 'FeatureCollection',
                features: [{
                    type: 'Feature',
                    geometry: {
                        type: 'Polygon',
                        coordinates: [[...coords.map(c => [c[1], c[0]]), coords[0] ? [coords[0][1], coords[0][0]] : null].filter(c => c)]
                    }
                }]
            };

            document.getElementById('ubicacion_geojson').value = JSON.stringify(geojson);
            document.getElementById('resumenUbicacion').style.display = 'block';
            dibujando = false;

            console.log('Polígono cerrado y guardado:', geojson);
        });

        // Guardar en el botón del footer
        document.getElementById('btnGuardarMapa').addEventListener('click', function() {
            if (poligonoPoints.length < 3) {
                alert('Necesitas al menos 3 puntos para crear un polígono');
                return;
            }

            // Crear polígono si no está cerrado aún
            if (!document.getElementById('ubicacion_geojson').value) {
                const coords = poligonoPoints.map(p => [p.lat, p.lng]);
                const geojson = {
                    type: 'FeatureCollection',
                    features: [{
                        type: 'Feature',
                        geometry: {
                            type: 'Polygon',
                            coordinates: [[...coords.map(c => [c[1], c[0]]), coords[0] ? [coords[0][1], coords[0][0]] : null].filter(c => c)]
                        }
                    }]
                };

                document.getElementById('ubicacion_geojson').value = JSON.stringify(geojson);
                console.log('Polígono guardado:', geojson);
            }

            document.getElementById('resumenUbicacion').style.display = 'block';
            document.getElementById('mapaModal').classList.remove('active');
            console.log('Modal cerrado');
        });

        // Deshacer último punto
        document.getElementById('btnDeshacerPunto').addEventListener('click', function() {
            if (poligonoPoints.length === 0) {
                alert('No hay puntos para deshacer');
                return;
            }

            poligonoPoints.pop();
            
            // Redibujar
            poligonoLayer.clearLayers();
            poligonoPoints.forEach(point => {
                L.circleMarker(point, {
                    radius: 6,
                    fillColor: '#2563eb',
                    color: '#1e40af',
                    weight: 2,
                    opacity: 1,
                    fillOpacity: 0.8
                }).addTo(poligonoLayer);
            });

            if (poligonoPoints.length > 1) {
                const coords = poligonoPoints.map(p => [p.lat, p.lng]);
                L.polyline(coords, {
                    color: '#2563eb',
                    weight: 2,
                    opacity: 0.8,
                    dashArray: '5,5'
                }).addTo(poligonoLayer);
            }

            console.log('Punto eliminado. Total:', poligonoPoints.length);
            
            // Actualizar tabla de coordenadas
            actualizarTablaCoordinadas();
        });

        // Limpiar
        document.getElementById('btnLimpiarMapa').addEventListener('click', function() {
            console.log('Limpiando mapa - puntos antes:', poligonoPoints.length);
            poligonoPoints = [];
            poligonoLayer.clearLayers();
            dibujando = false;
            document.getElementById('ubicacion_geojson').value = '';
            document.getElementById('resumenUbicacion').style.display = 'none';
            
            // Limpiar tabla de coordenadas
            actualizarTablaCoordinadas();
            
            console.log('Mapa limpiado - puntos después:', poligonoPoints.length);
        });

        document.getElementById('archivo_acta').addEventListener('change', function(e) {
            if(e.target.files.length > 0) {
                document.getElementById('file-name').textContent = "Seleccionado: " + e.target.files[0].name;
            }
        });

        // Validar formulario antes de enviar
        document.getElementById('frmActa').addEventListener('submit', function(e) {
            console.log('=== Validando formulario ===');
            
            const ubicacion = document.getElementById('ubicacion_geojson').value;
            console.log('Ubicación GeoJSON:', ubicacion ? 'SÍ' : 'NO');
            
            // Validar georreferenciación
            if (!ubicacion || ubicacion.trim() === '') {
                e.preventDefault();
                alert('Por favor, dibuja el polígono del terreno en el mapa');
                return false;
            }
            
            // Validar que tenga datos de texto requeridos
            const comunero = document.getElementById('id_comunero').value;
            const descripcion = document.querySelector('textarea[name="descripcion_predio"]').value;
            const colindancias = document.querySelector('textarea[name="colindancias"]').value;
            
            if (!comunero || !descripcion || !colindancias) {
                e.preventDefault();
                alert('Por favor, completa todos los campos obligatorios (Comunero, Descripción, Colindancias)');
                return false;
            }
            
            console.log('✓ Validación exitosa, enviando formulario...');
            console.log('Datos:', {comunero, descripcion, colindancias, ubicacion: ubicacion.substring(0, 50)});
        });

        // Manejar eliminación de documentos
        document.querySelectorAll('.btn-delete-doc').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const id_archivo = this.dataset.id;
                const nombre = this.dataset.name;

                if (confirm(`¿Estás seguro de que deseas eliminar el archivo "${nombre}"?`)) {
                    const formData = new FormData();
                    formData.append('accion', 'eliminar_archivo');
                    formData.append('id_archivo', id_archivo);
                    formData.append('id_acta', '<?= $id_acta;?>');

                    fetch('/proyectocomunitariov3/src/ActasDePosesion/Application/acciones.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.text())
                    .then(data => {
                        if (data.includes('success') || data.includes('eliminado')) {
                            // Recargar la página o la sección de documentos
                            location.reload();
                        } else {
                            alert('Error al eliminar el archivo.');
                            console.error('Response:', data);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error al eliminar el archivo.');
                    });
                }
            });
        });
    </script>
</body>
</html>
