<?php
/**
 * reiniciar_bd.php - Reiniciar Base de Datos
 * Permite resetear el sistema a su estado inicial
 */

$mensaje_exito = '';
$mensaje_error = '';

// Procesar reinicio
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'reiniciar_bd') {
    // Validar CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $mensaje_error = 'Error de seguridad: Token CSRF inválido.';
    } elseif ($rol_usuario === 'SuperAdmin' || in_array('reiniciar_bd', $permisos_usuario ?? [])) {
        try {
            // Verificar confirmación
            if (!isset($_POST['confirmar_reinicio']) || $_POST['confirmar_reinicio'] !== 'REINICIAR') {
                throw new Exception('Debe escribir REINICIAR para confirmar la acción.');
            }

            // Deshabilitar verificación de claves foráneas
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

            // Guardar datos del usuario actual antes de vaciar
            $usuario_actual_id = $_SESSION['usuario_id'];
            $stmt_user = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
            $stmt_user->execute([$usuario_actual_id]);
            $usuario_backup = $stmt_user->fetch(PDO::FETCH_ASSOC);

            // Obtener todas las tablas
            $result = $pdo->query("SHOW TABLES");
            $tables = $result->fetchAll(PDO::FETCH_COLUMN);

            // Tablas a preservar (sistema base)
            $tablas_sistema = ['roles', 'modulos', 'permisos_roles'];

            // Vaciar tablas de datos (excepto las del sistema)
            foreach ($tables as $table) {
                if (!in_array($table, $tablas_sistema)) {
                    $pdo->exec("TRUNCATE TABLE `$table`");
                }
            }

            // Rehabilitar verificación de claves foráneas
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

            // Restaurar el usuario SuperAdmin que hizo el reinicio
            if ($usuario_backup) {
                $stmt = $pdo->prepare("INSERT INTO usuarios (id, nombre_completo, email, password, rol_id, empresa_id, sucursal_id) 
                                       VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $usuario_backup['id'],
                    $usuario_backup['nombre_completo'],
                    $usuario_backup['email'],
                    $usuario_backup['password'],
                    $usuario_backup['rol_id'],
                    $usuario_backup['empresa_id'] ?? null,
                    $usuario_backup['sucursal_id'] ?? null
                ]);
            }

            // Insertar categorías por defecto
            $pdo->exec("INSERT INTO categorias (nombre) VALUES 
                ('Hardware'), ('Software'), ('Redes'), ('Cuentas y Accesos'), ('Otros')");

            $mensaje_exito = '✅ Base de datos reiniciada exitosamente. Su usuario ha sido preservado. Puede continuar usando el sistema.';

        } catch (Exception $e) {
            $mensaje_error = 'Error al reiniciar: ' . $e->getMessage();
            try {
                $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
            } catch (Exception $ex) {
            }
        }
    } else {
        $mensaje_error = 'Acceso denegado.';
    }
}
?>

