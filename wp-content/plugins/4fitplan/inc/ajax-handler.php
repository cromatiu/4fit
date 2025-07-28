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

    $dia    = isset($_GET['dia']) ? intval($_GET['dia']) : date('j');
    $fields = uf_get_user_fields( $user,  ['nutrition'], true);

    if(!isset($fields['objetivo']) && !isset($fields['cliente_peso']) && !isset($fields['cliente_altura']) && !isset($fields['comidas_diarias']) && !isset($fields['restricciones_list']) && !isset($fields['nivel'])) {
        echo '<p>Faltan datos en tu perfil para generar el plan.</p>';
        wp_die();
    }
    $objetivo               =  $fields['objetivo'];
    $peso                   =  $fields['cliente_peso'];
    $altura                 =  $fields['cliente_altura'];
    $comidas_diarias        =  $fields['comidas_diarias'];
    $preferencias           =  $fields['preferencias'];
    $restricciones          =  $fields['restricciones_list']; 
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

// Registramos los mismos hooks que usas para alimentación
add_action( 'wp_ajax_cargar_plan_ejercicio',      'ajax_cargar_plan_ejercicio' );
add_action( 'wp_ajax_nopriv_cargar_plan_ejercicio','ajax_cargar_plan_ejercicio' );

function ajax_cargar_plan_ejercicio() {
    
    $user    = wp_get_current_user();

    $fields = uf_get_user_fields( $user,  ['exercise'], true);

    if(!isset($fields['objetivo']) && !isset($fields['lugar']) && !isset($fields['dias_entreno_semana']) && !isset($fields['tiempo_entreno']) && !isset($fields['sexo']) && !isset($fields['nivel'])) {
        echo '<p>⚠️ Configura tu objetivo y lugar de entrenamiento en tu perfil.</p>';
        wp_die();
    }
    $objetivo       =  $fields['objetivo'];
    $lugar          =  $fields['lugar'];
    $dias_semana    =  $fields['dias_entreno_semana'];
    $tiempo_entreno =  $fields['tiempo_entreno'];
    $sexo           =  $fields['sexo'];
    $nivel          =  $fields['nivel'];

    $plan_semanal = obtener_plan_ejercicio(
        $objetivo,
        $lugar,
        $dias_semana,
        $tiempo_entreno,
        $sexo,
        $nivel
    );


    if ( ! $plan_semanal || empty( $plan_semanal) ) {
        error_log('Error ejercicio api' . var_export($plan_semanal));
        echo '<p>⚠️ No se pudo generar tu rutina.</p>';
        wp_die();
    }
    // 7) Renderizar HTML
    ob_start();

    echo render_weekly_exercise_plan( $plan_semanal );
    echo ob_get_clean();

    wp_die();
}

// -----------------------
// 1) ajax-handler.php
// -----------------------

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'wp_ajax_cargar_mensaje_motivacional',      'ajax_cargar_mensaje_motivacional' );
add_action( 'wp_ajax_nopriv_cargar_mensaje_motivacional','ajax_cargar_mensaje_motivacional' );

function ajax_cargar_mensaje_motivacional() {
    if ( ! is_user_logged_in() ) {
        echo '<p>⚠️ Necesitas iniciar sesión para ver tu mensaje motivacional.</p>';
        wp_die();
    }

    $user = wp_get_current_user();

    // 1) Obtener (o generar+cachear) el HTML motivacional
    if ( ! function_exists('obtener_mensaje_motivacional') ) {
        require_once plugin_dir_path(__FILE__) . 'api-handler.php';
    }
    $html = obtener_mensaje_motivacional( $user );

    // 2) Responder
    if ( ! $html ) {
        echo '<p>⚠️ No hemos podido generar tu mensaje motivacional. Inténtalo de nuevo.</p>';
    } else {
        echo '<h2>Aquí tienes tu consejo del día</h2>';
        echo '<div class="mensaje-motivacional">'.$html.'</div>';
    }

    wp_die();
}

// -------------------------
// 1) ajax-handler.php
// -------------------------
if ( ! defined( 'ABSPATH' ) ) exit;

