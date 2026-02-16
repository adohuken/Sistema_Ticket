<?php
/**
 * client_history.php - Historial de Créditos del Cliente (Sin Gráficas)
 */

// Simulación de datos de créditos (Préstamos)
$creditos = [
    [
        'id' => 2,
        'fecha_inicio' => '2025-11-30',
        'fecha_vencimiento' => '2025-12-30',
        'monto_prestado' => 200.00,
        'tasa_interes' => 20.00,
        'monto_total' => 240.00,
        'estado' => 'Activo',
        'pagado' => 80.00
    ],
    [
        'id' => 1,
        'fecha_inicio' => '2025-10-15',
        'fecha_vencimiento' => '2025-11-15',
        'monto_prestado' => 500.00,
        'tasa_interes' => 15.00,
        'monto_total' => 575.00,
        'estado' => 'Pagado',
        'pagado' => 575.00
    ],
    [
        'id' => 3,
        'fecha_inicio' => '2025-12-01',
        'fecha_vencimiento' => '2026-01-01',
        'monto_prestado' => 1000.00,
        'tasa_interes' => 10.00,
        'monto_total' => 1100.00,
        'estado' => 'Pendiente',
        'pagado' => 0.00
    ]
];
?>

<!-- SOLO TABLA - SIN GRÁFICAS -->
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center gap-3 mb-8">
        <div class="p-2 bg-blue-600 rounded-lg shadow-lg shadow-blue-600/20">
            <i class="ri-money-dollar-circle-line text-white text-2xl"></i>
        </div>
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Historial de Créditos</h2>
            <p class="text-slate-500 text-sm">Listado completo de préstamos del cliente</p>
        </div>
    </div>

    <!-- Tabla de Créditos -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr
                        class="bg-slate-50 border-b border-slate-200 text-xs uppercase text-slate-500 font-semibold tracking-wider">
                        <th class="px-6 py-4">ID Crédito</th>
                        <th class="px-6 py-4">Fecha Inicio</th>
                        <th class="px-6 py-4">Fecha Vencimiento</th>
                        <th class="px-6 py-4 text-right">Monto Prestado</th>
                        <th class="px-6 py-4 text-right">Interés (%)</th>
                        <th class="px-6 py-4 text-right">Total a Pagar</th>
                        <th class="px-6 py-4 text-right">Pagado</th>
                        <th class="px-6 py-4 text-center">Estado</th>
                        <th class="px-6 py-4 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($creditos as $credito): ?>
                        <?php
                        $estado_classes = match ($credito['estado']) {
                            'Activo' => 'bg-blue-100 text-blue-700 border-blue-200',
                            'Pagado' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                            'Pendiente' => 'bg-amber-100 text-amber-700 border-amber-200',
                            'Vencido' => 'bg-rose-100 text-rose-700 border-rose-200',
                            default => 'bg-slate-100 text-slate-700 border-slate-200'
                        };
                        ?>
                        <tr class="hover:bg-slate-50 transition-colors group">
                            <td class="px-6 py-4 font-medium text-slate-900">
                                #<?php echo str_pad($credito['id'], 4, '0', STR_PAD_LEFT); ?>
                            </td>
                            <td class="px-6 py-4 text-slate-600">
                                <div class="flex items-center gap-2">
                                    <i class="ri-calendar-line text-slate-400"></i>
                                    <?php echo $credito['fecha_inicio']; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-slate-600">
                                <div class="flex items-center gap-2">
                                    <i class="ri-calendar-event-line text-slate-400"></i>
                                    <?php echo $credito['fecha_vencimiento']; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right font-medium text-slate-700">
                                $<?php echo number_format($credito['monto_prestado'], 2); ?>
                            </td>
                            <td class="px-6 py-4 text-right text-slate-600">
                                <?php echo number_format($credito['tasa_interes'], 2); ?>%
                            </td>
                            <td class="px-6 py-4 text-right font-bold text-slate-800">
                                $<?php echo number_format($credito['monto_total'], 2); ?>
                            </td>
                            <td class="px-6 py-4 text-right font-medium text-emerald-600">
                                $<?php echo number_format($credito['pagado'], 2); ?>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span
                                    class="px-3 py-1 rounded-full text-xs font-bold border <?php echo $estado_classes; ?>">
                                    <?php echo strtoupper($credito['estado']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <button
                                    class="text-slate-400 hover:text-blue-600 transition-colors p-2 rounded-lg hover:bg-blue-50"
                                    title="Ver Detalles">
                                    <i class="ri-eye-line text-lg"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination (Simulated) -->
        <div class="px-6 py-4 border-t border-slate-200 bg-slate-50 flex items-center justify-between">
            <p class="text-sm text-slate-500">Mostrando <span class="font-medium">1</span> a <span
                    class="font-medium"><?php echo count($creditos); ?></span> de <span
                    class="font-medium"><?php echo count($creditos); ?></span> créditos</p>
            <div class="flex gap-2">
                <button
                    class="px-3 py-1 text-sm border border-slate-300 rounded-lg text-slate-500 hover:bg-white disabled:opacity-50"
                    disabled>Anterior</button>
                <button
                    class="px-3 py-1 text-sm border border-slate-300 rounded-lg text-slate-500 hover:bg-white disabled:opacity-50"
                    disabled>Siguiente</button>
            </div>
        </div>
    </div>
</div>