<?php
/**
 * restaurar_bd.php - Restaurar Base de Datos desde SQL
 */

$mensaje_exito = '';
$mensaje_error = '';

// Procesar restauración
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'restaurar_backup') {
    // Validar CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $mensaje_error = 'Error de seguridad: Token CSRF inválido.';
    } elseif ($rol_usuario === 'SuperAdmin' || in_array('restaurar_bd', $permisos_usuario ?? [])) {
        
        if (isset($_FILES['archivo_sql']) && $_FILES['archivo_sql']['error'] === UPLOAD_ERR_OK) {
            $tmp_name = $_FILES['archivo_sql']['tmp_name'];
            $name = $_FILES['archivo_sql']['name'];
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

            if ($ext !== 'sql') {
                $mensaje_error = "Error: El archivo debe tener extensión .sql";
            } else {
                try {
                    // Aumentar límites para archivos grandes
                    ini_set('memory_limit', '512M');
                    set_time_limit(300);

                    $sql_content = file_get_contents($tmp_name);
                    
                    // Deshabilitar FK
                    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
                    
                    // Eliminar comentarios y limpiar
                    $lines = explode("\n", $sql_content);
                    $clean_sql = "";
                    foreach ($lines as $line) {
                        $line = trim($line);
                        if ($line && substr($line, 0, 2) !== '--' && substr($line, 0, 1) !== '#') {
                            $clean_sql .= $line . "\n";
                        }
                    }

                    // Separar por ; (Básico - puede fallar con strings que contengan ;)
                    // Una mejor aproximación para dumps estándar
                    $queries = explode(";\n", $clean_sql);

                    foreach ($queries as $query) {
                        $query = trim($query);
                        if (!empty($query)) {
                            $pdo->exec($query);
                        }
                    }

                    // Rehabilitar FK
                    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

                    $mensaje_exito = "✅ Base de datos restaurada correctamente desde: " . htmlspecialchars($name);
                    registrar_actividad("Restaurar BD", "Sistema restaurado desde archivo: $name", $pdo);

                } catch (Exception $e) {
                    $mensaje_error = "Error al procesar SQL: " . $e->getMessage();
                    try { $pdo->exec('SET FOREIGN_KEY_CHECKS = 1'); } catch(Exception $x){}
                }
            }
        } else {
            $mensaje_error = "Error: Debe seleccionar un archivo SQL válido.";
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
                <div class="p-3 bg-blue-100 rounded-xl">
                    <i class="ri-upload-cloud-line text-3xl text-blue-600"></i>
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-slate-800">Restaurar Base de Datos</h2>
                    <p class="text-slate-500">Importar una copia de seguridad (archivo .sql)</p>
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

        <!-- Advertencia -->
        <div class="bg-amber-50 border-2 border-amber-200 rounded-2xl p-6 mb-6">
            <div class="flex items-start gap-4">
                <div class="p-3 bg-amber-100 rounded-full">
                    <i class="ri-alert-line text-3xl text-amber-600"></i>
                </div>
                <div>
                    <h3 class="font-bold text-amber-900 text-lg mb-2">⚠️ ATENCIÓN</h3>
                    <p class="text-sm text-amber-800 mb-2">
                        Esta acción <strong>SOBRESCRIBIRÁ</strong> los datos actuales con los del archivo de respaldo.
                    </p>
                    <ul class="text-sm text-amber-800 list-disc list-inside">
                        <li>Asegúrese de cargar el archivo correcto.</li>
                        <li>Se recomienda hacer un backup actual antes de restaurar una versión anterior.</li>
                        <li>El proceso puede tardar unos minutos dependiendo del tamaño del archivo.</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Formulario -->
        <div class="bg-white rounded-2xl p-8 shadow-lg border border-slate-100">
            <form method="POST" action="" enctype="multipart/form-data" id="formRestaurar">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="accion" value="restaurar_backup">

                <div class="mb-8 text-center">
                    <label for="archivo_sql" class="cursor-pointer block w-full border-2 border-dashed border-slate-300 rounded-xl p-10 hover:bg-slate-50 transition-colors group">
                        <div class="flex flex-col items-center">
                            <i class="ri-file-upload-line text-5xl text-slate-400 group-hover:text-blue-500 transition-colors mb-4"></i>
                            <span class="text-lg font-semibold text-slate-700 mb-2">Haga clic para seleccionar el archivo .SQL</span>
                            <span class="text-sm text-slate-500">o arrastre y suelte el archivo aquí</span>
                            <p id="fileName" class="mt-4 text-blue-600 font-bold hidden"></p>
                        </div>
                        <input type="file" name="archivo_sql" id="archivo_sql" accept=".sql" class="hidden" required onchange="updateFileName(this)">
                    </label>
                </div>

                <div class="flex justify-between items-center">
                    <a href="index.php?view=config" class="px-6 py-3 bg-slate-100 text-slate-700 rounded-xl font-semibold hover:bg-slate-200 transition-colors">
                        Cancelar
                    </a>
                    <button type="submit" class="px-6 py-3 bg-blue-600 text-white rounded-xl font-semibold hover:bg-blue-700 transition-colors shadow-lg shadow-blue-600/30">
                        <i class="ri-upload-2-line mr-2"></i>
                        Iniciar Restauración
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function updateFileName(input) {
        const display = document.getElementById('fileName');
        if (input.files && input.files.length > 0) {
            display.textContent = "Archivo seleccionado: " + input.files[0].name;
            display.classList.remove('hidden');
        } else {
            display.classList.add('hidden');
        }
    }
    
    document.getElementById('formRestaurar').addEventListener('submit', function(e) {
        const file = document.getElementById('archivo_sql').files[0];
        if (!file) {
            e.preventDefault();
            alert('Por favor seleccione un archivo');
            return;
        }
        
        if (!confirm('¿Está seguro de que desea restaurar la base de datos? Esto reemplazará los datos actuales.')) {
            e.preventDefault();
        }
    });
</script>
