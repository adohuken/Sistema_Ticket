<?php
/**
 * seccion_notificaciones.php - Centro de Notificaciones (Admin/SuperAdmin)
 */

$es_admin = in_array($rol_usuario, ['Admin', 'SuperAdmin']);

// --- Datos: mis notificaciones ---
$filtro_tipo = $_GET['tipo'] ?? '';
$filtro_leida = $_GET['leida'] ?? '';
$pagina = max(1, intval($_GET['pn'] ?? 1));
$por_pagina = 20;
$offset = ($pagina - 1) * $por_pagina;

$where_clauses = ['usuario_id = ?'];
$params = [$_SESSION['usuario_id']];

if ($filtro_tipo) {
    $where_clauses[] = 'tipo = ?';
    $params[] = $filtro_tipo;
}
if ($filtro_leida !== '') {
    $where_clauses[] = 'leida = ?';
    $params[] = intval($filtro_leida);
}

$where_sql = implode(' AND ', $where_clauses);

// Total para paginación
$stmt_total = $pdo->prepare("SELECT COUNT(*) FROM notificaciones WHERE $where_sql");
$stmt_total->execute($params);
$total_registros = $stmt_total->fetchColumn();
$total_paginas = max(1, ceil($total_registros / $por_pagina));

// Lista paginada
$params_page = array_merge($params, [$por_pagina, $offset]);
$stmt = $pdo->prepare("SELECT * FROM notificaciones WHERE $where_sql ORDER BY created_at DESC LIMIT ? OFFSET ?");
$stmt->execute($params_page);
$todas_notificaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Estadísticas rápidas
$stmt_stats = $pdo->prepare("
    SELECT
        COUNT(*) as total,
        SUM(CASE WHEN leida = 0 THEN 1 ELSE 0 END) as no_leidas,
        SUM(CASE WHEN tipo = 'success' THEN 1 ELSE 0 END) as tipo_success,
        SUM(CASE WHEN tipo = 'warning' THEN 1 ELSE 0 END) as tipo_warning,
        SUM(CASE WHEN tipo = 'error' THEN 1 ELSE 0 END) as tipo_error,
        SUM(CASE WHEN tipo = 'info' THEN 1 ELSE 0 END) as tipo_info
    FROM notificaciones WHERE usuario_id = ?
");
$stmt_stats->execute([$_SESSION['usuario_id']]);
$stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);

// Lista de usuarios para el panel de envío (solo admin)
$usuarios_lista = [];
if ($es_admin) {
    $stmt_u = $pdo->query("SELECT id, nombre_completo, rol_id FROM usuarios ORDER BY nombre_completo ASC");
    $usuarios_lista = $stmt_u->fetchAll(PDO::FETCH_ASSOC);
}

// Helper para tipo → color/icono
function notif_meta($tipo)
{
    return match ($tipo) {
        'success' => ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-600', 'border' => 'border-emerald-200', 'icon' => 'ri-checkbox-circle-fill', 'label' => 'Éxito', 'badge' => 'bg-emerald-100 text-emerald-700'],
        'warning' => ['bg' => 'bg-amber-100', 'text' => 'text-amber-600', 'border' => 'border-amber-200', 'icon' => 'ri-alert-fill', 'label' => 'Alerta', 'badge' => 'bg-amber-100 text-amber-700'],
        'error' => ['bg' => 'bg-red-100', 'text' => 'text-red-600', 'border' => 'border-red-200', 'icon' => 'ri-error-warning-fill', 'label' => 'Error', 'badge' => 'bg-red-100 text-red-700'],
        default => ['bg' => 'bg-blue-100', 'text' => 'text-blue-600', 'border' => 'border-blue-200', 'icon' => 'ri-information-fill', 'label' => 'Info', 'badge' => 'bg-blue-100 text-blue-700'],
    };
}

$current_url = 'index.php?view=notificaciones';
if ($filtro_tipo)
    $current_url .= '&tipo=' . urlencode($filtro_tipo);
if ($filtro_leida !== '')
    $current_url .= '&leida=' . urlencode($filtro_leida);
?>

