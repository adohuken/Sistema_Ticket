<?php
/**
 * seccion_personal_formulario.php - Registro de Nuevo Personal
 * Módulo de Gestión de Personal
 */

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validar CSRF
        if (!isset($_POST['csrf_token']) || !validar_csrf_token($_POST['csrf_token'])) {
            throw new Exception("Error de seguridad: Token inválido");
        }

        // Validar campos obligatorios
        $campos_requeridos = ['empresa_id', 'sucursal_id', 'nombres', 'apellidos', 'fecha_ingreso'];
        foreach ($campos_requeridos as $campo) {
            if (empty($_POST[$campo])) {
                throw new Exception("El campo $campo es obligatorio.");
            }
        }

        // Preparar datos para inserción
        $sql = "INSERT INTO personal (
            empresa_id, sucursal_id, codigo_empleado, nombres, apellidos, 
            cedula, fecha_nacimiento, genero, estado_civil,
            telefono, telefono_emergencia, email, direccion, ciudad, pais,
            cargo, departamento, fecha_ingreso, tipo_contrato, salario,
            usuario_sistema_id, estado, notas, creado_por
        ) VALUES (
            ?, ?, ?, ?, ?, 
            ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, 'Activo', ?, ?
        )";

        $stmt = $pdo->prepare($sql);

        // Manejar usuario del sistema (puede ser NULL)
        $usuario_sistema_id = !empty($_POST['usuario_sistema_id']) ? $_POST['usuario_sistema_id'] : null;

        $stmt->execute([
            $_POST['empresa_id'],
            $_POST['sucursal_id'],
            $_POST['codigo_empleado'],
            $_POST['nombres'],
            $_POST['apellidos'],
            $_POST['cedula'],
            $_POST['fecha_nacimiento'],
            $_POST['genero'],
            $_POST['estado_civil'],
            $_POST['telefono'],
            $_POST['telefono_emergencia'],
            $_POST['email'],
            $_POST['direccion'],
            $_POST['ciudad'],
            $_POST['pais'],
            $_POST['cargo'],
            $_POST['departamento'],
            $_POST['fecha_ingreso'],
            $_POST['tipo_contrato'],
            0, // Salario no utilizado
            $usuario_sistema_id,
            $_POST['notas'],
            $usuario_id // ID del usuario actual (global en index.php)
        ]);

        $mensaje_exito = "Personal registrado exitosamente.";

    } catch (Exception $e) {
        $mensaje_error = "Error al guardar: " . $e->getMessage();
    }
}

// Obtener datos para los selectores
try {
    // Obtener datos para los selectores
    $stmt = $pdo->query("SELECT * FROM empresas WHERE activa = 1 ORDER BY nombre");
    $empresas = $stmt->fetchAll();

    $stmt = $pdo->query("SELECT * FROM sucursales WHERE activa = 1 ORDER BY nombre");
    $sucursales = $stmt->fetchAll();

    // Contexto Multi-Sucursal (Filtrado)
    $usuario_sucursales_permitidas = $_SESSION['usuario_sucursales_permitidas'] ?? [];
    $es_superadmin = ($_SESSION['usuario_rol'] ?? '') === 'SuperAdmin';

    if (!$es_superadmin && !empty($usuario_sucursales_permitidas)) {
        // 1. Filter Branches
        $sucursales = array_filter($sucursales, function ($s) use ($usuario_sucursales_permitidas) {
            return in_array($s['id'], $usuario_sucursales_permitidas);
        });

        // 2. Filter Companies
        $allowed_company_ids = array_unique(array_column($sucursales, 'empresa_id'));
        $empresas = array_filter($empresas, function ($e) use ($allowed_company_ids) {
            return in_array($e['id'], $allowed_company_ids);
        });
    }

    // Usuarios del sistema disponibles
    $stmt = $pdo->query("SELECT id, nombre_completo as nombre, email FROM usuarios ORDER BY nombre_completo");
    $usuarios_sistema = $stmt->fetchAll();

} catch (PDOException $e) {
    $mensaje_error = "Error al cargar datos del sistema: " . $e->getMessage();
}
?>

