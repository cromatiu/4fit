<?php
/**
 * Archivo: form-handler.php
 * Descripción: Clase para manejar el renderizado de formularios (por ejemplo, para recopilar datos del usuario)
 *              en un formulario multiparte (wizard) y para procesar el envío de esos datos.
 * Versión: 1.0
 * Autor: Tu Nombre
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
function fields_personal_form($campos_faltantes) {
    ob_start();
    // 1) Lugar de entrenamiento (roles)
    if ( in_array( 'billing_first_name', $campos_faltantes, true ) ) {
        $attrs = array(
            'name'      => 'billing_first_name',
        );
        echo generate_input_form(
            'text',
            $attrs,
            'Hola, ¿Cómo te llamas?',
            $campos_faltantes,
    
        );
    }
    if(in_array( 'cliente_edad', $campos_faltantes, true )) {
        $attrs = array(
            'name'      => 'cliente_edad',
            'inputmode' => 'numeric',
            'pattern'   => '\d{2,3}',
            'maxlength' => '3',
            'required'  => 'required',
            'title'     => 'Introduce tu edad: sólo dígitos, 2–3 caracteres',
            'value'     => '');
        echo generate_input_form(
            'text',
            $attrs,
            '¿Cuántos años tienes?',
            $campos_faltantes,
        );
    }
    return ob_get_clean();
}

function render_form_nutrition_plan($campos_faltantes) {

    ob_start();

  ?>
  <form id="multiStepForm" class="page-form" method="post">
      
      <h2>Completa tu información para generar el plan:</h2>
      <?php echo fields_nutrition_form($campos_faltantes); ?>

  </form>
  <?php
  return ob_get_clean();
}
function fields_nutrition_form($campos_faltantes) {
    ob_start();

    if ( in_array('cliente_peso', $campos_faltantes) ) {
        // Paso 3: Peso (1–3 dígitos)
        $attrs = array(
            'name'      => 'cliente_peso',
            'inputmode' => 'numeric',
            'pattern'   => '\\d{2,3}',
            'maxlength' => '3',
            'required'  => 'required',
            'title'     => 'Introduce tu peso en kg: sólo dígitos, 2–3 caracteres',
            'value'     => ''
        );
        echo generate_input_form(
            'text',
            $attrs,
            '¿Cuál es tu peso actual? (kg)',
            $campos_faltantes
        );

    }
    if ( in_array('cliente_altura', $campos_faltantes) ) {

        // Paso 4: Altura (2–3 dígitos)
        $attrs = array(
            'name'      => 'cliente_altura',
            'inputmode' => 'numeric',
            'pattern'   => '\\d{2,3}',
            'maxlength' => '3',
            'required'  => 'required',
            'title'     => 'Introduce tu estatura en cm: sólo dígitos, 2–3 caracteres',
            'value'     => ''
        );
        echo generate_input_form(
            'text',
            $attrs,
            '¿Cuál es tu estatura? (cm)',
            $campos_faltantes
        );
    }
    if ( in_array('comidas_diarias', $campos_faltantes) ) {      
        // Por ejemplo, si deseas usar radiobotones para la cantidad
        $opciones_comidas = array (
            '3' => 3,
            '4' => 4,
            '5' => 5
        );
        $attrs = array(
            'name'      => 'comidas_diarias',
        );
        echo generate_input_form(
            'radio', 
            $attrs, 
            '¿Cuántas comidas diarias prefieres hacer?', 
            $campos_faltantes, 
            $opciones_comidas
        );
    }
    if ( in_array('objetivo', $campos_faltantes) ) {
        // Opciones para roles
        $attrs = array(
            'name'      => 'objetivo',
        );
        $opciones_objetivo = field_options('objetivo');
        echo generate_input_form(
            'radio', 
            $attrs, 
            '¿Cuál es tu principal objetivo?', 
            $campos_faltantes, 
            $opciones_objetivo
        );
    }
    if ( in_array('preferencias', $campos_faltantes) ) {

        $opciones_preferencias  = field_options('preferencias');
        $attrs = array(
            'name'      => 'preferencias',
        );
        echo generate_input_form(
            'radio', 
            $attrs, 
            '¿Qué tipo de alimentación prefieres?', 
            $campos_faltantes, 
            $opciones_preferencias,
            true
        );
    }
    if ( in_array('restricciones_list', $campos_faltantes) ) :         
            $opciones_restricciones = field_options('restricciones_list');
            unset($opciones_restricciones['ninguna']);
            $opciones_restricciones_prev = array(
                'yes'       => 'Sí',
                'no'   => 'No'
            );
            
            $first = (reset($campos_faltantes) == 'restricciones_list') ? true : false;
            $last  = (end($campos_faltantes) == 'restricciones_list') ? true : false;

        ?>
    
        <div class="form-step">
            <div class="step-container" id="restrictions">
                <?php echo step_top($campos_faltantes, 'restricciones_list'); ?>
                
                <div id="restricciones_container_prev">
                    <?php echo generate_options_group('radio', 'tiene_restricciones', $opciones_restricciones_prev, '¿Tienes restricciones alimentarias?'); ?>
                </div>
                <div id="restricciones_container" style="display: none;">
                    <?php echo generate_options_group('checkbox', 'restricciones_list', $opciones_restricciones, 'Selecciona tus restricciones', true); ?>
                </div>
                <?php echo form_nav($first, $last);  ?>
            </div>
        </div>
    <?php endif; 
    return ob_get_clean();
}
function render_form_exercise_plan($campos_faltantes) {
    ob_start();
        ?>
        <form id="multiStepForm" class="page-form" method="post">
            <h2>Completa tu información para generar el plan de entrenamiento:</h2>
            <?php echo fields_exercise_form($campos_faltantes); ?>
        </form>
        <?php
    return ob_get_clean();
}


/**
 * Genera los pasos del formulario para los datos de ejercicio
 *
 * @param array $campos_faltantes Array de claves de campos que faltan.
 * @return string HTML con los pasos de ejercicio.
 */
