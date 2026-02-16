<?php
/**
 * seccion_configuracion.php - Configuración del Sistema
 */
?>

<div class="p-6 flex-1">
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 mb-8">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-purple-100 rounded-xl">
                        <i class="ri-settings-3-line text-3xl text-purple-600"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-slate-800">Configuración del Sistema</h2>
                        <p class="text-slate-500">Personaliza la experiencia del sistema</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Apariencia -->
            <div class="bg-white rounded-2xl p-6 shadow-lg border border-slate-100">
                <h3 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
                    <i class="ri-palette-line text-purple-600"></i>
                    Apariencia
                </h3>

                <div class="space-y-4">
                    <div class="flex items-center justify-between p-4 bg-slate-50 rounded-xl">
                        <div>
                            <h4 class="font-semibold text-slate-800">Tema del Sistema</h4>
                            <p class="text-sm text-slate-500">Alternar entre modo claro y oscuro</p>
                        </div>
                        <button class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            <i class="ri-moon-line mr-1"></i> Tema
                        </button>
                    </div>
                </div>
            </div>

            <!-- Sistema -->
            <div class="bg-white rounded-2xl p-6 shadow-lg border border-slate-100">
                <h3 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
                    <i class="ri-settings-4-line text-blue-600"></i>
                    Sistema
                </h3>

                <div class="space-y-3">
                    <div
                        class="flex items-center justify-between p-3 bg-slate-50 rounded-lg hover:bg-slate-100 transition-colors">
                        <div class="flex items-center gap-3">
                            <i class="ri-shield-check-line text-xl text-green-600"></i>
                            <span class="font-medium text-slate-700">Versión del Sistema</span>
                        </div>
                        <span class="text-sm font-bold text-slate-600">v2.0 Premium</span>
                    </div>

                    <div
                        class="flex items-center justify-between p-3 bg-slate-50 rounded-lg hover:bg-slate-100 transition-colors">
                        <div class="flex items-center gap-3">
                            <i class="ri-database-2-line text-xl text-blue-600"></i>
                            <span class="font-medium text-slate-700">Base de Datos</span>
                        </div>
                        <span class="text-sm font-bold text-green-600">Conectada</span>
                    </div>
                </div>
            </div>