<div class="p-6 flex-1">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 mb-6">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-red-100 rounded-xl">
                    <i class="ri-restart-line text-3xl text-red-600"></i>
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-slate-800">Reiniciar Base de Datos</h2>
                    <p class="text-slate-500">Restaurar el sistema a su estado inicial</p>
                </div>
            </div>
        </div>

        <!-- Mensajes -->
        <?php if ($mensaje_exito): ?>
            <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6 rounded-lg">
                <div class="flex items-start gap-3">
                    <i class="ri-checkbox-circle-line text-2xl text-green-600 mt-1"></i>
                    <div>
                        <h3 class="font-bold text-green-900 mb-1">¡Éxito!</h3>
                        <p class="text-sm text-green-800"><?php echo $mensaje_exito; ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($mensaje_error): ?>
            <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-lg">
                <div class="flex items-start gap-3">
                    <i class="ri-error-warning-line text-2xl text-red-600 mt-1"></i>
                    <div>
                        <h3 class="font-bold text-red-900 mb-1">Error</h3>
                        <p class="text-sm text-red-800"><?php echo htmlspecialchars($mensaje_error); ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Advertencia Crítica -->
        <div class="bg-red-50 border-2 border-red-300 rounded-2xl p-6 mb-6">
            <div class="flex items-start gap-4">
                <div class="p-3 bg-red-200 rounded-full">
                    <i class="ri-alarm-warning-line text-3xl text-red-700"></i>
                </div>
                <div>
                    <h3 class="font-bold text-red-900 text-lg mb-2">⚠️ ADVERTENCIA CRÍTICA</h3>
                    <ul class="text-sm text-red-800 space-y-2">
                        <li class="flex items-start gap-2">
                            <i class="ri-error-warning-fill text-red-600 mt-0.5"></i>
                            Esta acción <strong>ELIMINARÁ TODOS LOS DATOS</strong> del sistema
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="ri-error-warning-fill text-red-600 mt-0.5"></i>
                            Se borrarán: tickets, usuarios, inventario, personal, formularios, etc.
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="ri-error-warning-fill text-red-600 mt-0.5"></i>
                            Esta acción es <strong>IRREVERSIBLE</strong>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="ri-error-warning-fill text-red-600 mt-0.5"></i>
                            Solo se preservarán las tablas del sistema (roles, módulos, permisos)
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Formulario de Reinicio -->
        <div class="bg-white rounded-2xl p-8 shadow-lg border border-slate-100">
            <form method="POST" action="" id="formReiniciar">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="accion" value="reiniciar_bd">

                <div class="text-center mb-6">
                    <div class="inline-flex p-4 bg-red-100 rounded-full mb-4">
                        <i class="ri-delete-bin-7-line text-5xl text-red-600"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-800 mb-2">Confirmar Reinicio del Sistema</h3>
                    <p class="text-slate-600">Para confirmar, escriba <strong class="text-red-600">REINICIAR</strong> en
                        el campo de abajo</p>
                </div>

                <!-- Campo de confirmación -->
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                        Escriba "REINICIAR" para confirmar:
                    </label>
                    <input type="text" name="confirmar_reinicio" required
                        class="w-full px-4 py-3 border-2 border-red-300 rounded-xl focus:border-red-500 focus:ring-4 focus:ring-red-500/20 outline-none text-center text-lg uppercase tracking-widest"
                        placeholder="REINICIAR" autocomplete="off">
                </div>

                <!-- Confirmación checkbox -->
                <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" required class="mt-1 w-5 h-5 accent-red-600 rounded">
                        <span class="text-sm text-red-800">
                            <strong>Confirmo que:</strong> Entiendo que esta acción eliminará permanentemente todos
                            los datos del sistema y que esta operación es IRREVERSIBLE.
                        </span>
                    </label>
                </div>

                <!-- Botones -->
                <div class="flex justify-between items-center">
                    <a href="index.php?view=config"
                        class="px-6 py-3 bg-slate-100 text-slate-700 rounded-xl font-semibold hover:bg-slate-200 transition-colors">
                        <i class="ri-arrow-left-line mr-1"></i>
                        Cancelar
                    </a>
                    <button type="submit"
                        class="px-6 py-3 bg-red-600 text-white rounded-xl font-semibold hover:bg-red-700 transition-colors shadow-lg shadow-red-600/30">
                        <i class="ri-restart-line mr-1"></i>
                        Reiniciar Base de Datos
                    </button>
                </div>
            </form>
        </div>

        <!-- Información Adicional -->
        <div class="bg-slate-50 rounded-2xl p-6 mt-6 border border-slate-200">
            <h3 class="font-bold text-slate-800 mb-3 flex items-center gap-2">
                <i class="ri-information-line text-blue-600"></i>
                Después de reiniciar
            </h3>
            <ul class="space-y-2 text-sm text-slate-700">
                <li class="flex gap-2">
                    <span class="font-bold text-blue-600">•</span>
                    <span>Se creará un usuario administrador: <strong>admin@ticketsys.com / admin123</strong></span>
                </li>
                <li class="flex gap-2">
                    <span class="font-bold text-blue-600">•</span>
                    <span>Se restaurarán las categorías por defecto</span>
                </li>
                <li class="flex gap-2">
                    <span class="font-bold text-blue-600">•</span>
                    <span>Deberá cerrar sesión y volver a iniciar</span>
                </li>
                <li class="flex gap-2">
                    <span class="font-bold text-blue-600">•</span>
                    <span>Se recomienda crear un backup antes de reiniciar</span>
                </li>
            </ul>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.getElementById('formReiniciar').addEventListener('submit', function (e) {
        e.preventDefault();

        const confirmInput = document.querySelector('input[name="confirmar_reinicio"]');
        const checkbox = document.querySelector('input[type="checkbox"]');

        if (confirmInput.value.toUpperCase() !== 'REINICIAR') {
            Swal.fire({
                icon: 'error',
                title: 'Confirmación incorrecta',
                text: 'Debe escribir REINICIAR exactamente para confirmar la acción.',
                confirmButtonColor: '#dc2626'
            });
            return;
        }

        if (!checkbox.checked) {
            Swal.fire({
                icon: 'warning',
                title: 'Confirmación requerida',
                text: 'Debe marcar la casilla de confirmación para continuar.',
                confirmButtonColor: '#dc2626'
            });
            return;
        }

        Swal.fire({
            title: '⚠️ ÚLTIMA ADVERTENCIA',
            html: `
                <div style="text-align: left;">
                    <p style="color: #dc2626; font-weight: bold; margin-bottom: 12px;">Esta acción:</p>
                    <ul style="font-size: 14px; color: #374151; line-height: 1.8;">
                        <li>🗑️ <strong>ELIMINARÁ</strong> todos los tickets</li>
                        <li>👥 <strong>ELIMINARÁ</strong> todos los usuarios</li>
                        <li>📦 <strong>ELIMINARÁ</strong> todo el inventario</li>
                        <li>👤 <strong>ELIMINARÁ</strong> todo el personal</li>
                        <li>📝 <strong>ELIMINARÁ</strong> todos los formularios</li>
                    </ul>
                    <div style="margin-top: 16px; padding: 12px; background: #fef2f2; border-radius: 8px;">
                        <p style="color: #991b1b; font-weight: bold; text-align: center;">
                            ❌ Esta operación es IRREVERSIBLE
                        </p>
                    </div>
                </div>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
            confirmButtonText: '🗑️ Sí, REINICIAR TODO',
            cancelButtonText: 'Cancelar',
            reverseButtons: true,
            focusCancel: true
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: '🔴 CONFIRMACIÓN FINAL',
                    text: '¿REALMENTE desea eliminar TODOS los datos del sistema?',
                    icon: 'error',
                    showCancelButton: true,
                    confirmButtonColor: '#dc2626',
                    cancelButtonColor: '#22c55e',
                    confirmButtonText: 'ELIMINAR TODO',
                    cancelButtonText: '¡NO, Cancelar!',
                    reverseButtons: true,
                    focusCancel: true
                }).then((finalResult) => {
                    if (finalResult.isConfirmed) {
                        Swal.fire({
                            title: 'Reiniciando...',
                            text: 'Por favor espere.',
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            didOpen: () => { Swal.showLoading(); }
                        });
                        document.getElementById('formReiniciar').submit();
                    }
                });
            }
        });
    });
</script>