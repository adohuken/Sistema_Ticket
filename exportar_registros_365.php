<?php
/**
 * exportar_registros_365.php
 * Exporta el listado de registros 365 a Excel (.xls)
 * Se utiliza formato HTML Table para asegurar que las columnas se respeten y permitir formato básico.
 */

require_once 'conexion.php';
session_start();

// Verificar autenticación
if (!isset($_SESSION['usuario_id'])) {
    die("Acceso denegado");
}

// Configurar nombre del archivo
$filename = 'registros_365_export_' . date('Y-m-d') . '.xls';

// Configurar cabeceras para obligar la descarga como Excel
header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// Recibir filtros
$filtro_estado = $_GET['estado'] ?? '';
$filtro_licencia = $_GET['licencia'] ?? '';
$filtro_empresa = $_GET['empresa'] ?? '';
$filtro_sucursal = $_GET['sucursal'] ?? '';
$filtro_asignacion = $_GET['asignacion'] ?? '';
$busqueda = $_GET['busqueda'] ?? '';

// Construir query
$sql = "
    SELECT r.*, 
           CONCAT(u.nombres, ' ', u.apellidos) as usuario_nombre,
           u.sucursal_nombre as departamento,
           e.nombre as empresa_nombre,
           s.nombre as sucursal_nombre
    FROM registros_365 r
    LEFT JOIN vista_personal_completo u ON r.usuario_id = u.id
    LEFT JOIN empresas e ON r.empresa_id = e.id
    LEFT JOIN sucursales s ON r.sucursal_id = s.id
    WHERE 1=1
";

$params = [];

if ($filtro_estado) {
    $sql .= " AND r.estado = ?";
    $params[] = $filtro_estado;
}

if ($filtro_licencia) {
    $sql .= " AND r.licencia = ?";
    $params[] = $filtro_licencia;
}

if ($filtro_empresa) {
    $sql .= " AND r.empresa_id = ?";
    $params[] = $filtro_empresa;
}

if ($filtro_sucursal) {
    $sql .= " AND r.sucursal_id = ?";
    $params[] = $filtro_sucursal;
}

if ($filtro_asignacion === 'asignado') {
    $sql .= " AND r.usuario_id IS NOT NULL";
} elseif ($filtro_asignacion === 'no_asignado') {
    $sql .= " AND r.usuario_id IS NULL";
}

if ($busqueda) {
    $sql .= " AND (r.email LIKE ? OR u.nombres LIKE ? OR u.apellidos LIKE ?)";
    $busqueda_param = "%$busqueda%";
    $params[] = $busqueda_param;
    $params[] = $busqueda_param;
    $params[] = $busqueda_param;
}

$sql .= " ORDER BY r.email ASC";

// Ejecutar consulta
$stmt = $pdo->prepare($sql);
$stmt->execute($params);

// Iniciar salida HTML
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #000000; padding: 5px; text-align: left; vertical-align: top; }
        th { background-color: #f0f0f0; font-weight: bold; }
        .text-center { text-align: center; }
    </style>
</head>
<body>
    <table>
        <thead>
            <tr>
                <th>Email / Cuenta</th>
                <th>Estado</th>
                <th>Licencia</th>
                <th>Usuario Asignado</th>
                <th>Departamento</th>
                <th>Empresa</th>
                <th>Sucursal</th>
                <th>Fecha Asignación</th>
                <th>Password Azure</th>
                <th>Password AG</th>
                <th>PIN Windows</th>
                <th>Cuenta Gmail</th>
                <th>Password Gmail</th>
                <th>Teléfono Principal</th>
                <th>Teléfono Secundario</th>
                <th>Observaciones</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                <tr>
                    <td><?= htmlspecialchars($row['email']) ?></td>
                    <td><?= htmlspecialchars($row['estado']) ?></td>
                    <td><?= htmlspecialchars($row['licencia']) ?></td>
                    <td><?= htmlspecialchars($row['usuario_nombre'] ?: 'Sin asignar') ?></td>
                    <td><?= htmlspecialchars($row['departamento']) ?></td>
                    <td><?= htmlspecialchars($row['empresa_nombre']) ?></td>
                    <td><?= htmlspecialchars($row['sucursal_nombre']) ?></td>
                    <td><?= $row['fecha_asignacion'] ? date('d/m/Y', strtotime($row['fecha_asignacion'])) : '' ?></td>
                    <td><?= htmlspecialchars($row['password_azure']) ?></td>
                    <td><?= htmlspecialchars($row['password_ag']) ?></td>
                    <td><?= htmlspecialchars($row['pin_windows']) ?></td>
                    <td><?= htmlspecialchars($row['cuenta_gmail']) ?></td>
                    <td><?= htmlspecialchars($row['password_gmail']) ?></td>
                    <td><?= htmlspecialchars($row['telefono_principal']) ?></td>
                    <td><?= htmlspecialchars($row['telefono_secundario']) ?></td>
                    <td><?= htmlspecialchars($row['observaciones']) ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</body>
</html>
