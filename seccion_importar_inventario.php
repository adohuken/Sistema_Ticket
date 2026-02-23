<?php
/**
 * seccion_importar_inventario.php - Módulo de Importación Masiva CSV de Inventario
 */

$import_mensaje = '';
$import_tipo = ''; // 'success' | 'warning' | 'error'
$import_errores = [];
$import_count = 0;

// ─── Procesar importación si viene POST ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo_csv'])) {

    // CSRF
    if (empty($_POST['csrf_token']) || !validar_csrf_token($_POST['csrf_token'])) {
        $import_tipo = 'error';
        $import_mensaje = 'Error de seguridad: token CSRF inválido. Recargue la página.';
    } elseif ($_FILES['archivo_csv']['error'] !== UPLOAD_ERR_OK) {
        $import_tipo = 'error';
        $import_mensaje = 'Error al subir el archivo. Verifique el tamaño (máx. 5 MB).';
    } else {
        $ext = strtolower(pathinfo($_FILES['archivo_csv']['name'], PATHINFO_EXTENSION));
        if ($ext !== 'csv') {
            $import_tipo = 'error';
            $import_mensaje = 'Solo se aceptan archivos .CSV.';
        } else {
            $tmp = $_FILES['archivo_csv']['tmp_name'];

            try {
                $pdo->beginTransaction();

                if (($handle = fopen($tmp, 'r')) === false) {
                    throw new Exception('No se pudo abrir el archivo.');
                }

                // Detectar delimitador (coma o punto y coma)
                $firstLine = fgets($handle);
                rewind($handle);
                $delim = (strpos($firstLine, ';') !== false) ? ';' : ',';

                // Leer cabecera
                $header = fgetcsv($handle, 0, $delim);
                if ($header === false) {
                    throw new Exception('El archivo CSV está vacío o mal formado.');
                }
                $header = array_map(fn($h) => strtolower(trim($h)), $header);

                // Columnas esperadas
                $required = ['tipo', 'marca', 'modelo', 'serial'];
                $missing = array_diff($required, $header);
                if (!empty($missing)) {
                    throw new Exception('Faltan columnas requeridas: ' . implode(', ', $missing));
                }
                $map = array_flip($header);

                // Cargar mapa de sucursales (nombre → id)
                $suc_rows = $pdo->query("SELECT id, nombre FROM sucursales")->fetchAll(PDO::FETCH_ASSOC);
                $suc_map = [];
                foreach ($suc_rows as $s) {
                    $suc_map[strtolower(trim($s['nombre']))] = $s['id'];
                }

                $stmtCheck = $pdo->prepare("SELECT id FROM inventario WHERE serial = ? OR (sku IS NOT NULL AND sku != '' AND sku = ?)");
                $stmtInsert = $pdo->prepare("INSERT INTO inventario
                    (tipo, marca, modelo, serial, sku, estado, condicion, comentarios, sucursal_id)
                    VALUES (?, ?, ?, ?, ?, ?, 'Disponible', ?, ?)");

                $row_num = 0;
                while (($data = fgetcsv($handle, 0, $delim)) !== false) {
                    $row_num++;

                    // Ignorar filas completamente vacías
                    if (empty(array_filter($data, fn($v) => trim($v) !== '')))
                        continue;

                    $tipo = trim($data[$map['tipo']] ?? '');
                    $marca = trim($data[$map['marca']] ?? '');
                    $modelo = trim($data[$map['modelo']] ?? '');
                    $serial = strtoupper(trim($data[$map['serial']] ?? ''));
                    $sku = trim($data[$map['sku']] ?? '');
                    $estado = trim($data[$map['estado']] ?? 'Buen Estado');
                    $comentarios = trim($data[$map['comentarios']] ?? '');
                    $suc_nombre = trim($data[$map['sucursal']] ?? '');

                    // Validaciones básicas
                    if (empty($tipo) || empty($marca) || empty($modelo) || empty($serial)) {
                        $import_errores[] = "Fila $row_num: Tipo, Marca, Modelo y Serial son obligatorios.";
                        continue;
                    }

                    // Resolver sucursal
                    $sucursal_id = null;
                    if (!empty($suc_nombre)) {
                        $sucursal_id = $suc_map[strtolower($suc_nombre)] ?? null;
                        if ($sucursal_id === null) {
                            $import_errores[] = "Fila $row_num: Sucursal '$suc_nombre' no encontrada (se dejará sin sucursal).";
                        }
                    }

                    // Duplicados
                    $stmtCheck->execute([$serial, $sku]);
                    if ($stmtCheck->fetch()) {
                        $import_errores[] = "Fila $row_num: Serial '$serial'" . (!empty($sku) ? " o SKU '$sku'" : '') . " ya existe — omitida.";
                        continue;
                    }

                    $stmtInsert->execute([$tipo, $marca, $modelo, $serial, $sku ?: null, $estado, $comentarios ?: null, $sucursal_id]);
                    $import_count++;
                }
                fclose($handle);

                $pdo->commit();

                if ($import_count > 0 && empty($import_errores)) {
                    $import_tipo = 'success';
                    $import_mensaje = "Importación completada. $import_count equipo(s) agregado(s) al inventario.";
                } elseif ($import_count > 0) {
                    $import_tipo = 'warning';
                    $import_mensaje = "$import_count equipo(s) importado(s) con algunas advertencias.";
                } else {
                    $pdo->rollBack();
                    $import_tipo = 'error';
                    $import_mensaje = 'No se importó ningún equipo. Revise los errores.';
                }

                if ($import_count > 0) {
                    registrar_actividad("Importar Inventario", "Se importaron $import_count activos desde CSV", $pdo);
                }

            } catch (Exception $e) {
                if ($pdo->inTransaction())
                    $pdo->rollBack();
                $import_tipo = 'error';
                $import_mensaje = 'Error: ' . $e->getMessage();
            }
        }
    }
}
?>

