<?php
require_once '../../Shared/Infrastructure/Database/Connection.php';

/** @var \PgSql\Connection $conexion */

$pageTitle = "Ver Acta de Posesión";
$activePage = "actas";

// Obtener el ID del acta
$id_acta = isset($_GET['id']) ? (int)$_GET['id'] : null;

if (!$id_acta) {
    header("Location: /proyectocomunitario/public/index.php?page=actas&msg=error");
    exit;
}

// Obtener datos del acta
$query = "
    SELECT a.*, c.nombre_completo, c.numero_progresivo, 
           ST_AsGeoJSON(a.ubicacion) as geojson
    FROM acta_posesion a
    JOIN comunero c ON a.id_comunero = c.id_comunero
    WHERE a.id_acta = $1
";
$resultado = pg_query_params($conexion, $query, [$id_acta]);

if (!$resultado || pg_num_rows($resultado) == 0) {
    header("Location: /proyectocomunitario/public/index.php?page=actas&msg=error");
    exit;
}

$acta = pg_fetch_assoc($resultado);

// Obtener documentos
$q_docs = pg_query_params($conexion, "SELECT id_archivo, nombre_archivo, ruta_archivo, tipo_archivo FROM archivo WHERE id_acta = $1 ORDER BY id_archivo DESC", [$id_acta]);
$documentos = [];
while ($doc = pg_fetch_assoc($q_docs)) {
    $documentos[] = $doc;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SysComunal | Ver Acta</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="<?= $GLOBALS['ASSETS_URL'] ?>/Css/style.css">
    <style>
        .info-badge {
            display: inline-block;
            background: rgba(37, 99, 235, 0.1);
            color: var(--primary);
            padding: 0.5rem 1rem;
            border-radius: var(--radius-lg);
            font-weight: 500;
            font-size: 0.875rem;
            margin: 0.25rem 0;
        }
        .info-group {
            margin-bottom: 2rem;
        }
        .info-label {
            display: block;
            font-weight: 600;
            color: var(--text-main);
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
        }
        .info-value {
            color: var(--text-main);
            font-size: 1rem;
            line-height: 1.5;
            padding: 0.75rem 1rem;
            background: #f8fafc;
            border-radius: var(--radius-md);
            border: 1px solid var(--border);
        }
        .info-value-large {
            padding: 1rem;
            white-space: pre-wrap;
            word-break: break-word;
        }
        #map {
            height: 500px;
            width: 100%;
            border-radius: var(--radius-md);
            border: 1px solid var(--border);
            margin-top: 0.75rem;
        }
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }
        .grid-3 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
        }
        .document-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem;
            background: #f8fafc;
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            transition: var(--transition-fast);
        }
        .document-item:hover {
            background: #f1f5f9;
        }
        .document-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex: 1;
        }
        .document-icon {
            font-size: 1.5rem;
            flex-shrink: 0;
        }
        .document-details {
            min-width: 0;
        }
        .document-name {
            font-weight: 500;
            color: var(--text-main);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .document-type {
            font-size: 0.75rem;
            color: var(--text-muted);
            text-transform: uppercase;
        }
        .btn-view-document {
            padding: 0.5rem 1rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: var(--radius-md);
            cursor: pointer;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            transition: var(--transition-fast);
        }
        .btn-view-document:hover {
            background: #1e40af;
            opacity: 0.9;
        }
        .empty-state {
            text-align: center;
            padding: 2rem;
            color: var(--text-muted);
        }
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }
        @media (max-width: 768px) {
            .grid-2 { grid-template-columns: 1fr; }
            .grid-3 { grid-template-columns: 1fr; }
            .action-buttons { flex-direction: column; }
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
                    <h1 style="font-size: 1.75rem; color: var(--text-main); margin-bottom: 0.5rem;">Información del Acta de Posesión</h1>
                    <p style="color: var(--text-muted);">Visualización de datos del registro</p>
                </div>
                <div class="action-buttons">
                    <a href="/proyectocomunitario/public/index.php?page=actas_formulario&id=<?= $acta['id_acta'] ?>" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-edit"></i> Editar Acta
                    </a>
                    <a href="/proyectocomunitario/public/index.php?page=actas" class="btn" style="background: white; border: 1px solid var(--border); display: inline-flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                </div>
            </div>

            <!-- Información General -->
            <div class="glass-panel" style="padding: 2rem; margin-bottom: 2rem;">
                <h2 style="margin-bottom: 1.5rem; color: var(--text-main); font-size: 1.25rem;">Información General</h2>
                
                <div class="grid-3">
                    <div class="info-group">
                        <span class="info-label">Folio / Comunero</span>
                        <div class="info-value">
                            <?= str_pad($acta['numero_progresivo'], 4, '0', STR_PAD_LEFT) ?>
                        </div>
                    </div>

                    <div class="info-group">
                        <span class="info-label">Nombre del Comunero</span>
                        <div class="info-value">
                            <?= htmlspecialchars($acta['nombre_completo']) ?>
                        </div>
                    </div>

                    <div class="info-group">
                        <span class="info-label">Fecha del Acta</span>
                        <div class="info-value">
                            <?= date('d/m/Y', strtotime($acta['fecha_acta'])) ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Descripción del Predio -->
            <div class="glass-panel" style="padding: 2rem; margin-bottom: 2rem;">
                <h2 style="margin-bottom: 1.5rem; color: var(--text-main); font-size: 1.25rem;">Descripción del Predio</h2>
                
                <div class="info-group">
                    <span class="info-label">Descripción</span>
                    <div class="info-value info-value-large">
                        <?= htmlspecialchars($acta['descripcion_predio']) ?>
                    </div>
                </div>

                <div class="info-group">
                    <span class="info-label">Colindancias</span>
                    <div class="info-value info-value-large">
                        <?= htmlspecialchars($acta['colindancias']) ?>
                    </div>
                </div>
            </div>

            <!-- Georreferenciación -->
            <div class="glass-panel" style="padding: 2rem; margin-bottom: 2rem;">
                <h2 style="margin-bottom: 1.5rem; color: var(--text-main); font-size: 1.25rem;">Georreferenciación del Terreno</h2>
                
                <?php if (!empty($acta['geojson'])): ?>
                    <div id="map"></div>
                    <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 0.75rem;">
                        <i class="fas fa-info-circle"></i> Polígono del terreno dibujado en el mapa
                    </p>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-map" style="font-size: 3rem; opacity: 0.5; margin-bottom: 1rem;"></i>
                        <p>No hay georreferenciación registrada para este acta.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Documentos -->
            <div class="glass-panel" style="padding: 2rem; margin-bottom: 2rem;">
                <h2 style="margin-bottom: 1.5rem; color: var(--text-main); font-size: 1.25rem;">
                    <i class="fas fa-file-check" style="color: #16a34a; margin-right: 0.5rem;"></i>
                    Documentos Digitalizados
                </h2>
                
                <?php if (count($documentos) > 0): ?>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <?php foreach ($documentos as $doc):
                            $is_pdf = strtolower($doc['tipo_archivo']) === 'pdf';
                            $icon = $is_pdf ? 'fa-file-pdf' : 'fa-image';
                            $color = $is_pdf ? '#dc2626' : '#3b82f6';
                        ?>
                            <div class="document-item">
                                <div class="document-info">
                                    <div class="document-icon" style="color: <?= $color ?>;">
                                        <i class="fas <?= $icon ?>"></i>
                                    </div>
                                    <div class="document-details">
                                        <div class="document-name" title="<?= htmlspecialchars($doc['nombre_archivo']) ?>">
                                            <?= htmlspecialchars($doc['nombre_archivo']) ?>
                                        </div>
                                        <div class="document-type">
                                            <?= strtoupper($doc['tipo_archivo']) ?> - 
                                            <?= $is_pdf ? 'Documento PDF' : 'Imagen' ?>
                                        </div>
                                    </div>
                                </div>
                                <a href="/proyectocomunitario/<?= htmlspecialchars($doc['ruta_archivo']) ?>" target="_blank" class="btn-view-document">
                                    <i class="fas fa-download"></i> Ver
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-file" style="font-size: 3rem; opacity: 0.5; margin-bottom: 1rem;"></i>
                        <p>No hay documentos digitalizados para este acta.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php if (!empty($acta['geojson'])): ?>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // Inicializar mapa
        const map = L.map('map').setView([17.0627, -96.7236], 10);
        
        // Agregar capa de base
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        // Cargar GeoJSON del acta
        const geojson = <?= $acta['geojson'] ?>;
        
        if (geojson) {
            const geoJsonLayer = L.geoJSON(geojson, {
                style: {
                    color: '#2563eb',
                    weight: 2,
                    opacity: 0.8,
                    fillOpacity: 0.3
                },
                pointToLayer: function(feature, latlng) {
                    return L.circleMarker(latlng, {
                        radius: 5,
                        fillColor: '#2563eb',
                        color: '#fff',
                        weight: 2,
                        opacity: 1,
                        fillOpacity: 0.8
                    });
                }
            }).addTo(map);

            // Ajustar zoom al polígono
            const bounds = geoJsonLayer.getBounds();
            if (bounds.isValid()) {
                map.fitBounds(bounds, { padding: [50, 50] });
            }
        }
    </script>
    <?php endif; ?>

</body>
</html>
