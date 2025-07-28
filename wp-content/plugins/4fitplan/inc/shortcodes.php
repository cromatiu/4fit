<?php 

function shortcode_plan_personalizado() {
    $user = wp_get_current_user();
  
    // Crear array de campos faltantes (incluyendo roles)
    $campos_faltantes = uf_get_user_fields( $user, ['nutrition'], false );

    // Procesar el formulario si se envía
    uf_process_user_data_form($user);

    // Si faltan datos, mostrar el formulario
    if ( ! empty($campos_faltantes) ) {
        return render_form_nutrition_plan($campos_faltantes);
    }

    // Mostrar selector de fecha (calendario) y contenedor para el plan con spinner
    ob_start();
    echo display_nutrition_data($user);
    ?>
    <div id="plan-container">
        <div class="spinner-container">
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

function shortcode_recetas() {
    $user = wp_get_current_user();
    $user_id = $user->ID;



    

    // Mostrar selector de fecha (calendario) y contenedor para el plan con spinner
    ob_start();
    echo display_recetas_data($user);
    ?>
   
    <div id="recetas-container">
        <div class="spinner-container">
            <div class="spinner"></div>
            <p>Cargando tus recetas...</p>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('recetas', 'shortcode_recetas');

/**
 * Shortcode [plan_ejercicio_semanal_ajax]:
 * - Si faltan datos de ejercicio, muestra el formulario.
 * - Si no, carga vía AJAX el plan semanal con spinner.
 */
function shortcode_plan_ejercicio_semanal_ajax() {
    $user = wp_get_current_user();
    $user_id = $user->ID;

    // 1) Procesar cualquier envío pendiente
    uf_process_user_data_form( $user );


    // 2) Detectar qué campos faltan
    $campos_faltantes = [];
    // Lugar de entrenamiento


    

    $campos_faltantes = uf_get_user_fields( $user, ['exercise'], false );

    // 3) Si faltan, mostramos el formulario de ejercicio
    if ( ! empty( $campos_faltantes ) ) {
        return render_form_exercise_plan( $campos_faltantes );
    }

    // 4) Si ya están todos los datos, montamos el contenedor + spinner + JS
    ob_start();
    echo display_exercise_data( $user );
    ?>
    <div id="plan-ejercicio-semanal-container" class="plan-ejercicio-semanal-container">
        <div class="spinner-container">
            <div class="spinner"></div>
            <p>Cargando tu plan de alimentación...</p>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'plan_ejercicio_semanal', 'shortcode_plan_ejercicio_semanal_ajax' );

// -----------------------
// 2) shortcodes.php
// -----------------------

/**
 * Shortcode [mensaje_motivacional] 
 * Muestra un spinner y carga vía AJAX el mensaje motivacional.
 */
function shortcode_mensaje_motivacional() {

    $user = wp_get_current_user();

    // Procesar posible POST (si usas un form distinto)
    uf_process_user_data_form($user);

    ob_start(); ?>
    <div id="mensaje-motivacional-container" class="mensaje-motivacional-container">
        <div class="spinner-container">
            <div class="spinner"></div>
            <p>Cargando tu mensaje...</p>
        </div>
    </div>

    <?php
    return ob_get_clean();
}
add_shortcode( 'mensaje_motivacional', 'shortcode_mensaje_motivacional' );
// Shortcode [metricas_chart]
function metricas_chart_shortcode() {
    // Output del canvas
    ob_start();
    $user_id = get_current_user_id();
    $count = get_number_of_revisions($user_id);
    
    ?>

    <h2>Número de revisiones <?php echo var_export($count, true); ?></h2>
    <div class="progress-container">
        <h2>Datos de tu progreso</h2>
        <div id="metricasCharts">
            <div class="spinner-container">
                <div class="spinner"></div>
                <p>Cargando...</p>
            </div>
        </div>
        <h2>Tu progreso en imagenes</h2>
        <div id="metricasSlider" class="metricas-slider">
            <div class="spinner-container">
                <div class="spinner"></div>
                <p>Cargando...</p>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('metricas_chart', 'metricas_chart_shortcode');

// 7) Shortcode [daily_metrics]
function fm_daily_metrics_shortcode() {
    if ( ! is_user_logged_in() ) {
        return '<p>Por favor inicia sesión para ver tus métricas.</p>';
    }

    $user  = get_current_user_id();

    $yesterday = date('Y-m-d', strtotime('-1 day'));

    // Si no ha guardado aún datos para HOY, muestro el formulario para HOY
    if ( ! fm_has_metrics_for_date( $user) ) {
        ob_start();
        ?>
        <form id="fm-metrics-form" class="js-validate-form" novalidate>
            <h3>Registra tus datos de ayer</h3>
            <input type="hidden" name="date" value="<?php echo esc_attr( $yesterday ); ?>">
            <div class="fields-content">
            <div class="input-group">
                <label>
                    Pasos diarios:
                    <input
                    type="text"
                    name="steps"
                    inputmode="numeric"
                    pattern="\d{1,5}"
                    maxlength="5"
                    required
                    title="Pasos diarios debe tener máximo 5 dígitos."
                    placeholder="Ej: 12345"
                    >
                </label>
                <div class="error-message"></div>
            </div>

            <div class="input-group">
                <label>
                    Agua bebida (ml):
                    <input
                    type="text"
                    name="water"
                    inputmode="numeric"
                    pattern="\d{3,4}"
                    maxlength="4"
                    required
                    title="Agua bebida debe tener entre 3 y 4 dígitos."
                    placeholder="Ej: 750"
                    >
                </label>
                <div class="error-message"></div>
            </div>

            <div class="input-group">
                <label>
                    Tiempo de entrenamiento (min):
                    <input
                    type="text"
                    name="training_time"
                    inputmode="numeric"
                    pattern="\d{1,3}"
                    maxlength="3"
                    required
                    title="Tiempo de entrenamiento debe tener máximo 3 dígitos."
                    placeholder="Ej: 45"
                    >
                </label>
                <div class="error-message"></div>
            </div>
            <div class="form-group">

                <fieldset class="stars-fieldset">
                    <legend>Seguimiento de dieta:</legend>
                    <input type="radio" id="star5" name="diet_rating" value="5" required title="Debes seleccionar un valor para seguimiento de dieta.">
                    <label for="star5"><span>★</span></label>
                    
                    <input type="radio" id="star4" name="diet_rating" value="4">
                    <label for="star4"><span>★</span></label>
                    
                    <input type="radio" id="star3" name="diet_rating" value="3">
                    <label for="star3"><span>★</span></label>
                    
                    <input type="radio" id="star2" name="diet_rating" value="2">
                    <label for="star2"><span>★</span></label>
                    
                    <input type="radio" id="star1" name="diet_rating" value="1">
                    <label for="star1"><span>★</span></label>
                </fieldset>
                <div class="error-message"></div>
            </div>
            <div class="button-cont">
                <button type="submit">Guardar datos</button>
            </div>
            
        </div>
        </form>
        <div id="fm-metrics-message"></div>
        <?php
        return ob_get_clean();
    }

    // Si YA hay registros de HOY, muestro el contenedor para los charts
    return '<div id="fm-metrics-charts"></div>';
}
add_shortcode( 'daily_metrics', 'fm_daily_metrics_shortcode' );


add_shortcode( 'user_all_fields', 'uf_customer_profile');
function uf_customer_profile(){
    $user = wp_get_current_user();
    // Consigue TODOS los valores:
    $datos = uf_get_user_fields( $user, ['personal','nutrition','exercise'], true );
    // Renderízalos:
    ob_start();
    echo uf_render_user_fields( $datos, $user);
    echo display_suscription_data($user);
    return ob_get_clean();
}


add_shortcode('affiliates_page', 'my_shortcode_affiliates_page');

function my_shortcode_affiliates_page() {
    if ( ! function_exists('is_plugin_active') ) {
        include_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    if ( ! is_plugin_active('affiliates-manager/boot-strap.php') ) {
        return '<p>El sistema de afiliados no está disponible actualmente.</p>';
    }

    require_once WP_PLUGIN_DIR . '/affiliates-manager/source/Data/DataAccess.php';

    $db        = new WPAM_Data_DataAccess();
    $user      = wp_get_current_user();
    $affiliate = $db->getAffiliateRepository()->loadByUserId($user->ID);

    if ( ! $affiliate || ! ( $affiliate->isApproved() || $affiliate->isActive() ) ) {
        return wpam_render_affiliate_profile_data();
    }

    $tabs = [
        'overview' => [
            'label'    => 'Resumen',
            'sub'      => 'overview',
            'callback' => function () use ( $affiliate ) {
                return wpam_render_affiliate_tab_dynamic('overview', $affiliate);
            }
        ],
        'payments' => [
            'label'    => 'Pagos',
            'sub'      => 'payments',
            'callback' => function () use ( $affiliate ) {
                return wpam_render_affiliate_tab_dynamic('payments', $affiliate);
            }
        ],
        'custom' => [
            'label'    => 'Links para nuevos clientes',
            'callback' => 'wpam_render_affiliate_profile_links'
        ],
        'profile_custom' => [
            'label'    => 'Editar mi perfil',
            'callback' => 'wpam_render_affiliate_profile_data'
        ],
        'my_clients' => [
            'label'    => 'Mis clientes',
            'callback' => 'render_clients_by_distributor'
        ],
    ];

    $current = isset($_GET['sub']) && isset($tabs[$_GET['sub']]) ? $_GET['sub'] : 'overview';

    $output = '<div class="affiliate-tabs-menu"><ul>';
    foreach ($tabs as $key => $tab) {
        $isActive = ($key === $current);
        $aria     = $isActive ? ' aria-current="page"' : '';
        if ( $isActive ) {
            $output .= '<li class="active-tab"><span' . $aria . '>' . esc_html($tab['label']) . '</span></li>';
        } else {
            $output .= '<li><a href="?sub=' . esc_attr($key) . '"' . $aria . '>' . esc_html($tab['label']) . '</a></li>';
        }
    }
    $output .= '</ul></div>';

    $output .= '<div class="affiliate-tab-content">';
    if ( isset($tabs[$current]['callback']) && is_callable($tabs[$current]['callback']) ) {
        $output .= call_user_func($tabs[$current]['callback']);
    } else {
        $output .= '<p>Sección no válida.</p>';
    }
    $output .= '</div>';

    return $output;
}
function wpam_render_affiliate_tab_dynamic($sub, $affiliate) {
	require_once WP_PLUGIN_DIR . '/affiliates-manager/source/Data/DataAccess.php';
	require_once WP_PLUGIN_DIR . '/affiliates-manager/source/Pages/AffiliatesHome.php';
	require_once WP_PLUGIN_DIR . '/affiliates-manager/source/Pages/TemplateResponse.php';

	$db   = new WPAM_Data_DataAccess();
	$view = null;
	$page = new WPAM_Pages_AffiliatesHome($db, $view);

	$_REQUEST['sub'] = $sub;
	$response = $page->doAffiliateControlPanel($affiliate, $_REQUEST);

	global $tabs;
	$response->viewData['navigation'] = [];
	foreach ($tabs as $key => $tab) {
		if (isset($tab['sub'])) {
			$response->viewData['navigation'][] = [$tab['label'], add_query_arg('sub', $key)];
		}
	}

	$html = $response->render();
	//$html = preg_replace('/<div id="aff-controls".*?<\/div>\s*/s', '', $html);
    /*
	if ($sub === 'payments') {
		$html = preg_replace([
			'/<div[^>]*class="[^"]*\bdaterange-form\b[^"]*"[^>]*>.*?<\/div>/s',
			'/<div[^>]*class="[^"]*\bwpam-daterange-action-buttons\b[^"]*"[^>]*>.*?<\/div>/s'
		], '', $html);
	}
    */
	return $html;
}

function fourfit_enqueue_affiliate_scripts() {
    if ( is_page('afiliados') || is_singular() ) {
        wp_enqueue_script('jquery-ui-datepicker');
        wp_enqueue_style('jquery-ui-css', 'https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css');
    }
}
add_action('wp_enqueue_scripts', 'fourfit_enqueue_affiliate_scripts');

function affiliates_edit_page() {
    return wpam_render_affiliate_profile_data();
}
add_shortcode('affiliates_edit_page', 'affiliates_edit_page');

add_shortcode('formulario_alta_usuario', function() {
    ob_start();
     $user = wp_get_current_user();
    $status = get_user_suscription_status($user) === 'active';
    echo '<div class="sus-hero">';
    if (is_user_logged_in()) {
        $user = wp_get_current_user();
        $status = get_user_suscription_status($user);
        $message = ($status !== 'none') ? 'Renueva' : 'Crea';


        if ($status === 'active') {
            echo '<h1>Ya tienes tu suscripción</h1>';
            echo '<p class="message">Puedes disfrutar de tu contenido 4fit</p>';
            echo '<a href="' . esc_url(home_url()) . '" class="elementor-button">Ir al contenido</a>';
            echo '</div>';
            return ob_get_clean();
        } else {
            if (function_exists('WC') && WC()->cart instanceof WC_Cart) {
                $cart = WC()->cart;
                if (count($cart->get_cart()) > 0) {
                    echo '<h1>Estás a un paso de completar tu suscripción</h1>';
                    echo '<p class="message">Completa tu pedido</p>';
                    echo '<a href="' . wc_get_cart_url() . '" class="elementor-button">Ir al carrito</a>';
                    echo '</div>';
                    return ob_get_clean();
                }
            }
            if (in_array('distribuidor', $user->roles)) {
                $url = wc_get_cart_url() . '?add-to-cart=77647';
                echo '<h1>' . esc_html($message) . ' tu suscripción como distribuidor</h1>';
                echo '<p class="message">Estamos muy contentos de que siguas con nosotros</p>';
                echo '<a href="' . esc_url($url) . '" class="elementor-button">Comprar suscripción</a>';
            } elseif (in_array('suscripcin_4fitplan', $user->roles)) {
                $url = wc_get_cart_url() . '?add-to-cart=77642';
                echo '<h1>' . esc_html($message) . ' tu suscripción</h1>';
                echo '<p class="message">Estamos muy contentos de que siguas con nosotros</p>';
                echo '<a href="' . esc_url($url) . '" class="elementor-button">Comprar suscripción</a>';
            } else {
                echo '<p>No tienes roles válidos.</p>';
            }
            echo '</div>';
            return ob_get_clean();
        }
    }

    $tipo = sanitize_text_field($_GET['tipo'] ?? '');
    $redirect_id = $tipo === 'distribuidor' ? 77647 : ($tipo === 'suscripcin_4fitplan' ? 77642 : 0);

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_nonce']) && wp_verify_nonce($_POST['register_nonce'], 'register_user')) {
        $nombre   = sanitize_text_field($_POST['nombre']);
        $email    = sanitize_email($_POST['email']);
        $telefono = sanitize_text_field($_POST['telefono']);
        $password = $_POST['password'];
        $rol      = sanitize_text_field($_POST['rol']);

        if (!in_array($rol, ['distribuidor', 'suscripcin_4fitplan'])) {
            echo '<p class="error">Rol no válido.</p>';
        } else {
            $user_id = wp_create_user($email, $password, $email);

            if (!is_wp_error($user_id)) {
                wp_update_user([
                    'ID' => $user_id,
                    'display_name' => $nombre,
                ]);

                $user = new WP_User($user_id);
                $user->add_role($rol);

                update_user_meta($user_id, 'billing_first_name', $nombre);
                update_user_meta($user_id, 'billing_phone', $telefono);
                update_user_meta($user_id, 'billing_email', $email);

                // 🔗 Guardar ID del distribuidor si viene desde wpam_id
                if (!empty($_GET['wpam_id'])) {
                $affiliate_id = intval($_GET['wpam_id']);
                $distribuidor_id = get_user_id_from_affiliate_id($affiliate_id);

                if ($distribuidor_id) {
               
                    if ($rol === 'distribuidor') {
                        update_user_meta($user_id, 'id_distribuidor', $user_id);
                        // Generar URL de WhatsApp Web para el distribuidor y guardar
                        $numero = preg_replace('/[^0-9]/', '', $telefono); // limpio por si vienen espacios o signos
                        $whatsapp_url = 'https://wa.me/' . $numero;
                        update_user_meta($user_id, 'whatsappweb', $whatsapp_url);
                    }
                    
                    if ($rol === 'suscripcin_4fitplan') {
                        update_user_meta($user_id, 'id_distribuidor', $distribuidor_id);
                        // Copiar whatsappweb del distribuidor al nuevo usuario
                        $whatsapp_distribuidor = get_user_meta($distribuidor_id, 'whatsappweb', true);
                        if ($whatsapp_distribuidor) {
                            update_user_meta($user_id, 'whatsappweb', $whatsapp_distribuidor);
                        }
                    }
                }
            }

                wp_set_current_user($user_id);
                wp_set_auth_cookie($user_id);
                do_action('wp_login', $email, $user);

                wp_redirect(wc_get_cart_url() . '?add-to-cart=' . $redirect_id);
                exit;
            } else {
                echo '<h1>Ha habido un error con alta</h1>';
                echo '<p class="message">Consulta con nuestro soporte, Error: ' . esc_html($user_id->get_error_message()) . '</p>';
                echo '<a href="#soporte" class="elementor-button">Enviar reporte</a>';
            }
        }
    } else {
        if(!empty($tipo)) {
            if($tipo == 'suscripcin_4fitplan') {
                echo '<h1>Transforma tu vida en solo unos pasos</h1>';
                echo '<p class="message">Empieza hoy con un plan completo de alimentación, ejercicio y motivación adaptado a ti rellenando el siguiente formulario.</p>';
            } else {
                echo '<h1>Comienza un nuevo proyecto</h1>';
                echo '<p class="message">Ayuda a tus clientes y obtén beneficios con nuestra potente herramienta.</p>';

            }
            
        ?>
   
        <form method="post" class="js-validate-form" novalidate>
            <div class="input-group">
                <label>Nombre:<br><input type="text" name="nombre" required title="Necesitamos saber tu nombre"></label>
                <div class="error-message"></div>
            </div>

            <div class="input-group">
                <label>Correo electrónico:<br><input type="email" name="email" title="Introduce un email válido" required></label>
                <div class="error-message"></div>
            </div>
            <div class="input-group">
                <label>Teléfono:<br><input type="tel" name="telefono" pattern="[0-9]{9}"  title="Introduce un teléfono de 9 dígitos (solo números)" inputmode="numeric" required></label>
                <div class="error-message"></div>
            </div>
            <div class="input-group">
                <label>Contraseña:<br><input type="password" name="password" minlength="8" pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[-_\*\+\,\.])[A-Za-z\d\-\_\*\+\,\.]{8,}" title="Mínimo 8 caracteres, debe incluir al menos una mayúscula, una minúscula, un número y uno de estos símbolos: - _ * + . ," required></label>
                <div class="error-message"></div>
            </div>
            <input type="hidden" name="rol" value="<?php echo esc_attr($tipo); ?>">
            <?php wp_nonce_field('register_user', 'register_nonce'); ?>
            <div class="button-cont"><button type="submit" class="elementor-button">Registrarme</button></div>
        </form>
    

        <?php
        }else {
            echo '<h1>Enlace no válido</h1>';
            echo '<p class="message">Consulta con nuestro servicio técnico</p>';
            echo '<a href="#soporte" class="elementor-button">Enviar reporte</a>';
        }
    }
    echo '</div>';
    return ob_get_clean();
});
add_shortcode('initial_form', 'initial_form');
function initial_form() {
    $user               = wp_get_current_user();
    uf_process_user_data_form( $user );
    $campos_faltantes   = uf_get_user_fields($user, ['nutrition','exercise', 'personal'], false);
    ob_start();
    if(!empty($campos_faltantes)) {
        echo    uf_render_user_data_form($campos_faltantes);
    } else {
        ?>
            <div class="welcome-message">
                <div class="welcome-cont">
                    <h1>¡Todo listo para empezar!</h1>
                    <p>Estás a un sólo click</p>
                    <a href="<?php echo get_bloginfo('url') ?>" class="elementor-button">Comienza ya</a>
                </div>
            </div>
        <?php
    }
    return ob_get_clean();
}

function shortcode_logout_url( $atts ) {
    $atts = shortcode_atts( array(
        'redirect' => home_url(), // Redirección tras cerrar sesión
    ), $atts, 'logout_url' );

    return esc_url( wp_logout_url( $atts['redirect'] ) );
}
add_shortcode( 'logout_url', 'shortcode_logout_url' );

function shortcode_notificaciones_usuario() {
    if ( ! is_user_logged_in() ) {
        return '';
    }

    $user_id = get_current_user_id();
    $notifications = get_user_notifications( $user_id );

    if ( empty( $notifications ) ) {
        $html = '<div class="user-notifications-empty">';
        $html .= '<p>No tienes notificaciones.</p>';
        $html .= '</div>';
        return $html;
    }

    ob_start();
    echo '<div class="user-notifications">';
    foreach ( $notifications as $n ) {
        $class = 'notification ' . esc_attr( $n['type'] );
        echo '<div class="' . $class . '">';
        if ( ! empty( $n['link'] ) ) {
            echo '<a href="' . esc_url( $n['link'] ) . '">' . esc_html( $n['message'] ) . '</a>';
        } else {
            echo esc_html( $n['message'] );
        }
        echo '</div>';
    }
    echo '</div>';

    return ob_get_clean();
}
add_shortcode( 'notificaciones_usuario', 'shortcode_notificaciones_usuario' );
function shortcode_contador_notificaciones() {
    if ( ! is_user_logged_in() ) {
        return '';
    }

    $user_id = get_current_user_id();
    $notifications = get_user_notifications( $user_id );
    $count = count( $notifications );

    if ( $count === 0 ) {
        return ''; // HTML vacío si no hay notificaciones
    }

    return '<div class="notifications-count">' . esc_html( $count ) . '</div>';
}
add_shortcode( 'contador_notificaciones', 'shortcode_contador_notificaciones' );
