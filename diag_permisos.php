<?php
/**
 * diag_permisos.php - Diagnostico tabla modulos y permisos
 * ELIMINAR DESPUES DE USAR
 */
session_start();
require_once __DIR__ . '/conexion.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Diagnóstico Permisos</title>
    <style>
        body {
            font-family: monospace;
            padding: 20px;
            background: #f1f5f9;
        }

        h2 {
            color: #1e3a5f;
        }

        h3 {
            color: #334155;
            margin-top: 20px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            margin-bottom: 20px;
        }

        th,
        td {
            border: 1px solid #cbd5e1;
            padding: 6px 12px;
            text-align: left;
        }

        th {
            background: #e2e8f0;
        }

        tr:hover {
            background: #f8fafc;
        }

        .ok {
            color: green;
            font-weight: bold;
        }

        .warn {
            color: #b45309;
            font-weight: bold;
        }

        .err {
            color: red;
            font-weight: bold;
        }

        .box {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 16px;
        }
    </style>
</head>

<body>
    <h2>🔐 Diagnóstico del Sistema de Permisos</h2>
    <p>Servidor: <b>
            <?= $_SERVER['SERVER_NAME'] ?>
        </b> &mdash;
        <?= date('Y-m-d H:i:s') ?>
    </p>

    <?php
    // ---- 1. Modulos en BD ----
    echo '<div class="box"><h3>📦 Tabla modulos</h3>';
    $modulos_locales = [
        14 => 'dashboard',
        15 => 'crear_ticket',
        16 => 'mis_tickets',
        17 => 'gestion_usuarios',
        18 => 'asignar_tickets',
        19 => 'mis_tareas',
        20 => 'reportes',
        21 => 'rrhh_altas',
        22 => 'rrhh_bajas',
        23 => 'rrhh_historial',
        24 => 'backup_bd',
        25 => 'restaurar_bd',
        26 => 'reiniciar_bd',
        27 => 'gestion_permisos',
        28 => 'configuracion',
        29 => 'categorias',
        30 => 'seguimiento_tickets',
        31 => 'gestion_personal',
        32 => 'gestion_sucursales',
        33 => 'rrhh_inventario',
        34 => 'rrhh_registro_equipo',
        35 => 'rrhh_asignacion_equipos',
        37 => 'estadisticas_globales',
        38 => 'personal_importar',
        39 => 'historial_tecnico',
        40 => 'registros_365',
        41 => 'mantenimiento_equipos',
        42 => 'visualizacion_it',
        43 => 'cargos',
        44 => 'notificaciones'
    ];

    $rows = $pdo->query("SELECT id, nombre, etiqueta FROM modulos ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
    $prod_by_id = [];
    foreach ($rows as $r)
        $prod_by_id[$r['id']] = $r;

    echo '<table><tr><th>ID</th><th>Nombre (local)</th><th>En Producción</th><th>Nombre Prod</th></tr>';
    $faltantes = [];
    foreach ($modulos_locales as $id => $nombre) {
        $existe = isset($prod_by_id[$id]);
        $match = $existe && $prod_by_id[$id]['nombre'] === $nombre;
        $estado = $match ? '<span class="ok">✓ OK</span>' :
            ($existe ? '<span class="warn">⚠ ID existe, nombre diferente</span>' :
                '<span class="err">✗ FALTA</span>');
        if (!$match)
            $faltantes[] = $id;
        $prod_nombre = $existe ? $prod_by_id[$id]['nombre'] : '---';
        echo "<tr><td>$id</td><td>$nombre</td><td>$estado</td><td>$prod_nombre</td></tr>";
    }
    echo '</table>';
    if (empty($faltantes))
        echo '<p class="ok">✓ Todos los módulos están sincronizados.</p>';
    echo '</div>';

    // ---- 2. permisos_roles ----
    echo '<div class="box"><h3>🛡️ Permisos por Rol</h3>';
    $roles_perms = $pdo->query("SELECT r.nombre as rol, COUNT(pr.modulo_id) as total FROM roles r LEFT JOIN permisos_roles pr ON r.id = pr.rol_id GROUP BY r.id ORDER BY r.id")->fetchAll();
    echo '<table><tr><th>Rol</th><th>Módulos asignados</th><th>Estado</th></tr>';
    foreach ($roles_perms as $rp) {
        $ok = $rp['total'] > 0;
        echo "<tr><td>{$rp['rol']}</td><td>{$rp['total']}</td><td>" .
            ($ok ? '<span class="ok">✓ Tiene permisos</span>' : '<span class="warn">⚠ Sin permisos</span>') .
            "</td></tr>";
    }
    echo '</table></div>';

    // ---- 3. Usuarios con permisos extra ----
    echo '<div class="box"><h3>👤 Permisos por Usuario (extra)</h3>';
    $users_perms = $pdo->query("SELECT u.nombre_completo, r.nombre as rol, COUNT(pu.modulo_id) as permisos_extra FROM usuarios u JOIN roles r ON u.rol_id = r.id LEFT JOIN permisos_usuarios pu ON u.id = pu.usuario_id GROUP BY u.id ORDER BY u.nombre_completo")->fetchAll();
    echo '<table><tr><th>Usuario</th><th>Rol</th><th>Permisos extra</th></tr>';
    foreach ($users_perms as $up) {
        echo "<tr><td>{$up['nombre_completo']}</td><td>{$up['rol']}</td><td>" .
            ($up['permisos_extra'] > 0 ? '<span class="ok">+' . $up['permisos_extra'] . '</span>' : 'ninguno') .
            "</td></tr>";
    }
    echo '</table></div>';

    // ---- 4. SQL para arreglar modulos faltantes ----
    if (!empty($faltantes)) {
        echo '<div class="box"><h3>🔧 SQL para agregar módulos faltantes</h3><pre style="background:#1e293b;color:#a5f3fc;padding:16px;border-radius:8px;overflow:auto;">';
        foreach ($faltantes as $id) {
            $nombre = $modulos_locales[$id];
            echo htmlspecialchars("INSERT IGNORE INTO modulos (id, nombre, etiqueta, descripcion) VALUES ($id, '$nombre', '$nombre', '');") . "\n";
        }
        echo '</pre></div>';
    }
    ?>
    <p style="color:#94a3b8;font-size:12px;">ELIMINA ESTE ARCHIVO DESPUÉS DE USARLO</p>
</body>

</html>