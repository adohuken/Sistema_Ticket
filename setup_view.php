<?php
// setup_view.php - Script para restaurar la vista faltante en Producción
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'conexion.php';

try {
    echo "<h1>Restaurando vista_personal_completo...</h1>";

    $sql = "
    CREATE OR REPLACE VIEW `vista_personal_completo` AS 
    select 
    `p`.`id` AS `id`,
    `p`.`empresa_id` AS `empresa_id`,
    `p`.`sucursal_id` AS `sucursal_id`,
    `p`.`codigo_empleado` AS `codigo_empleado`,
    `p`.`nombres` AS `nombres`,
    `p`.`apellidos` AS `apellidos`,
    `p`.`cedula` AS `cedula`,
    `p`.`fecha_nacimiento` AS `fecha_nacimiento`,
    `p`.`genero` AS `genero`,
    `p`.`estado_civil` AS `estado_civil`,
    `p`.`telefono` AS `telefono`,
    `p`.`telefono_emergencia` AS `telefono_emergencia`,
    `p`.`email` AS `email`,
    `p`.`direccion` AS `direccion`,
    `p`.`ciudad` AS `ciudad`,
    `p`.`pais` AS `pais`,
    `p`.`cargo` AS `cargo`,
    `p`.`departamento` AS `departamento`,
    `p`.`fecha_ingreso` AS `fecha_ingreso`,
    `p`.`fecha_salida` AS `fecha_salida`,
    `p`.`tipo_contrato` AS `tipo_contrato`,
    `p`.`salario` AS `salario`,
    `p`.`usuario_sistema_id` AS `usuario_sistema_id`,
    `p`.`estado` AS `estado`,
    `p`.`foto_url` AS `foto_url`,
    `p`.`notas` AS `notas`,
    `p`.`creado_por` AS `creado_por`,
    `p`.`fecha_creacion` AS `fecha_creacion`,
    `p`.`modificado_por` AS `modificado_por`,
    `p`.`fecha_modificacion` AS `fecha_modificacion`,
    `e`.`nombre` AS `empresa_nombre`,
    `e`.`codigo` AS `empresa_codigo`,
    `s`.`nombre` AS `sucursal_nombre`,
    `s`.`codigo` AS `sucursal_codigo`,
    `s`.`ciudad` AS `sucursal_ciudad`,
    `s`.`pais` AS `sucursal_pais`,
    `u`.`nombre_completo` AS `usuario_sistema_nombre`,
    case when `p`.`fecha_salida` is null then timestampdiff(YEAR,`p`.`fecha_ingreso`,curdate()) else timestampdiff(YEAR,`p`.`fecha_ingreso`,`p`.`fecha_salida`) end AS `anos_servicio` 
    from (((`personal` `p` left join `empresas` `e` on(`p`.`empresa_id` = `e`.`id`)) left join `sucursales` `s` on(`p`.`sucursal_id` = `s`.`id`)) left join `usuarios` `u` on(`p`.`usuario_sistema_id` = `u`.`id`))
    ";

    $pdo->exec($sql);

    echo "<div style='color:green; font-weight:bold; padding:20px; border:1px solid green; background:#eaffea;'>
        ✓ Vista creada correctamente. Ahora puedes acceder a la sección de Gestión IT.
    </div>";
    echo "<p><a href='index.php?view=visualizacion_it'>Ir a Gestión IT</a></p>";

} catch (PDOException $e) {
    echo "<div style='color:red; pkadding:20px; border:1px solid red; background:#ffeaea;'>
        Error creando vista: " . $e->getMessage() . "
    </div>";
}
?>