<?php
/**
 * seccion_3_formulario.php - Módulo de formularios del sistema
 */

// 1. Formulario Crear/Editar Ticket (Delegado a archivos específicos)
if (!empty($mostrar_solo_ticket)) {
    if (!empty($modo_edicion)) {
        include 'seccion_3_editar_ticket.php';
    } else {
        include 'seccion_3_crear_ticket.php';
    }
}

// 2. Formulario Crear Usuario (Solo Admin)
// Estilos CSS Inline para el formulario de usuario
$estilo_contenedor_user = "
    width: 100%;
    max-width: 800px;
    margin: 40px auto;
    background-color: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    position: relative;
    z-index: 10;
    font-family: 'Inter', sans-serif;
    overflow: hidden;
";

$estilo_header_user = "
    padding: 24px 32px;
    background-color: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
";

$estilo_titulo_user = "
    font-size: 1.25rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0;
";

$estilo_form_user = "padding: 32px;";
$estilo_grupo_user = "margin-bottom: 24px;";
$estilo_label_user = "display: block; font-size: 0.875rem; font-weight: 600; color: #475569; margin-bottom: 8px;";
$estilo_input_user = "width: 100%; padding: 12px 16px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 1rem; color: #334155; outline: none; background-color: #f8fafc;";
$estilo_botones_user = "display: flex; justify-content: flex-end; gap: 12px; margin-top: 32px; padding-top: 24px; border-top: 1px solid #f1f5f9;";
$estilo_btn_cancelar_user = "padding: 10px 20px; background-color: #f1f5f9; color: #475569; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;";
$estilo_btn_guardar_user = "padding: 10px 24px; background-color: #10b981; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;";

