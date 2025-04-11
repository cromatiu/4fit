<?php 
function ajax_cargar_plan_usuario() {
    $user = wp_get_current_user();
    $user_id = $user->ID;
    
    // Recoger los metadatos
    $peso             = get_user_meta($user_id, 'cliente_peso', true);
    $altura           = get_user_meta($user_id, 'cliente_altura', true);
    $comidas_diarias  = get_user_meta($user_id, 'comidas_diarias', true);
    // Si se envía un parámetro "dia", úsalo; de lo contrario, usar el día actual.
    $dia              = isset($_GET['dia']) ? intval($_GET['dia']) : date('j');

    // Mapear roles a objetivo y preferencias
    $roles = $user->roles;
    $objetivo = '';
    $preferencias = '';
    
    foreach ($roles as $rol) {
        if (in_array($rol, ['perdidapeso', 'mantenimiento', 'ganancia'])) {
            $objetivo = get_role_name_from_slug($rol);
        }
        if (in_array($rol, ['vegano', 'vegetariano', 'omnivoro', 'pescetariano'])) {
            $preferencias = get_role_name_from_slug($rol);
        }
        if (in_array($rol, ['sinlactosa', 'celiaco', 'sinpescado'])) {
            $restricciones = get_role_name_from_slug($rol);
        }
    }

    // Verificar que existan los datos necesarios
    if ( empty($peso) || empty($altura) || empty($comidas_diarias) ) {
        echo '<p>⚠️ Faltan datos en tu perfil para generar el plan.</p>';
        wp_die();
    }

    // Llamar a la función que obtiene el plan (esta función ya maneja la consulta a la base de datos y a la API)
    $plan = obtener_plan_alimentacion($peso, $altura, $objetivo, $preferencias, $restricciones, $comidas_diarias, $dia);
    $plan = is_string($plan) ? json_decode($plan, true) : $plan;
    
    if (!is_array($plan) || empty($plan['comidas'])) {
        echo '<p>⚠️ No se pudo cargar tu plan de alimentación.</p>';
        wp_die();
    }

    // Renderizar el plan en HTML
    ob_start();
    echo "<div class='plan-personalizado'>";
    echo "<h2>Día {$plan['dia']} - Tu plan personalizado</h2>";
    
    foreach ($plan['comidas'] as $tipo => $comida) {
        echo "<h3>" . ucfirst($tipo) . ": " . esc_html($comida['plato']) . "</h3>";
        echo "<div class=\"text-content\">";
        echo "<strong>Ingredientes:</strong><ul>";
        foreach ($comida['ingredientes'] as $ing) {
            echo "<li><i aria-hidden=\"true\" class=\"icon icon-check_circle\"></i> " . esc_html($ing) . "</li>";
        }
        echo "</ul>";
        echo "<p><strong>Preparación:</strong> " . esc_html($comida['preparacion']) . "</p>";
        echo "</div>";
    }
    
    echo "</div>";
    $html = ob_get_clean();
    echo $html;
    wp_die();
}
add_action('wp_ajax_cargar_plan_usuario', 'ajax_cargar_plan_usuario');


function shortcode_plan_personalizado() {
    $user = wp_get_current_user();
    $user_id = $user->ID;

    // Recoger posibles valores
    $peso            = get_user_meta($user_id, 'cliente_peso', true);
    $altura          = get_user_meta($user_id, 'cliente_altura', true);
    $comidas_diarias = get_user_meta($user_id, 'comidas_diarias', true);
    
    // Opciones para roles
    $opciones_objetivo = array(
        'perdidapeso'   => 'Pérdida de peso',
        'mantenimiento' => 'Mantenimiento',
        'ganancia'      => 'Ganancia muscular'
    );
    $opciones_preferencias = array(
        'vegano'        => 'Vegano',
        'vegetariano'   => 'Vegetariano',
        'omnivoro'      => 'Omnívoro',
        //'cetogenico'    => 'Cetogénico',
        'pescetariano'  => 'Pescetariano'
    );
    $opciones_restricciones = array(
        'sinlactosa' => 'Sin lactosa',
        'celiaco'    => 'Celiaco',
        'sinpescado' => 'Sin pescado'
    );

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
        return render_form_datos_plan($campos_faltantes, $opciones_objetivo, $opciones_preferencias, $opciones_restricciones);
    }

    // Mostrar selector de fecha (calendario) y contenedor para el plan con spinner
    ob_start();
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
    <script>
    document.addEventListener("DOMContentLoaded", function(){
        // Función para cargar el plan vía AJAX
        function cargarPlan(dia) {
            document.getElementById("plan-container").innerHTML = '<div class="spiner-container"><div class="spinner" id="spinner"></div><p>Cargando tu plan de alimentación...</p></div>';
            fetch("<?php echo admin_url('admin-ajax.php'); ?>?action=cargar_plan_usuario&dia=" + dia)
            .then(response => response.text())
            .then(html => {
                document.getElementById("plan-container").innerHTML = html;
                document.getElementById("plan-container").classList.add("fade-in");
            })
            .catch(error => {
                document.getElementById("plan-container").innerHTML = "<p>Error al cargar el plan.</p>";
                console.error("Error:", error);
            });
        }

        // Inicializar Flatpickr
        flatpickr("#plan-day-picker", {
            inline: true,
            altFormat: "d-m-Y", // Formato visible: día-mes-año
            dateFormat: "Y-m-d",
            minDate: "today",
            maxDate: new Date().fp_incr(7), // 7 días desde hoy
            defaultDate: "today",
            locale: "es", // Traduce al español
            onChange: function(selectedDates, dateStr, instance) {
                var d = new Date(dateStr);
                var dia = d.getDate();
                cargarPlan(dia);
                document.getElementById("plan-container").scrollIntoView({ behavior: "smooth" });
            }
        });

        // Cargar plan al inicio con la fecha predeterminada (hoy)
        var today = document.getElementById("plan-day-picker").value;
        var d = new Date(today);
        cargarPlan(d.getDate());
        
    });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('plan_personalizado', 'shortcode_plan_personalizado');