<div class="p-6 flex-1">
    <!-- ========== HEADER ========== -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
        <div>
            <h2 class="text-2xl font-bold text-slate-800 flex items-center gap-3">
                <span
                    class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center shadow-lg shadow-blue-500/20">
                    <i class="ri-notification-3-fill text-white text-lg"></i>
                </span>
                Centro de Notificaciones
            </h2>
            <p class="text-slate-500 text-sm mt-1 ml-13">Gestiona todas tus alertas y mensajes del sistema</p>
        </div>
        <button onclick="marcarTodasLeidasGlobal()"
            class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-xl hover:bg-slate-50 hover:text-blue-600 transition-all shadow-sm flex items-center gap-2 text-sm font-medium group">
            <i class="ri-checkbox-circle-line group-hover:scale-110 transition-transform"></i>
            Marcar todas como leídas
        </button>
    </div>

    <!-- ========== STATS ========== -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
        <?php
        $stat_items = [
            ['label' => 'Total', 'value' => $stats['total'], 'icon' => 'ri-notification-3-line', 'color' => 'blue'],
            ['label' => 'No Leídas', 'value' => $stats['no_leidas'], 'icon' => 'ri-mail-unread-line', 'color' => 'rose'],
            ['label' => 'Info', 'value' => $stats['tipo_info'], 'icon' => 'ri-information-line', 'color' => 'sky'],
            ['label' => 'Éxito', 'value' => $stats['tipo_success'], 'icon' => 'ri-checkbox-circle-line', 'color' => 'emerald'],
            ['label' => 'Alertas', 'value' => $stats['tipo_warning'], 'icon' => 'ri-alert-line', 'color' => 'amber'],
            ['label' => 'Errores', 'value' => $stats['tipo_error'], 'icon' => 'ri-error-warning-line', 'color' => 'red'],
        ];
        $color_map = [
            'blue' => 'bg-blue-50 text-blue-600 border-blue-100',
            'rose' => 'bg-rose-50 text-rose-600 border-rose-100',
            'sky' => 'bg-sky-50 text-sky-600 border-sky-100',
            'emerald' => 'bg-emerald-50 text-emerald-600 border-emerald-100',
            'amber' => 'bg-amber-50 text-amber-600 border-amber-100',
            'red' => 'bg-red-50 text-red-600 border-red-100',
        ];
        foreach ($stat_items as $s):
            $cls = $color_map[$s['color']];
            ?>
            <div class="bg-white rounded-2xl border border-slate-100 p-4 shadow-sm hover:shadow-md transition-shadow">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-9 h-9 rounded-xl <?= $cls ?> border flex items-center justify-center flex-shrink-0">
                        <i class="<?= $s['icon'] ?> text-base"></i>
                    </div>
                </div>
                <div class="text-2xl font-bold text-slate-800"><?= $s['value'] ?></div>
                <div class="text-xs text-slate-400 font-medium mt-0.5"><?= $s['label'] ?></div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="grid grid-cols-1 <?= $es_admin ? 'lg:grid-cols-3' : '' ?> gap-6">

        <!-- ========== LISTA DE NOTIFICACIONES ========== -->
        <div class="<?= $es_admin ? 'lg:col-span-2' : '' ?> space-y-4">

            <!-- Filtros -->
            <form method="GET" action="index.php"
                class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 flex flex-wrap gap-3 items-end">
                <input type="hidden" name="view" value="notificaciones">
                <div class="flex-1 min-w-[140px]">
                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1 block">Tipo</label>
                    <select name="tipo"
                        class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Todos</option>
                        <option value="info" <?= $filtro_tipo === 'info' ? 'selected' : '' ?>>Info</option>
                        <option value="success" <?= $filtro_tipo === 'success' ? 'selected' : '' ?>>Éxito</option>
                        <option value="warning" <?= $filtro_tipo === 'warning' ? 'selected' : '' ?>>Alerta</option>
                        <option value="error" <?= $filtro_tipo === 'error' ? 'selected' : '' ?>>Error</option>
                    </select>
                </div>
                <div class="flex-1 min-w-[140px]">
                    <label
                        class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1 block">Estado</label>
                    <select name="leida"
                        class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Todas</option>
                        <option value="0" <?= $filtro_leida === '0' ? 'selected' : '' ?>>No leídas</option>
                        <option value="1" <?= $filtro_leida === '1' ? 'selected' : '' ?>>Leídas</option>
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors flex items-center gap-1">
                        <i class="ri-search-line"></i> Filtrar
                    </button>
                    <?php if ($filtro_tipo || $filtro_leida !== ''): ?>
                        <a href="index.php?view=notificaciones"
                            class="px-4 py-2 bg-slate-100 text-slate-600 rounded-lg text-sm font-medium hover:bg-slate-200 transition-colors flex items-center gap-1">
                            <i class="ri-close-line"></i> Limpiar
                        </a>
                    <?php endif; ?>
                </div>
            </form>

            <!-- Lista -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                <?php if (empty($todas_notificaciones)): ?>
                    <div class="p-16 text-center">
                        <div
                            class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-slate-100">
                            <i class="ri-notification-off-line text-4xl text-slate-300"></i>
                        </div>
                        <h3 class="text-lg font-semibold text-slate-600 mb-1">Sin notificaciones</h3>
                        <p class="text-slate-400 text-sm">No hay notificaciones que coincidan con los filtros.</p>
                    </div>
                <?php else: ?>
                    <div class="divide-y divide-slate-50">
                        <?php foreach ($todas_notificaciones as $n):
                            $meta = notif_meta($n['tipo']);
                            ?>
                            <div id="notif-row-<?= $n['id'] ?>"
                                class="group flex gap-4 p-5 hover:bg-slate-50/70 transition-colors <?= $n['leida'] ? 'opacity-55' : '' ?>">

                                <!-- Icono -->
                                <div class="flex-shrink-0 mt-0.5">
                                    <div
                                        class="w-10 h-10 rounded-xl <?= $meta['bg'] ?> <?= $meta['text'] ?> border <?= $meta['border'] ?> flex items-center justify-center">
                                        <i class="<?= $meta['icon'] ?> text-lg"></i>
                                    </div>
                                </div>

                                <!-- Contenido -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-start justify-between gap-3 mb-1">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <h4 class="text-sm font-semibold text-slate-800">
                                                <?= htmlspecialchars($n['titulo']) ?>
                                            </h4>
                                            <span
                                                class="text-[10px] px-2 py-0.5 rounded-full font-medium <?= $meta['badge'] ?>">
                                                <?= $meta['label'] ?>
                                            </span>
                                            <?php if (!$n['leida']): ?>
                                                <span class="w-2 h-2 bg-blue-500 rounded-full inline-block flex-shrink-0"
                                                    title="No leída"></span>
                                            <?php endif; ?>
                                        </div>
                                        <span class="text-[11px] text-slate-400 flex-shrink-0 whitespace-nowrap">
                                            <?= date('d M Y, H:i', strtotime($n['created_at'])) ?>
                                        </span>
                                    </div>
                                    <p class="text-sm text-slate-500 leading-relaxed mb-2">
                                        <?= htmlspecialchars($n['mensaje']) ?>
                                    </p>
                                    <div class="flex items-center gap-3">
                                        <?php if ($n['enlace']): ?>
                                            <a href="<?= htmlspecialchars($n['enlace']) ?>"
                                                class="text-xs text-blue-600 hover:text-blue-800 font-medium flex items-center gap-1">
                                                <i class="ri-external-link-line"></i> Ver detalle
                                            </a>
                                        <?php endif; ?>
                                        <?php if (!$n['leida']): ?>
                                            <button onclick="marcarLeidaIndividual(<?= $n['id'] ?>)"
                                                class="text-xs text-slate-400 hover:text-blue-600 font-medium flex items-center gap-1 transition-colors btn-marcar-leida">
                                                <i class="ri-check-double-line"></i> Marcar leída
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Paginación -->
                    <?php if ($total_paginas > 1): ?>
                        <div class="border-t border-slate-50 px-5 py-4 flex items-center justify-between">
                            <span class="text-xs text-slate-400">
                                Mostrando <?= count($todas_notificaciones) ?> de <?= $total_registros ?> notificaciones
                            </span>
                            <div class="flex gap-1">
                                <?php for ($p = 1; $p <= $total_paginas; $p++): ?>
                                    <a href="<?= $current_url ?>&pn=<?= $p ?>"
                                        class="w-8 h-8 flex items-center justify-center rounded-lg text-sm font-medium transition-colors <?= $p === $pagina ? 'bg-blue-600 text-white' : 'text-slate-500 hover:bg-slate-100' ?>">
                                        <?= $p ?>
                                    </a>
                                <?php endfor; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- ========== PANEL ADMIN: ENVIAR NOTIFICACIÓN ========== -->
        <?php if ($es_admin): ?>
            <div class="space-y-4">

                <!-- Enviar notificación -->
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-50 bg-gradient-to-r from-indigo-50 to-blue-50">
                        <h3 class="font-semibold text-slate-800 flex items-center gap-2">
                            <i class="ri-send-plane-fill text-indigo-500"></i>
                            Enviar Notificación
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">Envía un mensaje a uno o todos los usuarios</p>
                    </div>
                    <form id="form-enviar-notif" class="p-5 space-y-4">
                        <input type="hidden" name="csrf_token"
                            value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

                        <!-- Destinatario -->
                        <div>
                            <label
                                class="text-xs font-semibold text-slate-600 uppercase tracking-wide mb-1.5 block">Destinatario</label>
                            <select name="destinatario_id" id="notif-destinatario"
                                class="w-full text-sm border border-slate-200 rounded-xl px-3 py-2.5 text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-400 bg-white">
                                <option value="todos">📢 Todos los usuarios</option>
                                <?php foreach ($usuarios_lista as $u): ?>
                                    <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['nombre_completo']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Tipo -->
                        <div>
                            <label
                                class="text-xs font-semibold text-slate-600 uppercase tracking-wide mb-1.5 block">Tipo</label>
                            <div class="grid grid-cols-2 gap-2">
                                <?php
                                $tipos_btn = [
                                    'info' => ['label' => 'Info', 'icon' => 'ri-information-fill', 'sel' => 'border-2 border-blue-500 bg-blue-50 text-blue-700', 'nor' => 'border border-slate-200 text-slate-600 hover:border-blue-300'],
                                    'success' => ['label' => 'Éxito', 'icon' => 'ri-checkbox-circle-fill', 'sel' => 'border-2 border-emerald-500 bg-emerald-50 text-emerald-700', 'nor' => 'border border-slate-200 text-slate-600 hover:border-emerald-300'],
                                    'warning' => ['label' => 'Alerta', 'icon' => 'ri-alert-fill', 'sel' => 'border-2 border-amber-500 bg-amber-50 text-amber-700', 'nor' => 'border border-slate-200 text-slate-600 hover:border-amber-300'],
                                    'error' => ['label' => 'Error', 'icon' => 'ri-error-warning-fill', 'sel' => 'border-2 border-red-500 bg-red-50 text-red-700', 'nor' => 'border border-slate-200 text-slate-600 hover:border-red-300'],
                                ];
                                foreach ($tipos_btn as $k => $t): ?>
                                    <button type="button" onclick="selectTipoNotif('<?= $k ?>', this)" data-tipo="<?= $k ?>"
                                        class="notif-tipo-btn flex items-center gap-2 px-3 py-2 rounded-xl text-sm font-medium transition-all <?= $k === 'info' ? $t['sel'] : $t['nor'] ?>">
                                        <i class="<?= $t['icon'] ?>"></i> <?= $t['label'] ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                            <input type="hidden" name="tipo" id="notif-tipo-value" value="info">
                        </div>

                        <!-- Título -->
                        <div>
                            <label
                                class="text-xs font-semibold text-slate-600 uppercase tracking-wide mb-1.5 block">Título</label>
                            <input type="text" name="titulo" placeholder="Ej: Mantenimiento programado"
                                class="w-full text-sm border border-slate-200 rounded-xl px-3 py-2.5 text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-400 placeholder:text-slate-300"
                                required>
                        </div>

                        <!-- Mensaje -->
                        <div>
                            <label
                                class="text-xs font-semibold text-slate-600 uppercase tracking-wide mb-1.5 block">Mensaje</label>
                            <textarea name="mensaje" rows="3" placeholder="Escribe el contenido de la notificación..."
                                class="w-full text-sm border border-slate-200 rounded-xl px-3 py-2.5 text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-400 placeholder:text-slate-300 resize-none"
                                required></textarea>
                        </div>

                        <!-- Enlace opcional -->
                        <div>
                            <label class="text-xs font-semibold text-slate-600 uppercase tracking-wide mb-1.5 block">Enlace
                                (opcional)</label>
                            <input type="text" name="enlace" placeholder="index.php?view=..."
                                class="w-full text-sm border border-slate-200 rounded-xl px-3 py-2.5 text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-400 placeholder:text-slate-300">
                        </div>

                        <button type="submit"
                            class="w-full py-2.5 bg-gradient-to-r from-indigo-500 to-blue-600 text-white rounded-xl font-semibold text-sm hover:from-indigo-600 hover:to-blue-700 transition-all shadow-md shadow-indigo-200 flex items-center justify-center gap-2">
                            <i class="ri-send-plane-fill"></i>
                            Enviar Notificación
                        </button>
                    </form>
                </div>

                <!-- Info card -->
                <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-5 text-white">
                    <div class="flex items-center gap-2 mb-3">
                        <i class="ri-lightbulb-flash-line text-amber-400 text-lg"></i>
                        <span class="font-semibold text-sm">Acerca de las notificaciones</span>
                    </div>
                    <ul class="text-xs text-slate-400 space-y-1.5">
                        <li class="flex items-start gap-1.5"><i
                                class="ri-arrow-right-s-line text-slate-500 mt-0.5 flex-shrink-0"></i> Las notificaciones
                            aparecen en tiempo real en la barra superior.</li>
                        <li class="flex items-start gap-1.5"><i
                                class="ri-arrow-right-s-line text-slate-500 mt-0.5 flex-shrink-0"></i> El sistema genera
                            notificaciones automáticamente al asignar o completar tickets.</li>
                        <li class="flex items-start gap-1.5"><i
                                class="ri-arrow-right-s-line text-slate-500 mt-0.5 flex-shrink-0"></i> El sonido de alerta
                            puede desactivarse desde Configuración.</li>
                    </ul>
                </div>
            </div>
        <?php endif; ?>

    </div><!-- /grid -->
