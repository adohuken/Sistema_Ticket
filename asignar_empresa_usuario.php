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

        <?php
        require_once __DIR__ . '/conexion.php';
        require_once __DIR__ . '/security_utils.php';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                if (!isset($_POST['csrf_token']) || !validar_csrf_token($_POST['csrf_token'])) {
                    throw new Exception("Error de seguridad: Token inválido");
                }

                $usuario_id = $_POST['usuario_id'];
                $empresa = $_POST['empresa'];

                $stmt = $pdo->prepare("UPDATE usuarios SET empresa_asignada = ? WHERE id = ?");
                $stmt->execute([$empresa, $usuario_id]);

                echo "<div class='bg-green-100 text-green-800 p-4 rounded-lg mb-6'>";
                echo "✅ Empresa asignada correctamente al usuario.";
                echo "</div>";

            } catch (Exception $e) {
                echo "<div class='bg-red-100 text-red-800 p-4 rounded-lg mb-6'>";
                echo "❌ Error: " . $e->getMessage();
                echo "</div>";
            }
        }

        // Obtener usuarios RRHH
        $stmt = $pdo->query("
            SELECT u.id, u.nombre_completo, u.email, u.empresa_asignada, r.nombre as rol_nombre
            FROM usuarios u
            LEFT JOIN roles r ON u.rol_id = r.id
            WHERE r.nombre = 'RRHH'
        ");
        $usuarios_rrhh = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Pre-seleccionar usuario si viene por URL
        $usuario_preseleccionado = isset($_GET['usuario_id']) ? (int) $_GET['usuario_id'] : null;
        $empresa_actual = null;

        if ($usuario_preseleccionado) {
            $stmt_actual = $pdo->prepare("SELECT empresa_asignada FROM usuarios WHERE id = ?");
            $stmt_actual->execute([$usuario_preseleccionado]);
            $empresa_actual = $stmt_actual->fetchColumn();
        }

        // Mapa de nombres legibles para empresas
        $nombres_empresas = [
            'mastertec' => 'MasterTec',
            'suministros' => 'Master Suministros',
            'centro' => 'Centro'
        ];
        ?>

        <form method="POST" class="space-y-6">
            <?= campo_csrf() ?>
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-2">Usuario RRHH</label>
                <select name="usuario_id" required
                    class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Seleccionar Usuario --</option>
                    <?php foreach ($usuarios_rrhh as $u): ?>
                        <option value="<?= $u['id'] ?>" <?= ($usuario_preseleccionado == $u['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($u['nombre_completo']) ?> (<?= htmlspecialchars($u['email']) ?>)
                            <?php if ($u['empresa_asignada']): ?>
                                - Actual:
                                <?= htmlspecialchars($nombres_empresas[$u['empresa_asignada']] ?? $u['empresa_asignada']) ?>
                            <?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php if ($empresa_actual): ?>
                <div class="p-4 bg-amber-50 border border-amber-200 rounded-lg">
                    <p class="text-sm text-amber-800">
                        <i class="ri-information-line"></i>
                        <strong>Empresa actual:</strong>
                        <?= htmlspecialchars($nombres_empresas[$empresa_actual] ?? $empresa_actual) ?>
                    </p>
                </div>
            <?php endif; ?>

            <div>
                <label class="block text-sm font-bold text-slate-700 mb-2">Empresa a Asignar</label>
                <select name="empresa" required
                    class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-teal-500">
                    <option value="">-- Seleccionar Empresa --</option>
                    <option value="mastertec" <?= ($empresa_actual === 'mastertec') ? 'selected' : '' ?>>MasterTec</option>
                    <option value="suministros" <?= ($empresa_actual === 'suministros') ? 'selected' : '' ?>>Master
                        Suministros</option>
                    <option value="centro" <?= ($empresa_actual === 'centro') ? 'selected' : '' ?>>Centro</option>
                </select>
            </div>

            <button type="submit"
                class="w-full px-6 py-3 bg-teal-600 text-white font-bold rounded-lg hover:bg-teal-700 transition">
                Asignar Empresa
            </button>
        </form>

        <div class="mt-8 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <h3 class="font-bold text-blue-900 mb-2">📋 Usuarios RRHH Actuales</h3>
            <div class="space-y-2">
                <?php foreach ($usuarios_rrhh as $u): ?>
                    <div class="text-sm text-blue-800">
                        <strong><?= htmlspecialchars($u['nombre_completo']) ?></strong>
                        <?php if ($u['empresa_asignada']): ?>
                            <span class="ml-2 px-2 py-1 bg-teal-100 text-teal-700 rounded text-xs font-bold">
                                <?= htmlspecialchars($nombres_empresas[$u['empresa_asignada']] ?? $u['empresa_asignada']) ?>
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