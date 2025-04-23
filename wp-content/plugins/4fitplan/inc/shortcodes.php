<?php 

function shortcode_plan_personalizado() {
    $user = wp_get_current_user();
    $user_id = $user->ID;

    // Recoger posibles valores
    $peso            = get_user_meta($user_id, 'cliente_peso', true);
    $altura          = get_user_meta($user_id, 'cliente_altura', true);
    $comidas_diarias = get_user_meta($user_id, 'comidas_diarias', true);
    
    // Opciones para roles
    $opciones_objetivo      = role_options('objetivo');
    $opciones_preferencias  = role_options('preferencias');
    $opciones_restricciones = role_options('restricciones');

    // Inicializar variables para roles
    $objetivo = '';
    $preferencias = '';
    $restriccion_roles = array();

    // Recorrer roles del usuario
    foreach ($user->roles as $rol) {
        if ( in_array($rol, array_keys($opciones_objetivo)) ) {
            $objetivo = get_role_name_from_slug($rol);
        }
        if ( in_array($rol, array_keys($opciones_preferencias)) ) {
            $preferencias = get_role_name_from_slug($rol);
        }
        if ( in_array($rol, array_keys($opciones_restricciones)) ) {
            $restriccion_roles[] = get_role_name_from_slug($rol);
        }
    }

    // Combinar restricciones de metadatos con las de roles, si existen
    if ( ! empty($restriccion_roles) ) {
        $restricciones = implode(', ', $restriccion_roles);
    }

     // Determinar qué roles faltan
     $faltan_roles = array();
     if ( empty($objetivo) ) { $faltan_roles[] = 'objetivo'; }
     if ( empty($preferencias) ) { $faltan_roles[] = 'preferencias'; }
     if ( empty($restricciones) ) { $faltan_roles[] = 'restricciones'; }

     // Crear array de campos faltantes (incluyendo roles)
    $campos_faltantes = array();
    if ( empty($peso) )            $campos_faltantes[] = 'cliente_peso';
    if ( empty($altura) )          $campos_faltantes[] = 'cliente_altura';
    if ( empty($comidas_diarias) )  $campos_faltantes[] = 'comidas_diarias';
    if ( in_array('objetivo', $faltan_roles) )       { $campos_faltantes[] = 'objetivo'; }
    if ( in_array('preferencias', $faltan_roles) )   { $campos_faltantes[] = 'preferencias'; }
    if ( in_array('restricciones', $faltan_roles) ) { $campos_faltantes[] = 'restricciones'; }

    // Procesar el formulario si se envía
    procesar_datos_plan($user);

    // Si faltan datos, mostrar el formulario
    if ( ! empty($campos_faltantes) ) {
        
        return render_form_nutrition_plan($campos_faltantes);
    }

    // Mostrar selector de fecha (calendario) y contenedor para el plan con spinner
    ob_start();
    echo display_nutrition_data();
    ?>
    <div id="plan-container">
        <div class="spiner-container">
            <div class="spinner"></div>
            <p>Cargando tu plan de alimentación...</p>
        </div>
    </div>
    <div id="plan-selector" style="text-align: center; margin-bottom:20px;">
        <h2>Planifica tu dieta</h2>
        <p>Consulta tu alimentación para los próximos 7 días</p>
        <label for="plan-day-picker"><strong>Planifica tu alimentación</strong></label>
        <!-- Input de texto para Flatpickr -->
        <input type="text" id="plan-day-picker" readonly style="cursor: pointer; padding: 5px; font-size: 1em;">
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('plan_personalizado', 'shortcode_plan_personalizado');