<?php
// seccion_categorias.php
?>
<div class="p-6 flex-1 glass">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-slate-800 flex items-center gap-2">
            <i class="ri-price-tag-3-line text-purple-600"></i> Gestión de Categorías
        </h2>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Formulario de Creación -->
        <div class="md:col-span-1">
            <div class="bg-white rounded-2xl shadow-xl shadow-slate-200/50 border border-slate-100 p-6">
                <h3 class="text-lg font-bold text-slate-700 mb-4">Nueva Categoría</h3>
                <form method="POST" action="index.php?view=categorias">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="accion" value="crear_categoria">

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-slate-600 mb-1">Nombre de la Categoría</label>
                        <input type="text" name="nombre_categoria" required placeholder="Ej: Hardware, Redes..."
                            class="w-full px-4 py-2 rounded-lg border border-slate-200 focus:border-purple-500 focus:ring-2 focus:ring-purple-200 outline-none transition-all">
                    </div>

                    <button type="submit"
                        class="w-full bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded-lg transition-colors flex items-center justify-center gap-2">
                        <i class="ri-add-line"></i> Agregar Categoría
                    </button>
                </form>
            </div>

            <div class="mt-6 bg-blue-50 border border-blue-100 rounded-xl p-4">
                <div class="flex items-start gap-3">
                    <i class="ri-information-line text-blue-500 mt-0.5"></i>
                    <div class="text-sm text-blue-700">
                        <p class="font-bold mb-1">Nota Importante</p>
                        <p>Las categorías se utilizan para clasificar los tickets. Asegúrate de crear nombres claros y
                            descriptivos.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Listado de Categorías -->
        <div class="md:col-span-2">
            <div class="bg-white rounded-2xl shadow-xl shadow-slate-200/50 border border-slate-100 overflow-hidden">
                <div class="p-4 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
                    <h3 class="font-bold text-slate-700">Categorías Existentes</h3>
                    <span
                        class="text-xs font-semibold bg-slate-200 text-slate-600 px-2 py-1 rounded-full"><?php echo count($categorias); ?>
                        Total</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/50 border-b border-slate-100">
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">ID</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Nombre
                                </th>
                                <th
                                    class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">
                                    Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($categorias as $c): ?>
                                <tr class="hover:bg-slate-50/80 transition-colors group">
                                    <td class="px-6 py-4 text-sm text-slate-500 font-mono">
                                        #<?php echo str_pad($c['id'], 2, '0', STR_PAD_LEFT); ?></td>
                                    <td class="px-6 py-4">
                                        <div class="font-medium text-slate-800 flex items-center gap-2">
                                            <span class="w-2 h-2 rounded-full bg-purple-400"></span>
                                            <?php echo htmlspecialchars($c['nombre']); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <form method="POST" action="index.php?view=categorias"
                                            onsubmit="return confirm('¿Estás seguro de eliminar esta categoría?');"
                                            class="inline-block">
                                            <input type="hidden" name="csrf_token"
                                                value="<?php echo $_SESSION['csrf_token']; ?>">
                                            <input type="hidden" name="accion" value="eliminar_categoria">
                                            <input type="hidden" name="categoria_id" value="<?php echo $c['id']; ?>">
                                            <button type="submit"
                                                class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 transition-all"
                                                title="Eliminar">
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if (empty($categorias)): ?>
                    <div class="p-12 text-center">
                        <div
                            class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4 text-slate-400">
                            <i class="ri-price-tag-3-line text-3xl"></i>
                        </div>
                        <h3 class="text-slate-800 font-medium mb-1">No hay categorías</h3>
                        <p class="text-slate-500 text-sm">Comienza agregando una nueva categoría desde el formulario.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>