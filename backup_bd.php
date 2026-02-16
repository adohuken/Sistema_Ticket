<?php
/**
 * backup_bd.php - Backup de Base de Datos
 */

// Procesar creación de backup ANTES de cualquier HTML
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'crear_backup') {
    if ($rol_usuario === 'SuperAdmin' || in_array('backup_bd', $permisos_usuario ?? [])) {
        try {
            // Limpiar cualquier salida previa (headers HTML, etc)
            if (ob_get_length())
                ob_end_clean();

            $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';

            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Pragma: no-cache');
            header('Expires: 0');

            // Obtener todas las tablas
            $tables = [];
            $result = $pdo->query("SHOW TABLES");
            while ($row = $result->fetch(PDO::FETCH_NUM)) {
                $tables[] = $row[0];
            }

            $output = "-- Backup de Base de Datos - Sistema de Tickets\n";
            $output .= "-- Fecha: " . date('Y-m-d H:i:s') . "\n";
            $output .= "-- Generado por: $rol_usuario\n\n";

            $output .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
            $output .= "START TRANSACTION;\n";
            $output .= "SET time_zone = \"+00:00\";\n\n";

            foreach ($tables as $table) {
                // Estructura
                $output .= "-- Estructura de tabla para la tabla `$table`\n";
                $output .= "DROP TABLE IF EXISTS `$table`;\n";
                $create = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_ASSOC);
                $output .= $create['Create Table'] . ";\n\n";

                // Datos
                $output .= "-- Volcado de datos para la tabla `$table`\n";
                $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);

                if (count($rows) > 0) {
                    $output .= "INSERT INTO `$table` VALUES \n";
                    $values = [];
                    foreach ($rows as $row) {
                        $row_values = array_map(function ($v) use ($pdo) {
                            if ($v === null)
                                return 'NULL';
                            return $pdo->quote($v);
                        }, $row);
                        $values[] = "(" . implode(',', $row_values) . ")";
                    }
                    $output .= implode(",\n", $values) . ";\n";
                }
                $output .= "\n";
            }

            $output .= "COMMIT;\n";

            echo $output;
            exit;

        } catch (Exception $e) {
            $mensaje_error = "Error al crear backup: " . $e->getMessage();
        }
    }
}
?>

<div class="p-6 flex-1">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 mb-6">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-emerald-100 rounded-xl">
                    <i class="ri-database-2-line text-3xl text-emerald-600"></i>
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-slate-800">Backup de Base de Datos</h2>
                    <p class="text-slate-500">Crea un respaldo completo del sistema</p>
                </div>
            </div>
        </div>

        <?php if (isset($mensaje_error)): ?>
            <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-lg">
                <p class="text-red-700 font-bold">Error: <?php echo $mensaje_error; ?></p>
            </div>
        <?php endif; ?>

        <!-- Info Card -->
        <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6 rounded-lg">
            <div class="flex items-start gap-3">
                <i class="ri-information-line text-2xl text-blue-600 mt-1"></i>
                <div>
                    <h3 class="font-bold text-blue-900 mb-1">Información Importante</h3>
                    <ul class="text-sm text-blue-800 space-y-1">
                        <li>• El backup incluirá todas las tablas y datos del sistema</li>
                        <li>• El archivo se descargará automáticamente en formato SQL</li>
                        <li>• Guarda el archivo en un lugar seguro</li>
                        <li>• Recomendamos hacer backups periódicos</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Action Card -->
        <div class="bg-white rounded-2xl p-8 shadow-lg border border-slate-100">
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="accion" value="crear_backup">

                <div class="text-center mb-6">
                    <div class="inline-flex p-4 bg-emerald-100 rounded-full mb-4">
                        <i class="ri-save-line text-5xl text-emerald-600"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-800 mb-2">¿Crear Backup Ahora?</h3>
                    <p class="text-slate-600">Se generará un archivo SQL con todos los datos del sistema</p>
                </div>

                <div class="flex gap-4 justify-center">
                    <a href="index.php?view=config"
                        class="px-6 py-3 bg-slate-100 text-slate-700 rounded-xl font-semibold hover:bg-slate-200 transition-colors">
                        Cancelar
                    </a>
                    <button type="submit"
                        class="px-6 py-3 bg-emerald-600 text-white rounded-xl font-semibold hover:bg-emerald-700 transition-colors shadow-lg shadow-emerald-600/30">
                        <i class="ri-download-line mr-2"></i>
                        Crear y Descargar Backup
                    </button>
                </div>
            </form>
        </div>

        <!-- Recent Backups (placeholder) -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 mt-6">
            <h3 class="font-bold text-slate-800 mb-4 flex items-center gap-2">
                <i class="ri-history-line text-slate-600"></i>
                Backups Recientes
            </h3>
            <p class="text-slate-500 text-center py-8">
                <i class="ri-folder-open-line text-4xl mb-2 block text-slate-300"></i>
                Los backups se descargan directamente a tu equipo
            </p>
        </div>
    </div>
</div>