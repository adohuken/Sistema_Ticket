<?php
/**
 * seccion_asignacion_equipos.php - Refactor V2
 */
?>
<!-- SweetAlert2 CDN -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php

// 1. DATA INITIALIZATION (Server Side)
// Lista de empleados activos
$stmt_empleados = $pdo->prepare("
    SELECT id, nombres, apellidos, cedula, cargo, sucursal_nombre as departamento, estado
    FROM vista_personal_completo 
    WHERE estado = 'Activo' 
    ORDER BY apellidos, nombres
");
$stmt_empleados->execute();
$empleados_lista = $stmt_empleados->fetchAll(PDO::FETCH_ASSOC);

// Lista de equipos disponibles (para la pestaña de asignación)
// Lista de equipos disponibles (para la pestaña de asignación)
$stmt_equipos = $pdo->query("SELECT * FROM inventario ORDER BY tipo, marca");
$todos_equipos = $stmt_equipos->fetchAll(PDO::FETCH_ASSOC);

// Agrupar equipos disponibles para UI
$equipos_disponibles_grouped = [];
$count_disponibles = 0;
foreach ($todos_equipos as $eq) {
    if ($eq['condicion'] === 'Disponible') {
        $equipos_disponibles_grouped[$eq['tipo']][] = $eq;
        $count_disponibles++;
    }
}
ksort($equipos_disponibles_grouped);
?>

<!-- CONTAINER PRINCIPAL (Altura fija para layout de aplicación) -->
<div class="h-[calc(100vh-5rem)] flex flex-col bg-slate-50 overflow-hidden">

    <!-- HEADER DEL MÓDULO -->
    <header
        class="bg-white border-b border-slate-200 px-6 py-3 flex justify-between items-center shadow-sm z-20 shrink-0">
        <div class="flex items-center gap-3">
            <div
                class="bg-blue-600 text-white w-10 h-10 rounded-xl flex items-center justify-center shadow-lg shadow-blue-200">
                <i class="ri-macbook-line text-xl"></i>
            </div>
            <div>
                <h1 class="text-xl font-bold text-slate-800 tracking-tight">Centro de Asignaciones</h1>
                <p class="text-xs text-slate-500 font-medium">Gestión integral de activos por colaborador</p>
            </div>
        </div>
        <div>
            <!-- Global Actions (Opcional: Reporte Global) -->
        </div>
    </header>

    <!-- CONTENT BODY (Master-Detail) -->
    <div class="flex flex-1 overflow-hidden">

        <!-- SIDEBAR IZQUIERDO: EMPLEADOS -->
        <aside
            class="w-1/3 max-w-sm bg-white border-r border-slate-200 flex flex-col z-10 shadow-[4px_0_24px_rgba(0,0,0,0.02)]">
            <!-- Search Area -->
            <div class="p-4 border-b border-slate-100 bg-slate-50/50 sticky top-0 z-10">
                <div class="relative group">
                    <i
                        class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-blue-500 transition-colors"></i>
                    <input type="text" id="search-employee"
                        class="w-full pl-10 pr-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all placeholder:text-slate-400"
                        placeholder="Buscar por nombre, cargo o cédula...">
                </div>
            </div>

            <!-- List -->
            <div id="employee-list" class="flex-1 overflow-y-auto p-2 space-y-1 custom-scrollbar">
                <?php foreach ($empleados_lista as $emp): ?>
                    <!-- Employee Card Item -->
                    <div class="employee-card group p-3 rounded-xl border border-transparent hover:bg-blue-50 hover:border-blue-100 cursor-pointer transition-all duration-200"
                        data-id="<?= $emp['id'] ?>"
                        data-search="<?= strtolower($emp['nombres'] . ' ' . $emp['apellidos'] . ' ' . $emp['cedula'] . ' ' . $emp['cargo']) ?>"
                        data-json='<?= htmlspecialchars(json_encode($emp), ENT_QUOTES, 'UTF-8') ?>'>

                        <div class="flex items-center gap-3">
                            <div
                                class="w-10 h-10 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center font-bold text-sm shrink-0 group-hover:bg-white group-hover:text-blue-600 group-hover:shadow-sm transition-all">
                                <?= strtoupper(substr($emp['nombres'], 0, 1) . substr($emp['apellidos'], 0, 1)) ?>
                            </div>
                            <div class="min-w-0 flex-1">
                                <h3 class="text-sm font-semibold text-slate-700 group-hover:text-blue-700 truncate">
                                    <?= $emp['nombres'] . ' ' . $emp['apellidos'] ?>
                                </h3>
                                <p class="text-xs text-slate-500 truncate"><?= $emp['cargo'] ?></p>
                            </div>
                            <i
                                class="ri-arrow-right-s-line text-slate-300 group-hover:text-blue-400 opacity-0 group-hover:opacity-100 transition-all"></i>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- No Results State -->
                <div id="no-employees" class="hidden flex flex-col items-center justify-center py-10 text-slate-400">
                    <i class="ri-user-unfollow-line text-3xl mb-2"></i>
                    <p class="text-sm">No encontrado</p>
                </div>
            </div>
        </aside>


        <!-- PANEL DERECHO: WORKSPACE -->
        <main class="flex-1 flex flex-col bg-slate-50/50 relative overflow-hidden">

            <!-- EMPTY STATE INITIAL -->
            <div id="workspace-empty"
                class="absolute inset-0 flex flex-col items-center justify-center text-slate-400 bg-slate-50 z-0">
                <div class="w-24 h-24 bg-white rounded-full flex items-center justify-center shadow-sm mb-4">
                    <i class="ri-user-search-line text-4xl text-slate-300"></i>
                </div>
                <h3 class="text-lg font-semibold text-slate-600">Selecciona un colaborador</h3>
                <p class="text-sm text-slate-500">Para ver sus activos o realizar nuevas asignaciones</p>
            </div>

            <!-- ACTIVE WORKSPACE -->
            <div id="workspace-active"
                class="flex flex-col h-full hidden opacity-0 transition-opacity duration-300 z-10 overflow-hidden">

                <!-- Employee Profile Header -->
                <div class="bg-white border-b border-slate-200 p-6 flex items-start gap-6 shrink-0 sticky top-0 z-20">
                    <div
                        class="w-16 h-16 rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white text-2xl font-bold shadow-lg shadow-blue-200">
                        <span id="profile-initials">--</span>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center justify-between">
                            <h2 id="profile-name" class="text-2xl font-bold text-slate-800">Nombre Colaborador</h2>
                            <a id="btn-print-acta" href="#" target="_blank"
                                class="hidden items-center gap-2 bg-slate-800 hover:bg-slate-900 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                                <i class="ri-printer-line"></i>
                                Imprimir Acta
                            </a>
                        </div>
                        <div class="flex gap-4 mt-2 text-sm text-slate-500">
                            <span class="flex items-center gap-1"><i class="ri-id-card-line text-slate-400"></i> <span
                                    id="profile-cedula">---</span></span>
                            <span class="flex items-center gap-1"><i class="ri-briefcase-line text-slate-400"></i> <span
                                    id="profile-cargo">---</span></span>
                            <span class="flex items-center gap-1"><i class="ri-building-line text-slate-400"></i> <span
                                    id="profile-dept">---</span></span>
                        </div>
                    </div>
                </div>

                <!-- Tabs Navigation -->
                <div class="px-6 border-b border-slate-200 bg-white/50 backdrop-blur-sm">
                    <div class="flex gap-6">
                        <button
                            class="tab-btn px-4 py-3 text-sm font-medium border-b-2 border-transparent text-slate-500 hover:text-slate-700 transition-all active-tab"
                            data-tab="tab-assigned">
                            Activos Asignados <span id="count-assigned"
                                class="ml-2 bg-slate-100 text-slate-600 px-2 py-0.5 rounded-full text-xs">0</span>
                        </button>
                        <button
                            class="tab-btn px-4 py-3 text-sm font-medium border-b-2 border-transparent text-slate-500 hover:text-slate-700 transition-all"
                            data-tab="tab-new">
                            <i class="ri-add-circle-line mr-1"></i> Nueva Asignación
                        </button>
                    </div>
                </div>

                <!-- Tab Contents -->
                <div id="tab-scroll-container" class="flex-1 overflow-y-auto p-6 custom-scrollbar relative">

                    <!-- TAB 1: ASSIGNED ASSETS -->
                    <div id="tab-assigned" class="tab-content">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr
                                    class="text-xs font-semibold text-slate-400 uppercase tracking-wider border-b border-slate-200">
                                    <th class="pb-3 pl-4">Tipo</th>
                                    <th class="pb-3">Equipo / Marca</th>
                                    <th class="pb-3 font-mono">Serial / ID</th>
                                    <th class="pb-3 text-right pr-4">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="table-assigned-body" class="divide-y divide-slate-50">
                                <!-- Filas generadas por JS -->
                            </tbody>
                        </table>
                        <div id="empty-assigned-msg" class="hidden py-8 text-center">
                            <p class="text-slate-400 text-sm">Este colaborador no tiene equipos asignados.</p>
                        </div>
                    </div>

                    <!-- TAB 2: NEW ASSIGNMENT (Inventory) -->
                    <div id="tab-new" class="tab-content hidden">
                        <!-- Inventory Filter -->
                        <div class="mb-4">
                            <input type="text" id="search-inventory" placeholder="Filtrar inventario disponible..."
                                class="w-full bg-white border border-slate-200 rounded-lg px-4 py-2 text-sm focus:ring-1 focus:ring-blue-500 outline-none">
                        </div>

                        <!-- Inventory Grid -->
                        <div class="space-y-6" id="inventory-container">
                            <?php foreach ($equipos_disponibles_grouped as $tipo => $list): ?>
                                <div class="inventory-group" data-type="<?= strtolower($tipo) ?>">
                                    <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3 ml-1">
                                        <?= $tipo ?>
                                    </h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                        <?php foreach ($list as $eq): ?>
                                            <!-- Asset Card -->
                                            <div class="asset-card relative bg-white border border-slate-200 rounded-xl p-4 cursor-pointer hover:border-blue-300 hover:shadow-md transition-all select-none"
                                                data-id="<?= $eq['id'] ?>"
                                                data-search="<?= strtolower($eq['tipo'] . ' ' . $eq['marca'] . ' ' . $eq['modelo'] . ' ' . $eq['serial']) ?>">

                                                <!-- Checkbox UI (Fake) -->
                                                <div
                                                    class="absolute top-3 right-3 w-5 h-5 rounded-full border border-slate-300 flex items-center justify-center check-indicator transition-colors bg-white">
                                                    <i class="ri-check-line text-white text-xs opacity-0"></i>
                                                </div>

                                                <div class="pr-6">
                                                    <p class="text-xs text-blue-600 font-semibold mb-1"><?= $eq['marca'] ?></p>
                                                    <h5 class="text-sm font-bold text-slate-700 truncate"
                                                        title="<?= $eq['modelo'] ?>"><?= $eq['modelo'] ?></h5>
                                                    <p class="text-xs font-mono text-slate-400 mt-1"><?= $eq['serial'] ?></p>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FLOATING ACTION BAR (Bulk Assign) -->
            <div id="bulk-action-bar"
                class="absolute bottom-6 left-1/2 -translate-x-1/2 bg-slate-900 text-white px-6 py-3 rounded-2xl shadow-2xl flex items-center gap-6 translate-y-24 opacity-0 transition-all duration-300 z-50">
                <div class="flex items-center gap-3">
                    <span class="bg-blue-500 w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold"
                        id="bulk-count">0</span>
                    <span class="text-sm font-medium">Equipos seleccionados</span>
                </div>
                <button id="btn-confirm-assign"
                    class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-1.5 rounded-lg text-sm font-bold shadow-lg shadow-emerald-200 transition-colors">
                    Asignar Ahora
                </button>
            </div>

        </main>
    </div>
</div>

<!-- JAVASCRIPT LOGIC (Module Scoped) -->
<script>
    /**
     * AssignmentManager V2
     * Lógica centralizada para gestión de asignaciones.
     */
    const CSRF_TOKEN = "<?= $_SESSION['csrf_token'] ?>";

    const AssignmentManager = {
        state: {
            currentEmployeeId: null,
            currentEmployeeData: null,
            selectedAssets: new Set()
        },

        init() {
            console.log("%c 🚀 SWEETALERT V2 ACTIVE ", "background: #22c55e; color: white; padding: 4px; border-radius: 4px;");

            // Bind Search Employees
            document.getElementById('search-employee').addEventListener('input', (e) => this.filterEmployees(e.target.value));

            // Bind Search Inventory
            document.getElementById('search-inventory').addEventListener('input', (e) => this.filterInventory(e.target.value));

            // Bind Employee Selection
            document.getElementById('employee-list').addEventListener('click', (e) => {
                const card = e.target.closest('.employee-card');
                if (card) this.selectEmployee(card);
            });

            // Bind Asset Selection (Delegation)
            document.getElementById('inventory-container').addEventListener('click', (e) => {
                const card = e.target.closest('.asset-card');
                if (card) this.toggleAssetSelection(card);
            });

            // Bind Tabs
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.addEventListener('click', () => this.switchTab(btn.dataset.tab));
            });

            // Bind Bulk Action
            document.getElementById('btn-confirm-assign').addEventListener('click', () => this.commitBulkAssignment());
        },

        /* --- LOGIC: SELECTION & RENDERING --- */

        selectEmployee(cardElement) {
            // UI Highlight
            document.querySelectorAll('.employee-card').forEach(c => c.classList.remove('bg-blue-50', 'border-blue-200', 'ring-1', 'ring-blue-100'));
            cardElement.classList.add('bg-blue-50', 'border-blue-200', 'ring-1', 'ring-blue-100');

            // Update State
            const data = JSON.parse(cardElement.dataset.json);
            this.state.currentEmployeeId = data.id;
            this.state.currentEmployeeData = data;
            this.state.selectedAssets.clear(); // Clear selection when changing user
            this.updateBulkUI(); // Hide bar

            // Update Header UI
            document.getElementById('profile-name').textContent = `${data.nombres} ${data.apellidos}`;
            document.getElementById('profile-initials').textContent = data.nombres[0] + data.apellidos[0];
            document.getElementById('profile-cedula').textContent = data.cedula;
            document.getElementById('profile-cargo').textContent = data.cargo;
            document.getElementById('profile-dept').textContent = data.departamento;

            // Update Print Button
            const printBtn = document.getElementById('btn-print-acta');
            printBtn.href = `index.php?view=generar_acta&id=${data.id}`;
            printBtn.classList.remove('hidden');
            printBtn.classList.add('flex');

            // Show Tables
            document.getElementById('workspace-empty').classList.add('hidden');
            const workspace = document.getElementById('workspace-active');
            workspace.classList.remove('hidden');
            setTimeout(() => workspace.classList.remove('opacity-0'), 10);

            // Fetch Current Assets
            this.fetchAssignedAssets(data.id);
        },

        fetchAssignedAssets(empId) {
            const tbody = document.getElementById('table-assigned-body');
            tbody.innerHTML = '<tr><td colspan="4" class="p-4 text-center text-slate-400"><i class="ri-loader-4-line animate-spin"></i> Cargando...</td></tr>';

            const url = `index.php?view=asignacion_equipos&ajax_action=get_empleado_data&id=${empId}`;

            fetch(url)
                .then(r => r.json())
                .then(res => {
                    if (res.status === 'success') {
                        this.renderAssignedTable(res.activos);
                    } else {
                        alert("Error cargando datos: " + res.msg);
                    }
                })
                .catch(err => {
                    console.error(err);
                    tbody.innerHTML = '<tr><td colspan="4" class="text-red-500 p-4">Error de conexión</td></tr>';
                });
        },

        renderAssignedTable(assets) {
            const tbody = document.getElementById('table-assigned-body');
            const counter = document.getElementById('count-assigned');
            const emptyMsg = document.getElementById('empty-assigned-msg');

            // Mapeo unificado de iconos (Sincronizado con inventario)
            const tipoMap = {
                'Laptop': { icon: 'ri-macbook-line', bg: 'bg-blue-100 text-blue-600' },
                'PC': { icon: 'ri-computer-line', bg: 'bg-indigo-100 text-indigo-600' },
                'Movil': { icon: 'ri-smartphone-line', bg: 'bg-purple-100 text-purple-600' },
                'Monitor': { icon: 'ri-tv-line', bg: 'bg-cyan-100 text-cyan-600' },
                'Silla': { icon: 'ri-armchair-line', bg: 'bg-orange-100 text-orange-600' },
                'Escritorio': { icon: 'ri-layout-line', bg: 'bg-amber-100 text-amber-600' },
                'Teclado': { icon: 'ri-keyboard-line', bg: 'bg-slate-100 text-slate-600' },
                'Mouse': { icon: 'ri-mouse-line', bg: 'bg-gray-100 text-gray-600' },
                'Headset': { icon: 'ri-headphone-line', bg: 'bg-pink-100 text-pink-600' },
                'Impresora': { icon: 'ri-printer-line', bg: 'bg-green-100 text-green-600' },
                'Otro': { icon: 'ri-box-3-line', bg: 'bg-slate-100 text-slate-600' }
            };

            tbody.innerHTML = '';
            if (!Array.isArray(assets)) assets = [];
            counter.textContent = assets.length;

            if (assets.length === 0) {
                emptyMsg.classList.remove('hidden');
            } else {
                emptyMsg.classList.add('hidden');
                assets.forEach(asset => {
                    // Obtener estilos según tipo
                    const style = tipoMap[asset.tipo] || tipoMap['Otro'];

                    const row = document.createElement('tr');
                    row.className = 'hover:bg-slate-50 transition-colors border-b border-slate-50';
                    row.innerHTML = `
                        <td class="py-3 pl-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg ${style.bg} flex items-center justify-center">
                                    <i class="${style.icon} text-lg"></i>
                                </div>
                                <span class="text-sm font-medium text-slate-700">${asset.tipo}</span>
                            </div>
                        </td>
                        <td class="py-3 text-sm text-slate-600">
                            <span class="font-bold block text-slate-800">${asset.marca}</span>
                            <span class="text-xs">${asset.modelo}</span>
                        </td>
                        <td class="py-3 font-mono text-xs text-slate-500">${asset.serial}</td>
                        <td class="py-3 pr-4 text-right">
                            <button onclick="AssignmentManager.releaseAsset(${asset.id})" 
                                class="text-xs bg-white border border-slate-200 text-slate-600 hover:text-red-600 hover:border-red-200 px-3 py-1 rounded-lg transition-colors font-medium shadow-sm">
                                Liberar
                            </button>
                        </td>
                    `;
                    tbody.appendChild(row);
                });
            }
        },

        /* --- LOGIC: ASSET SELECTION (BULK) --- */

        toggleAssetSelection(card) {
            const id = card.dataset.id;
            const indicator = card.querySelector('.check-indicator');
            const icon = indicator.querySelector('i');

            if (this.state.selectedAssets.has(id)) {
                // Deselect
                this.state.selectedAssets.delete(id);
                card.classList.remove('border-blue-500', 'ring-1', 'ring-blue-500', 'bg-blue-50');
                indicator.classList.remove('bg-blue-600', 'border-blue-600');
                indicator.classList.add('bg-white', 'border-slate-300');
                icon.classList.add('opacity-0');
            } else {
                // Select
                this.state.selectedAssets.add(id);
                card.classList.add('border-blue-500', 'ring-1', 'ring-blue-500', 'bg-blue-50');
                indicator.classList.remove('bg-white', 'border-slate-300');
                indicator.classList.add('bg-blue-600', 'border-blue-600');
                icon.classList.remove('opacity-0');
            }

            this.updateBulkUI();
        },

        updateBulkUI() {
            const bar = document.getElementById('bulk-action-bar');
            const count = this.state.selectedAssets.size;

            document.getElementById('bulk-count').textContent = count;

            if (count > 0) {
                bar.classList.remove('translate-y-24', 'opacity-0');
            } else {
                bar.classList.add('translate-y-24', 'opacity-0');
            }
        },

        /* --- LOGIC: ACTIONS --- */

        commitBulkAssignment() {
            if (this.state.selectedAssets.size === 0) return;

            const ids = Array.from(this.state.selectedAssets);

            Swal.fire({
                title: '¿Confirmar Asignación?',
                text: `Vas a asignar ${ids.length} equipos a ${this.state.currentEmployeeData.nombres}`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#2563EB',
                cancelButtonColor: '#64748B',
                confirmButtonText: 'Sí, asignar',
                cancelButtonText: 'Cancelar',
                returnFocus: false, // Evita problema de scroll al desaparecer botón
                heightAuto: false // Evita que Swal modifique el body
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('ajax_action', 'asignar_multiples_equipos');
                    formData.append('empleado_id', this.state.currentEmployeeId);
                    formData.append('equipo_ids', JSON.stringify(ids));
                    formData.append('csrf_token', CSRF_TOKEN);

                    // FORCE SCROLL RESET (Parche definitivo)
                    document.getElementById('workspace-active').scrollTop = 0;
                    document.getElementById('tab-scroll-container').scrollTop = 0;
                    document.querySelector('main').scrollTop = 0;

                    // Loading State

                    // Loading State
                    Swal.fire({
                        title: 'Procesando...',
                        text: 'Asignando equipos',
                        allowOutsideClick: false,
                        didOpen: () => { Swal.showLoading(); }
                    });

                    fetch('index.php?view=asignacion_equipos', {
                        method: 'POST',
                        body: formData
                    })
                        .then(r => r.json())
                        .then(data => {
                            if (data.status === 'success') {
                                // TOAST SUCCESS (No focus steal)
                                const Toast = Swal.mixin({
                                    toast: true,
                                    position: 'top-end',
                                    showConfirmButton: false,
                                    timer: 3000,
                                    timerProgressBar: true,
                                    didOpen: (toast) => {
                                        toast.addEventListener('mouseenter', Swal.stopTimer)
                                        toast.addEventListener('mouseleave', Swal.resumeTimer)
                                    }
                                });

                                Toast.fire({
                                    icon: 'success',
                                    title: '¡Asignación Completada!',
                                    text: `${ids.length} equipos asignados exitosamente`
                                });

                                // Remove assigned cards from Inventory View
                                ids.forEach(id => {
                                    const card = document.querySelector(`.asset-card[data-id="${id}"]`);
                                    if (card) card.remove();
                                });

                                // Clear Selection
                                this.state.selectedAssets.clear();
                                this.updateBulkUI();

                                // Switch back to assigned tab and reload without scroll jump
                                this.switchTab('tab-assigned');
                                this.fetchAssignedAssets(this.state.currentEmployeeId);

                            } else {
                                Swal.fire('Error', data.msg, 'error');
                            }
                        })
                        .catch(err => Swal.fire('Error de Red', err.toString(), 'error'));
                }
            });
        },

        releaseAsset(assetId) {
            Swal.fire({
                title: '¿Liberar Equipo?',
                text: "El equipo regresará al inventario como 'Disponible'.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#EF4444',
                cancelButtonColor: '#64748B',
                confirmButtonText: 'Sí, liberar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('ajax_action', 'liberar_equipo');
                    formData.append('equipo_id', assetId);
                    formData.append('csrf_token', CSRF_TOKEN);

                    fetch('index.php?view=asignacion_equipos', {
                        method: 'POST',
                        body: formData
                    })
                        .then(r => r.json())
                        .then(data => {
                            if (data.status === 'success') {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Equipo Liberado',
                                    toast: true,
                                    position: 'top-end',
                                    showConfirmButton: false,
                                    timer: 3000
                                });
                                this.fetchAssignedAssets(this.state.currentEmployeeId);
                            } else {
                                Swal.fire('Error', data.msg, 'error');
                            }
                        })
                        .catch(err => Swal.fire('Error', err.toString(), 'error'));
                }
            });
        },

        /* --- UTILS --- */

        switchTab(tabId) {
            // Update Buttons
            document.querySelectorAll('.tab-btn').forEach(btn => {
                if (btn.dataset.tab === tabId) {
                    btn.classList.add('border-blue-600', 'text-blue-600', 'active-tab');
                    btn.classList.remove('border-transparent', 'text-slate-500');
                } else {
                    btn.classList.remove('border-blue-600', 'text-blue-600', 'active-tab');
                    btn.classList.add('border-transparent', 'text-slate-500');
                }
            });

            // Show Content
            document.querySelectorAll('.tab-content').forEach(content => {
                if (content.id === tabId) content.classList.remove('hidden');
                else content.classList.add('hidden');
            });
        },

        filterEmployees(term) {
            term = term.toLowerCase();
            document.querySelectorAll('.employee-card').forEach(card => {
                if (card.dataset.search.includes(term)) card.classList.remove('hidden');
                else card.classList.add('hidden');
            });
        },

        filterInventory(term) {
            term = term.toLowerCase();
            document.querySelectorAll('.asset-card').forEach(card => {
                if (card.dataset.search.includes(term)) card.classList.remove('hidden');
                else card.classList.add('hidden');
            });
        }
    };

    // Initialize App
    document.addEventListener('DOMContentLoaded', () => {
        AssignmentManager.init();
    });
</script>