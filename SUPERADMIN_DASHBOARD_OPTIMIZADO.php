    <?php
    // -------------------------------------------------------------------------
    // 5. Dashboard para SuperAdmin
    // -------------------------------------------------------------------------
// -------------------------------------------------------------------------
if ($rol_usuario === 'SuperAdmin') {
    // Obtener estadísticas del sistema
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios");
        $total_usuarios = $stmt->fetchColumn();

        $stmt = $pdo->query("SELECT COUNT(*) FROM tickets");
        $total_tickets = $stmt->fetchColumn();

        $stmt = $pdo->query("SELECT COUNT(*) FROM formularios_rrhh");
        $total_rrhh = $stmt->fetchColumn();

        $stmt = $pdo->query("SELECT COUNT(*) FROM categorias");
        $total_categorias = $stmt->fetchColumn();

        // Estadísticas por rol
        $stmt = $pdo->query("SELECT r.nombre, COUNT(u.id) as total FROM usuarios u JOIN roles r ON u.rol_id = r.id GROUP BY r.nombre");
        $usuarios_por_rol = $stmt->fetchAll();

        // Tickets por estado
        $stmt = $pdo->query("SELECT estado, COUNT(*) as total FROM tickets GROUP BY estado");
        $tickets_por_estado = $stmt->fetchAll();

    } catch (PDOException $e) {
        $total_usuarios = 0;
        $total_tickets = 0;
        $total_rrhh = 0;
        $total_categorias = 0;
        $usuarios_por_rol = [];
        $tickets_por_estado = [];
    }
    ?>

    <div class="flex items-center gap-2 mb-3">
        <div class="w-14 h-14 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg flex items-center justify-center">
            <i class="ri-shield-star-line text-2xl text-white"></i>
        </div>
        <h2 class="text-3xl font-bold text-gray-900">Panel de SuperAdministrador</h2>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-3">
        <a href="index.php?view=usuarios" class="block transform hover:scale-105 transition">
            <div class="bg-gradient-to-br from-purple-500 to-purple-600 text-white p-3 rounded-xl shadow-lg h-full cursor-pointer">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-purple-100 text-sm font-medium">Total Usuarios</p>
                        <p class="text-3xl font-bold"><?= $total_usuarios ?></p>
                    </div>
                    <div class="bg-white bg-opacity-20 rounded-full flex-shrink-0" style="width: 48px; height: 48px; display: flex; align-items: center; justify-content: center;">
                        <i class="ri-user-line text-2xl"></i>
                    </div>
                </div>
                <p class="text-purple-100 text-xs mt-2 flex items-center gap-1">
                    <i class="ri-arrow-right-line"></i> Gestionar usuarios
                </p>
            </div>
        </a>

        <a href="index.php?view=listados" class="block transform hover:scale-105 transition">
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 text-white p-3 rounded-xl shadow-lg h-full cursor-pointer">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-blue-100 text-sm font-medium">Total Tickets</p>
                        <p class="text-3xl font-bold"><?= $total_tickets ?></p>
                    </div>
                    <div class="bg-white bg-opacity-20 rounded-full flex-shrink-0" style="width: 48px; height: 48px; display: flex; align-items: center; justify-content: center;">
                        <i class="ri-ticket-line text-2xl"></i>
                    </div>
                </div>
                <p class="text-blue-100 text-xs mt-2 flex items-center gap-1">
                    <i class="ri-arrow-right-line"></i> Ver todos los tickets
                </p>
            </div>
        </a>

        <a href="index.php?view=formularios_rrhh" class="block transform hover:scale-105 transition">
            <div class="bg-gradient-to-br from-pink-500 to-pink-600 text-white p-3 rounded-xl shadow-lg h-full cursor-pointer">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-pink-100 text-sm font-medium">Formularios RRHH</p>
                        <p class="text-3xl font-bold"><?= $total_rrhh ?></p>
                    </div>
                    <div class="bg-white bg-opacity-20 rounded-full flex-shrink-0" style="width: 48px; height: 48px; display: flex; align-items: center; justify-content: center;">
                        <i class="ri-user-star-line text-2xl"></i>
                    </div>
                </div>
                <p class="text-pink-100 text-xs mt-2 flex items-center gap-1">
                    <i class="ri-arrow-right-line"></i> Ver formularios RRHH
                </p>
            </div>
        </a>

        <a href="index.php?view=categorias" class="block transform hover:scale-105 transition">
            <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 text-white p-3 rounded-xl shadow-lg h-full cursor-pointer">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-emerald-100 text-sm font-medium">Categorías</p>
                        <p class="text-3xl font-bold"><?= $total_categorias ?></p>
                    </div>
                    <div class="bg-white bg-opacity-20 rounded-full flex-shrink-0" style="width: 48px; height: 48px; display: flex; align-items: center; justify-content: center;">
                        <i class="ri-folder-line text-2xl"></i>
                    </div>
                </div>
                <p class="text-emerald-100 text-xs mt-2 flex items-center gap-1">
                    <i class="ri-arrow-right-line"></i> Gestionar categorías
                </p>
            </div>
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-3">
        <!-- Herramientas de Administración -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="px-4 py-2 border-b border-slate-100 bg-gradient-to-r from-slate-50 to-slate-100">
                <h3 class="font-bold text-slate-800 flex items-center gap-2 text-sm">
                    <i class="ri-tools-line text-slate-600"></i> Herramientas de Administración
                </h3>
            </div>
            <div class="p-3">
                <div class="space-y-2">
                    <a href="backup_bd.php" class="flex items-center gap-2 p-2 border-2 border-emerald-200 rounded-lg hover:bg-emerald-50 transition group">
                        <div class="bg-emerald-100 p-2 rounded-lg group-hover:bg-emerald-200 transition">
                            <i class="ri-database-2-line text-xl text-emerald-600"></i>
                        </div>
                        <div class="flex-1">
                            <h4 class="font-bold text-slate-800 text-sm">Backup BD</h4>
                            <p class="text-xs text-slate-500">Respaldar base de datos</p>
                        </div>
                    </a>

                    <a href="restaurar_bd.php" class="flex items-center gap-2 p-2 border-2 border-blue-200 rounded-lg hover:bg-blue-50 transition group">
                        <div class="bg-blue-100 p-2 rounded-lg group-hover:bg-blue-200 transition">
                            <i class="ri-refresh-line text-xl text-blue-600"></i>
                        </div>
                        <div class="flex-1">
                            <h4 class="font-bold text-slate-800 text-sm">Restaurar BD</h4>
                            <p class="text-xs text-slate-500">Restaurar desde backup</p>
                        </div>
                    </a>

                    <a href="reiniciar_bd.php" class="flex items-center gap-2 p-2 border-2 border-red-200 rounded-lg hover:bg-red-50 transition group">
                        <div class="bg-red-100 p-2 rounded-lg group-hover:bg-red-200 transition">
                            <i class="ri-restart-line text-xl text-red-600"></i>
                        </div>
                        <div class="flex-1">
                            <h4 class="font-bold text-slate-800 text-sm">Reiniciar BD</h4>
                            <p class="text-xs text-slate-500">Resetear sistema</p>
                        </div>
                    </a>

                    <a href="index.php?view=usuarios" class="flex items-center gap-2 p-2 border-2 border-purple-200 rounded-lg hover:bg-purple-50 transition group">
                        <div class="bg-purple-100 p-2 rounded-lg group-hover:bg-purple-200 transition">
                            <i class="ri-user-settings-line text-xl text-purple-600"></i>
                        </div>
                        <div class="flex-1">
                            <h4 class="font-bold text-slate-800 text-sm">Configuración</h4>
                            <p class="text-xs text-slate-500">Gestionar sistema</p>
                        </div>
                    </a>

                    <a href="index.php?view=listados" class="flex items-center gap-2 p-2 border-2 border-slate-200 rounded-lg hover:bg-slate-50 transition group">
                        <div class="bg-slate-100 p-2 rounded-lg group-hover:bg-slate-200 transition">
                            <i class="ri-file-list-line text-xl text-slate-600"></i>
                        </div>
                        <div class="flex-1">
                            <h4 class="font-bold text-slate-800 text-sm">Todos los Tickets</h4>
                            <p class="text-xs text-slate-500">Ver listado completo</p>
                        </div>
                    </a>

                    <a href="index.php?view=categorias" class="flex items-center gap-2 p-2 border-2 border-amber-200 rounded-lg hover:bg-amber-50 transition group">
                        <div class="bg-amber-100 p-2 rounded-lg group-hover:bg-amber-200 transition">
                            <i class="ri-folder-settings-line text-xl text-amber-600"></i>
                        </div>
                        <div class="flex-1">
                            <h4 class="font-bold text-slate-800 text-sm">Categorías</h4>
                            <p class="text-xs text-slate-500">Gestionar categorías</p>
                        </div>
                    </a>
                </div>
            </div>
        </div>

        <!-- Usuarios por Rol -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="px-4 py-2 border-b border-slate-100 bg-gradient-to-r from-purple-50 to-purple-100">
                <h3 class="font-bold text-slate-800 flex items-center gap-2 text-sm">
                    <i class="ri-team-line text-purple-600"></i> Usuarios por Rol
                </h3>
            </div>
            <div class="p-3">
                <?php if (empty($usuarios_por_rol)): ?>
                    <p class="text-slate-400 text-center py-4 text-sm">No hay datos disponibles</p>
                <?php else: ?>
                    <div class="space-y-2">
                        <?php foreach ($usuarios_por_rol as $rol): ?>
                            <div class="flex items-center justify-between p-2 bg-slate-50 rounded-lg">
                                <span class="font-medium text-slate-700 text-sm"><?= htmlspecialchars($rol['nombre']) ?></span>
                                <span class="px-2.5 py-1 bg-purple-100 text-purple-700 rounded-full text-xs font-bold">
                                    <?= $rol['total'] ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tickets por Estado -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="px-4 py-2 border-b border-slate-100 bg-gradient-to-r from-blue-50 to-blue-100">
                <h3 class="font-bold text-slate-800 flex items-center gap-2 text-sm">
                    <i class="ri-pie-chart-line text-blue-600"></i> Tickets por Estado
                </h3>
            </div>
            <div class="p-3">
                <?php if (empty($tickets_por_estado)): ?>
                    <p class="text-slate-400 text-center py-4 text-sm">No hay datos disponibles</p>
                <?php else: ?>
                    <div class="space-y-2">
                        <?php
                        $estado_colors = [
                            'Pendiente' => 'bg-yellow-100 text-yellow-700',
                            'Asignado' => 'bg-blue-100 text-blue-700',
                            'Completo' => 'bg-emerald-100 text-emerald-700'
                        ];
                        foreach ($tickets_por_estado as $estado):
                            $color = $estado_colors[$estado['estado']] ?? 'bg-slate-100 text-slate-700';
                            ?>
                            <div class="flex items-center justify-between p-2 bg-slate-50 rounded-lg">
                                <span class="font-medium text-slate-700 text-sm"><?= htmlspecialchars($estado['estado']) ?></span>
                                <span class="px-2.5 py-1 <?= $color ?> rounded-full text-xs font-bold">
                                    <?= $estado['total'] ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php

}
?>