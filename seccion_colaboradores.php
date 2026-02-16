<?php
/**
 * seccion_colaboradores.php - Directorio de Colaboradores (Vista Visual)
 */

// Obtener lista completa de personal activo
// Reutilizamos la vista "vista_personal_completo" que ya tiene joins de empresa/sucursal
try {
    $stmt = $pdo->prepare("
        SELECT * 
        FROM vista_personal_completo 
        WHERE estado = 'Activo' 
        ORDER BY apellidos, nombres
    ");
    $stmt->execute();
    $colaboradores = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $colaboradores = [];
    $error_msg = "Error cargando directorio: " . $e->getMessage();
}

// Obtener sucursales para filtro rápido
$sucursales = [];
foreach ($colaboradores as $c) {
    if (!empty($c['sucursal_nombre'])) {
        $sucursales[$c['sucursal_nombre']] = true;
    }
}
ksort($sucursales);
?>

<div class="h-[calc(100vh-5rem)] flex flex-col bg-slate-50 overflow-hidden">

    <!-- 1. HEADER & BUSCADOR -->
    <div
        class="bg-white border-b border-slate-200 px-8 py-5 flex flex-col md:flex-row md:items-center justify-between gap-4 shrink-0 shadow-sm z-10 w-full">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 flex items-center gap-3">
                <span
                    class="w-10 h-10 rounded-xl bg-indigo-100 flex items-center justify-center text-indigo-600 text-xl">
                    <i class="ri-team-fill"></i>
                </span>
                Directorio de Colaboradores
            </h1>
            <p class="text-slate-500 mt-1 ml-14">Encuentra y conecta con el equipo rápidamente.</p>
        </div>

        <!-- Buscador Grande -->
        <div class="flex-1 max-w-xl relative group">
            <i
                class="ri-search-2-line absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-indigo-500 transition-colors text-lg"></i>
            <input type="text" id="buscador-colaboradores" placeholder="Buscar por nombre, cargo, sucursal..."
                class="w-full pl-12 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-700 outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all shadow-sm">
        </div>
    </div>

    <!-- 2. GRID CONTENIDO -->
    <div class="flex-1 overflow-y-auto p-8 custom-scrollbar">

        <?php if (isset($error_msg)): ?>
            <div class="bg-red-50 text-red-700 p-4 rounded-xl border border-red-200 mb-6 flex items-center gap-3">
                <i class="ri-error-warning-line text-xl"></i>
                <?= $error_msg ?>
            </div>
        <?php endif; ?>

        <!-- GRID DE TARJETAS -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6" id="grid-colaboradores">

            <?php foreach ($colaboradores as $col):
                $nombre_completo = $col['nombres'] . ' ' . $col['apellidos'];
                $iniciales = strtoupper(substr($col['nombres'], 0, 1) . substr($col['apellidos'], 0, 1));
                // Generar un color aleatorio consistente para el avatar basado en el nombre
                $colores = ['blue', 'indigo', 'violet', 'purple', 'fuchsia', 'pink', 'rose', 'orange', 'emerald', 'teal', 'cyan'];
                $color_idx = crc32($col['id']) % count($colores);
                $color_base = $colores[$color_idx];
                ?>

                <!-- TARJETA COLABORADOR -->
                <div class="colaborador-card group bg-white rounded-2xl border border-slate-100 p-5 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden"
                    data-texto="<?= strtolower($nombre_completo . ' ' . $col['cargo'] . ' ' . $col['sucursal_nombre']) ?>">

                    <!-- Decoración Fondo -->
                    <div
                        class="absolute top-0 right-0 w-24 h-24 bg-<?= $color_base ?>-50 rounded-bl-full -mr-4 -mt-4 opacity-50 transition-opacity group-hover:opacity-100">
                    </div>

                    <div class="flex items-start gap-4 relative z-10">
                        <!-- Avatar -->
                        <div
                            class="w-16 h-16 rounded-2xl bg-<?= $color_base ?>-100 text-<?= $color_base ?>-600 flex items-center justify-center text-xl font-bold shadow-sm shrink-0">
                            <?= $iniciales ?>
                        </div>

                        <!-- Info Principal -->
                        <div class="flex-1 min-w-0">
                            <h3 class="font-bold text-slate-800 text-lg leading-tight truncate"
                                title="<?= $nombre_completo ?>">
                                <?= $nombre_completo ?>
                            </h3>
                            <p class="text-sm text-<?= $color_base ?>-600 font-medium mt-1 truncate"
                                title="<?= $col['cargo'] ?>">
                                <?= $col['cargo'] ?>
                            </p>
                            <p class="text-xs text-slate-400 mt-0.5 truncate flex items-center gap-1">
                                <i class="ri-building-line"></i> <?= $col['empresa_nombre'] ?>
                            </p>
                        </div>
                    </div>

                    <div class="mt-5 pt-4 border-t border-slate-100 flex flex-col gap-2">
                        <div class="flex items-center gap-2 text-sm text-slate-600">
                            <i class="ri-map-pin-line text-slate-400"></i>
                            <span class="truncate"><?= $col['sucursal_nombre'] ?></span>
                        </div>

                        <?php if (!empty($col['telefono'])): ?>
                            <div class="flex items-center gap-2 text-sm text-slate-600">
                                <i class="ri-phone-line text-slate-400"></i>
                                <span><?= $col['telefono'] ?></span>
                                <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $col['telefono']) ?>" target="_blank"
                                    class="ml-auto text-xs font-semibold text-green-600 bg-green-50 px-2 py-0.5 rounded hover:bg-green-100 transition-colors">
                                    WhatsApp
                                </a>
                            </div>
                        <?php endif; ?>

                        <div class="flex items-center gap-2 text-sm text-slate-600">
                            <i class="ri-mail-line text-slate-400"></i>
                            <span
                                class="truncate text-xs"><?= strtolower(str_replace(' ', '.', $col['nombres'])) ?>@empresa.com</span>
                            <!-- Placeholder si no hay email real en view -->
                        </div>
                    </div>

                    <!-- Botón Acción hover -->
                    <div
                        class="absolute bottom-4 right-4 opacity-0 group-hover:opacity-100 transition-opacity translate-y-2 group-hover:translate-y-0 duration-300">
                        <a href="index.php?view=personal_detalle&id=<?= $col['id'] ?>"
                            class="w-10 h-10 rounded-full bg-slate-800 text-white flex items-center justify-center shadow-lg hover:bg-black transition-colors"
                            title="Ver Perfil Completo">
                            <i class="ri-arrow-right-line"></i>
                        </a>
                    </div>
                </div>

            <?php endforeach; ?>

            <!-- ESTADO VACIO BUSQUEDA -->
            <div id="no-results"
                class="hidden col-span-full py-12 flex flex-col items-center justify-center text-center">
                <div class="w-20 h-20 bg-slate-100 rounded-full flex items-center justify-center mb-4">
                    <i class="ri-search-eye-line text-3xl text-slate-400"></i>
                </div>
                <h3 class="text-lg font-semibold text-slate-700">No se encontraron colaboradores</h3>
                <p class="text-slate-500">Intenta con otro término de búsqueda.</p>
            </div>

        </div>
    </div>
</div>

<script>
    document.getElementById('buscador-colaboradores').addEventListener('input', function (e) {
        const term = e.target.value.toLowerCase().trim();
        const cards = document.querySelectorAll('.colaborador-card');
        let visibleCount = 0;

        cards.forEach(card => {
            if (card.dataset.texto.includes(term)) {
                card.classList.remove('hidden');
                visibleCount++;
            } else {
                card.classList.add('hidden');
            }
        });

        document.getElementById('no-results').classList.toggle('hidden', visibleCount > 0);
    });
</script>