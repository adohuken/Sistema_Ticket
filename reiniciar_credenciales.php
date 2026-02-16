<?php
/**
 * reiniciar_credenciales.php - Script para reiniciar las credenciales de usuarios
 */

require_once __DIR__ . '/conexion.php';

try {
    echo "=== REINICIANDO CREDENCIALES DE USUARIOS ===\n\n";

    // Verificar roles existentes
    echo "Verificando roles en la base de datos...\n";
    $stmt = $pdo->query("SELECT id, nombre FROM roles");
    $roles = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    echo "Roles encontrados:\n";
    foreach ($roles as $id => $nombre) {
        echo "  - ID: $id, Nombre: $nombre\n";
    }
    echo "\n";

    if (empty($roles)) {
        echo "❌ No hay roles en la base de datos. Ejecuta setup_db.php primero.\n";
        exit(1);
    }

    // Eliminar asignaciones primero (por foreign key)
    $pdo->exec("DELETE FROM asignaciones");
    echo "✓ Asignaciones eliminadas\n";

    // Eliminar tickets (por foreign key con usuarios)
    $pdo->exec("DELETE FROM tickets");
    echo "✓ Tickets eliminados\n";

    // Eliminar todos los usuarios existentes
    $pdo->exec("DELETE FROM usuarios");
    echo "✓ Usuarios anteriores eliminados\n\n";

    // Usuarios de prueba con credenciales reiniciadas
    $usuarios_prueba = [
        [
            'nombre' => 'Administrador del Sistema',
            'email' => 'admin@ticketsys.com',
            'password' => 'admin123',
            'rol' => 'Admin'
        ],
        [
            'nombre' => 'Juan Técnico',
            'email' => 'tecnico@ticketsys.com',
            'password' => 'tecnico123',
            'rol' => 'Tecnico'
        ],
        [
            'nombre' => 'María Gerente',
            'email' => 'gerente@ticketsys.com',
            'password' => 'gerente123',
            'rol' => 'Gerencia'
        ],
        [
            'nombre' => 'Carlos SuperAdmin',
            'email' => 'superadmin@ticketsys.com',
            'password' => 'super123',
            'rol' => 'SuperAdmin'
        ],
        [
            'nombre' => 'Ana RRHH',
            'email' => 'rrhh@ticketsys.com',
            'password' => 'rrhh123',
            'rol' => 'RRHH'
        ]
    ];

    $stmt = $pdo->prepare("INSERT INTO usuarios (nombre_completo, email, password, rol_id) VALUES (?, ?, ?, ?)");

    echo "Creando usuarios:\n";
    echo str_repeat("-", 100) . "\n";
    printf("%-30s | %-35s | %-15s | %s\n", "Nombre", "Email", "Password", "Rol");
    echo str_repeat("-", 100) . "\n";

    $usuarios_creados = 0;
    foreach ($usuarios_prueba as $usuario) {
        $rol_id = $roles[$usuario['rol']] ?? null;
        if ($rol_id) {
            try {
                $stmt->execute([
                    $usuario['nombre'],
                    $usuario['email'],
                    password_hash($usuario['password'], PASSWORD_DEFAULT),
                    $rol_id
                ]);
                printf(
                    "✓ %-30s | %-35s | %-15s | %s\n",
                    $usuario['nombre'],
                    $usuario['email'],
                    $usuario['password'],
                    $usuario['rol']
                );
                $usuarios_creados++;
            } catch (PDOException $e) {
                echo "❌ Error al crear {$usuario['email']}: " . $e->getMessage() . "\n";
            }
        } else {
            echo "❌ Rol '{$usuario['rol']}' no encontrado en la base de datos\n";
        }
    }

    echo str_repeat("-", 100) . "\n";
    echo "\n¡$usuarios_creados usuarios creados exitosamente!\n\n";

    if ($usuarios_creados > 0) {
        echo "=== CREDENCIALES DE ACCESO ===\n\n";
        echo "Admin:\n";
        echo "  Email: admin@ticketsys.com\n";
        echo "  Password: admin123\n\n";

        echo "Técnico:\n";
        echo "  Email: tecnico@ticketsys.com\n";
        echo "  Password: tecnico123\n\n";

        echo "Gerencia:\n";
        echo "  Email: gerente@ticketsys.com\n";
        echo "  Password: gerente123\n\n";

        echo "SuperAdmin:\n";
        echo "  Email: superadmin@ticketsys.com\n";
        echo "  Password: super123\n\n";

        echo "RRHH:\n";
        echo "  Email: rrhh@ticketsys.com\n";
        echo "  Password: rrhh123\n\n";
    }

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>