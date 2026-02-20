<?php
/**
 * seccion_registro_equipo.php - Formulario de Registro de Activos (Premium UI)
 */
// Cargar sucursales: solo las del usuario (salvo SuperAdmin)
if (isset($rol_usuario) && $rol_usuario === 'SuperAdmin') {
    $sucursales_lista = $pdo->query("SELECT id, nombre FROM sucursales ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt_suc = $pdo->prepare("
        SELECT s.id, s.nombre
        FROM sucursales s
        INNER JOIN usuarios_accesos ua ON ua.sucursal_id = s.id
        WHERE ua.usuario_id = ?
        ORDER BY s.nombre
    ");
    $stmt_suc->execute([$usuario_id]);
    $sucursales_lista = $stmt_suc->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="p-6 flex-1 bg-slate-50/50">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="mb-8 flex items-center justify-between">
            <div>
                <div class="flex items-center gap-2 text-sm text-slate-500 mb-2">
                    <a href="index.php?view=inventario" class="hover:text-blue-600 transition-colors">Inventario</a>
                    <i class="ri-arrow-right-s-line"></i>
                    <span>Registro</span>
                </div>
                <h1 class="text-3xl font-black text-slate-800 tracking-tight">
                    Nuevo Activo
                </h1>
                <p class="text-slate-500 mt-1">Incorporación de equipos al inventario general</p>
            </div>
            <a href="index.php?view=inventario"
                class="bg-white border border-slate-200 text-slate-600 px-4 py-2 rounded-lg text-sm font-semibold hover:bg-slate-50 transition-colors shadow-sm">
                <i class="ri-arrow-left-line mr-1"></i> Volver
            </a>
        </div>

        <!-- Formulario Premium -->
        <div
            class="bg-white rounded-2xl shadow-xl shadow-slate-200/50 border border-slate-100 overflow-hidden relative">

            <!-- Decoración Top -->
            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-blue-500 via-indigo-500 to-violet-500">
            </div>

            <form id="form-registro-activo" class="p-8">

                <!-- Sección 1: Datos Principales -->
                <div class="mb-8">
                    <h3 class="flex items-center gap-2 text-sm font-bold text-slate-400 uppercase tracking-wider mb-6">
                        <i class="ri-macbook-line text-lg"></i> Datos del Equipo
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Tipo -->
                        <div class="relative group">
                            <label class="block text-xs font-bold text-slate-500 mb-1.5 ml-1">TIPO DE ACTIVO</label>
                            <div class="relative">
                                <i
                                    class="ri-computer-line absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-blue-500 transition-colors"></i>
                                <select name="tipo" required
                                    class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-700 font-medium focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all appearance-none">
                                    <option value="">Seleccione tipo...</option>
                                    <option value="Laptop">💻 Laptop</option>
                                    <option value="PC">🖥️ PC Escritorio</option>
                                    <option value="Monitor">🖥️ Monitor</option>
                                    <option value="Movil">📱 Celular / Tablet</option>
                                    <option value="Teclado">⌨️ Teclado</option>
                                    <option value="Mouse">🖱️ Mouse</option>
                                    <option value="Headset">🎧 Audífonos</option>
                                    <option value="Impresora">🖨️ Impresora / Escáner</option>
                                    <option value="Silla">🪑 Silla</option>
                                    <option value="Escritorio">🪑 Escritorio</option>
                                    <option value="Otro">📦 Otro</option>
                                </select>
                                <i
                                    class="ri-arrow-down-s-line absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                            </div>
                        </div>

                        <!-- Estado -->
                        <div class="relative group">
                            <label class="block text-xs font-bold text-slate-500 mb-1.5 ml-1">ESTADO FÍSICO</label>
                            <div class="relative">
                                <i
                                    class="ri-heart-pulse-line absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-emerald-500 transition-colors"></i>
                                <select name="estado" required
                                    class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-700 font-medium focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 outline-none transition-all appearance-none">
                                    <option value="Nuevo">✨ Nuevo</option>
                                    <option value="Buen Estado">✅ Bueno</option>
                                    <option value="Regular">⚠️ Regular</option>
                                    <option value="En Reparacion">🔧 En Reparación</option>
                                    <option value="Malo">❌ Malo / Para Descarte</option>
                                </select>
                                <i
                                    class="ri-arrow-down-s-line absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <!-- Marca -->
                    <div class="relative group">
                        <label class="block text-xs font-bold text-slate-500 mb-1.5 ml-1">MARCA</label>
                        <div class="relative">
                            <i
                                class="ri-price-tag-3-line absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-blue-500 transition-colors"></i>
                            <input type="text" name="marca" required placeholder="Ej: Dell, HP, Apple"
                                class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-700 font-bold focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all">
                        </div>
                    </div>

                    <!-- Modelo -->
                    <div class="relative group">
                        <label class="block text-xs font-bold text-slate-500 mb-1.5 ml-1">MODELO</label>
                        <div class="relative">
                            <i
                                class="ri-barcode-box-line absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-blue-500 transition-colors"></i>
                            <input type="text" name="modelo" required placeholder="Ej: Latitude 5420"
                                class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-700 font-bold focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all">
                        </div>
                    </div>
                </div>

                <!-- Sección 2: Identificadores -->
                <div class="mb-8 pt-6 border-t border-slate-100">
                    <h3 class="flex items-center gap-2 text-sm font-bold text-slate-400 uppercase tracking-wider mb-6">
                        <i class="ri-qr-code-line text-lg"></i> Identificación Única
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Serial -->
                        <div class="relative group">
                            <label class="block text-xs font-bold text-slate-500 mb-1.5 ml-1">SERIAL (S/N)</label>
                            <div class="relative">
                                <i
                                    class="ri-fingerprint-line absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-indigo-500 transition-colors"></i>
                                <input type="text" name="serial" required placeholder="ABCD-1234-XYZ"
                                    class="w-full pl-11 pr-4 py-3 bg-white border-2 border-slate-200 rounded-xl text-slate-800 font-mono font-bold focus:border-indigo-500 focus:ring-0 outline-none transition-all uppercase placeholder:normal-case placeholder:text-slate-400">
                            </div>
                            <p class="text-[10px] text-slate-400 mt-1 ml-1">Debe ser único en el sistema.</p>
                        </div>

                        <!-- SKU -->
                        <div class="relative group">
                            <label class="block text-xs font-bold text-slate-500 mb-1.5 ml-1">CÓDIGO INTERNO / SKU <span
                                    class="text-slate-300 font-normal">(Opcional)</span></label>
                            <div class="relative">
                                <i
                                    class="ri-price-tag-line absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-indigo-500 transition-colors"></i>
                                <input type="text" name="sku" placeholder="INV-001"
                                    class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-700 font-mono focus:bg-white focus:border-indigo-500 focus:ring-0 outline-none transition-all">
                            </div>
                        </div>
                    </div>
                </div>
        </div>

        <!-- Sección 3: Sucursal de Destino -->
        <div class="mb-8 pt-6 border-t border-slate-100">
            <h3 class="flex items-center gap-2 text-sm font-bold text-slate-400 uppercase tracking-wider mb-6">
                <i class="ri-building-2-line text-lg"></i> Sucursal Destino
            </h3>
            <div class="relative group">
                <label class="block text-xs font-bold text-slate-500 mb-1.5 ml-1">SUCURSAL <span
                        class="text-slate-300 font-normal">(Opcional)</span></label>
                <div class="relative">
                    <i
                        class="ri-map-pin-line absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-blue-500 transition-colors"></i>
                    <select name="sucursal_id"
                        class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-700 font-medium focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all appearance-none">
                        <option value="">-- Sin sucursal asignada --</option>
                        <?php foreach ($sucursales_lista as $suc): ?>
                            <option value="<?= $suc['id'] ?>"><?= htmlspecialchars($suc['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <i
                        class="ri-arrow-down-s-line absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                </div>
                <p class="text-[10px] text-slate-400 mt-1 ml-1">Asocia este equipo a una sucursal para filtrar
                    correctamente en las asignaciones.</p>
            </div>
        </div>

        <!-- Sección 4: Informacion Adicional -->
        <div class="mb-8 pt-6 border-t border-slate-100">
            <h3 class="flex items-center gap-2 text-sm font-bold text-slate-400 uppercase tracking-wider mb-6">
                <i class="ri-file-text-line text-lg"></i> Información Adicional
            </h3>

            <div class="relative group">
                <label class="block text-xs font-bold text-slate-500 mb-1.5 ml-1">COMENTARIOS / NOTAS <span
                        class="text-slate-300 font-normal">(Opcional)</span></label>
                <div class="relative">
                    <i
                        class="ri-sticky-note-line absolute left-4 top-4 text-slate-400 group-focus-within:text-indigo-500 transition-colors"></i>
                    <textarea name="comentarios" rows="3"
                        placeholder="Detalles de garantía, ubicación física, estado de cargador, etc..."
                        class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-700 focus:bg-white focus:border-indigo-500 focus:ring-0 outline-none transition-all resize-none"></textarea>
                </div>
            </div>
        </div>

        <!-- Footer Actions -->
        <div class="pt-6 border-t border-slate-100 flex items-center justify-end gap-3">
            <button type="button" onclick="history.back()"
                class="px-6 py-3 rounded-xl border border-slate-200 text-slate-600 font-bold hover:bg-slate-50 transition-colors">
                Cancelar
            </button>
            <button type="submit" id="btn-submit"
                class="px-8 py-3 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-bold hover:shadow-lg hover:shadow-blue-500/30 hover:-translate-y-0.5 transition-all flex items-center gap-2">
                <i class="ri-save-line text-lg"></i>
                Guardar Activo
            </button>
        </div>

        </form>
    </div>

    <!-- Info Card -->
    <div class="mt-8 flex items-start gap-4 p-4 bg-indigo-50/50  rounded-xl border border-indigo-100">
        <div
            class="w-10 h-10 bg-white rounded-full flex items-center justify-center shadow-sm text-indigo-500 flex-shrink-0">
            <i class="ri-lightbulb-flash-line text-xl"></i>
        </div>
        <div>
            <h4 class="text-sm font-bold text-indigo-900 mb-1">¿Qué sucede después?</h4>
            <p class="text-sm text-indigo-800/70 leading-relaxed">
                El equipo quedará registrado como <span
                    class="font-bold bg-white px-1 rounded text-indigo-600">Disponible</span>.
                Podrás asignarlo inmediatamente desde el módulo "Asignaciones" o dejarlo en stock desde el
                Inventario.
            </p>
        </div>
    </div>

</div>
</div>

<script>
    document.getElementById('form-registro-activo').addEventListener('submit', function (e) {
        e.preventDefault();

        const btn = document.getElementById('btn-submit');
        const originalText = btn.innerHTML;

        // Loading State
        btn.disabled = true;
        btn.innerHTML = '<i class="ri-loader-4-line animate-spin text-lg"></i> Guardando...';

        const formData = new FormData(this);
        formData.append('ajax_action', 'ajax_crear_activo'); // Important: Match backend

        fetch('index.php?view=asignacion_equipos', { // URL doesn't strictly matter as long as it hits index.php
            method: 'POST',
            body: formData
        })
            .then(r => r.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Activo Guardado!',
                        text: 'El equipo se ha añadido al inventario correctamente.',
                        confirmButtonText: 'Ir al Inventario',
                        showCancelButton: true,
                        cancelButtonText: 'Registrar Otro',
                        confirmButtonColor: '#3b82f6'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = 'index.php?view=inventario';
                        } else {
                            document.getElementById('form-registro-activo').reset();
                            btn.disabled = false;
                            btn.innerHTML = originalText;
                            document.querySelector('select[name="tipo"]').focus();
                        }
                    });
                } else {
                    throw new Error(data.msg || 'Error desconocido');
                }
            })
            .catch(err => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: err.message,
                    confirmButtonColor: '#ef4444'
                });
                btn.disabled = false;
                btn.innerHTML = originalText;
            });
    });
</script>