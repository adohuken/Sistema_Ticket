<?php
/**
 * SCRIPT HELPER: Agregar Tokens CSRF a Formularios
 * 
 * Este archivo contiene los snippets de código que debes agregar
 * manualmente a cada formulario del sistema.
 */

?>

<!-- ============================================ -->
<!-- INSTRUCCIONES PARA AGREGAR CSRF A FORMULARIOS -->
<!-- ============================================ -->

<!--
PASO 1: En cada archivo PHP que contenga formularios, agregar ANTES de los botones de submit:

<?php echo campo_csrf(); ?>

EJEMPLO COMPLETO:
-->

<!-- FORMULARIO DE CREAR USUARIO (seccion_3_formulario.php línea 97) -->
<form action="index.php?view=usuarios" method="POST">
    <input type="hidden" name="accion" value="crear_usuario">

    <div>
        <label>Nombre Completo</label>
        <input type="text" name="nombre_usuario" required>
    </div>

    <!-- AGREGAR AQUÍ EL TOKEN CSRF -->
    <?php echo campo_csrf(); ?>

    <div>
        <button type="submit">Guardar Usuario</button>
    </div>
</form>


<!-- ============================================ -->
<!-- ARCHIVOS QUE NECESITAN TOKENS CSRF -->
<!-- ============================================ -->

/**
* 1. seccion_3_formulario.php (línea 97)
* Formulario: Crear Usuario
* Agregar antes de: <div style="<?php echo $estilo_botones_user; ?>">
    */

    /**
    * 2. seccion_3_crear_ticket.php
    * Formulario: Crear Ticket
    * Buscar: <button type="submit" * Agregar ANTES del botón: <?php echo campo_csrf(); ?> */ /** * 3.
        seccion_3_editar_ticket.php * Formulario: Editar Ticket * Buscar: <button type="submit" * Agregar ANTES del
        botón: <?php echo campo_csrf(); ?> */ /** * 4. seccion_rrhh_ingreso.php * Formulario: Registro de Ingreso *
        Buscar: <button type="submit" * Agregar ANTES del botón: <?php echo campo_csrf(); ?> */ /** * 5.
        seccion_rrhh_salida.php * Formulario: Registro de Salida * Buscar: <button type="submit" * Agregar ANTES del
        botón: <?php echo campo_csrf(); ?> */ /** * 6. seccion_4_listados.php (líneas 97-112) * Formulario: Asignación
        de Tickets * Buscar: <form method="POST" action="index.php?view=asignar" * Agregar después de: <input
        type="hidden" name="accion" value="asignar_ticket">
        */


        <!-- ============================================ -->
        <!-- CÓDIGO EXACTO PARA CADA ARCHIVO -->
        <!-- ============================================ -->

        <!-- 
ARCHIVO: seccion_3_formulario.php
LÍNEA: 97 (después de </div> del select de roles)
AGREGAR:
-->
        <?php echo campo_csrf(); ?>


        <!-- 
ARCHIVO: seccion_4_listados.php
LÍNEA: 99 (después de <input type="hidden" name="accion" value="asignar_ticket">)
AGREGAR:
-->
        <?php echo campo_csrf(); ?>


        <!-- 
ARCHIVO: seccion_3_crear_ticket.php
BUSCAR: <button type="submit"
AGREGAR ANTES:
-->
        <?php echo campo_csrf(); ?>


        <!-- 
ARCHIVO: seccion_3_editar_ticket.php
BUSCAR: <button type="submit"
AGREGAR ANTES:
-->
        <?php echo campo_csrf(); ?>


        <!-- 
ARCHIVO: seccion_rrhh_ingreso.php
BUSCAR: <button type="submit"
AGREGAR ANTES:
-->
        <?php echo campo_csrf(); ?>


        <!-- 
ARCHIVO: seccion_rrhh_salida.php
BUSCAR: <button type="submit"
AGREGAR ANTES:
-->
        <?php echo campo_csrf(); ?>


        <!-- ============================================ -->
        <!-- VERIFICACIÓN -->
        <!-- ============================================ -->

        <!--
DESPUÉS DE AGREGAR LOS TOKENS, VERIFICAR:

1. Cada formulario debe tener exactamente UN campo CSRF
2. El campo debe estar DENTRO del <form>
3. El campo debe estar ANTES del botón submit
4. No debe haber espacios extra o caracteres especiales

EJEMPLO CORRECTO:
<form method="POST">
    <input type="text" name="campo1">
    <?php echo campo_csrf(); ?>
    <button type="submit">Enviar</button>
</form>

EJEMPLO INCORRECTO:
<form method="POST">
    <input type="text" name="campo1">
    <button type="submit">Enviar</button>
</form>
<?php echo campo_csrf(); ?> ❌ FUERA DEL FORM
-->