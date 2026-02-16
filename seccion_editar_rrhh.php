<?php
/**
 * seccion_editar_rrhh.php
 * Editar formulario RRHH (Ingreso o Salida)
 * RESTRICCIÓN: Solo se puede editar si NO está asignado a técnico
 */

// Validar ID
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    echo "<div class='p-6 text-red-500'>ID de formulario inválido.</div>";
    return;
}

// Obtener datos del formulario
try {
    $stmt = $pdo->prepare("
        SELECT f.*, u.nombre_completo as creador_nombre
        FROM formularios_rrhh f
        LEFT JOIN usuarios u ON f.creado_por = u.id
        WHERE f.id = ?
    ");
    $stmt->execute([$id]);
    $formulario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$formulario) {
        echo "<div class='p-6 text-red-500'>Formulario no encontrado.</div>";
        return;
    }

    // VALIDACIÓN CRÍTICA: Verificar que NO esté asignado
    if (isset($formulario['asignado_a']) && $formulario['asignado_a'] !== null) {
        ?>
        <div class="p-6 flex-1">
            <div class="max-w-2xl mx-auto">
                <div class="bg-red-50 border-2 border-red-200 rounded-2xl p-8 text-center">
                    <i class="ri-lock-line text-6xl text-red-500 mb-4"></i>
                    <h2 class="text-2xl font-bold text-red-800 mb-2">Formulario Bloqueado</h2>
                    <p class="text-red-600 mb-4">
                        Este formulario ya está asignado a un técnico y no puede ser editado.
                    </p>
                    <a href="index.php?view=formularios_rrhh"
                        class="inline-block px-6 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                        <i class="ri-arrow-left-line"></i> Volver al Historial
                    </a>
                </div>
            </div>
        </div>
        <?php
        return;
    }

} catch (PDOException $e) {
    echo "<div class='p-6 text-red-500'>Error: " . $e->getMessage() . "</div>";
    return;
}

$es_ingreso = $formulario['tipo'] === 'Ingreso';
?>

