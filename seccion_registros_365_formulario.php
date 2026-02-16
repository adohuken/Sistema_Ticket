<?php
/**
 * seccion_registros_365_formulario.php - Crear/Editar Cuenta 365
 */

// Si hay ID, es edición
$id_registro = $_GET['id'] ?? null;
$registro = null;

if ($id_registro) {
    $stmt = $pdo->prepare("SELECT * FROM registros_365 WHERE id = ?");
    $stmt->execute([$id_registro]);
    $registro = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$registro) {
        die("Registro no encontrado");
    }
}

// Obtener lista de empleados para el select
$stmt_emp = $pdo->prepare("SELECT id, nombres, apellidos, sucursal_nombre, cargo FROM vista_personal_completo WHERE estado = 'Activo' ORDER BY nombres ASC");
$stmt_emp->execute();
$empleados = $stmt_emp->fetchAll(PDO::FETCH_ASSOC);

// Obtener empresas y sucursales para los selectores
$empresas = $pdo->query("SELECT id, nombre FROM empresas ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
$sucursales = $pdo->query("SELECT id, nombre, empresa_id FROM sucursales WHERE activa = 1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

// Obtener cargos para el selector
$cargos = $pdo->query("SELECT id, nombre FROM cargos WHERE activo = 1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="p-6 flex-1">
    <div class="max-w-3xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center gap-2 text-sm text-slate-500 mb-2">
                <a href="index.php?view=registros_365" class="hover:text-blue-600 transition-colors">Cuentas 365</a>
                <i class="ri-arrow-right-s-line"></i>
                <span><?= $id_registro ? 'Editar Cuenta' : 'Nueva Cuenta' ?></span>
            </div>
            <h1 class="text-3xl font-bold text-slate-800 flex items-center gap-3">
                <span
                    class="bg-gradient-to-br from-blue-600 to-cyan-600 text-white p-3 rounded-xl shadow-lg shadow-blue-500/30">
                    <i class="ri-microsoft-fill"></i>
                </span>
                <?= $id_registro ? 'Editar Cuenta 365' : 'Registrar Nueva Cuenta' ?>
            </h1>
        </div>

        <form method="POST" action="index.php"
            class="bg-white rounded-2xl shadow-xl border border-slate-100 overflow-hidden">
            <input type="hidden" name="accion"
                value="<?= $id_registro ? 'editar_registro_365' : 'crear_registro_365' ?>">
            <?php if ($id_registro): ?>
                <input type="hidden" name="id" value="<?= $id_registro ?>">
            <?php endif; ?>
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            <input type="hidden" name="view" value="registros_365">

            <div class="p-8 space-y-6">
                <!-- 1. Empresa, Sucursal y Cargo -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">
                            <i class="ri-building-4-line text-blue-500"></i> Empresa
                        </label>
                        <select name="empresa_id" id="empresa_id"
                            class="w-full px-4 py-3 border-2 border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none bg-white">
                            <option value="">-- Selecciona Empresa --</option>
                            <?php foreach ($empresas as $emp): ?>
                                <option value="<?= $emp['id'] ?>" <?= ($registro['empresa_id'] ?? '') == $emp['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($emp['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">
                            <i class="ri-store-2-line text-green-500"></i> Sucursal
                        </label>
                        <select name="sucursal_id" id="sucursal_id"
                            class="w-full px-4 py-3 border-2 border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none bg-white">
                            <option value="">-- Selecciona Sucursal --</option>
                            <?php foreach ($sucursales as $suc): ?>
                                <option value="<?= $suc['id'] ?>" data-empresa="<?= $suc['empresa_id'] ?>"
                                    <?= ($registro['sucursal_id'] ?? '') == $suc['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($suc['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">
                            <i class="ri-briefcase-line text-purple-500"></i> Cargo
                        </label>
                        <select name="cargo_id"
                            class="w-full px-4 py-3 border-2 border-slate-200 rounded-lg focus:ring-2 focus:ring-purple-500 outline-none bg-white">
                            <option value="">-- Selecciona Cargo --</option>
                            <?php foreach ($cargos as $cargo): ?>
                                <option value="<?= $cargo['id'] ?>" <?= ($registro['cargo_id'] ?? '') == $cargo['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cargo['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- 2. Asignación -->
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">
                        <i class="ri-user-settings-line text-indigo-500"></i> Asignado a <span
                            class="text-slate-400 font-normal">(Opcional)</span>
                    </label>
                    <!-- Contenedor de Selección de Usuario (Modal Trigger) -->
                    <div id="user-selection-ui"
                        class="bg-slate-50 border border-slate-200 rounded-xl p-4 flex items-center justify-between group hover:border-blue-300 transition-colors cursor-pointer"
                        onclick="openUserModal()">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-10 h-10 rounded-full bg-white border border-slate-200 flex items-center justify-center text-slate-400">
                                <i class="ri-user-line text-xl"></i>
                            </div>
                            <div>
                                <h4 id="display_user_name" class="font-bold text-slate-700 text-sm">
                                    <?= ($registro['usuario_id'] ?? false) ? 'Usuario Seleccionado' : 'Sin Asignar' ?>
                                </h4>
                                <p id="display_user_detail" class="text-xs text-slate-500">
                                    <?= ($registro['usuario_id'] ?? false) ? 'Click para cambiar' : 'Click para seleccionar empleado' ?>
                                </p>
                            </div>
                        </div>
                        <div
                            class="text-blue-600 bg-blue-50 px-3 py-1.5 rounded-lg text-sm font-medium opacity-0 group-hover:opacity-100 transition-opacity">
                            Seleccionar
                        </div>
                    </div>

                    <!-- Input Oculto Real -->
                    <input type="hidden" name="usuario_id" id="usuario_id_real"
                        value="<?= $registro['usuario_id'] ?? '' ?>">
                    <?php if ($id_registro && $registro['usuario_id']): ?>
                        <p class="text-xs text-slate-500 mt-2 flex items-center gap-1">
                            <i class="ri-calendar-check-line"></i>
                            Asignado desde: <?= date('d/m/Y', strtotime($registro['fecha_asignacion'])) ?>
                        </p>
                    <?php endif; ?>
                </div>

                <!-- 3. Cuenta 365 y Credenciales -->
                <div class="border-t-2 border-slate-100 pt-6">
                    <h3 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
                        <i class="ri-lock-password-line text-blue-600"></i>
                        Cuenta 365 y Credenciales
                    </h3>

                    <!-- Correo y Licencia -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Correo Electrónico <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <i class="ri-mail-line absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                <input type="email" name="email" required
                                    value="<?= htmlspecialchars($registro['email'] ?? '') ?>"
                                    class="w-full pl-10 pr-4 py-3 border-2 border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none transition-all"
                                    placeholder="usuario@dominio.com">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Licencia <span class="text-red-500">*</span>
                            </label>
                            <select name="licencia"
                                class="w-full px-4 py-3 border-2 border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none bg-white">
                                <?php
                                $licencias = ['Business Basic', 'Business Standard', 'Business Premium', 'E3', 'E5', 'Exchange Online'];
                                foreach ($licencias as $lic): ?>
                                    <option value="<?= $lic ?>" <?= ($registro['licencia'] ?? '') == $lic ? 'selected' : '' ?>>
                                        <?= $lic ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Contraseñas AG y Azure -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                <i class="ri-shield-keyhole-line text-purple-500"></i> Contraseña AG
                                <span class="text-slate-400 font-normal text-xs">(Sistema Interno)</span>
                            </label>
                            <div class="relative">
                                <i class="ri-key-2-line absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                <input type="text" name="password_ag"
                                    value="<?= htmlspecialchars($registro['password_ag'] ?? '') ?>"
                                    class="w-full pl-10 pr-4 py-3 border-2 border-slate-200 rounded-lg focus:ring-2 focus:ring-purple-500 outline-none transition-all font-mono text-sm"
                                    placeholder="Contraseña sistema AG">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                <i class="ri-microsoft-fill text-blue-500"></i> Contraseña Azure AD
                                <span class="text-slate-400 font-normal text-xs">(Correo 365)</span>
                            </label>
                            <div class="relative">
                                <i class="ri-key-line absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                <input type="text" name="password_azure"
                                    value="<?= htmlspecialchars($registro['password_azure'] ?? '') ?>"
                                    class="w-full pl-10 pr-4 py-3 border-2 border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none transition-all font-mono text-sm"
                                    placeholder="Contraseña correo 365">
                            </div>
                        </div>
                    </div>

                    <!-- PIN Windows -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                PIN de Windows
                            </label>
                            <div class="relative">
                                <i class="ri-lock-line absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                <input type="text" name="pin_windows"
                                    value="<?= htmlspecialchars($registro['pin_windows'] ?? '') ?>"
                                    class="w-full pl-10 pr-4 py-3 border-2 border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none transition-all font-mono"
                                    placeholder="PIN de inicio de sesión">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4. Información de Contacto (Gmail y Teléfonos) -->
                <div class="border-t-2 border-slate-100 pt-6">
                    <h3 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
                        <i class="ri-smartphone-line text-green-600"></i>
                        Información de Contacto
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                <i class="ri-google-fill text-red-500"></i> Cuenta Gmail
                            </label>
                            <div class="relative">
                                <i class="ri-mail-line absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                <input type="email" name="cuenta_gmail"
                                    value="<?= htmlspecialchars($registro['cuenta_gmail'] ?? '') ?>"
                                    class="w-full pl-10 pr-4 py-3 border-2 border-slate-200 rounded-lg focus:ring-2 focus:ring-green-500 outline-none transition-all"
                                    placeholder="usuario@gmail.com">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Contraseña Gmail
                            </label>
                            <div class="relative">
                                <i class="ri-key-line absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                <input type="text" name="password_gmail"
                                    value="<?= htmlspecialchars($registro['password_gmail'] ?? '') ?>"
                                    class="w-full pl-10 pr-4 py-3 border-2 border-slate-200 rounded-lg focus:ring-2 focus:ring-green-500 outline-none transition-all font-mono text-sm"
                                    placeholder="Contraseña de Gmail">
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Teléfono Principal
                            </label>
                            <div class="relative">
                                <i class="ri-phone-line absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                <input type="tel" name="telefono_principal"
                                    value="<?= htmlspecialchars($registro['telefono_principal'] ?? '') ?>"
                                    class="w-full pl-10 pr-4 py-3 border-2 border-slate-200 rounded-lg focus:ring-2 focus:ring-green-500 outline-none transition-all"
                                    placeholder="+506 8888-8888">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Teléfono Secundario
                            </label>
                            <div class="relative">
                                <i class="ri-phone-line absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                <input type="tel" name="telefono_secundario"
                                    value="<?= htmlspecialchars($registro['telefono_secundario'] ?? '') ?>"
                                    class="w-full pl-10 pr-4 py-3 border-2 border-slate-200 rounded-lg focus:ring-2 focus:ring-green-500 outline-none transition-all"
                                    placeholder="+506 8888-8888">
                            </div>
                        </div>
                    </div>

                    <!-- 5. Estado y Notas -->
                    <div class="border-t-2 border-slate-100 pt-6">
                        <h3 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
                            <i class="ri-file-text-line text-orange-500"></i>
                            Estado y Notas
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Estado</label>
                                <select name="estado"
                                    class="w-full px-4 py-3 border-2 border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none bg-white">
                                    <option value="Activo" <?= ($registro['estado'] ?? '') == 'Activo' ? 'selected' : '' ?>>Activo</option>
                                    <option value="Inactivo" <?= ($registro['estado'] ?? '') == 'Inactivo' ? 'selected' : '' ?>>Inactivo</option>
                                    <option value="Suspendido" <?= ($registro['estado'] ?? '') == 'Suspendido' ? 'selected' : '' ?>>Suspendido</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Observaciones</label>
                                <textarea name="observaciones" rows="2"
                                    class="w-full px-4 py-3 border-2 border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none resize-none"
                                    placeholder="Detalles adicionales..."><?= htmlspecialchars($registro['observaciones'] ?? '') ?></textarea>
                            </div>
                        </div>

                        <div class="mt-4">
                            <label class="block text-sm font-bold text-slate-700 mb-2">Notas Adicionales de
                                Configuración</label>
                            <textarea name="notas_adicionales" rows="2"
                                class="w-full px-4 py-3 border-2 border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none resize-none"
                                placeholder="Configuraciones especiales, aplicaciones instaladas, etc..."><?= htmlspecialchars($registro['notas_adicionales'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-8 py-6 bg-slate-50 border-t border-slate-200 flex justify-end gap-3">
                    <a href="index.php?view=registros_365"
                        class="px-6 py-3 rounded-lg border-2 border-slate-300 text-slate-700 font-semibold hover:bg-slate-100 transition-all">
                        Cancelar
                    </a>
                    <button type="submit"
                        class="px-6 py-3 rounded-lg bg-blue-600 text-white font-bold hover:bg-blue-700 shadow-lg shadow-blue-500/30 transition-all flex items-center gap-2">
                        <i class="ri-save-line"></i>
                        Guardar
                    </button>
                </div>
        </form>
    </div>
</div>

<!-- Modal de Selección de Usuario -->
<div id="modalUserSelection" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog"
    aria-modal="true">
    <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity opacity-0" id="modal-user-backdrop">
    </div>

    <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                id="modal-user-panel">

                <div class="bg-white px-4 pb-4 pt-5 sm:p-6 cursor-default">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold text-slate-800">Seleccionar Empleado</h3>
                        <button type="button" onclick="closeUserModal()"
                            class="text-slate-400 hover:text-red-500 transition-colors">
                            <i class="ri-close-line text-2xl"></i>
                        </button>
                    </div>

                    <!-- Buscador -->
                    <div class="relative mb-4">
                        <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="text" id="modal_user_search"
                            class="w-full pl-10 pr-4 py-3 border-2 border-slate-100 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none text-sm transition-all bg-slate-50 focus:bg-white"
                            placeholder="Buscar por nombre o sucursal..." autocomplete="off">
                    </div>

                    <!-- Lista con Scroll -->
                    <div class="max-h-80 overflow-y-auto space-y-1 custom-scrollbar pr-1" id="modal_user_list">
                        <!-- Items se llenan con JS -->
                    </div>
                </div>

                <div class="bg-slate-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6 border-t border-slate-100">
                    <button type="button" onclick="selectUser('', 'Sin Asignar', 'Click para seleccionar empleado')"
                        class="inline-flex w-full justify-center rounded-lg bg-white px-3 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 sm:ml-3 sm:w-auto transition-colors">
                        Desasignar / Ninguno
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // --- Lógica Dependencia Empresa -> Sucursal (Existente, simplificada) ---
        const empresaSelect = document.getElementById('empresa_id');
        const sucursalSelect = document.getElementById('sucursal_id');

        // Guardar opciones originales
        const allOptions = Array.from(sucursalSelect.querySelectorAll('option'));

        function filterSucursales() {
            const empresaId = empresaSelect.value;
            const currentSucursalId = sucursalSelect.getAttribute('data-value') || sucursalSelect.value;

            // Limpiar
            sucursalSelect.innerHTML = '<option value="">-- Selecciona Sucursal --</option>';

            // Filtrar
            allOptions.forEach(opt => {
                // Si tiene valor y coincide empresa (o empresa vacía muestra todo/nada segun logica, aqui restringimos)
                if (opt.value && opt.getAttribute('data-empresa') == empresaId) {
                    sucursalSelect.add(opt.cloneNode(true));
                }
            });

            // Restaurar selección si existe en las nuevas opciones
            if (currentSucursalId) {
                // Verificar si existe en el nuevo set
                const exists = Array.from(sucursalSelect.options).some(o => o.value == currentSucursalId);
                if (exists) sucursalSelect.value = currentSucursalId;
            }
        }

        // Init
        sucursalSelect.setAttribute('data-value', sucursalSelect.value); // Guardar valor inicial PHP
        empresaSelect.addEventListener('change', filterSucursales);
        filterSucursales(); // Ejecutar al inicio


        // ---------------------------------------------------------
        // Lógica del MODAL DE USUARIOS (MEJORADO - INTENTO 2)
        // ---------------------------------------------------------
        
        // Datos de empleados
        const employees = <?= json_encode($empleados) ?>;
        
        // Colores para avatares
        const avatarColors = [
            'bg-blue-100 text-blue-600',
            'bg-green-100 text-green-600',
            'bg-purple-100 text-purple-600',
            'bg-orange-100 text-orange-600',
            'bg-pink-100 text-pink-600',
            'bg-cyan-100 text-cyan-600',
            'bg-emerald-100 text-emerald-600',
            'bg-indigo-100 text-indigo-600'
        ];

        function getAvatarColor(name) {
            let hash = 0;
            for (let i = 0; i < name.length; i++) {
                hash = name.charCodeAt(i) + ((hash << 5) - hash);
            }
            return avatarColors[Math.abs(hash) % avatarColors.length];
        }

        // Referencias DOM
        const modal = document.getElementById('modalUserSelection');
        const backdrop = document.getElementById('modal-user-backdrop');
        const panel = document.getElementById('modal-user-panel');
        const searchInput = document.getElementById('modal_user_search');
        const listContainer = document.getElementById('modal_user_list');
        
        const realInput = document.getElementById('usuario_id_real');
        const displayName = document.getElementById('display_user_name');
        const displayDetail = document.getElementById('display_user_detail');

        // Renderizar Lista (Con Smart Sort)
        function renderList(filter = '') {
            listContainer.innerHTML = '';
            
            // 1. Obtener contexto actual (Sucursal seleccionada)
            const sucursalSelect = document.getElementById('sucursal_id');
            const selectedSucursalText = sucursalSelect.options[sucursalSelect.selectedIndex]?.text || '';
            const currentSelectedId = realInput.value;

            // 2. Filtrar
            const lowerFilter = filter.toLowerCase();
            let filtered = employees.filter(emp => {
                const fullName = (emp.nombres + ' ' + emp.apellidos).toLowerCase();
                const sucursal = (emp.sucursal_nombre || '').toLowerCase();
                const cargo = (emp.cargo || '').toLowerCase();
                return fullName.includes(lowerFilter) || sucursal.includes(lowerFilter) || cargo.includes(lowerFilter);
            });

            // 3. Ordenar (Smart Sort)
            // Prioridad: 
            // 1. Ya seleccionado
            // 2. Coincide con Sucursal seleccionada
            // 3. Alfabético
            filtered.sort((a, b) => {
                // Check selection
                if (a.id == currentSelectedId) return -1;
                if (b.id == currentSelectedId) return 1;

                // Check Sucursal match
                const aMatch = (a.sucursal_nombre && selectedSucursalText.includes(a.sucursal_nombre));
                const bMatch = (b.sucursal_nombre && selectedSucursalText.includes(b.sucursal_nombre));
                
                if (aMatch && !bMatch) return -1;
                if (!aMatch && bMatch) return 1;

                return 0; // Maintain original alphabetical order
            });

            if (filtered.length === 0) {
               listContainer.innerHTML = '<div class="p-8 text-center text-slate-400 text-sm flex flex-col items-center gap-2"><i class="ri-user-unfollow-line text-2xl"></i><span>No se encontraron empleados.</span></div>';
               return; 
            }

            // 4. Render Layout
            // Agregar headers si hay ordenamiento por sucursal relevante y sin filtro de texto
            let showingRecommended = false;
            let showingOthers = false;

            filtered.forEach((emp, index) => {
                const isSelected = (emp.id == currentSelectedId);
                const isRecommended = (emp.sucursal_nombre && selectedSucursalText.includes(emp.sucursal_nombre));
                
                // Headers logicos (solo si no estamos buscando texto especifico, para no ensuciar resultados)
                if (filter === '') {
                    if (isRecommended && !showingRecommended && !isSelected) {
                        const header = document.createElement('div');
                        header.className = 'px-2 py-1 text-[10px] font-bold text-blue-500 uppercase tracking-wider mt-2 mb-1';
                        header.textContent = 'Sugeridos (Misma Sucursal)';
                        listContainer.appendChild(header);
                        showingRecommended = true;
                    } else if (!isRecommended && !isSelected && showingRecommended && !showingOthers) {
                        const header = document.createElement('div');
                        header.className = 'px-2 py-1 text-[10px] font-bold text-slate-400 uppercase tracking-wider mt-4 mb-1';
                        header.textContent = 'Otros Empleados';
                        listContainer.appendChild(header);
                        showingOthers = true;
                    }
                }

                const fullName = emp.nombres + ' ' + emp.apellidos;
                const cargo = emp.cargo || 'Sin Cargo';
                const sucursal = emp.sucursal_nombre || 'Sin Sucursal';
                const colorClass = getAvatarColor(fullName);
                
                const div = document.createElement('div');
                div.className = `p-3 rounded-xl cursor-pointer transition-all group flex items-center justify-between border ${isSelected ? 'bg-blue-50 border-blue-200 ring-1 ring-blue-200' : 'bg-white border-transparent hover:bg-slate-50 hover:border-slate-100'}`;
                div.onclick = () => selectUser(emp.id, fullName, cargo + ' • ' + sucursal);
                
                div.innerHTML = `
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full ${colorClass} flex items-center justify-center font-bold text-sm uppercase shadow-sm">
                            ${emp.nombres.charAt(0)}${emp.apellidos.charAt(0)}
                        </div>
                        <div>
                            <div class="font-bold text-slate-700 text-sm group-hover:text-blue-700 transition-colors flex items-center gap-2">
                                ${fullName}
                                ${isSelected ? '<span class="px-2 py-0.5 rounded-md bg-blue-100 text-blue-700 text-[10px]">Seleccionado</span>' : ''}
                            </div>
                            <div class="text-xs text-slate-500 flex items-center gap-1">
                                <span class="font-medium text-slate-600">${cargo}</span>
                                <span class="text-slate-300">•</span>
                                <span>${sucursal}</span>
                            </div>
                        </div>
                    </div>
                    ${isSelected ? 
                        '<i class="ri-checkbox-circle-fill text-blue-600 text-xl"></i>' : 
                        '<i class="ri-arrow-right-s-line text-slate-300 opacity-0 group-hover:opacity-100 transform group-hover:translate-x-1 transition-all"></i>'
                    }
                `;
                listContainer.appendChild(div);
            });
        }

        // Funciones Globales
        window.openUserModal = function() {
            modal.classList.remove('hidden');
            renderList(''); 
            searchInput.value = '';
            
            requestAnimationFrame(() => {
                backdrop.classList.remove('opacity-0');
                panel.classList.remove('opacity-0', 'translate-y-4', 'sm:translate-y-0', 'sm:scale-95');
                panel.classList.add('opacity-100', 'translate-y-0', 'sm:scale-100');
            });
            
            setTimeout(() => searchInput.focus(), 100);
        };

        window.closeUserModal = function() {
            backdrop.classList.add('opacity-0');
            panel.classList.remove('opacity-100', 'translate-y-0', 'sm:scale-100');
            panel.classList.add('opacity-0', 'translate-y-4', 'sm:translate-y-0', 'sm:scale-95');
            
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 300);
        };

        window.selectUser = function(id, name, detail) {
            realInput.value = id;
            
            if (id) {
                displayName.textContent = name;
                displayDetail.textContent = detail || 'Empleado Seleccionado';
                displayName.classList.add('text-blue-700');
                // Highlight UI container
                document.getElementById('user-selection-ui').classList.add('border-blue-200', 'bg-blue-50/30');
            } else {
                displayName.textContent = 'Sin Asignar';
                displayDetail.textContent = 'Click para seleccionar empleado';
                displayName.classList.remove('text-blue-700');
                document.getElementById('user-selection-ui').classList.remove('border-blue-200', 'bg-blue-50/30');
            }
            
            closeUserModal();
        };

        // Event Listeners
        searchInput.addEventListener('input', (e) => renderList(e.target.value));

        // Setup Inicial
        const initialId = realInput.value;
        if (initialId) {
            const emp = employees.find(e => e.id == initialId);
            if (emp) {
                displayName.textContent = emp.nombres + ' ' + emp.apellidos;
                displayDetail.textContent = (emp.cargo || 'Cargo') + ' • ' + (emp.sucursal_nombre || 'Sucursal');
                displayName.classList.add('text-blue-700');
                document.getElementById('user-selection-ui').classList.add('border-blue-200', 'bg-blue-50/30');
            }
        }
    });
</script>