if (!empty($mostrar_solo_usuario)) {
    ?>
    <div style="<?php echo $estilo_contenedor_user; ?>">
        <div style="<?php echo $estilo_header_user; ?>">
            <div>
                <h2 style="<?php echo $estilo_titulo_user; ?>">Registrar Nuevo Usuario</h2>
                <p style="margin: 4px 0 0; color: #64748b; font-size: 0.875rem;">Añadir un nuevo miembro al sistema</p>
            </div>
            <div
                style="width: 40px; height: 40px; background: #ecfdf5; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #059669;">
                <i class="ri-user-add-line" style="font-size: 24px;"></i>
            </div>
        </div>

        <form action="index.php?view=usuarios" method="POST" style="<?php echo $estilo_form_user; ?>" autocomplete="off">
            <?php echo campo_csrf(); ?>
            <input type="hidden" name="accion" value="crear_usuario">

            <div style="<?php echo $estilo_grupo_user; ?>">
                <label style="<?php echo $estilo_label_user; ?>">Nombre Completo</label>
                <input type="text" name="nombre_usuario" style="<?php echo $estilo_input_user; ?>"
                    placeholder="Ej: Juan Pérez" required autocomplete="off">
            </div>

            <div style="<?php echo $estilo_grupo_user; ?>">
                <label style="<?php echo $estilo_label_user; ?>">Correo Electrónico</label>
                <input type="email" name="email_usuario" style="<?php echo $estilo_input_user; ?>"
                    placeholder="correo@empresa.com" required autocomplete="new-password">
            </div>

            <div style="<?php echo $estilo_grupo_user; ?>">
                <label style="<?php echo $estilo_label_user; ?>">Contraseña</label>
                <input type="password" name="password_usuario" style="<?php echo $estilo_input_user; ?>"
                    placeholder="••••••••" required autocomplete="new-password">
            </div>

            <div style="<?php echo $estilo_grupo_user; ?>">
                <label style="<?php echo $estilo_label_user; ?>">Rol del Usuario</label>
                <select name="rol_usuario" id="rol_usuario_select" style="<?php echo $estilo_input_user; ?>"
                    onchange="togglePermisosRRHH()">
                    <?php
                    if (isset($roles) && is_array($roles)) {
                        foreach ($roles as $r) {
                            echo "<option value='{$r['id']}'>{$r['nombre']}</option>";
                        }
                    }
                    ?>
                </select>
            </div>

            <!-- Contenedor Permisos RRHH (Oculto por defecto) -->
            <?php
            // Cargar datos para selectores si no existen (Reubicado para que esté disponible aquí)
            if (!isset($empresas_form)) {
                try {
                    $stmt = $GLOBALS['pdo']->query("SELECT * FROM empresas WHERE activa=1 ORDER BY nombre");
                    $empresas_form = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $stmt = $GLOBALS['pdo']->query("SELECT * FROM sucursales WHERE activa=1 ORDER BY nombre");
                    $sucursales_form = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } catch (Exception $e) {
                    $empresas_form = [];
                    $sucursales_form = [];
                }
            }
            ?>
            <div id="contenedor_permisos_rrhh"
                style="<?php echo $estilo_grupo_user; ?>; display:none; border: 1px solid #cbd5e1; padding: 16px; border-radius: 8px; background: #f8fafc;">
                <label style="<?php echo $estilo_label_user; ?>; color: #0f172a;">Permisos de Visualización (Solo
                    RRHH)</label>
                <p style="font-size: 0.8rem; color: #64748b; margin-bottom: 12px; margin-top:0;">Seleccione las sucursales a
                    las que tendrá acceso:</p>

                <div style="max-height: 250px; overflow-y: auto; padding-right: 8px;">
                    <?php foreach ($empresas_form as $e): ?>
                        <div
                            style="margin-bottom: 16px; background: white; padding: 10px; border-radius: 6px; border: 1px solid #e2e8f0;">
                            <strong
                                style="display:block; font-size: 0.9rem; color: #1e293b; margin-bottom: 8px; border-bottom: 1px solid #f1f5f9; padding-bottom: 4px;">
                                <i class="ri-building-line"></i> <?php echo htmlspecialchars($e['nombre']); ?>
                            </strong>
                            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 8px;">
                                <?php
                                $sucs_empresa = array_filter($sucursales_form, function ($s) use ($e) {
                                    return $s['empresa_id'] == $e['id'];
                                });
                                if (empty($sucs_empresa)): ?>
                                    <span style="font-size: 0.8rem; color: #94a3b8; grid-column: 1/-1;">Sin sucursales</span>
                                <?php else: ?>
                                    <?php foreach ($sucs_empresa as $s): ?>
                                        <label
                                            style="display: flex; align-items: center; gap: 8px; font-size: 0.85rem; color: #475569; cursor: pointer;">
                                            <input type="checkbox" name="permisos_sucursal[]" value="<?php echo $s['id']; ?>"
                                                style="width: 16px; height: 16px; accent-color: #2563eb;">
                                            <?php echo htmlspecialchars($s['nombre']); ?>
                                        </label>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <script>
                function togglePermisosRRHH() {
                    const select = document.getElementById('rol_usuario_select');
                    if (select.selectedIndex === -1) return; // Protección si no hay opciones

                    const rolNombre = select.options[select.selectedIndex].text;
                    const contenedor = document.getElementById('contenedor_permisos_rrhh');

                    if (rolNombre.toUpperCase().includes('RRHH')) {
                        contenedor.style.display = 'block';
                    } else {
                        contenedor.style.display = 'none';
                    }
                }
                // Ejecutar al cargar por si hay valor preseleccionado
                document.addEventListener('DOMContentLoaded', togglePermisosRRHH);
            </script>

            <!-- --- Nuevos Campos: Vinculación Empresarial --- -->
            <?php
            // Cargar datos para selectores si no existen
            if (!isset($empresas_form)) {
                try {
                    $stmt = $GLOBALS['pdo']->query("SELECT * FROM empresas WHERE activa=1 ORDER BY nombre");
                    $empresas_form = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $stmt = $GLOBALS['pdo']->query("SELECT * FROM sucursales WHERE activa=1 ORDER BY nombre");
                    $sucursales_form = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } catch (Exception $e) {
                    $empresas_form = [];
                    $sucursales_form = [];
                }
            }
            ?>

            <div style="<?php echo $estilo_grupo_user; ?>">
                <label style="<?php echo $estilo_label_user; ?>">Empresa Asignada (Para Reportes)</label>
                <select name="empresa_id" id="user_empresa_id" style="<?php echo $estilo_input_user; ?>"
                    onchange="filtrarSucursalesUser()">
                    <option value="">-- Ninguna / Global --</option>
                    <?php foreach ($empresas_form as $e): ?>
                        <option value="<?php echo $e['id']; ?>"><?php echo htmlspecialchars($e['nombre']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="<?php echo $estilo_grupo_user; ?>">
                <label style="<?php echo $estilo_label_user; ?>">Sucursal</label>
                <select name="sucursal_id" id="user_sucursal_id" style="<?php echo $estilo_input_user; ?>">
                    <option value="">-- Seleccione Empresa Primero --</option>
                    <?php foreach ($sucursales_form as $s): ?>
                        <option value="<?php echo $s['id']; ?>" data-empresa="<?php echo $s['empresa_id']; ?>"
                            style="display:none;">
                            <?php echo htmlspecialchars($s['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <script>
                function filtrarSucursalesUser() {
                    const empId = document.getElementById('user_empresa_id').value;
                    const sucSelect = document.getElementById('user_sucursal_id');
                    const options = sucSelect.querySelectorAll('option');

                    let visibleCount = 0;
                    options.forEach(opt => {
                        if (opt.value === "") {
                            opt.text = empId ? "-- Seleccione Sucursal --" : "-- Seleccione Empresa Primero --";
                            return;
                        }

                        if (empId && opt.getAttribute('data-empresa') == empId) {
                            opt.style.display = 'block';
                            visibleCount++;
                        } else {
                            opt.style.display = 'none';
                        }
                    });
                    sucSelect.value = ""; // Reset selección
                }
            </script>

            <div style="<?php echo $estilo_botones_user; ?>">
                <button type="button" style="<?php echo $estilo_btn_cancelar_user; ?>"
                    onclick="history.back()">Cancelar</button>
                <button type="submit" style="<?php echo $estilo_btn_guardar_user; ?>">
                    <i class="ri-save-line" style="margin-right: 8px;"></i> Guardar Usuario
                </button>
            </div>
        </form>
    </div>
    <?php
}
?>