import re

# Leer el archivo
with open(r'c:\xampp\htdocs\Sistema_Ticket\index.php', 'r', encoding='utf-8') as f:
    content = f.read()

# Código a insertar
casos_nuevos = """
    case 'listados':
        $mostrar_listado_general = true;
        include __DIR__ . '/seccion_4_listados.php';
        break;

    case 'nuevo_ingreso':
        if ($rol_usuario === 'RRHH' || $rol_usuario === 'SuperAdmin') {
            include __DIR__ . '/seccion_rrhh_ingreso.php';
        } else {
            echo "<div class='p-6 text-red-500'>Acceso denegado.</div>";
        }
        break;

    case 'nueva_salida':
        if ($rol_usuario === 'RRHH' || $rol_usuario === 'SuperAdmin') {
            include __DIR__ . '/seccion_rrhh_salida.php';
        } else {
            echo "<div class='p-6 text-red-500'>Acceso denegado.</div>";
        }
        break;

    case 'config':
        if ($rol_usuario === 'Admin' || $rol_usuario === 'SuperAdmin') {
            include __DIR__ . '/seccion_configuracion.php';
        } else {
            echo "<div class='p-6 text-red-500'>Acceso denegado.</div>";
        }
        break;

    case 'permisos':
        if ($rol_usuario === 'SuperAdmin') {
            include __DIR__ . '/seccion_gestion_permisos.php';
        } else {
            echo "<div class='p-6 text-red-500'>Acceso denegado.</div>";
        }
        break;

    case 'categorias':
        if ($rol_usuario === 'Admin' || $rol_usuario === 'SuperAdmin') {
            include __DIR__ . '/seccion_categorias.php';
        } else {
            echo "<div class='p-6 text-red-500'>Acceso denegado.</div>";
        }
        break;

    case 'backup':
        if ($rol_usuario === 'SuperAdmin') {
            include __DIR__ . '/backup_bd.php';
        }
        break;

    case 'restore':
        if ($rol_usuario === 'SuperAdmin') {
            include __DIR__ . '/restaurar_bd.php';
        }
        break;

    case 'restart':
        if ($rol_usuario === 'SuperAdmin') {
            include __DIR__ . '/reiniciar_bd.php';
        }
        break;

    default:
        echo "<div class='p-6'>Vista no encontrada.</div>";
        break;
}
"""

# Buscar el patrón: después del último break; antes de include footer
pattern = r"(case\s+'formularios_rrhh':.*?break;)\s*\n\s*\n\s*(include\s+__DIR__\s*\.\s*'/footer\.php';)"

# Reemplazar
nuevo_content = re.sub(
    pattern,
    r"\1" + casos_nuevos + r"\n\n\2",
    content,
    flags=re.DOTALL
)

# Si no encontró el patrón, intentar otro
if nuevo_content == content:
    # Buscar simplemente antes del footer
    pattern2 = r"(\s*)(include\s+__DIR__\s*\.\s*'/footer\.php';)"
    # Verificar si ya existe el caso 'permisos'
    if "case 'permisos':" not in content:
        nuevo_content = re.sub(
            pattern2,
            casos_nuevos + r"\n\n\1\2",
            content
        )

# Guardar
with open(r'c:\xampp\htdocs\Sistema_Ticket\index.php', 'w', encoding='utf-8') as f:
    f.write(nuevo_content)

print("Archivo actualizado exitosamente")
