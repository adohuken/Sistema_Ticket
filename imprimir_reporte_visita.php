<?php
// imprimir_reporte_visita.php
require_once __DIR__ . '/conexion.php';
session_start();

// Validar Sesión
if (!isset($_SESSION['usuario_id'])) {
    die("Acceso Denegado: Sesión no iniciada.");
}

// Validar ID
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    die("ID de visita inválido.");
}

try {
    // 1. Obtener Datos de la Visita
    $sql_visita = "SELECT v.*, 
                   e.nombre as empresa_nombre, 
                   s.nombre as sucursal_nombre,
                   u.nombre_completo as tecnico_nombre
                   FROM mantenimiento_solicitudes v
                   LEFT JOIN empresas e ON v.empresa_id = e.id
                   LEFT JOIN sucursales s ON v.sucursal_id = s.id
                   LEFT JOIN usuarios u ON v.asignado_a = u.id
                   WHERE v.id = ?";
    $stmt = $pdo->prepare($sql_visita);
    $stmt->execute([$id]);
    $visita = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$visita) {
        die("Visita no encontrada.");
    }

    // 2. Obtener Lista de Equipos Mantenidos en esta Visita
    // Buscamos en mantenimiento_equipos aquellos que tengan en descripcion_problema el ID de la visita
    // O idealmente, si tuviéramos una columna visita_id. Como usamos "Visita #ID" en descripcion, usaremos LIKE
    $term = "%Visita #$id)%";
    $sql_equipos = "SELECT m.*, 
                    i.tipo, i.marca, i.modelo, i.serial,
                    u.nombre_completo as tecnico_interno,
                    CONCAT(ua.nombres, ' ', ua.apellidos) as usuario_asignado,
                    m.proveedor as tecnico_externo
                    FROM mantenimiento_equipos m
                    JOIN inventario i ON m.equipo_id = i.id
                    LEFT JOIN usuarios u ON m.registrado_por = u.id
                    LEFT JOIN vista_personal_completo ua ON i.asignado_a = ua.id
                    WHERE m.descripcion_problema LIKE ?
                    ORDER BY i.tipo, i.marca";
    $stmt_eq = $pdo->prepare($sql_equipos);
    $stmt_eq->execute([$term]);
    $equipos = $stmt_eq->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error de base de datos: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Reporte Visita #<?= str_pad($id, 5, '0', STR_PAD_LEFT) ?> - <?= htmlspecialchars($visita['empresa_nombre']) ?> - <?= date('d-m-Y', strtotime($visita['fecha_programada'])) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print {
                display: none;
            }

            body {
                -webkit-print-color-adjust: exact;
            }
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
    </style>
</head>

