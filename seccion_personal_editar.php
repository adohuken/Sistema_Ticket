<?php
/**
 * seccion_personal_editar.php - Editar Información de Personal
 * Módulo de Gestión de Personal
 */

// Verificar ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<div class='p-6'><div class='bg-red-100 text-red-700 p-4 rounded'>ID no especificado.</div></div>";
    return;
}

$personal_id = $_GET['id'];
$mensaje_exito = null;
$mensaje_error = null;

// Obtener datos actuales
try {
    $stmt = $pdo->prepare("SELECT * FROM personal WHERE id = ?");
    $stmt->execute([$personal_id]);
    $empleado = $stmt->fetch();

    if (!$empleado) {
        echo "<div class='p-6'><div class='bg-red-100 text-red-700 p-4 rounded'>Empleado no encontrado.</div></div>";
        return;
    }

    // VERIFICACIÓN DE AISLAMIENTO (RRHH)
    $es_superadmin = ($_SESSION['usuario_rol'] ?? '') === 'SuperAdmin';
    $usuario_empresa_id = $_SESSION['usuario_empresa_id'] ?? null;
    $usuario_sucursales_permitidas = $_SESSION['usuario_sucursales_permitidas'] ?? [];
    
    $acceso_permitido = true;

    if (!$es_superadmin) {
        if (!empty($usuario_sucursales_permitidas)) {
            // Caso Multi-Sucursal: Verificar si la sucursal del empleado está permitida
            if (!in_array($empleado['sucursal_id'], $usuario_sucursales_permitidas)) {
                $acceso_permitido = false;
            }
        } elseif ($usuario_empresa_id) {
            // Caso Empresa Única: Verificar empresa
            if ($empleado['empresa_id'] != $usuario_empresa_id) {
                $acceso_permitido = false;
            }
        } else {
            $acceso_permitido = false; // Ni empresa ni sucursales
        }
    }

    if (!$acceso_permitido) {
        echo "<div class='p-6'>
                <div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded shadow-sm'>
                    <h3 class='font-bold flex items-center'><i class='ri-lock-2-line mr-2'></i> Acceso Denegado</h3>
                    <p>No tienes permisos para editar este registro.</p>
                </div>
              </div>";
        return;
    }

} catch (PDOException $e) {
    die("Error al cargar datos: " . $e->getMessage());
}

