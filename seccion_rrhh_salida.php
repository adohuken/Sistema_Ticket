<?php
/**
 * seccion_rrhh_salida.php - Formulario de Solicitud de Baja de Personal
 * MASTER TECHNOLOGIES - Cierre de Cuentas y Devolución
 */
?>
<div class="p-6 flex-1">
    <div class="max-w-5xl mx-auto">
        <!-- Header con branding Master Technologies -->
        <div class="mb-8 text-center">
            <div class="mb-4">
                <h1
                    class="text-4xl font-bold bg-gradient-to-r from-red-600 via-rose-600 to-pink-500 bg-clip-text text-transparent">
                    SOLICITUD DE BAJA DE PERSONAL
                </h1>
                <p class="text-xl text-slate-600 font-semibold">(Cierre de Cuentas y Devolución)</p>
            </div>
            <div
                class="inline-block px-6 py-2 bg-gradient-to-r from-red-500 via-rose-500 to-pink-500 rounded-full shadow-lg">
                <h2 class="text-2xl font-bold text-white tracking-wider">MASTER TECHNOLOGIES</h2>
            </div>
        </div>

        <!-- Formulario -->
        <form method="POST" action="index.php?view=dashboard"
            class="bg-white rounded-2xl shadow-2xl border-2 border-red-100 overflow-hidden">
            <input type="hidden" name="accion" value="registrar_salida">
            <input type="hidden" name="csrf_token"
                value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">

            <!-- Fecha -->
            <div class="bg-gradient-to-r from-red-50 via-rose-50 to-pink-50 px-6 py-4 border-b-2 border-red-200">
                <div class="flex items-center justify-between">
                    <label class="text-sm font-bold text-slate-700">
                        <i class="ri-calendar-line text-red-600"></i> Fecha de Solicitud:
                    </label>
                    <input type="date" name="fecha_solicitud" value="<?= date('Y-m-d') ?>" required
                        class="px-4 py-2 border-2 border-red-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none transition-all font-semibold">
                </div>
            </div>

            <div class="p-8 space-y-8">
                <!-- SECCIÓN 1: DATOS DEL COLABORADOR -->
                <div class="border-2 border-red-200 rounded-xl overflow-hidden">
                    <div class="bg-gradient-to-r from-red-600 to-rose-600 px-6 py-3">
                        <h3 class="text-lg font-bold text-white flex items-center gap-2">
                            <i class="ri-user-unfollow-line"></i> SECCIÓN 1: DATOS DEL COLABORADOR
                        </h3>
                    </div>
                    <div class="p-6 space-y-4 bg-red-50/30">
                        <!-- Nombres y Apellidos -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Nombres y Apellidos: *
                            </label>
                            <input type="text" name="nombre_colaborador" required
                                class="w-full px-4 py-3 border-2 border-red-200 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none transition-all font-medium"
                                placeholder="Ej: María González López">
                        </div>

                        <!-- Cédula y Teléfono (separados) -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">
                                    Cédula: *
                                </label>
                                <input type="text" name="cedula" required
                                    class="w-full px-4 py-3 border-2 border-red-200 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none transition-all font-medium"
                                    placeholder="Ej: 001-250590-0000A">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">
                                    Teléfono:
                                </label>
                                <input type="text" name="telefono"
                                    class="w-full px-4 py-3 border-2 border-red-200 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none transition-all font-medium"
                                    placeholder="Ej: 8888-8888">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Cargo / Zona -->
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">
                                    Cargo / Zona: *
                                </label>
                                <input type="text" name="cargo_zona" required
                                    class="w-full px-4 py-3 border-2 border-red-200 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none transition-all font-medium"
                                    placeholder="Ej: Ventas Zona Norte">
                            </div>

                            <!-- Fecha Efectiva de Salida -->
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">
                                    Fecha Efectiva de Salida: *
                                </label>
                                <input type="date" name="fecha_efectiva" required
                                    class="w-full px-4 py-3 border-2 border-red-200 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none transition-all font-medium">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECCIÓN 2: CIERRE DE ACCESOS Y CUENTAS -->
                <div class="border-2 border-rose-200 rounded-xl overflow-hidden">
                    <div class="bg-gradient-to-r from-rose-600 to-pink-600 px-6 py-3">
                        <h3 class="text-lg font-bold text-white flex items-center gap-2">
                            <i class="ri-lock-2-line"></i> SECCIÓN 2: CIERRE DE ACCESOS Y CUENTAS
                        </h3>
                    </div>
                    <div class="p-6 space-y-4 bg-rose-50/30">
                        <!-- Liberación de Licencias -->
                        <div class="p-4 bg-white rounded-lg border-2 border-rose-100 space-y-3">
                            <label class="font-bold text-slate-700 flex items-center gap-2">
                                <i class="ri-uninstall-line text-rose-600"></i> Liberación de Licencias:
                            </label>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 bg-rose-50/50 p-3 rounded-lg">
                                <label
                                    class="flex items-center gap-2 cursor-pointer p-2 hover:bg-white rounded transition-colors">
                                    <input type="checkbox" name="licencias_revocar[]" value="Revocar Microsoft 365"
                                        class="w-4 h-4 text-rose-600 rounded focus:ring-rose-500">
                                    <span class="text-sm font-medium text-slate-700">Revocar Microsoft 365</span>
                                </label>
                                <label
                                    class="flex items-center gap-2 cursor-pointer p-2 hover:bg-white rounded transition-colors">
                                    <input type="checkbox" name="licencias_revocar[]" value="Revocar Antivirus"
                                        class="w-4 h-4 text-rose-600 rounded focus:ring-rose-500">
                                    <span class="text-sm font-medium text-slate-700">Revocar Antivirus</span>
                                </label>
                                <label
                                    class="flex items-center gap-2 cursor-pointer p-2 hover:bg-white rounded transition-colors">
                                    <input type="checkbox" name="licencias_revocar[]" value="Eliminar Cta Correo"
                                        class="w-4 h-4 text-rose-600 rounded focus:ring-rose-500">
                                    <span class="text-sm font-medium text-slate-700">Eliminar Cuenta Correo</span>
                                </label>
                                <label
                                    class="flex items-center gap-2 cursor-pointer p-2 hover:bg-white rounded transition-colors col-span-1 md:col-span-2">
                                    <input type="checkbox" id="check_otras_revocar" name="licencias_revocar[]"
                                        value="Otras" onchange="toggleDetalle('container_otras_revocar', this.checked)"
                                        class="w-4 h-4 text-rose-600 rounded focus:ring-rose-500">
                                    <span class="text-sm font-medium text-slate-700">Otra (Especificar)</span>
                                </label>
                            </div>
                            <div id="container_otras_revocar" class="mt-2" style="display: none;">
                                <input type="text" name="licencias_revocar_otras"
                                    class="w-full px-4 py-2 border-2 border-rose-200 rounded-lg focus:ring-2 focus:ring-rose-500 focus:border-rose-500 outline-none transition-all text-sm"
                                    placeholder="Especifique otras licencias a revocar...">
                            </div>
                        </div>

                        <!-- Bloqueo de Correo (Mantenido) -->
                        <div class="p-4 bg-white rounded-lg border-2 border-rose-100 space-y-3">
                            <div class="flex items-start gap-4">
                                <div class="flex-1">
                                    <label class="font-bold text-slate-700">Bloqueo inmediato de correo
                                        corporativo:</label>
                                </div>
                                <div class="flex gap-4">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="radio" name="bloqueo_correo" value="SI" checked
                                            onchange="toggleDetalle('detalle_bloqueo', this.checked)"
                                            class="w-5 h-5 text-rose-600 focus:ring-rose-500">
                                        <span class="font-semibold text-green-600">SI</span>
                                    </label>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="radio" name="bloqueo_correo" value="NO"
                                            onchange="toggleDetalle('detalle_bloqueo', !this.checked)"
                                            class="w-5 h-5 text-rose-600 focus:ring-rose-500">
                                        <span class="font-semibold text-red-600">NO</span>
                                    </label>
                                </div>
                            </div>
                            <div id="detalle_bloqueo">
                                <input type="text" name="cuenta_correo"
                                    class="w-full px-4 py-2 border-2 border-rose-200 rounded-lg focus:ring-2 focus:ring-rose-500 focus:border-rose-500 outline-none transition-all text-sm"
                                    placeholder="Indicar cuenta de correo a bloquear...">
                            </div>
                        </div>

                        <!-- Respaldo de Información -->
                        <div class="p-4 bg-white rounded-lg border-2 border-rose-100 space-y-3">
                            <div class="flex items-start gap-4">
                                <div class="flex-1">
                                    <label class="font-bold text-slate-700">¿Requiere respaldo de información?:</label>
                                </div>
                                <div class="flex gap-4">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="radio" name="respaldo_info" value="SI"
                                            onchange="toggleDetalle('detalle_respaldo_salida', this.checked)"
                                            class="w-5 h-5 text-rose-600 focus:ring-rose-500">
                                        <span class="font-semibold text-green-600">SI</span>
                                    </label>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="radio" name="respaldo_info" value="NO" checked
                                            onchange="toggleDetalle('detalle_respaldo_salida', !this.checked)"
                                            class="w-5 h-5 text-rose-600 focus:ring-rose-500">
                                        <span class="font-semibold text-red-600">NO</span>
                                    </label>
                                </div>
                            </div>
                            <div id="detalle_respaldo_salida" style="display: none;">
                                <input type="text" name="detalle_respaldo_salida"
                                    class="w-full px-4 py-2 border-2 border-rose-200 rounded-lg focus:ring-2 focus:ring-rose-500 focus:border-rose-500 outline-none transition-all text-sm"
                                    placeholder="Especificar qué información respaldar y a quién entregar...">
                            </div>
                        </div>

                        <!-- Redirección de Correo -->
                        <div class="p-4 bg-white rounded-lg border-2 border-rose-100 space-y-3">
                            <div class="flex items-start gap-4">
                                <div class="flex-1">
                                    <label class="font-bold text-slate-700">Redirección de correos entrantes:</label>
                                </div>
                                <div class="flex gap-4">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="radio" name="redireccion_correo" value="SI"
                                            onchange="toggleDetalle('detalle_redireccion', this.checked)"
                                            class="w-5 h-5 text-rose-600 focus:ring-rose-500">
                                        <span class="font-semibold text-green-600">SI</span>
                                    </label>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="radio" name="redireccion_correo" value="NO" checked
                                            onchange="toggleDetalle('detalle_redireccion', !this.checked)"
                                            class="w-5 h-5 text-rose-600 focus:ring-rose-500">
                                        <span class="font-semibold text-red-600">NO</span>
                                    </label>
                                </div>
                            </div>
                            <div id="detalle_redireccion" style="display: none;">
                                <input type="email" name="email_redireccion"
                                    class="w-full px-4 py-2 border-2 border-rose-200 rounded-lg focus:ring-2 focus:ring-rose-500 focus:border-rose-500 outline-none transition-all text-sm"
                                    placeholder="Indicar correo destino para la redirección...">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECCIÓN 3: DEVOLUCIÓN DE EQUIPOS -->
                <div class="border-2 border-pink-200 rounded-xl overflow-hidden">
                    <div class="bg-gradient-to-r from-pink-600 to-rose-600 px-6 py-3">
                        <h3 class="text-lg font-bold text-white flex items-center gap-2">
                            <i class="ri-computer-line"></i> SECCIÓN 3: DEVOLUCIÓN DE EQUIPOS
                        </h3>
                    </div>
                    <div class="p-6 space-y-4 bg-pink-50/30">
                        <!-- Devolución PC/Laptop -->
                        <div class="p-4 bg-white rounded-lg border-2 border-pink-100 space-y-3">
                            <div class="flex items-start gap-4">
                                <div class="flex-1">
                                    <label class="font-bold text-slate-700">Devolución PC/Laptop/Tablet:</label>
                                </div>
                                <div class="flex gap-4">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="radio" name="devolucion_equipo" value="SI" checked
                                            onchange="toggleDetalle('detalle_devolucion_equipo', this.checked)"
                                            class="w-5 h-5 text-pink-600 focus:ring-pink-500">
                                        <span class="font-semibold text-green-600">SI</span>
                                    </label>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="radio" name="devolucion_equipo" value="NO"
                                            onchange="toggleDetalle('detalle_devolucion_equipo', !this.checked)"
                                            class="w-5 h-5 text-pink-600 focus:ring-pink-500">
                                        <span class="font-semibold text-red-600">NO</span>
                                    </label>
                                </div>
                            </div>
                            <div id="detalle_devolucion_equipo">
                                <input type="text" name="detalle_devolucion_equipo"
                                    class="w-full px-4 py-2 border-2 border-pink-200 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500 outline-none transition-all text-sm"
                                    placeholder="Especificar equipo, modelo y estado físico...">
                            </div>
                        </div>

                        <!-- Devolución Móvil -->
                        <div class="p-4 bg-white rounded-lg border-2 border-pink-100 space-y-3">
                            <div class="flex items-start gap-4">
                                <div class="flex-1">
                                    <label class="font-bold text-slate-700">Devolución Móvil Corporativo:</label>
                                </div>
                                <div class="flex gap-4">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="radio" name="devolucion_movil" value="SI"
                                            onchange="toggleDetalle('detalle_devolucion_movil', this.checked)"
                                            class="w-5 h-5 text-pink-600 focus:ring-pink-500">
                                        <span class="font-semibold text-green-600">SI</span>
                                    </label>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="radio" name="devolucion_movil" value="NO" checked
                                            onchange="toggleDetalle('detalle_devolucion_movil', !this.checked)"
                                            class="w-5 h-5 text-pink-600 focus:ring-pink-500">
                                        <span class="font-semibold text-red-600">NO</span>
                                    </label>
                                </div>
                            </div>
                            <div id="detalle_devolucion_movil" style="display: none;">
                                <input type="text" name="detalle_devolucion_movil"
                                    class="w-full px-4 py-2 border-2 border-pink-200 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500 outline-none transition-all text-sm"
                                    placeholder="Especificar dispositivo, accesorios y estado...">
                            </div>
                        </div>

                        <!-- Observaciones Adicionales -->
                        <div class="p-4 bg-white rounded-lg border-2 border-pink-100">
                            <label class="block font-bold text-slate-700 mb-2">Observaciones Adicionales:</label>
                            <textarea name="observaciones" rows="3"
                                class="w-full px-4 py-3 border-2 border-pink-200 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500 outline-none transition-all resize-none"
                                placeholder="Cualquier otro detalle relevante sobre la baja..."></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Botones -->
            <div
                class="bg-gradient-to-r from-red-50 via-rose-50 to-pink-50 px-8 py-6 border-t-2 border-red-200 flex items-center justify-end gap-4">
                <a href="index.php?view=dashboard"
                    class="px-8 py-3 rounded-xl border-2 border-slate-400 text-slate-700 font-bold hover:bg-slate-100 transition-all shadow-lg">
                    <i class="ri-close-line"></i> Cancelar
                </a>
                <button type="submit"
                    class="px-8 py-3 rounded-xl bg-gradient-to-r from-red-600 via-rose-600 to-pink-600 text-white font-bold hover:from-red-700 hover:via-rose-700 hover:to-pink-700 shadow-xl shadow-red-500/50 transition-all transform hover:scale-105">
                    <i class="ri-user-unfollow-line"></i> Procesar Baja
                </button>
            </div>
        </form>

        <!-- Nota informativa -->
        <div class="mt-6 bg-gradient-to-r from-red-50 to-rose-50 border-2 border-red-200 rounded-xl p-5">
            <div class="flex gap-3">
                <i class="ri-alert-line text-red-600 text-2xl flex-shrink-0"></i>
                <div class="text-sm text-red-900">
                    <p class="font-bold mb-2">Importante - Proceso de Baja</p>
                    <ul class="list-disc list-inside space-y-1 text-red-800">
                        <li>Asegúrese de recibir todos los equipos asignados antes de firmar la baja.</li>
                        <li>El bloqueo de cuentas se procesará inmediatamente al enviar esta solicitud.</li>
                        <li>Verifique si existe información crítica que deba ser respaldada.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Función para mostrar/ocultar campos de detalle (Reutilizada)
    function toggleDetalle(elementId, show) {
        const element = document.getElementById(elementId);
        if (element) {
            element.style.display = show ? 'block' : 'none';
            if (!show) {
                const input = element.querySelector('input, textarea');
                if (input) input.value = '';
            }
        }
    }

    // Inicializar estados al cargar la página
    document.addEventListener('DOMContentLoaded', function () {
        toggleDetalle('detalle_bloqueo', true);
        toggleDetalle('detalle_respaldo_salida', false);
        toggleDetalle('detalle_redireccion', false);
        toggleDetalle('detalle_devolucion_equipo', true);
        toggleDetalle('detalle_devolucion_movil', false);
        toggleDetalle('container_otras_revocar', false);
    });
</script>