function render_form_datos_plan($campos_faltantes, $opciones_objetivo, $opciones_preferencias, $opciones_restricciones) {

      ob_start();
    ?>
    <form id="multiStepForm" method="post">
        
        <h3>Completa tu información para generar el plan:</h3>
        <?php if ( in_array('cliente_peso', $campos_faltantes) ) : ?>
            <div class="form-step active">
                <div class="step-container">
                    <div class="top"></div>
                    <div class="text-group">
                        <label class="label-title">¿Cuál es tu peso actual? (kg)</label>
                        <input type="number" maxlength="3" name="cliente_peso" required oninput="this.value = this.value.slice(0,3)">
                    </div>
                    <?php
                    echo form_nav($campos_faltantes, 'cliente_peso',true); 
                    ?>
                </div>
            </div>
        <?php endif; ?>
        <?php if ( in_array('cliente_altura', $campos_faltantes) ) : ?>
            <div class="form-step">
                <div class="step-container">
                    <div class="top"></div>
                    <div class="text-group">
                        <label class="label-title">¿Cuál es tu estatura? (cm)</label>
                        <input type="number" maxlength="3" name="cliente_altura" required oninput="this.value = this.value.slice(0,3)">
                    </div>
                    <?php
                        echo form_nav($campos_faltantes, 'cliente_altura',true); 
                    ?>
                </div>
            </div>
        <?php endif; ?>
        <?php if ( in_array('comidas_diarias', $campos_faltantes) ) : ?>
            <div class="form-step">
                <div class="step-container">
                    <div class="top"></div>
                    <?php 
                    // Por ejemplo, si deseas usar radiobotones para la cantidad
                    $opciones_comidas = array (
                        '1' => 1,
                        '2' => 2,
                        '3' => 3
                    );
                    echo generate_radio_group('comidas_diarias', $opciones_comidas, '¿Cuántas comidas diarias prefieres hacer?'); 
                    ?>
                    <?php
                        echo form_nav($campos_faltantes, 'comidas_diarias',true); 
                    ?>
                </div>
            </div>
        <?php endif; ?>
        <?php if ( in_array('objetivo', $campos_faltantes) ) : ?>
            <div class="form-step">
                <div class="step-container">
                    <div class="top"></div>
                    <?php echo generate_radio_group('objetivo_seleccionado', $opciones_objetivo, '¿Cuál es tu principal objetivo nutricional?'); ?>
                    <?php
                        echo form_nav($campos_faltantes, 'objetivo',true); 
                    ?>
                </div>
            </div>
        <?php endif; ?>
        <?php if ( in_array('preferencias', $campos_faltantes) ) : ?>
            <div class="form-step">
                <div class="step-container">
                    <div class="top"></div>
                    <?php echo generate_radio_group('preferencias_seleccionado', $opciones_preferencias, '¿Qué tipo de alimentación prefieres?', true); ?>
                    <?php
                        echo form_nav($campos_faltantes, 'preferencias',true); 
                    ?>
                </div>
            </div>
        <?php endif; ?>
        <?php if ( in_array('restricciones', $campos_faltantes) ) : ?>
            <div class="form-step">
                <div class="step-container">
                    <div class="top"></div>
                    <div class="option-group">
                        <label class="label-title">¿Tienes restricciones alimentarias?</label><br>
                        <label class="custom-radio">
                            <input type="radio" name="tiene_restricciones" value="yes" required>
                            <div class="option-container">
                                <div class="option-text big-text">Sí</div>
                            </div>
                        </label>
                        <label class="custom-radio">
                            <input type="radio" name="tiene_restricciones" value="no" required>
                            <div class="option-container">
                                <div class="option-text big-text">No</div>
                            </div>
                        </label>
                    </div>
                    <div id="restricciones_container" style="display: none;">
                        <?php echo generate_checkbox_group('restricciones_seleccionadas', $opciones_restricciones, 'Selecciona tus restricciones', true); ?>
                    </div>
                    <?php
                        echo form_nav($campos_faltantes, 'restricciones',true); 
                    ?>
                </div>
            </div>
        <?php endif; ?>


    </form>
    <script>
    /*
    document.addEventListener('DOMContentLoaded', function(){
        var radios = document.getElementsByName('tiene_restricciones');
        var container = document.getElementById('restricciones_container');
        function toggleRestricciones() {
            for (var i = 0; i < radios.length; i++){
                if (radios[i].checked) {
                    container.style.display = (radios[i].value === 'yes') ? 'block' : 'none';
                }
            }
        }
        for (var i = 0; i < radios.length; i++){
            radios[i].addEventListener('change', toggleRestricciones);
        }
        const input = document.getElementById('numero');
        input.addEventListener('input', () => {
            if (input.value.length > 3) {
            input.value = input.value.slice(0, 3);
            }
        });

    });
    */
    </script>
    <?php
    return ob_get_clean();
}

