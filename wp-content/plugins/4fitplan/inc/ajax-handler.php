<?php
/**
 * Archivo: ajax-handlers.php
 * Descripción: Funciones para manejar las peticiones AJAX de 4FitPlan.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Función para cargar el plan de alimentación vía AJAX.
 */

 function ajax_cargar_plan_usuario() {
    $user = wp_get_current_user();
    $user_id = $user->ID;

    // 1) Metadatos
    $peso            = get_user_meta($user_id, 'cliente_peso', true);
    $altura          = get_user_meta($user_id, 'cliente_altura', true);
    $comidas_diarias = get_user_meta($user_id, 'comidas_diarias', true);
    $dia             = isset($_GET['dia']) ? intval($_GET['dia']) : date('j');

    // 2) Roles → objetivo, preferencias, restricciones
    $objetivo     = '';
    $preferencias = '';
    $restricciones = ''; // asegúrate de inicializar

    $opciones_objetivo      = role_options('objetivo');
    $opciones_preferencias  = role_options('preferencias');
    $opciones_restricciones = role_options('restricciones');
    foreach ($user->roles as $rol) {
        if (array_key_exists($rol,  $opciones_objetivo )) {
            $objetivo = get_role_name_from_slug($rol);
        }
        if (array_key_exists($rol, $opciones_preferencias)) {
            $preferencias = get_role_name_from_slug($rol);
        }
        if (array_key_exists($rol, $opciones_restricciones )) {
            $restricciones = trim($restricciones . ', ' . get_role_name_from_slug($rol), ', ');
        }
    }

    // 3) Validar datos básicos
    if (empty($peso) || empty($altura) || empty($comidas_diarias)) {
        echo '<p>⚠️ Faltan datos en tu perfil para generar el plan.</p>';
        wp_die();
    }

    // 4) Obtener plan (string o array)
    $plan_json = obtener_plan_alimentacion(
        $peso, 
        $altura, 
        $objetivo, 
        $preferencias, 
        $restricciones, 
        $comidas_diarias, 
        $dia
    );

    $plan = db_json_to_array($plan_json);

    // 6) Validar estructura y número exacto de comidas
    if (
        ! is_array($plan)
        || ! isset($plan['comidas'])
        || ! is_array($plan['comidas'])
        || count($plan['comidas']) !== (int)$comidas_diarias
    ) {
        error_log("Plan mal formado o cantidad de comidas incorrecta: " . print_r($plan, true));
        echo '<p>⚠️ No se pudo cargar tu plan de alimentación.</p>';
        wp_die();
    }
   
    echo display_nutrition($plan);
    wp_die();
}
add_action('wp_ajax_cargar_plan_usuario', 'ajax_cargar_plan_usuario');
// Si quieres permitir peticiones no autenticadas:
// add_action('wp_ajax_nopriv_cargar_plan_usuario', 'ajax_cargar_plan_usuario');

