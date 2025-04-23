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

function render_form_nutrition_plan($campos_faltantes) {

    ob_start();

  ?>
  <form id="multiStepForm" method="post">
      
      <h3>Completa tu información para generar el plan:</h3>
      <?php echo fields_nutrition_form($campos_faltantes); ?>

  </form>
  <?php
  return ob_get_clean();
}
function fields_nutrition_form($campos_faltantes) {
    ob_start();

    if ( in_array('cliente_peso', $campos_faltantes) ) {

        echo generate_input_form(
            'number', 
            'cliente_peso', 
            '¿Cuál es tu peso actual? (kg)', 
            $campos_faltantes,
        );
    }
    if ( in_array('cliente_altura', $campos_faltantes) ) {

        echo generate_input_form(
            'number', 
            'cliente_altura', 
            '¿Cuál es tu estatura? (cm)', 
            $campos_faltantes,
        );
    }
    if ( in_array('comidas_diarias', $campos_faltantes) ) {

        
        // Por ejemplo, si deseas usar radiobotones para la cantidad
        $opciones_comidas = array (
            '3' => 3,
            '4' => 4,
            '5' => 5
        );
        echo generate_input_form(
            'radio', 
            'comidas_diarias', 
            '¿Cuántas comidas diarias prefieres hacer?', 
            $campos_faltantes, 
            $opciones_comidas
        );
        
    }
    if ( in_array('objetivo', $campos_faltantes) ) {

        // Opciones para roles
        $opciones_objetivo = role_options('objetivo');
        echo generate_input_form(
            'radio', 
            'objetivo', 
            '¿Cuál es tu principal objetivo nutricional?', 
            $campos_faltantes, 
            $opciones_objetivo
        );
    }
    if ( in_array('preferencias', $campos_faltantes) ) {

        $opciones_preferencias  = role_options('preferencias');
        echo generate_input_form(
            'radio', 
            'preferencias', 
            '¿Qué tipo de alimentación prefieres?', 
            $campos_faltantes, 
            $opciones_preferencias,
            true
        );
    }
    if ( in_array('restricciones', $campos_faltantes) ) :         
            $opciones_restricciones = role_options('restricciones');
            unset($opciones_restricciones['ninguna']);
            $opciones_restricciones_prev = array(
                'yes'       => 'Sí',
                'no'   => 'No'
            );
            
            $first = (reset($campos_faltantes) == 'restricciones') ? true : false;
            $last  = (end($campos_faltantes) == 'restricciones') ? true : false;

        ?>
    
        <div class="form-step">
            <div class="step-container" id="restrictions">
                <?php echo step_top($campos_faltantes, 'restricciones'); ?>
                
                <div id="restricciones_container_prev">
                    <?php echo generate_options_group('radio', 'tiene_restricciones', $opciones_restricciones_prev, '¿Tienes restricciones alimentarias?'); ?>
                </div>
                <div id="restricciones_container" style="display: none;">
                    <?php echo generate_options_group('checkbox', 'restricciones', $opciones_restricciones, 'Selecciona tus restricciones', true); ?>
                </div>
                <?php echo form_nav($first, $last);  ?>
            </div>
        </div>
    <?php endif; 
    return ob_get_clean();
}

