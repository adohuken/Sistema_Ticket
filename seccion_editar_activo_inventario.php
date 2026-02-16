<?php
/**
 * seccion_editar_activo_inventario.php - Formulario de Edición de Activos
 */

// Obtener el ID del activo a editar
$id_activo = $_GET['id'] ?? null;
$activo = null;

if ($id_activo) {
    $stmt = $pdo->prepare("SELECT * FROM inventario WHERE id = ?");
    $stmt->execute([$id_activo]);
    $activo = $stmt->fetch();
}

if (!$activo) {
    echo "<div class='p-6 text-red-500 font-bold'>Error: Activo no encontrado.</div>";
    return;
}
?>

<div class="p-6 flex-1">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center gap-2 text-sm text-slate-500 mb-2">
                <a href="index.php?view=inventario" class="hover:text-blue-600 transition-colors">Inventario</a>
                <i class="ri-arrow-right-s-line"></i>
                <span>Edición de Equipo</span>
            </div>
            <h1 class="text-3xl font-bold text-slate-800 flex items-center gap-3">
                <span
                    class="bg-gradient-to-br from-amber-500 to-orange-600 text-white p-3 rounded-xl shadow-lg shadow-amber-500/30">
                    <i class="ri-edit-box-line"></i>
                </span>
                Editar Activo
            </h1>
            <p class="text-slate-500 mt-2">Modifique los datos del equipo seleccionado</p>
        </div>

        <!-- SweetAlert2 CDN -->
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

        <!-- Mensaje de Error (si viene de index.php) -->
        <!-- Mensaje de Error (desde sesión) -->
        <?php if (!empty($_SESSION['error_accion'])):
            $texto_error = $_SESSION['error_accion'];
            unset($_SESSION['error_accion']); // Limpiar mensaje después de mostrar
            ?>
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    Swal.fire({
                        title: 'Error al Actualizar',
                        text: <?= json_encode($texto_error) ?>,
                        icon: 'error',
                        confirmButtonText: 'Entendido',
                        confirmButtonColor: '#ef4444'
                    });
                });
            </script>
        <?php endif; ?>

        <!-- Formulario Principal -->
        <form method="POST" action="index.php"
            class="bg-white rounded-2xl shadow-xl border border-slate-100 overflow-hidden">
            <input type="hidden" name="accion" value="actualizar_activo_inventario">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            <input type="hidden" name="id" value="<?= $activo['id'] ?>">
            <input type="hidden" name="view" value="editar_activo_inventario"> <!-- Para volver aquí si hay error -->

            <!-- Sección: Información del Equipo -->
            <div class="px-8 py-6 bg-gradient-to-r from-amber-50 to-orange-50 border-b border-slate-100">
                <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                    <i class="ri-information-line text-amber-600"></i>
                    Información del Equipo
                </h2>
            </div>

            <div class="p-8 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Tipo de Equipo -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">
                            Tipo de Equipo <span class="text-red-500">*</span>
                        </label>
                        <select name="tipo" required
                            class="w-full px-4 py-3 border-2 border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none bg-white transition-all">
                            <option value="">Seleccionar tipo...</option>
                            <option value="Laptop" <?= $activo['tipo'] === 'Laptop' ? 'selected' : '' ?>>💻 Laptop</option>
                            <option value="PC" <?= $activo['tipo'] === 'PC' ? 'selected' : '' ?>>🖥️ PC Escritorio</option>
                            <option value="Monitor" <?= $activo['tipo'] === 'Monitor' ? 'selected' : '' ?>>🖥️ Monitor
                            </option>
                            <option value="Movil" <?= $activo['tipo'] === 'Movil' ? 'selected' : '' ?>>📱 Celular / Tablet
                            </option>
                            <option value="Teclado" <?= $activo['tipo'] === 'Teclado' ? 'selected' : '' ?>>⌨️ Teclado
                            </option>
                            <option value="Mouse" <?= $activo['tipo'] === 'Mouse' ? 'selected' : '' ?>>🖱️ Mouse</option>
                            <option value="Headset" <?= $activo['tipo'] === 'Headset' ? 'selected' : '' ?>>🎧 Audífonos
                            </option>
                            <option value="Silla" <?= $activo['tipo'] === 'Silla' ? 'selected' : '' ?>>🪑 Silla</option>
                            <option value="Escritorio" <?= $activo['tipo'] === 'Escritorio' ? 'selected' : '' ?>>🪑
                                Escritorio</option>
                            <option value="Impresora" <?= $activo['tipo'] === 'Impresora' ? 'selected' : '' ?>>🖨️
                                Impresora / Escáner</option>
                            <option value="Otro" <?= $activo['tipo'] === 'Otro' ? 'selected' : '' ?>>📦 Otro</option>
                        </select>
                    </div>

                    <!-- Estado Físico -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">
                            Estado Físico <span class="text-red-500">*</span>
                        </label>
                        <select name="estado" required
                            class="w-full px-4 py-3 border-2 border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none bg-white transition-all">
                            <option value="Nuevo" <?= $activo['estado'] === 'Nuevo' ? 'selected' : '' ?>>✨ Nuevo</option>
                            <option value="Buen Estado" <?= $activo['estado'] === 'Buen Estado' ? 'selected' : '' ?>>👍
                                Bueno</option>
                            <option value="Regular" <?= $activo['estado'] === 'Regular' ? 'selected' : '' ?>>😐 Regular
                            </option>
                            <option value="Malo" <?= $activo['estado'] === 'Malo' ? 'selected' : '' ?>>👎 Malo</option>
                            <option value="En Reparacion" <?= $activo['estado'] === 'En Reparacion' ? 'selected' : '' ?>>🔧
                                En Reparación
                            </option>
                        </select>
                    </div>

                    <!-- Marca -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">
                            Marca <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="marca" required value="<?= htmlspecialchars($activo['marca']) ?>"
                            placeholder="Ej: Dell, HP, Samsung"
                            class="w-full px-4 py-3 border-2 border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
                    </div>

                    <!-- Modelo -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">
                            Modelo <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="modelo" required value="<?= htmlspecialchars($activo['modelo']) ?>"
                            placeholder="Ej: Latitude 7490, ProBook 450"
                            class="w-full px-4 py-3 border-2 border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
                    </div>

                    <!-- Serial -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">
                            Serial / Código de Activo <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="serial" required value="<?= htmlspecialchars($activo['serial']) ?>"
                            placeholder="Identificador único del equipo"
                            class="w-full px-4 py-3 border-2 border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
                        <p class="text-xs text-slate-400 mt-1"><i class="ri-information-line"></i> Este código debe ser
                            único</p>
                    </div>

                    <!-- SKU -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">
                            SKU / Código Sistema <span class="text-slate-400 font-normal">(Opcional)</span>
                        </label>
                        <input type="text" name="sku" value="<?= htmlspecialchars($activo['sku'] ?? '') ?>"
                            placeholder="Ej: SKU-0012345"
                            class="w-full px-4 py-3 border-2 border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
                    </div>

                    <!-- Condición (Disponible/Asignado) -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">
                            Estatus Actual
                        </label>
                        <div
                            class="px-4 py-3 bg-slate-50 border border-slate-200 rounded-lg text-slate-600 font-medium">
                            <?= $activo['condicion'] ?>
                            <?php if ($activo['condicion'] === 'Asignado'): ?>
                                <span
                                    class="text-xs text-slate-500 ml-2">(<?= htmlspecialchars($activo['asignado_a']) ?>)</span>
                            <?php endif; ?>
                        </div>
                        <p class="text-xs text-slate-400 mt-1">El estatus cambia automáticamente al asignar/devolver
                            equipos.</p>
                    </div>

                </div>
            </div>

            <!-- Sección: Especificaciones Técnicas (Solo IT) -->
            <div class="px-8 py-6 bg-gradient-to-r from-blue-50 to-indigo-50 border-b border-slate-100 border-t">
                <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                    <i class="ri-cpu-line text-blue-600"></i>
                    Especificaciones Técnicas (IT)
                </h2>
            </div>

            <div class="p-8 space-y-6 bg-slate-50/50">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Procesador</label>
                        <input type="text" name="procesador"
                            value="<?= htmlspecialchars($activo['procesador'] ?? '') ?>"
                            placeholder="Ej: Intel Core i5-1135G7"
                            class="w-full px-4 py-3 border-2 border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none bg-white">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Memoria RAM</label>
                        <input type="text" name="ram" value="<?= htmlspecialchars($activo['ram'] ?? '') ?>"
                            placeholder="Ej: 16GB DDR4"
                            class="w-full px-4 py-3 border-2 border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none bg-white">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Almacenamiento</label>
                        <input type="text" name="disco_duro"
                            value="<?= htmlspecialchars($activo['disco_duro'] ?? '') ?>"
                            placeholder="Ej: 512GB SSD NVMe"
                            class="w-full px-4 py-3 border-2 border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none bg-white">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Sistema Operativo</label>
                        <input type="text" name="sistema_operativo"
                            value="<?= htmlspecialchars($activo['sistema_operativo'] ?? '') ?>"
                            placeholder="Ej: Windows 10 Pro"
                            class="w-full px-4 py-3 border-2 border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none bg-white">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">AnyDesk ID</label>
                        <input type="text" name="anydesk_id"
                            value="<?= htmlspecialchars($activo['anydesk_id'] ?? '') ?>" placeholder="Ej: 123 456 789"
                            class="w-full px-4 py-3 border-2 border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none bg-white">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Dirección IP</label>
                        <input type="text" name="ip_address"
                            value="<?= htmlspecialchars($activo['ip_address'] ?? '') ?>" placeholder="Ej: 192.168.1.105"
                            class="w-full px-4 py-3 border-2 border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none bg-white font-mono">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Dirección MAC</label>
                        <input type="text" name="mac_address"
                            value="<?= htmlspecialchars($activo['mac_address'] ?? '') ?>"
                            placeholder="Ej: 00:1A:2B:3C:4D:5E"
                            class="w-full px-4 py-3 border-2 border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none bg-white font-mono">
                    </div>
                </div>
            </div>

            <!-- Botones de Acción -->
            <div class="px-8 py-6 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-4">
                <a href="index.php?view=inventario"
                    class="px-6 py-3 rounded-xl border border-slate-300 text-slate-700 font-bold hover:bg-slate-100 transition-all">
                    Cancelar
                </a>
                <button type="submit"
                    class="px-8 py-3 rounded-xl bg-gradient-to-r from-amber-500 to-orange-600 text-white font-bold shadow-lg shadow-amber-500/30 hover:shadow-amber-500/50 hover:scale-[1.02] transition-all flex items-center gap-2">
                    <i class="ri-save-line text-xl"></i>
                    Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>