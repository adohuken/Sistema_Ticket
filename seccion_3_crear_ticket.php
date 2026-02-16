<?php
/**
 * seccion_3_crear_ticket.php - Rediseño Moderno & Minimalista
 */
?>

<div class="max-w-7xl mx-auto my-6 md:my-10 animate-fade-in px-4 md:px-6">

    <!-- Header Flotante -->
    <div class="flex items-center gap-3 mb-6">
        <div
            class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-600 to-indigo-600 flex items-center justify-center shadow-lg shadow-blue-600/20 text-white">
            <i class="ri-add-line text-2xl"></i>
        </div>
        <div>
            <h2 class="text-2xl font-bold text-slate-800 tracking-tight">Nueva Solicitud</h2>
            <p class="text-slate-500 text-sm">Describe tu problema y lo resolveremos pronto</p>
        </div>
    </div>

    <!-- Card Principal -->
    <div class="bg-white rounded-3xl shadow-xl shadow-slate-200/60 border border-slate-100">

        <form action="index.php?view=crear_ticket" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generar_csrf_token(); ?>">

            <div class="grid grid-cols-1 lg:grid-cols-12 divide-y lg:divide-y-0 lg:divide-x divide-slate-100">

                <!-- COLUMNA IZQUIERDA: Contenido Principal (Inputs) -->
                <div class="lg:col-span-8 p-6 md:p-8 space-y-8">

                    <!-- Asunto -->
                    <div class="group">
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2 ml-1">Asunto
                            de la solicitud <span class="text-red-500">*</span></label>
                        <div
                            class="relative transition-all duration-300 transform group-hover:-translate-y-1 group-focus-within:-translate-y-1">
                            <input type="text" name="titulo_ticket" required placeholder="Ej: Error al conectar VPN..."
                                class="w-full pl-5 pr-4 py-4 text-lg font-medium text-slate-800 bg-slate-50 border border-transparent rounded-2xl focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 outline-none transition-all placeholder:text-slate-400">
                            <div class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
                                <i class="ri-edit-2-line"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Descripción -->
                    <div class="group">
                        <div class="flex justify-between items-center mb-2 ml-1">
                            <label class="text-xs font-bold text-slate-400 uppercase tracking-wider">Descripción
                                Detallada <span class="text-red-500">*</span></label>
                            <span id="charCount"
                                class="text-xs font-medium text-slate-400 bg-slate-100 px-2 py-1 rounded-md">0 /
                                500</span>
                        </div>
                        <div class="relative flex-1">
                            <textarea name="descripcion_ticket" rows="8" required maxlength="500"
                                oninput="updateCharCount(this)"
                                placeholder="Describe el problema con el mayor detalle posible. Incluye mensajes de error si aparecen..."
                                class="w-full p-5 text-base text-slate-600 bg-slate-50 border border-transparent rounded-2xl focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 outline-none transition-all resize-none leading-relaxed placeholder:text-slate-400 min-h-[250px]"></textarea>
                        </div>
                    </div>

                    <!-- Botones -->
                    <div class="flex items-center gap-4 pt-4 mt-8 border-t border-slate-100 relative z-10">
                        <button type="submit"
                            class="flex-1 py-4 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-xl shadow-lg shadow-slate-900/20 transition-all transform active:scale-95 flex items-center justify-center gap-2 group">
                            <span>Crear Solicitud</span>
                            <i class="ri-send-plane-fill group-hover:translate-x-1 transition-transform"></i>
                        </button>
                        <button type="button" onclick="window.history.back()"
                            class="px-8 py-4 bg-white text-slate-500 font-bold rounded-xl border border-slate-200 hover:bg-slate-50 transition-colors">
                            Cancelar
                        </button>
                    </div>
                </div>

                <!-- COLUMNA DERECHA: Metadatos (Selectores) -->
                <div class="lg:col-span-4 bg-slate-50/50 p-6 md:p-8 flex flex-col gap-8">

                    <!-- Categorías -->
                    <div>
                        <label class="flex items-center gap-2 text-sm font-bold text-slate-700 mb-4">
                            <span
                                class="w-6 h-6 rounded bg-indigo-100 text-indigo-600 flex items-center justify-center text-xs"><i
                                    class="ri-folder-line"></i></span>
                            Categoría <span class="text-red-400">*</span>
                        </label>

                        <div class="grid grid-cols-2 gap-3">
                            <?php
                            $cat_icons = [
                                'Hardware' => 'ri-cpu-line',
                                'Software' => 'ri-code-line',
                                'Red' => 'ri-router-line',
                                'Cuentas' => 'ri-shield-user-line',
                                'Correo' => 'ri-mail-send-line',
                                'Impresoras' => 'ri-printer-line',
                                'General' => 'ri-folder-open-line'
                            ];

                            // Si no hay categorías, mostrar default
                            if (!isset($categorias) || !is_array($categorias)) {
                                $categorias = [['id' => 1, 'nombre' => 'General']];
                            }

                            foreach ($categorias as $c):
                                $icon = $cat_icons[$c['nombre']] ?? 'ri-folder-line'; // Fallback icon
                                ?>
                                <label class="cursor-pointer relative group">
                                    <input type="radio" name="categoria_id" value="<?php echo $c['id']; ?>"
                                        class="peer sr-only" required>
                                    <div
                                        class="p-2 rounded-xl border border-slate-200 bg-white text-slate-500 transition-all duration-200 group-hover:border-indigo-300 
                                            peer-checked:border-transparent peer-checked:ring-2 peer-checked:ring-indigo-500 peer-checked:bg-white peer-checked:text-indigo-600 peer-checked:shadow-lg peer-checked:shadow-indigo-500/20 flex flex-col items-center gap-0.5">
                                        <i
                                            class="<?php echo $icon; ?> text-lg mb-0.5 opacity-70 peer-checked:opacity-100"></i>
                                        <span
                                            class="text-[10px] md:text-xs font-semibold text-center leading-tight"><?php echo htmlspecialchars($c['nombre']); ?></span>
                                    </div>
                                    <div
                                        class="absolute inset-0 border-2 border-indigo-500 rounded-xl opacity-0 peer-checked:opacity-100 pointer-events-none transition-opacity">
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Prioridad -->
                    <div>
                        <label class="flex items-center gap-2 text-sm font-bold text-slate-700 mb-4">
                            <span
                                class="w-6 h-6 rounded bg-amber-100 text-amber-600 flex items-center justify-center text-xs"><i
                                    class="ri-flag-line"></i></span>
                            Prioridad <span class="text-red-400">*</span>
                        </label>

                        <div class="space-y-2">
                            <?php
                            $priorities = [
                                'Baja' => ['color' => 'emerald', 'icon' => 'ri-arrow-down-line', 'desc' => 'No urgente'],
                                'Media' => ['color' => 'blue', 'icon' => 'ri-subtract-line', 'desc' => 'Normal'],
                                'Alta' => ['color' => 'orange', 'icon' => 'ri-arrow-up-line', 'desc' => 'Urgente'],
                                'Critica' => ['color' => 'red', 'icon' => 'ri-alarm-warning-line', 'desc' => 'Bloqueante']
                            ];
                            foreach ($priorities as $key => $p):
                                ?>
                                <label class="cursor-pointer block relative group">
                                    <input type="radio" name="prioridad" value="<?php echo $key; ?>" class="peer sr-only"
                                        required>

                                    <div
                                        class="flex items-center p-3 rounded-xl border border-slate-200 bg-white hover:border-<?php echo $p['color']; ?>-300 transition-all
                                            peer-checked:border-<?php echo $p['color']; ?>-500 peer-checked:bg-<?php echo $p['color']; ?>-50 peer-checked:shadow-md">

                                        <div
                                            class="w-10 h-10 rounded-lg bg-<?php echo $p['color']; ?>-100 text-<?php echo $p['color']; ?>-600 flex items-center justify-center text-lg mr-3 shadow-inner">
                                            <i class="<?php echo $p['icon']; ?>"></i>
                                        </div>

                                        <div class="flex-1">
                                            <div
                                                class="font-bold text-sm text-slate-700 peer-checked:text-<?php echo $p['color']; ?>-800">
                                                <?php echo $key; ?></div>
                                            <div class="text-xs text-slate-400 font-medium"><?php echo $p['desc']; ?></div>
                                        </div>

                                        <div
                                            class="w-5 h-5 rounded-full border-2 border-slate-200 peer-checked:border-<?php echo $p['color']; ?>-500 peer-checked:bg-<?php echo $p['color']; ?>-500 flex items-center justify-center transition-all">
                                            <i
                                                class="ri-check-line text-white text-xs opacity-0 peer-checked:opacity-100"></i>
                                        </div>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    function updateCharCount(textarea) {
        const count = textarea.value.length;
        const max = textarea.getAttribute('maxlength');
        const counter = document.getElementById('charCount');
        counter.textContent = count + ' / ' + max;
         if( count > max * 0.9) {
            counter.className = 'text-xs font-bold text-red-500 bg-red-100 px-2 py-1 rounded-md';
        } else {
            counter.className = 'text-xs font-medium text-slate-400 bg-slate-100 px-2 py-1 rounded-md';
        }
    }
</script>

<style>
    .animate-fade-in {
        animation: fadeIn 0.6s cubic-bezier(0.16, 1, 0.3, 1);
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>