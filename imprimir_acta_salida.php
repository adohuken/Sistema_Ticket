<?php
// imprimir_acta_salida.php
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

// Obtener datos del formulario y empresa del creador
try {
    $stmt = $pdo->prepare("
        SELECT f.*, u.nombre_completo as nombre_registrador, u.empresa_asignada
        FROM formularios_rrhh f
        LEFT JOIN usuarios u ON f.creado_por = u.id
        WHERE f.id = ? AND f.tipo = 'Salida'
    ");
    $stmt->execute([$id]);
    $solicitud = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$solicitud) {
        die("Solicitud de Salida no encontrada.");
    }

    // Obtener logo según empresa asignada del usuario
    $logo_a_mostrar = null;
    $nombre_empresa = '';

    if (!empty($solicitud['empresa_asignada'])) {
        // Mapeo de empresa a clave de logo
        $logo_map = [
            'mastertec' => 'logo_mastertec',
            'suministros' => 'logo_master_suministros',
            'centro' => 'logo_centro'
        ];

        $nombre_map = [
            'mastertec' => 'MasterTec',
            'suministros' => 'Master Suministros',
            'centro' => 'Centro'
        ];

        $logo_key = $logo_map[$solicitud['empresa_asignada']] ?? null;
        $nombre_empresa = $nombre_map[$solicitud['empresa_asignada']] ?? '';

        if ($logo_key) {
            $stmt_logo = $pdo->prepare("SELECT valor FROM configuracion_sistema WHERE clave = ?");
            $stmt_logo->execute([$logo_key]);
            $logo_a_mostrar = $stmt_logo->fetchColumn();
        }
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
    <title>Acta Informativa de Baja - #<?= $solicitud['id'] ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            @page {
                size: letter;
                margin: 1.5cm;
            }

            body {
                background: white;
                -webkit-print-color-adjust: exact;
                margin: 0;
                padding: 0;
            }

            .no-print {
                display: none !important;
            }

            .print-border {
                border: 2px solid #ddd;
            }

            .document-container {
                box-shadow: none !important;
                margin: 0 !important;
                padding: 0 !important;
                max-width: 100% !important;
                width: 100% !important;
                border: none !important;
            }

            /* Evitar cortes feos en tablas y bloques */
            .avoid-break {
                page-break-inside: avoid;
                break-inside: avoid;
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
            Imprimir Acta Informativa
        </button>
    </div>

    <!-- Documento -->
    <div class="document-container border-t-8 border-red-500 avoid-break">

        <!-- Encabezado -->
        <div class="text-center border-b-2 border-slate-200 pb-4 mb-6">
            <!-- Logo de la Empresa -->
            <?php if (!empty($logo_a_mostrar)): ?>
                <div class="flex justify-center items-center mb-4">
                    <img src="<?= htmlspecialchars($logo_a_mostrar) ?>" alt="<?= htmlspecialchars($nombre_empresa) ?>"
                        class="h-24 object-contain">
                </div>
            <?php endif; ?>

            <h1 class="text-3xl font-bold text-slate-900 uppercase">Acta Informativa de Baja</h1>
            <p class="text-sm text-slate-500 mt-1 uppercase tracking-widest">
                <?= !empty($nombre_empresa) ? htmlspecialchars($nombre_empresa) : 'Master Technologies' ?> -
                Departamento de RRHH
            </p>
            <div class="mt-2 inline-block bg-blue-100 text-blue-800 text-xs font-bold px-3 py-1 rounded uppercase">Copia
                Informativa - No Válida como Finiquito</div>
            <p class="text-xs text-slate-400 mt-2">Folio: #<?= str_pad($solicitud['id'], 6, '0', STR_PAD_LEFT) ?> |
                Generado: <?= date('d/m/Y h:i A') ?></p>
        </div>

        <!-- Sección 1: Datos del Colaborador -->
        <div class="mb-6 avoid-break">
            <h2 class="text-lg font-bold text-slate-800 border-l-4 border-red-500 pl-3 mb-3 uppercase">1. Datos del
                Colaborador</h2>
            <div class="grid grid-cols-2 gap-x-8 gap-y-4 bg-slate-50 p-4 rounded-lg">
                <div>
                    <span class="text-xs font-bold text-slate-500 uppercase block">Nombre Completo</span>
                    <span
                        class="text-sm font-semibold text-slate-800"><?= htmlspecialchars($solicitud['nombre_colaborador']) ?></span>
                </div>
                <div>
                    <span class="text-xs font-bold text-slate-500 uppercase block">Cédula / Teléfono</span>
                    <span
                        class="text-sm font-semibold text-slate-800"><?= htmlspecialchars($solicitud['cedula_telefono']) ?></span>
                </div>
                <div>
                    <span class="text-xs font-bold text-slate-500 uppercase block">Cargo / Zona</span>
                    <span
                        class="text-sm font-semibold text-slate-800"><?= htmlspecialchars($solicitud['cargo_zona']) ?></span>
                </div>
                <div>
                    <span class="text-xs font-bold text-slate-500 uppercase block">Fecha Efectiva Salida</span>
                    <span
                        class="text-sm font-semibold text-slate-800"><?= date('d/m/Y', strtotime($solicitud['fecha_efectiva'])) ?></span>
                </div>
            </div>
        </div>

        <!-- Sección 2: Cierre de Accesos -->
        <div class="mb-6 avoid-break">
            <h2 class="text-lg font-bold text-slate-800 border-l-4 border-red-500 pl-3 mb-3 uppercase">2. Cierre de
                Accesos e Información</h2>
            <table class="w-full text-sm mb-2 text-left">
                <thead class="bg-slate-100 text-slate-600">
                    <tr>
                        <th class="py-2 px-3 font-bold">Concepto</th>
                        <th class="py-2 px-3 font-bold text-center">Estado</th>
                        <th class="py-2 px-3 font-bold">Detalle</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr>
                        <td class="py-2 px-3 font-bold text-slate-600">Bloqueo de Correo</td>
                        <td class="py-2 px-3 text-center font-bold"><?= $solicitud['bloqueo_correo'] ?></td>
                        <td class="py-2 px-3 text-slate-500 italic">
                            <?= htmlspecialchars($solicitud['cuenta_correo_bloqueo'] ?? '-') ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="py-2 px-3 font-bold text-slate-600">Respaldo Información</td>
                        <td class="py-2 px-3 text-center font-bold"><?= $solicitud['respaldo_info'] ?></td>
                        <td class="py-2 px-3 text-slate-500 italic">
                            <?= htmlspecialchars($solicitud['detalle_respaldo_salida'] ?? '-') ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="py-2 px-3 font-bold text-slate-600">Redirección Correos</td>
                        <td class="py-2 px-3 text-center font-bold"><?= $solicitud['redireccion_correo'] ?></td>
                        <td class="py-2 px-3 text-slate-500 italic">
                            <?= htmlspecialchars($solicitud['email_redireccion'] ?? '-') ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Sección 3: Devolución de Equipos -->
        <div class="mb-6 avoid-break">
            <h2 class="text-lg font-bold text-slate-800 border-l-4 border-red-500 pl-3 mb-3 uppercase">3. Devolución de
                Activos</h2>
            <div class="grid grid-cols-2 gap-6 bg-slate-50 p-4 rounded-lg">
                <div class="border-b border-slate-200 pb-3">
                    <p class="font-bold text-xs text-slate-500 uppercase mb-1">PC / Laptop</p>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-sm font-bold text-slate-700">Devuelto:</span>
                        <span
                            class="text-sm font-bold <?= $solicitud['devolucion_equipo'] == 'SI' ? 'text-green-600' : 'text-slate-400' ?>">
                            <?= $solicitud['devolucion_equipo'] ?>
                        </span>
                    </div>
                    <?php if ($solicitud['devolucion_equipo'] == 'SI'): ?>
                        <p class="text-xs text-slate-600">
                            <?= htmlspecialchars($solicitud['detalle_devolucion_equipo']) ?>
                        </p>
                    <?php endif; ?>
                </div>

                <div class="border-b border-slate-200 pb-3">
                    <p class="font-bold text-xs text-slate-500 uppercase mb-1">Móvil Corporativo</p>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-sm font-bold text-slate-700">Devuelto:</span>
                        <span
                            class="text-sm font-bold <?= $solicitud['devolucion_movil'] == 'SI' ? 'text-green-600' : 'text-slate-400' ?>">
                            <?= $solicitud['devolucion_movil'] ?>
                        </span>
                    </div>
                    <?php if ($solicitud['devolucion_movil'] == 'SI'): ?>
                        <p class="text-xs text-slate-600">
                            <?= htmlspecialchars($solicitud['detalle_devolucion_movil']) ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Observaciones -->
        <?php if (!empty($solicitud['observaciones'])): ?>
            <div class="mb-8 p-4 bg-yellow-50 border border-yellow-200 rounded-lg avoid-break">
                <h3 class="font-bold text-yellow-800 text-sm uppercase mb-2">Observaciones Adicionales</h3>
                <p class="text-sm text-slate-700 italic">
                    <?= nl2br(htmlspecialchars($solicitud['observaciones'])) ?>
                </p>
            </div>
        <?php endif; ?>

        <!-- Footer Informativo -->
        <div class="mt-8 pt-4 border-t border-slate-300 text-center">
            <p class="text-xs font-bold text-slate-500 uppercase mb-1">--- DOCUMENTO MERAMENTE INFORMATIVO ---</p>
            <p class="text-xs text-slate-400">Generado por Sistema de Tickets Master Technologies.</p>
        </div>

    </div>

</body>

</html>