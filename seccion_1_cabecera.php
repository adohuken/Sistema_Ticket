<?php
/**
 * seccion_1_cabecera.php - Cabecera superior del sistema
 */

// Obtener información del usuario actual (corregido: buscar por ID, no por rol)
$usuario_actual = null;
if (isset($usuarios)) {
    foreach ($usuarios as $u) {
        if ($u['id'] == $usuario_id) {
            $usuario_actual = $u;
            break;
        }
    }
}
$nombre_usuario_display = $usuario_actual ? $usuario_actual['nombre'] : $nombre_usuario;

// Verificar preferencia de sonido
$sound_enabled = 1; // Default
try {
    if (isset($pdo) && isset($usuario_id)) {
        $stmt_sound = $pdo->prepare("SELECT notifs_sonido FROM usuarios WHERE id = ?");
        $stmt_sound->execute([$usuario_id]);
        $res = $stmt_sound->fetchColumn();
        if ($res !== false)
            $sound_enabled = $res;
    }
} catch (Exception $e) {
}
?>
<!-- Audio para notificaciones -->
<?php if ($sound_enabled): ?>
    <audio id="notif-sound" preload="auto">
        <source src="https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3" type="audio/mpeg">
    </audio>
    <script>
        window.playNotificationSound = function () {
            const audio = document.getElementById('notif-sound');
            if (audio) {
                audio.currentTime = 0;
                const promise = audio.play();
                if (promise !== undefined) {
                    promise.catch(error => {
                        console.log('Autoplay prevent or error:', error);
                    });
                }
            }
        }
    </script>
<?php else: ?>
    <script>window.playNotificationSound = function () { };</script>
<?php endif; ?>