<div class="p-6 ml-10"> <!-- Añadido margen izquierdo para compensar sidebar si es necesario -->

    <!-- Encabezado -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-slate-800 flex items-center gap-2">
                <i class="ri-user-add-line text-blue-600"></i> Nuevo Empleado
            </h2>
            <p class="text-slate-500 text-sm">Registro de nuevo personal en el sistema.</p>
        </div>
        <a href="index.php?view=personal"
            class="px-4 py-2 bg-slate-100 text-slate-600 hover:bg-slate-200 rounded-lg text-sm font-medium transition-colors">
            <i class="ri-arrow-left-line mr-1"></i> Volver a la Lista
        </a>
    </div>

    <!-- Mensajes de Estado -->
    <?php if (isset($mensaje_exito)): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm relative"
            role="alert">
            <strong class="font-bold"><i class="ri-checkbox-circle-line"></i> ¡Éxito!</strong>
            <span class="block sm:inline"><?php echo $mensaje_exito; ?></span>
            <script>
                setTimeout(function () { window.location.href = 'index.php?view=personal'; }, 1500);
            </script>
        </div>
    <?php endif; ?>

    <?php if (isset($mensaje_error)): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm" role="alert">
            <strong class="font-bold"><i class="ri-error-warning-line"></i> Error:</strong>
            <span class="block sm:inline"><?php echo $mensaje_error; ?></span>
        </div>
    <?php endif; ?>

    <form method="POST" action="" class="space-y-6">
        <?php echo campo_csrf(); ?>

        <!-- Sección 1: Datos de la Empresa -->
        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
            <h3
                class="text-lg font-semibold text-slate-700 mb-4 pb-2 border-b border-slate-100 flex items-center gap-2">
                <i class="ri-building-line text-blue-500"></i> Asignación Empresarial
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Empresa -->
                <div class="<?php echo (!$es_superadmin && $usuario_empresa_id) ? 'col-span-1 md:col-span-3' : ''; ?>">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Empresa <span
                            class="text-red-500">*</span></label>

                    <?php
                    // Contexto de aislamiento
                    // $es_superadmin y $usuario_sucursales_permitidas ya definidos arriba
                    $usuario_empresa_id = $_SESSION['usuario_empresa_id'] ?? null;
                    $tiene_multi_sucursal = !empty($usuario_sucursales_permitidas);

                    if (!$es_superadmin && !$tiene_multi_sucursal && $usuario_empresa_id):
                        // Visualización Fija para RRHH aislado (Empresa Única sin sucursales extra)
                        $empresa_nombre = 'Desconocida';
                        foreach ($empresas as $e) {
                            if ($e['id'] == $usuario_empresa_id)
                                $empresa_nombre = $e['nombre'];
                        }
                        ?>
                        <input type="hidden" name="empresa_id" id="empresa_select"
                            value="<?php echo $usuario_empresa_id; ?>">

                        <!-- Banner de Contexto -->
                        <div class="flex items-center gap-3 p-4 bg-blue-50 border border-blue-100 rounded-xl text-blue-800">
                            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="ri-building-4-line text-xl text-blue-600"></i>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-blue-500 uppercase tracking-wider mb-0.5">Asignación
                                    Automática</p>
                                <p class="font-bold text-lg"><?php echo htmlspecialchars($empresa_nombre); ?></p>
                            </div>
                            <div class="ml-auto">
                                <span
                                    class="px-3 py-1 bg-white/60 text-blue-700 text-xs font-bold rounded-lg border border-blue-100">
                                    <i class="ri-lock-line mr-1"></i> Fijo
                                </span>
                            </div>
                        </div>

                    <?php else: ?>
                        <!-- Visualización Normal para SuperAdmin O Multi-Sucursal (Con Lista Filtrada) -->
                        <select name="empresa_id" id="empresa_select" required
                            class="w-full bg-white border border-slate-300 text-slate-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5">
                            <option value="">Seleccione una empresa</option>
                            <?php foreach ($empresas as $emp): ?>
                                <option value="<?php echo $emp['id']; ?>"><?php echo htmlspecialchars($emp['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif; ?>
                </div>

                <!-- Sucursal -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Sucursal <span
                            class="text-red-500">*</span></label>
                    <select name="sucursal_id" id="sucursal_select" required
                        class="w-full bg-white border border-slate-300 text-slate-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5">
                        <option value="">Seleccione primero empresa</option>
                        <?php foreach ($sucursales as $suc): ?>
                            <option value="<?php echo $suc['id']; ?>" data-empresa="<?php echo $suc['empresa_id']; ?>"
                                style="display:none;">
                                <?php echo htmlspecialchars($suc['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Código Empleado -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Código Empleado</label>
                    <input type="text" name="codigo_empleado"
                        class="w-full bg-white border border-slate-300 text-slate-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5"
                        placeholder="Ej. EMP-001">
                </div>
            </div>
        </div>

        <!-- Sección 2: Información Personal -->
        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
            <h3
                class="text-lg font-semibold text-slate-700 mb-4 pb-2 border-b border-slate-100 flex items-center gap-2">
                <i class="ri-user-line text-blue-500"></i> Información Personal
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Nombres -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Nombres <span
                            class="text-red-500">*</span></label>
                    <input type="text" name="nombres" required
                        class="w-full bg-white border border-slate-300 text-slate-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5">
                </div>
                <!-- Apellidos -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Apellidos <span
                            class="text-red-500">*</span></label>
                    <input type="text" name="apellidos" required
                        class="w-full bg-white border border-slate-300 text-slate-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5">
                </div>
                <!-- Cédula -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Cédula / ID</label>
                    <input type="text" name="cedula"
                        class="w-full bg-white border border-slate-300 text-slate-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5">
                </div>
                <!-- Fecha Nacimiento -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Fecha de Nacimiento</label>
                    <input type="date" name="fecha_nacimiento"
                        class="w-full bg-white border border-slate-300 text-slate-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5">
                </div>
                <!-- Género -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Género</label>
                    <select name="genero"
                        class="w-full bg-white border border-slate-300 text-slate-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5">
                        <option value="">Seleccione</option>
                        <option value="Masculino">Masculino</option>
                        <option value="Femenino">Femenino</option>
                        <option value="Otro">Otro</option>
                    </select>
                </div>
                <!-- Estado Civil -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Estado Civil</label>
                    <select name="estado_civil"
                        class="w-full bg-white border border-slate-300 text-slate-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5">
                        <option value="">Seleccione</option>
                        <option value="Soltero">Soltero(a)</option>
                        <option value="Casado">Casado(a)</option>
                        <option value="Divorciado">Divorciado(a)</option>
                        <option value="Viudo">Viudo(a)</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Sección 3: Contacto y Dirección -->
        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
            <h3
                class="text-lg font-semibold text-slate-700 mb-4 pb-2 border-b border-slate-100 flex items-center gap-2">
                <i class="ri-map-pin-user-line text-blue-500"></i> Contacto y Dirección
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Teléfono Principal -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Teléfono Móvil</label>
                    <input type="text" name="telefono"
                        class="w-full bg-white border border-slate-300 text-slate-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5">
                </div>
                <!-- Teléfono Emergencia -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Teléfono Emergencia</label>
                    <input type="text" name="telefono_emergencia"
                        class="w-full bg-white border border-slate-300 text-slate-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5">
                </div>
                <!-- Email -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Correo Personal</label>
                    <input type="email" name="email"
                        class="w-full bg-white border border-slate-300 text-slate-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5">
                </div>
                <!-- Dirección (ocupa 3 columnas en md) -->
                <div class="md:col-span-3">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Dirección Domiciliar</label>
                    <input type="text" name="direccion"
                        class="w-full bg-white border border-slate-300 text-slate-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5">
                </div>
                <!-- Ciudad -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Ciudad</label>
                    <input type="text" name="ciudad"
                        class="w-full bg-white border border-slate-300 text-slate-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5">
                </div>
                <!-- País -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">País</label>
                    <input type="text" name="pais"
                        class="w-full bg-white border border-slate-300 text-slate-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5">
                </div>
            </div>
        </div>

        <!-- Sección 4: Información Laboral -->
        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
            <h3
                class="text-lg font-semibold text-slate-700 mb-4 pb-2 border-b border-slate-100 flex items-center gap-2">
                <i class="ri-briefcase-line text-blue-500"></i> Información Laboral
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Cargo -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Cargo / Puesto</label>
                    <input type="text" name="cargo"
                        class="w-full bg-white border border-slate-300 text-slate-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5">
                </div>
                <!-- Departamento -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Departamento</label>
                    <input type="text" name="departamento"
                        class="w-full bg-white border border-slate-300 text-slate-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5">
                </div>
                <!-- Fecha Ingreso -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Fecha de Ingreso <span
                            class="text-red-500">*</span></label>
                    <input type="date" name="fecha_ingreso" required
                        class="w-full bg-white border border-slate-300 text-slate-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5">
                </div>
                <!-- Tipo Contrato -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Tipo de Contrato</label>
                    <select name="tipo_contrato"
                        class="w-full bg-white border border-slate-300 text-slate-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5">
                        <option value="Indefinido">Indefinido / Permanente</option>
                        <option value="Determinado">Tiempo Determinado</option>
                        <option value="Servicios">Servicios Profesionales</option>
                        <option value="Proyecto">Por Proyecto</option>
                        <option value="Pasantia">Pasantía</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Sección 5: Vinculación Usuario de Sistema (Opcional) -->
        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
            <h3
                class="text-lg font-semibold text-slate-700 mb-4 pb-2 border-b border-slate-100 flex items-center gap-2">
                <i class="ri-id-card-line text-blue-500"></i> Acceso al Sistema (Opcional)
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Vincular con Usuario
                        Existente</label>
                    <select name="usuario_sistema_id"
                        class="w-full bg-slate-50 border border-slate-300 text-slate-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5">
                        <option value="">-- Sin usuario de sistema --</option>
                        <?php foreach ($usuarios_sistema as $usu): ?>
                            <option value="<?php echo $usu['id']; ?>">
                                <?php echo htmlspecialchars($usu['nombre'] . ' (' . $usu['email'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="mt-1 text-xs text-slate-500">Si selecciona un usuario, este perfil se vinculará a la
                        cuenta de acceso.</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Notas Adicionales</label>
                    <textarea name="notas" rows="3"
                        class="w-full bg-slate-50 border border-slate-300 text-slate-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5"></textarea>
                </div>
            </div>
        </div>

        <!-- Botones de Acción -->
        <div class="flex items-center justify-end gap-3 pt-4">
            <a href="index.php?view=personal"
                class="px-5 py-2.5 bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 rounded-lg text-sm font-medium transition-colors">
                Cancelar
            </a>
            <button type="submit"
                class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium shadow-md transition-colors flex items-center gap-2">
                <i class="ri-save-line"></i> Guardar Empleado
            </button>
        </div>

    </form>
</div>

<!-- Script para filtrar sucursales por empresa -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const empresaSelect = document.getElementById('empresa_select');
        const sucursalSelect = document.getElementById('sucursal_select');

        function filtrarSucursales() {
            const empresaId = empresaSelect.value;
            const opciones = sucursalSelect.querySelectorAll('option');
            let primeraVisible = null;

            opciones.forEach(opcion => {
                if (opcion.value === "") {
                    // Opción placeholder
                    opcion.textContent = empresaId ? "-- Seleccione Sucursal --" : "Seleccione primero empresa";
                    return;
                }

                const empresaData = opcion.getAttribute('data-empresa');
                if (empresaId && empresaData === empresaId) {
                    opcion.style.display = 'block';
                    if (!primeraVisible) primeraVisible = opcion.value;
                } else {
                    opcion.style.display = 'none';
                }
            });

            // Resetear selección si la actual no es válida
            const seleccionActual = sucursalSelect.options[sucursalSelect.selectedIndex];
            if (seleccionActual.style.display === 'none') {
                sucursalSelect.value = "";
            }
        }

        empresaSelect.addEventListener('change', filtrarSucursales);

        // Ejecutar al inicio por si hay valores preseleccionados (aunque en create suele estar vacío)
        filtrarSucursales();
    });
</script>