// Hooks AJAX para recetas
add_action( 'wp_ajax_cargar_recetas',      'ajax_cargar_recetas' );
add_action( 'wp_ajax_nopriv_cargar_recetas','ajax_cargar_recetas' );

function ajax_cargar_recetas() {
    $user    = wp_get_current_user();
    $user_id = $user->ID;
     $fields = uf_get_user_fields( $user,  ['nutrition'], true);

    if( !isset($fields['objetivo']) && !isset($fields['comidas_diarias']) && !isset($fields['preferencias']) && !isset($fields['restricciones_list'])) {
        echo '<p>⚠️ Tu perfil no tiene configurado objetivo y/o preferencias.</p>';
        wp_die();
    }
    $objetivo               =  $fields['objetivo'];
    $preferencias           =  $fields['preferencias'];
    $restricciones     =  $fields['restricciones_list']; 
    // 3) Parámetro opcional: tipo de comida
    $tipo_comida = isset( $_GET['tipo_comida'] ) 
        ? sanitize_text_field( wp_unslash($_GET['tipo_comida']) ) 
        : null;

    // 4) Obtener recetas filtradas
    if ( ! function_exists( 'obtener_recetas' ) ) {
        require_once plugin_dir_path(__FILE__) . 'db_handler.php';
    }
    $recetas = obtener_recetas( $objetivo, $preferencias, $restricciones, $tipo_comida );

    if ( empty( $recetas ) ) {
        echo '<div class="recetas-error">';
        echo '<h2>Aún no tienes recetas para tu perfil.</h2>';
        echo '<p>Visita la sección de alimentación para generar las recetas.</p>';
        echo '<a href="'. get_permalink(124375) . '" class="elementor-button">Ir a alimentación</a>';
        echo '</div>';
        wp_die();
    }

    $comidas_diarias = intval( get_user_meta( $user_id, 'comidas_diarias', true ) );
    // Generamos los tipos posibles
    $tipos_raw = get_comidas_diarias_types( $comidas_diarias );
    // Normalizamos a slug para el value y label amigable
    $tipos = array_map( function( $tipo ) {
        $slug  = sanitize_title( $tipo );
        $label = ucwords( str_replace( '-', ' ', $slug ) );
        return [ 'slug' => $slug, 'label' => $label ];
    }, $tipos_raw );
    echo '<div id="recetas-filter-container" class="recetas-filter-container" style="margin-bottom:1em;">';
        echo '<p class="recipes-filter-title">Filtrar por tipo de comida:</p>';
        echo '<div class="recetas-filter-options option-group">';
        echo '<label class="filter-btn custom-radio">';
        echo '<input type="radio" name="tipo_comida" value="" checked>';
        echo '<div class="option-container">';
        echo '<span class="option-text">Todas</span>';
        echo '</div>';
        echo '</label>';
        foreach ( $tipos as $t ) :
            $checked = ($t['slug'] == $tipo_comida) ? ' checked' : '';
            echo '<label class="filter-btn custom-radio">';
            echo '<input type="radio" name="tipo_comida" value="' . $t['slug'] . '"' . $checked . '>';
            echo '<div class="option-container">';
            echo '<span class="option-text">' . $t['label'] . '</span>';
            echo '</div>';
            echo '</label>';
        endforeach;
        echo '</div>';
    echo '</div>';
    // 5) Renderizar cada receta
    foreach ( $recetas as $key => $receta ) {
        echo display_receta($receta, true);
    }

    wp_die();
}

// 1) Asegúrate de que tu función ya exista:
// function obtener_metricas_usuario_formidable($user_id) { ... }

