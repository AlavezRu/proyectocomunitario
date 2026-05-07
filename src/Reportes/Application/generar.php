<?php
require_once __DIR__ . '/../../Shared/Infrastructure/Database/Connection.php';

$tipo_reporte = isset($_GET['tipo']) ? $_GET['tipo'] : '';
$titulo = "Reporte General";

$thead = "";
$tbody = "";

switch ($tipo_reporte) {
    case 'padron':
        $titulo = "Listado General de Comuneros Activos";
        $q = pg_query($conexion, "
            SELECT c.numero_progresivo, c.nombre_completo, s.descripcion as situacion, l.nombre as localidad, c.numero_certificado
            FROM comunero c
            JOIN situacion s ON c.id_situacion = s.id_situacion
            JOIN localidad l ON c.id_localidad = l.id_localidad
            WHERE c.activo = TRUE
            ORDER BY c.numero_progresivo ASC
        ");
        
        $thead = "<tr><th>N° Prog</th><th>Nombre Completo</th><th>Situación</th><th>Localidad</th><th>Certificado</th></tr>";
        while($row = pg_fetch_assoc($q)) {
            $tbody .= "<tr>
                <td>".str_pad($row['numero_progresivo'],4,'0',STR_PAD_LEFT)."</td>
                <td>".htmlspecialchars($row['nombre_completo'])."</td>
                <td>".htmlspecialchars($row['situacion'])."</td>
                <td>".htmlspecialchars($row['localidad'])."</td>
                <td>".htmlspecialchars($row['numero_certificado'])."</td>
            </tr>";
        }
        break;

    case 'adeudo_predial':
        $anio = date('Y');
        $titulo = "Comuneros con Adeudo Predial ($anio)";
        $q = pg_query($conexion, "
            SELECT c.numero_progresivo, c.nombre_completo, l.nombre as localidad
            FROM comunero c
            JOIN localidad l ON c.id_localidad = l.id_localidad
            LEFT JOIN pago_predial p ON c.id_comunero = p.id_comunero AND p.anio = $anio
            WHERE c.activo = TRUE AND (p.pagado IS NULL OR p.pagado = FALSE)
            ORDER BY c.numero_progresivo ASC
        ");

        $thead = "<tr><th>N° Prog</th><th>Nombre Completo</th><th>Localidad</th><th>Estado Predial</th></tr>";
        while($row = pg_fetch_assoc($q)) {
            $tbody .= "<tr>
                <td>".str_pad($row['numero_progresivo'],4,'0',STR_PAD_LEFT)."</td>
                <td>".htmlspecialchars($row['nombre_completo'])."</td>
                <td>".htmlspecialchars($row['localidad'])."</td>
                <td style='color: red;'>Adeudo</td>
            </tr>";
        }
        break;

    case 'sin_sucesores':
        $titulo = "Comuneros Activos Sin Lista de Sucesores";
        $q = pg_query($conexion, "
            SELECT c.numero_progresivo, c.nombre_completo, c.numero_certificado
            FROM comunero c
            LEFT JOIN sucesor s ON c.id_comunero = s.id_comunero
            WHERE c.activo = TRUE AND s.id_sucesor IS NULL
            ORDER BY c.numero_progresivo ASC
        ");

        $thead = "<tr><th>N° Prog</th><th>Nombre Completo</th><th>Certificado</th><th>Sucesores</th></tr>";
        while($row = pg_fetch_assoc($q)) {
            $tbody .= "<tr>
                <td>".str_pad($row['numero_progresivo'],4,'0',STR_PAD_LEFT)."</td>
                <td>".htmlspecialchars($row['nombre_completo'])."</td>
                <td>".htmlspecialchars($row['numero_certificado'])."</td>
                <td style='color: #f59e0b;'>Sin Registrar</td>
            </tr>";
        }
        break;

    case 'por_localidad':
        $titulo = "Resumen de Comuneros por Localidad";
        $q = pg_query($conexion, "
            SELECT l.nombre as localidad, COUNT(c.id_comunero) as total
            FROM localidad l
            LEFT JOIN comunero c ON l.id_localidad = c.id_localidad AND c.activo = TRUE
            GROUP BY l.nombre
            ORDER BY l.nombre ASC
        ");

        $thead = "<tr><th>Localidad</th><th>Total de Comuneros / Avecindados</th></tr>";
        $total = 0;
        while($row = pg_fetch_assoc($q)) {
            $total += $row['total'];
            $tbody .= "<tr>
                <td>".htmlspecialchars($row['localidad'])."</td>
                <td>".$row['total']."</td>
            </tr>";
        }
        $tbody .= "<tr style='font-weight: bold; background: #f8fafc;'><td>TOTAL GENERAL</td><td>$total</td></tr>";
        break;

    case 'actas':
        $titulo = "Registro de Actas de Posesión";
        $q = pg_query($conexion, "
            SELECT a.id_acta, a.fecha_acta, c.nombre_completo, c.numero_progresivo, a.descripcion_predio
            FROM acta_posesion a
            JOIN comunero c ON a.id_comunero = c.id_comunero
            ORDER BY a.fecha_acta DESC
        ");

        $thead = "<tr><th>Folio</th><th>Fecha</th><th>N° Prog</th><th>Comunero</th><th>Descripción Predio</th></tr>";
        while($row = pg_fetch_assoc($q)) {
            $tbody .= "<tr>
                <td>".htmlspecialchars($row['id_acta'])."</td>
                <td>".date('d/m/Y', strtotime($row['fecha_acta']))."</td>
                <td>".str_pad($row['numero_progresivo'],4,'0',STR_PAD_LEFT)."</td>
                <td>".htmlspecialchars($row['nombre_completo'])."</td>
                <td>".htmlspecialchars($row['descripcion_predio'])."</td>
            </tr>";
        }
        break;

    case 'asambleas':
        $titulo = "Historial de Asambleas Comunales";
        $q = pg_query($conexion, "
            SELECT a.id_asamblea, a.fecha, a.descripcion, 
                   COUNT(CASE WHEN aa.asistio = TRUE THEN 1 END) as asistentes,
                   COUNT(aa.id_asamblea) as total_convocados
            FROM asamblea a
            LEFT JOIN asistencia_asamblea aa ON a.id_asamblea = aa.id_asamblea
            GROUP BY a.id_asamblea, a.fecha, a.descripcion
            ORDER BY a.fecha DESC
        ");

        $thead = "<tr><th>Fecha</th><th>Descripción</th><th>Asistentes</th><th>Convocados</th><th>Tasa Asistencia</th></tr>";
        while($row = pg_fetch_assoc($q)) {
            $tasa = $row['total_convocados'] > 0 ? round(($row['asistentes']/$row['total_convocados'])*100, 1) : 0;
            $tbody .= "<tr>
                <td>".date('d/m/Y', strtotime($row['fecha']))."</td>
                <td>".htmlspecialchars($row['descripcion'])."</td>
                <td>".$row['asistentes']."</td>
                <td>".$row['total_convocados']."</td>
                <td>".$tasa."%</td>
            </tr>";
        }
        break;

    case 'tequios':
        $titulo = "Registro de Participantes en Tequios";
        $q = pg_query($conexion, "
            SELECT t.id_tequio, t.fecha, t.descripcion,
                   COUNT(CASE WHEN c.cumplio = TRUE THEN 1 END) as participantes
            FROM tequio t
            LEFT JOIN cumplimiento_tequio c ON t.id_tequio = c.id_tequio
            GROUP BY t.id_tequio, t.fecha, t.descripcion
            ORDER BY t.fecha DESC
        ");

        $thead = "<tr><th>Fecha</th><th>Descripción</th><th>Participantes</th></tr>";
        while($row = pg_fetch_assoc($q)) {
            $tbody .= "<tr>
                <td>".date('d/m/Y', strtotime($row['fecha']))."</td>
                <td>".htmlspecialchars($row['descripcion'])."</td>
                <td>".$row['participantes']."</td>
            </tr>";
        }
        break;

    case 'pagos':
        $anio = isset($_GET['anio']) ? (int)$_GET['anio'] : date('Y');
        $titulo = "Reporte de Pagos Prediales - Año $anio";
        $q = pg_query($conexion, "
            SELECT c.numero_progresivo, c.nombre_completo, l.nombre as localidad,
                   COUNT(p.id_pago) as total_pagos, 
                   SUM(CASE WHEN p.pagado = TRUE THEN p.monto ELSE 0 END) as monto_pagado
            FROM comunero c
            JOIN localidad l ON c.id_localidad = l.id_localidad
            LEFT JOIN pago_predial p ON c.id_comunero = p.id_comunero AND p.anio = $anio
            WHERE c.activo = TRUE
            GROUP BY c.numero_progresivo, c.nombre_completo, l.nombre
            ORDER BY c.numero_progresivo ASC
        ");

        $thead = "<tr><th>N° Prog</th><th>Nombre</th><th>Localidad</th><th>Pagos Realizados</th><th>Monto Total</th></tr>";
        $total_monto = 0;
        while($row = pg_fetch_assoc($q)) {
            $monto = $row['monto_pagado'] ? (float)$row['monto_pagado'] : 0;
            $total_monto += $monto;
            $tbody .= "<tr>
                <td>".str_pad($row['numero_progresivo'],4,'0',STR_PAD_LEFT)."</td>
                <td>".htmlspecialchars($row['nombre_completo'])."</td>
                <td>".htmlspecialchars($row['localidad'])."</td>
                <td>".$row['total_pagos']."</td>
                <td>\$".number_format($monto, 2)."</td>
            </tr>";
        }
        $tbody .= "<tr style='font-weight: bold; background: #f8fafc;'><td colspan='4'>TOTAL RECAUDADO</td><td>\$".number_format($total_monto, 2)."</td></tr>";
        break;

    case 'situacion_comuneros':
        $titulo = "Estatus de Comuneros por Situación";
        $q = pg_query($conexion, "
            SELECT s.descripcion as situacion, COUNT(c.id_comunero) as total
            FROM situacion s
            LEFT JOIN comunero c ON s.id_situacion = c.id_situacion AND c.activo = TRUE
            GROUP BY s.descripcion
            ORDER BY total DESC
        ");

        $thead = "<tr><th>Situación</th><th>Total</th></tr>";
        $total = 0;
        while($row = pg_fetch_assoc($q)) {
            $total += $row['total'];
            $tbody .= "<tr>
                <td>".htmlspecialchars($row['situacion'])."</td>
                <td>".$row['total']."</td>
            </tr>";
        }
        $tbody .= "<tr style='font-weight: bold; background: #f8fafc;'><td>TOTAL GENERAL</td><td>$total</td></tr>";
        break;

    case 'comuneros_filtro':
        $titulo = "Reporte de Comuneros (Filtro Personalizado)";
        $where = "WHERE c.activo = TRUE";
        
        if (!empty($_GET['localidad'])) {
            $localidad = (int)$_GET['localidad'];
            $where .= " AND c.id_localidad = $localidad";
        }
        if (!empty($_GET['situacion'])) {
            $situacion = (int)$_GET['situacion'];
            $where .= " AND c.id_situacion = $situacion";
        }

        $q = pg_query($conexion, "
            SELECT c.numero_progresivo, c.nombre_completo, s.descripcion as situacion, l.nombre as localidad, c.numero_certificado
            FROM comunero c
            JOIN situacion s ON c.id_situacion = s.id_situacion
            JOIN localidad l ON c.id_localidad = l.id_localidad
            $where
            ORDER BY c.numero_progresivo ASC
        ");
        
        $thead = "<tr><th>N° Prog</th><th>Nombre Completo</th><th>Situación</th><th>Localidad</th><th>Certificado</th></tr>";
        while($row = pg_fetch_assoc($q)) {
            $tbody .= "<tr>
                <td>".str_pad($row['numero_progresivo'],4,'0',STR_PAD_LEFT)."</td>
                <td>".htmlspecialchars($row['nombre_completo'])."</td>
                <td>".htmlspecialchars($row['situacion'])."</td>
                <td>".htmlspecialchars($row['localidad'])."</td>
                <td>".htmlspecialchars($row['numero_certificado'])."</td>
            </tr>";
        }
        break;

    case 'pagos_filtro':
        $anio = isset($_GET['anio']) ? (int)$_GET['anio'] : date('Y');
        $titulo = "Reporte de Pagos Prediales Filtrado - Año $anio";
        $where = "WHERE c.activo = TRUE";
        
        if (!empty($_GET['localidad'])) {
            $localidad = (int)$_GET['localidad'];
            $where .= " AND l.id_localidad = $localidad";
        }

        $q = pg_query($conexion, "
            SELECT c.numero_progresivo, c.nombre_completo, l.nombre as localidad,
                   COUNT(p.id_pago) as total_pagos, 
                   SUM(CASE WHEN p.pagado = TRUE THEN p.monto ELSE 0 END) as monto_pagado
            FROM comunero c
            JOIN localidad l ON c.id_localidad = l.id_localidad
            LEFT JOIN pago_predial p ON c.id_comunero = p.id_comunero AND p.anio = $anio
            $where
            GROUP BY c.numero_progresivo, c.nombre_completo, l.nombre
            ORDER BY c.numero_progresivo ASC
        ");

        $thead = "<tr><th>N° Prog</th><th>Nombre</th><th>Localidad</th><th>Pagos Realizados</th><th>Monto Total</th></tr>";
        $total_monto = 0;
        while($row = pg_fetch_assoc($q)) {
            $monto = $row['monto_pagado'] ? (float)$row['monto_pagado'] : 0;
            $total_monto += $monto;
            $tbody .= "<tr>
                <td>".str_pad($row['numero_progresivo'],4,'0',STR_PAD_LEFT)."</td>
                <td>".htmlspecialchars($row['nombre_completo'])."</td>
                <td>".htmlspecialchars($row['localidad'])."</td>
                <td>".$row['total_pagos']."</td>
                <td>\$".number_format($monto, 2)."</td>
            </tr>";
        }
        $tbody .= "<tr style='font-weight: bold; background: #f8fafc;'><td colspan='4'>TOTAL RECAUDADO</td><td>\$".number_format($total_monto, 2)."</td></tr>";
        break;

    case 'asambleas_filtro':
        $anio = isset($_GET['anio']) ? (int)$_GET['anio'] : date('Y');
        $titulo = "Reporte de Asambleas Filtrado - Año $anio";
        
        $q = pg_query($conexion, "
            SELECT a.id_asamblea, a.fecha, a.descripcion, 
                   COUNT(CASE WHEN aa.asistio = TRUE THEN 1 END) as asistentes,
                   COUNT(aa.id_asamblea) as total_convocados
            FROM asamblea a
            LEFT JOIN asistencia_asamblea aa ON a.id_asamblea = aa.id_asamblea
            WHERE EXTRACT(YEAR FROM a.fecha) = $anio
            GROUP BY a.id_asamblea, a.fecha, a.descripcion
            ORDER BY a.fecha DESC
        ");

        $thead = "<tr><th>Fecha</th><th>Descripción</th><th>Asistentes</th><th>Convocados</th><th>Tasa Asistencia</th></tr>";
        while($row = pg_fetch_assoc($q)) {
            $tasa = $row['total_convocados'] > 0 ? round(($row['asistentes']/$row['total_convocados'])*100, 1) : 0;
            $tbody .= "<tr>
                <td>".date('d/m/Y', strtotime($row['fecha']))."</td>
                <td>".htmlspecialchars($row['descripcion'])."</td>
                <td>".$row['asistentes']."</td>
                <td>".$row['total_convocados']."</td>
                <td>".$tasa."%</td>
            </tr>";
        }
        break;

    case 'tequios_filtro':
        $anio = isset($_GET['anio']) ? (int)$_GET['anio'] : date('Y');
        $titulo = "Reporte de Tequios Filtrado - Año $anio";
        
        $q = pg_query($conexion, "
            SELECT t.id_tequio, t.fecha, t.descripcion,
                   COUNT(CASE WHEN c.cumplio = TRUE THEN 1 END) as participantes
            FROM tequio t
            LEFT JOIN cumplimiento_tequio c ON t.id_tequio = c.id_tequio
            WHERE EXTRACT(YEAR FROM t.fecha) = $anio
            GROUP BY t.id_tequio, t.fecha, t.descripcion
            ORDER BY t.fecha DESC
        ");

        $thead = "<tr><th>Fecha</th><th>Descripción</th><th>Participantes</th></tr>";
        while($row = pg_fetch_assoc($q)) {
            $tbody .= "<tr>
                <td>".date('d/m/Y', strtotime($row['fecha']))."</td>
                <td>".htmlspecialchars($row['descripcion'])."</td>
                <td>".$row['participantes']."</td>
            </tr>";
        }
        break;

    default:
        die("Tipo de reporte no válido");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= $titulo ?></title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; padding: 20px; color: #333; }
        .header { display: flex; align-items: center; gap: 16px; border-bottom: 2px solid #2563eb; padding-bottom: 15px; margin-bottom: 20px; }
        .header-logo { width: 70px; height: 70px; object-fit: contain; flex-shrink: 0; }
        .header-text { flex: 1; text-align: center; }
        h1 { margin: 0 0 5px 0; font-size: 22px; color: #1e293b; }
        p { margin: 0; color: #64748b; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 12px; }
        th, td { border: 1px solid #cbd5e1; padding: 8px 10px; text-align: left; }
        th { background-color: #f1f5f9; color: #1e293b; font-weight: bold; }
        .footer { margin-top: 40px; font-size: 10px; text-align: center; color: #94a3b8; border-top: 1px solid #cbd5e1; padding-top: 10px; }
        @media print {
            .no-print { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body>

    <div class="no-print" style="margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #2563eb; color: white; border: none; border-radius: 5px; cursor: pointer;">
            Imprimir Reporte
        </button>
        <button onclick="window.close()" style="padding: 10px 20px; background: #fff; color: #333; border: 1px solid #ccc; border-radius: 5px; cursor: pointer; margin-left: 10px;">
            Cerrar
        </button>
    </div>

    <div class="header">
        <img
            src="/proyectocomunitario/shared/img/logo.png"
            alt="Escudo Municipal de San Bartolo Soyaltepec"
            class="header-logo"
        >
        <div class="header-text">
            <h1>SISTEMA COMUNAL - SAN BARTOLO SOYALTEPEC</h1>
            <p><?= $titulo ?> | Generado el: <?= date('d/m/Y H:i') ?></p>
        </div>
        <img
            src="/proyectocomunitario/shared/img/logo.png"
            alt="Escudo Municipal de San Bartolo Soyaltepec"
            class="header-logo"
        >
    </div>

    <table>
        <thead>
            <?= $thead ?>
        </thead>
        <tbody>
            <?= $tbody ?>
        </tbody>
    </table>

    <div class="footer">
        Firma del Comisariado de Bienes Comunales<br><br><br>
        __________________________________________
    </div>

</body>
</html>
