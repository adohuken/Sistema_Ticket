<?php
/**
 * seccion_notificaciones.php - Centro de Notificaciones Completo
 */

// Obtener notificaciones
$stmt = $pdo->prepare("SELECT * FROM notificaciones WHERE usuario_id = ? ORDER BY created_at DESC LIMIT 50");
$stmt->execute([$_SESSION['usuario_id']]);
$todas_notificaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="p-6 flex-1">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="flex items-center justify-between mb-8">
            <div>
                <h2 class="text-2xl font-bold text-slate-800">Centro de Notificaciones</h2>
                <p class="text-slate-500">Historial de tus últimas alertas y mensajes</p>
            </div>
            <button onclick="marcarTodasLeidasGlobal()"
                class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 hover:text-blue-600 transition-colors shadow-sm flex items-center gap-2">
                <i class="ri-checkbox-circle-line"></i>
                Marcar todas como leídas
            </button>
        </div>

        <!-- Lista de Notificaciones -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <?php if (empty($todas_notificaciones)): ?>
                <div class="p-12 text-center">
                    <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="ri-notification-off-line text-3xl text-slate-300"></i>
                    </div>
                    <h3 class="text-lg font-medium text-slate-700 mb-1">No tienes notificaciones</h3>
                    <p class="text-slate-500">Te avisaremos cuando haya actividad importante.</p>
                </div>
            <?php else: ?>
                <div class="divide-y divide-slate-100">
                    <?php foreach ($todas_notificaciones as $n): ?>
                        <div id="notif-row-<?= $n['id'] ?>"
                            class="p-6 hover:bg-slate-50 transition-colors flex gap-4 <?= $n['leida'] ? 'opacity-60 bg-slate-50/50' : 'bg-white' ?>">
                            <!-- Icono -->
                            <div class="flex-shrink-0">
                                <?php
                                $bg_color = 'bg-blue-100 text-blue-600';
                                $icon = 'ri-information-fill';

                                if ($n['tipo'] == 'success') {
                                    $bg_color = 'bg-emerald-100 text-emerald-600';
                                    $icon = 'ri-checkbox-circle-fill';
                                }
                                if ($n['tipo'] == 'warning') {
                                    $bg_color = 'bg-amber-100 text-amber-600';
                                    $icon = 'ri-alert-fill';
                                }
                                if ($n['tipo'] == 'error') {
                                    $bg_color = 'bg-red-100 text-red-600';
                                    $icon = 'ri-error-warning-fill';
                                }
                                ?>
                                <div class="w-10 h-10 rounded-full <?= $bg_color ?> flex items-center justify-center text-lg">
                                    <i class="<?= $icon ?>"></i>
                                </div>
                            </div>

                            <!-- Contenido -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between mb-1">
                                    <h4 class="text-sm font-semibold text-slate-800 truncate pr-4">
                                        <?= htmlspecialchars($n['titulo']) ?>
                                    </h4>
                                    <span class="text-xs text-slate-400 flex-shrink-0 whitespace-nowrap">
                                        <?= date('d M Y, h:i A', strtotime($n['created_at'])) ?>
                                    </span>
                                </div>
                                <p class="text-sm text-slate-600 mb-2 leading-relaxed">
                                    <?= htmlspecialchars($n['mensaje']) ?>
                                </p>

                                <?php if (!$n['leida']): ?>
                                    <button onclick="marcarLeidaIndividual(<?= $n['id'] ?>)"
                                        class="text-xs font-medium text-blue-600 hover:text-blue-800 transition-colors flex items-center gap-1 btn-marcar-leida">
                                        <i class="ri-check-double-line"></i> Marcar como leída
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    function marcarTodasLeidasGlobal() {
        const formData = new FormData();
        formData.append('accion', 'marcar_notificacion');
        formData.append('id', 'todas');

        fetch('index.php', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    // Recargar para ver cambios limpios o actualizar UI
                    location.reload();
                }
            });
    }

    function marcarLeidaIndividual(id) {
        const formData = new FormData();
        formData.append('accion', 'marcar_notificacion');
        formData.append('id', id);

        fetch('index.php', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    const row = document.getElementById('notif-row-' + id);
                    if (row) {
                        row.classList.add('opacity-60', 'bg-slate-50/50');
                        row.classList.remove('bg-white');
                        const btn = row.querySelector('.btn-marcar-leida');
                        if (btn) btn.remove();
                    }
                }
            });
    }
</script>