function ajax_get_metricas_usuario() {
    // 1) Obtén el array crudo de objetos
    $user_id = get_current_user_id();
    
    // Nota: aquí $raw es el resultado de tu SELECT, array de stdClass
    $chart = get_user_metrics($user_id);
    
    $html = '';
    foreach ( $chart['datasets'] as $ds ) {
        $html .= '<div style="margin-bottom:40px;font-family:Montserrat;">';
        $html .= '<h3 style="font-size:1.25rem;margin-bottom:8px;">' . esc_html($ds['label']) . '</h3>';
        $html .= '<canvas id="' . esc_attr($ds['id']) . '"></canvas>';
        $html .= '</div>';
    }
    $slides = get_user_images($user_id);

    $html_slides = '';
    foreach($slides as $s) {
        $html_slides .= '<div class="slide">';
        $html_slides .= '<h3 >'.esc_html($s['fecha']).'</h3>';
        $html_slides .= '<div class="slide-image">';
        $html_slides .= '<img src="' .esc_url($s['frente']). '" alt="Frente" style="max-width:30%;" />';
        $html_slides .= '<img src="' .esc_url($s['perfil']). '" alt="Perfil" style="max-width:30%;" />';
        $html_slides .= '<img src="' .esc_url($s['espalda']). '" alt="Espalda" style="max-width:30%;" />';
        $html_slides .= '</div></div>';
    }

    wp_send_json_success([ 'html' => $html, 'slides' => $html_slides, 'labels' => $chart['labels'], 'datasets' => $chart['datasets'] ]);
}
add_action('wp_ajax_nopriv_get_metricas_usuario','ajax_get_metricas_usuario');
add_action('wp_ajax_get_metricas_usuario','ajax_get_metricas_usuario');



add_action( 'wp_ajax_uf_render_edit_field', 'uf_render_edit_field_ajax' );
function uf_render_edit_field_ajax() {
    if ( ! is_user_logged_in() || empty($_GET['field']) || empty($_GET['user_id']) ) {
        wp_die('Solicitud no válida');
    }

    $slug    = sanitize_key($_GET['field']);
    $user_id = intval($_GET['user_id']);

    if ( ! current_user_can('edit_user', $user_id) ) {
        wp_die('No tienes permiso para editar este usuario.');
    }

    $user = get_user_by('ID', $user_id);

    echo '<form method="post" class="uf-inline-form">';
    echo render_single_field_form($slug, $user);
    echo '</form>';

    wp_die();
}
function render_single_field_form( string $slug, WP_User $user ): string {
    $campos_faltantes = [ $slug ]; // simulamos que solo falta este
    ob_start();

    // Etiqueta legible
    $label = uf_get_field_labels()[ $slug ] ?? ucfirst($slug);

    // CASO ESPECIAL: restricciones
    if ( $slug === 'restricciones' || $slug === 'restricciones_list' ) {
    $opts = field_options('restricciones_list');
    $opciones_prev = [ 'yes' => 'Sí', 'no' => 'No' ];
    unset($opts['ninguna']);

    $tiene_restricciones = ! in_array('ninguna', $user->roles, true);

    
    //$seleccionadas = array_values(array_intersect(array_keys($opts), $user->roles));
    echo '<div id="restricciones_container_prev">';
    $attrs = array(
        'name'      => 'tiene_restricciones'
    );
    echo generate_input_form(
        'radio',
        $attrs,
        '¿Tienes restricciones alimentarias?',
        $campos_faltantes,
        $opciones_prev,
        false,
        true,
        $user
    );
    echo '</div>';
    
    echo '<div id="restricciones_container" style="' . ( $tiene_restricciones ? '' : 'display:none;' ) . '">';
    $attrs = array(
        'name'      => 'restricciones_list'
    );
    echo generate_input_form(
        'checkbox',
        $attrs,
        'Selecciona tus restricciones',
        $campos_faltantes,
        $opts,
        true,
        true,
        null, 
        $user
    );
    echo '</div>';

}
 else {
        // Campos estándar
        $field_type = 'text';
        $options    = [];
        $image      = false;

        if (in_array($slug, ['cliente_peso', 'cliente_altura', 'cliente_edad'])) {
            $field_type = 'number';
        } elseif ( in_array($slug, ['comidas_diarias', 'dias_entreno_semana', 'tiempo_entreno', 'objetivo']) ) {
            $field_type = 'radio';
            $options = field_options($slug);
        } elseif ( in_array($slug, ['preferencias', 'sexo', 'nivel', 'lugar']) ) {
            $field_type = 'radio';
            $options = field_options($slug);
            $image = true;
        }
        $attrs = array(
            'name'      => $slug,
        );
        echo '<div class="uf-inline-field">';
        echo generate_input_form(
            $field_type,
            $attrs,
            $label,
            $campos_faltantes,
            $options,
            $image,
            true,  // modo simple
            null,  // valor seleccionado (usará user)
            $user
        );
        echo '</div>';
    }

    // Campos ocultos + botón
    ?>
    <input type="hidden" name="action" value="uf_save_field_ajax">
    <input type="hidden" name="uf_field" value="<?php echo esc_attr($slug); ?>">
    <input type="hidden" name="user_id" value="<?php echo esc_attr($user->ID); ?>">
    <?php wp_nonce_field('uf_save_field_' . $user->ID, 'uf_nonce'); ?>
    <button type="submit" class="submit-inline"><i aria-hidden="true" class="icon icon-task_alt"></i></button>
    <?php

    return ob_get_clean();
}



