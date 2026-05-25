<?php
require_once __DIR__ . '/../../Shared/Infrastructure/Database/Connection.php';

$fecha = date('Y-m-d');
$nombreArchivo = "SysComunal_Reporte_Completo_$fecha.xls";

header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
header('Cache-Control: max-age=0');

// ── helpers ──────────────────────────────────────────────────────────────────
function xval(string $type, $value): string {
    $v = htmlspecialchars((string)$value, ENT_XML1, 'UTF-8');
    return "<Cell><Data ss:Type=\"$type\">$v</Data></Cell>";
}
function xstr($v): string { return xval('String', $v ?? ''); }
function xnum($v): string { return xval('Number', is_numeric($v) ? $v : 0); }
function xdate($v): string {
    return xstr($v ? date('d/m/Y', strtotime($v)) : '');
}

function encabezado(array $cols): string {
    $cells = '';
    foreach ($cols as $c) {
        $cells .= '<Cell ss:StyleID="hdr"><Data ss:Type="String">'
                . htmlspecialchars($c, ENT_XML1, 'UTF-8')
                . '</Data></Cell>';
    }
    return "<Row>$cells</Row>";
}

function hoja(string $nombre, string $filas): string {
    $n = htmlspecialchars($nombre, ENT_XML1, 'UTF-8');
    return "<Worksheet ss:Name=\"$n\"><Table>$filas</Table></Worksheet>";
}

// ── consultas ─────────────────────────────────────────────────────────────────

// 1. Resumen
$res_comuneros_activos  = (int)pg_fetch_result(pg_query($conexion, "SELECT COUNT(*) FROM comunero WHERE activo = TRUE"),  0, 0);
$res_comuneros_inactivos= (int)pg_fetch_result(pg_query($conexion, "SELECT COUNT(*) FROM comunero WHERE activo = FALSE"), 0, 0);
$res_sucesores          = (int)pg_fetch_result(pg_query($conexion, "SELECT COUNT(*) FROM sucesor"),                       0, 0);
$res_actas              = (int)pg_fetch_result(pg_query($conexion, "SELECT COUNT(*) FROM acta_posesion"),                  0, 0);
$res_asambleas          = (int)pg_fetch_result(pg_query($conexion, "SELECT COUNT(*) FROM asamblea"),                      0, 0);
$res_tequios            = (int)pg_fetch_result(pg_query($conexion, "SELECT COUNT(*) FROM tequio"),                        0, 0);
$res_pagos              = (int)pg_fetch_result(pg_query($conexion, "SELECT COUNT(*) FROM pago_predial WHERE pagado = TRUE"), 0, 0);
$res_monto              = pg_fetch_result(pg_query($conexion, "SELECT COALESCE(SUM(monto),0) FROM pago_predial WHERE pagado = TRUE"), 0, 0);

