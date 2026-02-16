<?php
/**
 * seccion_estadisticas_globales.php - Dashboard de Métricas y Visualizaciones
 * Centraliza la información clave para toma de decisiones rápida.
 */
?>

<!-- Contenedor Principal con Padding y Fondo Suave -->
<div class="p-8 min-h-screen bg-slate-50/50">

    <!-- 1. ENCABEZADO "Executive Summary" -->
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-3xl font-black text-slate-800 flex items-center gap-3 tracking-tight">
                <div
                    class="w-12 h-12 rounded-2xl bg-gradient-to-br from-indigo-600 to-violet-600 flex items-center justify-center text-white shadow-xl shadow-indigo-500/30">
                    <i class="ri-bar-chart-groupped-line text-2xl"></i>
                </div>
                <span class="bg-clip-text text-transparent bg-gradient-to-r from-slate-700 to-slate-900">
                    Estadísticas Globales
                </span>
            </h2>
            <p class="text-slate-500 mt-2 font-medium ml-16">Análisis estratégico en tiempo real &bull; <span
                    class="text-indigo-600"><?php echo date('F Y'); ?></span></p>
        </div>

        <form method="GET" action="index.php" class="flex flex-col md:flex-row gap-3 items-end">
            <input type="hidden" name="view" value="estadisticas_globales">

            <div class="flex flex-col gap-1">
                <label class="text-xs font-bold text-slate-400 uppercase tracking-wider">Desde</label>
                <input type="date" name="fecha_inicio" value="<?php echo $fecha_inicio ?? date('Y-m-01'); ?>"
                    class="px-3 py-2 bg-white border border-slate-200 rounded-xl text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all shadow-sm">
            </div>

            <div class="flex flex-col gap-1">
                <label class="text-xs font-bold text-slate-400 uppercase tracking-wider">Hasta</label>
                <input type="date" name="fecha_fin" value="<?php echo $fecha_fin ?? date('Y-m-d'); ?>"
                    class="px-3 py-2 bg-white border border-slate-200 rounded-xl text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all shadow-sm">
            </div>

            <div class="flex flex-col gap-1">
                <label class="text-xs font-bold text-slate-400 uppercase tracking-wider">Técnico</label>
                <select name="tecnico_id"
                    class="px-3 py-2 bg-white border border-slate-200 rounded-xl text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all shadow-sm min-w-[150px]">
                    <option value="">Todos</option>
                    <?php if (!empty($lista_tecnicos)): ?>
                        <?php foreach ($lista_tecnicos as $tec): ?>
                            <option value="<?php echo $tec['id']; ?>" <?php echo (isset($filtro_tecnico) && $filtro_tecnico == $tec['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($tec['nombre_completo']); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div class="flex flex-col gap-1">
                <label class="text-xs font-bold text-slate-400 uppercase tracking-wider">RRHH</label>
                <select name="rrhh_id"
                    class="px-3 py-2 bg-white border border-slate-200 rounded-xl text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all shadow-sm min-w-[150px]">
                    <option value="">Todos</option>
                    <?php if (!empty($lista_rrhh)): ?>
                        <?php foreach ($lista_rrhh as $rh): ?>
                            <option value="<?php echo $rh['id']; ?>" <?php echo (isset($filtro_rrhh) && $filtro_rrhh == $rh['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($rh['nombre_completo']); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div class="flex gap-2">
                <button type="submit"
                    class="px-4 py-2 bg-indigo-600 text-white font-semibold rounded-xl shadow-lg shadow-indigo-500/30 hover:bg-indigo-700 hover:scale-105 active:scale-95 transition-all text-sm flex items-center gap-2">
                    <i class="ri-filter-3-line"></i> Filtrar
                </button>

                <button type="button" onclick="window.print()"
                    class="w-10 h-10 bg-white border border-slate-200 text-slate-600 rounded-xl hover:bg-slate-50 flex items-center justify-center transition-all shadow-sm"
                    title="Imprimir Reporte">
                    <i class="ri-printer-line"></i>
                </button>
            </div>
        </form>
    </div>

    <!-- 2. TARJETAS KPI (Big Numbers) -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
        <!-- KPI 1: Salud del Sistema -->
        <div
            class="bg-white rounded-2xl p-6 shadow-lg shadow-slate-200/40 border border-slate-100 group hover:-translate-y-1 transition-transform relative overflow-hidden">
            <div
                class="absolute top-0 right-0 w-24 h-24 bg-gradient-to-br from-indigo-500/10 to-transparent rounded-bl-full -mr-4 -mt-4">
            </div>
            <div class="flex justify-between items-start mb-4 relative z-10">
                <div class="p-3 bg-indigo-50 rounded-xl text-indigo-600">
                    <i class="ri-pulse-line text-2xl"></i>
                </div>
                <span class="px-2 py-1 bg-indigo-100 text-indigo-700 text-xs font-bold rounded-lg">+5% vs mes
                    ant.</span>
            </div>
            <h3 class="text-slate-500 text-sm font-bold uppercase tracking-wider mb-1">Eficacia Global</h3>
            <div class="flex items-baseline gap-2">
                <span class="text-4xl font-black text-slate-800" id="kpi_eficacia">--%</span>
                <span class="text-sm font-medium text-slate-400">Resueltos</span>
            </div>
        </div>

        <!-- KPI 2: Carga Operativa -->
        <div
            class="bg-white rounded-2xl p-6 shadow-lg shadow-slate-200/40 border border-slate-100 group hover:-translate-y-1 transition-transform relative overflow-hidden">
            <div
                class="absolute top-0 right-0 w-24 h-24 bg-gradient-to-br from-amber-500/10 to-transparent rounded-bl-full -mr-4 -mt-4">
            </div>
            <div class="flex justify-between items-start mb-4 relative z-10">
                <div class="p-3 bg-amber-50 rounded-xl text-amber-600">
                    <i class="ri-tickets-line text-2xl"></i>
                </div>
            </div>
            <h3 class="text-slate-500 text-sm font-bold uppercase tracking-wider mb-1">Tickets Abiertos</h3>
            <div class="flex items-baseline gap-2">
                <span class="text-4xl font-black text-slate-800" id="kpi_abiertos">--</span>
                <span class="text-sm font-medium text-slate-400">Pendientes</span>
            </div>
        </div>

        <!-- KPI 3: Activos Globales -->
        <div
            class="bg-white rounded-2xl p-6 shadow-lg shadow-slate-200/40 border border-slate-100 group hover:-translate-y-1 transition-transform relative overflow-hidden">
            <div
                class="absolute top-0 right-0 w-24 h-24 bg-gradient-to-br from-blue-500/10 to-transparent rounded-bl-full -mr-4 -mt-4">
            </div>
            <div class="flex justify-between items-start mb-4 relative z-10">
                <div class="p-3 bg-blue-50 rounded-xl text-blue-600">
                    <i class="ri-macbook-line text-2xl"></i>
                </div>
                <span class="px-2 py-1 bg-blue-100 text-blue-700 text-xs font-bold rounded-lg"
                    id="kpi_inventario_total">Total</span>
            </div>
            <h3 class="text-slate-500 text-sm font-bold uppercase tracking-wider mb-1">Inventario</h3>
            <div class="flex items-baseline gap-2">
                <span class="text-4xl font-black text-slate-800" id="kpi_activos">--</span>
                <span class="text-sm font-medium text-slate-400">Equipos</span>
            </div>
        </div>

        <!-- KPI 4: Fuerza Laboral -->
        <div
            class="bg-white rounded-2xl p-6 shadow-lg shadow-slate-200/40 border border-slate-100 group hover:-translate-y-1 transition-transform relative overflow-hidden">
            <div
                class="absolute top-0 right-0 w-24 h-24 bg-gradient-to-br from-emerald-500/10 to-transparent rounded-bl-full -mr-4 -mt-4">
            </div>
            <div class="flex justify-between items-start mb-4 relative z-10">
                <div class="p-3 bg-emerald-50 rounded-xl text-emerald-600">
                    <i class="ri-team-line text-2xl"></i>
                </div>
            </div>
            <h3 class="text-slate-500 text-sm font-bold uppercase tracking-wider mb-1">Personal Activo</h3>
            <div class="flex items-baseline gap-2">
                <span class="text-4xl font-black text-slate-800" id="kpi_personal">--</span>
                <span class="text-sm font-medium text-slate-400">Colaboradores</span>
            </div>
        </div>
    </div>

    <!-- 3. GRAFICOS PRINCIPALES (Grid Bento) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">

        <!-- Grafico 1: Tendencia Tickets (Linea) - Ocupa 2 columnas -->
        <div class="lg:col-span-2 bg-white rounded-2xl shadow-xl shadow-slate-200/50 border border-slate-100 p-6">
            <h3 class="text-lg font-bold text-slate-800 mb-6 flex items-center gap-2">
                <i class="ri-line-chart-line text-indigo-500"></i> Dinámica de Solicitudes (Últimos 6 Meses)
            </h3>
            <div class="relative h-80 w-full">
                <canvas id="chartTendencia"></canvas>
            </div>
        </div>

        <!-- Grafico 2: Distribución Categorías (Dona) -->
        <div class="bg-white rounded-2xl shadow-xl shadow-slate-200/50 border border-slate-100 p-6">
            <h3 class="text-lg font-bold text-slate-800 mb-6 flex items-center gap-2">
                <i class="ri-pie-chart-2-line text-pink-500"></i> Categorías
            </h3>
            <div class="relative h-64 w-full flex items-center justify-center">
                <canvas id="chartCategorias"></canvas>
            </div>
            <div class="mt-4 text-center text-xs text-slate-400">Distribución de tipos de soporte</div>
        </div>

    </div>

    <!-- 4. SECCION SECUNDARIA -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">

        <!-- Grafico 3: Inventario (Barra Horizontal o Radar) -->
        <div class="bg-white rounded-2xl shadow-xl shadow-slate-200/50 border border-slate-100 p-6">
            <h3 class="text-lg font-bold text-slate-800 mb-6 flex items-center gap-2">
                <i class="ri-cpu-line text-blue-500"></i> Tipos de Hardware
            </h3>
            <div class="relative h-64 w-full">
                <canvas id="chartInventario"></canvas>
            </div>
        </div>

        <!-- Grafico 4: Movimientos RRHH (Barras Apiladas) -->
        <div class="lg:col-span-2 bg-white rounded-2xl shadow-xl shadow-slate-200/50 border border-slate-100 p-6">
            <h3 class="text-lg font-bold text-slate-800 mb-6 flex items-center gap-2">
                <i class="ri-user-follow-line text-emerald-500"></i> Flujo de Personal (Ingresos vs Bajas)
            </h3>
            <div class="relative h-64 w-full">
                <canvas id="chartRRHH"></canvas>
            </div>
        </div>

    </div>

    <!-- 5. TOP TECNICOS -->
    <div class="bg-white rounded-2xl shadow-xl shadow-slate-200/50 border border-slate-100 p-6 mb-8">
        <h3 class="text-lg font-bold text-slate-800 mb-6 flex items-center gap-2">
            <i class="ri-medal-line text-amber-500"></i> Top Rendimiento: Técnicos
        </h3>
        <div class="relative h-64 w-full">
            <canvas id="chartTecnicos"></canvas>
        </div>
    </div>


</div>

<!-- INYECCION DE DATOS DESDE PHP -->
<script>
    // KPIs
    const kpiData = <?php echo json_encode($stats_kpis ?? []); ?>;

    // Gráficos Data
    const chartData = <?php echo json_encode($stats_charts ?? []); ?>;
</script>

<!-- LOGICA CHART.JS -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // --- 0. Actualizar KPIs en DOM ---
        if (kpiData) {
            document.getElementById('kpi_eficacia').innerText = (kpiData.eficacia || 0) + '%';
            document.getElementById('kpi_abiertos').innerText = kpiData.abiertos || 0;
            document.getElementById('kpi_activos').innerText = kpiData.total_activos || 0;
            document.getElementById('kpi_personal').innerText = kpiData.personal_activo || 0;
        }

        // Configuración Global de Chart.js para look premium
        Chart.defaults.font.family = "'Inter', sans-serif";
        Chart.defaults.color = '#64748b'; // slate-500
        Chart.defaults.scale.grid.color = '#f1f5f9'; // slate-100

        // --- 1. Gráfico Tendencia (Line) ---
        const ctxTendencia = document.getElementById('chartTendencia').getContext('2d');
        // Gradiente Lindo
        let gradient = ctxTendencia.createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, 'rgba(99, 102, 241, 0.5)');   // Indigo 500
        gradient.addColorStop(1, 'rgba(99, 102, 241, 0.0)');

        new Chart(ctxTendencia, {
            type: 'line',
            data: {
                labels: chartData.tendencia?.labels || ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun'],
                datasets: [{
                    label: 'Tickets Creados',
                    data: chartData.tendencia?.data || [0, 0, 0, 0, 0, 0],
                    borderColor: '#6366f1', // Indigo 500
                    backgroundColor: gradient,
                    borderWidth: 3,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#6366f1',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    fill: true,
                    tension: 0.4 // Curva suave
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: 'rgba(15, 23, 42, 0.9)',
                        titleColor: '#fff',
                        bodyColor: '#cbd5e1',
                        borderColor: 'rgba(255,255,255,0.1)',
                        borderWidth: 1,
                        padding: 10,
                        cornerRadius: 8
                    }
                },
                scales: {
                    y: { beginAtZero: true, grid: { borderDash: [2, 4] } },
                    x: { grid: { display: false } }
                }
            }
        });

        // --- 2. Gráfico Categorías (Doughnut) ---
        const ctxCat = document.getElementById('chartCategorias').getContext('2d');
        new Chart(ctxCat, {
            type: 'doughnut',
            data: {
                labels: chartData.categorias?.labels || [],
                datasets: [{
                    data: chartData.categorias?.data || [],
                    backgroundColor: [
                        '#6366f1', // Indigo
                        '#ec4899', // Pink
                        '#10b981', // Emerald
                        '#f59e0b', // Amber
                        '#3b82f6', // Blue
                        '#8b5cf6'  // Violet
                    ],
                    borderWidth: 0,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'right', labels: { usePointStyle: true, boxWidth: 8 } }
                },
                cutout: '75%'
            }
        });

        // --- 3. Inventario (Polar Area o Bar) ---
        const ctxInv = document.getElementById('chartInventario').getContext('2d');
        new Chart(ctxInv, {
            type: 'polarArea',
            data: {
                labels: chartData.inventario?.labels || [],
                datasets: [{
                    data: chartData.inventario?.data || [],
                    backgroundColor: [
                        'rgba(59, 130, 246, 0.6)',
                        'rgba(168, 85, 247, 0.6)',
                        'rgba(16, 185, 129, 0.6)',
                        'rgba(244, 63, 94, 0.6)',
                        'rgba(245, 158, 11, 0.6)'
                    ],
                    borderWidth: 1,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 6 } }
                },
                scales: {
                    r: { ticks: { display: false }, grid: { circular: true } } // Mantiene la forma circular
                }
            }
        });

        // --- 4. RRHH (Barra Apilada o Doble) ---
        const ctxRRHH = document.getElementById('chartRRHH').getContext('2d');
        new Chart(ctxRRHH, {
            type: 'bar',
            data: {
                labels: chartData.rrhh?.labels || [],
                datasets: [
                    {
                        label: 'Ingresos',
                        data: chartData.rrhh?.ingresos || [],
                        backgroundColor: '#10b981', // Emerald 500
                        borderRadius: 4
                    },
                    {
                        label: 'Bajas',
                        data: chartData.rrhh?.salidas || [],
                        backgroundColor: '#f43f5e', // Rose 500
                        borderRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top', align: 'end' }
                },
                scales: {
                    x: { grid: { display: false } },
                    y: { beginAtZero: true }
                }
            }
        });

        // --- 5. Técnicos (Barra Horizontal) ---
        const ctxTec = document.getElementById('chartTecnicos').getContext('2d');
        new Chart(ctxTec, {
            type: 'bar', // Puede ser 'bar' con indexAxis: 'y' para horizontal
            data: {
                labels: chartData.tecnicos?.labels || [],
                datasets: [{
                    label: 'Tickets Resueltos',
                    data: chartData.tecnicos?.data || [],
                    backgroundColor: 'rgba(245, 158, 11, 0.8)', // Amber
                    borderColor: 'rgba(245, 158, 11, 1)',
                    borderWidth: 1,
                    borderRadius: 6,
                    barThickness: 20
                }]
            },
            options: {
                indexAxis: 'y', // Hace las barras horizontales
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: { beginAtZero: true, grid: { borderDash: [2, 2] } },
                    y: { grid: { display: false } }
                }
            }
        });

    });
</script>