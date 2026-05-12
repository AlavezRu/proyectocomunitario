<?php
require_once '../../Shared/Infrastructure/Database/Connection.php';
/** @var \PgSql\Connection $conexion */

$pageTitle = "Mapa de Predios";
$activePage = "mapa";

// Obtener Puntos y Polígonos desde PostGIS
$query = "
    SELECT a.id_acta, a.descripcion_predio, a.fecha_acta, c.nombre_completo, c.numero_progresivo, c.color_mapa,
           ST_AsGeoJSON(a.ubicacion) AS geojson
    FROM acta_posesion a
    JOIN comunero c ON a.id_comunero = c.id_comunero
    WHERE a.ubicacion IS NOT NULL
    ORDER BY a.fecha_acta DESC
";

$resultado = pg_query($conexion, $query);
if (!$resultado) {
    die("Error en query: " . pg_last_error($conexion));
}

$marcadores = [];
while ($row = pg_fetch_assoc($resultado)) {
    $marcadores[] = $row;
    error_log("DEBUG: Ubicación guardada - " . substr($row['geojson'] ?? 'NULL', 0, 100));
}

// Obtener lista de localidades
$q_localidades = pg_query($conexion, "SELECT id_localidad, nombre FROM localidad ORDER BY nombre");
$localidades_mapa = [];
if ($q_localidades) {
    while ($row = pg_fetch_assoc($q_localidades)) {
        $localidades_mapa[] = $row;
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SysComunal | Mapas</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= $GLOBALS['ASSETS_URL'] ?>/Css/style.css">
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        #map { 
            height: 75vh; 
            width: 100%; 
            border-radius: var(--radius-lg); 
            box-shadow: var(--shadow-md); 
            z-index: 10; 
            border: 1px solid var(--border);
        }
        .info-popup h4 { 
            margin-top: 0; 
            color: var(--primary); 
            font-size: 1rem; 
            margin-bottom: 0.5rem; 
        }
        .info-popup p { 
            margin: 0.25rem 0; 
            font-size: 0.85rem; 
            color: var(--text-muted); 
        }
        .localidades-control {
            background: var(--surface, #fff);
            border-radius: var(--radius-md, 8px);
            padding: 0.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            display: flex;
            flex-direction: column;
            gap: 0.3rem;
            min-width: 155px;
        }
        .localidades-control-title {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted, #64748b);
            padding: 0 0.25rem 0.25rem;
            border-bottom: 1px solid var(--border, #e2e8f0);
            margin-bottom: 0.15rem;
        }
        .localidad-btn {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            width: 100%;
            padding: 0.4rem 0.6rem;
            font-size: 0.82rem;
            border: 1px solid var(--border, #e2e8f0);
            border-radius: 6px;
            background: var(--surface, #fff);
            color: var(--text, #1e293b);
            cursor: pointer;
            text-align: left;
            transition: background 0.15s, color 0.15s, border-color 0.15s;
            white-space: nowrap;
            font-family: inherit;
        }
        .localidad-btn:hover:not(:disabled) {
            background: #eff6ff;
            color: #2563eb;
            border-color: #93c5fd;
        }
        .localidad-btn:disabled {
            opacity: 0.45;
            cursor: not-allowed;
        }
        .localidad-btn i {
            font-size: 0.75rem;
            color: #3b82f6;
            flex-shrink: 0;
        }
        .localidad-btn:disabled i {
            color: var(--text-muted, #94a3b8);
        }
        .leaflet-control-layers {
            border: 1px solid var(--border, #e2e8f0);
            border-radius: var(--radius-md, 8px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            overflow: hidden;
        }
        .leaflet-control-layers-expanded {
            background: var(--surface, #fff);
            color: var(--text, #1e293b);
            font-size: 0.82rem;
            line-height: 1.3;
            min-width: 180px;
        }
    </style>
</head>
<body>

    <?php include '../../Shared/Ui/Layout/sidebar.php'; ?>

    <main class="main-wrapper">
        <?php include '../../Shared/Ui/Layout/header.php'; ?>

        <div class="content-container animate-fade-in">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <div>
                    <h1>Mapa de Predios</h1>
                    <p class="text-muted">Visualización geográfica de todos los predios registrados</p>
                </div>
            </div>

            <div id="map"></div>
        </div>
    </main>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <script>
        // Datos de marcadores desde PHP (ahora incluyen color_mapa)
        const marcadores = <?php echo json_encode($marcadores, JSON_UNESCAPED_UNICODE); ?>;
        const localidades = <?php echo json_encode($localidades_mapa, JSON_UNESCAPED_UNICODE); ?>;
        
        let geoJsonLayer = null;

        // Inicializar mapa
        const map = L.map('map').setView([17.5689, -97.3295], 13);

        const capaCalles = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19
        });

        const capaTopografica = L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
            attribution: 'Map data: © OpenStreetMap contributors, SRTM | Map style: © OpenTopoMap (CC-BY-SA)',
            maxZoom: 17
        });

        const capaSatelital = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
            attribution: 'Tiles © Esri',
            maxZoom: 19
        });

        const capaEtiquetas = L.tileLayer('https://services.arcgisonline.com/ArcGIS/rest/services/Reference/World_Boundaries_and_Places/MapServer/tile/{z}/{y}/{x}', {
            attribution: 'Labels © Esri',
            maxZoom: 19
        });

        const capaHibrida = L.layerGroup([capaSatelital, capaEtiquetas]);

        capaCalles.addTo(map);

        L.control.layers({
            'Calles (OSM)': capaCalles,
            'Topografica': capaTopografica,
            'Satelital': capaSatelital,
            'Hibrida (Satelital + etiquetas)': capaHibrida
        }, null, {
            position: 'topleft',
            collapsed: false
        }).addTo(map);

        // Oscurecer color para el borde
        function oscurecerColor(color) {
            const num = parseInt(color.slice(1), 16);
            const amt = -30;
            const R = Math.max(0, (num >> 16) + amt);
            const G = Math.max(0, (num >> 8 & 0x00FF) + amt);
            const B = Math.max(0, (num & 0x0000FF) + amt);
            return '#' + (0x1000000 + (R < 255 ? R : 255) * 0x10000 + (G < 255 ? G : 255) * 0x100 + (B < 255 ? B : 255)).toString(16).slice(1);
        }

        // Crear GeoJSON layer
        function crearGeoJsonLayer() {
            geoJsonLayer = L.geoJSON(null, {
                style: function(feature) {
                    const colorUsuario = feature.properties.color_mapa || '#3b82f6';
                    return {
                        color: oscurecerColor(colorUsuario),
                        weight: 2,
                        opacity: 0.8,
                        fillOpacity: 0.3,
                        fillColor: colorUsuario
                    };
                },
                pointToLayer: function(feature, latlng) {
                    const colorUsuario = feature.properties.color_mapa || '#3b82f6';
                    return L.circleMarker(latlng, {
                        radius: 6,
                        fillColor: colorUsuario,
                        color: oscurecerColor(colorUsuario),
                        weight: 2,
                        opacity: 1,
                        fillOpacity: 0.8
                    });
                },
                onEachFeature: function(feature, layer) {
                    const props = feature.properties;
                    const popup = `
                        <div class="info-popup">
                            <h4>${props.nombre}</h4>
                            <p><strong>Predio:</strong> ${props.predio || 'N/A'}</p>
                            <p><strong>Fecha:</strong> ${props.fecha || 'N/A'}</p>
                            <p><strong>Progresivo:</strong> ${props.progresivo || 'N/A'}</p>
                        </div>
                    `;
                    layer.bindPopup(popup);
                }
            }).addTo(map);
        }

        // Agregar datos al mapa
        function agregarDatosAlMapa() {
            marcadores.forEach((marcador, index) => {
                try {
                    const geojson = JSON.parse(marcador.geojson);
                    if (geojson && geojson.type) {
                        const feature = {
                            type: 'Feature',
                            geometry: geojson,
                            properties: {
                                nombre: marcador.nombre_completo,
                                predio: marcador.descripcion_predio,
                                fecha: marcador.fecha_acta,
                                progresivo: marcador.numero_progresivo,
                                color_mapa: marcador.color_mapa
                            }
                        };
                        geoJsonLayer.addData(feature);
                    }
                } catch(e) {
                    console.error('Error procesando marcador:', e);
                }
            });
        }

        // Coordenadas fijas por comunidad (nombre en minúsculas normalizado como clave)
        const coordenadasComunidades = {
            'gavillera':        [17.609956445951376, -97.27334527452484],
            'soyatepec':        [17.59245671490403,  -97.30955349838028],
            'la union reforma': [17.580575784419143, -97.28478471527424],
            'tejocotal':        [17.599406174103784, -97.28524531812714],
            'rio verde':        [17.570968820630586, -97.3046363469873]
        };

        function normalizarNombre(nombre) {
            return nombre.toLowerCase()
                .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                .trim();
        }

        // Control de comunidades (top-right)
        const LocalidadesControl = L.Control.extend({
            options: { position: 'topright' },
            onAdd: function(map) {
                const container = L.DomUtil.create('div', 'localidades-control');
                L.DomEvent.disableClickPropagation(container);
                L.DomEvent.disableScrollPropagation(container);

                const title = L.DomUtil.create('div', 'localidades-control-title', container);
                title.innerHTML = '<i class="fas fa-map-marked-alt" style="margin-right:0.3rem"></i> Comunidades';

                localidades.forEach(function(loc) {
                    const btn = L.DomUtil.create('button', 'localidad-btn', container);
                    btn.innerHTML = '<i class="fas fa-location-dot"></i> ' + loc.nombre;

                    const coords = coordenadasComunidades[normalizarNombre(loc.nombre)];
                    if (coords) {
                        L.DomEvent.on(btn, 'click', function() {
                            map.flyTo(coords, 15, { duration: 1 });
                        });
                    } else {
                        btn.disabled = true;
                        btn.title = 'Sin coordenadas definidas';
                    }
                });

                return container;
            }
        });
        new LocalidadesControl().addTo(map);

        function inicializarMapa() {
            crearGeoJsonLayer();
            agregarDatosAlMapa();
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', inicializarMapa);
        } else {
            inicializarMapa();
        }
    </script>
</body>
</html>
