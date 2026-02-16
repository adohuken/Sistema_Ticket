<?php
/**
 * seccion_gestion_usuarios.php - Gestión de Usuarios del Sistema
 * Muestra tabla de usuarios con opciones de crear, editar y eliminar
 */
?>

<div class="p-6 flex-1">
    <!-- Encabezado -->
    <div
        class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 mb-8 flex flex-col md:flex-row items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-800 flex items-center gap-3">
                <span class="p-2 bg-blue-100 text-blue-600 rounded-lg">
                    <i class="ri-team-line text-xl"></i>
                </span>
                Gestión de Usuarios
            </h2>
            <p class="text-slate-500 text-sm mt-1 ml-12">Administra los usuarios del sistema</p>
        </div>
        <a href="index.php?view=crear_usuario"
            class="group px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl transition-all shadow-lg shadow-blue-600/30 hover:shadow-blue-600/40 flex items-center gap-2">
            <i class="ri-user-add-line text-lg group-hover:rotate-12 transition-transform"></i>
            <span>Nuevo Usuario</span>
        </a>
    </div>

    <!-- Barra de Búsqueda y Filtros -->
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Búsqueda -->
            <div class="md:col-span-2">
                <div class="relative">
                    <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg"></i>
                    <input type="text" id="busquedaUsuarios" placeholder="Buscar por nombre o email..."
                        class="w-full pl-10 pr-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                        onkeyup="filtrarTabla()">
                </div>
            </div>

            <!-- Filtro por Rol -->
            <div>
                <select id="filtroRol" onchange="filtrarTabla()"
                    class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                    <option value="">Todos los roles</option>
                    <option value="SuperAdmin">SuperAdmin</option>
                    <option value="Admin">Admin</option>
                    <option value="RRHH">RRHH</option>
                    <option value="Gerencia">Gerencia</option>
                    <option value="Tecnico">Técnico</option>
                    <option value="Usuario">Usuario</option>
                </select>
            </div>

            <!-- Botón Limpiar Filtros -->
            <div>
                <button onclick="limpiarFiltros()"
                    class="w-full px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-lg transition-all flex items-center justify-center gap-2">
                    <i class="ri-refresh-line"></i>
                    Limpiar
                </button>
            </div>
        </div>

        <!-- Contador de resultados -->
        <div class="mt-4 flex items-center justify-between text-sm">
            <span id="contadorResultados" class="text-slate-600"></span>
            <span id="estadoFiltro" class="text-slate-500"></span>
        </div>
    </div>

    <!-- Tabla de Usuarios -->
    <div class="bg-white rounded-2xl shadow-xl shadow-slate-200/50 border border-slate-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gradient-to-r from-slate-50 to-slate-100 border-b border-slate-200">
                        <th class="px-6 py-4 text-xs font-bold text-slate-600 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-600 uppercase tracking-wider">Nombre</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-600 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-600 uppercase tracking-wider">Rol</th>
                        <th class="px-6 py-4 text-xs font-bold text-slate-600 uppercase tracking-wider text-center">
                            Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($usuarios)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-slate-500">
                                <i class="ri-user-search-line text-4xl mb-2 block text-slate-300"></i>
                                No hay usuarios registrados
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($usuarios as $u): ?>
                            <tr class="hover:bg-slate-50 transition-colors group">
                                <td class="px-6 py-4">
                                    <span
                                        class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-blue-100 text-blue-600 font-bold text-sm">
                                        <?php echo $u['id']; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-purple-500 flex items-center justify-center text-white font-bold">
                                            <?php echo strtoupper(substr($u['nombre'], 0, 1)); ?>
                                        </div>
                                        <span
                                            class="font-semibold text-slate-700"><?php echo htmlspecialchars($u['nombre']); ?></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-slate-600">
                                    <i class="ri-mail-line text-slate-400 mr-1"></i>
                                    <?php echo htmlspecialchars($u['email']); ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php
                                    $rol_colors = [
                                        'SuperAdmin' => 'bg-purple-100 text-purple-700 border-purple-200',
                                        'Admin' => 'bg-blue-100 text-blue-700 border-blue-200',
                                        'RRHH' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                                        'Tecnico' => 'bg-amber-100 text-amber-700 border-amber-200',
                                        'Usuario' => 'bg-slate-100 text-slate-700 border-slate-200'
                                    ];
                                    $rol_class = $rol_colors[$u['rol']] ?? 'bg-slate-100 text-slate-700 border-slate-200';
                                    ?>
                                    <span
                                        class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold border <?php echo $rol_class; ?>">
                                        <i class="ri-shield-user-line mr-1"></i>
                                        <?php echo htmlspecialchars($u['rol']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-center gap-2">
                                        <a href="index.php?view=editar_usuario&id=<?php echo $u['id']; ?>"
                                            class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-all"
                                            title="Editar Usuario">
                                            <i class="ri-pencil-line text-lg"></i>
                                        </a>

                                        <?php if ($u['rol'] === 'RRHH'): ?>
                                            <a href="asignar_empresa_usuario.php?usuario_id=<?php echo $u['id']; ?>"
                                                class="p-2 text-teal-600 hover:bg-teal-50 rounded-lg transition-all"
                                                title="Asignar Empresa">
                                                <i class="ri-building-4-line text-lg"></i>
                                            </a>
                                        <?php endif; ?>

                                        <button
                                            onclick="confirmarEliminar(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars($u['nombre'], ENT_QUOTES); ?>')"
                                            class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-all"
                                            title="Eliminar Usuario">
                                            <i class="ri-delete-bin-line text-lg"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    // Función de filtrado en tiempo real
    function filtrarTabla() {
        const busqueda = document.getElementById('busquedaUsuarios').value.toLowerCase();
        const filtroRol = document.getElementById('filtroRol').value.toLowerCase();
        const tabla = document.querySelector('tbody');
        const filas = tabla.getElementsByTagName('tr');

        let visibles = 0;
        let total = 0;

        for (let i = 0; i < filas.length; i++) {
            const fila = filas[i];

            // Saltar fila de "no hay usuarios"
            if (fila.querySelector('td[colspan]')) continue;

            total++;

            const nombre = fila.cells[1].textContent.toLowerCase();
            const email = fila.cells[2].textContent.toLowerCase();
            const rol = fila.cells[3].textContent.toLowerCase();

            const coincideBusqueda = nombre.includes(busqueda) || email.includes(busqueda);
            const coincideRol = filtroRol === '' || rol.includes(filtroRol);

            if (coincideBusqueda && coincideRol) {
                fila.style.display = '';
                visibles++;

                // Animación de entrada
                fila.style.animation = 'fadeIn 0.3s ease-in';
            } else {
                fila.style.display = 'none';
            }
        }

        actualizarContador(visibles, total);
        actualizarEstadoFiltro(busqueda, filtroRol);
    }

    function actualizarContador(visibles, total) {
        const contador = document.getElementById('contadorResultados');
        if (visibles === total) {
            contador.innerHTML = `<i class="ri-user-line mr-1"></i>Mostrando <strong>${total}</strong> usuario(s)`;
        } else {
            contador.innerHTML = `<i class="ri-filter-line mr-1"></i>Mostrando <strong>${visibles}</strong> de <strong>${total}</strong> usuario(s)`;
        }
    }

    function actualizarEstadoFiltro(busqueda, rol) {
        const estado = document.getElementById('estadoFiltro');
        const filtros = [];

        if (busqueda) {
            filtros.push(`Búsqueda: "${busqueda}"`);
        }
        if (rol) {
            filtros.push(`Rol: ${rol}`);
        }

        if (filtros.length > 0) {
            estado.innerHTML = `<i class="ri-filter-2-line mr-1"></i>${filtros.join(' | ')}`;
            estado.classList.add('text-blue-600', 'font-semibold');
        } else {
            estado.innerHTML = '';
            estado.classList.remove('text-blue-600', 'font-semibold');
        }
    }

    function limpiarFiltros() {
        document.getElementById('busquedaUsuarios').value = '';
        document.getElementById('filtroRol').value = '';
        filtrarTabla();

        // Animación de limpieza
        const inputs = [document.getElementById('busquedaUsuarios'), document.getElementById('filtroRol')];
        inputs.forEach(input => {
            input.style.animation = 'pulse 0.3s ease-in-out';
            setTimeout(() => {
                input.style.animation = '';
            }, 300);
        });
    }

    function confirmarEliminar(id, nombre) {
        if (confirm(`¿Estás seguro de eliminar al usuario "${nombre}"?\n\nEsta acción no se puede deshacer.`)) {
            // Crear formulario dinámico para enviar POST
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'index.php?view=usuarios';

            const accion = document.createElement('input');
            accion.type = 'hidden';
            accion.name = 'accion';
            accion.value = 'eliminar_usuario';

            const userId = document.createElement('input');
            userId.type = 'hidden';
            userId.name = 'usuario_id';
            userId.value = id;

            const csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = 'csrf_token';
            csrf.value = '<?php echo generar_csrf_token(); ?>';

            form.appendChild(accion);
            form.appendChild(userId);
            form.appendChild(csrf);
            document.body.appendChild(form);
            form.submit();
        }
    }

    // Inicializar contador al cargar la página
    document.addEventListener('DOMContentLoaded', function () {
        // Verificar parámetros URL para pre-filtrar
        const urlParams = new URLSearchParams(window.location.search);
        const rolParam = urlParams.get('rol');

        if (rolParam) {
            const selectRol = document.getElementById('filtroRol');
            if (selectRol) {
                // Iterar para encontrar coincidencia exacta (case-sensitive o no, los values son exactos)
                for (let i = 0; i < selectRol.options.length; i++) {
                    if (selectRol.options[i].value === rolParam) {
                        selectRol.value = rolParam;
                        break;
                    }
                }
            }
        }

        filtrarTabla();

        // Agregar animación de entrada a las filas
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(-10px); }
                to { opacity: 1; transform: translateY(0); }
            }
            @keyframes pulse {
                0%, 100% { transform: scale(1); }
                50% { transform: scale(1.05); }
            }
        `;
        document.head.appendChild(style);
    });

    // Atajo de teclado: Ctrl+F para enfocar búsqueda
    document.addEventListener('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
            e.preventDefault();
            document.getElementById('busquedaUsuarios').focus();
        }
    });
</script>