add_action( 'wp_ajax_uf_save_field_ajax', 'uf_save_field_ajax' );
add_action( 'wp_ajax_uf_save_field_ajax', 'uf_save_field_ajax' );

function uf_save_field_ajax() {
    if ( ! is_user_logged_in() || empty($_POST['user_id']) || empty($_POST['uf_field']) ) {
        wp_send_json_error(['message' => 'Solicitud incompleta']);
    }

    $user_id = intval($_POST['user_id']);
    $slug    = sanitize_key($_POST['uf_field']);

    if ( ! current_user_can('edit_user', $user_id) || ! wp_verify_nonce($_POST['uf_nonce'], 'uf_save_field_' . $user_id) ) {
        wp_send_json_error(['message' => 'No autorizado']);
    }

    $user  = get_user_by('ID', $user_id);
    $value = $_POST[$slug] ?? null;

    if ( $value === null ) {
        if ( $slug !== 'restricciones_list' || ! isset($_POST['tiene_restricciones']) || $_POST['tiene_restricciones'] === 'yes' ) {
            wp_send_json_error(['message' => 'Valor no proporcionado'. ' -  ' . $slug  ] );
        }
    }

    $label_value = $value;

    // Guardado y conversión
    if ( in_array($slug, ['cliente_peso', 'cliente_altura', 'billing_first_name', 'cliente_edad', 'comidas_diarias', 'dias_entreno_semana', 'tiempo_entreno']) ) {
        update_user_meta($user_id, $slug, sanitize_text_field($value));
    } elseif ( in_array($slug, ['objetivo', 'preferencias', 'sexo', 'nivel', 'lugar']) ) {
        $role = sanitize_text_field($value);
        $group_roles = field_role_group_slugs($slug);

        // Eliminar roles actuales del mismo grupo
        foreach ( $group_roles as $group_role ) {
            if ( in_array($group_role, $user->roles, true) ) {
                $user->remove_role($group_role);
            }
        }

        // Añadir el nuevo rol
        if ( ! in_array($role, $user->roles, true) ) {
            $user->add_role($role);
        }

        // Devolver nombre legible
        $options = field_options($slug);
        $label_value = $options[$role] ?? $role;
    } elseif ( $slug === 'restricciones_list' ) {
        $opts = field_options('restricciones_list');

        // Elimina todos los roles del grupo de restricciones, incluido 'ninguna'
        foreach ( array_keys($opts) as $r ) {
            if ( in_array($r, $user->roles, true) ) {
                $user->remove_role($r);
            }
        }

        // Evaluar la respuesta del usuario
        if ( isset($_POST['tiene_restricciones']) && $_POST['tiene_restricciones'] === 'yes' ) {
            $seleccionadas = isset($_POST['restricciones_list']) && is_array($_POST['restricciones_list'])
                ? array_map('sanitize_text_field', $_POST['restricciones_list'])
                : [];

            foreach ( $seleccionadas as $rol ) {
                if ( isset($opts[$rol]) ) {
                    $user->add_role($rol);
                }
            }

            $label_value = implode(', ', array_map(fn($r) => $opts[$r], $seleccionadas));
        } else {
            $user->add_role('ninguna');
            $label_value = 'Ninguna';
        }
    }

    wp_send_json_success([
        'slug'       => $value,
        'label'      => $label_value,
        'new_value'  => $label_value
    ]);
}

