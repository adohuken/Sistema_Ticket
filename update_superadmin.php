<?php
$file = 'c:/xampp/htdocs/Sistema_Ticket/seccion_5_panel_inferior.php';
$content = file_get_contents($file);

// Definir marcador de inicio
$startMarker = '} elseif ($rol_usuario === \'SuperAdmin\') {';

// Definir marcador de fin (el inicio del bloque de Modals comunes)
$endMarker = '<!-- Quick View Modal -->';

$startPos = strpos($content, $startMarker);
$endPos = strpos($content, $endMarker);

if ($startPos === false || $endPos === false) {
    die("No se encontraron los marcadores de inicio ('SuperAdmin') o fin ('Quick View Modal').");
}

// Ajustar $endPos para incluir el cierre de PHP anterior
// Buscamos el "?>" justo antes del comentario del modal
$endPos = strrpos(substr($content, 0, $endPos), '?>');

// Asegurarse de incluir el "?>" en el reemplazo si vamos a reescribirlo, 
// o dejarlo fuera. 
// El nuevo contenido terminará con un bloque PHP cerrado o abierto?
// El contenido original termina con:
// }
// ?>
// Vamos a reemplazar desde el inicio del elseif hasta justo antes del "?>" final del bloque.
// Pero esperate, el bloque original termina en "}" y luego "?>".
// Si yo reemplazo hasta $endPos (que es la posición de "?>"), no incluyo el "?>".
// Entonces mi nuevo contenido NO debe tener "?>" al final si quiero mantener el existente,
// O debo reemplazar INCLUYENDO el "?>" y ponerlo yo.
// Vamos a reemplazar HASTA el "?>" (exclusive) y poner nuestro propio cierre } y } else { ... }

// Mejor estrategia: Reemplazar todo desde el marker de inicio hasta el marker de fin (Quick View Modal),
// y escribir todo el bloque PHP y el cierre y los modals si es necesario (no, los modals no los toco).
// Solo quiero tocar el bloque PHP.

// Reemplazo desde $startPos hasta $endPos + 2 (para incluir el ?>).
// Asi mi string replacemenet tendra el control total del cierre.
$endPos = $endPos + 2; 

