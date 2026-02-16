<?php
/**
 * seccion_rrhh_ingreso.php - Formulario de Solicitud de Nuevo Ingreso
 * MASTER TECHNOLOGIES - Correo y Equipos
 */

// Cargar equipos disponibles del inventario
try {
    $equipos_disponibles = $pdo->query("SELECT * FROM inventario WHERE condicion = 'Disponible' ORDER BY tipo, marca")->fetchAll();
} catch (PDOException $e) {
    $equipos_disponibles = [];
}
?>
<div class="p-6 flex-1">
    <div class="max-w-5xl mx-auto">
        <!-- Header con branding Master Technologies -->
        <div class="mb-8 text-center">
            <div class="mb-4">
                <h1
                    class="text-4xl font-bold bg-gradient-to-r from-blue-600 via-purple-600 to-cyan-500 bg-clip-text text-transparent">
                    SOLICITUD DE NUEVO INGRESO
                </h1>
                <p class="text-xl text-slate-600 font-semibold">(Correo y Equipos)</p>
            </div>
            <div
                class="inline-block px-6 py-2 bg-gradient-to-r from-blue-500 via-purple-500 to-cyan-500 rounded-full shadow-lg">
                <h2 class="text-2xl font-bold text-white tracking-wider">MASTER TECHNOLOGIES</h2>
            </div>
        </div>

        <!-- Formulario -->
        <form method="POST" action="index.php?view=dashboard"
            class="bg-white rounded-2xl shadow-2xl border-2 border-blue-100 overflow-hidden">
            <input type="hidden" name="accion" value="registrar_ingreso">
            <input type="hidden" name="csrf_token"
                value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">

            <!-- Fecha -->
            <div class="bg-gradient-to-r from-blue-50 via-purple-50 to-cyan-50 px-6 py-4 border-b-2 border-blue-200">
                <div class="flex items-center justify-between">
                    <label class="text-sm font-bold text-slate-700">
                        <i class="ri-calendar-line text-blue-600"></i> Fecha:
                    </label>
                    <input type="date" name="fecha_solicitud" value="<?= date('Y-m-d') ?>" required
                        class="px-4 py-2 border-2 border-blue-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all font-semibold">
                </div>
            </div>

            <div class="p-8 space-y-8">
                <!-- SECCIÓN 1: DATOS DEL NUEVO COLABORADOR -->
                <div class="border-2 border-blue-200 rounded-xl overflow-hidden">
                    <div class="bg-gradient-to-r from-blue-600 to-purple-600 px-6 py-3">
                        <h3 class="text-lg font-bold text-white flex items-center gap-2">
                            <i class="ri-user-line"></i> SECCIÓN 1: DATOS DEL NUEVO COLABORADOR
                        </h3>
                    </div>
                    <div class="p-6 space-y-4 bg-blue-50/30">
                        <!-- Nombres y Apellidos -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Nombres y Apellidos: *
                            </label>
                            <input type="text" name="nombre_colaborador" required
                                class="w-full px-4 py-3 border-2 border-blue-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all font-medium"
                                placeholder="Ej: Carlos Adonis Castillo Martinez">
                        </div>

                        <!-- Cédula y Teléfono (separados) -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">
                                    Cédula: *
                                </label>
                                <input type="text" name="cedula" required
                                    class="w-full px-4 py-3 border-2 border-blue-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all font-medium"
                                    placeholder="Ej: 001-200998-1029F">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">
                                    Teléfono:
                                </label>
                                <input type="text" name="telefono"
                                    class="w-full px-4 py-3 border-2 border-blue-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all font-medium"
                                    placeholder="Ej: 8888-8888">
                            </div>
                        </div>

                        <!-- Cargo / Zona -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Cargo / Zona: *
                            </label>
                            <input type="text" name="cargo_zona" required
                                class="w-full px-4 py-3 border-2 border-blue-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all font-medium"
                                placeholder="Ej: Control Interno">
                        </div>
                    </div>
                </div>

                <!-- SECCIÓN 2: REQUERIMIENTOS DE CORREO Y NUBE -->
                <div class="border-2 border-purple-200 rounded-xl overflow-hidden">
                    <div class="bg-gradient-to-r from-purple-600 to-blue-600 px-6 py-3">
                        <h3 class="text-lg font-bold text-white flex items-center gap-2">
                            <i class="ri-mail-line"></i> SECCIÓN 2: REQUERIMIENTOS DE CORREO Y NUBE
                        </h3>
                    </div>
                    <div class="p-6 space-y-4 bg-purple-50/30">
                        <!-- Solicitud de Licencias -->
                        <div class="p-4 bg-white rounded-lg border-2 border-purple-100 space-y-3">
                            <div class="flex items-start gap-4">
                                <div class="flex-1">
                                    <label class="font-bold text-slate-700 flex items-center gap-2">
                                        <i class="ri-microsoft-line text-purple-600"></i> Solicitud de Licencias:
                                    </label>
                                    <p class="text-xs text-slate-500 mt-1">Seleccione las licencias que requiere el
                                        nuevo colaborador.</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-2 bg-purple-50/50 p-3 rounded-lg">
                                <label
                                    class="flex items-center gap-2 cursor-pointer p-2 hover:bg-white rounded transition-colors">
                                    <input type="checkbox" name="licencias[]" value="Licencia Microsoft 365"
                                        class="w-4 h-4 text-purple-600 rounded focus:ring-purple-500">
                                    <span class="text-sm font-medium text-slate-700">Licencia Microsoft 365</span>
                                </label>
                                <label
                                    class="flex items-center gap-2 cursor-pointer p-2 hover:bg-white rounded transition-colors">
                                    <input type="checkbox" name="licencias[]" value="Antivirus ESET"
                                        class="w-4 h-4 text-purple-600 rounded focus:ring-purple-500">
                                    <span class="text-sm font-medium text-slate-700">Antivirus ESET</span>
                                </label>
                                <label
                                    class="flex items-center gap-2 cursor-pointer p-2 hover:bg-white rounded transition-colors col-span-1 md:col-span-2">
                                    <input type="checkbox" id="check_otras_licencias" name="licencias[]" value="Otras"
                                        onchange="toggleDetalle('container_otras_licencias', this.checked)"
                                        class="w-4 h-4 text-purple-600 rounded focus:ring-purple-500">
                                    <span class="text-sm font-medium text-slate-700">Otra (Especificar)</span>
                                </label>
                            </div>

                            <div id="container_otras_licencias" class="mt-2" style="display: none;">
                                <input type="text" name="licencias_otras"
                                    class="w-full px-4 py-2 border-2 border-purple-200 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none transition-all text-sm"
                                    placeholder="Especifique la licencia requerida...">
                            </div>

                            <!-- Hidden field para mantener compatibilidad con validaciones básicas si las hubiera -->
                            <input type="hidden" name="disponibilidad_licencias" value="SI">
                        </div>

                        <!-- Correo nuevo -->
                        <div class="p-4 bg-white rounded-lg border-2 border-purple-100 space-y-3">
                            <div class="flex items-start gap-4">
                                <div class="flex-1">
                                    <label class="font-bold text-slate-700">Correo nuevo (indicar dirección):</label>
                                </div>
                                <div class="flex gap-4">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="radio" name="correo_nuevo" value="SI" checked
                                            onchange="toggleDetalle('detalle_correo', this.checked)"
                                            class="w-5 h-5 text-purple-600 focus:ring-purple-500">
                                        <span class="font-semibold text-green-600">SI</span>
                                    </label>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="radio" name="correo_nuevo" value="NO"
                                            onchange="toggleDetalle('detalle_correo', !this.checked)"
                                            class="w-5 h-5 text-purple-600 focus:ring-purple-500">
                                        <span class="font-semibold text-red-600">NO</span>
                                    </label>
                                </div>
                            </div>
                            <div id="detalle_correo">
                                <input type="email" name="direccion_correo"
                                    class="w-full px-4 py-2 border-2 border-purple-200 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none transition-all"
                                    placeholder="ejemplo@mastertechnologies.com">
                            </div>
                        </div>

                        <!-- Remitente a mostrar -->
                        <div class="p-4 bg-white rounded-lg border-2 border-purple-100 space-y-3">
                            <div class="flex items-start gap-4">
                                <div class="flex-1">
                                    <label class="font-bold text-slate-700">Remitente a mostrar:</label>
                                </div>
                                <div class="flex gap-4">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="radio" name="remitente_mostrar" value="SI" checked
                                            onchange="toggleDetalle('detalle_remitente', this.checked)"
                                            class="w-5 h-5 text-purple-600 focus:ring-purple-500">
                                        <span class="font-semibold text-green-600">SI</span>
                                    </label>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="radio" name="remitente_mostrar" value="NO"
                                            onchange="toggleDetalle('detalle_remitente', !this.checked)"
                                            class="w-5 h-5 text-purple-600 focus:ring-purple-500">
                                        <span class="font-semibold text-red-600">NO</span>
                                    </label>
                                </div>
                            </div>
                            <div id="detalle_remitente">
                                <input type="text" name="detalle_remitente"
                                    class="w-full px-4 py-2 border-2 border-purple-200 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none transition-all text-sm"
                                    placeholder="Especificar nombre del remitente a mostrar...">
                            </div>
                        </div>

                        <!-- Respaldo información nube -->
                        <div class="p-4 bg-white rounded-lg border-2 border-purple-100 space-y-3">
                            <div class="flex items-start gap-4">
                                <div class="flex-1">
                                    <label class="font-bold text-slate-700">Respaldo información nube
                                        (total/parcial):</label>
                                </div>
                                <div class="flex gap-4">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="radio" name="respaldo_nube" value="SI"
                                            onchange="toggleDetalle('detalle_respaldo', this.checked)"
                                            class="w-5 h-5 text-purple-600 focus:ring-purple-500">
                                        <span class="font-semibold text-green-600">SI</span>
                                    </label>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="radio" name="respaldo_nube" value="NO" checked
                                            onchange="toggleDetalle('detalle_respaldo', !this.checked)"
                                            class="w-5 h-5 text-purple-600 focus:ring-purple-500">
                                        <span class="font-semibold text-red-600">NO</span>
                                    </label>
                                </div>
                            </div>
                            <div id="detalle_respaldo" style="display: none;">
                                <input type="text" name="detalle_respaldo"
                                    class="w-full px-4 py-2 border-2 border-purple-200 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none transition-all text-sm"
                                    placeholder="Especificar si es total o parcial y detalles...">
                            </div>
                        </div>

                        <!-- Reenvíos correo -->
                        <div class="p-4 bg-white rounded-lg border-2 border-purple-100 space-y-3">
                            <div class="flex items-start gap-4">
                                <div class="flex-1">
                                    <label class="font-bold text-slate-700">Reenvíos correo:</label>
                                </div>
                                <div class="flex gap-4">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="radio" name="reenvios_correo" value="SI"
                                            onchange="toggleDetalle('detalle_reenvios', this.checked)"
                                            class="w-5 h-5 text-purple-600 focus:ring-purple-500">
                                        <span class="font-semibold text-green-600">SI</span>
                                    </label>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="radio" name="reenvios_correo" value="NO" checked
                                            onchange="toggleDetalle('detalle_reenvios', !this.checked)"
                                            class="w-5 h-5 text-purple-600 focus:ring-purple-500">
                                        <span class="font-semibold text-red-600">NO</span>
                                    </label>
                                </div>
                            </div>
                            <div id="detalle_reenvios" style="display: none;">
                                <input type="text" name="detalle_reenvios"
                                    class="w-full px-4 py-2 border-2 border-purple-200 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none transition-all text-sm"
                                    placeholder="Especificar direcciones de reenvío...">
                            </div>
                        </div>

                        <!-- Otras Indicaciones -->
                        <div class="p-4 bg-white rounded-lg border-2 border-purple-100">
                            <label class="block font-bold text-slate-700 mb-2">Otras Indicaciones:</label>
                            <textarea name="otras_indicaciones" rows="3"
                                class="w-full px-4 py-3 border-2 border-purple-200 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none transition-all resize-none"
                                placeholder="Especificar cualquier indicación adicional..."></textarea>
                        </div>
                    </div>
                </div>


                <!-- SECCIÓN 3: ASIGNACIÓN DE EQUIPOS -->
                <div class="border-2 border-cyan-200 rounded-xl overflow-hidden">
                    <div class="bg-gradient-to-r from-cyan-600 to-blue-600 px-6 py-3 flex items-center justify-between">
                        <h3 class="text-lg font-bold text-white flex items-center gap-2">
                            <i class="ri-computer-line"></i> SECCIÓN 3: ASIGNACIÓN DE EQUIPOS
                        </h3>
                        <a href="index.php?view=inventario" target="_blank"
                            class="text-xs bg-white/20 hover:bg-white/30 px-3 py-1 rounded-lg transition-colors flex items-center gap-1">
                            <i class="ri-external-link-line"></i> Ver Inventario
                        </a>
                    </div>
                    <div class="p-6 space-y-6 bg-cyan-50/30">

                        <!-- Pregunta Principal -->
                        <div class="p-4 bg-white rounded-lg border-2 border-cyan-100 flex items-center justify-between">
                            <label class="font-bold text-slate-700 text-lg">¿Requiere asignación de equipos?</label>
                            <div class="flex gap-4">
                                <label
                                    class="flex items-center gap-2 cursor-pointer bg-green-50 px-3 py-1 rounded-lg border border-green-200">
                                    <input type="radio" name="asignacion_equipo" value="SI" checked
                                        onchange="toggleDetalle('detalle_asignacion', this.checked)"
                                        class="w-5 h-5 text-green-600 focus:ring-green-500">
                                    <span class="font-bold text-green-700">SI</span>
                                </label>
                                <label
                                    class="flex items-center gap-2 cursor-pointer bg-red-50 px-3 py-1 rounded-lg border border-red-200">
                                    <input type="radio" name="asignacion_equipo" value="NO"
                                        onchange="toggleDetalle('detalle_asignacion', !this.checked)"
                                        class="w-5 h-5 text-red-600 focus:ring-red-500">
                                    <span class="font-bold text-red-700">NO</span>
                                </label>
                            </div>
                        </div>

                        <!-- Contenedor de asignación detallada -->
                        <div id="detalle_asignacion" class="space-y-4 animate-fade-in">
                            <!-- Laptop/PC -->
                            <div class="p-4 bg-white rounded-lg border-2 border-cyan-100 space-y-3">
                                <label class="font-bold text-slate-700 flex items-center gap-2">
                                    <i class="ri-macbook-line text-cyan-600"></i> Asignar Laptop / PC
                                </label>
                                <select name="equipo_laptop_id"
                                    class="w-full px-4 py-3 border-2 border-cyan-200 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition-all bg-white">
                                    <option value="">-- No asignar --</option>
                                    <?php
                                    $laptops = array_filter($equipos_disponibles, fn($e) => in_array($e['tipo'], ['Laptop', 'PC']));
                                    foreach ($laptops as $eq):
                                        ?>
                                        <option value="<?= $eq['id'] ?>">
                                            <?= htmlspecialchars($eq['marca'] . ' ' . $eq['modelo']) ?> - SN:
                                            <?= htmlspecialchars($eq['serial']) ?> (<?= $eq['estado'] ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="text-xs text-slate-500 flex items-center gap-1">
                                    <i class="ri-information-line"></i>
                                    <?= count($laptops) ?> equipos disponibles en inventario
                                </p>
                            </div>

                            <!-- Monitor -->
                            <div class="p-4 bg-white rounded-lg border-2 border-cyan-100 space-y-3">
                                <label class="font-bold text-slate-700 flex items-center gap-2">
                                    <i class="ri-tv-line text-cyan-600"></i> Asignar Monitor
                                </label>
                                <select name="equipo_monitor_id"
                                    class="w-full px-4 py-3 border-2 border-cyan-200 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition-all bg-white">
                                    <option value="">-- No asignar --</option>
                                    <?php
                                    $monitores = array_filter($equipos_disponibles, fn($e) => $e['tipo'] === 'Monitor');
                                    foreach ($monitores as $eq):
                                        ?>
                                        <option value="<?= $eq['id'] ?>">
                                            <?= htmlspecialchars($eq['marca'] . ' ' . $eq['modelo']) ?> - SN:
                                            <?= htmlspecialchars($eq['serial']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Móvil/Tablet -->
                            <div class="p-4 bg-white rounded-lg border-2 border-cyan-100 space-y-3">
                                <label class="font-bold text-slate-700 flex items-center gap-2">
                                    <i class="ri-smartphone-line text-cyan-600"></i> Asignar Celular / Tablet
                                </label>
                                <select name="equipo_movil_id"
                                    class="w-full px-4 py-3 border-2 border-cyan-200 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition-all bg-white">
                                    <option value="">-- No asignar --</option>
                                    <?php
                                    $moviles = array_filter($equipos_disponibles, fn($e) => $e['tipo'] === 'Movil');
                                    foreach ($moviles as $eq):
                                        ?>
                                        <option value="<?= $eq['id'] ?>">
                                            <?= htmlspecialchars($eq['marca'] . ' ' . $eq['modelo']) ?> - SN:
                                            <?= htmlspecialchars($eq['serial']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Mobiliario (Silla/Escritorio) -->
                            <div class="p-4 bg-white rounded-lg border-2 border-cyan-100 space-y-3">
                                <label class="font-bold text-slate-700 flex items-center gap-2">
                                    <i class="ri-armchair-line text-cyan-600"></i> Asignar Mobiliario
                                </label>
                                <select name="equipo_mobiliario_id"
                                    class="w-full px-4 py-3 border-2 border-cyan-200 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition-all bg-white">
                                    <option value="">-- No asignar --</option>
                                    <?php
                                    $mobiliario = array_filter($equipos_disponibles, fn($e) => in_array($e['tipo'], ['Silla', 'Escritorio']));
                                    foreach ($mobiliario as $eq):
                                        ?>
                                        <option value="<?= $eq['id'] ?>">
                                            <?= htmlspecialchars($eq['tipo'] . ' - ' . $eq['marca'] . ' ' . $eq['modelo']) ?>
                                            - SN: <?= htmlspecialchars($eq['serial']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Observaciones adicionales -->
                            <div class="p-4 bg-white rounded-lg border-2 border-cyan-100">
                                <label class="block font-bold text-slate-700 mb-2">Observaciones sobre
                                    equipos:</label>
                                <textarea name="observaciones_equipos" rows="2"
                                    class="w-full px-4 py-3 border-2 border-cyan-200 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition-all resize-none"
                                    placeholder="Especificar cualquier detalle adicional sobre los equipos asignados..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botones -->
                <div
                    class="bg-gradient-to-r from-blue-50 via-purple-50 to-cyan-50 px-8 py-6 border-t-2 border-blue-200 flex items-center justify-end gap-4">
                    <a href="index.php?view=dashboard"
                        class="px-8 py-3 rounded-xl border-2 border-slate-400 text-slate-700 font-bold hover:bg-slate-100 transition-all shadow-lg">
                        <i class="ri-close-line"></i> Cancelar
                    </a>
                    <button type="submit"
                        class="px-8 py-3 rounded-xl bg-gradient-to-r from-blue-600 via-purple-600 to-cyan-600 text-white font-bold hover:from-blue-700 hover:via-purple-700 hover:to-cyan-700 shadow-xl shadow-blue-500/50 transition-all transform hover:scale-105">
                        <i class="ri-send-plane-fill"></i> Enviar Solicitud de Ingreso
                    </button>
                </div>
        </form>

        <!-- Nota informativa -->
        <div class="mt-6 bg-gradient-to-r from-blue-50 to-purple-50 border-2 border-blue-200 rounded-xl p-5">
            <div class="flex gap-3">
                <i class="ri-information-line text-blue-600 text-2xl flex-shrink-0"></i>
                <div class="text-sm text-blue-900">
                    <p class="font-bold mb-2">Master Technologies - Sistema de Gestión de Personal</p>
                    <ul class="list-disc list-inside space-y-1 text-blue-800">
                        <li>Complete todos los campos obligatorios marcados con (*)</li>
                        <li>Verifique la dirección de correo propuesta antes de enviar</li>
                        <li>Esta solicitud será procesada por el departamento de IT</li>
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
        // Los campos que están en SI por defecto se muestran
        // toggleDetalle('detalle_licencias', true); // Removed as field is replaced
        toggleDetalle('detalle_correo', true);
        toggleDetalle('detalle_remitente', true);
        toggleDetalle('detalle_asignacion', true);
        toggleDetalle('detalle_nube_movil', true);
        toggleDetalle('container_otras_licencias', false); // Por defecto oculto

        // Los que están en NO por defecto se ocultan
        toggleDetalle('detalle_respaldo', false);
        toggleDetalle('detalle_reenvios', false);
        toggleDetalle('detalle_equipo_usado', false);
    });
</script>