function procesar_datos_plan($user) {
    $user_id = $user->ID;
    
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
                if ( isset($_POST['restricciones_seleccionadas']) && is_array($_POST['restricciones_seleccionadas']) ) {
                    foreach ($_POST['restricciones_seleccionadas'] as $rol_slug) {
                        if ( ! in_array($rol_slug, $user->roles) ) {
                            $user->add_role($rol_slug);
                        }
                    }
                    update_user_meta($user_id, 'cliente_restricciones', implode(', ', $_POST['restricciones_seleccionadas']));
                }
            } elseif ( $tiene_restricciones === 'no' ) {
                // Si el usuario indica que no tiene restricciones,
                // eliminamos cualquier rol de restricciones asignado y aplicamos el rol "ninguna"
                $opciones_restricciones = array('sinlactosa', 'celiaco', 'sinpescado');
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
        if ( isset($_POST['objetivo_seleccionado']) ) {
            $nuevo_objetivo = sanitize_text_field($_POST['objetivo_seleccionado']);
            if ( ! in_array($nuevo_objetivo, $user->roles) ) {
                $user->add_role($nuevo_objetivo);
            }
        }
        // Procesar preferencias
        if ( isset($_POST['preferencias_seleccionado']) ) {
            $nueva_preferencia = sanitize_text_field($_POST['preferencias_seleccionado']);
            if ( ! in_array($nueva_preferencia, $user->roles) ) {
                $user->add_role($nueva_preferencia);
            }
        }
        // Recargar la página para reflejar los cambios
        echo '<meta http-equiv="refresh" content="0">';
        exit;
    }
}

// Función para generar un grupo de radiobotones
function generate_radio_group($field_name, $options, $label_text, $image = false) {
    ob_start();
    echo "<div class=\"option-group\"><strong class=\"label-title\">{$label_text}</strong>";
    $plugin_url = plugin_dir_url( dirname(__FILE__) );
    $text_size = ($image) ? '' : ' big-text';
    foreach ($options as $slug => $nombre) {
        ?>
        <label class="custom-radio">
            <input type="radio" name="<?php echo esc_attr($field_name); ?>" value="<?php echo esc_attr($slug); ?>" required>
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
function generate_checkbox_group($field_name, $options, $label_text, $image = false) {
    $plugin_url = plugin_dir_url( dirname(__FILE__) );
    $text_size = ($image) ? '' : ' big-text';
    ob_start();
    echo "<div class=\"option-group\"><strong class=\"label-title\">{$label_text}</strong>";
    foreach ($options as $slug => $nombre) {
        ?>
        <label class="custom-checkbox">
            <input type="checkbox" name="<?php echo esc_attr($field_name); ?>[]" value="<?php echo esc_attr($slug); ?>">
            <div class="option-container">
                <?php if($image): ?>
                <div class="option-image">
                    <!-- Aquí reemplaza la URL con la imagen correspondiente -->
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

function form_nav($campos_faltantes, $value, $first = false, ) {
    $last = end($campos_faltantes) == $value ? true : false;
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