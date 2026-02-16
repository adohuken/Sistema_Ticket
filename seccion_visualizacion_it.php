<?php
/**
 * seccion_visualizacion_it.php - Dashboard de Visualización IT
 * Muestra tarjetas con información completa de cuentas 365
 */

// Obtener todas las cuentas con información completa
$sql = "
    SELECT r.*, 
           CONCAT(u.nombres, ' ', u.apellidos) as usuario_nombre,
           u.sucursal_nombre as departamento
    FROM registros_365 r
    LEFT JOIN vista_personal_completo u ON r.usuario_id = u.id
    ORDER BY r.email ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$cuentas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Estadísticas
$stats = [
    'total' => count($cuentas),
    'activas' => 0,
    'con_credenciales' => 0,
    'con_telefono' => 0
];

foreach ($cuentas as $c) {
    if ($c['estado'] === 'Activo')
        $stats['activas']++;
    if ($c['password_ag'] || $c['pin_windows'])
        $stats['con_credenciales']++;
    if ($c['telefono_principal'])
        $stats['con_telefono']++;
}
?>

<div class="p-6 flex-1">
    <div class="max-w-7xl mx-auto space-y-6">
        <!-- Header -->
        <div>
            <h1 class="text-3xl font-bold text-slate-800 flex items-center gap-3">
                <span class="bg-gradient-to-br from-cyan-600 to-blue-600 text-white p-3 rounded-xl shadow-lg">
                    <i class="ri-information-line"></i>
                </span>
                Info IT - Visualización Completa
            </h1>
            <p class="text-slate-500 mt-2">Vista detallada de todas las cuentas 365 con credenciales y contactos</p>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-100">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                        <i class="ri-mail-line text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-2xl font-bold text-slate-800"><?= $stats['total'] ?></h3>
                        <p class="text-xs text-slate-500">Total Cuentas</p>
                    </div>
                </div>
            </div>
            <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-100">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-green-50 text-green-600 flex items-center justify-center">
                        <i class="ri-checkbox-circle-line text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-2xl font-bold text-slate-800"><?= $stats['activas'] ?></h3>
                        <p class="text-xs text-slate-500">Activas</p>
                    </div>
                </div>
            </div>
            <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-100">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center">
                        <i class="ri-key-2-line text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-2xl font-bold text-slate-800"><?= $stats['con_credenciales'] ?></h3>
                        <p class="text-xs text-slate-500">Con Credenciales</p>
                    </div>
                </div>
            </div>
            <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-100">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                        <i class="ri-phone-line text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-2xl font-bold text-slate-800"><?= $stats['con_telefono'] ?></h3>
                        <p class="text-xs text-slate-500">Con Teléfono</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Vista de Tarjetas -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php if (empty($cuentas)): ?>
                <div class="col-span-full bg-white rounded-2xl shadow-sm border border-slate-100 p-12 text-center">
                    <i class="ri-inbox-line text-6xl text-slate-300 mb-4 block"></i>
                    <p class="text-slate-400 text-lg">No hay cuentas registradas</p>
                </div>
            <?php else: ?>
                <?php foreach ($cuentas as $c): ?>
                    <div
                        class="bg-white rounded-2xl shadow-lg border border-slate-100 overflow-hidden hover:shadow-xl transition-all">
                        <!-- Header -->
                        <div class="bg-gradient-to-br from-blue-600 to-cyan-600 p-4 text-white">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-1">
                                        <i class="ri-microsoft-fill text-2xl"></i>
                                        <span class="text-xs font-bold uppercase tracking-wider opacity-90">Microsoft 365</span>
                                    </div>
                                    <h3 class="font-bold text-lg truncate" title="<?= htmlspecialchars($c['email']) ?>">
                                        <?= htmlspecialchars($c['email']) ?>
                                    </h3>
                                </div>
                                <?php
                                $estado_badges = [
                                    'Activo' => 'bg-green-500',
                                    'Inactivo' => 'bg-slate-400',
                                    'Suspendido' => 'bg-red-500'
                                ];
                                $estado_badge = $estado_badges[$c['estado']] ?? 'bg-slate-400';
                                ?>
                                <span class="<?= $estado_badge ?> px-2 py-1 rounded-full text-xs font-bold">
                                    <?= $c['estado'] ?>
                                </span>
                            </div>
                        </div>

                        <!-- Contenido -->
                        <div class="p-4 space-y-3">
                            <!-- Licencia -->
                            <div class="flex items-center gap-2 pb-3 border-b border-slate-100">
                                <div class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center">
                                    <i class="ri-price-tag-3-line"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-xs text-slate-500">Licencia</p>
                                    <p class="font-semibold text-slate-800"><?= htmlspecialchars($c['licencia']) ?></p>
                                </div>
                            </div>

                            <!-- Usuario -->
                            <div class="flex items-center gap-2 pb-3 border-b border-slate-100">
                                <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                                    <i class="ri-user-line"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-xs text-slate-500">Asignado a</p>
                                    <?php if ($c['usuario_id']): ?>
                                        <p class="font-semibold text-slate-800"><?= htmlspecialchars($c['usuario_nombre']) ?></p>
                                        <p class="text-xs text-slate-500"><?= htmlspecialchars($c['departamento']) ?></p>
                                    <?php else: ?>
                                        <p class="text-slate-400 italic">Sin asignar</p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Credenciales -->
                            <?php if ($c['password_ag'] || $c['pin_windows']): ?>
                                <div class="bg-slate-50 rounded-lg p-3 space-y-2">
                                    <p class="text-xs font-bold text-slate-600 uppercase flex items-center gap-1">
                                        <i class="ri-lock-password-line"></i> Credenciales
                                    </p>
                                    <?php if ($c['password_ag']): ?>
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs text-slate-500">Password AG:</span>
                                            <code class="text-xs bg-white px-2 py-1 rounded border border-slate-200 font-mono">
                                                                                <?= htmlspecialchars($c['password_ag']) ?>
                                                                            </code>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($c['pin_windows']): ?>
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs text-slate-500">PIN Windows:</span>
                                            <code class="text-xs bg-white px-2 py-1 rounded border border-slate-200 font-mono">
                                                                                <?= htmlspecialchars($c['pin_windows']) ?>
                                                                            </code>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <!-- Contacto -->
                            <?php if ($c['cuenta_gmail'] || $c['telefono_principal'] || $c['telefono_secundario']): ?>
                                <div class="bg-green-50 rounded-lg p-3 space-y-2">
                                    <p class="text-xs font-bold text-green-700 uppercase flex items-center gap-1">
                                        <i class="ri-smartphone-line"></i> Contacto
                                    </p>
                                    <?php if ($c['cuenta_gmail']): ?>
                                        <div class="flex items-center gap-2">
                                            <i class="ri-google-fill text-slate-400 text-xs"></i>
                                            <span class="text-xs text-slate-700"><?= htmlspecialchars($c['cuenta_gmail']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($c['telefono_principal']): ?>
                                        <div class="flex items-center gap-2">
                                            <i class="ri-phone-line text-slate-400 text-xs"></i>
                                            <span
                                                class="text-xs text-slate-700"><?= htmlspecialchars($c['telefono_principal']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($c['telefono_secundario']): ?>
                                        <div class="flex items-center gap-2">
                                            <i class="ri-phone-line text-slate-400 text-xs"></i>
                                            <span
                                                class="text-xs text-slate-500"><?= htmlspecialchars($c['telefono_secundario']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <!-- Observaciones -->
                            <?php if ($c['observaciones']): ?>
                                <div class="text-xs text-slate-600 bg-amber-50 p-2 rounded border-l-2 border-amber-400">
                                    <i class="ri-information-line text-amber-600"></i>
                                    <?= htmlspecialchars($c['observaciones']) ?>
                                </div>
                            <?php endif; ?>

                            <!-- Fecha -->
                            <?php if ($c['fecha_asignacion']): ?>
                                <div class="text-xs text-slate-500 flex items-center gap-1">
                                    <i class="ri-calendar-check-line"></i>
                                    Asignado: <?= date('d/m/Y', strtotime($c['fecha_asignacion'])) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>