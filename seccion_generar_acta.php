<?php
/**
 * seccion_generar_acta.php - Generar Acta de Entrega
 * Vista imprimible para asignación de equipos
 */

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "ID no especificado.";
    return;
}

$personal_id = $_GET['id'];

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

    // 2. Obtener Activos Asignados
    // Buscamos por ID de usuario en 'asignado_a'
    $stmtActivos = $pdo->prepare("
        SELECT * FROM inventario 
        WHERE condicion = 'Asignado' 
        AND asignado_a = ?
        ORDER BY tipo, marca
    ");
    $stmtActivos->execute([$personal_id]);
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
            /* Al poner 0, el navegador oculta encabezados y pies de página automáticos */
        }

        /* Ocultar TODO por defecto */
        body>* {
            display: none !important;
        }

        /* Mostrar solo el contenedor de impresión */
        body>.print-container {
            display: block !important;
            position: relative !important;
            top: 0 !important;
            left: 0 !important;
            width: 100% !important;
            height: auto !important;

            /* Compensamos el margen 0 del @page con padding interno */
            padding: 1.5cm 2cm !important;
            margin: 0 !important;

            background: white;
            box-sizing: border-box !important;
            /* Para que el padding no rompa el ancho */
        }

        /* Asegurar reset completo de estilos básicos para impresión */
        html,
        body {
            width: 100%;
            margin: 0 !important;
            padding: 0 !important;
            background: white;
        }

        /* Ocultar elementos marcados como no-print explícitamente */
        .no-print {
            display: none !important;
        }

        /* Forzar colores */
        * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
    }
</style>

<!-- Aviso de Edición -->
<div class="max-w-[21cm] mx-auto mb-4 bg-blue-50 border-l-4 border-blue-500 p-4 rounded shadow-sm no-print">
    <div class="flex items-center justify-between">
        <div class="flex items-center">
            <i class="ri-edit-circle-line text-2xl text-blue-600 mr-4"></i>
            <div>
                <h3 class="font-bold text-blue-800">Documento Editable</h3>
                <p class="text-sm text-blue-700">Puedes hacer clic en cualquier texto para editarlo antes de imprimir.
                </p>
            </div>
        </div>
    </div>
</div>

