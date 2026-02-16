<?php
/**
 * seccion_registros_365_importar.php - Importación Masiva de Cuentas 365
 */

$mensaje_exito = "";
$errores = [];
$advertencias = [];
$procesados = 0;
$insertados = 0;

// Descargar plantilla
if (isset($_GET['action']) && $_GET['action'] == 'descargar_plantilla') {
    if (ob_get_level())
        ob_end_clean();
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="plantilla_cuentas_365.csv"');
    $output = fopen('php://output', 'w');
    fwrite($output, "\xEF\xBB\xBF"); // BOM UTF-8

    // Encabezados
    fputcsv($output, [
        'Email',
        'Usuario Asignado (Nombre Completo)',
        'Cargo',
        'Licencia',
        'Estado (Activo/Inactivo)',
        'Empresa',
        'Sucursal',
        'Password Azure',
        'Password AG',
        'Cuenta Gmail',
        'Password Gmail',
        'PIN Windows',
        'Teléfono Principal',
        'Teléfono Secundario',
        'Observaciones',
        'Notas Adicionales'
    ], ';');

    fclose($output);
    exit;
}

// Procesar Importación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo_csv'])) {
    if (empty($_POST['csrf_token']) || !validar_csrf_token($_POST['csrf_token'])) {
        $errores[] = "Error de seguridad: Token inválido.";
    } elseif ($_FILES['archivo_csv']['error'] == UPLOAD_ERR_OK) {
        $tmp_name = $_FILES['archivo_csv']['tmp_name'];

        try {
            $pdo->beginTransaction();

            if (($handle = fopen($tmp_name, "r")) !== FALSE) {
                // Detectar delimitador
                $firstLine = fgets($handle);
                rewind($handle);
                $delimiter = (strpos($firstLine, ';') !== false) ? ';' : ',';

                fgetcsv($handle, 0, $delimiter); // Saltar header

                // Prepara statements
                $stmtEmpresa = $pdo->prepare("SELECT id FROM empresas WHERE nombre LIKE ? LIMIT 1");
                $stmtSucursal = $pdo->prepare("SELECT id FROM sucursales WHERE nombre LIKE ? AND empresa_id = ? LIMIT 1");
                $stmtCargo = $pdo->prepare("SELECT id FROM cargos WHERE nombre LIKE ? LIMIT 1");

                // Búsqueda de usuario (En vista_personal_completo que tiene nombres y apellidos)
                $stmtUsuario = $pdo->prepare("
                    SELECT id FROM vista_personal_completo 
                    WHERE CONCAT(nombres, ' ', apellidos) LIKE ? 
                    LIMIT 1
                ");

                $stmtCheck = $pdo->prepare("SELECT id FROM registros_365 WHERE email = ?");

                $sqlInsert = "INSERT INTO registros_365 (
                    email, usuario_id, cargo_id, licencia, estado, empresa_id, sucursal_id, 
                    password_azure, password_ag, cuenta_gmail, password_gmail, 
                    pin_windows, telefono_principal, telefono_secundario,
                    observaciones, notas_adicionales
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmtInsert = $pdo->prepare($sqlInsert);

                while (($data = fgetcsv($handle, 1000, $delimiter)) !== FALSE) {
                    $procesados++;

                    // Mapeo
                    $email = trim($data[0] ?? '');
                    $usuario_nom = trim($data[1] ?? '');
                    $cargo_nom = trim($data[2] ?? '');
                    $licencia = trim($data[3] ?? '');
                    $estado = trim($data[4] ?? 'Activo');
                    $empresa_nom = trim($data[5] ?? '');
                    $sucursal_nom = trim($data[6] ?? '');
                    $pass_azure = trim($data[7] ?? '');
                    $pass_ag = trim($data[8] ?? '');
                    $gmail = trim($data[9] ?? '');
                    $pass_gmail = trim($data[10] ?? '');
                    $pin = trim($data[11] ?? '');
                    $tel1 = trim($data[12] ?? '');
                    $tel2 = trim($data[13] ?? '');
                    $obs = trim($data[14] ?? '');
                    $notas = trim($data[15] ?? '');

                    if (empty($email))
                        continue; // Saltar filas vacías

                    // Verificar si existe y Actualizar o Insertar
                    $stmtCheck->execute([$email]);
                    $existing_id = $stmtCheck->fetchColumn();

                    // Buscar Empresa
                    $empresa_id = null;
                    if ($empresa_nom) {
                        $stmtEmpresa->execute(["%$empresa_nom%"]);
                        $empresa_id = $stmtEmpresa->fetchColumn();
                        if (!$empresa_id) {
                            $errores[] = "Fila $procesados: Empresa '$empresa_nom' no encontrada.";
                            continue; // Empresa es obligatoria para la lógica de visualización normalmente
                        }
                    }

                    // Buscar Sucursal
                    $sucursal_id = null;
                    if ($sucursal_nom && $empresa_id) {
                        $stmtSucursal->execute(["%$sucursal_nom%", $empresa_id]);
                        $sucursal_id = $stmtSucursal->fetchColumn();
                        if (!$sucursal_id) {
                            $sucursal_id = null; // Evitar que sea false
                            $advertencias[] = "Fila $procesados: Sucursal '$sucursal_nom' no encontrada en '$empresa_nom'. Se importará sin sucursal.";
                        }
                    }

                    // Buscar Cargo
                    $cargo_id = null;
                    if ($cargo_nom) {
                        $stmtCargo->execute(["%$cargo_nom%"]);
                        $cargo_id = $stmtCargo->fetchColumn();
                        if (!$cargo_id) {
                            $cargo_id = null; // Evitar que sea false
                            $advertencias[] = "Fila $procesados: Cargo '$cargo_nom' no encontrado. Se importará sin cargo.";
                        }
                    }

                    // Buscar Usuario
                    $usuario_id = null;
                    if ($usuario_nom) {
                        $stmtUsuario->execute(["%$usuario_nom%"]);
                        $usuario_id = $stmtUsuario->fetchColumn();
                        if (!$usuario_id) {
                            $usuario_id = null; // Evitar false
                            $advertencias[] = "Fila $procesados: Usuario '$usuario_nom' no encontrado en el sistema. Se importará sin asignar.";
                        }
                    }

                    // Insertar
                    if (!$existing_id) {
                        $stmtInsert->execute([
                            $email,
                            $usuario_id,
                            $cargo_id,
                            $licencia,
                            $estado,
                            $empresa_id,
                            $sucursal_id,
                            $pass_azure,
                            $pass_ag,
                            $gmail,
                            $pass_gmail,
                            $pin,
                            $tel1,
                            $tel2,
                            $obs,
                            $notas
                        ]);
                        $insertados++;
                    } else {
                        // Actualizar existente
                        $sqlUpdate = "UPDATE registros_365 SET 
                            usuario_id = ?, cargo_id = ?, licencia = ?, estado = ?, empresa_id = ?, sucursal_id = ?, 
                            password_azure = ?, password_ag = ?, cuenta_gmail = ?, password_gmail = ?, 
                            pin_windows = ?, telefono_principal = ?, telefono_secundario = ?,
                            observaciones = ?, notas_adicionales = ?
                            WHERE id = ?";
                        $stmtUpdate = $pdo->prepare($sqlUpdate);
                        $stmtUpdate->execute([
                            $usuario_id,
                            $cargo_id,
                            $licencia,
                            $estado,
                            $empresa_id,
                            $sucursal_id,
                            $pass_azure,
                            $pass_ag,
                            $gmail,
                            $pass_gmail,
                            $pin,
                            $tel1,
                            $tel2,
                            $obs,
                            $notas,
                            $existing_id
                        ]);
                        // Se actualizó
                    }
                }
                fclose($handle);

                if (empty($errores)) {
                    $pdo->commit();
                    $mensaje_exito = "Se importaron $insertados cuentas exitosamente.";
                } else {
                    $pdo->rollBack();
                }
            } else {
                $errores[] = "No se pudo leer el archivo.";
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $errores[] = "Error crítico: " . $e->getMessage();
        }
    }
}
?>

<div class="p-6 flex-1">
    <div class="max-w-4xl mx-auto">
        <div class="mb-6 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-slate-800 flex items-center gap-3">
                    <span class="bg-blue-600 text-white p-2 rounded-xl shadow-lg shadow-blue-500/30">
                        <i class="ri-file-upload-line"></i>
                    </span>
                    Importar Cuentas 365
                </h1>
                <p class="text-slate-500 mt-2">Carga masiva desde archivo CSV (Excel)</p>
            </div>
            <a href="index.php?view=registros_365" class="text-slate-500 hover:text-blue-600 font-medium">
                <i class="ri-arrow-left-line"></i> Volver
            </a>
        </div>

        <?php if ($mensaje_exito): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm">
                <strong class="font-bold"><i class="ri-checkbox-circle-line"></i> ¡Éxito!</strong>
                <p><?= $mensaje_exito ?></p>
            </div>
        <?php endif; ?>

        <?php if (!empty($errores)): ?>
            <div class="bg-red-50 border border-red-200 rounded-xl p-6 mb-6">
                <h3 class="text-red-800 font-bold flex items-center gap-2 mb-3">
                    <i class="ri-error-warning-fill text-xl"></i> Errores encontrados
                </h3>
                <ul class="list-disc list-inside text-sm text-red-700 space-y-1 max-h-60 overflow-y-auto">
                    <?php foreach ($errores as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (!empty($advertencias)): ?>
            <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-6 mb-6">
                <h3 class="text-yellow-800 font-bold flex items-center gap-2 mb-3">
                    <i class="ri-alert-fill text-xl"></i> Advertencias
                </h3>
                <ul class="list-disc list-inside text-sm text-yellow-700 space-y-1 max-h-60 overflow-y-auto">
                    <?php foreach ($advertencias as $adv): ?>
                        <li><?= htmlspecialchars($adv) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Instrucciones -->
            <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
                <h3 class="font-bold text-slate-800 mb-4 flex items-center gap-2">
                    <i class="ri-information-line text-blue-500"></i> Pasos para importar
                </h3>
                <ol class="list-decimal pl-4 space-y-3 text-sm text-slate-600">
                    <li>Descargue la <strong>plantilla CSV Actualizada</strong>.</li>
                    <li>Abra el archivo en Excel y llene los datos.</li>
                    <li>
                        Coloque el <strong>Usuario</strong> y <strong>Cargo</strong> tal como aparecen en el sistema.
                        <br><span class="text-xs text-slate-400">Si no se encuentran, la cuenta quedará sin
                            asignar.</span>
                    </li>
                    <li>Las <strong>Empresas</strong> deben coincidir exactamente.</li>
                    <li>Guarde el archivo manteniendo el formato CSV.</li>
                    <li>Súbalo utilizando el formulario de la derecha.</li>
                </ol>
                <div class="mt-6">
                    <a href="index.php?view=registros_365_importar&action=descargar_plantilla"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-slate-100 text-slate-700 hover:bg-slate-200 rounded-lg font-medium transition-colors">
                        <i class="ri-download-2-line"></i> Descargar Plantilla
                    </a>
                </div>
            </div>

            <!-- Upload -->
            <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
                <form action="" method="POST" enctype="multipart/form-data" class="space-y-4">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

                    <label class="block text-sm font-medium text-slate-700">Archivo CSV</label>
                    <div class="flex items-center justify-center w-full">
                        <label for="archivo_csv"
                            class="flex flex-col items-center justify-center w-full h-32 border-2 border-slate-300 border-dashed rounded-lg cursor-pointer bg-slate-50 hover:bg-slate-100 transition-colors">
                            <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                <i class="ri-upload-cloud-2-line text-3xl text-slate-400 mb-2"></i>
                                <p class="text-sm text-slate-500">Clic para seleccionar</p>
                            </div>
                            <input id="archivo_csv" name="archivo_csv" type="file" accept=".csv" class="hidden" required
                                onchange="document.getElementById('fname').textContent = this.files[0].name" />
                        </label>
                    </div>
                    <p id="fname" class="text-center text-xs text-blue-600 font-semibold h-4"></p>

                    <button type="submit"
                        class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg shadow-lg flex justify-center items-center gap-2 transition-all">
                        <i class="ri-save-line"></i> Importar Datos
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>