function fields_exercise_form( $campos_faltantes, $exclude_goal = false) {
    ob_start();
    // 1) Lugar de entrenamiento (roles)
    if ( in_array( 'sexo', $campos_faltantes, true ) ) {
        $attrs = array(
            'name' => 'sexo',
        );
        $opciones = field_options( 'sexo' );
        echo generate_input_form(
            'radio',
            $attrs,
            '¿Cuál es tu género?',
            $campos_faltantes,
            $opciones,
            true
        );
    }
    // 1) Lugar de entrenamiento (roles)
    if ( in_array( 'objetivo', $campos_faltantes, true ) && $exclude_goal ) {
        $attrs = array(
            'name' => 'objetivo',
        );
        $opciones = field_options( 'objetivo' );
        echo generate_input_form(
            'radio',
            $attrs,
            '¿Cuál es tu objetivo?',
            $campos_faltantes,
            $opciones
        );
    }
    // 1) Lugar de entrenamiento (roles)
    if ( in_array( 'nivel', $campos_faltantes, true ) ) {
        $attrs = array(
            'name' => 'nivel',
        );
        $opciones = field_options( 'nivel' );
        echo generate_input_form(
            'radio',
            $attrs,
            '¿Qué nivel físico consideras que tienes?',
            $campos_faltantes,
            $opciones
        );
    }
 

    // 1) Lugar de entrenamiento (roles)
    if ( in_array( 'lugar', $campos_faltantes, true ) ) {
        $attrs = array(
            'name' => 'lugar',
        );
        $opciones = field_options( 'lugar' );
        echo generate_input_form(
            'radio',
            $attrs,
            '¿Dónde prefieres entrenar?',
            $campos_faltantes,
            $opciones,
            true
        );
    }

    // 2) Días de entrenamiento a la semana (user_meta)
    if ( in_array( 'dias_entreno_semana', $campos_faltantes, true ) ) {
        $opciones_dias = [
            '2' => '2 días',
            '3' => '3 días',
            '4' => '4 días',
            '5' => '5 días',
            '6' => '6 días',
            '7' => '7 días',
        ];
        $attrs = array(
            'name' => 'dias_entreno_semana',
        );
        echo generate_input_form(
            'radio',
            $attrs,
            '¿Cuántos días a la semana entrenas?',
            $campos_faltantes,
            $opciones_dias
        );
    }

    // 3) Tiempo de dedicación por sesión (user_meta)
    if ( in_array( 'tiempo_entreno', $campos_faltantes, true ) ) {
        $opciones_tiempo = [
            '15min'  => '15 minutos',
            '30min'  => '30 minutos',
            '45min'  => '45 minutos',
            '60min+' => 'Más de 60 minutos',
        ];
        $attrs = array(
            'name'   => 'tiempo_entreno',
        );
        echo generate_input_form(
            'radio',
            $attrs,
            '¿Cuánto tiempo dedicas por sesión?',
            $campos_faltantes,
            $opciones_tiempo
        );
    }

    return ob_get_clean();
}
function render_personal_form($campos_faltantes ) {
    ob_start();
    ?>
    <form id="multiStepForm" method="post">
        <h3>Completa tu información para que podamos ayudarte con tu motivación:</h3>
        <?php echo fields_personal_form($campos_faltantes); ?>
    </form>
    <?php
    return ob_get_clean();
}