<div
    class="print-container bg-white p-8 max-w-[21cm] mx-auto shadow-xl rounded-xl my-8 border border-slate-100 font-sans text-slate-900 flex flex-col min-h-[29.7cm]">

    <!-- Script para sacar el contenedor del DOM anidado y ponerlo en el root para imprimir sin herencias -->
    <!-- (Eliminado: Ahora se gestiona dinámicamente al hacer click en imprimir) -->

    <!-- Encabezado del Acta -->
    <div class="flex justify-between items-start mb-4 border-b-2 border-slate-800 pb-4">
        <div class="flex items-center gap-4">
            <!-- Logo Dinámico -->
            <?php
            $logo_url = '';
            $logo_key = '';

            // 1. Obtener contexto del USUARIO ACTUAL (RRHH) para forzar el logo
            // Esto permite que si RRHH cambia de empresa, el acta salga con ese logo
            $empresa_usuario_str = '';
            if (isset($_SESSION['usuario_id'])) {
                try {
                    $stmt_u = $pdo->prepare("SELECT empresa_asignada FROM usuarios WHERE id = ?");
                    $stmt_u->execute([$_SESSION['usuario_id']]);
                    $empresa_usuario_str = $stmt_u->fetchColumn();
                } catch (Exception $e) {
                }
            }

            // 2. Determinar Key (Prioridad: Usuario > Empleado)
            if ($empresa_usuario_str) {
                switch (strtolower($empresa_usuario_str)) {
                    case 'mastertec':
                        $logo_key = 'logo_mastertec';
                        break;
                    case 'suministros':
                        $logo_key = 'logo_master_suministros';
                        break;
                    case 'centro':
                        $logo_key = 'logo_centro';
                        break;
                }
            }

            // Si no hay empresa de usuario (o no coincide), usar la del empleado como fallback
            if (!$logo_key) {
                $empresa_id = $empleado['empresa_id'] ?? null;
                if ($empresa_id) {
                    switch ($empresa_id) {
                        case 1:
                            $logo_key = 'logo_mastertec';
                            break;
                        case 3:
                            $logo_key = 'logo_master_suministros';
                            break;
                        case 4:
                            $logo_key = 'logo_centro';
                            break;
                    }
                }
            }

            if ($logo_key) {
                try {
                    $stmt_logo = $pdo->prepare("SELECT valor FROM configuracion_sistema WHERE clave = ?");
                    $stmt_logo->execute([$logo_key]);
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
                <!-- Logo Placeholder (Círculo si no hay imagen) -->
                <div
                    class="w-16 h-16 bg-slate-800 text-white flex items-center justify-center rounded-lg font-bold text-2xl">
                    ST
                </div>
            <?php endif; ?>
            <div>
                <h1 class="text-3xl font-black text-slate-800 tracking-tight" contenteditable="true">ACTA DE ENTREGA
                </h1>
                <p class="text-slate-500 font-medium uppercase tracking-wider text-xs" contenteditable="true">
                    Responsabilidad de Activos Fijos
                </p>
            </div>
        </div>
        <div class="text-right">
            <p class="text-slate-400 text-sm" contenteditable="true">Fecha de Emisión</p>
            <p class="text-lg font-bold text-slate-800" contenteditable="true"><?= date('d/m/Y') ?></p>
            <p class="text-xs text-slate-400 mt-1" contenteditable="true">Hora: <?= date('H:i') ?></p>
        </div>
    </div>

    <!-- Datos del Colaborador -->
    <div class="mb-4">
        <h3 class="text-sm font-bold text-slate-400 uppercase tracking-wider mb-2 border-b border-slate-100 pb-1"
            contenteditable="true">Datos del Colaborador</h3>
        <div class="grid grid-cols-2 gap-y-3 gap-x-12 text-sm">
            <!-- Fila 1 -->
            <div class="grid grid-cols-[130px_1fr] border-b border-slate-100 border-dashed pb-1 items-baseline">
                <span class="font-semibold text-slate-600" contenteditable="true">Nombre Completo:</span>
                <span class="font-bold text-slate-900"
                    contenteditable="true"><?= htmlspecialchars($nombre_completo) ?></span>
            </div>
            <div class="grid grid-cols-[130px_1fr] border-b border-slate-100 border-dashed pb-1 items-baseline">
                <span class="font-semibold text-slate-600" contenteditable="true">Cédula / ID:</span>
                <span class="font-bold text-slate-900 text-right"
                    contenteditable="true"><?= htmlspecialchars($empleado['cedula']) ?></span>
            </div>

            <!-- Fila 2 -->
            <div class="grid grid-cols-[130px_1fr] border-b border-slate-100 border-dashed pb-1 items-baseline">
                <span class="font-semibold text-slate-600" contenteditable="true">Cargo:</span>
                <span class="font-bold text-slate-900"
                    contenteditable="true"><?= htmlspecialchars($empleado['cargo']) ?></span>
            </div>
            <div class="grid grid-cols-[130px_1fr] border-b border-slate-100 border-dashed pb-1 items-baseline">
                <span class="font-semibold text-slate-600" contenteditable="true">Departamento:</span>
                <span class="font-bold text-slate-900 text-right"
                    contenteditable="true"><?= htmlspecialchars($empleado['departamento']) ?></span>
            </div>

            <!-- Fila 3 -->
            <div class="grid grid-cols-[130px_1fr] border-b border-slate-100 border-dashed pb-1 items-baseline">
                <span class="font-semibold text-slate-600" contenteditable="true">Sucursal:</span>
                <span class="font-bold text-slate-900"
                    contenteditable="true"><?= htmlspecialchars($empleado['sucursal_nombre']) ?></span>
            </div>
            <div class="grid grid-cols-[130px_1fr] border-b border-slate-100 border-dashed pb-1 items-baseline">
                <span class="font-semibold text-slate-600" contenteditable="true">Ciudad:</span>
                <span class="font-bold text-slate-900 text-right"
                    contenteditable="true"><?= htmlspecialchars($empleado['sucursal_ciudad'] ?? 'N/A') ?></span>
            </div>
        </div>
    </div>

    <!-- Tabla de Equipos -->
    <div class="mb-4">
        <div class="flex items-center justify-between mb-2">
            <h3 class="text-sm font-bold text-slate-400 uppercase tracking-wider border-b border-slate-100 pb-2 flex-1"
                contenteditable="true">
                Equipos Asignados</h3>
            <button onclick="agregarFila()"
                class="no-print text-xs bg-emerald-50 text-emerald-600 hover:bg-emerald-100 px-2 py-1 rounded border border-emerald-200 transition-colors flex items-center gap-1">
                <i class="ri-add-line"></i> Agregar Fila Manual
            </button>
        </div>
        <table class="w-full text-sm text-left border-collapse" id="tabla-equipos">
            <thead>
                <tr class="bg-slate-100 text-slate-600 uppercase text-xs">
                    <th class="px-3 py-2 font-bold border-b-2 border-slate-300" contenteditable="true">Tipo</th>
                    <th class="px-3 py-2 font-bold border-b-2 border-slate-300" contenteditable="true">Marca / Modelo
                    </th>
                    <th class="px-3 py-2 font-bold border-b-2 border-slate-300" contenteditable="true">Serial</th>
                    <th class="px-3 py-2 font-bold border-b-2 border-slate-300 w-24" contenteditable="true">SKU</th>
                    <th class="px-3 py-2 font-bold border-b-2 border-slate-300 w-24 text-center" contenteditable="true">
                        Estado</th>
                    <th class="px-1 py-2 w-8 no-print border-b-2 border-slate-300"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200" id="tbody-equipos">
                <?php if (empty($activos_asignados)): ?>
                    <tr class="fila-vacia">
                        <td colspan="6" class="py-4 text-center text-slate-400 italic" contenteditable="true">
                            No se encontraron activos asignados a este colaborador en el sistema.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($activos_asignados as $activo): ?>
                        <tr class="hover:bg-slate-50 group">
                            <td class="px-3 py-2 text-slate-700 font-medium" contenteditable="true">
                                <?= htmlspecialchars($activo['tipo']) ?>
                            </td>
                            <td class="px-3 py-2 text-slate-700" contenteditable="true">
                                <?= htmlspecialchars($activo['marca'] . ' ' . $activo['modelo']) ?>
                            </td>
                            <td class="px-3 py-2 font-mono text-slate-600 text-xs" contenteditable="true">
                                <?= htmlspecialchars($activo['serial']) ?>
                            </td>
                            <td class="px-3 py-2 text-slate-600 text-xs" contenteditable="true">
                                <?= htmlspecialchars($activo['sku']) ?>
                            </td>
                            <td class="px-3 py-2 text-center" contenteditable="true">
                                <span class="px-2 py-1 rounded text-xs font-bold border
                                <?= match ($activo['estado']) {
                                    'Nuevo' => 'border-emerald-200 text-emerald-700',
                                    'Bueno' => 'border-blue-200 text-blue-700',
                                    'Regular' => 'border-amber-200 text-amber-700',
                                    default => 'border-slate-200 text-slate-600'
                                } ?>">
                                    <?= $activo['estado'] ?>
                                </span>
                            </td>
                            <td class="px-1 py-2 text-center no-print opacity-0 group-hover:opacity-100 transition-opacity">
                                <button onclick="eliminarFila(this)" class="text-red-400 hover:text-red-600"
                                    title="Eliminar fila temporalmente">
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Cláusulas -->
    <div class="mb-6 bg-slate-50 p-3 rounded-xl border border-slate-200 text-justify text-xs text-slate-600 leading-relaxed"
        contenteditable="true">
        <p class="mb-2 font-bold text-slate-700">TÉRMINOS Y CONDICIONES:</p>
        <p class="mb-1">
            1. El colaborador confirma haber recibido los equipos y herramientas de trabajo detallados en el presente
            documento, declarando que se encuentran en el estado descrito.
        </p>
        <p class="mb-1">
            2. El colaborador se compromete a utilizar los equipos única y exclusivamente para el desempeño de sus
            funciones laborales, cuidándolos con la debida diligencia y evitando su uso indebido.
        </p>
        <p class="mb-1">
            3. En caso de pérdida, robo o daño por negligencia comprobada, el colaborador deberá notificar
            inmediatamente a la empresa y seguir los procedimientos establecidos.
        </p>
        <p>
            4. A la terminación de la relación laboral, el colaborador deberá devolver todos los equipos asignados.
        </p>
    </div>

    <!-- Firmas -->
    <div class="grid grid-cols-2 gap-20 mt-auto" style="page-break-inside: avoid;">
        <div class="text-center relative">
            <div
                class="absolute -top-12 left-1/2 -translate-x-1/2 w-32 h-20 flex items-center justify-center opacity-10 rotate-[-5deg] pointer-events-none">
                <!-- Espacio para sello -->
            </div>
            <div class="border-t border-black pt-4">
                <p class="font-bold text-slate-900 text-sm mb-1" contenteditable="true">
                    <?= htmlspecialchars($nombre_completo) ?>
                </p>
                <p class="text-xs text-slate-500 uppercase font-semibold" contenteditable="true">Recibí Conforme
                    (Colaborador)</p>
                <p class="text-xs text-slate-400 mt-1" contenteditable="true">
                    <?= htmlspecialchars($empleado['cedula']) ?>
                </p>
            </div>
        </div>

        <div class="text-center">
            <div class="border-t border-slate-900 pt-2">
                <p class="font-bold text-slate-900 text-sm mb-1" contenteditable="true">DEPARTAMENTO T.I. / RR.HH.</p>
                <p class="text-xs text-slate-500 uppercase font-semibold" contenteditable="true">Entregado Por (Firma y
                    Sello)</p>
            </div>
        </div>
    </div>

    <script>
        function agregarFila() {
            const tbody = document.getElementById('tbody-equipos');
            // Eliminar fila de "vacío" si existe
            const vacia = tbody.querySelector('.fila-vacia');
            if (vacia) vacia.remove();

            const tr = document.createElement('tr');
            tr.className = 'hover:bg-slate-50 group';
            tr.innerHTML = `
            <td class="px-3 py-3 text-slate-700 font-medium" contenteditable="true">Dispositivo</td>
            <td class="px-3 py-3 text-slate-700" contenteditable="true">-</td>
            <td class="px-3 py-3 font-mono text-slate-600 text-xs" contenteditable="true">-</td>
            <td class="px-3 py-3 text-slate-600 text-xs" contenteditable="true">-</td>
            <td class="px-3 py-3 text-center" contenteditable="true">Bueno</td>
            <td class="px-1 py-3 text-center no-print opacity-0 group-hover:opacity-100">
                <button onclick="eliminarFila(this)" class="text-red-400 hover:text-red-600"><i class="ri-delete-bin-line"></i></button>
            </td>
        `;
            tbody.appendChild(tr);
        }

        function eliminarFila(btn) {
            if (confirm('¿Eliminar esta fila?')) {
                btn.closest('tr').remove();
                const tbody = document.getElementById('tbody-equipos');
                if (tbody.children.length === 0) {
                    const tr = document.createElement('tr');
                    tr.className = 'fila-vacia';
                    tr.innerHTML = `
                    <td colspan="6" class="py-8 text-center text-slate-400 italic" contenteditable="true">
                        No se encontraron activos asignados a este colaborador en el sistema.
                    </td>
                `;
                    tbody.appendChild(tr);
                }
            }
        }
    </script>

</div>

<!-- Botonera No Imprimible (Fuera del contenedor de papel) -->
<div class="max-w-[21cm] mx-auto mt-8 text-center no-print pb-12">
    <button onclick="handlePrint()"
        class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-full font-bold shadow-lg shadow-blue-600/30 transition-all flex items-center gap-2 mx-auto">
        <i class="ri-printer-line text-xl"></i> Imprimir Documento
    </button>
    <p class="mt-4">
        <a href="index.php?view=personal_detalle&id=<?= $personal_id ?>"
            class="text-slate-500 hover:text-slate-700 text-sm underline">
            Volver al detalle
        </a>
    </p>
</div>

<!-- Script de Gestión de Impresión -->
<script>
    function handlePrint() {
        const container = document.querySelector('.print-container');
        const originalParent = container.parentElement;
        const nextSibling = container.nextElementSibling;

        // Mover al body para imprimir limpio (sin herencias de layout)
        document.body.appendChild(container);

        // Escuchar el evento afterprint para restaurar
        // Esto asegura que al cancelar o terminar, el layout vuelva a la normalidad
        const cleanup = () => {
            if (originalParent) {
                if (nextSibling) {
                    originalParent.insertBefore(container, nextSibling);
                } else {
                    originalParent.appendChild(container);
                }
            }
            // Scroll al principio para evitar saltos raros
            container.scrollIntoView({ behavior: "auto", block: "center" });
        };

        window.addEventListener('afterprint', cleanup, { once: true });

        // Para navegadores donde afterprint pueda fallar o ser inconsistente, 
        // un setTimeout es un fallback, pero print() suele ser bloqueante en UI.
        // Dejamos solo evento standard.

        window.print();
    }
</script>