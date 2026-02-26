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
    <link rel="icon"
        href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🎫</text></svg>">
    <title>Iniciar Sesión - TicketSys</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <style>
        body {
            /* Fondo base oscuro premium */
            background-color: #0f111a;
            background-image:
                radial-gradient(ellipse at top right, rgba(99, 102, 241, 0.15) 0%, transparent 40%),
                radial-gradient(ellipse at bottom left, rgba(168, 85, 247, 0.15) 0%, transparent 40%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', sans-serif;
            overflow: hidden;
            position: relative;
        }

        /* ----- Grid en Movimiento ----- */
        .animated-grid {
            position: absolute;
            width: 200vw;
            height: 200vh;
            top: -50vh;
            left: -50vw;
            background-image:
                linear-gradient(rgba(255, 255, 255, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
            background-size: 50px 50px;
            transform: perspective(600px) rotateX(60deg) translateY(-100px) translateZ(-200px);
            animation: grid-move 20s linear infinite;
            z-index: -2;
            pointer-events: none;
        }

        @keyframes grid-move {
            0% {
                transform: perspective(600px) rotateX(60deg) translateY(0) translateZ(-200px);
            }

            100% {
                transform: perspective(600px) rotateX(60deg) translateY(50px) translateZ(-200px);
            }
        }

        /* ----- Estrellas Fugaces (Shooting Stars) ----- */
        .shooting-star {
            position: absolute;
            background: linear-gradient(90deg, #fff, transparent);
            height: 2px;
            width: 100px;
            border-radius: 50%;
            animation: shooting 4s linear infinite;
            z-index: -1;
            opacity: 0;
            pointer-events: none;
        }

        .star-1 {
            top: 10%;
            left: -100px;
            animation-delay: 0s;
        }

        .star-2 {
            top: 30%;
            left: -100px;
            animation-delay: 1.5s;
            width: 140px;
        }

        .star-3 {
            top: 60%;
            left: -100px;
            animation-delay: 3s;
            width: 80px;
        }

        .star-4 {
            top: 80%;
            left: -100px;
            animation-delay: 2.2s;
            width: 120px;
        }

        @keyframes shooting {
            0% {
                transform: translateX(0) scale(1) rotate(20deg);
                opacity: 1;
            }

            30% {
                transform: translateX(100vw) scale(0) rotate(20deg);
                opacity: 0.5;
            }

            100% {
                transform: translateX(100vw) scale(0) rotate(20deg);
                opacity: 0;
            }
        }

        /* ----- Estilos Originales Preservados ----- */
        .glass {
            background: rgba(25, 28, 40, 0.7);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        /* Ajustes de texto para modo oscuro */
        .glass h2 {
            color: #f8fafc;
        }

        .glass label {
            color: #cbd5e1;
        }

        .glass input {
            background: rgba(15, 23, 42, 0.6);
            border-color: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .glass input::placeholder {
            color: #64748b;
        }

        .input-focus:focus {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
            border-color: #8b5cf6;
        }

        @keyframes float {
            0% {
                transform: translateY(0px);
            }

            50% {
                transform: translateY(-12px);
                box-shadow: 0 25px 30px -10px rgba(0, 0, 0, 0.3);
                border-color: rgba(255, 255, 255, 0.2);
            }

            100% {
                transform: translateY(0px);
            }
        }

        .animate-float {
            animation: float 4s ease-in-out infinite;
            background: rgba(30, 41, 59, 0.9);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        @keyframes fade-in-up {
            0% {
                opacity: 0;
                transform: translateY(30px);
            }

            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in-up {
            opacity: 0;
            animation: fade-in-up 0.8s ease-out forwards;
        }

        .delay-100 {
            animation-delay: 0.1s;
        }

        .delay-150 {
            animation-delay: 0.15s;
        }

        .delay-200 {
            animation-delay: 0.2s;
        }
    </style>
</head>

<body>
    <!-- Fondo Moderno Animado -->
    <div class="animated-grid"></div>
    <div class="shooting-star star-1"></div>
    <div class="shooting-star star-2"></div>
    <div class="shooting-star star-3"></div>
    <div class="shooting-star star-4"></div>

    <div class="w-full max-w-md px-4 relative z-10">
        <!-- Logo y Título -->
        <div class="text-center mb-8 animate-fade-in-up">
            <div
                class="inline-flex items-center justify-center w-20 h-20 bg-white rounded-full shadow-lg mb-4 animate-float">
                <i
                    class="ri-ticket-2-line text-4xl text-transparent bg-clip-text bg-gradient-to-r from-purple-600 to-indigo-600"></i>
            </div>
            <h1 class="text-4xl font-bold text-white mb-2 tracking-tight">TicketSys</h1>
            <p class="text-purple-100 font-medium">Sistema de Gestión de Tickets</p>
        </div>

        <!-- Formulario de Login -->
        <div class="glass rounded-2xl shadow-2xl p-8 animate-fade-in-up delay-100">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">Iniciar Sesión</h2>

            <?php if ($error): ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-lg animate-fade-in-up">
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
                <div class="animate-fade-in-up delay-100">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="ri-mail-line mr-1 text-purple-500"></i> Correo Electrónico
                    </label>
                    <input type="email" name="email"
                        class="input-focus w-full px-4 py-3 rounded-xl border border-gray-300 focus:border-purple-500 focus:ring-4 focus:ring-purple-500/20 transition-all outline-none"
                        placeholder="correo@ejemplo.com" required autocomplete="email">
                </div>

                <!-- Contraseña -->
                <div class="animate-fade-in-up delay-150">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="ri-lock-password-line mr-1 text-purple-500"></i> Contraseña
                    </label>
                    <input type="password" name="password"
                        class="input-focus w-full px-4 py-3 rounded-xl border border-gray-300 focus:border-purple-500 focus:ring-4 focus:ring-purple-500/20 transition-all outline-none"
                        placeholder="••••••••" required autocomplete="current-password">
                </div>

                <!-- Botón de Login -->
                <div class="animate-fade-in-up delay-200">
                    <button type="submit"
                        class="w-full bg-gradient-to-r from-purple-600 to-indigo-600 text-white font-bold py-3 px-6 rounded-xl shadow-lg hover:shadow-indigo-500/40 hover:-translate-y-1 transition-all flex items-center justify-center gap-2 group">
                        <i class="ri-login-box-line group-hover:translate-x-1 transition-transform"></i>
                        Iniciar Sesión
                    </button>
                </div>
            </form>
        </div>

        <!-- Footer -->
        <p class="text-center text-purple-100/80 text-sm mt-8 animate-fade-in-up delay-200">
            © 2025 TicketSys. Todos los derechos reservados.
        </p>
    </div>
</body>

</html>