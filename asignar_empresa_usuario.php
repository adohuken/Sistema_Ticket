<?php
/**
 * asignar_empresa_usuario.php
 * Permite asignar una empresa a un usuario con rol RRHH.
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

        $stmt = $pdo->prepare("UPDATE usuarios SET empresa_id = ? WHERE id = ?");
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
    SELECT u.id, u.nombre_completo, u.email, u.empresa_id, e.nombre AS empresa_nombre
    FROM usuarios u
    LEFT JOIN roles r ON u.rol_id = r.id
    LEFT JOIN empresas e ON u.empresa_id = e.id
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
    <title>Asignar Empresa · RRHH</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <style>
        body { font-family: 'Inter', system-ui, sans-serif; }
        .select-styled {
            -webkit-appearance: none;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2394a3b8'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 1.2em;
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-100 via-slate-50 to-blue-50 flex items-center justify-center p-6">

    <div class="w-full max-w-xl">

        <!-- Card principal -->
        <div class="bg-white rounded-3xl shadow-2xl shadow-slate-200/60 border border-slate-100 overflow-hidden">

            <!-- Header con gradiente -->
            <div class="bg-gradient-to-r from-teal-600 to-cyan-500 px-8 pt-8 pb-10 relative overflow-hidden">
                <div class="absolute -top-6 -right-6 w-32 h-32 bg-white/10 rounded-full blur-2xl"></div>
                <div class="absolute bottom-0 left-1/3 w-24 h-24 bg-white/10 rounded-full blur-xl"></div>
                <div class="relative flex items-center gap-4">
                    <div class="w-14 h-14 bg-white/20 backdrop-blur rounded-2xl flex items-center justify-center text-2xl text-white shadow-lg">
                        <i class="ri-building-4-line"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-white leading-tight">Asignar Empresa</h1>
                        <p class="text-teal-100 text-sm mt-0.5">Vincula un usuario RRHH con su empresa</p>
                    </div>
                </div>
            </div>

            <!-- Contenido -->
            <div class="px-8 py-7">

                <!-- Alerta resultado -->
                <?php if ($mensaje):
                    if ($tipo_msg === 'success') {
                        $alertClass = 'bg-emerald-50 border-emerald-200 text-emerald-800';
                        $iconClass  = 'ri-checkbox-circle-fill text-emerald-500';
                    } else {
                        $alertClass = 'bg-red-50 border-red-200 text-red-800';
                        $iconClass  = 'ri-close-circle-fill text-red-500';
                    }
                ?>
                    <div class="flex items-start gap-3 p-4 rounded-2xl border <?= $alertClass ?> mb-6">
                        <i class="<?= $iconClass ?> text-xl mt-0.5 shrink-0"></i>
                        <p class="text-sm font-medium"><?= htmlspecialchars($mensaje) ?></p>
                    </div>
                <?php endif; ?>

                <!-- Formulario -->
                <form method="POST" class="space-y-5">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

                    <!-- Usuario RRHH -->
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">
                            <i class="ri-user-line mr-1"></i>Usuario RRHH
                        </label>
                        <div class="relative">
                            <i class="ri-user-3-line absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-lg pointer-events-none"></i>
                            <select name="usuario_id" required
                                class="select-styled w-full pl-11 pr-10 py-3.5 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:border-teal-400 focus:ring-2 focus:ring-teal-100 outline-none transition-all text-slate-700 font-medium text-sm">
                                <option value="">-- Seleccionar usuario --</option>
                                <?php foreach ($usuarios_rrhh as $u): ?>
                                    <option value="<?= $u['id'] ?>" <?= ($usuario_preseleccionado == $u['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($u['nombre_completo']) ?> (<?= htmlspecialchars($u['email']) ?>)
                                        <?php if ($u['empresa_nombre']): ?> — <?= htmlspecialchars($u['empresa_nombre']) ?><?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Empresa -->
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">
                            <i class="ri-building-line mr-1"></i>Empresa a Asignar
                        </label>
                        <div class="relative">
                            <i class="ri-building-4-line absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-lg pointer-events-none"></i>
                            <select name="empresa_id" required
                                class="select-styled w-full pl-11 pr-10 py-3.5 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:border-teal-400 focus:ring-2 focus:ring-teal-100 outline-none transition-all text-slate-700 font-medium text-sm">
                                <option value="">-- Seleccionar empresa --</option>
                                <?php foreach ($empresas as $emp): ?>
                                    <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Botón guardar -->
                    <button type="submit"
                        class="w-full mt-2 bg-gradient-to-r from-teal-600 to-cyan-500 hover:from-teal-700 hover:to-cyan-600 text-white font-bold py-3.5 rounded-xl shadow-lg shadow-teal-500/25 transition-all duration-200 flex items-center justify-center gap-2 hover:-translate-y-0.5 active:translate-y-0">
                        <i class="ri-save-line text-lg"></i>
                        Asignar Empresa
                    </button>
                </form>

                <!-- Separador -->
                <div class="flex items-center gap-3 my-6">
                    <div class="flex-1 h-px bg-slate-100"></div>
                    <span class="text-xs text-slate-400 font-medium uppercase tracking-wide">Asignaciones actuales</span>
                    <div class="flex-1 h-px bg-slate-100"></div>
                </div>

                <!-- Lista usuarios RRHH -->
                <div class="space-y-2">
                    <?php foreach ($usuarios_rrhh as $u): ?>
                        <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-100 hover:border-slate-200 transition-colors">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-sm shrink-0">
                                    <?= strtoupper(substr($u['nombre_completo'], 0, 1)) ?>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-slate-700 leading-tight"><?= htmlspecialchars($u['nombre_completo']) ?></p>
                                    <p class="text-xs text-slate-400"><?= htmlspecialchars($u['email']) ?></p>
                                </div>
                            </div>
                            <?php if ($u['empresa_nombre']): ?>
                                <span class="px-3 py-1 bg-teal-100 text-teal-700 rounded-lg text-xs font-bold whitespace-nowrap">
                                    <i class="ri-building-line mr-1"></i><?= htmlspecialchars($u['empresa_nombre']) ?>
                                </span>
                            <?php else: ?>
                                <span class="px-3 py-1 bg-amber-100 text-amber-600 rounded-lg text-xs font-medium whitespace-nowrap">Sin asignar</span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($usuarios_rrhh)): ?>
                        <p class="text-center text-sm text-slate-400 py-4">No hay usuarios RRHH registrados.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Footer de navegación -->
            <div class="px-8 py-5 bg-slate-50 border-t border-slate-100 flex gap-3">
                <a href="index.php?view=usuarios"
                    class="flex-1 text-center px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-600 font-semibold text-sm hover:bg-slate-100 transition-colors flex items-center justify-center gap-2">
                    <i class="ri-team-line"></i> Gestión de Usuarios
                </a>
                <a href="index.php"
                    class="flex-1 text-center px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm transition-colors flex items-center justify-center gap-2 shadow-md shadow-blue-500/20">
                    <i class="ri-dashboard-line"></i> Dashboard
                </a>
            </div>

        </div>

        <!-- Nota informativa -->
        <p class="text-center text-xs text-slate-400 mt-4">
            <i class="ri-information-line"></i>
            Las actas generadas mostrarán el logo de la empresa asignada al usuario
        </p>

    </div>
</body>
</html>