/**
 * Renderiza el formulario unificado multi-paso.
 * @param array $missing Lista de campos faltantes.
 * @return string HTML form.
 */
function uf_render_user_data_form( array $missing ): string {
    ob_start();
    ?>
    <form method="post" id="uf-user-data-form">

        <?php
        echo fields_personal_form($missing);
        // Campos de nutrición
        echo fields_nutrition_form( $missing, true );

        // Campos de ejercicio
        echo fields_exercise_form( $missing );
        ?>
    </form>
    <?php
    return ob_get_clean();
}


/**
 * Procesa los datos del formulario unificado, guarda user_meta y roles.
 */
function uf_process_user_data_form( WP_User $user ) {
    if ( ! isset( $_POST['uf_save_profile'] ) ) {
        return;
    }
    $user_id = $user->ID;

    // Personal metadatos
    if ( isset( $_POST['billing_first_name'] ) ) {
        update_user_meta( $user_id, 'billing_first_name', sanitize_text_field( $_POST['billing_first_name'] ) );
    }
    // Personal metadatos
    if ( isset( $_POST['cliente_edad'] ) ) {
        update_user_meta( $user_id, 'cliente_edad', sanitize_text_field( $_POST['cliente_edad'] ) );
    }
    // Nutrición metadatos
    if ( isset( $_POST['cliente_peso'] ) ) {
        update_user_meta( $user_id, 'cliente_peso', sanitize_text_field( $_POST['cliente_peso'] ) );
    }
    if ( isset( $_POST['cliente_altura'] ) ) {
        update_user_meta( $user_id, 'cliente_altura', sanitize_text_field( $_POST['cliente_altura'] ) );
    }
    if ( isset( $_POST['comidas_diarias'] ) ) {
        update_user_meta( $user_id, 'comidas_diarias', sanitize_text_field( $_POST['comidas_diarias'] ) );
    }

    // Nutrición roles
    if ( isset( $_POST['objetivo'] ) ) {
        $rol = sanitize_text_field( $_POST['objetivo'] );
        if ( ! in_array( $rol, $user->roles ) ) {
            $user->add_role( $rol );
        }
    }
    if ( isset( $_POST['preferencias'] ) ) {
        $rol = sanitize_text_field( $_POST['preferencias'] );
        if ( ! in_array( $rol, $user->roles ) ) {
            $user->add_role( $rol );
        }
    }
    if(isset($_POST['tiene_restricciones'])) {

        // Limpia roles previos de restricciones
        $opts = field_options('restricciones_list');
        foreach ( $opts as $slug => $_ ) {
            if ( in_array( $slug, $user->roles ) ) {
                $user->remove_role( $slug );
            }
        }
        if ( isset( $_POST['restricciones_list'] ) ) {
            // Añade seleccionadas
            foreach ( (array) $_POST['restricciones_list'] as $rol ) {
                $rol = sanitize_text_field( $rol );
                if ( ! in_array( $rol, $user->roles ) ) {
                    $user->add_role( $rol );
                }
            }
        } else {
           $user->add_role( 'ninguna' );
        }
    }
    

    // Ejercicio metadatos
    if ( isset( $_POST['dias_entreno_semana'] ) ) {
        update_user_meta( $user_id, 'dias_entreno_semana', intval( $_POST['dias_entreno_semana'] ) );
    }
    if ( isset( $_POST['tiempo_entreno'] ) ) {
        update_user_meta( $user_id, 'tiempo_entreno', sanitize_text_field( $_POST['tiempo_entreno'] ) );
    }
    // Ejercicio roles
    if ( isset( $_POST['lugar'] ) ) {
        $rol = sanitize_text_field( $_POST['lugar'] );
        if ( ! in_array( $rol, $user->roles ) ) {
            $user->add_role( $rol );
        }
    }
    if ( isset( $_POST['sexo'] ) ) {
        $rol = sanitize_text_field( $_POST['sexo'] );
        if ( ! in_array( $rol, $user->roles ) ) {
            $user->add_role( $rol );
        }
    }
    if ( isset( $_POST['nivel'] ) ) {
        $rol = sanitize_text_field( $_POST['nivel'] );
        if ( ! in_array( $rol, $user->roles ) ) {
            $user->add_role( $rol );
        }
    }

    // Recargar
    wp_safe_redirect( get_permalink() );
    exit;
}

