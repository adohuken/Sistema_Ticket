<?php
/**
 * login.php - Página de inicio de sesión
 */
session_start();

// Si ya está autenticado, redirigir al dashboard
if (isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/security_utils.php';

// Inicializar token CSRF
generar_csrf_token();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar CSRF
    if (!isset($_POST['csrf_token']) || !validar_csrf_token($_POST['csrf_token'])) {
        $error = 'Error de seguridad: Token inválido. Recargue la página.';
    } else {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $error = 'Por favor, complete todos los campos.';
        } else {
            try {
                $stmt = $pdo->prepare("SELECT u.id, u.nombre_completo, u.email, u.password, u.empresa_id, u.sucursal_id, r.nombre as rol 
                                       FROM usuarios u 
                                       JOIN roles r ON u.rol_id = r.id 
                                       WHERE u.email = ?");
                $stmt->execute([$email]);
                $usuario = $stmt->fetch();

                if ($usuario && password_verify($password, $usuario['password'])) {
                    // Regenerar token y session ID logueo exitoso (Seguridad)
                    session_regenerate_id(true);
                    regenerar_csrf_token();

                    // Login exitoso
                    $_SESSION['usuario_id'] = $usuario['id'];
                    $_SESSION['usuario_nombre'] = $usuario['nombre_completo'];
                    $_SESSION['usuario_email'] = $usuario['email'];
                    $_SESSION['usuario_rol'] = $usuario['rol'];

                    // Guardar contexto de empresa/sucursal para aislamiento
                    $_SESSION['usuario_empresa_id'] = $usuario['empresa_id'];
                    $_SESSION['usuario_sucursal_id'] = $usuario['sucursal_id'];

                    // Obtener permisos de sucursales adicionales (Multi-Empresa)
                    try {
                        $stmt_acc = $pdo->prepare("SELECT sucursal_id FROM usuarios_accesos WHERE usuario_id = ?");
                        $stmt_acc->execute([$usuario['id']]);
                        $_SESSION['usuario_sucursales_permitidas'] = $stmt_acc->fetchAll(PDO::FETCH_COLUMN);
                    } catch (Exception $e) {
                        $_SESSION['usuario_sucursales_permitidas'] = [];
                    }

                    header('Location: index.php');
                    exit;
                } else {
                    $error = 'Credenciales incorrectas. Por favor, intente de nuevo.';
                }
            } catch (PDOException $e) {
                $error = 'Error en el sistema. Por favor, contacte al administrador.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - TicketSys</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', sans-serif;
        }

        .glass {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .input-focus:focus {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>

<body>
    <div class="w-full max-w-md px-4">
        <!-- Logo y Título -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-white rounded-full shadow-2xl mb-4">
                <i class="ri-ticket-2-line text-4xl text-purple-600"></i>
            </div>
            <h1 class="text-4xl font-bold text-white mb-2">TicketSys</h1>
            <p class="text-purple-100">Sistema de Gestión de Tickets</p>
        </div>

        <!-- Formulario de Login -->
        <div class="glass rounded-2xl shadow-2xl p-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">Iniciar Sesión</h2>

            <?php if ($error): ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-lg">
                    <div class="flex items-center">
                        <i class="ri-error-warning-line text-red-500 text-xl mr-3"></i>
                        <p class="text-red-700 text-sm"><?= htmlspecialchars($error) ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php" class="space-y-6">
                <!-- CSRF Token -->
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <!-- Email -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="ri-mail-line mr-1"></i> Correo Electrónico
                    </label>
                    <input type="email" name="email"
                        class="input-focus w-full px-4 py-3 rounded-xl border border-gray-300 focus:border-purple-500 focus:ring-4 focus:ring-purple-500/20 transition-all outline-none"
                        placeholder="correo@ejemplo.com" required autocomplete="email">
                </div>

                <!-- Contraseña -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="ri-lock-password-line mr-1"></i> Contraseña
                    </label>
                    <input type="password" name="password"
                        class="input-focus w-full px-4 py-3 rounded-xl border border-gray-300 focus:border-purple-500 focus:ring-4 focus:ring-purple-500/20 transition-all outline-none"
                        placeholder="••••••••" required autocomplete="current-password">
                </div>

                <!-- Botón de Login -->
                <button type="submit"
                    class="w-full bg-gradient-to-r from-purple-600 to-indigo-600 text-white font-bold py-3 px-6 rounded-xl shadow-lg hover:shadow-xl hover:-translate-y-0.5 transition-all flex items-center justify-center gap-2">
                    <i class="ri-login-box-line"></i>
                    Iniciar Sesión
                </button>
            </form>


        </div>

        <!-- Footer -->
        <p class="text-center text-purple-100 text-sm mt-6">
            © 2025 TicketSys. Todos los derechos reservados.
        </p>
    </div>
</body>

</html>