<body class="bg-gray-100 p-8 print:p-0 print:bg-white">

    <div class="max-w-4xl mx-auto bg-white p-8 shadow-lg print:shadow-none">

        <!-- Header -->
        <div class="flex justify-between items-start border-b-2 border-slate-800 pb-6 mb-6">
            <div>
                <h1 class="text-3xl font-bold text-slate-800 uppercase tracking-wider">Reporte de Mantenimiento</h1>
                <p class="text-slate-500 font-semibold mt-1">Visita Técnica #
                    <?= str_pad($id, 5, '0', STR_PAD_LEFT) ?>
                </p>
            </div>
            <div class="text-right">
                <p class="font-bold text-slate-800 text-lg">
                    <?= htmlspecialchars($visita['empresa_nombre']) ?>
                </p>
                <p class="text-slate-600">
                    <?= htmlspecialchars($visita['sucursal_nombre']) ?>
                </p>
                <p class="text-slate-500 text-sm mt-1">
                    <?= date('d/m/Y', strtotime($visita['fecha_programada'])) ?>
                </p>
            </div>
        </div>

        <!-- Info General -->
        <div class="grid grid-cols-2 gap-8 mb-8">
            <div class="bg-slate-50 p-4 rounded-lg border border-slate-200">
                <p class="text-xs font-bold text-slate-500 uppercase mb-1">Motivo / Título</p>
                <p class="font-medium text-slate-800">
                    <?= htmlspecialchars($visita['titulo']) ?>
                </p>
            </div>
            <div class="bg-slate-50 p-4 rounded-lg border border-slate-200">
                <p class="text-xs font-bold text-slate-500 uppercase mb-1">Estado General</p>
                <span
                    class="inline-block px-2 py-0.5 rounded text-xs font-bold 
                    <?= $visita['estado'] === 'Completado' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' ?>">
                    <?= $visita['estado'] ?>
                </span>
            </div>
        </div>

        <!-- Tabla de Equipos -->
        <div class="mb-8">
            <h3 class="text-lg font-bold text-slate-700 mb-3 flex items-center gap-2">
                <span>Detalle de Equipos Atendidos</span>
                <span class="bg-slate-200 text-slate-600 text-xs px-2 py-0.5 rounded-full">
                    <?= count($equipos) ?>
                </span>
            </h3>

            <table class="w-full text-sm border-collapse">
                <thead>
                    <tr class="bg-slate-800 text-white text-xs uppercase">
                        <th class="p-2 text-left rounded-tl-lg">Equipo / Serial</th>
                        <th class="p-2 text-left">Asignado A</th>
                        <th class="p-2 text-left">Técnico Resp.</th>
                        <th class="p-2 text-center">Estado</th>
                        <th class="p-2 text-left rounded-tr-lg w-1/4">Observaciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 border border-slate-200">
                    <?php if (empty($equipos)): ?>
                        <tr>
                            <td colspan="5" class="p-8 text-center text-slate-400 italic">No se registraron equipos en este
                                reporte.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($equipos as $eq): ?>
                            <tr class="odd:bg-white even:bg-slate-50">
                                <td class="p-3 align-top">
                                    <div class="font-bold text-slate-800">
                                        <?= htmlspecialchars($eq['tipo']) ?>
                                        <?= htmlspecialchars($eq['marca']) ?>
                                    </div>
                                    <div class="text-xs text-slate-500">
                                        <?= htmlspecialchars($eq['modelo']) ?>
                                    </div>
                                    <div class="text-xs font-mono text-slate-400">SN:
                                        <?= htmlspecialchars($eq['serial']) ?>
                                    </div>
                                </td>
                                <td class="p-3 align-top text-slate-700 font-medium">
                                    <?= htmlspecialchars($eq['usuario_asignado'] ?? 'Sin Asignar') ?>
                                </td>
                                <td class="p-3 align-top">
                                    <?php if (!empty($eq['tecnico_externo'])): ?>
                                        <div class="font-medium text-indigo-700">
                                            <?= htmlspecialchars($eq['tecnico_externo']) ?>
                                        </div>
                                        <div class="text-[10px] text-slate-400 uppercase">Externo / Taller</div>
                                    <?php else: ?>
                                        <div class="font-medium text-slate-700">
                                            <?= htmlspecialchars($eq['tecnico_interno'] ?? 'Sistema') ?>
                                        </div>
                                        <div class="text-[10px] text-slate-400 uppercase">Interno</div>
                                    <?php endif; ?>
                                </td>
                                <td class="p-3 align-top text-center">
                                    <span
                                        class="text-xs font-bold px-2 py-1 rounded 
                                        <?= $eq['estado'] === 'Completado' ? 'bg-green-100 text-green-700' :
                                            ($eq['estado'] === 'En Proceso' ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-600') ?>">
                                        <?= $eq['estado'] ?>
                                    </span>
                                </td>
                                <td class="p-3 align-top text-slate-600 italic">
                                    <?= nl2br(htmlspecialchars($eq['descripcion_solucion'] ?? $eq['descripcion_problema'])) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Firmas -->
        <div class="mt-16 grid grid-cols-2 gap-16">
            <div class="text-center">
                <div class="border-b border-slate-400 mb-2 h-16"></div>
                <p class="font-bold text-slate-800 uppercase text-sm">Firma Técnico Responsable</p>
            </div>
            <div class="text-center">
                <div class="border-b border-slate-400 mb-2 h-16"></div>
                <p class="font-bold text-slate-800 uppercase text-sm">Firma / Sello Sucursal</p>
            </div>
        </div>

        <!-- Footer -->
        <div class="mt-8 pt-4 border-t border-slate-200 text-center text-xs text-slate-400">
            Generado el
            <?= date('d/m/Y H:i A') ?>
        </div>

        <!-- Botón Imprimir -->
        <div class="fixed bottom-8 right-8 no-print">
            <button onclick="window.print()"
                class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-6 rounded-full shadow-lg flex items-center gap-2 transition-transform hover:scale-105">
                <i class="ri-printer-line text-xl"></i> Imprimir Reporte
            </button>
        </div>

        <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    </div>

</body>

</html>