<?php
// Obtener preferencias actuales
$stmt_prefs = $pdo->prepare("SELECT notifs_email, notifs_sonido FROM usuarios WHERE id = ?");
$stmt_prefs->execute([$_SESSION['usuario_id']]);
$prefs = $stmt_prefs->fetch(PDO::FETCH_ASSOC) ?: ['notifs_email' => 0, 'notifs_sonido' => 1];
?>
            <!-- Notificaciones -->
            <div class="bg-white rounded-2xl p-6 shadow-lg border border-slate-100">
                <h3 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
                    <i class="ri-notification-3-line text-amber-600"></i>
                    Notificaciones
                </h3>

                <form id="form-notifs" class="space-y-3">
                    <label
                        class="flex items-center justify-between p-3 bg-slate-50 rounded-lg cursor-pointer hover:bg-slate-100 transition-colors">
                        <div class="flex items-center gap-3">
                            <i class="ri-mail-line text-xl text-blue-600"></i>
                            <span class="font-medium text-slate-700">Notificaciones por Email</span>
                        </div>
                        <input type="checkbox" name="notifs_email" value="1" 
                            <?= $prefs['notifs_email'] ? 'checked' : '' ?>
                            onchange="guardarPreferencias()"
                            class="w-5 h-5 accent-blue-600 rounded">
                    </label>

                    <label
                        class="flex items-center justify-between p-3 bg-slate-50 rounded-lg cursor-pointer hover:bg-slate-100 transition-colors">
                        <div class="flex items-center gap-3">
                            <i class="ri-sound-module-line text-xl text-purple-600"></i>
                            <span class="font-medium text-slate-700">Sonidos del Sistema</span>
                        </div>
                        <input type="checkbox" name="notifs_sonido" value="1" 
                            <?= $prefs['notifs_sonido'] ? 'checked' : '' ?>
                            onchange="guardarPreferencias()"
                            class="w-5 h-5 accent-purple-600 rounded">
                    </label>
                    <div id="msg-notifs" class="text-xs text-center h-4 transition-all duration-300"></div>
                </form>

                <script>
                function guardarPreferencias() {
                    const form = document.getElementById('form-notifs');
                    const formData = new FormData(form);
                    formData.append('accion', 'guardar_preferencias_notifs');
                    
                    const msgDiv = document.getElementById('msg-notifs');
                    msgDiv.textContent = 'Guardando...';
                    msgDiv.className = 'text-xs text-center h-4 text-slate-500';

                    fetch('index.php', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if(data.status === 'success') {
                            msgDiv.textContent = 'Cambios guardados';
                            msgDiv.className = 'text-xs text-center h-4 text-green-600 font-bold';
                            setTimeout(() => { msgDiv.textContent = ''; }, 2000);
                        } else {
                            msgDiv.textContent = 'Error al guardar';
                            msgDiv.className = 'text-xs text-center h-4 text-red-600 font-bold';
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        msgDiv.textContent = 'Error de conexión';
                        msgDiv.className = 'text-xs text-center h-4 text-red-600 font-bold';
                    });
                }
                </script>
            </div>

            <!-- Configuración SMTP (Nuevo) -->
            <?php $mail_config = require __DIR__ . '/config_mail.php'; ?>
            <div class="bg-white rounded-2xl p-6 shadow-lg border border-slate-100 lg:col-span-2">
                <h3 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
                    <i class="ri-mail-send-line text-indigo-600"></i>
                    Configuración de Correo (SMTP)
                </h3>
                
                <form method="POST" action="index.php?view=config" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="accion" value="guardar_config_mail">

                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Método de Envío</label>
                        <select name="driver" class="w-full px-4 py-2 border border-slate-200 rounded-lg bg-slate-50">
                            <option value="smtp" <?= ($mail_config['driver'] ?? '') === 'smtp' ? 'selected' : '' ?>>SMTP (Recomendado)</option>
                            <option value="mail" <?= ($mail_config['driver'] ?? '') === 'mail' ? 'selected' : '' ?>>Mail Nativo (PHP)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Servidor SMTP (Host)</label>
                        <input type="text" name="host" value="<?= htmlspecialchars($mail_config['host'] ?? '') ?>" placeholder="smtp.gmail.com"
                            class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>

                    <div>
                         <label class="block text-sm font-bold text-slate-700 mb-2">Usuario (Correo)</label>
                        <input type="text" name="username" value="<?= htmlspecialchars($mail_config['username'] ?? '') ?>"
                            class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>

                    <div>
                         <label class="block text-sm font-bold text-slate-700 mb-2">Contraseña</label>
                        <input type="password" name="password" placeholder="Dejar vacía para no cambiar"
                            class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Puerto</label>
                            <input type="number" name="port" value="<?= htmlspecialchars($mail_config['port'] ?? 587) ?>"
                                class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>
                        <div>
                             <label class="block text-sm font-bold text-slate-700 mb-2">Cifrado</label>
                             <select name="encryption" class="w-full px-4 py-2 border border-slate-200 rounded-lg bg-slate-50">
                                <option value="tls" <?= ($mail_config['encryption'] ?? '') === 'tls' ? 'selected' : '' ?>>TLS</option>
                                <option value="ssl" <?= ($mail_config['encryption'] ?? '') === 'ssl' ? 'selected' : '' ?>>SSL</option>
                                <option value="" <?= empty($mail_config['encryption']) ? 'selected' : '' ?>>Ninguno</option>
                            </select>
                        </div>
                    </div>

                     <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Nombre Remitente</label>
                        <input type="text" name="from_name" value="<?= htmlspecialchars($mail_config['from_name'] ?? 'Sistema Web') ?>"
                            class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>

                    <div class="md:col-span-2 flex justify-between mt-2 items-center">
                        <button type="button" onclick="probarConexionSMTP()"
                            class="px-5 py-2 border border-slate-300 text-slate-700 bg-white hover:bg-slate-50 font-bold rounded-lg transition-all flex items-center gap-2">
                            <i class="ri-wifi-line"></i> Probar Conexión
                        </button>

                        <button type="submit" 
                            class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-lg shadow-lg shadow-indigo-500/30 transition-all flex items-center gap-2">
                            <i class="ri-save-line"></i> Guardar Configuración
                        </button>
                    </div>
                </form>
            </div>
            
            <script>
            function probarConexionSMTP() {
                const btn = document.querySelector('button[onclick="probarConexionSMTP()"]');
                const originalText = btn.innerHTML;
                const form = btn.closest('form');
                const formData = new FormData(form);
                
                btn.disabled = true;
                btn.innerHTML = '<i class="ri-loader-4-line animate-spin"></i> Probando...';
                
                fetch('smtp_tester.php', {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .then(data => {
                    if (data.status === 'success') {
                         Swal.fire('¡Éxito!', data.msg, 'success');
                    } else {
                         Swal.fire('Error de Conexión', data.msg, 'error');
                    }
                })
                .catch(err => {
                    Swal.fire('Error', 'No se pudo contactar con el script de prueba', 'error');
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                });
            }
            </script>

            <!-- Seguridad -->
            <div class="bg-white rounded-2xl p-6 shadow-lg border border-slate-100">
                <h3 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
                    <i class="ri-shield-keyhole-line text-red-600"></i>
                    Seguridad
                </h3>

                <div class="space-y-3">
                    <a href="index.php?view=usuarios"
                        class="flex items-center justify-between p-3 bg-slate-50 rounded-lg hover:bg-blue-50 transition-colors group">
                        <div class="flex items-center gap-3">
                            <i class="ri-team-line text-xl text-blue-600"></i>
                            <span class="font-medium text-slate-700 group-hover:text-blue-600">Gestión de
                                Usuarios</span>
                        </div>
                        <i class="ri-arrow-right-s-line text-slate-400 group-hover:text-blue-600"></i>
                    </a>

                    <a href="index.php?view=permisos"
                        class="flex items-center justify-between p-3 bg-slate-50 rounded-lg hover:bg-purple-50 transition-colors group">
                        <div class="flex items-center gap-3">
                            <i class="ri-lock-password-line text-xl text-purple-600"></i>
                            <span class="font-medium text-slate-700 group-hover:text-purple-600">Permisos y Roles</span>
                        </div>
                        <i class="ri-arrow-right-s-line text-slate-400 group-hover:text-purple-600"></i>
                    </a>
                </div>
            </div>

            <!-- Personalización de Roles -->
            <div class="bg-white rounded-2xl p-6 shadow-lg border border-slate-100 lg:col-span-2">
                <h3 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
                    <i class="ri-palette-line text-pink-600"></i>
                    Personalización de Etiquetas de Roles
                </h3>

                <?php if (isset($mensaje_accion))
                    echo $mensaje_accion; ?>

                <form method="POST" action="index.php?view=config">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="accion" value="guardar_colores_roles">
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                        <?php
                        $roles_sistema = ['SuperAdmin', 'Admin', 'Tecnico', 'RRHH', 'Usuario', 'Analista'];
                        $colors_avail = array_keys($GLOBALS['colores_badges_map'] ?? ['slate' => '']);

                        foreach ($roles_sistema as $rol):
                            $current_code = $GLOBALS['rol_colors_config'][$rol] ?? 'slate';
                            // Preview class logic
                            $preview_class = $GLOBALS['colores_badges_map'][$current_code] ?? '';
                            ?>
                            <div class="p-3 bg-slate-50/50 rounded-xl border border-slate-100">
                                <label
                                    class="block text-xs font-bold text-slate-500 mb-2 uppercase tracking-wide"><?= $rol ?></label>

                                <div class="mb-2 text-center">
                                    <span
                                        class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset <?= $preview_class ?>">
                                        <?= $rol ?>
                                    </span>
                                </div>

                                <select name="colores[<?= $rol ?>]"
                                    class="w-full text-sm border-slate-200 rounded-lg focus:ring-2 focus:ring-pink-500 outline-none cursor-pointer">
                                    <?php foreach ($colors_avail as $c): ?>
                                        <option value="<?= $c ?>" <?= $current_code == $c ? 'selected' : '' ?>><?= ucfirst($c) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-6 flex justify-end">
                        <button type="submit"
                            class="px-6 py-2 bg-pink-600 hover:bg-pink-700 text-white font-medium rounded-lg shadow-sm transition-colors flex items-center gap-2">
                            <i class="ri-save-3-line"></i> Guardar Colores
                        </button>
                    </div>
                </form>
            </div>

            <!-- Personalización de Actas -->
            <div class="bg-white rounded-2xl p-6 shadow-lg border border-slate-100 lg:col-span-2">
                <h3 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
                    <i class="ri-file-text-line text-teal-600"></i>
                    Personalización de Actas Informativas
                </h3>

                <?php
                // Obtener logos actuales
                $stmt_logos = $pdo->query("SELECT clave, valor FROM configuracion_sistema WHERE clave IN ('logo_mastertec', 'logo_master_suministros', 'logo_centro')");
                $logos = [];
                while ($row = $stmt_logos->fetch(PDO::FETCH_ASSOC)) {
                    $logos[$row['clave']] = $row['valor'];
                }
                ?>

                <form method="POST" action="index.php?view=config" enctype="multipart/form-data" id="form-logos">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="accion" value="guardar_logos_actas">
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Logo MasterTec -->
                        <div class="p-4 bg-slate-50/50 rounded-xl border border-slate-100">
                            <label class="block text-sm font-bold text-slate-700 mb-3">
                                <i class="ri-image-line text-teal-600"></i> Logo MasterTec
                            </label>
                            
                            <?php if (!empty($logos['logo_mastertec'])): ?>
                                <div class="mb-3 p-3 bg-white rounded-lg border border-slate-200">
                                    <img src="<?= htmlspecialchars($logos['logo_mastertec']) ?>" 
                                         alt="Logo MasterTec" 
                                         class="max-h-24 mx-auto object-contain">
                                    <p class="text-xs text-slate-500 text-center mt-2">Logo actual</p>
                                </div>
                            <?php else: ?>
                                <div class="mb-3 p-6 bg-slate-100 rounded-lg border-2 border-dashed border-slate-300 text-center">
                                    <i class="ri-image-add-line text-3xl text-slate-400"></i>
                                    <p class="text-xs text-slate-500 mt-2">Sin logo configurado</p>
                                </div>
                            <?php endif; ?>
                            
                            <input type="file" 
                                   name="logo_mastertec" 
                                   accept="image/png,image/jpeg,image/jpg"
                                   class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-teal-50 file:text-teal-700 hover:file:bg-teal-100 cursor-pointer">
                            <p class="text-xs text-slate-400 mt-2">PNG o JPG, máx. 2MB</p>
                        </div>

                        <!-- Logo Master Suministros -->
                        <div class="p-4 bg-slate-50/50 rounded-xl border border-slate-100">
                            <label class="block text-sm font-bold text-slate-700 mb-3">
                                <i class="ri-image-line text-teal-600"></i> Logo Master Suministros
                            </label>
                            
                            <?php if (!empty($logos['logo_master_suministros'])): ?>
                                <div class="mb-3 p-3 bg-white rounded-lg border border-slate-200">
                                    <img src="<?= htmlspecialchars($logos['logo_master_suministros']) ?>" 
                                         alt="Logo Master Suministros" 
                                         class="max-h-24 mx-auto object-contain">
                                    <p class="text-xs text-slate-500 text-center mt-2">Logo actual</p>
                                </div>
                            <?php else: ?>
                                <div class="mb-3 p-6 bg-slate-100 rounded-lg border-2 border-dashed border-slate-300 text-center">
                                    <i class="ri-image-add-line text-3xl text-slate-400"></i>
                                    <p class="text-xs text-slate-500 mt-2">Sin logo configurado</p>
                                </div>
                            <?php endif; ?>
                            
                            <input type="file" 
                                   name="logo_master_suministros" 
                                   accept="image/png,image/jpeg,image/jpg"
                                   class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-teal-50 file:text-teal-700 hover:file:bg-teal-100 cursor-pointer">
                            <p class="text-xs text-slate-400 mt-2">PNG o JPG, máx. 2MB</p>
                        </div>

                        <!-- Logo Centro -->
                        <div class="p-4 bg-slate-50/50 rounded-xl border border-slate-100">
                            <label class="block text-sm font-bold text-slate-700 mb-3">
                                <i class="ri-image-line text-teal-600"></i> Logo Centro
                            </label>
                            
                            <?php if (!empty($logos['logo_centro'])): ?>
                                <div class="mb-3 p-3 bg-white rounded-lg border border-slate-200">
                                    <img src="<?= htmlspecialchars($logos['logo_centro']) ?>" 
                                         alt="Logo Centro" 
                                         class="max-h-24 mx-auto object-contain">
                                    <p class="text-xs text-slate-500 text-center mt-2">Logo actual</p>
                                </div>
                            <?php else: ?>
                                <div class="mb-3 p-6 bg-slate-100 rounded-lg border-2 border-dashed border-slate-300 text-center">
                                    <i class="ri-image-add-line text-3xl text-slate-400"></i>
                                    <p class="text-xs text-slate-500 mt-2">Sin logo configurado</p>
                                </div>
                            <?php endif; ?>
                            
                            <input type="file" 
                                   name="logo_centro" 
                                   accept="image/png,image/jpeg,image/jpg"
                                   class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-teal-50 file:text-teal-700 hover:file:bg-teal-100 cursor-pointer">
                            <p class="text-xs text-slate-400 mt-2">PNG o JPG, máx. 2MB</p>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <button type="submit"
                            class="px-6 py-2 bg-teal-600 hover:bg-teal-700 text-white font-medium rounded-lg shadow-sm transition-colors flex items-center gap-2">
                            <i class="ri-save-3-line"></i> Guardar Logos
                        </button>
                    </div>
                </form>
            </div>

            <!-- Editor de Contenido de Actas -->
            <div class="bg-white rounded-2xl p-6 shadow-lg border border-slate-100 lg:col-span-2">
                <h3 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
                    <i class="ri-edit-line text-purple-600"></i>
                    Editor de Contenido de Actas
                </h3>

                <?php
                // Obtener todas las configuraciones de contenido
                $stmt_content = $pdo->query("SELECT clave, valor, descripcion FROM configuracion_sistema WHERE clave LIKE 'acta_%' ORDER BY clave");
                $content_configs = [];
                while ($row = $stmt_content->fetch(PDO::FETCH_ASSOC)) {
                    $content_configs[$row['clave']] = $row;
                }
                ?>

                <form method="POST" action="index.php?view=config" id="form-contenido-actas">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="accion" value="guardar_contenido_actas">
                    
                    <!-- Tabs para organizar el contenido -->
                    <div class="mb-6">
                        <div class="border-b border-slate-200">
                            <nav class="-mb-px flex space-x-4" aria-label="Tabs">
                                <button type="button" onclick="switchTab('general')" id="tab-general" class="tab-button active border-b-2 border-purple-600 py-2 px-4 text-sm font-medium text-purple-600">
                                    General
                                </button>
                                <button type="button" onclick="switchTab('ingreso')" id="tab-ingreso" class="tab-button border-b-2 border-transparent py-2 px-4 text-sm font-medium text-slate-500 hover:text-slate-700 hover:border-slate-300">
                                    Acta Ingreso
                                </button>
                                <button type="button" onclick="switchTab('salida')" id="tab-salida" class="tab-button border-b-2 border-transparent py-2 px-4 text-sm font-medium text-slate-500 hover:text-slate-700 hover:border-slate-300">
                                    Acta Salida
                                </button>
                                <button type="button" onclick="switchTab('etiquetas')" id="tab-etiquetas" class="tab-button border-b-2 border-transparent py-2 px-4 text-sm font-medium text-slate-500 hover:text-slate-700 hover:border-slate-300">
                                    Etiquetas
                                </button>
                            </nav>
                        </div>
                    </div>

                    <!-- Tab Content: General -->
                    <div id="content-general" class="tab-content">
                        <h4 class="text-md font-semibold text-slate-700 mb-4">Configuración General</h4>
                        <div class="grid grid-cols-1 gap-4">
                            <?php
                            $general_fields = ['acta_titulo_empresa', 'acta_subtitulo_empresa'];
                            foreach ($general_fields as $field):
                                if (isset($content_configs[$field])):
                            ?>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-2">
                                        <?= htmlspecialchars($content_configs[$field]['descripcion']) ?>
                                    </label>
                                    <input type="text" 
                                           name="<?= $field ?>" 
                                           value="<?= htmlspecialchars($content_configs[$field]['valor']) ?>"
                                           class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                </div>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </div>
                    </div>

                    <!-- Tab Content: Ingreso -->
                    <div id="content-ingreso" class="tab-content hidden">
                        <h4 class="text-md font-semibold text-slate-700 mb-4">Textos del Acta de Ingreso</h4>
                        <div class="grid grid-cols-1 gap-4">
                            <?php
                            $ingreso_fields = [
                                'acta_ingreso_titulo', 'acta_ingreso_descripcion', 'acta_ingreso_nota_pie',
                                'acta_ingreso_seccion_datos', 'acta_ingreso_seccion_correo', 
                                'acta_ingreso_seccion_equipos', 'acta_ingreso_seccion_accesos'
                            ];
                            foreach ($ingreso_fields as $field):
                                if (isset($content_configs[$field])):
                            ?>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-2">
                                        <?= htmlspecialchars($content_configs[$field]['descripcion']) ?>
                                    </label>
                                    <input type="text" 
                                           name="<?= $field ?>" 
                                           value="<?= htmlspecialchars($content_configs[$field]['valor']) ?>"
                                           class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                </div>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </div>
                    </div>

                    <!-- Tab Content: Salida -->
                    <div id="content-salida" class="tab-content hidden">
                        <h4 class="text-md font-semibold text-slate-700 mb-4">Textos del Acta de Salida</h4>
                        <div class="grid grid-cols-1 gap-4">
                            <?php
                            $salida_fields = [
                                'acta_salida_titulo', 'acta_salida_descripcion', 'acta_salida_nota_pie',
                                'acta_salida_seccion_datos', 'acta_salida_seccion_correo', 
                                'acta_salida_seccion_equipos', 'acta_salida_seccion_respaldo'
                            ];
                            foreach ($salida_fields as $field):
                                if (isset($content_configs[$field])):
                            ?>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-2">
                                        <?= htmlspecialchars($content_configs[$field]['descripcion']) ?>
                                    </label>
                                    <input type="text" 
                                           name="<?= $field ?>" 
                                           value="<?= htmlspecialchars($content_configs[$field]['valor']) ?>"
                                           class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                </div>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </div>
                    </div>

                    <!-- Tab Content: Etiquetas -->
                    <div id="content-etiquetas" class="tab-content hidden">
                        <h4 class="text-md font-semibold text-slate-700 mb-4">Etiquetas de Campos</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <?php
                            $label_fields = [
                                'acta_label_colaborador', 'acta_label_cedula', 'acta_label_telefono',
                                'acta_label_cargo', 'acta_label_fecha', 'acta_label_correo',
                                'acta_label_equipos', 'acta_label_observaciones'
                            ];
                            foreach ($label_fields as $field):
                                if (isset($content_configs[$field])):
                            ?>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-2">
                                        <?= htmlspecialchars($content_configs[$field]['descripcion']) ?>
                                    </label>
                                    <input type="text" 
                                           name="<?= $field ?>" 
                                           value="<?= htmlspecialchars($content_configs[$field]['valor']) ?>"
                                           class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                </div>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" onclick="resetForm()" class="px-6 py-2 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300 transition font-medium">
                            <i class="ri-refresh-line"></i> Restaurar
                        </button>
                        <button type="submit" class="px-6 py-2 bg-purple-600 hover:bg-purple-700 text-white font-medium rounded-lg shadow-sm transition flex items-center gap-2">
                            <i class="ri-save-3-line"></i> Guardar Cambios
                        </button>
                    </div>
                </form>

                <script>
                function switchTab(tabName) {
                    // Hide all tab contents
                    document.querySelectorAll('.tab-content').forEach(content => {
                        content.classList.add('hidden');
                    });
                    
                    // Remove active class from all buttons
                    document.querySelectorAll('.tab-button').forEach(button => {
                        button.classList.remove('active', 'border-purple-600', 'text-purple-600');
                        button.classList.add('border-transparent', 'text-slate-500');
                    });
                    
                    // Show selected tab content
                    document.getElementById('content-' + tabName).classList.remove('hidden');
                    
                    // Add active class to selected button
                    const activeButton = document.getElementById('tab-' + tabName);
                    activeButton.classList.add('active', 'border-purple-600', 'text-purple-600');
                    activeButton.classList.remove('border-transparent', 'text-slate-500');
                }

                function resetForm() {
                    if (confirm('¿Estás seguro de que quieres restaurar los valores originales?')) {
                        document.getElementById('form-contenido-actas').reset();
                    }
                }
                </script>
            </div>

            <!-- Mantenimiento -->
            <div class="bg-white rounded-2xl p-6 shadow-lg border border-slate-100 lg:col-span-2">
                <h3 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
                    <i class="ri-tools-line text-emerald-600"></i>
                    Herramientas de Mantenimiento
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <a href="index.php?view=backup"
                        class="group p-4 bg-gradient-to-br from-emerald-50 to-emerald-100 rounded-xl border-2 border-emerald-200 hover:border-emerald-400 transition-all">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="p-2 bg-emerald-200 rounded-lg group-hover:bg-emerald-300 transition-colors">
                                <i class="ri-database-2-line text-2xl text-emerald-700"></i>
                            </div>
                            <h4 class="font-bold text-emerald-900">Backup BD</h4>
                        </div>
                        <p class="text-sm text-emerald-700">Crear respaldo completo</p>
                    </a>

                    <a href="index.php?view=restore"
                        class="group p-4 bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl border-2 border-blue-200 hover:border-blue-400 transition-all">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="p-2 bg-blue-200 rounded-lg group-hover:bg-blue-300 transition-colors">
                                <i class="ri-refresh-line text-2xl text-blue-700"></i>
                            </div>
                            <h4 class="font-bold text-blue-900">Restaurar BD</h4>
                        </div>
                        <p class="text-sm text-blue-700">Recuperar desde backup</p>
                    </a>

                    <a href="index.php?view=restart"
                        class="group p-4 bg-gradient-to-br from-red-50 to-red-100 rounded-xl border-2 border-red-200 hover:border-red-400 transition-all">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="p-2 bg-red-200 rounded-lg group-hover:bg-red-300 transition-colors">
                                <i class="ri-restart-line text-2xl text-red-700"></i>
                            </div>
                            <h4 class="font-bold text-red-900">Reiniciar BD</h4>
                        </div>
                        <p class="text-sm text-red-700">Resetear sistema</p>
                    </a>
                </div>
            </div>

            <!-- Información del Sistema -->
            <div
                class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 shadow-lg lg:col-span-2 text-white">
                <h3 class="text-lg font-bold mb-4 flex items-center gap-2">
                    <i class="ri-information-line"></i>
                    Información del Sistema
                </h3>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-white/10 rounded-lg p-3 backdrop-blur-sm">
                        <div class="text-xs text-slate-300 mb-1">PHP Version</div>
                        <div class="font-bold"><?php echo phpversion(); ?></div>
                    </div>
                    <div class="bg-white/10 rounded-lg p-3 backdrop-blur-sm">
                        <div class="text-xs text-slate-300 mb-1">Servidor</div>
                        <div class="font-bold"><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'N/A'; ?></div>
                    </div>
                    <div class="bg-white/10 rounded-lg p-3 backdrop-blur-sm">
                        <div class="text-xs text-slate-300 mb-1">Usuario Actual</div>
                        <div class="font-bold"><?php echo $rol_usuario; ?></div>
                    </div>
                    <div class="bg-white/10 rounded-lg p-3 backdrop-blur-sm">
                        <div class="text-xs text-slate-300 mb-1">Última Actualización</div>
                        <div class="font-bold"><?php echo date('d/m/Y'); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>