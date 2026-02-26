<?php
/**
 * seccion_acta_devolucion_rapida.php - Generar Acta de Devolución (Baja)
 * Vista imprimible para auditar equipos al momento de la baja de personal
 */

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "ID de empleado no especificado.";
    return;
}

if (!isset($_GET['activos']) || empty($_GET['activos'])) {
    echo "No se especificaron activos a devolver.";
    return;
}

$personal_id = (int) $_GET['id'];
$activos_ids_csv = $_GET['activos'];

// Validar que sean solo IDs numéricos separados por coma para prevenir inyección SQL
if (!preg_match('/^[0-9,]+$/', $activos_ids_csv)) {
    echo "Formato de activos inválido.";
    return;
}

try {
    // 1. Obtener Datos del Empleado
    $stmt = $pdo->prepare("SELECT * FROM vista_personal_completo WHERE id = ?");
    $stmt->execute([$personal_id]);
    $empleado = $stmt->fetch();

    if (!$empleado) {
        echo "Personal no encontrado.";
        return;
    }

    $nombre_completo = $empleado['nombres'] . ' ' . $empleado['apellidos'];

    // 2. Obtener Activos Específicos ('En Revisión' o 'Disponible' pero por ID)
    $stmtActivos = $pdo->query("
        SELECT * FROM inventario 
        WHERE id IN ($activos_ids_csv)
        ORDER BY tipo, marca
    ");
    $activos_asignados = $stmtActivos->fetchAll();

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    return;
}
?>

<!-- Estilos para impresión -->
<style>
    @media print {
        @page {
            size: A4;
            margin: 0;
        }

        body>* {
            display: none !important;
        }

        body>.print-container {
            display: block !important;
            position: relative !important;
            top: 0 !important;
            left: 0 !important;
            width: 100% !important;
            height: auto !important;
            padding: 1.5cm 2cm !important;
            margin: 0 !important;
            background: white;
            box-sizing: border-box !important;
        }

        html,
        body {
            width: 100%;
            margin: 0 !important;
            padding: 0 !important;
            background: white;
        }

        .no-print {
            display: none !important;
        }

        * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
    }
</style>

<!-- Aviso de Edición -->
<div class="max-w-[21cm] mx-auto mb-4 bg-orange-50 border-l-4 border-orange-500 p-4 rounded shadow-sm no-print mt-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center">
            <i class="ri-edit-circle-line text-2xl text-orange-600 mr-4"></i>
            <div>
                <h3 class="font-bold text-orange-800">Documento Editable</h3>
                <p class="text-sm text-orange-700">Puedes hacer clic en cualquier texto para editarlo antes de imprimir.
                </p>
            </div>
        </div>
    </div>
</div>

<div
    class="print-container bg-white p-8 max-w-[21cm] mx-auto shadow-xl rounded-xl my-8 border border-slate-100 font-sans text-slate-900 flex flex-col min-h-[29.7cm]">

    <!-- Encabezado del Acta -->
    <div class="flex justify-between items-start mb-4 border-b-2 border-slate-800 pb-4">
        <div class="flex items-center gap-4">
            <!-- Logo Dinámico -->
            <?php
            $logo_url = '';
            $logo_key_val = '';

            if (isset($_SESSION['usuario_id'])) {
                try {
                    $stmt_u = $pdo->prepare("
                        SELECT u.empresa_id, e.logo_key
                        FROM usuarios u
                        LEFT JOIN empresas e ON u.empresa_id = e.id
                        WHERE u.id = ?
                    ");
                    $stmt_u->execute([$_SESSION['usuario_id']]);
                    $row_u = $stmt_u->fetch(PDO::FETCH_ASSOC);
                    $logo_key_val = $row_u['logo_key'] ?? '';
                } catch (Exception $e) {
                }
            }

            if (!$logo_key_val && !empty($empleado['empresa_id'])) {
                try {
                    $stmt_emp_logo = $pdo->prepare("SELECT logo_key FROM empresas WHERE id = ?");
                    $stmt_emp_logo->execute([$empleado['empresa_id']]);
                    $logo_key_val = $stmt_emp_logo->fetchColumn() ?? '';
                } catch (Exception $e) {
                }
            }

            if ($logo_key_val) {
                try {
                    $stmt_logo = $pdo->prepare("SELECT valor FROM configuracion_sistema WHERE clave = ?");
                    $stmt_logo->execute([$logo_key_val]);
                    $logo_url = $stmt_logo->fetchColumn();
                } catch (Exception $e) {
                }
            }
            ?>

            <?php if ($logo_url): ?>
                <div class="h-20 w-auto min-w-[5rem] flex items-center justify-center">
                    <img src="<?= htmlspecialchars($logo_url) ?>" class="h-full w-auto object-contain max-w-[12rem]"
                        alt="Logo Empresa">
                </div>
            <?php else: ?>
                <div
                    class="w-16 h-16 bg-slate-800 text-white flex items-center justify-center rounded-lg font-bold text-2xl">
                    ST
                </div>
            <?php endif; ?>
            <div>
                <h1 class="text-2xl font-black text-slate-800 tracking-tight" contenteditable="true">ACTA DE DEVOLUCIÓN
                    Y AUDITORÍA</h1>
                <p class="text-rose-600 font-bold uppercase tracking-wider text-xs" contenteditable="true">
                    Baja de Personal / Devolución de Activos
                </p>
            </div>
        </div>
        <div class="text-right">
            <p class="text-slate-400 text-sm" contenteditable="true">Fecha de Emisión</p>
            <p class="text-lg font-bold text-slate-800" contenteditable="true">
                <?= date('d/m/Y') ?>
            </p>
            <p class="text-xs text-slate-400 mt-1" contenteditable="true">Hora:
                <?= date('H:i') ?>
            </p>
        </div>
    </div>

    <!-- Datos del Colaborador -->
    <div class="mb-4">
        <h3 class="text-sm font-bold text-slate-400 uppercase tracking-wider mb-2 border-b border-slate-100 pb-1"
            contenteditable="true">Datos del Colaborador Saliente</h3>
        <div class="grid grid-cols-2 gap-y-3 gap-x-12 text-sm">
            <!-- Fila 1 -->
            <div class="grid grid-cols-[130px_1fr] border-b border-slate-100 border-dashed pb-1 items-baseline">
                <span class="font-semibold text-slate-600" contenteditable="true">Nombre Completo:</span>
                <span class="font-bold text-slate-900" contenteditable="true">
                    <?= htmlspecialchars($nombre_completo) ?>
                </span>
            </div>
            <div class="grid grid-cols-[130px_1fr] border-b border-slate-100 border-dashed pb-1 items-baseline">
                <span class="font-semibold text-slate-600" contenteditable="true">Cédula / ID:</span>
                <span class="font-bold text-slate-900 text-right" contenteditable="true">
                    <?= htmlspecialchars($empleado['cedula'] ?? 'N/A') ?>
                </span>
            </div>

            <!-- Fila 2 -->
            <div class="grid grid-cols-[130px_1fr] border-b border-slate-100 border-dashed pb-1 items-baseline">
                <span class="font-semibold text-slate-600" contenteditable="true">Cargo:</span>
                <span class="font-bold text-slate-900" contenteditable="true">
                    <?= htmlspecialchars($empleado['cargo'] ?? 'N/A') ?>
                </span>
            </div>
            <div class="grid grid-cols-[130px_1fr] border-b border-slate-100 border-dashed pb-1 items-baseline">
                <span class="font-semibold text-slate-600" contenteditable="true">Sucursal:</span>
                <span class="font-bold text-slate-900 text-right" contenteditable="true">
                    <?= htmlspecialchars($empleado['sucursal_nombre'] ?? 'N/A') ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Tabla de Equipos y Auditoría Físico -->
    <div class="mb-4 flex-grow">
        <h3 class="text-sm font-bold text-slate-400 uppercase tracking-wider border-b border-slate-100 pb-2 mb-2"
            contenteditable="true">
            Revisión Física de Equipos Devueltos
        </h3>
        <table class="w-full text-sm text-left border-collapse" id="tabla-equipos">
            <thead>
                <tr class="bg-slate-100 text-slate-600 uppercase text-xs">
                    <th class="px-3 py-2 font-bold border-b-2 border-slate-300 w-1/4" contenteditable="true">Equipo /
                        Modelo</th>
                    <th class="px-3 py-2 font-bold border-b-2 border-slate-300" contenteditable="true">Serial / SKU</th>
                    <th class="px-3 py-2 font-bold border-b-2 border-slate-300 text-center" contenteditable="true">
                        Físico OK</th>
                    <th class="px-3 py-2 font-bold border-b-2 border-slate-300 text-center" contenteditable="true">
                        Accesorios OK</th>
                    <th class="px-3 py-2 font-bold border-b-2 border-slate-300 w-1/3" contenteditable="true">
                        Observaciones T.I.</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                <?php if (empty($activos_asignados)): ?>
                    <tr class="fila-vacia">
                        <td colspan="5" class="py-4 text-center text-slate-400 italic" contenteditable="true">
                            No se encontraron activos a auditar.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($activos_asignados as $activo): ?>
                        <tr class="hover:bg-slate-50 group">
                            <td class="px-3 py-4 text-slate-700 font-medium" contenteditable="true">
                                <span class="block font-bold">
                                    <?= htmlspecialchars($activo['tipo']) ?>
                                </span>
                                <span class="text-xs text-slate-500">
                                    <?= htmlspecialchars($activo['marca'] . ' ' . $activo['modelo']) ?>
                                </span>
                            </td>
                            <td class="px-3 py-4 font-mono text-slate-600 text-xs" contenteditable="true">
                                S/N:
                                <?= htmlspecialchars($activo['serial']) ?><br>
                                SKU:
                                <?= htmlspecialchars($activo['sku']) ?>
                            </td>
                            <td class="px-3 py-4 text-center align-middle">
                                <div class="w-5 h-5 border-2 border-slate-400 rounded-sm inline-block mx-auto cursor-pointer"
                                    onclick="this.classList.toggle('bg-slate-800'); this.classList.toggle('border-slate-800')">
                                </div>
                            </td>
                            <td class="px-3 py-4 text-center align-middle">
                                <div class="w-5 h-5 border-2 border-slate-400 rounded-sm inline-block mx-auto cursor-pointer"
                                    onclick="this.classList.toggle('bg-slate-800'); this.classList.toggle('border-slate-800')">
                                </div>
                            </td>
                            <td class="px-3 py-2 text-slate-700 italic border-l border-slate-100" contenteditable="true">

                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Cláusulas de Auditoría -->
    <div class="mb-6 bg-rose-50 p-4 rounded-xl border border-rose-200 text-justify text-xs text-rose-900 leading-relaxed"
        contenteditable="true">
        <p class="mb-2 font-bold text-rose-800">DECLARACIÓN DE AUDITORÍA DE SALIDA:</p>
        <p class="mb-1">
            1. El departamento de T.I. hace constar la recepción física de los activos arriba enumerados por motivo del
            cese de funciones del colaborador.
        </p>
        <p class="mb-1">
            2. La firma de este documento certifica únicamente la devolución física del Hardware a nuestras
            instalaciones. No supone la liberación de responsabilidades en caso de daños ocultos u omisiones
            informáticas detectadas en rutinas de diagnóstico posteriores.
        </p>
        <p>
            3. Toda anomalía o discrepancia hallada al momento de la entrega ha sido documentada en la columna de
            observaciones. El costo de partes faltantes será notificado y trasladado al área correspondiente.
        </p>
    </div>

    <!-- Firmas de Auditoría -->
    <div class="grid grid-cols-2 gap-16 mt-auto" style="page-break-inside: avoid;">
        <div class="text-center">
            <div class="border-t border-black pt-4">
                <p class="font-bold text-slate-900 text-sm mb-1" contenteditable="true">
                    <?= htmlspecialchars($nombre_completo) ?>
                </p>
                <p class="text-xs text-slate-500 uppercase font-semibold" contenteditable="true">Entregué Conforme
                    (Colaborador Saliente)</p>
            </div>
        </div>

        <div class="text-center relative">
            <div
                class="absolute -top-12 left-1/2 -translate-x-1/2 w-32 h-20 flex items-center justify-center opacity-10 rotate-[-5deg] pointer-events-none">
                <!-- Espacio para sello TI -->
            </div>
            <div class="border-t border-slate-900 pt-4">
                <p class="font-bold text-slate-900 text-sm mb-1" contenteditable="true">TÉCNICO / AUDITOR T.I.</p>
                <p class="text-xs text-slate-500 uppercase font-semibold" contenteditable="true">Recibí Conforme (Firma
                    y Sello)</p>
            </div>
        </div>
    </div>
</div>

<!-- Botonera No Imprimible -->
<div class="max-w-[21cm] mx-auto mt-8 text-center no-print pb-12">
    <button onclick="handlePrint()"
        class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-full font-bold shadow-lg shadow-blue-600/30 transition-all flex items-center gap-2 mx-auto">
        <i class="ri-printer-line text-xl"></i> Imprimir / Guardar PDF
    </button>
    <p class="mt-4">
        <button onclick="window.close()" class="text-slate-500 hover:text-slate-700 text-sm underline cursor-pointer">
            Cerrar Pestaña
        </button>
    </p>
</div>

<!-- Script de Gestión de Impresión -->
<script>
    function handlePrint() {
        const container = document.querySelector('.print-container');
        document.body.appendChild(container);
        const cleanup = () => {
            container.scrollIntoView({ behavior: "auto", block: "center" });
        };
        window.addEventListener('afterprint', cleanup, { once: true });
        window.print();
    }
</script>