<?php
/**
 * seccion_solicitud_licencia.php - Formulario de Petición de Licencia (365 / Antivirus)
 */
?>
<div class="p-6 flex-1 glass min-h-screen">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <h2 class="text-3xl font-bold text-slate-800 flex items-center gap-3">
                <i class="ri-shield-keyhole-line text-blue-600"></i> Solicitud de Licencia
            </h2>
            <p class="text-slate-500 mt-2">Gestiona las solicitudes de licencias de software (Microsoft 365, Antivirus,
                etc.) para colaboradores.</p>
        </div>

        <form action="index.php?view=solicitud_licencia" method="POST" class="space-y-6">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="hidden" name="accion" value="solicitar_licencia">

            <!-- Card Principal -->
            <div class="bg-white rounded-2xl shadow-xl shadow-slate-200/50 border border-slate-100 p-8">

                <h3
                    class="text-lg font-bold text-slate-700 mb-6 pb-2 border-b border-slate-100 flex items-center gap-2">
                    <i class="ri-file-list-3-line text-indigo-500"></i> Detalles de la Solicitud
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

                    <!-- Columna Izquierda -->
                    <div class="space-y-6">
                        <!-- Beneficiario -->
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Beneficiario (Usuario
                                Final)</label>
                            <div class="relative">
                                <i class="ri-user-line absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                <input type="text" name="beneficiario" required placeholder="Nombre del colaborador..."
                                    class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-all text-slate-700 shadow-sm">
                            </div>
                            <p class="text-xs text-slate-400 mt-1 pl-1">Nombre de la persona que usará la licencia.</p>
                        </div>

                        <!-- Tipo de Licencia -->
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Tipo de Licencia</label>
                            <div class="relative">
                                <i
                                    class="ri-microsoft-line absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                <select name="tipo_licencia" required
                                    class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-all text-slate-700 shadow-sm appearance-none cursor-pointer">
                                    <option value="" disabled selected>Selecciona una opción...</option>
                                    <option value="Licencia Microsoft 365">Licencia Microsoft 365</option>
                                    <option value="Antivirus ESET">Antivirus ESET Endpoint</option>
                                    <option value="Otra">Otra (Especificar en justificación)</option>
                                </select>
                                <i
                                    class="ri-arrow-down-s-line absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Columna Derecha -->
                    <div class="space-y-6">
                        <!-- Prioridad -->
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Prioridad</label>
                            <div class="relative">
                                <i class="ri-flag-line absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                <select name="prioridad" required
                                    class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-all text-slate-700 shadow-sm appearance-none cursor-pointer">
                                    <option value="Baja">Baja</option>
                                    <option value="Media" selected>Media</option>
                                    <option value="Alta">Alta</option>
                                </select>
                                <i
                                    class="ri-arrow-down-s-line absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                            </div>
                        </div>

                        <!-- Departamento / Área -->
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Departamento / Área</label>
                            <div class="relative">
                                <i class="ri-building-line absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                <input type="text" name="departamento" placeholder="Ej. Ventas, Contabilidad..."
                                    class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-all text-slate-700 shadow-sm">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Justificación (Full Width) -->
                <div class="mt-8">
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Justificación / Detalles
                        Adicionales</label>
                    <div class="relative">
                        <textarea name="justificacion" rows="4" required
                            placeholder="Explica por qué se requiere esta licencia o añade detalles específicos..."
                            class="w-full p-4 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-all text-slate-700 shadow-sm"></textarea>
                    </div>
                </div>

            </div>

            <!-- Botones de Acción -->
            <div class="flex items-center justify-end gap-4 mt-8">
                <a href="index.php?view=dashboard"
                    class="px-6 py-3 bg-white text-slate-600 font-semibold rounded-xl border border-slate-200 hover:bg-slate-50 hover:text-slate-800 transition-all shadow-sm">
                    Cancelar
                </a>
                <button type="submit"
                    class="px-8 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-bold rounded-xl hover:from-blue-700 hover:to-indigo-700 shadow-lg shadow-blue-500/30 transform hover:-translate-y-0.5 transition-all flex items-center gap-2">
                    <i class="ri-send-plane-fill"></i> Enviar Solicitud
                </button>
            </div>

        </form>
    </div>
</div>