<?php
/**
 * seccion_mantenimiento_reporte.php - Reporte Técnico Detallado
 */

// Filtros
$filtro_empresa = $_GET['empresa'] ?? '';
$filtro_sucursal = $_GET['sucursal'] ?? '';
$busqueda = $_GET['busqueda'] ?? '';

// Obtener listas para filtros
$empresas = $pdo->query("SELECT id, nombre FROM empresas ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
$sucursales = $pdo->query("SELECT id, nombre, empresa_id FROM sucursales WHERE activa=1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

// Construir Query
$sql = "
    SELECT i.*, 
           CONCAT(u.nombres, ' ', u.apellidos) as usuario_asignado,
           u.sucursal_nombre,
           e.nombre as empresa_nombre
    FROM inventario i
    LEFT JOIN vista_personal_completo u ON i.asignado_a = u.id
    LEFT JOIN sucursales s ON u.sucursal_id = s.id
    LEFT JOIN empresas e ON s.empresa_id = e.id
    WHERE i.estado = 'Activo'
"; // Asumiendo que solo queremos activos 'Activos' o 'En Uso'

$params = [];

// Filtros Lógicos
if ($filtro_empresa) {
    $sql .= " AND e.id = ?";
    $params[] = $filtro_empresa;
}

if ($filtro_sucursal) {
    $sql .= " AND s.id = ?";
    $params[] = $filtro_sucursal;
}

if ($busqueda) {
    $sql .= " AND (i.serial LIKE ? OR i.modelo LIKE ? OR u.nombres LIKE ?)";
    $term = "%$busqueda%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

$sql .= " ORDER BY e.nombre, s.nombre, i.tipo";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$equipos = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="p-6 flex-1 bg-slate-50 min-h-screen">
    <div class="max-w-[1920px] mx-auto space-y-6">

        <!-- Header & Nav -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 text-sm text-slate-500 mb-2">
                    <a href="index.php?view=mantenimiento_equipos" class="hover:text-blue-600 transition-colors">Control
                        Mantenimiento</a>
                    <i class="ri-arrow-right-s-line"></i>
                    <span>Reporte Técnico</span>
                </div>
                <h1 class="text-3xl font-bold text-slate-800 flex items-center gap-3">
                    <span class="bg-blue-600 text-white p-3 rounded-xl shadow-lg shadow-blue-500/30">
                        <i class="ri-file-list-3-fill"></i>
                    </span>
                    Reporte Técnico de Activos
                </h1>
                <p class="text-slate-500 mt-2">Visión global de asignaciones y especificaciones técnicas</p>
            </div>

            <div class="flex gap-3">
                <button onclick="exportarReporte()"
                    class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2.5 rounded-xl font-semibold shadow-lg shadow-emerald-500/20 transition-all flex items-center gap-2">
                    <i class="ri-file-excel-2-line"></i>
                    Exportar Excel
                </button>
            </div>
        </div>

        <!-- Filtros -->
        <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200">
            <form action="" method="GET" class="flex flex-wrap gap-4 items-end">
                <input type="hidden" name="view" value="mantenimiento_reporte">

                <div class="w-full md:w-64">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Empresa</label>
                    <div class="relative">
                        <i class="ri-building-line absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <select name="empresa" onchange="this.form.submit()"
                            class="w-full pl-9 pr-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none bg-white text-sm">
                            <option value="">Todas las Empresas</option>
                            <?php foreach ($empresas as $emp): ?>
                                <option value="<?= $emp['id'] ?>" <?= $filtro_empresa == $emp['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($emp['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="w-full md:w-64">
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Búsqueda</label>
                    <div class="relative">
                        <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="text" name="busqueda" value="<?= htmlspecialchars($busqueda) ?>"
                            class="w-full pl-9 pr-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm"
                            placeholder="Serial, Modelo, Usuario...">
                    </div>
                </div>

                <button type="submit"
                    class="bg-slate-800 text-white px-4 py-2 rounded-lg hover:bg-slate-900 transition-colors text-sm font-medium">
                    Filtrar
                </button>

                <?php if ($filtro_empresa || $busqueda): ?>
                    <a href="index.php?view=mantenimiento_reporte"
                        class="text-xs text-red-500 hover:underline self-center ml-2">
                        Limpiar Filtros
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Tabla -->
        <div class="bg-white rounded-xl shadow-xl border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse" id="tabla-reporte">
                    <thead class="bg-sky-600 text-white">
                        <tr>
                            <th class="px-3 py-3 text-xs font-bold uppercase tracking-wider border-r border-sky-500/30">
                                Asignación</th>
                            <th class="px-3 py-3 text-xs font-bold uppercase tracking-wider border-r border-sky-500/30">
                                Equipo</th>
                            <th class="px-3 py-3 text-xs font-bold uppercase tracking-wider border-r border-sky-500/30">
                                Marca/Modelo</th>
                            <th
                                class="px-3 py-3 text-xs font-bold uppercase tracking-wider border-r border-sky-500/30 max-w-[100px]">
                                Serie</th>
                            <th class="px-3 py-3 text-xs font-bold uppercase tracking-wider border-r border-sky-500/30">
                                Procesador</th>
                            <th class="px-3 py-3 text-xs font-bold uppercase tracking-wider border-r border-sky-500/30">
                                HDD/SSD</th>
                            <th class="px-3 py-3 text-xs font-bold uppercase tracking-wider border-r border-sky-500/30">
                                RAM</th>
                            <th class="px-3 py-3 text-xs font-bold uppercase tracking-wider border-r border-sky-500/30">
                                Antivirus</th>
                            <th class="px-3 py-3 text-xs font-bold uppercase tracking-wider border-r border-sky-500/30">
                                OneDrive</th>
                            <th class="px-3 py-3 text-xs font-bold uppercase tracking-wider border-r border-sky-500/30">
                                BackUp</th>
                            <th class="px-3 py-3 text-xs font-bold uppercase tracking-wider border-r border-sky-500/30">
                                ScreenConnect</th>
                            <th class="px-3 py-3 text-xs font-bold uppercase tracking-wider">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs text-slate-700">
                        <?php if (empty($equipos)): ?>
                            <tr>
                                <td colspan="12" class="p-8 text-center text-slate-500">No se encontraron equipos.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($equipos as $eq): ?>
                                <tr class="hover:bg-blue-50/50 group transition-colors">
                                    <td class="px-3 py-2 border-r border-slate-100 align-middle">
                                        <?php if ($eq['usuario_asignado']): ?>
                                            <div class="font-bold text-blue-700">
                                                <?= htmlspecialchars($eq['empresa_nombre']) ?>
                                            </div>
                                            <div class="text-[10px] text-slate-500">
                                                <?= htmlspecialchars($eq['sucursal_nombre']) ?>
                                            </div>
                                            <div class="mt-0.5 text-slate-800 font-medium">
                                                <?= htmlspecialchars($eq['usuario_asignado']) ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-slate-400 italic">Sin Asignar</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-3 py-2 border-r border-slate-100 font-bold align-middle">
                                        <?= htmlspecialchars($eq['tipo']) ?>
                                    </td>
                                    <td class="px-3 py-2 border-r border-slate-100 align-middle">
                                        <div class="font-semibold">
                                            <?= htmlspecialchars($eq['marca']) ?>
                                        </div>
                                        <div class="text-slate-500">
                                            <?= htmlspecialchars($eq['modelo']) ?>
                                        </div>
                                    </td>
                                    <td class="px-3 py-2 border-r border-slate-100 font-mono text-[10px] align-middle">
                                        <?= htmlspecialchars($eq['serial']) ?>
                                    </td>

                                    <!-- Campos Editables (Visualización) -->
                                    <td class="px-3 py-2 border-r border-slate-100 align-middle">
                                        <?= htmlspecialchars($eq['procesador'] ?: '---') ?>
                                    </td>
                                    <td class="px-3 py-2 border-r border-slate-100 align-middle">
                                        <?= htmlspecialchars($eq['disco_duro'] ?: '---') ?>
                                    </td>
                                    <td class="px-3 py-2 border-r border-slate-100 align-middle">
                                        <?= htmlspecialchars($eq['ram'] ?: '---') ?>
                                    </td>
                                    <td class="px-3 py-2 border-r border-slate-100 align-middle">
                                        <?= htmlspecialchars($eq['antivirus'] ?: '---') ?>
                                    </td>
                                    <td class="px-3 py-2 border-r border-slate-100 align-middle">
                                        <?= htmlspecialchars($eq['onedrive'] ?: '---') ?>
                                    </td>
                                    <td class="px-3 py-2 border-r border-slate-100 align-middle">
                                        <?= htmlspecialchars($eq['backup_status'] ?: '---') ?>
                                    </td>
                                    <td class="px-3 py-2 border-r border-slate-100 align-middle">
                                        <?= htmlspecialchars($eq['screenconnect'] ?: '---') ?>
                                    </td>

                                    <td class="px-3 py-2 align-middle text-center">
                                        <button onclick='editarSpecs(<?= json_encode($eq) ?>)'
                                            class="w-7 h-7 flex items-center justify-center rounded bg-white border border-slate-200 text-slate-400 hover:text-blue-600 hover:border-blue-300 transition-colors shadow-sm"
                                            title="Editar Especificaciones">
                                            <i class="ri-edit-2-line"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Edición Specs -->
<div id="modalSpecs" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity" onclick="cerrarModalSpecs()"></div>
    <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
            <div
                class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all w-full max-w-lg">
                <form id="formSpecs" onsubmit="guardarSpecs(event)">
                    <input type="hidden" name="ajax_action" value="actualizar_specs_equipo">
                    <input type="hidden" name="id" id="edit_id">

                    <div class="bg-blue-600 px-6 py-4 flex justify-between items-center">
                        <h3 class="text-lg font-bold text-white flex items-center gap-2">
                            <i class="ri-cpu-line"></i> Ficha Técnica
                        </h3>
                        <button type="button" onclick="cerrarModalSpecs()" class="text-white/80 hover:text-white">
                            <i class="ri-close-line text-2xl"></i>
                        </button>
                    </div>

                    <div class="p-6 space-y-4">
                        <div class="bg-blue-50 p-3 rounded-lg border border-blue-100 mb-4">
                            <h4 class="font-bold text-blue-900 text-sm" id="edit_titulo">Equipo</h4>
                            <p class="text-xs text-blue-700" id="edit_subtitulo">SN: ...</p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-1">Procesador</label>
                                <input type="text" name="procesador" id="edit_procesador"
                                    class="w-full px-3 py-2 border rounded-lg text-sm bg-slate-50 focus:bg-white focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-1">RAM</label>
                                <input type="text" name="ram" id="edit_ram"
                                    class="w-full px-3 py-2 border rounded-lg text-sm bg-slate-50 focus:bg-white focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                            </div>
                            <div class="col-span-2">
                                <label class="block text-xs font-bold text-slate-500 mb-1">Disco Duro</label>
                                <input type="text" name="disco_duro" id="edit_disco_duro"
                                    class="w-full px-3 py-2 border rounded-lg text-sm bg-slate-50 focus:bg-white focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4 border-t border-slate-100 pt-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-1">Antivirus</label>
                                <input type="text" name="antivirus" id="edit_antivirus"
                                    class="w-full px-3 py-2 border rounded-lg text-sm bg-slate-50 focus:bg-white focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-1">OneDrive</label>
                                <select name="onedrive" id="edit_onedrive"
                                    class="w-full px-3 py-2 border rounded-lg text-sm bg-slate-50 focus:bg-white focus:ring-2 focus:ring-blue-500 outline-none">
                                    <option value="">--</option>
                                    <option value="Activo">Activo</option>
                                    <option value="No Configurado">No Configurado</option>
                                    <option value="No Aplica">No Aplica</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-1">BackUp</label>
                                <select name="backup_status" id="edit_backup"
                                    class="w-full px-3 py-2 border rounded-lg text-sm bg-slate-50 focus:bg-white focus:ring-2 focus:ring-blue-500 outline-none">
                                    <option value="">--</option>
                                    <option value="Si">Si</option>
                                    <option value="No">No</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-1">ScreenConnect</label>
                                <select name="screenconnect" id="edit_screenconnect"
                                    class="w-full px-3 py-2 border rounded-lg text-sm bg-slate-50 focus:bg-white focus:ring-2 focus:ring-blue-500 outline-none">
                                    <option value="">--</option>
                                    <option value="Activo">Activo</option>
                                    <option value="Inactivo">Inactivo</option>
                                    <option value="No Instalado">No Instalado</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="bg-slate-50 px-6 py-4 flex justify-end gap-2">
                        <button type="button" onclick="cerrarModalSpecs()"
                            class="px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-200 rounded-lg transition-colors">Cancelar</button>
                        <button type="submit"
                            class="px-4 py-2 text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-md transition-colors">Guardar
                            Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function editarSpecs(data) {
        document.getElementById('edit_id').value = data.id;
        document.getElementById('edit_titulo').textContent = `${data.tipo} ${data.marca} ${data.modelo}`;
        document.getElementById('edit_subtitulo').textContent = `Serial: ${data.serial}`;

        document.getElementById('edit_procesador').value = data.procesador || '';
        document.getElementById('edit_ram').value = data.ram || '';
        document.getElementById('edit_disco_duro').value = data.disco_duro || '';
        document.getElementById('edit_antivirus').value = data.antivirus || '';
        document.getElementById('edit_onedrive').value = data.onedrive || '';
        document.getElementById('edit_backup').value = data.backup_status || '';
        document.getElementById('edit_screenconnect').value = data.screenconnect || '';

        document.getElementById('modalSpecs').classList.remove('hidden');
    }

    function cerrarModalSpecs() {
        document.getElementById('modalSpecs').classList.add('hidden');
    }

    function guardarSpecs(e) {
        e.preventDefault();
        const formData = new FormData(document.getElementById('formSpecs'));

        // Inyectar view param para que index.php lo enrute correctamente si usas un handler unificado
        // O llamar directamente a index.php?view=mantenimiento_reporte&ajax=1

        fetch('index.php?view=mantenimiento_reporte&ajax_action=guardar_specs', {
            method: 'POST',
            body: formData
        })
            .then(r => r.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Guardado',
                        text: 'Especificaciones actualizadas correctamente',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => location.reload());
                } else {
                    Swal.fire('Error', data.msg || 'No se pudo guardar', 'error');
                }
            })
            .catch(err => {
                console.error(err);
                Swal.fire('Error', 'Error de conexión', 'error');
            });
    }

    function exportarReporte() {
        // Muy simple exportación a Excel
        let table = document.getElementById("tabla-reporte");
        let html = table.outerHTML;
        let url = 'data:application/vnd.ms-excel,' + escape(html); // Simple data URI
        let link = document.createElement("a");
        link.download = "Reporte_Tecnico_" + new Date().toISOString().slice(0, 10) + ".xls";
        link.href = url;
        link.click();
    }
</script>