// Procesar Actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validar CSRF
        if (!isset($_POST['csrf_token']) || !validar_csrf_token($_POST['csrf_token'])) {
            throw new Exception("Error de seguridad: Token inválido");
        }

        // Validar campos obligatorios
        $requeridos = ['empresa_id', 'sucursal_id', 'nombres', 'apellidos', 'estado'];
        foreach ($requeridos as $campo) {
            if (empty($_POST[$campo])) throw new Exception("El campo $campo es obligatorio.");
        }

        // --- INICIO AUDITORÍA (Historial de Cambios) ---
        $campos_monitoreados = [
            'empresa_id', 'sucursal_id', 'codigo_empleado', 'nombres', 'apellidos',
            'cedula', 'fecha_nacimiento', 'genero', 'estado_civil',
            'telefono', 'telefono_emergencia', 'email', 'direccion', 'ciudad', 'pais',
            'cargo', 'departamento', 'fecha_ingreso', 'tipo_contrato', 
            'usuario_sistema_id', 'estado', 'notas', 'fecha_salida'
        ];

        $stmt_audit = $pdo->prepare("INSERT INTO historial_cambios (entidad_tipo, entidad_id, usuario_id, campo_modificado, valor_anterior, valor_nuevo) VALUES ('personal', ?, ?, ?, ?, ?)");

        foreach ($campos_monitoreados as $campo) {
            $nuevo_valor = $_POST[$campo] ?? null;
            $antiguo_valor = $empleado[$campo] ?? null;

            // Manejo especial para valores nulos o vacíos para evitar falsos positivos
            if (trim((string)$nuevo_valor) !== trim((string)$antiguo_valor)) {
                $stmt_audit->execute([
                    $personal_id, 
                    $usuario_id, 
                    $campo, 
                    (string)$antiguo_valor, 
                    (string)$nuevo_valor
                ]);
            }
        }
        // --- FIN AUDITORÍA ---

        // Actualizar
        $sql = "UPDATE personal SET 
            empresa_id = ?, sucursal_id = ?, codigo_empleado = ?, nombres = ?, apellidos = ?,
            cedula = ?, fecha_nacimiento = ?, genero = ?, estado_civil = ?,
            telefono = ?, telefono_emergencia = ?, email = ?, direccion = ?, ciudad = ?, pais = ?,
            cargo = ?, departamento = ?, fecha_ingreso = ?, tipo_contrato = ?,
            usuario_sistema_id = ?, estado = ?, notas = ?, fecha_salida = ?,
            modificado_por = ?, fecha_modificacion = CURRENT_TIMESTAMP
            WHERE id = ?";

        $stmt = $pdo->prepare($sql);
        
        // Manejar valores nulos/vacíos
        $usuario_sistema_id = !empty($_POST['usuario_sistema_id']) ? $_POST['usuario_sistema_id'] : null;
        $fecha_salida = !empty($_POST['fecha_salida']) ? $_POST['fecha_salida'] : null;
        
        // Si el estado cambia a 'Retirado' y no hay fecha de salida, poner la de hoy
        if ($_POST['estado'] === 'Retirado' && empty($fecha_salida)) {
            $fecha_salida = date('Y-m-d');
        }

        $stmt->execute([
            $_POST['empresa_id'], $_POST['sucursal_id'], $_POST['codigo_empleado'], $_POST['nombres'], $_POST['apellidos'],
            $_POST['cedula'], $_POST['fecha_nacimiento'], $_POST['genero'], $_POST['estado_civil'],
            $_POST['telefono'], $_POST['telefono_emergencia'], $_POST['email'], $_POST['direccion'], $_POST['ciudad'], $_POST['pais'],
            $_POST['cargo'], $_POST['departamento'], $_POST['fecha_ingreso'], $_POST['tipo_contrato'],
            $usuario_sistema_id, $_POST['estado'], $_POST['notas'], $fecha_salida,
            $usuario_id, // Modificado por
            $personal_id
        ]);

        $mensaje_exito = "Información actualizada correctamente.";
        
        // Recargar datos actualizados
        $stmt = $pdo->prepare("SELECT * FROM personal WHERE id = ?");
        $stmt->execute([$personal_id]);
        $empleado = $stmt->fetch();

    } catch (Exception $e) {
        $mensaje_error = "Error al actualizar: " . $e->getMessage();
    }
}

// Cargar listas para selectores
try {
    $empresas = $pdo->query("SELECT * FROM empresas WHERE activa = 1 ORDER BY nombre")->fetchAll();
    $sucursales = $pdo->query("SELECT * FROM sucursales WHERE activa = 1 ORDER BY nombre")->fetchAll();
    $usuarios_sistema = $pdo->query("SELECT id, nombre_completo as nombre, email FROM usuarios ORDER BY nombre_completo")->fetchAll();

    // FILTERING OPTIONS (Dropdowns) para Multi-Sucursal en Edición
    if (!$es_superadmin && !empty($usuario_sucursales_permitidas)) {
        // 1. Filter Branches
        $sucursales = array_filter($sucursales, function($s) use ($usuario_sucursales_permitidas) {
            return in_array($s['id'], $usuario_sucursales_permitidas);
        });
        
        // 2. Filter Companies
        $allowed_company_ids = array_unique(array_column($sucursales, 'empresa_id'));
        $empresas = array_filter($empresas, function($e) use ($allowed_company_ids) {
             // Permitir también la empresa actual del empleado por si acaso es una transferencia
             return in_array($e['id'], $allowed_company_ids);
        });
    }
} catch (PDOException $e) {
    die("Error al cargar listas: " . $e->getMessage());
}
?>