function procesar_datos_plan($user) {
  $user_id = $user->ID;
  error_log(var_export($_POST, true));
  if ( isset($_POST['guardar_datos_plan']) ) {
      if ( isset($_POST['cliente_peso']) ) {
          update_user_meta($user_id, 'cliente_peso', sanitize_text_field($_POST['cliente_peso']));
      }
      if ( isset($_POST['cliente_altura']) ) {
          update_user_meta($user_id, 'cliente_altura', sanitize_text_field($_POST['cliente_altura']));
      }
      if ( isset($_POST['comidas_diarias']) ) {
          update_user_meta($user_id, 'comidas_diarias', sanitize_text_field($_POST['comidas_diarias']));
      }
      
      // Procesar restricciones según la opción seleccionada
      if ( isset($_POST['tiene_restricciones']) ) {
          $tiene_restricciones = sanitize_text_field($_POST['tiene_restricciones']);
          if ( $tiene_restricciones === 'yes' ) {
              // Si el usuario tiene restricciones, se procesan los checkboxes
              if ( isset($_POST['restricciones']) && is_array($_POST['restricciones']) ) {
                  foreach ($_POST['restricciones'] as $rol_slug) {
                      if ( ! in_array($rol_slug, $user->roles) ) {
                          $user->add_role($rol_slug);
                      }
                  }
              }
          } elseif ( $tiene_restricciones === 'no' ) {
                // Si el usuario indica que no tiene restricciones,
                // eliminamos cualquier rol de restricciones asignado y aplicamos el rol "ninguna"
                $opciones_restricciones = role_options('restricciones');
                unset($opciones_restricciones['ninguna']);
              
                foreach ($opciones_restricciones as $rol_slug) {
                    if ( in_array($rol_slug, $user->roles) ) {
                        $user->remove_role($rol_slug);
                    }
                }
                if ( ! in_array('ninguna', $user->roles) ) {
                    $user->add_role('ninguna');
                }
                update_user_meta($user_id, 'cliente_restricciones', 'ninguna');
          }
      }
      
      // Procesar objetivo
      if ( isset($_POST['objetivo']) ) {
          $nuevo_objetivo = sanitize_text_field($_POST['objetivo']);
          if ( ! in_array($nuevo_objetivo, $user->roles) ) {
              $user->add_role($nuevo_objetivo);
          }
      }
      // Procesar preferencias
      if ( isset($_POST['preferencias']) ) {
          $nueva_preferencia = sanitize_text_field($_POST['preferencias']);
          if ( ! in_array($nueva_preferencia, $user->roles) ) {
              $user->add_role($nueva_preferencia);
          }
      }
      // Recargar la página para reflejar los cambios
      echo '<meta http-equiv="refresh" content="0">';
      exit;
  }
}

function generate_options_group($type, $field_name, $options, $label_text, $image = false) {
  ob_start();
  echo "<div class=\"option-group\"><strong class=\"label-title\">{$label_text}</strong>";
  $plugin_url = plugin_dir_url( dirname(__FILE__) );
  $text_size = ($image) ? '' : ' big-text';
  $field_name = ($type == 'checkbox') ? $field_name . '[]' : $field_name;
  foreach ($options as $slug => $nombre) {
      ?>
      <label class="custom-<?php echo $type; ?>">
          <input type="<?php echo $type; ?>" name="<?php echo esc_attr($field_name); ?>" value="<?php echo esc_attr($slug); ?>">
          <div class="option-container">
              <?php if($image): ?>
                  <div class="option-image">
                      <!-- Aquí reemplaza la URL con la imagen correspondiente a cada opción -->
                      <img src="<?php echo $plugin_url . 'assets/images/svg/' . $slug . '.svg' ?>" alt="<?php echo esc_attr($nombre); ?>">
                  </div>
              <?php endif; ?>
              <div class="option-text <?php echo $text_size; ?>"><?php echo esc_html($nombre); ?></div>
          </div>
      </label>
      <?php
  }
  echo "</div>";
  return ob_get_clean();
}

// Función para generar un grupo de checkboxes
function generate_input_form($type, $field_name,  $label_text, $campos_faltantes, $options = array(), $image = false) {
    $first = (reset($campos_faltantes) == $field_name) ? true : false;
    $last  = (end($campos_faltantes) == $field_name) ? true : false;

    $active_class = ($first) ? ' active ': '';

    ob_start();
    if($type == 'checkbox' || $type == 'radio') {
        if(!empty($options)) {
            ?>
            <div class="form-step <?php echo $active_class; ?>">
                <div class="step-container">
                    <?php echo step_top($campos_faltantes, $field_name);?>
                    <?php
                        echo generate_options_group($type, $field_name, $options, $label_text, $image);
                        echo form_nav($first, $last); 
                    ?>
                </div>
            </div>
        

        <?php
        }
    }
    if($type == 'number' || $type == 'text') {
        ?>
            <div class="form-step <?php echo $active_class; ?>">
              <div class="step-container">
                  <?php echo step_top($campos_faltantes, $field_name); ?>
                  <div class="text-group">
                      <label class="label-title"><?php echo $label_text; ?></label>
                      <input type="<?php echo $type; ?>" name="<?php echo $field_name; ?>">
                  </div>
                  <?php
                      echo form_nav($first, $last); 
                  ?>
              </div>
          </div>
        <?php
    }
    return ob_get_clean();
}

function form_nav($first, $last ) {

  ob_start();
  ?>
      <div class="form-nav">
          <?php if(!$first): ?>
              <button type="button" class="prev-btn">Anterior</button>
          <?php endif; ?>
          <?php if($last): ?>
              <button type="submit" name="guardar_datos_plan" class="button">Guardar y generar plan</button>
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
    echo   "<p>{$step} / {$total}</p>";
    echo '</div>';
    return ob_get_clean();
}