<div class="p-6 flex-1">
    <div class="max-w-4xl mx-auto">

        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center gap-2 text-sm text-slate-500 mb-2">
                <a href="index.php?view=inventario" class="hover:text-blue-600 transition-colors">Inventario</a>
                <i class="ri-arrow-right-s-line"></i>
                <span>Importación Masiva</span>
            </div>
            <h1 class="text-3xl font-bold text-slate-800 flex items-center gap-3">
                <span class="bg-emerald-600 text-white p-2 rounded-xl shadow-lg shadow-emerald-500/30">
                    <i class="ri-file-excel-2-line"></i>
                </span>
                Importar Inventario desde CSV
            </h1>
            <p class="text-slate-500 mt-2">Carga masiva de activos mediante archivo delimitado por comas (.csv)</p>
        </div>

        <!-- Resultado de la importación -->
        <?php if ($import_tipo === 'success'): ?>
            <div class="mb-6 flex items-start gap-4 p-4 bg-emerald-50 border border-emerald-200 rounded-2xl">
                <div
                    class="w-10 h-10 bg-emerald-100 rounded-full flex items-center justify-center text-emerald-600 shrink-0">
                    <i class="ri-checkbox-circle-fill text-xl"></i>
                </div>
                <div>
                    <h4 class="font-bold text-emerald-800 mb-1">¡Importación Exitosa!</h4>
                    <p class="text-emerald-700 text-sm"><?= htmlspecialchars($import_mensaje) ?></p>
                    <a href="index.php?view=inventario"
                        class="inline-flex items-center gap-1 mt-2 text-sm font-semibold text-emerald-700 hover:underline">
                        <i class="ri-arrow-right-line"></i> Ir al Inventario
                    </a>
                </div>
            </div>
        <?php elseif ($import_tipo === 'warning'): ?>
            <div class="mb-6 flex items-start gap-4 p-4 bg-amber-50 border border-amber-200 rounded-2xl">
                <div class="w-10 h-10 bg-amber-100 rounded-full flex items-center justify-center text-amber-600 shrink-0">
                    <i class="ri-error-warning-fill text-xl"></i>
                </div>
                <div class="flex-1">
                    <h4 class="font-bold text-amber-800 mb-1">Importación con Advertencias</h4>
                    <p class="text-amber-700 text-sm"><?= htmlspecialchars($import_mensaje) ?></p>
                    <?php if (!empty($import_errores)): ?>
                        <ul class="mt-2 space-y-1 max-h-40 overflow-y-auto">
                            <?php foreach ($import_errores as $err): ?>
                                <li class="text-xs text-amber-700 flex items-start gap-1"><i
                                        class="ri-alert-line shrink-0 mt-0.5"></i><?= htmlspecialchars($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        <?php elseif ($import_tipo === 'error'): ?>
            <div class="mb-6 flex items-start gap-4 p-4 bg-red-50 border border-red-200 rounded-2xl">
                <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center text-red-600 shrink-0">
                    <i class="ri-close-circle-fill text-xl"></i>
                </div>
                <div class="flex-1">
                    <h4 class="font-bold text-red-800 mb-1">Error en la Importación</h4>
                    <p class="text-red-700 text-sm"><?= htmlspecialchars($import_mensaje) ?></p>
                    <?php if (!empty($import_errores)): ?>
                        <ul class="mt-2 space-y-1 max-h-40 overflow-y-auto">
                            <?php foreach ($import_errores as $err): ?>
                                <li class="text-xs text-red-700 flex items-start gap-1"><i
                                        class="ri-close-line shrink-0 mt-0.5"></i><?= htmlspecialchars($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Instrucciones y Template -->
        <div class="bg-blue-50 border border-blue-100 rounded-2xl p-6 mb-8 flex flex-col md:flex-row gap-6 items-start">
            <div class="flex-1">
                <h3 class="font-bold text-blue-800 text-lg mb-2 flex items-center gap-2">
                    <i class="ri-information-line"></i> Instrucciones
                </h3>
                <ul class="list-disc list-inside text-sm text-blue-700 space-y-1">
                    <li>El archivo debe tener formato <strong>.CSV</strong> (delimitado por comas o punto y coma).</li>
                    <li>La primera fila debe contener los encabezados exactos.</li>
                    <li>Las columnas requeridas son: <code
                            class="bg-blue-100 px-1 rounded font-bold">Tipo, Marca, Modelo, Serial</code>.</li>
                    <li>Opcionales: <code
                            class="bg-blue-100 px-1 rounded">SKU, Estado, Condición, Comentarios, Sucursal</code>.</li>
                    <li>El <strong>Serial</strong> debe ser único en el sistema.</li>
                    <li>El campo <strong>Sucursal</strong> debe coincidir exactamente con el nombre registrado.</li>
                </ul>
            </div>
            <div class="flex-none">
                <a href="template_inventario.csv" download
                    class="bg-white text-blue-600 border border-blue-200 hover:bg-blue-50 px-4 py-2 rounded-lg font-bold text-sm flex items-center gap-2 shadow-sm transition-all">
                    <i class="ri-download-line text-lg"></i>
                    Descargar Plantilla
                </a>
            </div>
        </div>

        <!-- Formulario de Carga -->
        <div class="bg-white rounded-2xl shadow-xl shadow-slate-200/50 border border-slate-100 overflow-hidden">
            <div class="px-8 py-6 bg-slate-50 border-b border-slate-100">
                <h2 class="text-lg font-bold text-slate-700">Subir Archivo</h2>
            </div>

            <form action="index.php?view=importar_inventario" method="POST" enctype="multipart/form-data" class="p-8">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

                <div class="flex flex-col items-center justify-center w-full mb-6">
                    <label for="dropzone-file"
                        class="flex flex-col items-center justify-center w-full h-64 border-2 border-slate-300 border-dashed rounded-2xl cursor-pointer bg-slate-50 hover:bg-slate-100 transition-colors">
                        <div class="flex flex-col items-center justify-center pt-5 pb-6">
                            <i class="ri-upload-cloud-2-line text-5xl text-slate-400 mb-4"></i>
                            <p class="mb-2 text-sm text-slate-500"><span class="font-bold text-slate-700">Haz clic para
                                    subir</span> o arrastra el archivo aquí</p>
                            <p class="text-xs text-slate-400">Solo archivos .CSV (Max 5MB)</p>
                        </div>
                        <input id="dropzone-file" type="file" name="archivo_csv" accept=".csv" class="hidden" required
                            onchange="mostrarNombreArchivo(this)" />
                    </label>
                    <div id="nombre-archivo"
                        class="mt-4 text-sm font-bold text-emerald-600 hidden flex items-center gap-2">
                        <i class="ri-file-text-line"></i> <span></span>
                    </div>
                </div>

                <div class="flex justify-end pt-4 border-t border-slate-100">
                    <a href="index.php?view=inventario"
                        class="mr-4 px-6 py-3 rounded-xl text-slate-600 hover:bg-slate-100 font-bold transition-colors">Cancelar</a>
                    <button type="submit"
                        class="bg-emerald-600 hover:bg-emerald-700 text-white px-8 py-3 rounded-xl shadow-lg shadow-emerald-600/20 font-bold flex items-center gap-2 transition-all">
                        <i class="ri-upload-2-line"></i> Procesar Importación
                    </button>
                </div>
            </form>
        </div>

        <!-- Referencia de Sucursales -->
        <div class="mt-8 bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="px-6 py-4 bg-slate-50 border-b border-slate-100">
                <h3 class="font-bold text-slate-700 text-sm uppercase tracking-wide">Sucursales Disponibles</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead
                        class="text-xs font-semibold text-slate-400 uppercase tracking-wider border-b border-slate-100">
                        <tr>
                            <th class="px-6 py-3">Nombre de Sucursal (usar en CSV)</th>
                            <th class="px-6 py-3">Empresa</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <?php
                        try {
                            $stmt_suc_ref = $pdo->query("SELECT s.nombre as suc, e.nombre as emp FROM sucursales s LEFT JOIN empresas e ON s.empresa_id = e.id WHERE s.activa = 1 ORDER BY e.nombre, s.nombre");
                            while ($ref = $stmt_suc_ref->fetch(PDO::FETCH_ASSOC)):
                                ?>
                                <tr class="hover:bg-slate-50">
                                    <td class="px-6 py-3 font-mono text-slate-800 font-semibold">
                                        <?= htmlspecialchars($ref['suc']) ?></td>
                                    <td class="px-6 py-3 text-slate-500"><?= htmlspecialchars($ref['emp'] ?? '—') ?></td>
                                </tr>
                            <?php endwhile;
                        } catch (Exception $e) {
                            echo '<tr><td colspan="2" class="px-6 py-3 text-red-500 text-sm">Error al cargar sucursales.</td></tr>';
                        } ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<script>
    function mostrarNombreArchivo(input) {
        const divNombre = document.getElementById('nombre-archivo');
        const spanNombre = divNombre.querySelector('span');
        if (input.files && input.files[0]) {
            spanNombre.textContent = input.files[0].name;
            divNombre.classList.remove('hidden');
        } else {
            divNombre.classList.add('hidden');
        }
    }
</script>