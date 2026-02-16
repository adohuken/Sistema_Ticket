<?php
/**
 * seccion_importar_inventario.php - Módulo de Importación Masiva CSV
 */
?>

<div class="p-6 flex-1">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="mb-8 ">
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

        <!-- Instrucciones y Template -->
        <div class="bg-blue-50 border border-blue-100 rounded-2xl p-6 mb-8 flex flex-col md:flex-row gap-6 items-start">
            <div class="flex-1">
                <h3 class="font-bold text-blue-800 text-lg mb-2 flex items-center gap-2">
                    <i class="ri-information-line"></i> Instrucciones
                </h3>
                <ul class="list-disc list-inside text-sm text-blue-700 space-y-1">
                    <li>El archivo debe tener formato <strong>.CSV</strong> (delimitado por comas o punto y coma).</li>
                    <li>La primera fila debe contener los encabezados exactos (o seguir el orden).</li>
                    <li>Las columnas requeridas son: <code
                            class="bg-blue-100 px-1 rounded font-bold">Tipo, Marca, Modelo, Serial, SKU, Estado, Condición</code>.
                    </li>
                    <li>El <strong>Serial</strong> y <strong>SKU</strong> deben ser únicos en el sistema.</li>
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

            <form action="index.php" method="POST" enctype="multipart/form-data" class="p-8">
                <input type="hidden" name="accion" value="procesar_importacion_inventario">
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