$newContent = <<<PHP
} elseif (\$rol_usuario === 'SuperAdmin') {
    // -------------------------------------------------------------------------
    // 5. Dashboard para SuperAdmin (REDISEÑADO)
    // -------------------------------------------------------------------------

    // Obtener estadísticas del sistema
    try {
        \$stmt = \$pdo->query("SELECT COUNT(*) FROM usuarios");
        \$total_usuarios = \$stmt->fetchColumn();

        \$stmt = \$pdo->query("SELECT COUNT(*) FROM tickets");
        \$total_tickets = \$stmt->fetchColumn();

        \$stmt = \$pdo->query("SELECT COUNT(*) FROM formularios_rrhh");
        \$total_rrhh = \$stmt->fetchColumn();

        \$stmt = \$pdo->query("SELECT COUNT(*) FROM categorias");
        \$total_categorias = \$stmt->fetchColumn();

        // Estadísticas por rol
        \$stmt = \$pdo->query("SELECT r.nombre, COUNT(u.id) as total FROM usuarios u JOIN roles r ON u.rol_id = r.id GROUP BY r.nombre");
        \$usuarios_por_rol = \$stmt->fetchAll();

        // Tickets por estado
        \$stmt = \$pdo->query("SELECT estado, COUNT(*) as total FROM tickets GROUP BY estado");
        \$tickets_por_estado = \$stmt->fetchAll();

    } catch (PDOException \$e) {
        \$total_usuarios = 0;
        \$total_tickets = 0;
        \$total_rrhh = 0;
        \$total_categorias = 0;
        \$usuarios_por_rol = [];
        \$tickets_por_estado = [];
    }

    // Calcular tickets por país
    \$tickets_nicaragua = 0;
    \$tickets_honduras = 0;
    if (isset(\$tickets)) {
        foreach (\$tickets as \$t) {
            \$pais = \$t['creador_pais'] ?? '';
            if (stripos(\$pais, 'Nicaragua') !== false) \$tickets_nicaragua++;
            if (stripos(\$pais, 'Honduras') !== false) \$tickets_honduras++;
        }
    }
    ?>

    <div class="flex flex-col lg:flex-row gap-6 h-[calc(100vh-140px)]">

        <!-- COLUMNA IZQUIERDA: Panel Maestro (1/4) -->
        <div class="w-full lg:w-1/4 flex flex-col gap-5 overflow-y-auto custom-scrollbar pb-4">
            
            <!-- Tarjeta Perfil SuperAdmin -->
            <div class="bg-gradient-to-br from-purple-900 to-indigo-900 rounded-2xl p-5 shadow-xl text-white relative overflow-hidden shrink-0">
                <div class="absolute top-0 right-0 -mr-6 -mt-6 w-24 h-24 rounded-full bg-white/10 blur-xl"></div>
                <div class="relative z-10">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-12 h-12 rounded-xl bg-white/10 flex items-center justify-center border border-white/10 shadow-inner">
                            <i class="ri-shield-star-line text-2xl text-amber-300"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-lg leading-tight">SuperAdmin</h3>
                            <p class="text-xs text-indigo-200">Control Total</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2 mt-2">
                        <div class="bg-black/20 rounded-lg p-2 text-center border border-white/5">
                            <span class="block text-xl font-bold text-white"><?= \$total_usuarios ?></span>
                            <span class="text-[10px] text-indigo-200 uppercase tracking-wider">Usuarios</span>
                        </div>
                        <div class="bg-black/20 rounded-lg p-2 text-center border border-white/5">
                            <span class="block text-xl font-bold text-white"><?= \$total_tickets ?></span>
                            <span class="text-[10px] text-indigo-200 uppercase tracking-wider">Tickets</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Menú de Administración -->
            <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm shrink-0">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3 flex items-center gap-2">
                    <i class="ri-tools-line text-purple-500"></i> Gestión del Sistema
                </h3>
                <div class="flex flex-col gap-2">
                    <a href="index.php?view=usuarios" class="flex items-center gap-3 p-2.5 rounded-xl hover:bg-slate-50 transition-colors group">
                        <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center group-hover:bg-blue-100 transition-colors">
                            <i class="ri-user-settings-line"></i>
                        </div>
                        <span class="text-sm font-medium text-slate-700">Usuarios y Roles</span>
                    </a>
                    <a href="index.php?view=backup" class="flex items-center gap-3 p-2.5 rounded-xl hover:bg-slate-50 transition-colors group">
                        <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center group-hover:bg-emerald-100 transition-colors">
                            <i class="ri-database-2-line"></i>
                        </div>
                        <span class="text-sm font-medium text-slate-700">Backup Base de Datos</span>
                    </a>
                    <a href="index.php?view=config" class="flex items-center gap-3 p-2.5 rounded-xl hover:bg-slate-50 transition-colors group">
                        <div class="w-8 h-8 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center group-hover:bg-slate-200 transition-colors">
                            <i class="ri-settings-4-line"></i>
                        </div>
                        <span class="text-sm font-medium text-slate-700">Configuración Global</span>
                    </a>
                    <a href="index.php?view=categorias" class="flex items-center gap-3 p-2.5 rounded-xl hover:bg-slate-50 transition-colors group">
                        <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center group-hover:bg-amber-100 transition-colors">
                            <i class="ri-folder-settings-line"></i>
                        </div>
                        <span class="text-sm font-medium text-slate-700">Categorías de Tickets</span>
                    </a>
                </div>
            </div>
            
            <!-- Accesos Rápidas -->
             <div class="grid grid-cols-2 gap-3 shrink-0">
                <a href="index.php?view=restore" class="p-3 bg-white border border-slate-200 rounded-xl hover:shadow-md transition-all text-center group">
                    <i class="ri-restart-line text-2xl text-blue-500 mb-1 group-hover:scale-110 inline-block transition-transform"></i>
                    <span class="block text-xs font-bold text-slate-600">Restaurar</span>
                </a>
                 <a href="index.php?view=formularios_rrhh" class="p-3 bg-white border border-slate-200 rounded-xl hover:shadow-md transition-all text-center group">
                    <i class="ri-file-list-3-line text-2xl text-pink-500 mb-1 group-hover:scale-110 inline-block transition-transform"></i>
                    <span class="block text-xs font-bold text-slate-600">RRHH Logs</span>
                </a>
            </div>

        </div>

        <!-- COLUMNA DERECHA: Visión Global (3/4) -->
        <div class="w-full lg:w-3/4 bg-slate-100/50 rounded-3xl p-6 border border-slate-200 overflow-hidden flex flex-col gap-6">

             <!-- Header Visual -->
            <div class="flex justify-between items-center shrink-0">
                <div>
                    <h2 class="text-2xl font-bold text-slate-800">Monitor del Sistema</h2>
                    <p class="text-sm text-slate-500">Vista general de operaciones y usuarios</p>
                </div>
                <!-- Mini Stats Pais -->
                <div class="flex gap-4">
                     <div class="flex items-center gap-3 bg-white px-4 py-2 rounded-xl shadow-sm border border-slate-100">
                         <div class="w-8 h-8 rounded-full bg-blue-50 flex items-center justify-center">
                             <span class="fi fi-ni rounded-sm h-4 w-6 shadow-sm"></span> <!-- Flag Placeholder -->
                         </div>
                         <div>
                             <span class="block text-xs text-slate-400 font-bold uppercase">Nicaragua</span>
                             <span class="block text-lg font-black text-slate-800 leading-none"><?= \$tickets_nicaragua ?></span>
                         </div>
                     </div>
                     <div class="flex items-center gap-3 bg-white px-4 py-2 rounded-xl shadow-sm border border-slate-100">
                         <div class="w-8 h-8 rounded-full bg-blue-50 flex items-center justify-center">
                             <span class="fi fi-hn rounded-sm h-4 w-6 shadow-sm"></span> <!-- Flag Placeholder -->
                         </div>
                         <div>
                             <span class="block text-xs text-slate-400 font-bold uppercase">Honduras</span>
                             <span class="block text-lg font-black text-slate-800 leading-none"><?= \$tickets_honduras ?></span>
                         </div>
                     </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 h-full min-h-0">
                
                <!-- Usuarios por Rol (Card) -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 flex flex-col">
                    <h3 class="font-bold text-slate-800 mb-4 flex items-center gap-2">
                        <i class="ri-group-line text-purple-500"></i> Distribución de Usuarios
                    </h3>
                    <div class="flex-1 overflow-y-auto custom-scrollbar pr-2 space-y-3">
                        <?php foreach (\$usuarios_por_rol as \$rol): 
                             \$icon = 'ri-user-line';
                             \$bg = 'bg-slate-100 text-slate-600';
                             if (\$rol['nombre'] === 'Admin') { \$icon = 'ri-shield-star-line'; \$bg = 'bg-blue-100 text-blue-600'; }
                             if (\$rol['nombre'] === 'SuperAdmin') { \$icon = 'ri-vip-crown-line'; \$bg = 'bg-purple-100 text-purple-600'; }
                             if (\$rol['nombre'] === 'Tecnico') { \$icon = 'ri-tools-line'; \$bg = 'bg-orange-100 text-orange-600'; }
                             if (\$rol['nombre'] === 'RRHH') { \$icon = 'ri-briefcase-line'; \$bg = 'bg-emerald-100 text-emerald-600'; }
                        ?>
                        <div class="flex items-center justify-between p-3 rounded-xl border border-slate-100 hover:border-purple-200 hover:bg-purple-50/50 transition-colors">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg <?= \$bg ?> flex items-center justify-center">
                                    <i class="<?= \$icon ?> text-xl"></i>
                                </div>
                                <span class="font-medium text-slate-700"><?= \$rol['nombre'] ?></span>
                            </div>
                            <span class="text-xl font-bold text-slate-800"><?= \$rol['total'] ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Tickets por Estado (Card) -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 flex flex-col">
                    <h3 class="font-bold text-slate-800 mb-4 flex items-center gap-2">
                        <i class="ri-pie-chart-2-line text-blue-500"></i> Estado de Tickets
                    </h3>
                    <div class="flex-1 overflow-y-auto custom-scrollbar pr-2 space-y-4">
                        <?php 
                        \$max_t = 0;
                        foreach(\$tickets_por_estado as \$e) \$max_t = max(\$max_t, \$e['total']);
                        
                        foreach (\$tickets_por_estado as \$estado): 
                            \$pct = \$max_t > 0 ? (\$estado['total'] / \$max_t) * 100 : 0;
                            \$color = 'bg-slate-500';
                            if (\$estado['estado'] === 'Pendiente') \$color = 'bg-yellow-500';
                            if (\$estado['estado'] === 'Asignado') \$color = 'bg-blue-500';
                            if (\$estado['estado'] === 'En Proceso') \$color = 'bg-purple-500';
                            if (\$estado['estado'] === 'Completo') \$color = 'bg-emerald-500';
                        ?>
                        <div>
                            <div class="flex justify-between text-xs mb-1">
                                <span class="font-semibold text-slate-700"><?= \$estado['estado'] ?></span>
                                <span class="font-bold text-slate-900"><?= \$estado['total'] ?></span>
                            </div>
                            <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                                <div class="h-full <?= \$color ?> rounded-full" style="width: <?= \$pct ?>%"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            </div>
        </div>

    </div>

<?php
} else {
    // 6. Default (Otros roles o sin rol asignado)
    ?>
    <div class="flex flex-col items-center justify-center h-[calc(100vh-140px)] text-center">
        <div class="bg-slate-50 p-8 rounded-full mb-6 border border-slate-200 shadow-inner">
            <i class="ri-shield-keyhole-line text-6xl text-slate-300"></i>
        </div>
        <h2 class="text-2xl font-bold text-slate-800 mb-2">Acceso Limitado</h2>
        <p class="text-slate-500 max-w-md mx-auto">Tu rol de usuario no tiene un dashboard asignado en este momento. Contacta al administrador si crees que esto es un error.</p>
        <div class="mt-8 flex gap-4">
             <a href="logout.php" class="px-6 py-2 bg-slate-800 text-white rounded-xl hover:bg-black transition-colors">Cerrar Sesión</a>
        </div>
    </div>
    <?php
}
?>

PHP;

// Reemplazar
$finalContent = substr($content, 0, $startPos) . $newContent . substr($content, $endPos);

file_put_contents($file, $finalContent);
echo "SuperAdmin dashboard actualizado correctamente.";
?>