<div class="p-6 flex-1">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-slate-800 flex items-center gap-2">
            <i class="ri-edit-line text-blue-600"></i>
            Editar Formulario de <?= $es_ingreso ? 'Ingreso' : 'Salida' ?>
        </h2>
        <a href="index.php?view=formularios_rrhh"
            class="text-slate-500 hover:text-slate-700 font-medium flex items-center gap-1">
            <i class="ri-arrow-left-line"></i> Volver al listado
        </a>
    </div>

    <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-6">
        <p class="text-sm text-amber-800">
            <i class="ri-information-line"></i>
            <strong>Nota:</strong> Este formulario puede editarse porque aún no ha sido asignado a un técnico.
        </p>
    </div>

    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-2xl shadow-xl shadow-slate-200/50 border border-slate-100 p-8">
            <form method="POST" action="procesar_editar_rrhh.php">
                <input type="hidden" name="csrf_token" value="<?php echo generar_csrf_token(); ?>">
                <input type="hidden" name="formulario_id" value="<?= $formulario['id'] ?>">
                <input type="hidden" name="tipo" value="<?= $formulario['tipo'] ?>">

                <!-- Datos del Colaborador -->
                <div class="mb-8">
                    <h3 class="text-lg font-bold text-slate-800 mb-4 border-b pb-2">
                        <i class="ri-user-line text-blue-600"></i> Datos del Colaborador
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Nombre Completo</label>
                            <input type="text" name="nombre_colaborador" required
                                value="<?= htmlspecialchars($formulario['nombre_colaborador']) ?>"
                                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Cédula / Teléfono</label>
                            <input type="text" name="cedula_telefono" required
                                value="<?= htmlspecialchars($formulario['cedula_telefono']) ?>"
                                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Cargo / Zona</label>
                            <input type="text" name="cargo_zona" required
                                value="<?= htmlspecialchars($formulario['cargo_zona']) ?>"
                                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Fecha
                                <?= $es_ingreso ? 'Solicitud' : 'Efectiva Salida' ?></label>
                            <input type="date" name="<?= $es_ingreso ? 'fecha_solicitud' : 'fecha_efectiva' ?>" required
                                value="<?= $es_ingreso ? ($formulario['fecha_solicitud'] ?? '') : ($formulario['fecha_efectiva'] ?? '') ?>"
                                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                </div>

                <?php if ($es_ingreso): ?>
                    <!-- Campos específicos de Ingreso -->
                    <div class="mb-8">
                        <h3 class="text-lg font-bold text-slate-800 mb-4 border-b pb-2">
                            <i class="ri-mail-settings-line text-green-600"></i> Configuración de Correo
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Disponibilidad Licencias</label>
                                <select name="disponibilidad_licencias" required
                                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <option value="SI" <?= $formulario['disponibilidad_licencias'] === 'SI' ? 'selected' : '' ?>>SI</option>
                                    <option value="NO" <?= $formulario['disponibilidad_licencias'] === 'NO' ? 'selected' : '' ?>>NO</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Detalle Licencias</label>
                                <input type="text" name="detalle_licencias"
                                    value="<?= htmlspecialchars($formulario['detalle_licencias'] ?? '') ?>"
                                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Crear Correo Nuevo</label>
                                <select name="correo_nuevo" required
                                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <option value="SI" <?= $formulario['correo_nuevo'] === 'SI' ? 'selected' : '' ?>>SI
                                    </option>
                                    <option value="NO" <?= $formulario['correo_nuevo'] === 'NO' ? 'selected' : '' ?>>NO
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Dirección de Correo</label>
                                <input type="email" name="direccion_correo"
                                    value="<?= htmlspecialchars($formulario['direccion_correo'] ?? '') ?>"
                                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                    </div>

                    <div class="mb-8">
                        <h3 class="text-lg font-bold text-slate-800 mb-4 border-b pb-2">
                            <i class="ri-computer-line text-purple-600"></i> Asignación de Equipos
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Asignación de Equipo</label>
                                <select name="asignacion_equipo" required
                                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <option value="SI" <?= $formulario['asignacion_equipo'] === 'SI' ? 'selected' : '' ?>>SI
                                    </option>
                                    <option value="NO" <?= $formulario['asignacion_equipo'] === 'NO' ? 'selected' : '' ?>>NO
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Detalle Asignación</label>
                                <input type="text" name="detalle_asignacion"
                                    value="<?= htmlspecialchars($formulario['detalle_asignacion'] ?? '') ?>"
                                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                    </div>

                <?php else: ?>
                    <!-- Campos específicos de Salida -->
                    <div class="mb-8">
                        <h3 class="text-lg font-bold text-slate-800 mb-4 border-b pb-2">
                            <i class="ri-mail-close-line text-red-600"></i> Gestión de Correo
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Bloqueo de Correo</label>
                                <select name="bloqueo_correo" required
                                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <option value="SI" <?= $formulario['bloqueo_correo'] === 'SI' ? 'selected' : '' ?>>SI
                                    </option>
                                    <option value="NO" <?= $formulario['bloqueo_correo'] === 'NO' ? 'selected' : '' ?>>NO
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Cuenta Correo Bloqueo</label>
                                <input type="text" name="cuenta_correo_bloqueo"
                                    value="<?= htmlspecialchars($formulario['cuenta_correo_bloqueo'] ?? '') ?>"
                                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Respaldo de Información</label>
                                <select name="respaldo_info" required
                                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <option value="SI" <?= ($formulario['respaldo_info'] ?? '') === 'SI' ? 'selected' : '' ?>>
                                        SI
                                    </option>
                                    <option value="NO" <?= ($formulario['respaldo_info'] ?? '') === 'NO' ? 'selected' : '' ?>>
                                        NO
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Detalle Respaldo</label>
                                <input type="text" name="detalle_respaldo_salida"
                                    value="<?= htmlspecialchars($formulario['detalle_respaldo_salida'] ?? '') ?>"
                                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>

                        <!-- New Redirection Fields -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Redirección de Correo</label>
                                <select name="redireccion_correo" required
                                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <option value="SI" <?= ($formulario['redireccion_correo'] ?? '') === 'SI' ? 'selected' : '' ?>>
                                        SI
                                    </option>
                                    <option value="NO" <?= ($formulario['redireccion_correo'] ?? '') === 'NO' ? 'selected' : '' ?>>
                                        NO
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Email para Redirección</label>
                                <input type="email" name="email_redireccion"
                                    value="<?= htmlspecialchars($formulario['email_redireccion'] ?? '') ?>"
                                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                    placeholder="correo@destino.com">
                            </div>
                        </div>
                    </div>

                    <div class="mb-8">
                        <h3 class="text-lg font-bold text-slate-800 mb-4 border-b pb-2">
                            <i class="ri-device-recover-line text-orange-600"></i> Devolución de Equipos
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Devolución PC/Laptop</label>
                                <select name="devolucion_equipo" required
                                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <option value="SI" <?= ($formulario['devolucion_equipo'] ?? '') === 'SI' ? 'selected' : '' ?>>SI
                                    </option>
                                    <option value="NO" <?= ($formulario['devolucion_equipo'] ?? '') === 'NO' ? 'selected' : '' ?>>NO
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Devolución Móvil</label>
                                <select name="devolucion_movil" required
                                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <option value="SI" <?= $formulario['devolucion_movil'] === 'SI' ? 'selected' : '' ?>>SI
                                    </option>
                                    <option value="NO" <?= $formulario['devolucion_movil'] === 'NO' ? 'selected' : '' ?>>NO
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Observaciones -->
                <div class="mb-8">
                    <label class="block text-sm font-bold text-slate-700 mb-2">Otras Indicaciones</label>
                    <?php
                    $campo_observaciones = $es_ingreso ? 'otras_indicaciones' : 'observaciones';
                    $valor_observaciones = $formulario[$campo_observaciones] ?? '';
                    ?>
                    <textarea name="<?= $campo_observaciones ?>" rows="4"
                        class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars($valor_observaciones) ?></textarea>
                </div>

                <!-- Botones -->
                <div class="flex gap-4 pt-6 border-t">
                    <a href="index.php?view=formularios_rrhh"
                        class="flex-1 py-3 px-4 rounded-xl border border-slate-200 text-slate-600 font-bold hover:bg-slate-50 transition-colors text-center">
                        Cancelar
                    </a>
                    <button type="submit"
                        class="flex-1 py-3 px-4 rounded-xl bg-blue-600 text-white font-bold hover:bg-blue-700 shadow-lg shadow-blue-200 transition-all transform hover:-translate-y-0.5">
                        <i class="ri-save-line"></i> Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>