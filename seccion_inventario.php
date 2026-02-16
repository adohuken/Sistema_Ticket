<?php
/**
 * seccion_inventario.php - Visualización de Inventario de Activos
 */

// Lógica de eliminación eliminada (movida a index.php)

// Consultas para KPIs
$total_activos = $pdo->query("SELECT COUNT(*) FROM inventario")->fetchColumn();
$disponibles = $pdo->query("SELECT COUNT(*) FROM inventario WHERE condicion = 'Disponible'")->fetchColumn();
$asignados = $pdo->query("SELECT COUNT(*) FROM inventario WHERE condicion = 'Asignado'")->fetchColumn();

// Consulta listado con JOIN para obtener nombre del empleado
// Consulta listado con JOIN para obtener nombre del empleado
$filtro_condicion = $_GET['condicion'] ?? '';
$sql_inv = "SELECT i.id as id_activo_real, i.*, 
            CONCAT(p.nombres, ' ', p.apellidos) as nombre_asignado 
            FROM inventario i 
            LEFT JOIN personal p ON i.asignado_a = p.id ";

$params = [];
if ($filtro_condicion && in_array($filtro_condicion, ['Disponible', 'Asignado'])) {
    $sql_inv .= " WHERE i.condicion = ?";
    $params[] = $filtro_condicion;
}
$sql_inv .= " ORDER BY i.id DESC";

$stmt = $pdo->prepare($sql_inv);
$stmt->execute($params);
$activos = $stmt->fetchAll();

// Capturar mensajes de sesión
$mensaje_exito = $_SESSION['mensaje_exito'] ?? null;
$mensaje_error = $_SESSION['mensaje_error'] ?? null;
unset($_SESSION['mensaje_exito'], $_SESSION['mensaje_error']);
?>