function generate_options_group($type, $field_name, $options, $label_text = '', $image = false, $simple = false, $selected = null) {
    ob_start();
    $plugin_url = plugin_dir_url(dirname(__FILE__));
    $text_size = $image ? '' : ' big-text';
    $field_name = ($type === 'checkbox') ? $field_name . '[]' : $field_name;

    $wrapper_class = $simple ? 'simple-options' : 'option-group';

    echo "<div class=\"$wrapper_class\">";
    if (!$simple && $label_text) {
        echo "<strong class=\"label-title\">{$label_text}</strong>";
    }

    foreach ($options as $slug => $nombre) {
        $is_checked = false;
        if (is_array($selected)) {
            $is_checked = in_array($slug, $selected);
        } elseif (!is_null($selected)) {
            $is_checked = $slug == $selected;
        }
        ?>
        <label class="custom-<?php echo esc_attr($type); ?>">
            <input type="<?php echo esc_attr($type); ?>" name="<?php echo esc_attr($field_name); ?>" value="<?php echo esc_attr($slug); ?>" <?php checked($is_checked); ?>>
            <div class="option-container">
                <?php if ($image): ?>
                    <div class="option-image">
                        <img src="<?php echo esc_url($plugin_url . 'assets/images/svg/' . $slug . '.svg'); ?>" alt="<?php echo esc_attr($nombre); ?>">
                    </div>
                <?php endif; ?>
                <div class="option-text<?php echo $text_size; ?>"><?php echo esc_html($nombre); ?></div>
            </div>
        </label>
        <?php
    }

    echo "</div>";
    echo '<div class="verification"></div>';
    
    return ob_get_clean();
}


function generate_input_form($type, $attrs, $label_text, $campos_faltantes, $options = array(), $image = false, $simple = false, $selected = null, WP_User $user = null) {
    $field_name = $attrs['name'];
    $first = (reset($campos_faltantes) == $field_name);
    $last  = (end($campos_faltantes) == $field_name);
    $active_class = $first ? ' active' : '';

    ob_start();

    if ($type === 'checkbox' || $type === 'radio') {
        if (!empty($options)) {
            if ($simple) {
                echo '<div class="simple-field">';
                echo render_inline_input_field($type, $field_name, $selected, $options, $image, $user);
                echo '</div>';
            } else {
                ?>
                <div class="form-step<?php echo $active_class; ?>">
                    <div class="step-container">
                        <?php echo step_top($campos_faltantes, $field_name); ?>
                        <?php echo generate_options_group($type, $field_name, $options, $label_text, $image, false, $selected); ?>
                        <?php echo form_nav($first, $last); ?>
                    </div>
                </div>
                <?php
            }
        }
    }

    if ($type === 'number' || $type === 'text') {
        
        if ($simple) {
            echo '<div class="simple-field text-group">';
            echo render_inline_input_field($type, $field_name, $selected, [], false, $user);
            echo '</div>';
        } else {
            $attrs_string = '';
            foreach ($attrs as $attr => $value) {
                $attrs_string .= $attr . '="' . esc_attr($value) . '" ';
            }
            ?>
            <div class="form-step<?php echo $active_class; ?>">
                <div class="step-container">
                    <?php echo step_top($campos_faltantes, $field_name); ?>
                    <div class="text-group">
                        <label class="label-title"><?php echo $label_text; ?></label>
                        <input type="<?php echo esc_attr($type); ?>" <?php echo $attrs_string; ?> value="<?php echo esc_attr($selected); ?>">
                        <div class="verification"></div>
                    </div>
                    <?php echo form_nav($first, $last); ?>
                </div>
            </div>
            <?php
        }
    }

    return ob_get_clean();
}


