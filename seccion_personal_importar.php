<?php
/**
 * seccion_personal_importar.php - Importación Masiva de Personal
 * Permite cargar empleados desde un archivo CSV
 */

$mensaje_exito = "";
$errores = [];
$procesados = 0;
$insertados = 0;

// Descargar plantilla
if (isset($_GET['action']) && $_GET['action'] == 'descargar_plantilla') {
    // Limpiar buffer de salida para evitar que se mezcle con HTML del index
    if (ob_get_level())
        ob_end_clean();
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="plantilla_personal.csv"');
    $output = fopen('php://output', 'w');
    // BOM para UTF-8 en Excel
    fwrite($output, "\xEF\xBB\xBF");
    // Cabeceras con punto y coma
    fputcsv($output, ['Codigo_Empleado', 'Nombres', 'Apellidos', 'Cedula', 'Codigo_Empresa', 'Codigo_Sucursal', 'Cargo', 'Fecha_Ingreso (YYYY-MM-DD)', 'Telefono'], ';');
    // Ejemplo con punto y coma
    fputcsv($output, ['EMP-001', 'Juan', 'Perez', '001-000000-0000A', 'MTL', 'MTL-LEON', 'Tecnico', '2024-01-15', '8888-8888'], ';');
    fclose($output);
    exit;
}

// Procesar subida
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo_csv'])) {
    // Validar CSRF
    if (empty($_POST['csrf_token']) || !validar_csrf_token($_POST['csrf_token'])) {
        $errores[] = "Error de seguridad: Token CSRF inválido. Por favor recargue la página.";
    } elseif ($_FILES['archivo_csv']['error'] == UPLOAD_ERR_OK) {
        $tmp_name = $_FILES['archivo_csv']['tmp_name'];


        try {
            $pdo->beginTransaction();

            if (($handle = fopen($tmp_name, "r")) !== FALSE) {
                // Detectar separador (coma o punto y coma)
                $firstLine = fgets($handle);
                rewind($handle); // Volver al inicio
                $delimiter = (strpos($firstLine, ';') !== false) ? ';' : ',';

                // Leer cabecera y descartarla usando el delimitador correcto
                fgetcsv($handle, 0, $delimiter);

                // Preparar selects para validación rápida
                $stmtEmpresa = $pdo->prepare("SELECT id FROM empresas WHERE codigo = ? LIMIT 1");
                $stmtSucursal = $pdo->prepare("SELECT id FROM sucursales WHERE codigo = ? AND empresa_id = ? LIMIT 1");
                $stmtCheck = $pdo->prepare("SELECT id FROM personal WHERE codigo_empleado = ? OR cedula = ?");

                // Preparar insert
                $sqlInsert = "INSERT INTO personal (
                    codigo_empleado, nombres, apellidos, cedula, empresa_id, sucursal_id, 
                    cargo, fecha_ingreso, salario, telefono, estado, creado_por
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, ?, 'Activo', ?)";
                $stmtInsert = $pdo->prepare($sqlInsert);

                while (($data = fgetcsv($handle, 1000, $delimiter)) !== FALSE) {
                    $procesados++;

                    // Mapear columnas (Asegúrate que coincida con la plantilla)
                    // 0: Codigo_Empleado, 1: Nombres, 2: Apellidos, 3: Cedula, 
                    // 4: Codigo_Empresa, 5: Codigo_Sucursal, 6: Cargo, 
                    // 7: Fecha_Ingreso, 8: Salario, 9: Telefono

                    $codigo_emp = trim($data[0] ?? '');
                    $nombres = trim($data[1] ?? '');
                    $apellidos = trim($data[2] ?? '');
                    $cedula = trim($data[3] ?? '');
                    $cod_empresa = trim($data[4] ?? '');
                    $cod_sucursal = trim($data[5] ?? '');
                    $cargo = trim($data[6] ?? '');
                    $fecha_ingreso = trim($data[7] ?? date('Y-m-d'));
                    // $salario eliminado index 8
                    $telefono = trim($data[8] ?? '');

                    // Validaciones básicas
                    $faltantes = [];
                    if (empty($nombres))
                        $faltantes[] = "Nombres";
                    if (empty($apellidos))
                        $faltantes[] = "Apellidos";
                    // if (empty($cod_empresa))
                    //     $faltantes[] = "Código Empresa";
                    // if (empty($cod_sucursal))
                    //     $faltantes[] = "Código Sucursal";

                    if (!empty($faltantes)) {
                        $errores[] = "Fila $procesados: Faltan datos obligatorios: " . implode(", ", $faltantes);
                        continue;
                    }

                    // 1. Validar Empresa (Opcional)
                    $empresa_id = null;
                    if (!empty($cod_empresa)) {
                        $stmtEmpresa->execute([$cod_empresa]);
                        $empresa_id = $stmtEmpresa->fetchColumn();
                        if (!$empresa_id) {
                            $errores[] = "Fila $procesados: Código de empresa '$cod_empresa' no existe.";
                            continue;
                        }
                    }

                    // 2. Validar Sucursal (Opcional)
                    $sucursal_id = null;
                    if (!empty($cod_sucursal)) {
                        if (!$empresa_id) {
                            $errores[] = "Fila $procesados: No se puede asignar sucursal sin empresa.";
                            continue;
                        }
                        $stmtSucursal->execute([$cod_sucursal, $empresa_id]);
                        $sucursal_id = $stmtSucursal->fetchColumn();
                        if (!$sucursal_id) {
                            $errores[] = "Fila $procesados: Código de sucursal '$cod_sucursal' no pertenece a la empresa '$cod_empresa'.";
                            continue;
                        }
                    }

                    // 3. Validar Duplicados
                    if (!empty($codigo_emp) || !empty($cedula)) {
                        $stmtCheck->execute([$codigo_emp, $cedula]);
                        if ($stmtCheck->fetch()) {
                            $errores[] = "Fila $procesados: Empleado ya existe (Código '$codigo_emp' o Cédula '$cedula').";
                            continue;
                        }
                    }

                    // Insertar
                    $stmtInsert->execute([
                        $codigo_emp,
                        $nombres,
                        $apellidos,
                        $cedula,
                        $empresa_id,
                        $sucursal_id,
                        $cargo,
                        $fecha_ingreso,
                        $telefono,
                        $usuario_id
                    ]);
                    $insertados++;
                }
                fclose($handle);

                if (empty($errores)) {
                    $pdo->commit();
                    $mensaje_exito = "Se procesaron $procesados filas y se crearon $insertados empleados correctamente.";
                } else {
                    $pdo->rollBack(); // Si hay errores, no guardamos nada para evitar datos a medias
                    $mensaje_exito = "No se importaron datos debido a errores en el archivo.";
                }
            } else {
                $errores[] = "No se pudo abrir el archivo CSV.";
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $errores[] = "Error del sistema: " . $e->getMessage();
        }
    } else {
        $errores[] = "Error al subir el archivo.";
    }
}
?>

