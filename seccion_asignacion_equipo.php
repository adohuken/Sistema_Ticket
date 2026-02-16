<?php
/**
 * seccion_asignacion_equipo.php - Formulario de Registro de Activos
 */
?>

<div class="p-6 flex-1">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center gap-2 text-sm text-slate-500 mb-2">
                <a href="index.php?view=inventario" class="hover:text-blue-600 transition-colors">Inventario</a>
                <i class="ri-arrow-right-s-line"></i>
                <span>Asignación de Equipo</span>
            </div>
            <h1 class="text-3xl font-bold text-slate-800 flex items-center gap-3">
                <span
                    class="bg-gradient-to-br from-blue-600 to-indigo-600 text-white p-3 rounded-xl shadow-lg shadow-blue-500/30">
                    <i class="ri-add-box-line"></i>
                </span>
                Registrar Nuevo Activo
            </h1>
            <p class="text-slate-500 mt-2">Complete el formulario para añadir un nuevo equipo al inventario</p>
        </div>

        <!-- Mensaje de Error (si viene de index.php) -->
        <?php if (!empty($mensaje_accion) && strpos($mensaje_accion, 'Error') !== false): ?>
            <?= $mensaje_accion ?>
        <?php endif; ?>

        <!-- Formulario Principal -->
        <form method="POST" action="index.php"
            class="bg-white rounded-2xl shadow-xl border border-slate-100 overflow-hidden">
            <input type="hidden" name="accion" value="guardar_activo_inventario">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            <input type="hidden" name="view" value="asignacion_equipo">
            <!-- Para volver aquí si hay error de validación simple, aunque el éxito redirige a inventario -->

            <!-- Sección: Información del Equipo -->
            <div class="px-8 py-6 bg-gradient-to-r from-blue-50 to-indigo-50 border-b border-slate-100">
                <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                    <i class="ri-information-line text-blue-600"></i>
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
                            <option value="Laptop">💻 Laptop</option>
                            <option value="PC">🖥️ PC Escritorio</option>
                            <option value="Monitor">🖥️ Monitor</option>
                            <option value="Movil">📱 Celular / Tablet</option>
                            <option value="Teclado">⌨️ Teclado</option>
                            <option value="Mouse">🖱️ Mouse</option>
                            <option value="Headset">🎧 Audífonos</option>
                            <option value="Silla">🪑 Silla</option>
                            <option value="Escritorio">🪑 Escritorio</option>
                            <option value="Impresora">🖨️ Impresora / Escáner</option>
                            <option value="Otro">📦 Otro</option>
                        </select>
                    </div>

                    <!-- Estado Físico -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">
                            Estado Físico <span class="text-red-500">*</span>
                        </label>
                        <select name="estado" required
                            class="w-full px-4 py-3 border-2 border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none bg-white transition-all">
                            <option value="Nuevo">✨ Nuevo</option>
                            <option value="Buen Estado">✅ Buen Estado</option>
                            <option value="Regular">⚠️ Regular</option>
                            <option value="En Reparacion">🔧 En Reparación</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Marca -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">
                            Marca <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="marca" required
                            class="w-full px-4 py-3 border-2 border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all"
                            placeholder="Ej: Dell, HP, Samsung">
                    </div>

                    <!-- Modelo -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">
                            Modelo <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="modelo" required
                            class="w-full px-4 py-3 border-2 border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all"
                            placeholder="Ej: Latitude 7490, ProBook 450">
                    </div>
                </div>

                <!-- Serial y SKU -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Serial / Código -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">
                            Serial / Código de Activo <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="serial" required
                            class="w-full px-4 py-3 border-2 border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all font-mono"
                            placeholder="Identificador único del equipo">
                        <p class="text-xs text-slate-500 mt-1 flex items-center gap-1">
                            <i class="ri-information-line"></i>
                            Este código debe ser único
                        </p>
                    </div>

                    <!-- SKU -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">
                            SKU / Código Sistema <span class="text-slate-400 font-normal">(Opcional)</span>
                        </label>
                        <input type="text" name="sku"
                            class="w-full px-4 py-3 border-2 border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all font-mono"
                            placeholder="Ej: SKU-0012345">
                    </div>
                </div>
            </div>

            <!-- Botones de Acción -->
            <div class="px-8 py-6 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-3">
                <a href="index.php?view=inventario"
                    class="px-6 py-3 rounded-lg border-2 border-slate-300 text-slate-700 font-semibold hover:bg-slate-100 transition-all">
                    <i class="ri-close-line"></i> Cancelar
                </a>
                <button type="submit"
                    class="px-6 py-3 rounded-lg bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-bold hover:from-blue-700 hover:to-indigo-700 shadow-lg shadow-blue-500/30 transition-all flex items-center gap-2">
                    <i class="ri-save-line"></i> Guardar Activo
                </button>
            </div>
        </form>

        <!-- Información Adicional -->
        <div class="mt-6 bg-blue-50 border-2 border-blue-200 rounded-xl p-5">
            <div class="flex gap-3">
                <i class="ri-lightbulb-line text-blue-600 text-2xl flex-shrink-0"></i>
                <div class="text-sm text-blue-900">
                    <p class="font-bold mb-2">Consejos para el registro:</p>
                    <ul class="list-disc list-inside space-y-1 text-blue-800">
                        <li>Verifica que el serial/código sea único y esté correctamente escrito</li>
                        <li>El equipo se registrará como "Disponible" por defecto</li>
                        <li>Podrás asignarlo a un colaborador desde el módulo de Gestión de Personal</li>
                        <li>Después de guardar, serás redirigido al inventario para ver el equipo registrado</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>