<div class="p-6 ml-10">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-slate-800 flex items-center gap-2">
                <i class="ri-edit-2-line text-blue-600"></i> Editar Empleado
            </h2>
            <p class="text-slate-500 text-sm">
                Editando a: <strong><?php echo htmlspecialchars($empleado['nombres'] . ' ' . $empleado['apellidos']); ?></strong>
            </p>
        </div>
        <div class="flex gap-2">
            <a href="index.php?view=personal_detalle&id=<?php echo $personal_id; ?>" class="px-4 py-2 bg-white border border-slate-300 text-slate-700 hover:bg-white rounded-lg text-sm font-medium transition-colors">
                <i class="ri-eye-line mr-1"></i> Ver Detalle
            </a>
            <a href="index.php?view=personal" class="px-4 py-2 bg-slate-100 text-slate-600 hover:bg-slate-200 rounded-lg text-sm font-medium transition-colors">
                <i class="ri-arrow-left-line mr-1"></i> Lista
            </a>
        </div>
    </div>

    <?php if ($mensaje_exito): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm relative">
            <strong class="font-bold"><i class="ri-checkbox-circle-line"></i> ¡Actualizado!</strong>
            <span class="block sm:inline"><?php echo $mensaje_exito; ?></span>
        </div>
    <?php endif; ?>

    <?php if ($mensaje_error): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm">
            <strong class="font-bold"><i class="ri-error-warning-line"></i> Error:</strong>
            <span class="block sm:inline"><?php echo $mensaje_error; ?></span>
        </div>
    <?php endif; ?>

    <form method="POST" action="" class="space-y-6">
        <?php echo campo_csrf(); ?>
        
        <!-- Estado y Control -->
        <div class="bg-blue-50 p-6 rounded-xl border border-blue-100 shadow-sm flex flex-col md:flex-row gap-6 items-center justify-between">
            <div class="w-full md:w-auto">
                <h3 class="text-lg font-bold text-blue-900 mb-1">Estado del Empleado</h3>
                <p class="text-xs text-blue-700">El estado determina el acceso y visibilidad.</p>
            </div>
            <div class="w-full md:w-auto flex gap-4">
                <div class="flex-1">
                    <label class="block text-xs font-bold text-blue-800 mb-1 uppercase">Estado Actual</label>
                    <select name="estado" class="w-full bg-white border border-blue-200 text-slate-800 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5 font-semibold">
                        <option value="Activo" <?php echo $empleado['estado'] == 'Activo' ? 'selected' : ''; ?>>Activo</option>
                        <option value="Inactivo" <?php echo $empleado['estado'] == 'Inactivo' ? 'selected' : ''; ?>>Inactivo</option>
                        <option value="Suspendido" <?php echo $empleado['estado'] == 'Suspendido' ? 'selected' : ''; ?>>Suspendido</option>
                        <option value="Retirado" <?php echo $empleado['estado'] == 'Retirado' ? 'selected' : ''; ?>>Retirado</option>
                    </select>
                </div>
                <div class="flex-1">
                    <label class="block text-xs font-bold text-blue-800 mb-1 uppercase">Fecha Salida</label>
                    <input type="date" name="fecha_salida" value="<?php echo $empleado['fecha_salida'] ?? ''; ?>"
                        class="w-full bg-white border border-blue-200 text-slate-800 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5">
                </div>
            </div>
        </div>

        <!-- Sección 1: Datos de la Empresa (Transferencias) -->
        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm relative overflow-hidden">
            <div class="absolute top-0 right-0 p-4 opacity-10 pointer-events-none">
                <i class="ri-building-line text-9xl"></i>
            </div>
            <h3 class="text-lg font-semibold text-slate-700 mb-4 pb-2 border-b border-slate-100 flex items-center gap-2">
                <i class="ri-exchange-line text-blue-500"></i> Ubicación y Cargo
                <span class="text-xs font-normal text-slate-400 ml-2 bg-white px-2 py-1 rounded border border-slate-100">(Los cambios aquí generan historial)</span>
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 relative z-10">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Empresa</label>
                    
                    <?php if (!$es_superadmin && $usuario_empresa_id): ?>
                        <!-- Advertencia de Transferencia -->
                        <div class="mb-2 p-2 bg-yellow-50 border border-yellow-200 rounded-lg flex items-start gap-2">
                            <i class="ri-alert-line text-yellow-600 mt-0.5"></i>
                            <div class="text-xs text-yellow-800">
                                <strong>Advertencia:</strong> Si cambias la empresa, este empleado desaparecerá de tu lista y perderás el acceso a él.
                            </div>
                        </div>
                    <?php endif; ?>

                    <select name="empresa_id" id="empresa_select" required
                        class="w-full bg-white border border-slate-300 text-slate-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5">
                        <?php foreach ($empresas as $emp): ?>
                            <option value="<?php echo $emp['id']; ?>" <?php echo $empleado['empresa_id'] == $emp['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($emp['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Sucursal</label>
                    <select name="sucursal_id" id="sucursal_select" required
                        class="w-full bg-white border border-slate-300 text-slate-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5">
                        <?php foreach ($sucursales as $suc): ?>
                            <option value="<?php echo $suc['id']; ?>" 
                                data-empresa="<?php echo $suc['empresa_id']; ?>"
                                <?php echo $empleado['sucursal_id'] == $suc['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($suc['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Código Empleado</label>
                    <input type="text" name="codigo_empleado" value="<?php echo htmlspecialchars($empleado['codigo_empleado'] ?? ''); ?>"
                        class="w-full bg-white border border-slate-300 text-slate-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Cargo / Puesto</label>
                    <input type="text" name="cargo" value="<?php echo htmlspecialchars($empleado['cargo'] ?? ''); ?>"
                        class="w-full bg-white border border-slate-300 text-slate-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Departamento</label>
                    <input type="text" name="departamento" value="<?php echo htmlspecialchars($empleado['departamento'] ?? ''); ?>"
                        class="w-full bg-white border border-slate-300 text-slate-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5">
                </div>
            </div>
        </div>

        <!-- Sección 2: Información Personal -->
        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-700 mb-4 pb-2 border-b border-slate-100">Información Personal</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Nombres</label>
                    <input type="text" name="nombres" value="<?php echo htmlspecialchars($empleado['nombres']); ?>" required
                        class="w-full bg-white border border-slate-300 text-slate-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Apellidos</label>
                    <input type="text" name="apellidos" value="<?php echo htmlspecialchars($empleado['apellidos']); ?>" required
                        class="w-full bg-white border border-slate-300 text-slate-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Cédula</label>
                    <input type="text" name="cedula" value="<?php echo htmlspecialchars($empleado['cedula'] ?? ''); ?>"
                        class="w-full bg-white border border-slate-300 text-slate-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Fecha Nacimiento</label>
                    <input type="date" name="fecha_nacimiento" value="<?php echo $empleado['fecha_nacimiento'] ?? ''; ?>"
                        class="w-full bg-white border border-slate-300 text-slate-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Teléfono</label>
                    <input type="text" name="telefono" value="<?php echo htmlspecialchars($empleado['telefono'] ?? ''); ?>"
                        class="w-full bg-white border border-slate-300 text-slate-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($empleado['email'] ?? ''); ?>"
                        class="w-full bg-slate-50 border border-slate-300 text-slate-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5">
                </div>
            </div>
            
                <!-- Género -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Género</label>
                    <select name="genero" class="w-full bg-white border border-slate-300 text-slate-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5">
                        <option value="">Seleccione</option>
                        <option value="Masculino" <?php echo ($empleado['genero'] == 'Masculino') ? 'selected' : ''; ?>>Masculino</option>
                        <option value="Femenino" <?php echo ($empleado['genero'] == 'Femenino') ? 'selected' : ''; ?>>Femenino</option>
                        <option value="Otro" <?php echo ($empleado['genero'] == 'Otro') ? 'selected' : ''; ?>>Otro</option>
                    </select>
                </div>
                <!-- Estado Civil -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Estado Civil</label>
                    <select name="estado_civil" class="w-full bg-white border border-slate-300 text-slate-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5">
                        <option value="">Seleccione</option>
                        <option value="Soltero" <?php echo ($empleado['estado_civil'] == 'Soltero') ? 'selected' : ''; ?>>Soltero(a)</option>
                        <option value="Casado" <?php echo ($empleado['estado_civil'] == 'Casado') ? 'selected' : ''; ?>>Casado(a)</option>
                        <option value="Divorciado" <?php echo ($empleado['estado_civil'] == 'Divorciado') ? 'selected' : ''; ?>>Divorciado(a)</option>
                        <option value="Viudo" <?php echo ($empleado['estado_civil'] == 'Viudo') ? 'selected' : ''; ?>>Viudo(a)</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Sección 3: Contacto y Dirección -->
        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-700 mb-4 pb-2 border-b border-slate-100 flex items-center gap-2">
                <i class="ri-map-pin-user-line text-blue-500"></i> Contacto y Dirección
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                 <!-- Teléfono Móvil (Ya estaba arriba pero lo movemos aquí para agrupar mejor si se desea, o lo dejamos y agregamos los faltantes) -->
                 <!-- Voy a dejar Telefono y Email arriba en personal y poner aquí los extra -->
                 
                 <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Teléfono Emergencia</label>
                    <input type="text" name="telefono_emergencia" value="<?php echo htmlspecialchars($empleado['telefono_emergencia'] ?? ''); ?>"
                        class="w-full bg-white border border-slate-300 text-slate-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5">
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Dirección Domiciliar</label>
                    <input type="text" name="direccion" value="<?php echo htmlspecialchars($empleado['direccion'] ?? ''); ?>"
                        class="w-full bg-white border border-slate-300 text-slate-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Ciudad</label>
                    <input type="text" name="ciudad" value="<?php echo htmlspecialchars($empleado['ciudad'] ?? ''); ?>"
                        class="w-full bg-white border border-slate-300 text-slate-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">País</label>
                    <input type="text" name="pais" value="<?php echo htmlspecialchars($empleado['pais'] ?? ''); ?>"
                        class="w-full bg-white border border-slate-300 text-slate-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5">
                </div>
            </div>
        </div>

        <!-- Sección 4: Información Laboral (Detalle) -->
        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-700 mb-4 pb-2 border-b border-slate-100 flex items-center gap-2">
                <i class="ri-briefcase-line text-blue-500"></i> Detalle Laboral
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Fecha de Ingreso</label>
                    <input type="date" name="fecha_ingreso" value="<?php echo $empleado['fecha_ingreso']; ?>" required
                        class="w-full bg-white border border-slate-300 text-slate-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Tipo de Contrato</label>
                    <select name="tipo_contrato" class="w-full bg-white border border-slate-300 text-slate-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5">
                        <option value="Indefinido" <?php echo ($empleado['tipo_contrato'] == 'Indefinido') ? 'selected' : ''; ?>>Indefinido / Permanente</option>
                        <option value="Determinado" <?php echo ($empleado['tipo_contrato'] == 'Determinado') ? 'selected' : ''; ?>>Tiempo Determinado</option>
                        <option value="Servicios" <?php echo ($empleado['tipo_contrato'] == 'Servicios') ? 'selected' : ''; ?>>Servicios Profesionales</option>
                        <option value="Proyecto" <?php echo ($empleado['tipo_contrato'] == 'Proyecto') ? 'selected' : ''; ?>>Por Proyecto</option>
                        <option value="Pasantia" <?php echo ($empleado['tipo_contrato'] == 'Pasantia') ? 'selected' : ''; ?>>Pasantía</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Sección 5: Sistema y Notas -->
        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
             <h3 class="text-lg font-semibold text-slate-700 mb-4 pb-2 border-b border-slate-100 flex items-center gap-2">
                <i class="ri-id-card-line text-blue-500"></i> Sistema y Notas
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Vincular con Usuario Sistema</label>
                    <select name="usuario_sistema_id" class="w-full bg-white border border-slate-300 text-slate-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5">
                        <option value="">-- Sin usuario de sistema --</option>
                        <?php foreach ($usuarios_sistema as $usu): ?>
                            <option value="<?php echo $usu['id']; ?>" <?php echo ($empleado['usuario_sistema_id'] == $usu['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($usu['nombre'] . ' (' . $usu['email'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Notas Adicionales</label>
                    <textarea name="notas" rows="3" class="w-full bg-white border border-slate-300 text-slate-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5"><?php echo htmlspecialchars($empleado['notas'] ?? ''); ?></textarea>
                </div>
            </div>

        <div class="flex justify-end gap-4">
            <a href="index.php?view=personal" class="px-6 py-3 bg-white border border-slate-300 text-slate-700 rounded-lg font-medium hover:bg-slate-50 transition-colors">Cancelar</a>
            <button type="submit" class="px-6 py-3 bg-blue-600 text-white rounded-lg font-bold hover:bg-blue-700 transition-colors shadow-lg">
                Guardar Cambios
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const empresaSelect = document.getElementById('empresa_select');
    const sucursalSelect = document.getElementById('sucursal_select');
    const sucursalActual = "<?php echo $empleado['sucursal_id']; ?>";
    
    function filtrarSucursales() {
        const empresaId = empresaSelect.value;
        const opciones = sucursalSelect.querySelectorAll('option');
        let encontrada = false;

        opciones.forEach(opcion => {
            const empresaData = opcion.getAttribute('data-empresa');
            if (empresaId && empresaData === empresaId) {
                opcion.style.display = 'block';
                if (opcion.value === sucursalActual) encontrada = true;
            } else {
                opcion.style.display = 'none';
            }
        });

        // Si la sucursal actual no pertenece a la nueva empresa seleccionada, resetear
        if (!encontrada && empresaId != "<?php echo $empleado['empresa_id']; ?>") {
            sucursalSelect.value = "";
        }
    }

    empresaSelect.addEventListener('change', filtrarSucursales);
    filtrarSucursales(); // Ejecutar al cargar
});
</script>
