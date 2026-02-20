<?php
// imprimir_solicitud_ingreso.php
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/security_utils.php';

session_start();

// Validar Sesión
if (!isset($_SESSION['usuario_id'])) {
    die("Acceso Denegado: Sesión no iniciada.");
}

// Validar ID
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    die("ID de solicitud inválido.");
}

// Obtener datos del formulario
try {
    $stmt = $pdo->prepare("
        SELECT f.*, u.nombre_completo as nombre_registrador, u.empresa_id,
               e.nombre as empresa_nombre, e.logo_key
        FROM formularios_rrhh f
        LEFT JOIN usuarios u ON f.registrado_por = u.id
        LEFT JOIN empresas e ON u.empresa_id = e.id
        WHERE f.id = ? AND f.tipo = 'Ingreso'
    ");
    $stmt->execute([$id]);
    $solicitud = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$solicitud) {
        die("Solicitud de Ingreso no encontrada.");
    }

    // Obtener logo según empresa_id del usuario (dinámico desde BD)
    $logo_a_mostrar = null;
    $nombre_empresa = $solicitud['empresa_nombre'] ?? '';

    if (!empty($solicitud['logo_key'])) {
        $stmt_logo = $pdo->prepare("SELECT valor FROM configuracion_sistema WHERE clave = ?");
        $stmt_logo->execute([$solicitud['logo_key']]);
        $logo_a_mostrar = $stmt_logo->fetchColumn();
    }


} catch (PDOException $e) {
    die("Error de Base de Datos: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Imprimir Solicitud de Ingreso - #<?= $solicitud['id'] ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print {
                display: none !important;
            }

            body {
                background: white;
                -webkit-print-color-adjust: exact;
            }

            .print-border {
                border: 2px solid #ddd;
            }
        }

        body {
            background: #f3f4f6;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .document-container {
            max-width: 800px;
            margin: 20px auto;
            background: white;
            padding: 40px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>

<body>

    <!-- Botones de Acción -->
    <div class="max-w-4xl mx-auto mt-4 mb-4 flex justify-between no-print px-4">
        <button onclick="window.close()"
            class="px-4 py-2 bg-slate-500 text-white rounded hover:bg-slate-600 transition">
            Cerrar
        </button>
        <button onclick="window.print()"
            class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition font-bold">
            Imprimir Documento
        </button>
    </div>

    <!-- Documento -->
    <div class="document-container">

        <!-- Encabezado -->
        <div class="text-center border-b-2 border-slate-800 pb-4 mb-6">
            <!-- Logo de la Empresa -->
            <?php if (!empty($logo_a_mostrar)): ?>
                <div class="flex justify-center items-center mb-4">
                    <img src="<?= htmlspecialchars($logo_a_mostrar) ?>" alt="<?= htmlspecialchars($nombre_empresa) ?>"
                        class="h-24 object-contain">
                </div>
            <?php endif; ?>

            <h1 class="text-3xl font-bold text-slate-900 uppercase">Solicitud de Ingreso de Personal</h1>
            <p class="text-sm text-slate-500 mt-1 uppercase tracking-widest">
                <?= !empty($nombre_empresa) ? htmlspecialchars($nombre_empresa) : 'Master Technologies' ?> -
                Departamento de RRHH
            </p>
            <p class="text-xs text-slate-400 mt-2">Folio: #<?= str_pad($solicitud['id'], 6, '0', STR_PAD_LEFT) ?> |
                Fecha: <?= date('d/m/Y h:i A', strtotime($solicitud['fecha_creacion'] ?? 'now')) ?></p>
        </div>

        <!-- Sección 1: Datos del Colaborador -->
        <div class="mb-6">
            <h2 class="text-sm font-bold text-white bg-slate-800 px-3 py-1 uppercase mb-3">1. Datos del Colaborador</h2>
            <table class="w-full text-sm">
                <tr>
                    <td class="font-bold text-slate-600 w-1/3 py-1">Nombre Completo:</td>
                    <td class="border-b border-slate-300"><?= htmlspecialchars($solicitud['nombre_colaborador']) ?></td>
                </tr>
                <tr>
                    <td class="font-bold text-slate-600 py-1">Cédula / Teléfono:</td>
                    <td class="border-b border-slate-300"><?= htmlspecialchars($solicitud['cedula_telefono']) ?></td>
                </tr>
                <tr>
                    <td class="font-bold text-slate-600 py-1">Cargo / Zona:</td>
                    <td class="border-b border-slate-300"><?= htmlspecialchars($solicitud['cargo_zona']) ?></td>
                </tr>
                <tr>
                    <td class="font-bold text-slate-600 py-1">Fecha Solicitud:</td>
                    <td class="border-b border-slate-300 font-bold">
                        <?= date('d/m/Y', strtotime($solicitud['fecha_solicitud'])) ?>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Sección 2: Requerimientos -->
        <div class="mb-6">
            <h2 class="text-sm font-bold text-white bg-slate-800 px-3 py-1 uppercase mb-3">2. Requerimientos
                Tecnológicos</h2>
            <table class="w-full text-sm mb-2">
                <tr>
                    <td class="font-bold text-slate-600 w-1/3 py-1">Licencias (M365):</td>
                    <td class="w-16 font-bold"><?= $solicitud['disponibilidad_licencias'] ?></td>
                    <td class="text-xs text-slate-500 italic">
                        <?= htmlspecialchars($solicitud['detalle_licencias'] ?? '') ?>
                    </td>
                </tr>
                <tr>
                    <td class="font-bold text-slate-600 py-1">Crear Correo:</td>
                    <td class="font-bold"><?= $solicitud['correo_nuevo'] ?></td>
                    <td class="text-xs text-slate-500 italic">
                        <?= htmlspecialchars($solicitud['direccion_correo'] ?? '') ?>
                    </td>
                </tr>
                <tr>
                    <td class="font-bold text-slate-600 py-1">Mostrar Remitente:</td>
                    <td class="font-bold"><?= $solicitud['remitente_mostrar'] ?></td>
                    <td class="text-xs text-slate-500 italic">
                        <?= htmlspecialchars($solicitud['detalle_remitente'] ?? '') ?>
                    </td>
                </tr>
                <tr>
                    <td class="font-bold text-slate-600 py-1">Respaldo Nube:</td>
                    <td class="font-bold"><?= $solicitud['respaldo_nube'] ?></td>
                    <td class="text-xs text-slate-500 italic">
                        <?= htmlspecialchars($solicitud['detalle_respaldo'] ?? '') ?>
                    </td>
                </tr>
                <tr>
                    <td class="font-bold text-slate-600 py-1">Reenvío Correos:</td>
                    <td class="font-bold"><?= $solicitud['reenvios_correo'] ?></td>
                    <td class="text-xs text-slate-500 italic">
                        <?= htmlspecialchars($solicitud['detalle_reenvios'] ?? '') ?>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Sección 3: Asignación de Activos -->
        <div class="mb-6">
            <h2 class="text-sm font-bold text-white bg-slate-800 px-3 py-1 uppercase mb-3">3. Asignación de Activos</h2>
            <div class="grid grid-cols-2 gap-6">
                <!-- Equipo Cómputo -->
                <div class="border border-slate-200 p-3 rounded bg-slate-50">
                    <p class="font-bold text-xs text-slate-500 uppercase mb-1">Equipo de Cómputo</p>
                    <div class="flex justify-between items-center mb-1">
                        <span class="text-sm font-bold text-slate-700">Asignar:</span>
                        <span
                            class="text-sm font-bold <?= $solicitud['asignacion_equipo'] == 'SI' ? 'text-green-600' : 'text-slate-400' ?>">
                            <?= $solicitud['asignacion_equipo'] ?>
                        </span>
                    </div>
                    <?php if ($solicitud['asignacion_equipo'] == 'SI'): ?>
                        <p class="text-xs text-slate-600 border-t border-slate-200 pt-1 mt-1">
                            <?= htmlspecialchars($solicitud['detalle_asignacion']) ?>
                        </p>
                    <?php endif; ?>
                </div>

                <!-- Nube Móvil -->
                <div class="border border-slate-200 p-3 rounded bg-slate-50">
                    <p class="font-bold text-xs text-slate-500 uppercase mb-1">Nube Móvil</p>
                    <div class="flex justify-between items-center mb-1">
                        <span class="text-sm font-bold text-slate-700">Asignar:</span>
                        <span
                            class="text-sm font-bold <?= $solicitud['nube_movil'] == 'SI' ? 'text-green-600' : 'text-slate-400' ?>">
                            <?= $solicitud['nube_movil'] ?>
                        </span>
                    </div>
                    <?php if ($solicitud['nube_movil'] == 'SI'): ?>
                        <p class="text-xs text-slate-600 border-t border-slate-200 pt-1 mt-1">
                            <?= htmlspecialchars($solicitud['detalle_nube_movil']) ?>
                        </p>
                    <?php endif; ?>
                </div>

                <!-- Equipo Usado (Si aplica) -->
                <?php if (!empty($solicitud['especificacion_equipo_usado'])): ?>
                    <div class="border border-slate-200 p-3 rounded bg-slate-50 col-span-2">
                        <p class="font-bold text-xs text-slate-500 uppercase mb-1">Equipo Reasignado / Usado</p>
                        <p class="text-xs text-slate-600">
                            <?= htmlspecialchars($solicitud['especificacion_equipo_usado']) ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Observaciones -->
        <?php if (!empty($solicitud['otras_indicaciones'])): ?>
            <div class="mb-8">
                <h2 class="text-sm font-bold text-slate-700 uppercase mb-2 border-b border-slate-200">Indicaciones
                    Adicionales</h2>
                <p class="text-sm text-slate-600 italic p-3 bg-slate-50 border border-slate-100 rounded">
                    <?= nl2br(htmlspecialchars($solicitud['otras_indicaciones'])) ?>
                </p>
            </div>
        <?php endif; ?>

        <!-- Firmas -->
        <div class="mt-16 pt-8 grid grid-cols-2 gap-12">
            <div class="text-center">
                <div class="border-t border-slate-800 w-3/4 mx-auto pt-2"></div>
                <p class="font-bold text-sm text-slate-700">Firma del Colaborador</p>
                <p class="text-xs text-slate-500">Recibí activos y cuentas conformes</p>
            </div>
            <div class="text-center">
                <div class="border-t border-slate-800 w-3/4 mx-auto pt-2"></div>
                <p class="font-bold text-sm text-slate-700">Autorizado por (RRHH/Sistemas)</p>
                <p class="text-xs text-slate-500"><?= htmlspecialchars($solicitud['nombre_registrador'] ?? 'Sistema') ?>
                </p>
            </div>
        </div>

        <!-- Footer -->
        <div class="mt-12 text-center border-t border-slate-200 pt-4">
            <p class="text-xs text-slate-400">Documento generado automáticamente por Sistema de Tickets Master
                Technologies.</p>
        </div>

    </div>

    <script>
        // Auto-imprimir al cargar opcional
        // window.onload = function() { window.print(); }
    </script>
</body>

</html>