// 2. Comuneros activos
$q_activos = pg_query($conexion, "
    SELECT c.numero_progresivo, c.nombre_completo, c.telefono,
           s.descripcion AS situacion, l.nombre AS localidad,
           c.lugar_residencia, c.numero_ran, c.numero_certificado, c.observaciones
    FROM comunero c
    JOIN situacion s ON c.id_situacion = s.id_situacion
    JOIN localidad l ON c.id_localidad = l.id_localidad
    WHERE c.activo = TRUE
    ORDER BY c.numero_progresivo ASC
");

// 3. Comuneros inactivos
$q_inactivos = pg_query($conexion, "
    SELECT c.numero_progresivo, c.nombre_completo, c.telefono,
           s.descripcion AS situacion, l.nombre AS localidad,
           c.lugar_residencia, c.numero_ran, c.numero_certificado, c.observaciones
    FROM comunero c
    JOIN situacion s ON c.id_situacion = s.id_situacion
    JOIN localidad l ON c.id_localidad = l.id_localidad
    WHERE c.activo = FALSE
    ORDER BY c.numero_progresivo ASC
");

// 4. Sucesores
$q_sucesores = pg_query($conexion, "
    SELECT c.numero_progresivo, c.nombre_completo,
           su.nombre_sucesor, su.parentesco
    FROM sucesor su
    JOIN comunero c ON su.id_comunero = c.id_comunero
    ORDER BY c.numero_progresivo ASC, su.nombre_sucesor ASC
");

// 5. Actas de posesión
$q_actas = pg_query($conexion, "
    SELECT a.id_acta, c.numero_progresivo, c.nombre_completo,
           a.fecha_acta, a.descripcion_predio,
           (SELECT COUNT(*) FROM archivo ar WHERE ar.id_acta = a.id_acta) AS num_archivos
    FROM acta_posesion a
    JOIN comunero c ON a.id_comunero = c.id_comunero
    ORDER BY a.fecha_acta DESC
");

// 6. Asambleas (resumen)
$q_asambleas = pg_query($conexion, "
    SELECT a.id_asamblea, a.fecha, a.descripcion,
           COUNT(CASE WHEN aa.asistio = TRUE  THEN 1 END) AS asistentes,
           COUNT(aa.id_asamblea)                           AS convocados
    FROM asamblea a
    LEFT JOIN asistencia_asamblea aa ON a.id_asamblea = aa.id_asamblea
    GROUP BY a.id_asamblea, a.fecha, a.descripcion
    ORDER BY a.fecha DESC
");

// 7. Asistencia detallada
$q_asistencia = pg_query($conexion, "
    SELECT a.fecha, a.descripcion AS asamblea,
           c.numero_progresivo, c.nombre_completo,
           aa.asistio
    FROM asistencia_asamblea aa
    JOIN asamblea  a ON aa.id_asamblea  = a.id_asamblea
    JOIN comunero  c ON aa.id_comunero  = c.id_comunero
    ORDER BY a.fecha DESC, c.numero_progresivo ASC
");

// 8. Tequios (resumen)
$q_tequios = pg_query($conexion, "
    SELECT t.id_tequio, t.fecha, t.descripcion,
           COUNT(CASE WHEN ct.cumplio = TRUE THEN 1 END) AS participantes,
           COUNT(ct.id_tequio)                           AS convocados
    FROM tequio t
    LEFT JOIN cumplimiento_tequio ct ON t.id_tequio = ct.id_tequio
    GROUP BY t.id_tequio, t.fecha, t.descripcion
    ORDER BY t.fecha DESC
");

// 9. Cumplimiento detallado de tequios
$q_cumplimiento = pg_query($conexion, "
    SELECT t.fecha, t.descripcion AS tequio,
           c.numero_progresivo, c.nombre_completo,
           ct.cumplio
    FROM cumplimiento_tequio ct
    JOIN tequio   t ON ct.id_tequio  = t.id_tequio
    JOIN comunero c ON ct.id_comunero = c.id_comunero
    ORDER BY t.fecha DESC, c.numero_progresivo ASC
");

// 10. Pagos prediales
$q_pagos = pg_query($conexion, "
    SELECT c.numero_progresivo, c.nombre_completo, l.nombre AS localidad,
           p.anio, p.monto, p.pagado,
           COALESCE(p.fecha_pago::text, '') AS fecha_pago
    FROM pago_predial p
    JOIN comunero c ON p.id_comunero  = c.id_comunero
    JOIN localidad l ON c.id_localidad = l.id_localidad
    ORDER BY p.anio DESC, c.numero_progresivo ASC
");

// ── construcción de hojas ─────────────────────────────────────────────────────

// Hoja 1: Resumen
$h1 = encabezado(['Concepto', 'Total']);
$resumenData = [
    ['Comuneros Activos',   $res_comuneros_activos],
    ['Comuneros Inactivos', $res_comuneros_inactivos],
    ['Total Comuneros',     $res_comuneros_activos + $res_comuneros_inactivos],
    ['Sucesores Registrados', $res_sucesores],
    ['Actas de Posesión',   $res_actas],
    ['Asambleas Realizadas', $res_asambleas],
    ['Tequios Realizados',  $res_tequios],
    ['Pagos Prediales Realizados', $res_pagos],
    ['Monto Total Recaudado ($)', number_format((float)$res_monto, 2)],
    ['Fecha de Generación', date('d/m/Y H:i')],
];
foreach ($resumenData as [$label, $val]) {
    $h1 .= '<Row>' . xstr($label) . xstr($val) . '</Row>';
}

// Hoja 2: Comuneros Activos
$cols2 = ['N° Prog', 'Nombre Completo', 'Teléfono', 'Situación', 'Localidad', 'Lugar de Residencia', 'N° RAN', 'N° Certificado', 'Observaciones'];
$h2 = encabezado($cols2);
while ($r = pg_fetch_assoc($q_activos)) {
    $h2 .= '<Row>'
        . xstr(str_pad($r['numero_progresivo'], 4, '0', STR_PAD_LEFT))
        . xstr($r['nombre_completo'])
        . xstr($r['telefono'])
        . xstr($r['situacion'])
        . xstr($r['localidad'])
        . xstr($r['lugar_residencia'])
        . xstr($r['numero_ran'])
        . xstr($r['numero_certificado'])
        . xstr($r['observaciones'])
        . '</Row>';
}

// Hoja 3: Comuneros Inactivos
$h3 = encabezado($cols2);
while ($r = pg_fetch_assoc($q_inactivos)) {
    $h3 .= '<Row>'
        . xstr(str_pad($r['numero_progresivo'], 4, '0', STR_PAD_LEFT))
        . xstr($r['nombre_completo'])
        . xstr($r['telefono'])
        . xstr($r['situacion'])
        . xstr($r['localidad'])
        . xstr($r['lugar_residencia'])
        . xstr($r['numero_ran'])
        . xstr($r['numero_certificado'])
        . xstr($r['observaciones'])
        . '</Row>';
}

// Hoja 4: Sucesores
$h4 = encabezado(['N° Prog Comunero', 'Nombre Comunero', 'Nombre Sucesor', 'Parentesco']);
while ($r = pg_fetch_assoc($q_sucesores)) {
    $h4 .= '<Row>'
        . xstr(str_pad($r['numero_progresivo'], 4, '0', STR_PAD_LEFT))
        . xstr($r['nombre_completo'])
        . xstr($r['nombre_sucesor'])
        . xstr($r['parentesco'])
        . '</Row>';
}

// Hoja 5: Actas de Posesión
$h5 = encabezado(['Folio', 'N° Prog', 'Comunero', 'Fecha', 'Descripción Predio', 'N° Archivos']);
while ($r = pg_fetch_assoc($q_actas)) {
    $h5 .= '<Row>'
        . xnum($r['id_acta'])
        . xstr(str_pad($r['numero_progresivo'], 4, '0', STR_PAD_LEFT))
        . xstr($r['nombre_completo'])
        . xdate($r['fecha_acta'])
        . xstr($r['descripcion_predio'])
        . xnum($r['num_archivos'])
        . '</Row>';
}

// Hoja 6: Asambleas
$h6 = encabezado(['Folio', 'Fecha', 'Descripción', 'Asistentes', 'Convocados', 'Tasa Asistencia (%)']);
while ($r = pg_fetch_assoc($q_asambleas)) {
    $tasa = $r['convocados'] > 0 ? round(($r['asistentes'] / $r['convocados']) * 100, 1) : 0;
    $h6 .= '<Row>'
        . xnum($r['id_asamblea'])
        . xdate($r['fecha'])
        . xstr($r['descripcion'])
        . xnum($r['asistentes'])
        . xnum($r['convocados'])
        . xnum($tasa)
        . '</Row>';
}

// Hoja 7: Asistencia Detallada
$h7 = encabezado(['Fecha Asamblea', 'Descripción Asamblea', 'N° Prog', 'Comunero', 'Asistió']);
while ($r = pg_fetch_assoc($q_asistencia)) {
    $h7 .= '<Row>'
        . xdate($r['fecha'])
        . xstr($r['asamblea'])
        . xstr(str_pad($r['numero_progresivo'], 4, '0', STR_PAD_LEFT))
        . xstr($r['nombre_completo'])
        . xstr($r['asistio'] === 't' ? 'Sí' : 'No')
        . '</Row>';
}

// Hoja 8: Tequios
$h8 = encabezado(['Folio', 'Fecha', 'Descripción', 'Participantes', 'Convocados']);
while ($r = pg_fetch_assoc($q_tequios)) {
    $h8 .= '<Row>'
        . xnum($r['id_tequio'])
        . xdate($r['fecha'])
        . xstr($r['descripcion'])
        . xnum($r['participantes'])
        . xnum($r['convocados'])
        . '</Row>';
}

// Hoja 9: Cumplimiento Tequios
$h9 = encabezado(['Fecha Tequio', 'Descripción Tequio', 'N° Prog', 'Comunero', 'Cumplió']);
while ($r = pg_fetch_assoc($q_cumplimiento)) {
    $h9 .= '<Row>'
        . xdate($r['fecha'])
        . xstr($r['tequio'])
        . xstr(str_pad($r['numero_progresivo'], 4, '0', STR_PAD_LEFT))
        . xstr($r['nombre_completo'])
        . xstr($r['cumplio'] === 't' ? 'Sí' : 'No')
        . '</Row>';
}

// Hoja 10: Pagos Prediales
$h10 = encabezado(['N° Prog', 'Comunero', 'Localidad', 'Año', 'Monto ($)', 'Pagado', 'Fecha de Pago']);
while ($r = pg_fetch_assoc($q_pagos)) {
    $h10 .= '<Row>'
        . xstr(str_pad($r['numero_progresivo'], 4, '0', STR_PAD_LEFT))
        . xstr($r['nombre_completo'])
        . xstr($r['localidad'])
        . xnum($r['anio'])
        . xnum($r['monto'])
        . xstr($r['pagado'] === 't' ? 'Sí' : 'No')
        . xstr($r['fecha_pago'] ? date('d/m/Y', strtotime($r['fecha_pago'])) : '—')
        . '</Row>';
}

// ── salida XML ────────────────────────────────────────────────────────────────
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<?mso-application progid="Excel.Sheet"?>' . "\n";
?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
          xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">
 <Styles>
  <Style ss:ID="hdr">
   <Alignment ss:Horizontal="Center"/>
   <Font ss:Bold="1" ss:Color="#FFFFFF" ss:Size="10"/>
   <Interior ss:Color="#1E3A5F" ss:Pattern="Solid"/>
   <Borders>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#FFFFFF"/>
   </Borders>
  </Style>
  <Style ss:ID="default">
   <Font ss:Size="10"/>
  </Style>
 </Styles>

<?= hoja('Resumen General',         $h1) ?>
<?= hoja('Comuneros Activos',       $h2) ?>
<?= hoja('Comuneros Inactivos',     $h3) ?>
<?= hoja('Sucesores',               $h4) ?>
<?= hoja('Actas de Posesión',       $h5) ?>
<?= hoja('Asambleas',               $h6) ?>
<?= hoja('Asistencia Asambleas',    $h7) ?>
<?= hoja('Tequios',                 $h8) ?>
<?= hoja('Cumplimiento Tequios',    $h9) ?>
<?= hoja('Pagos Prediales',         $h10) ?>

</Workbook>
