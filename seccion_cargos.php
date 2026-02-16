<?php
/**
 * seccion_cargos.php - Gestión de Cargos/Puestos
 */

// Obtener todos los cargos
$cargos = $pdo->query("SELECT * FROM cargos ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="p-6 flex-1">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-800 flex items-center gap-3">
                <span class="bg-gradient-to-br from-indigo-600 to-purple-600 text-white p-3 rounded-xl shadow-lg">
                    <i class="ri-briefcase-4-line"></i>
                </span>
                Gestión de Cargos
            </h1>
            <p class="text-slate-500 mt-2">Administra los cargos/puestos disponibles en el sistema.</p>
        </div>

        <!-- Formulario Nuevo Cargo -->
        <div class="bg-white rounded-xl shadow-lg border border-slate-200 p-6 mb-6">
            <h3 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
                <i class="ri-add-circle-line text-green-500"></i>
                Agregar Nuevo Cargo
            </h3>
            <form method="POST" action="index.php?view=cargos" class="flex flex-col md:flex-row gap-4">
                <input type="hidden" name="accion" value="crear_cargo">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <input type="hidden" name="view" value="cargos">

                <div class="flex-1">
                    <input type="text" name="nombre" required
                        class="w-full px-4 py-3 border-2 border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
                        placeholder="Nombre del cargo (ej: Gerente de Ventas)">
                </div>
                <div class="flex-1">
                    <input type="text" name="descripcion"
                        class="w-full px-4 py-3 border-2 border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
                        placeholder="Descripción (opcional)">
                </div>
                <button type="submit"
                    class="px-6 py-3 bg-indigo-600 text-white font-bold rounded-lg hover:bg-indigo-700 shadow-lg transition-all flex items-center gap-2">
                    <i class="ri-add-line"></i> Agregar
                </button>
            </form>
        </div>

        <!-- Tabla de Cargos -->
        <div class="bg-white rounded-xl shadow-lg border border-slate-200 overflow-hidden">
            <div class="p-4 border-b border-slate-200 bg-slate-50 flex justify-between items-center">
                <h3 class="font-bold text-slate-700 flex items-center gap-2">
                    <i class="ri-list-check text-indigo-500"></i>
                    Cargos Registrados
                </h3>
                <span
                    class="px-3 py-1 bg-indigo-100 text-indigo-700 text-xs font-bold rounded-full border border-indigo-200">
                    <?= count($cargos) ?> Registros
                </span>
            </div>

            <?php if (empty($cargos)): ?>
                <div class="p-8 text-center text-slate-500">
                    <i class="ri-folder-open-line text-4xl text-slate-300 mb-2"></i>
                    <p>No hay cargos registrados. Agrega el primero arriba.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600">
                        <thead class="bg-slate-50 border-b border-slate-200 text-xs uppercase font-bold text-slate-500">
                            <tr>
                                <th class="px-6 py-3">Nombre del Cargo</th>
                                <th class="px-6 py-3">Descripción</th>
                                <th class="px-6 py-3 text-center">Estado</th>
                                <th class="px-6 py-3 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            <?php foreach ($cargos as $cargo): ?>
                                <tr class="hover:bg-slate-50 transition-colors group">
                                    <td class="px-6 py-4 font-semibold text-slate-800">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="w-8 h-8 rounded bg-indigo-50 text-indigo-600 flex items-center justify-center">
                                                <i class="ri-briefcase-line"></i>
                                            </div>
                                            <?= htmlspecialchars($cargo['nombre']) ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-slate-500">
                                        <?= htmlspecialchars($cargo['descripcion'] ?: '-') ?>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <?php if ($cargo['activo']): ?>
                                            <span
                                                class="px-2 py-1 bg-emerald-100 text-emerald-700 text-xs font-bold rounded-full border border-emerald-200">
                                                Activo
                                            </span>
                                        <?php else: ?>
                                            <span
                                                class="px-2 py-1 bg-slate-100 text-slate-500 text-xs font-bold rounded-full border border-slate-200">
                                                Inactivo
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-2 transition-opacity">
                                            <!-- Toggle Activo -->
                                            <form method="POST" action="index.php?view=cargos" class="inline">
                                                <input type="hidden" name="accion" value="toggle_cargo">
                                                <input type="hidden" name="csrf_token"
                                                    value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                                <input type="hidden" name="id" value="<?= $cargo['id'] ?>">
                                                <input type="hidden" name="activo" value="<?= $cargo['activo'] ? 0 : 1 ?>">
                                                <input type="hidden" name="view" value="cargos">
                                                <button type="submit" title="<?= $cargo['activo'] ? 'Desactivar' : 'Activar' ?>"
                                                    class="p-1.5 rounded-lg <?= $cargo['activo'] ? 'text-amber-600 hover:bg-amber-50' : 'text-emerald-600 hover:bg-emerald-50' ?> transition-colors border border-transparent hover:border-slate-200">
                                                    <i class="ri-<?= $cargo['activo'] ? 'eye-off' : 'eye' ?>-line text-lg"></i>
                                                </button>
                                            </form>
                                            <!-- Eliminar -->
                                            <form id="formEliminarCargo_<?= $cargo['id'] ?>" method="POST"
                                                action="index.php?view=cargos" class="inline"
                                                onsubmit="confirmarEliminacionCargo(event, <?= $cargo['id'] ?>, '<?= htmlspecialchars($cargo['nombre']) ?>')">
                                                <input type="hidden" name="accion" value="eliminar_cargo">
                                                <input type="hidden" name="csrf_token"
                                                    value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                                <input type="hidden" name="id" value="<?= $cargo['id'] ?>">
                                                <input type="hidden" name="view" value="cargos">
                                                <button type="submit" title="Eliminar"
                                                    class="p-1.5 rounded-lg text-red-600 hover:bg-red-50 transition-colors border border-transparent hover:border-red-100">
                                                    <i class="ri-delete-bin-line text-lg"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    function confirmarEliminacionCargo(event, id, nombre) {
        event.preventDefault();

        Swal.fire({
            title: '¿Eliminar Cargo?',
            html: `
                <div class="text-left bg-slate-50 p-4 rounded-lg border border-slate-200 mt-2">
                    <p class="font-bold text-slate-800 mb-1">Cargo: ${nombre}</p>
                    <p class="text-sm text-slate-600">Al eliminar este cargo, ya no estará disponible para nuevos usuarios.</p>
                </div>
                <p class="mt-4 text-xs text-red-500 font-bold">Esta acción no se puede deshacer.</p>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#94a3b8',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            reverseButtons: true,
            focusCancel: true,
            customClass: {
                popup: 'rounded-2xl shadow-xl'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('formEliminarCargo_' + id).submit();
            }
        });
    }
</script>