<header class="fixed top-0 right-0 left-72 h-20 z-10 transition-all duration-300">
    <div
        class="h-full px-8 flex items-center justify-between bg-white/80 backdrop-blur-md border-b border-slate-200/60 shadow-sm">
        <!-- Breadcrumb / Title -->
        <div>
            <h2 class="text-xl font-bold text-slate-800 tracking-tight">
                <?php
                $view = $_GET['view'] ?? 'dashboard';
                $titles = [
                    'dashboard' => 'Panel de Control',
                    'crear_ticket' => 'Nuevo Ticket',
                    'mis_tickets' => 'Mis Tickets',
                    'usuarios' => 'Gestión de Usuarios',
                    'asignar' => 'Asignación de Tickets',
                    'asignados' => 'Tickets Asignados',
                    'reportes' => 'Reportes y Estadísticas',
                    'backup' => 'Copia de Seguridad',
                    'restore' => 'Restaurar Sistema',
                    'config' => 'Configuración',
                    'historial_tecnico' => 'Historial Técnico',
                    'notificaciones' => 'Notificaciones',
                ];
                echo $titles[$view] ?? 'Sistema de Tickets';
                ?>
            </h2>
            <p class="text-xs text-slate-500 font-medium mt-0.5">Bienvenido de nuevo,
                <?php echo htmlspecialchars($nombre_usuario); ?>
            </p>
        </div>

        <!-- Right Actions -->
        <div class="flex items-center gap-4">
            <!-- Notifications -->
            <?php
            // Obtener notificaciones no leídas
            $notificaciones = [];
            $count_noleidas = 0;

            if (isset($pdo) && isset($usuario_id)) {
                try {
                    // Contar no leídas
                    $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM notificaciones WHERE usuario_id = ? AND leida = 0");
                    $stmt_count->execute([$usuario_id]);
                    $count_noleidas = $stmt_count->fetchColumn();

                    // Obtener últimas 5 NO LEÍDAS
                    $stmt_notifs = $pdo->prepare("SELECT * FROM notificaciones WHERE usuario_id = ? AND leida = 0 ORDER BY created_at DESC LIMIT 5");
                    $stmt_notifs->execute([$usuario_id]);
                    $notificaciones = $stmt_notifs->fetchAll(PDO::FETCH_ASSOC);
                } catch (Exception $e) {
                }
            }
            ?>
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open"
                    onclick="document.getElementById('notif-dropdown').classList.toggle('hidden');"
                    class="w-10 h-10 rounded-full bg-white border border-slate-200 text-slate-500 hover:text-blue-600 hover:border-blue-200 hover:shadow-md transition-all flex items-center justify-center relative group">
                    <i class="ri-notification-3-line text-lg"></i>
                    <?php if ($count_noleidas > 0): ?>
                        <span
                            class="absolute top-2 right-2.5 w-2 h-2 bg-red-500 rounded-full border border-white animate-pulse"></span>
                    <?php endif; ?>
                </button>

                <!-- Dropdown Notificaciones -->
                <div id="notif-dropdown"
                    class="hidden absolute right-0 mt-3 w-80 bg-white rounded-xl shadow-xl border border-slate-100 z-50 overflow-hidden">
                    <div class="p-3 border-b border-slate-50 bg-slate-50/50 flex justify-between items-center">
                        <h3 class="font-semibold text-slate-700 text-sm">Notificaciones</h3>
                        <?php if ($count_noleidas > 0): ?>
                            <span id="notif-count-text"
                                class="text-xs bg-blue-100 text-blue-600 px-2 py-0.5 rounded-full font-medium cursor-pointer hover:bg-blue-200 transition-colors"
                                onclick="marcarNotificacion('todas')">
                                Marcar todas leídas
                            </span>
                        <?php endif; ?>
                    </div>

                    <div id="notif-list-body" class="max-h-[300px] overflow-y-auto">
                        <?php if (empty($notificaciones)): ?>
                            <div class="p-8 text-center text-slate-400">
                                <i class="ri-notification-off-line text-3xl mb-2 block"></i>
                                <span class="text-sm">No tienes notificaciones</span>
                            </div>
                        <?php else: ?>
                            <?php foreach ($notificaciones as $n): ?>
                                <div onclick="marcarNotificacion(<?= $n['id'] ?>, this, <?= htmlspecialchars(json_encode($n['enlace'] ?: ''), ENT_QUOTES) ?>)"
                                    class="notif-item p-4 border-b border-slate-50 hover:bg-slate-50 transition-colors cursor-pointer relative group">
                                    <?php if (!$n['leida']): ?>
                                        <div class="notif-indicator absolute left-0 top-0 bottom-0 w-1 bg-blue-500"></div>
                                    <?php endif; ?>

                                    <div class="flex gap-3">
                                        <div class="mt-1">
                                            <?php
                                            $icon = 'ri-information-line';
                                            $color = 'text-blue-500';
                                            if ($n['tipo'] == 'success') {
                                                $icon = 'ri-checkbox-circle-line';
                                                $color = 'text-emerald-500';
                                            }
                                            if ($n['tipo'] == 'warning') {
                                                $icon = 'ri-alert-line';
                                                $color = 'text-amber-500';
                                            }
                                            if ($n['tipo'] == 'error') {
                                                $icon = 'ri-error-warning-line';
                                                $color = 'text-red-500';
                                            }
                                            ?>
                                            <i class="<?= $icon ?> <?= $color ?> text-lg"></i>
                                        </div>
                                        <div>
                                            <h4 class="text-sm font-semibold text-slate-800 mb-0.5">
                                                <?= htmlspecialchars($n['titulo']) ?>
                                            </h4>
                                            <p class="text-xs text-slate-500 leading-relaxed">
                                                <?= htmlspecialchars($n['mensaje']) ?>
                                            </p>
                                            <span class="text-[10px] text-slate-400 mt-2 block">
                                                <?= date('d M, H:i', strtotime($n['created_at'])) ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <div class="p-2 border-t border-slate-50 bg-slate-50 text-center">
                        <a href="index.php?view=notificaciones"
                            class="text-xs text-blue-600 font-medium hover:text-blue-700">Ver todas</a>
                    </div>
                </div>
            </div>

            <!-- Interacción de notificaciones -->
            <script>
                function marcarNotificacion(id, elemento, enlace) {
                    if (elemento && elemento.classList.contains('opacity-60')) return;

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
                                if (id === 'todas') {
                                    // Eliminar todas las notificaciones del dropdown
                                    const lista = document.getElementById('notif-list-body');
                                    if (lista) {
                                        lista.innerHTML = `<div class="p-8 text-center text-slate-400"><i class="ri-notification-off-line text-3xl mb-2 block"></i><span class="text-sm">No tienes notificaciones</span></div>`;
                                    }
                                    document.querySelector('.animate-pulse')?.remove();
                                    document.getElementById('notif-count-text')?.remove();
                                } else {
                                    // Animar salida y eliminar del DOM
                                    if (elemento) {
                                        elemento.style.transition = 'all 0.25s ease';
                                        elemento.style.maxHeight = elemento.offsetHeight + 'px';
                                        elemento.style.overflow = 'hidden';
                                        requestAnimationFrame(() => {
                                            elemento.style.maxHeight = '0';
                                            elemento.style.opacity = '0';
                                            elemento.style.paddingTop = '0';
                                            elemento.style.paddingBottom = '0';
                                        });
                                        setTimeout(() => {
                                            elemento.remove();
                                            const items = document.querySelectorAll('.notif-item');
                                            if (items.length === 0) {
                                                const lista = document.getElementById('notif-list-body');
                                                if (lista) lista.innerHTML = `<div class="p-8 text-center text-slate-400"><i class="ri-notification-off-line text-3xl mb-2 block"></i><span class="text-sm">No tienes notificaciones</span></div>`;
                                                document.querySelector('.animate-pulse')?.remove();
                                                document.getElementById('notif-count-text')?.remove();
                                            }
                                            // Navegar al enlace si existe
                                            if (enlace) window.location.href = enlace;
                                        }, 270);
                                    }
                                }
                            }
                        })
                        .catch(function (err) {
                            console.error('Error al marcar notificacion:', err);
                        });
                }

                document.addEventListener('click', function (event) {
                    const dropdown = document.getElementById('notif-dropdown');
                    const button = event.target.closest('button');
                    const isDropdown = event.target.closest('#notif-dropdown');

                    if (!button && !isDropdown && !dropdown.classList.contains('hidden')) {
                        dropdown.classList.add('hidden');
                    }
                });

                // ========== SONIDO DE NOTIFICACIÓN ========== //
                window.playNotificationSound = function() {
                    try {
                        var ctx = new (window.AudioContext || window.webkitAudioContext)();

                        function playTone(freq, startTime, duration) {
                            var osc = ctx.createOscillator();
                            var gain = ctx.createGain();
                            osc.connect(gain);
                            gain.connect(ctx.destination);
                            osc.type = 'sine';
                            osc.frequency.value = freq;
                            gain.gain.setValueAtTime(0.35, startTime);
                            gain.gain.exponentialRampToValueAtTime(0.001, startTime + duration);
                            osc.start(startTime);
                            osc.stop(startTime + duration);
                        }

                        var t = ctx.currentTime;
                        playTone(523, t,        0.18); // Do
                        playTone(659, t + 0.19, 0.25); // Mi
                        playTone(784, t + 0.38, 0.35); // Sol
                    } catch(e) {}
                };

                // ========== POLLING TIEMPO REAL ========== //
                // Guardar el conteo actual como base para detectar cambios
                let _lastNotifCount = <?= (int) $count_noleidas ?>;
                let _lastSeenId = null;

                function _checkNewNotifs() {
                    fetch('index.php?action=check_notifs', {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    })
                        .then(r => r.json())
                        .then(data => {
                            const newCount = data.count || 0;

                            if (newCount > _lastNotifCount) {
                                // Hay notificaciones nuevas
                                const latest = data.latest;
                                const isNew = latest && latest.id !== _lastSeenId;

                                if (isNew) {
                                    _lastSeenId = latest.id;

                                    // Actualizar badge rojo
                                    const bellBtn = document.querySelector('[onclick*="notif-dropdown"]');
                                    let badge = document.querySelector('.animate-pulse');
                                    if (!badge && bellBtn) {
                                        badge = document.createElement('span');
                                        badge.className = 'absolute top-2 right-2.5 w-2 h-2 bg-red-500 rounded-full border border-white animate-pulse';
                                        bellBtn.appendChild(badge);
                                    }

                                    // Reproducir sonido
                                    if (typeof window.playNotificationSound === 'function') {
                                        window.playNotificationSound();
                                    }

                                    // Toast SweetAlert (si está disponible)
                                    const tipoIconos = { warning: '⚠️', success: '✅', error: '❌', info: 'ℹ️' };
                                    const icono = tipoIconos[latest.tipo] || 'ℹ️';

                                    if (typeof Swal !== 'undefined') {
                                        Swal.fire({
                                            toast: true,
                                            position: 'top-end',
                                            icon: (latest.tipo === 'warning' ? 'warning' : (latest.tipo === 'error' ? 'error' : (latest.tipo === 'success' ? 'success' : 'info'))),
                                            title: latest.titulo,
                                            text: latest.mensaje,
                                            showConfirmButton: false,
                                            timer: 6000,
                                            timerProgressBar: true,
                                            showCloseButton: true,
                                            customClass: { popup: 'shadow-xl text-sm' }
                                        });
                                    }
                                }
                            }

                            _lastNotifCount = newCount;
                        })
                        .catch(() => { }); // Silenciar errores de red
                }

                // Iniciar polling cada 30 segundos
                setTimeout(() => {
                    _checkNewNotifs();
                    setInterval(_checkNewNotifs, 30000);
                }, 5000); // Primer check a los 5s de cargar
            </script>

            <!-- User Menu -->
            <div class="flex items-center gap-3 pl-4 border-l border-slate-200">
                <div class="text-right hidden sm:block">
                    <p class="text-sm font-semibold text-slate-700 leading-none">
                        <?php echo htmlspecialchars($nombre_usuario); ?>
                    </p>
                    <p class="text-xs text-slate-400 mt-1 font-medium"><?php echo htmlspecialchars($rol_usuario); ?></p>
                </div>
                <div
                    class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-100 to-indigo-100 border-2 border-white shadow-sm flex items-center justify-center text-blue-600 font-bold">
                    <?php echo strtoupper(substr($nombre_usuario, 0, 1)); ?>
                </div>
                <a href="logout.php"
                    class="w-10 h-10 rounded-full bg-red-50 border border-red-200 text-red-600 hover:bg-red-600 hover:text-white hover:shadow-md transition-all flex items-center justify-center"
                    title="Cerrar Sesión">
                    <i class="ri-logout-box-line text-lg"></i>
                </a>
            </div>
        </div>
    </div>
</header>