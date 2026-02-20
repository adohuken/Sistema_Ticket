<?php
// imprimir_acta_licencia.php
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
        SELECT f.*, u.nombre_completo as nombre_registrador, u.empresa_id,
               e.nombre as empresa_nombre, e.logo_key
        FROM formularios_rrhh f
        LEFT JOIN usuarios u ON f.creado_por = u.id
        LEFT JOIN empresas e ON u.empresa_id = e.id
        WHERE f.id = ? AND (f.tipo = 'Licencia' OR (f.tipo NOT IN ('Ingreso', 'Salida') AND f.detalle_licencias IS NOT NULL AND f.detalle_licencias != ''))
    ");
    $stmt->execute([$id]);
    $solicitud = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$solicitud) {
        die("Solicitud de Licencia no encontrada (o ID incorrecto).");
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
    <title>Acta Informativa de Licencia - #
        <?= $solicitud['id'] ?>
    </title>
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
            class="px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700 transition font-bold">
            Imprimir Acta
        </button>
    </div>

    <!-- Documento -->
    <div class="document-container border-t-8 border-purple-500 avoid-break">

        <!-- Encabezado -->
        <div class="text-center border-b-2 border-slate-200 pb-4 mb-6">
            <!-- Logo de la Empresa -->
            <?php if (!empty($logo_a_mostrar)): ?>
                <div class="flex justify-center items-center mb-4">
                    <img src="<?= htmlspecialchars($logo_a_mostrar) ?>" alt="<?= htmlspecialchars($nombre_empresa) ?>"
                        class="h-24 object-contain">
                </div>
            <?php endif; ?>

            <h1 class="text-3xl font-bold text-slate-900 uppercase">Acta Informativa de Licencia</h1>
            <p class="text-sm text-slate-500 mt-1 uppercase tracking-widest">
                <?= !empty($nombre_empresa) ? htmlspecialchars($nombre_empresa) : 'Master Technologies' ?> -
                Departamento de TI
            </p>
            <div class="mt-2 inline-block bg-purple-100 text-purple-800 text-xs font-bold px-3 py-1 rounded uppercase">
                Documento Oficial de Asignación
            </div>
            <p class="text-xs text-slate-400 mt-2">Folio: #
                <?= str_pad($solicitud['id'], 6, '0', STR_PAD_LEFT) ?> |
                Fecha Solicitud:
                <?= date('d/m/Y', strtotime($solicitud['fecha_solicitud'])) ?>
            </p>
        </div>

        <!-- Introducción -->
        <div class="mb-6 text-justify text-sm text-slate-700 leading-relaxed">
            <p>
                Por medio de la presente, se hace constar la entrega y asignación de la licencia de software descrita a
                continuación,
                para uso exclusivo de las actividades laborales dentro de la empresa. El colaborador acepta la
                responsabilidad
                sobre el buen uso de las credenciales y el software asignado.
            </p>
        </div>

        <!-- Sección 1: Datos del Beneficiario -->
        <div class="mb-6 avoid-break">
            <h2 class="text-lg font-bold text-slate-800 border-l-4 border-purple-500 pl-3 mb-3 uppercase">1.
                Beneficiario (Usuario Final)</h2>
            <div class="grid grid-cols-2 gap-x-8 gap-y-4 bg-slate-50 p-4 rounded-lg">
                <div>
                    <span class="text-xs font-bold text-slate-500 uppercase block">Nombre Completo</span>
                    <span class="text-sm font-semibold text-slate-800">
                        <?= htmlspecialchars($solicitud['nombre_colaborador']) ?>
                    </span>
                </div>

                <div>
                    <span class="text-xs font-bold text-slate-500 uppercase block">Departamento / Área</span>
                    <span class="text-sm font-semibold text-slate-800">
                        <?= htmlspecialchars($solicitud['cargo_zona']) ?>
                    </span>
                </div>
                <!-- Usamos cargo_zona para guardar el departamento en este tipo de formulario -->
            </div>
        </div>

        <!-- Sección 2: Detalle de la Licencia -->
        <div class="mb-6 avoid-break">
            <h2 class="text-lg font-bold text-slate-800 border-l-4 border-purple-500 pl-3 mb-3 uppercase">2. Detalle del
                Software / Licencia</h2>

            <div class="bg-white border border-slate-200 rounded-lg overflow-hidden">
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-100 text-slate-600">
                        <tr>
                            <th class="py-3 px-4 font-bold border-b border-slate-200">Tipo de Licencia</th>
                            <th class="py-3 px-4 font-bold border-b border-slate-200">Detalles / Cuenta</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr>
                            <td class="py-4 px-4 font-bold text-slate-700 w-1/3">
                                <div class="flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-purple-500"></span>
                                    Producto
                                </div>
                            </td>
                            <td class="py-4 px-4 text-slate-600">
                                <?= htmlspecialchars($solicitud['detalle_licencias']) ?>
                            </td>
                        </tr>
                        <?php if (!empty($solicitud['otras_indicaciones'])): ?>
                            <tr>
                                <td class="py-4 px-4 font-bold text-slate-700 align-top pt-4">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                                        Justificación / Notas
                                    </div>
                                </td>
                                <td class="py-4 px-4 text-slate-600 italic align-top">
                                    <?= nl2br(htmlspecialchars($solicitud['otras_indicaciones'])) ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Firmas -->
        <div class="mt-12 pt-8 avoid-break">
            <div class="grid grid-cols-2 gap-12">
                <!-- Firma Entrega (TI/Sistemas) -->
                <div class="text-center">
                    <div class="border-b border-slate-400 mb-2 h-16"></div>
                    <p class="font-bold text-slate-800 uppercase text-sm">Entregado Por (TI)</p>
                    <p class="text-xs text-slate-500">
                        <?= htmlspecialchars($solicitud['nombre_registrador'] ?? 'Sistemas') ?>
                    </p>
                </div>

                <!-- Firma Recibe (Usuario) -->
                <div class="text-center">
                    <div class="border-b border-slate-400 mb-2 h-16"></div>
                    <p class="font-bold text-slate-800 uppercase text-sm">Recibido Conforme</p>
                    <p class="text-xs text-slate-500">
                        <?= htmlspecialchars($solicitud['nombre_colaborador']) ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Footer Informativo -->
        <div class="mt-12 pt-4 border-t border-slate-300 text-center">
            <p class="text-xs font-bold text-slate-500 uppercase mb-1">--- DOCUMENTO INTERNO ---</p>
            <p class="text-xs text-slate-400">Generado por Sistema de Tickets Master Technologies.</p>
        </div>

    </div>

</body>

</html>