<?php
// seccion_editar_usuario.php
// Obtener datos del usuario a editar
$usuario_editar = null;
if (isset($_GET['id'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $usuario_editar = $stmt->fetch();

        // Obtener permisos actuales de sucursal para este usuario
        $permisos_actuales = [];
        if ($usuario_editar) {
            $stmt_perm = $pdo->prepare("SELECT sucursal_id FROM usuarios_accesos WHERE usuario_id = ?");
            $stmt_perm->execute([$usuario_editar['id']]);
            $permisos_actuales = $stmt_perm->fetchAll(PDO::FETCH_COLUMN);
        }

        // Cargar empresas y sucursales para el formulario
        $stmt_emp = $pdo->query("SELECT * FROM empresas WHERE activa=1 ORDER BY nombre");
        $empresas_form = $stmt_emp->fetchAll(PDO::FETCH_ASSOC);
        $stmt_suc = $pdo->query("SELECT * FROM sucursales WHERE activa=1 ORDER BY nombre");
        $sucursales_form = $stmt_suc->fetchAll(PDO::FETCH_ASSOC);

        // Obtener todos los módulos disponibles
        $stmt_mod = $pdo->query("SELECT * FROM modulos ORDER BY nombre");
        $modulos_disponibles = $stmt_mod->fetchAll(PDO::FETCH_ASSOC);

        // Obtener permisos/módulos adicionales ya asignados al usuario
        $permisos_extra_actuales = [];
        if ($usuario_editar) {
            $stmt_pe = $pdo->prepare("SELECT modulo_id FROM permisos_usuarios WHERE usuario_id = ?");
            $stmt_pe->execute([$usuario_editar['id']]);
            $permisos_extra_actuales = $stmt_pe->fetchAll(PDO::FETCH_COLUMN);
        }

    } catch (PDOException $e) {
        $usuario_editar = null;
    }
}

if (!$usuario_editar) {
    echo "<div class='p-6 text-red-500'>Usuario no encontrado. ID buscado: " . htmlspecialchars($_GET['id'] ?? 'Nulo') . "</div>";
    if (isset($e))
        echo "<div class='p-6 text-red-500'>Error: " . $e->getMessage() . "</div>";
    return;
}
?>

<div class="p-6 flex-1 glass">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-slate-800 flex items-center gap-2">
            <i class="ri-user-settings-line text-blue-600"></i> Editar Usuario
        </h2>
        <a href="index.php?view=usuarios"
            class="text-slate-500 hover:text-slate-700 font-medium flex items-center gap-1">
            <i class="ri-arrow-left-line"></i> Volver al listado
        </a>
    </div>

    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-2xl shadow-xl shadow-slate-200/50 border border-slate-100 p-8">
            <form method="POST" action="index.php">
                <input type="hidden" name="csrf_token" value="<?php echo generar_csrf_token(); ?>">
                <input type="hidden" name="accion" value="actualizar_usuario">
                <input type="hidden" name="usuario_id" value="<?php echo $usuario_editar['id']; ?>">

                <div class="space-y-6">
                    <!-- Nombre Completo -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Nombre Completo</label>
                        <div class="relative">
                            <i class="ri-user-line absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                            <input type="text" name="nombre_usuario" required
                                value="<?php echo htmlspecialchars($usuario_editar['nombre_completo']); ?>"
                                class="w-full pl-10 pr-4 py-3 rounded-xl border border-slate-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition-all bg-slate-50 focus:bg-white">
                        </div>
                    </div>

                    <!-- Email -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Correo Electrónico</label>
                        <div class="relative">
                            <i class="ri-mail-line absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                            <input type="email" name="email" required
                                value="<?php echo htmlspecialchars($usuario_editar['email']); ?>"
                                class="w-full pl-10 pr-4 py-3 rounded-xl border border-slate-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition-all bg-slate-50 focus:bg-white">
                        </div>
                    </div>

                    <!-- Rol -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Rol del Usuario</label>
                        <div class="relative">
                            <i class="ri-shield-user-line absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                            <select name="rol" required
                                class="w-full pl-10 pr-4 py-3 rounded-xl border border-slate-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition-all bg-slate-50 focus:bg-white appearance-none cursor-pointer">
                                <?php foreach ($roles as $rol): ?>
                                    <option value="<?php echo $rol['id']; ?>" <?php echo ($usuario_editar['rol_id'] == $rol['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($rol['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400">
                                <i class="ri-arrow-down-s-line"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Empresa Asignada (solo para RRHH) — dinámico desde BD -->
                    <div id="contenedor_empresa_asignada" style="display:none;" class="pt-4 border-t border-slate-100">
                        <label class="block text-sm font-bold text-slate-700 mb-2">
                            <i class="ri-building-4-line text-teal-600"></i> Empresa Asignada
                        </label>
                        <p class="text-xs text-slate-500 mb-3">Seleccione la empresa a la que pertenece este usuario
                            RRHH:</p>
                        <div class="relative">
                            <i class="ri-building-line absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                            <select name="empresa_asignada"
                                class="w-full pl-10 pr-4 py-3 rounded-xl border border-slate-200 focus:border-teal-500 focus:ring-2 focus:ring-teal-200 outline-none transition-all bg-slate-50 focus:bg-white appearance-none cursor-pointer">
                                <option value="">-- Sin asignar --</option>
                                <?php foreach ($empresas_form as $e): ?>
                                    <option value="<?= $e['id'] ?>" <?= ($usuario_editar['empresa_asignada'] == $e['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($e['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400">
                                <i class="ri-arrow-down-s-line"></i>
                            </div>
                        </div>
                        <p class="text-xs text-amber-600 mt-2">
                            <i class="ri-information-line"></i> Las actas que genere este usuario mostrarán el logo de
                            la empresa seleccionada
                        </p>
                    </div>



                    <!-- Permisos de Visualización RRHH -->
                    <div id="contenedor_permisos_rrhh_edit" style="display:none;"
                        class="pt-4 border-t border-slate-100">
                        <label class="block text-sm font-bold text-slate-700 mb-2">
                            <i class="ri-eye-line text-blue-600"></i> Permisos de Visualización (RRHH)
                        </label>
                        <p class="text-xs text-slate-500 mb-3">Seleccione las sucursales a las que tendrá acceso:</p>
                        <div class="max-h-64 overflow-y-auto border border-slate-200 rounded-xl p-4 bg-slate-50">
                            <?php foreach ($empresas_form as $e): ?>
                                <div class="mb-4 bg-white rounded-lg p-3 border border-slate-100">
                                    <strong class="block text-sm text-slate-800 mb-2 pb-2 border-b border-slate-100">
                                        <i class="ri-building-line text-blue-500"></i>
                                        <?php echo htmlspecialchars($e['nombre']); ?>
                                    </strong>
                                    <div class="grid grid-cols-2 gap-2">
                                        <?php
                                        $sucs_empresa = array_filter($sucursales_form, function ($s) use ($e) {
                                            return $s['empresa_id'] == $e['id'];
                                        });
                                        if (empty($sucs_empresa)): ?>
                                            <span class="text-xs text-slate-400 col-span-2">Sin sucursales</span>
                                        <?php else: ?>
                                            <?php foreach ($sucs_empresa as $s):
                                                $checked = in_array($s['id'], $permisos_actuales) ? 'checked' : '';
                                                ?>
                                                <label
                                                    class="flex items-center gap-2 text-sm text-slate-600 cursor-pointer hover:text-blue-600">
                                                    <input type="checkbox" name="permisos_sucursal[]"
                                                        value="<?php echo $s['id']; ?>" <?php echo $checked; ?>
                                                        class="w-4 h-4 accent-blue-600 rounded">
                                                    <?php echo htmlspecialchars($s['nombre']); ?>
                                                </label>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>


                    <script>
                        function togglePermisosRRHHEdit() {
                            const select = document.querySelector('select[name="rol"]');
                            const selectedOption = select.options[select.selectedIndex];
                            const rolText = selectedOption.text.toUpperCase();
                            const contenedorPermisos = document.getElementById('contenedor_permisos_rrhh_edit');
                            const contenedorEmpresa = document.getElementById('contenedor_empresa_asignada');

                            if (rolText.includes('RRHH')) {
                                contenedorPermisos.style.display = 'block';
                                contenedorEmpresa.style.display = 'block';
                            } else {
                                contenedorPermisos.style.display = 'none';
                                contenedorEmpresa.style.display = 'none';
                            }
                        }

                        document.addEventListener('DOMContentLoaded', function () {
                            const selectRol = document.querySelector('select[name="rol"]');
                            if (selectRol) {
                                selectRol.addEventListener('change', togglePermisosRRHHEdit);
                                togglePermisosRRHHEdit();
                            }
                        });
                    </script>

                    <!-- Contraseña (Opcional) -->
                    <div class="pt-4 border-t border-slate-100">
                        <label class="block text-sm font-bold text-slate-700 mb-2">
                            Nueva Contraseña
                            <span class="text-xs font-normal text-slate-500 ml-2">(Dejar en blanco para mantener la
                                actual)</span>
                        </label>
                        <div class="relative">
                            <i
                                class="ri-lock-password-line absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                            <input type="password" name="password" placeholder="••••••••"
                                class="w-full pl-10 pr-4 py-3 rounded-xl border border-slate-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition-all bg-slate-50 focus:bg-white">
                        </div>
                    </div>
                </div>

                <!-- Botones Acción -->
                <div
                    class="flex justify-end gap-3 pt-6 border-t border-slate-100 mt-6 bg-white sticky bottom-0 p-4 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)] rounded-b-2xl z-10">
                    <a href="index.php?view=usuarios"
                        class="px-6 py-2.5 rounded-xl border border-slate-300 text-slate-600 font-bold hover:bg-slate-50 transition-colors">
                        Cancelar
                    </a>
                    <button type="submit"
                        class="px-8 py-2.5 rounded-xl bg-blue-600 text-white font-bold hover:bg-blue-700 shadow-lg shadow-blue-200 transition-all transform hover:-translate-y-0.5 flex items-center gap-2">
                        <i class="ri-save-line"></i> Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>