<div class="p-6 ml-10">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-slate-800 flex items-center gap-2">
                <i class="ri-file-upload-line text-blue-600"></i> Importar Personal
            </h2>
            <p class="text-slate-500 text-sm">Carga masiva de empleados mediante archivo CSV.</p>
        </div>
        <a href="index.php?view=personal"
            class="px-4 py-2 bg-slate-100 text-slate-600 hover:bg-slate-200 rounded-lg text-sm font-medium transition-colors">
            <i class="ri-arrow-left-line mr-1"></i> Volver a la Lista
        </a>
    </div>

    <!-- Resultados -->
    <?php if ($mensaje_exito && empty($errores)): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm">
            <strong class="font-bold"><i class="ri-checkbox-circle-line"></i> ¡Importación Exitosa!</strong>
            <p><?php echo $mensaje_exito; ?></p>
        </div>
    <?php endif; ?>

    <?php if (!empty($errores)): ?>
        <div class="bg-red-50 border border-red-200 rounded-xl p-6 mb-6">
            <h3 class="text-red-800 font-bold flex items-center gap-2 mb-3">
                <i class="ri-error-warning-fill text-xl"></i> Errores encontrados
            </h3>
            <p class="text-red-600 mb-2">La importación se canceló. Corrige los siguientes errores y vuelve a intentarlo:
            </p>
            <ul class="list-disc list-inside text-sm text-red-700 space-y-1 max-h-60 overflow-y-auto">
                <?php foreach ($errores as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Instrucciones y Formulario -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Tarjeta de Instrucciones -->
        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
            <h3 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
                <i class="ri-information-line text-blue-500"></i> Instrucciones
            </h3>
            <div class="prose prose-sm text-slate-600">
                <p>Para importar personal correctamente, sigue estos pasos:</p>
                <ol class="list-decimal pl-4 space-y-2">
                    <li>Descarga la <strong>plantilla CSV</strong> para ver el formato requerido.</li>
                    <li>Usa los <strong>Códigos de Empresa</strong> y <strong>Sucursal</strong> correctos (ver tabla
                        abajo).</li>
                    <li>Las fechas deben estar en formato <code>YYYY-MM-DD</code> (Ej: 2024-01-30).</li>
                    <li>Guarda tu archivo como <strong>CSV (delimitado por comas o punto y coma)</strong>.</li>
                </ol>
            </div>

            <div class="mt-6">
                <a href="index.php?view=personal_importar&action=descargar_plantilla"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-blue-50 text-blue-700 hover:bg-blue-100 rounded-lg font-medium transition-colors border border-blue-200">
                    <i class="ri-download-2-line"></i>
                    Descargar Plantilla CSV
                </a>
            </div>
        </div>

        <!-- Formulario de Subida -->
        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-center">
            <form action="" method="POST" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <label class="block text-sm font-medium text-slate-700 mb-1">Seleccionar Archivo</label>
                <div class="flex items-center justify-center w-full">
                    <label for="archivo_csv"
                        class="flex flex-col items-center justify-center w-full h-32 border-2 border-slate-300 border-dashed rounded-lg cursor-pointer bg-slate-50 hover:bg-slate-100 transition-colors">
                        <div class="flex flex-col items-center justify-center pt-5 pb-6">
                            <i class="ri-upload-cloud-2-line text-3xl text-slate-400 mb-2"></i>
                            <p class="text-sm text-slate-500"><span class="font-semibold">Clic para subir</span></p>
                            <p class="text-xs text-slate-500">Formato .CSV</p>
                        </div>
                        <input id="archivo_csv" name="archivo_csv" type="file" accept=".csv" class="hidden" required />
                    </label>
                </div>
                <div id="file-name" class="text-sm text-center text-slate-600 min-h-[20px]"></div>

                <div class="pt-2">
                    <button type="submit"
                        class="w-full px-4 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg shadow-lg shadow-emerald-600/20 transition-all flex items-center justify-center gap-2">
                        <i class="ri-save-line"></i> Procesar Importación
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de Referencia de Códigos -->
    <div class="mt-8">
        <h3 class="text-lg font-bold text-slate-800 mb-4">Referencia de Códigos (Para llenar el CSV)</h3>
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <table class="w-full text-sm text-left text-slate-600">
                <thead class="text-xs text-slate-700 uppercase bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-3">Empresa</th>
                        <th class="px-6 py-3">Código Empresa</th>
                        <th class="px-6 py-3">Sucursal</th>
                        <th class="px-6 py-3">Código Sucursal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    try {
                        $stmt = $pdo->query("SELECT e.nombre as emp, e.codigo as cod_emp, s.nombre as suc, s.codigo as cod_suc 
                                           FROM sucursales s JOIN empresas e ON s.empresa_id = e.id 
                                           WHERE s.activa = 1 ORDER BY e.nombre");
                        while ($row = $stmt->fetch()) {
                            echo "<tr class='border-b border-slate-100 hover:bg-slate-50'>";
                            echo "<td class='px-6 py-3 font-medium text-slate-800'>{$row['emp']}</td>";
                            echo "<td class='px-6 py-3 font-mono text-blue-600'>{$row['cod_emp']}</td>";
                            echo "<td class='px-6 py-3'>{$row['suc']}</td>";
                            echo "<td class='px-6 py-3 font-mono text-emerald-600'>{$row['cod_suc']}</td>";
                            echo "</tr>";
                        }
                    } catch (PDOException $e) {
                        echo "<tr><td colspan='4' class='px-6 py-3 text-red-500'>Error al cargar referencias</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    document.getElementById('archivo_csv').addEventListener('change', function (e) {
        const fileName = e.target.files[0]?.name;
        document.getElementById('file-name').textContent = fileName ? 'Archivo seleccionado: ' + fileName : '';
    });
</script>