</div>

<script>
    // Tipo de notificación selector
    const tipoMeta = {
        info: { sel: 'border-2 border-blue-500 bg-blue-50 text-blue-700', nor: 'border border-slate-200 text-slate-600 hover:border-blue-300' },
        success: { sel: 'border-2 border-emerald-500 bg-emerald-50 text-emerald-700', nor: 'border border-slate-200 text-slate-600 hover:border-emerald-300' },
        warning: { sel: 'border-2 border-amber-500 bg-amber-50 text-amber-700', nor: 'border border-slate-200 text-slate-600 hover:border-amber-300' },
        error: { sel: 'border-2 border-red-500 bg-red-50 text-red-700', nor: 'border border-slate-200 text-slate-600 hover:border-red-300' },
    };

    function selectTipoNotif(tipo, el) {
        document.getElementById('notif-tipo-value').value = tipo;
        document.querySelectorAll('.notif-tipo-btn').forEach(btn => {
            const t = btn.dataset.tipo;
            btn.className = btn.className.replace(/border-2 border-\w+-500 bg-\w+-50 text-\w+-700/, '').replace(/border border-slate-200 text-slate-600 hover:border-\w+-300/, '').trim();
            const classes = tipoMeta[t];
            if (btn.dataset.tipo === tipo) {
                btn.classList.add(...classes.sel.split(' '));
            } else {
                btn.classList.add(...classes.nor.split(' '));
            }
        });
    }

    // Enviar notificación (admin)
    document.getElementById('form-enviar-notif')?.addEventListener('submit', function (e) {
        e.preventDefault();
        const fd = new FormData(this);
        fd.append('accion', 'enviar_notificacion_admin');

        fetch('index.php', {
            method: 'POST',
            body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(r => r.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({ icon: 'success', title: '¡Enviado!', text: data.msg || 'Notificación enviada correctamente.', timer: 2500, showConfirmButton: false });
                    this.reset();
                    document.getElementById('notif-tipo-value').value = 'info';
                    document.querySelectorAll('.notif-tipo-btn').forEach(btn => {
                        const t = btn.dataset.tipo;
                        btn.className = btn.className.replace(/border-2 border-\w+-500 bg-\w+-50 text-\w+-700/g, '').trim();
                        if (t === 'info') btn.classList.add(...tipoMeta.info.sel.split(' '));
                        else btn.classList.add(...tipoMeta[t].nor.split(' '));
                    });
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: data.msg || 'No se pudo enviar.' });
                }
            })
            .catch(() => Swal.fire({ icon: 'error', title: 'Error de red', text: 'Intenta de nuevo.' }));
    });

    // Marcar todas como leídas
    function marcarTodasLeidasGlobal() {
        const fd = new FormData();
        fd.append('accion', 'marcar_notificacion');
        fd.append('id', 'todas');
        fetch('index.php', { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json())
            .then(data => { if (data.status === 'success') location.reload(); });
    }

    // Marcar una como leída
    function marcarLeidaIndividual(id) {
        const fd = new FormData();
        fd.append('accion', 'marcar_notificacion');
        fd.append('id', id);
        fetch('index.php', { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json())
            .then(data => {
                if (data.status === 'success') {
                    const row = document.getElementById('notif-row-' + id);
                    if (row) {
                        row.classList.add('opacity-55');
                        row.querySelector('.btn-marcar-leida')?.remove();
                        row.querySelector('span[title="No leída"]')?.remove();
                    }
                }
            });
    }
</script>