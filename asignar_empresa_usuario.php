<?php
/**
 * asignar_empresa_usuario.php
 * Permite asignar una empresa a un usuario con rol RRHH.
 * Acceso directo (fuera de index.php) — maneja su propia sesión y CSRF.
 */
session_start();
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/security_utils.php';

// Generar token CSRF si no existe en sesión
if (empty($_SESSION['csrf_token'])) {
    generar_csrf_token();
}

$mensaje  = '';
$tipo_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $token_post    = $_POST['csrf_token']    ?? '';
        $token_session = $_SESSION['csrf_token'] ?? '';

        if (empty($token_post) || $token_post !== $token_session) {
            throw new Exception("Error de seguridad: token CSRF inválido. Recarga la página.");
        }

        $usuario_id = (int) ($_POST['usuario_id'] ?? 0);
        $empresa_id = !empty($_POST['empresa_id']) ? (int) $_POST['empresa_id'] : null;

        if (!$usuario_id) {
            throw new Exception("Selecciona un usuario válido.");
        }

        $stmt = $pdo->prepare("UPDATE usuarios SET empresa_asignada = ? WHERE id = ?");
        $stmt->execute([$empresa_id, $usuario_id]);

        $tipo_msg = 'success';
        $mensaje  = '✅ Empresa asignada correctamente al usuario.';

    } catch (Exception $e) {
        $tipo_msg = 'error';
        $mensaje  = '❌ Error: ' . $e->getMessage();
    }
}

// Obtener usuarios RRHH con su empresa actual
$stmt_rrhh = $pdo->query("
    SELECT u.id, u.nombre_completo, u.email, u.empresa_asignada, e.nombre AS empresa_nombre
    FROM usuarios u
    LEFT JOIN roles r ON u.rol_id = r.id
    LEFT JOIN empresas e ON u.empresa_asignada = e.id
    WHERE r.nombre = 'RRHH'
    ORDER BY u.nombre_completo
");
$usuarios_rrhh = $stmt_rrhh->fetchAll(PDO::FETCH_ASSOC);

// Cargar empresas dinámicamente desde la BD
$stmt_emp = $pdo->query("SELECT id, nombre FROM empresas ORDER BY nombre");
$empresas = $stmt_emp->fetchAll(PDO::FETCH_ASSOC);

// Pre-seleccionar usuario si viene por URL
$usuario_preseleccionado = isset($_GET['usuario_id']) ? (int) $_GET['usuario_id'] : null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asignar Empresa a Usuario RRHH</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 p-8">
    <div class="max-w-2xl mx-auto bg-white rounded-2xl p-8 shadow-lg">
        <h1 class="text-2xl font-bold text-slate-800 mb-6">🏢 Asignar Empresa a Usuario RRHH</h1>

        <?php if ($mensaje):
            $color = $tipo_msg === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
        ?>
            <div class="<?= $color ?> p-4 rounded-lg mb-6"><?= htmlspecialchars($mensaje) ?></div>
        <?php endif; ?>

        <form method="POST" class="space-y-6">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

            <!-- Usuario RRHH -->
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-2">Usuario RRHH</label>
                <select name="usuario_id" required
                    class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Seleccionar Usuario --</option>
                    <?php foreach ($usuarios_rrhh as $u): ?>
                        <option value="<?= $u['id'] ?>" <?= ($usuario_preseleccionado == $u['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($u['nombre_completo']) ?> (<?= htmlspecialchars($u['email']) ?>)
                            <?php if ($u['empresa_nombre']): ?> — Actual: <?= htmlspecialchars($u['empresa_nombre']) ?><?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Empresa -->
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-2">Empresa a Asignar</label>
                <select name="empresa_id" required
                    class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-teal-500">
                    <option value="">-- Seleccionar Empresa --</option>
                    <?php foreach ($empresas as $emp): ?>
                        <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit"
                class="w-full px-6 py-3 bg-teal-600 text-white font-bold rounded-lg hover:bg-teal-700 transition">
                Asignar Empresa
            </button>
        </form>

        <!-- Lista de usuarios RRHH actuales -->
        <div class="mt-8 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <h3 class="font-bold text-blue-900 mb-2">📋 Usuarios RRHH Actuales</h3>
            <div class="space-y-2">
                <?php foreach ($usuarios_rrhh as $u): ?>
                    <div class="text-sm text-blue-800">
                        <strong><?= htmlspecialchars($u['nombre_completo']) ?></strong>
                        <?php if ($u['empresa_nombre']): ?>
                            <span class="ml-2 px-2 py-1 bg-teal-100 text-teal-700 rounded text-xs font-bold">
                                <?= htmlspecialchars($u['empresa_nombre']) ?>
                            </span>
                        <?php else: ?>
                            <span class="ml-2 px-2 py-1 bg-amber-100 text-amber-700 rounded text-xs">
                                Sin empresa asignada
                            </span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="mt-6 flex gap-4">
            <a href="index.php?view=usuarios"
                class="flex-1 text-center px-4 py-2 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300 transition font-medium">
                Ir a Gestión de Usuarios
            </a>
            <a href="index.php"
                class="flex-1 text-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium">
                Ir al Dashboard
            </a>
        </div>
    </div>
</body>
</html>