// RENDER DE CAMPOS INDIVIDUALES PARA EDITAR PERFIL
function render_inline_input_field(
        string $type,
        string $field_name,
        $value = '',
        array $options = [],
        bool $image = false,
        WP_User $user = null
    ): string {
    $plugin_url = plugin_dir_url(dirname(__FILE__));
    $text_size = $image ? '' : ' big-text';

    // Obtener valores predeterminados si es posible
    if ($user instanceof WP_User && empty($value)) {
        $defaults = uf_get_user_fields($user, ['nutrition','exercise','personal'], true);
        $value = $defaults[$field_name] ?? '';
    }

    ob_start();
    echo "<div class=\"inline-field\">";

    if ($type === 'checkbox' || $type === 'radio') {
        $input_name = ($type === 'checkbox') ? $field_name . '[]' : $field_name;
        foreach ($options as $slug => $nombre) {
            $checked = is_array($value) ? in_array($slug, $value) : $slug === $value;
            ?>
            <label class="custom-<?php echo esc_attr($type); ?>">
                <input type="<?php echo esc_attr($type); ?>" name="<?php echo esc_attr($input_name); ?>" value="<?php echo esc_attr($slug); ?>" <?php checked($checked); ?>>
                <div class="option-container">
                    <?php if ($image): ?>
                        <div class="option-image">
                            <img src="<?php echo esc_url($plugin_url . 'assets/images/svg/' . $slug . '.svg'); ?>" alt="<?php echo esc_attr($nombre); ?>">
                        </div>
                    <?php endif; ?>
                    <div class="option-text<?php echo $text_size; ?>"><?php echo esc_html($nombre); ?></div>
                </div>
            </label>
            <?php
        }
        ?>
        <div class="verification"></div>
        <?php
    } elseif ($type === 'text' || $type === 'number') {
        ?>
        <input type="<?php echo esc_attr($type); ?>" name="<?php echo esc_attr($field_name); ?>" value="<?php echo esc_attr($value); ?>">
        <div class="verification"></div>
        <?php

    }

    echo "</div>";
    return ob_get_clean();
}

// RENDER DE LA NAVEGACIÓN EN FORMULARIO MULTIPART
function form_nav($first, $last ) {

  ob_start();
  ?>
      <div class="form-nav">
          <?php if(!$first): ?>
              <button type="button" class="prev-btn">Anterior</button>
          <?php endif; ?>
          <?php if($last): ?>
              <button type="submit" name="uf_save_profile" class="button">Guardar</button>
          <?php else:?>
              <button type="button" class="next-btn">Siguiente</button>
          <?php endif; ?>
      </div>
  <?php
  return ob_get_clean();
}
/**
 * Muestra la barra de progreso para el paso actual en un conjunto de campos.
 *
 * @param array  $fields      Array indexado con las keys de todos los campos, en el orden del formulario.
 * @param string $current_key La key del campo que estás mostrando ahora.
 */
function step_top(array $fields, string $current_key) {
    $total = count($fields);

    // Buscar la posición (0-based) del campo actual
    $index = array_search($current_key, $fields, true);
    if ($index === false) {
        return; // key no encontrada
    }

    $step     = $index + 1;                           // paso 1-based
    $percent  = round($step / $total * 100, 0);       // porcentaje entero
    ob_start();
    // HTML de la barra de progreso
    echo '<div class="top">';
    echo   '<div class="progress-container">';
    echo     "<div class=\"progress-bar\" style=\"width:{$percent}%;\"></div>";
    echo   '</div>';
    echo   "<span>{$step} / {$total}</span>";
    echo '</div>';
    return ob_get_clean();
}