<div class="p-6 flex-1 glass min-h-screen">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-bold text-slate-800 flex items-center gap-3">
                <span class="bg-blue-600 text-white p-2 rounded-xl shadow-lg shadow-blue-500/30">
                    <i class="ri-archive-line"></i>
                </span>
                Inventario de Activos
            </h1>
            <p class="text-slate-500 mt-1 ml-1">Consulta y gestión de equipos informáticos y mobiliario</p>
        </div>
        <div class="flex gap-4">
            <a href="index.php?view=importar_inventario"
                class="bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-3 rounded-xl shadow-lg transition-all flex items-center gap-2 font-semibold">
                <i class="ri-file-upload-line text-xl"></i> Importar Masivo
            </a>
            <a href="index.php?view=asignacion_equipo"
                class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl shadow-lg transition-all flex items-center gap-2 font-semibold">
                <i class="ri-add-circle-line text-xl"></i> Registrar Nuevo Activo
            </a>

        </div>
    </div>

    <!-- Mensaje de Éxito -->
    <?php if ($mensaje_exito): ?>
        <div class="mb-6 bg-emerald-50 border-2 border-emerald-200 rounded-xl p-4 flex items-start gap-3 animate-fade-in">
            <i class="ri-checkbox-circle-line text-emerald-600 text-2xl flex-shrink-0"></i>
            <div class="flex-1">
                <p class="font-bold text-emerald-800">¡Registro Exitoso!</p>
                <p class="text-sm text-emerald-700"><?= htmlspecialchars($mensaje_exito) ?></p>
            </div>
            <button onclick="this.parentElement.remove()" class="text-emerald-400 hover:text-emerald-600">
                <i class="ri-close-line text-xl"></i>
            </button>
        </div>
    <?php endif; ?>

    <!-- Mensaje de Error -->
    <?php if ($mensaje_error): ?>
        <div class="mb-6 bg-red-50 border-2 border-red-200 rounded-xl p-4 flex items-start gap-3">
            <i class="ri-error-warning-line text-red-600 text-2xl flex-shrink-0"></i>
            <div class="flex-1">
                <p class="font-bold text-red-800">Error</p>
                <p class="text-sm text-red-700"><?= htmlspecialchars($mensaje_error) ?></p>
            </div>
            <button onclick="this.parentElement.remove()" class="text-red-400 hover:text-red-600">
                <i class="ri-close-line text-xl"></i>
            </button>
        </div>
    <?php endif; ?>

    <!-- KPIs -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <!-- Total -->
        <a href="index.php?view=inventario" class="block group">
            <div
                class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 flex items-center justify-between transition-all hover:shadow-md hover:border-blue-200 cursor-pointer relative overflow-hidden">
                <div
                    class="absolute top-0 right-0 w-16 h-16 bg-blue-50 rounded-bl-full -mr-8 -mt-8 transition-transform group-hover:scale-110">
                </div>
                <div class="relative z-10">
                    <p
                        class="text-slate-500 text-sm font-semibold uppercase tracking-wider group-hover:text-blue-600 transition-colors">
                        Total Activos</p>
                    <h3 class="text-3xl font-bold text-slate-800 mt-1"><?= $total_activos ?></h3>
                </div>
                <div
                    class="bg-blue-50 text-blue-600 p-4 rounded-xl relative z-10 group-hover:scale-110 transition-transform">
                    <i class="ri-archive-line text-2xl"></i>
                </div>
            </div>
        </a>

        <!-- Disponibles -->
        <a href="index.php?view=inventario&condicion=Disponible" class="block group">
            <div
                class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 flex items-center justify-between transition-all hover:shadow-md hover:border-emerald-200 cursor-pointer relative overflow-hidden">
                <div
                    class="absolute top-0 right-0 w-16 h-16 bg-emerald-50 rounded-bl-full -mr-8 -mt-8 transition-transform group-hover:scale-110">
                </div>
                <div class="relative z-10">
                    <p
                        class="text-slate-500 text-sm font-semibold uppercase tracking-wider group-hover:text-emerald-600 transition-colors">
                        Disponibles</p>
                    <h3 class="text-3xl font-bold text-emerald-600 mt-1"><?= $disponibles ?></h3>
                </div>
                <div
                    class="bg-emerald-50 text-emerald-600 p-4 rounded-xl relative z-10 group-hover:scale-110 transition-transform">
                    <i class="ri-check-double-line text-2xl"></i>
                </div>
            </div>
        </a>

        <!-- Asignados -->
        <a href="index.php?view=inventario&condicion=Asignado" class="block group">
            <div
                class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 flex items-center justify-between transition-all hover:shadow-md hover:border-amber-200 cursor-pointer relative overflow-hidden">
                <div
                    class="absolute top-0 right-0 w-16 h-16 bg-amber-50 rounded-bl-full -mr-8 -mt-8 transition-transform group-hover:scale-110">
                </div>
                <div class="relative z-10">
                    <p
                        class="text-slate-500 text-sm font-semibold uppercase tracking-wider group-hover:text-amber-600 transition-colors">
                        Asignados</p>
                    <h3 class="text-3xl font-bold text-amber-600 mt-1"><?= $asignados ?></h3>
                </div>
                <div
                    class="bg-amber-50 text-amber-600 p-4 rounded-xl relative z-10 group-hover:scale-110 transition-transform">
                    <i class="ri-user-shared-line text-2xl"></i>
                </div>
            </div>
        </a>
    </div>

    <!-- Tabla Inventario -->
    <div class="bg-white rounded-2xl shadow-xl shadow-slate-200/50 border border-slate-100 overflow-hidden">
        <div
            class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex flex-col md:flex-row justify-between items-center gap-4">
            <h3 class="font-bold text-slate-700">Listado General</h3>
            <div class="relative w-full md:w-auto">
                <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                <input type="text" id="buscadorInventario" onkeyup="filtrarInventario()"
                    placeholder="Buscar por SKU, Serial, Marca, Modelo..."
                    class="w-full md:w-80 pl-10 pr-4 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none transition-shadow">
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left" id="tablaInventario">
                <thead <thead
                    class="bg-slate-50 text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-100">
                    <tr>
                        <th class="px-6 py-4 text-left w-[10%]">SKU</th>
                        <th class="px-6 py-4 text-left w-[30%]">Equipo</th>
                        <th class="px-6 py-4 text-left w-[15%]">Serial</th>
                        <th class="px-6 py-4 text-left w-[10%]">Estado</th>
                        <th class="px-6 py-4 text-center w-[10%]">Estatus</th>
                        <th class="px-6 py-4 text-left w-[15%]">Asignado A</th>
                        <th class="px-6 py-4 text-center w-[10%]">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($activos as $a):
                        // DEBUG: Mostrar el tipo exacto
                        if (stripos($a['marca'], 'scan') !== false || stripos($a['modelo'], 'scan') !== false) {
                            error_log("DEBUG - Tipo: '" . $a['tipo'] . "' | Marca: " . $a['marca'] . " | Modelo: " . $a['modelo']);
                        }

                        $icon_bg = match ($a['tipo']) {
                            'Laptop' => 'bg-blue-100 text-blue-600',
                            'PC' => 'bg-indigo-100 text-indigo-600',
                            'Movil' => 'bg-purple-100 text-purple-600',
                            'Monitor' => 'bg-cyan-100 text-cyan-600',
                            'Silla' => 'bg-orange-100 text-orange-600',
                            'Escritorio' => 'bg-amber-100 text-amber-600',
                            'Impresora' => 'bg-green-100 text-green-600',
                            'Teclado' => 'bg-slate-100 text-slate-600',
                            'Mouse' => 'bg-gray-100 text-gray-600',
                            'Headset' => 'bg-pink-100 text-pink-600',
                            'Otro' => 'bg-slate-100 text-slate-600',
                            default => 'bg-slate-100 text-slate-600'
                        };

                        $icon = match ($a['tipo']) {
                            'Laptop' => 'ri-macbook-line',
                            'PC' => 'ri-computer-line',
                            'Movil' => 'ri-smartphone-line',
                            'Monitor' => 'ri-tv-line',
                            'Silla' => 'ri-armchair-line',
                            'Escritorio' => 'ri-layout-line',
                            'Impresora' => 'ri-printer-line',
                            'Teclado' => 'ri-keyboard-line',
                            'Mouse' => 'ri-mouse-line',
                            'Headset' => 'ri-headphone-line',
                            'Otro' => 'ri-box-3-line',
                            default => 'ri-box-3-line'
                        };
                        $status_class = $a['condicion'] === 'Disponible' ? 'bg-emerald-100 text-emerald-700 border-emerald-200' :
                            ($a['condicion'] === 'Asignado' ? 'bg-blue-100 text-blue-700 border-blue-200' : 'bg-red-100 text-red-700');

                        $sku_display = !empty($a['sku']) ? htmlspecialchars($a['sku']) : '<span class="text-slate-300 italic">N/A</span>';
                        ?>
                        <tr onclick='verDetallesActivo(<?= json_encode($a, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
                            class="hover:bg-slate-50 transition-colors fila-inventario border-b border-slate-100 last:border-0 align-middle cursor-pointer group">
                            <td class="px-6 py-4 text-sm font-mono font-semibold text-slate-700">
                                <?= $sku_display ?>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-10 h-10 rounded-lg <?= $icon_bg ?> flex items-center justify-center text-xl shadow-sm shrink-0">
                                        <i class="<?= $icon ?>"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <span
                                            class="font-bold text-slate-800 group-hover:text-blue-600 transition-colors truncate w-full block">
                                            <?= htmlspecialchars($a['marca'] . ' ' . $a['modelo']) ?>
                                        </span>
                                        <div class="text-xs text-slate-500 font-medium"><?= $a['tipo'] ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm font-mono text-slate-600 truncate">
                                <?= htmlspecialchars($a['serial']) ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-600">
                                <?= !empty($a['estado']) ? $a['estado'] : '<span class="text-slate-300">-</span>' ?>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span
                                    class="px-3 py-1 rounded-full text-xs font-bold border <?= $status_class ?> flex items-center justify-center gap-1 w-fit mx-auto">
                                    <span class="w-1.5 h-1.5 rounded-full bg-current"></span> <?= $a['condicion'] ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <?php if ($a['condicion'] == 'Asignado'): ?>
                                    <span class="text-sm font-medium text-slate-700 flex items-center gap-2 truncate">
                                        <i class="ri-user-line text-slate-400"></i>
                                        <span
                                            class="truncate"><?= htmlspecialchars($a['nombre_asignado'] ?? 'ID: ' . $a['asignado_a']) ?></span>
                                    </span>
                                <?php else: ?>
                                    <span class="text-xs text-slate-400 italic">-- Sin asignar --</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <!-- Botón Ver Detalles ELIMINADO -->

                                    <a href="index.php?view=editar_activo_inventario&id=<?= $a['id_activo_real'] ?>"
                                        onclick="event.stopPropagation()"
                                        class="text-blue-600 hover:text-blue-800 p-2 rounded-lg hover:bg-blue-50 transition-colors"
                                        title="Editar">
                                        <i class="ri-edit-line"></i>
                                    </a>
                                    <form id="formEliminar_<?= $a['id_activo_real'] ?>" method="POST" action="index.php"
                                        class="inline"
                                        onsubmit="event.stopPropagation(); confirmarEliminacion(event, <?= $a['id_activo_real'] ?>, '<?= htmlspecialchars($a['marca'] . ' ' . $a['modelo']) ?>', '<?= htmlspecialchars($a['serial']) ?>')">
                                        <input type="hidden" name="accion" value="eliminar_activo_inventario">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                        <input type="hidden" name="id" value="<?= $a['id_activo_real'] ?>">
                                        <button type="submit" onclick="event.stopPropagation()"
                                            class="text-red-600 hover:text-red-800 p-2 rounded-lg hover:bg-red-50 transition-colors"
                                            title="Eliminar">
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($activos)): ?>
                        <tr id="noResults">
                            <td colspan="7" class="px-6 py-8 text-center text-slate-400">No hay activos registrados.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- SweetAlert2 CDN -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    function verDetallesActivo(activo) {
        // Formatear valores nulos o vacíos
        const sku = activo.sku ? activo.sku : '<span class="italic text-slate-400">N/A</span>';

        // Mapeo de Iconos y Colores según Tipo
        const tipoMap = {
            'Laptop': { icon: 'ri-macbook-line', bg: 'bg-blue-100 text-blue-600', border: 'border-blue-200' },
            'PC': { icon: 'ri-computer-line', bg: 'bg-indigo-100 text-indigo-600', border: 'border-indigo-200' },
            'Movil': { icon: 'ri-smartphone-line', bg: 'bg-purple-100 text-purple-600', border: 'border-purple-200' },
            'Monitor': { icon: 'ri-tv-line', bg: 'bg-cyan-100 text-cyan-600', border: 'border-cyan-200' },
            'Silla': { icon: 'ri-armchair-line', bg: 'bg-orange-100 text-orange-600', border: 'border-orange-200' },
            'Escritorio': { icon: 'ri-layout-line', bg: 'bg-amber-100 text-amber-600', border: 'border-amber-200' },
            'Teclado': { icon: 'ri-keyboard-line', bg: 'bg-slate-100 text-slate-600', border: 'border-slate-200' },
            'Mouse': { icon: 'ri-mouse-line', bg: 'bg-gray-100 text-gray-600', border: 'border-gray-200' },
            'Headset': { icon: 'ri-headphone-line', bg: 'bg-pink-100 text-pink-600', border: 'border-pink-200' },
            'Impresora': { icon: 'ri-printer-line', bg: 'bg-green-100 text-green-600', border: 'border-green-200' },
            'Otro': { icon: 'ri-box-3-line', bg: 'bg-slate-100 text-slate-600', border: 'border-slate-200' }
        };

        const def = { icon: 'ri-box-3-line', bg: 'bg-slate-100 text-slate-600', border: 'border-slate-200' };
        const estilo = tipoMap[activo.tipo] || def;

        // Determinar Bloque de Asignación
        let asignadoBlock = '';
        if (activo.condicion === 'Asignado') {
            asignadoBlock = `
                <div class="p-3 rounded-lg border border-blue-200 bg-blue-50">
                    <div class="flex items-center gap-2 mb-1">
                        <i class="ri-user-star-fill text-blue-500"></i>
                        <span class="text-xs font-bold text-blue-600 uppercase">Asignado A</span>
                    </div>
                    <div class="text-blue-900 font-bold text-lg leading-tight">${activo.nombre_asignado || 'Usuario'}</div>
                </div>`;
        } else {
            asignadoBlock = `
                <div class="p-3 rounded-lg border border-dashed border-slate-300 bg-slate-50 flex flex-col justify-center">
                    <div class="flex items-center gap-2 mb-1 opacity-60">
                        <i class="ri-user-unfollow-line text-slate-500"></i>
                        <span class="text-xs font-bold text-slate-500 uppercase">Asignación</span>
                    </div>
                    <div class="text-slate-400 italic text-sm">Disponible para asignación</div>
                </div>`;
        }

        const badgeCondicion = activo.condicion === 'Disponible'
            ? `<span class="px-3 py-1 rounded-full bg-emerald-100 text-emerald-700 text-sm font-bold border border-emerald-200 flex items-center justify-center gap-1"><i class="ri-check-line"></i> Disponible</span>`
            : (activo.condicion === 'Asignado'
                ? `<span class="px-3 py-1 rounded-full bg-blue-100 text-blue-700 text-sm font-bold border border-blue-200 flex items-center justify-center gap-1"><i class="ri-user-check-line"></i> Asignado</span>`
                : `<span class="px-3 py-1 rounded-full bg-red-100 text-red-700 text-sm font-bold border border-red-200 flex items-center justify-center gap-1"><i class="ri-close-circle-line"></i> ${activo.condicion}</span>`);

        Swal.fire({
            title: `<div class="flex flex-col items-center gap-2 pt-2">
                        <div class="w-20 h-20 ${estilo.bg} rounded-2xl flex items-center justify-center shadow-sm border ${estilo.border} mb-2">
                            <i class="${estilo.icon} text-4xl"></i>
                        </div>
                        <h3 class="text-slate-800 text-xl font-bold">Ficha de Activo</h3>
                    </div>`,
            html: `
                <div class="text-left space-y-4 mt-2 px-1">
                    <!-- Cabecera Equipo -->
                    <div class="text-center pb-2 border-b border-slate-100">
                        <h2 class="text-2xl font-bold text-slate-800">${activo.marca} ${activo.modelo}</h2>
                        <div class="inline-block px-3 py-1 mt-1 bg-slate-100 text-slate-600 rounded-full text-xs font-bold uppercase tracking-wider">
                            ${activo.tipo}
                        </div>
                    </div>
                    
                    <!-- Grilla de Detalles -->
                    <div class="grid grid-cols-2 gap-3">
                        <!-- Serial -->
                        <div class="p-3 rounded-lg border border-slate-100 bg-white shadow-sm hover:shadow-md transition-shadow">
                            <div class="flex items-center gap-2 mb-1">
                                <i class="ri-barcode-line text-slate-400"></i>
                                <span class="text-xs font-bold text-slate-500 uppercase">Serial</span>
                            </div>
                            <div class="text-slate-800 font-mono text-sm font-semibold select-all truncate" title="${activo.serial}">${activo.serial}</div>
                        </div>
                        
                        <!-- SKU -->
                         <div class="p-3 rounded-lg border border-slate-100 bg-white shadow-sm hover:shadow-md transition-shadow">
                            <div class="flex items-center gap-2 mb-1">
                                <i class="ri-qr-code-line text-slate-400"></i>
                                <span class="text-xs font-bold text-slate-500 uppercase">SKU</span>
                            </div>
                            <div class="text-slate-800 font-mono text-sm font-semibold select-all">${sku}</div>
                        </div>

                         <!-- Estado Físico -->
                         <div class="p-3 rounded-lg border border-slate-100 bg-white shadow-sm hover:shadow-md transition-shadow">
                             <div class="flex items-center gap-2 mb-1">
                                <i class="ri-pulse-line text-slate-400"></i>
                                <span class="text-xs font-bold text-slate-500 uppercase">Estado</span>
                            </div>
                            <div class="text-slate-800 font-medium">${activo.estado}</div>
                        </div>

                         <!-- Estatus (Badge) -->
                         <div class="flex items-center justify-center">
                            ${badgeCondicion}
                        </div>
                    </div>
                    
                    <!-- Bloque de Asignación (Full Width) -->
                    ${asignadoBlock}

                    <!-- Fechas y Comentarios -->
                    <div class="pt-2 text-xs text-slate-400">
                        <span class="font-bold">Registrado:</span> ${activo.fecha_registro || 'N/A'}
                    </div>

                    ${activo.comentarios ? `
                    <div class="p-3 rounded-lg bg-amber-50 border border-amber-100 text-amber-800 text-sm">
                        <div class="flex items-center gap-2 mb-1 font-bold text-amber-900">
                            <i class="ri-sticky-note-line"></i> Notas:
                        </div>
                        ${activo.comentarios}
                    </div>` : ''}
                </div>
            `,
            showCloseButton: true,
            confirmButtonText: 'Cerrar',
            confirmButtonColor: '#334155',
            width: '480px',
            customClass: {
                popup: 'rounded-2xl shadow-2xl'
            }
        });
    }

    function confirmarEliminacion(event, id, equipo, serial) {
        event.preventDefault(); // Detener el envío automático

        Swal.fire({
            title: '¿Eliminar Activo?',
            html: `
                <div class="text-left bg-slate-50 p-4 rounded-lg border border-slate-200 mt-2">
                    <p class="font-bold text-slate-800 mb-1">${equipo}</p>
                    <p class="text-sm text-slate-500 font-mono">SN: ${serial}</p>
                </div>
                <p class="mt-4 text-sm text-slate-600">Esta acción eliminará el registro permanentemente del inventario.</p>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#94a3b8',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            reverseButtons: true,
            focusCancel: true
        }).then((result) => {
            if (result.isConfirmed) {
                // Enviar el formulario manualmente
                document.getElementById('formEliminar_' + id).submit();
            }
        });
    }

    function filtrarInventario() {
        var input, filter, table, tr, td, i, txtValue;
        input = document.getElementById("buscadorInventario");
        filter = input.value.toUpperCase();
        table = document.getElementById("tablaInventario");
        tr = table.getElementsByClassName("fila-inventario");
        var foundAny = false;

        for (i = 0; i < tr.length; i++) {
            // Buscar en todas las celdas relevantes (SKU, Equipo, Serial, Asignado A)
            var rowText = tr[i].textContent || tr[i].innerText;

            if (rowText.toUpperCase().indexOf(filter) > -1) {
                tr[i].style.display = "";
                foundAny = true;
            } else {
                tr[i].style.display = "none";
            }
        }
    }
</script>