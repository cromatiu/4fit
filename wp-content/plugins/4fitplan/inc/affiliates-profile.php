<?php
function wpam_render_affiliate_profile_data() {
    if ( ! is_user_logged_in() ) {
        return '<p>Debes iniciar sesión para ver esta sección.</p>';
    }

    if ( ! class_exists( 'WC_Customer' ) ) {
        return '<p>WooCommerce no está activo.</p>';
    }

    $user     = wp_get_current_user();
    $user_id  = $user->ID;
    $db       = new WPAM_Data_DataAccess();
    $affiliate = $db->getAffiliateRepository()->loadByUserId($user_id);

    $fields = wpam_get_affiliate_profile_fields();
    $html = '';

    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['wpam_custom_profile_nonce']) && wp_verify_nonce($_POST['wpam_custom_profile_nonce'], 'wpam_update_profile') ) {
        if ( ! $affiliate ) {
            $affiliate = wpam_create_new_affiliate_from_post($user_id, $fields);
            $db->getAffiliateRepository()->insert($affiliate);
            $html .= '<div class="updated"><p><i aria-hidden="true" class="icon icon-check_circle"></i> Registro completado correctamente como afiliado.</p></div>';
        } else {
            wpam_update_affiliate_from_post($user_id, $affiliate, $fields);
            $db->getAffiliateRepository()->update($affiliate);
            $html .= '<div class="updated"><p><i aria-hidden="true" class="icon icon-check_circle"></i> Datos guardados correctamente.</p></div>';
        }
    }

    $values = wpam_get_affiliate_profile_values($user_id, $affiliate, $fields);
    $html .= wpam_render_affiliate_profile_form($affiliate, $fields, $values);

    return $html;
}

function wpam_get_affiliate_profile_fields() {
    return [
        'first_name' => ['label' => 'Nombre', 'required' => true, 'layout' => 'half', 'title' => 'Es necesario introducir tu nombre'],
        'last_name'  => ['label' => 'Apellidos', 'required' => true, 'layout' => 'half', 'title' => 'Es necesario introducir tus apellidos'],
        'company'    => ['label' => 'Empresa', 'required' => false, 'layout' => 'half'],
        'nif'        => ['label' => 'NIF', 'required' => false, 'layout' => 'half'],
        'address_1'  => ['label' => 'Dirección 1', 'required' => true, 'layout' => 'half', 'title' => 'Es necesario introducir la dirección'],
        'address_2'  => ['label' => 'Dirección 2', 'required' => false, 'layout' => 'half'],
        'postcode'   => ['label' => 'Código postal', 'required' => true, 'layout' => 'half', 'title' => 'Es necesario introducir tu código postal'],
        'city'       => ['label' => 'Ciudad', 'required' => true, 'layout' => 'half', 'title' => 'Es necesario introducir tu ciudad'],
        'state'      => ['label' => 'Provincia', 'required' => true, 'layout' => 'half', 'title' => 'Es necesario introducir tu estado o provincia'],
        'country'    => ['label' => 'País', 'required' => true, 'layout' => 'half', 'title' => 'Es necesario introducir tu país', 'inputmode'=> 'numeric', 'pattern'=> '\d{1,5}'],
        'phone'      => ['label' => 'Teléfono', 'required' => true, 'layout' => 'half', 'title' => 'Introduce un teléfono de 9 dígitos (solo números)',  'pattern'=> '[0-9]{9}', 'inputmode'=> 'numeric' ],
        'email'      => ['label' => 'Correo electrónico', 'required' => true, 'layout' => 'half', 'type' => 'email', 'title' => 'Introduce un email válido'],
    ];
}

function wpam_create_new_affiliate_from_post($user_id, $fields) {
    $affiliate = new WPAM_Data_Models_AffiliateModel();
    $affiliate->userId = $user_id;
    foreach ($fields as $key => $_) {
        $value = sanitize_text_field($_POST['billing_' . $key] ?? '');
        update_user_meta($user_id, 'billing_' . $key, $value);
        if ($key === 'first_name') $affiliate->firstName = $value;
        if ($key === 'last_name') $affiliate->lastName = $value;
        if ($key === 'email') $affiliate->email = sanitize_email($value);
    }
    $affiliate->status = 'active';
    $affiliate->dateCreated = time();
    return $affiliate;
}

function wpam_update_affiliate_from_post($user_id, $affiliate, $fields) {
    foreach ($fields as $key => $_) {
        $value = sanitize_text_field($_POST['billing_' . $key] ?? '');
        update_user_meta($user_id, 'billing_' . $key, $value);
    }
    $affiliate->firstName = sanitize_text_field($_POST['billing_first_name']);
    $affiliate->lastName  = sanitize_text_field($_POST['billing_last_name']);
    $affiliate->email     = sanitize_email($_POST['billing_email']);
}

function wpam_get_affiliate_profile_values($user_id, $affiliate, $fields) {
    $customer = new WC_Customer($user_id);
    $values = [];

    foreach ($fields as $key => $_) {
        $val = get_user_meta($user_id, 'billing_' . $key, true);
        if (empty($val)) {
            $getter = "get_billing_{$key}";
            if (method_exists($customer, $getter)) {
                $val = $customer->$getter();
            }
        }
        if (empty($val) && $affiliate) {
            if ($key === 'first_name') $val = $affiliate->firstName;
            elseif ($key === 'last_name') $val = $affiliate->lastName;
            elseif ($key === 'email') $val = $affiliate->email;
        }
        $values[$key] = $val;
    }

    return $values;
}

function wpam_render_affiliate_profile_form($affiliate, $fields, $values) {
    $countries = new WC_Countries();
    $country_options = $countries->get_countries();
    $state_options   = $countries->get_states();

    ob_start();

    echo '<h2>' . ( $affiliate ? 'Editar dirección de facturación' : 'Registro como afiliado' ) . '</h2>';
    echo '<p>Tus datos son importantes para recibir tus pagos</p>';
    echo '<form method="post" class="wpam-profile-form js-validate-form" novalidate>';
    wp_nonce_field('wpam_update_profile', 'wpam_custom_profile_nonce');

    foreach ($fields as $key => $info) {
        $label = $info['label'];
        $required = !empty($info['required']) ? 'required' : '';
        $title = !empty($info['title']) ? 'title="' . $info['title'] . '"' : '';
        $pattern = !empty($info['pattern']) ? 'pattern="' . $info['pattern'] . '"' : '';
        $inputmode = !empty($info['inputmode']) ? 'inputmode="' . $info['inputmode'] . '"' : '';
        $required = !empty($info['required']) ? 'required' : '';
        $required_element = !empty($info['required']) ? '*' : '';
        $type = $info['type'] ?? 'text';
        $value = esc_attr($values[$key]);
        $layout = $info['layout'] ?? 'full';
        $class = $layout === 'half' ? 'form-field half-width' : 'form-field full-width';

        echo "<div class=\"{$class} form-group\">";
        if ($key === 'country') {
            echo "<label>{$label} {$required_element}<br><select name=\"billing_country\" class=\"affiliate-select\" id=\"billing_country\" {$title}  {$required} >";
            foreach ($country_options as $code => $name) {
                $selected = $code === $value ? 'selected' : '';
                echo "<option value=\"{$code}\" {$selected}>{$name}</option>";
            }
            echo "</select></label>";
            if(!empty($required)) {
                echo "<div class=\"error-message\"></div>";
            }
        } elseif ($key === 'state') {
            echo "<label>{$label} {$required_element}<br><select name=\"billing_state\" class=\"affiliate-select\" id=\"billing_state\" {$title} {$required}></select></label>";
            if(!empty($required)) {
                echo "<div class=\"error-message\"></div>";
            }
        } else {
            echo "<label>{$label} {$required_element}<br>";
            echo "<input type=\"{$type}\" name=\"billing_{$key}\" value=\"{$value}\" {$title} {$pattern} {$inputmode}  {$required}>";
            echo "</label>";
            if(!empty($required)) {
                echo "<div class=\"error-message\"></div>";
            }
        }
        echo "</div>";
    }

    echo '<div class="button-content"><button type="submit">Guardar cambios</button></div>';
    echo '</form>';

    return ob_get_clean();
}



function wpam_render_affiliate_profile_links() {
   
    $url_suscriptor = affiliate_url_by_type('suscripcin_4fitplan');
    $url_distribuidor = affiliate_url_by_type('distribuidor');

    ob_start();
    if($url_suscriptor && $url_distribuidor):
    ?>
    <h2>Tu enlace de afiliado</h2>
    <p>Envía este enlace de afiliado a tus clientes o nuevos distribuidores para recibir tu comisión por su compra.</p>
    <div class="copy-group">

        <h3>Enlace para clientes</h3>
        <div class="copy-content">
            
            <input class="copy-input" value="<?php echo esc_url($url_suscriptor); ?>" id="copyClipboard-1" readonly>
            <button class=" copy-link" title="Copiar enlace" data-link="<?php echo esc_url($url_suscriptor); ?>">
                <img src="<?php echo FOURFIT_PLUGIN_URL . '/assets/images/svg/copy.svg'?>" alt="">
            </button>
            <div class="message"><span>Enlace copiado!<span></span></span></div>
        </div>
    </div>
    <div class="copy-group">

        <h3>Enlace para distribuidores</h3>
        <div class="copy-content">
            <input class="copy-input" value="<?php echo esc_url($url_distribuidor); ?>" id="copyClipboard-2" readonly>
            <button class=" copy-link" title="Copiar enlace" data-link="<?php echo esc_url($url_distribuidor); ?>">
                <img src="<?php echo FOURFIT_PLUGIN_URL . '/assets/images/svg/copy.svg'?>" alt="">
            </button>
            <div class="message"><span>Enlace copiado!<span></span></span></div>
        </div>
    </div>
    <?php
    else:

        $url_profile = get_permalink(125343);
        $url_profile = add_query_arg('sub', 'profile_custom', $url_profile);
        ?>
        <h2>
            Necesitas confirmar tus datos de facturación para obtener tus enlaces de afiliado.
        </h2>
        <a href="<?php echo esc_url($url_profile); ?>" class="elementor-button">Completar mis datos de perfil</a>
        <?php 
    endif;
    return ob_get_clean();
}

function affiliate_url_by_type($type) {
    $db = new WPAM_Data_DataAccess();
    $affiliate = $db->getAffiliateRepository()->loadByUserId(get_current_user_id());

    if ( ! $affiliate ) {
        return false;
    }

    $url = get_permalink(43141);
    $url = add_query_arg('wpam_id', $affiliate->affiliateId, $url);
    $url = add_query_arg('tipo', $type, $url);

    return $url;
}

function render_clients_by_distributor() {
    if (isset($_GET['perfil-cliente'])) {
        $user_id = intval($_GET['perfil-cliente']);
        $user = get_user_by('id', $user_id);
        ob_start();

        echo '<div class="perfil-cliente">';
        echo '<p><a href="' . esc_url(remove_query_arg('perfil-cliente')) . '" class="clients-back">← Ver todos los clientes</a></p>';
        
        if (!$user) {
            echo '<p>Cliente no encontrado.</p>';
        } else {
            $datos = uf_get_user_fields( $user, ['personal','nutrition','exercise'], true );
            // Renderízalos:
            echo uf_render_user_fields( $datos, $user, false);
            echo display_suscription_data($user, false);
        }

        echo '</div>';
        return ob_get_clean();
    }

    if (isset($_GET['revisiones-cliente']) && isset($_GET['tipo'])) {
        $user_id = intval($_GET['revisiones-cliente']);
        $form_id = intval($_GET['tipo']);
        $user = get_user_by('id', $user_id);
        ob_start();
        echo '<div class="revisiones-cliente">';
        echo '<p><a href="' . esc_url(remove_query_arg(['revisiones-cliente', 'tipo'])) . '" class="clients-back">← Ver todos los clientes</a></p>';

        if (!$user) {
            echo '<p>Cliente no encontrado.</p>';
        } else {
            echo '<p>Revisiones del cliente <strong>' . esc_html($user->first_name . ' ' . $user->last_name) . '</strong></p>';
            $entries = FrmEntry::getAll(['it.form_id' => $form_id, 'it.user_id' => $user_id]);

            if (empty($entries)) {
                echo '<p>Este cliente no tiene revisiones registradas.</p>';
            } else {
                echo '<ul>';
                foreach ($entries as $entry) {
                    echo '<li>' . esc_html($entry->created_at) . '</li>'; // Puedes mostrar más campos si quieres
                }
                echo '</ul>';
            }
        }

        echo '</div>';
        return ob_get_clean();
    }
    if (isset($_GET['progreso'])) {
        ob_start();
        echo '<p><a href="' . esc_url(remove_query_arg(['progreso'])) . '" class="clients-back">← Ver todos los clientes</a></p>';
        echo '<div id="fm-metrics-charts" data-user="' . $_GET['progreso'] . '"></div>';
        return ob_get_clean();
    }
    $current_user = wp_get_current_user();
    $current_user_id = strval($current_user->ID);

    $args = [
        'meta_key'   => 'id_distribuidor',
        'meta_value' => $current_user_id,
    ];

    if (!empty($_GET['cliente'])) {
        $args['search'] = '*' . sanitize_text_field($_GET['cliente']) . '*';
    }

    $clients = get_users($args);
    if (empty($clients)) {
        return '<form method="get" action="' . esc_url(remove_query_arg('cliente')) . '" class="search-users">
                    <input type="text" name="cliente" placeholder="Busca cliente…" required />
                    <button type="submit">Buscar</button>
                </form>
                <p>No se han encontrado clientes.</p>';

    }

    // Agrupar por estado de suscripción
    $grouped_clients = [];
    foreach ($clients as $client) {
        $user_id = $client->ID;
        $name    = trim($client->first_name . ' ' . $client->last_name);
        $email   = $client->user_email;

        $revision_count_7 = count(FrmEntry::getAll(['it.form_id' => 7, 'it.user_id' => $user_id]));
        $revision_count_8 = count(FrmEntry::getAll(['it.form_id' => 8, 'it.user_id' => $user_id]));

        $member = pms_get_member($user_id);
        $status = isset($member->subscriptions[0]['status']) ? $member->subscriptions[0]['status'] : 'none';

        switch ($status) {
            case 'active':    $estado = 'Suscripción activa'; break;
            case 'expired':   $estado = 'Suscripción expirada'; break;
            case 'canceled':  $estado = 'Suscripción cancelada'; break;
            case 'pending':   $estado = 'Suscripción pendiente'; break;
            case 'abandoned': $estado = 'Suscripción abandonada'; break;
            default:          $estado = 'No suscrito'; break;
        }

        $grouped_clients[$estado][] = [
            'id'         => $user_id,
            'name'       => $name,
            'email'      => $email,
            'revision_7' => $revision_count_7,
            'revision_8' => $revision_count_8,
        ];
    }

    // Obtener todos los parámetros actuales excepto 'cliente'
    $query_args = $_GET;
    unset($query_args['cliente']);

    // Construir la URL base manteniendo los parámetros existentes
    $base_url = add_query_arg($query_args, get_permalink());

    $search_val = isset($_GET['cliente']) ? esc_attr($_GET['cliente']) : '';
 
    
    $html  = '<form method="get" action="' . esc_url(get_permalink()) . '"  class="search-users">';
    $html .= '<input type="hidden" name="sub" value="my_clients" />';
    $html .= '<input type="text" name="cliente" value="' . $search_val . '" placeholder="Busca cliente…" required />';
    $html .= '<button type="submit">Buscar</button>';
    $html .= '</form>';

    $url = get_bloginfo('url');
    $html .= '<div class="clients-by-status">';

    foreach ($grouped_clients as $estado => $clientes) {
        $html .= '<h3>' . esc_html($estado) . ' (' . count($clientes) . ')</h3>';
        $html .= '<div class="clients-group">';

        foreach ($clientes as $client) {
            $html .= '<div class="client">';
                $html .= '<div class="client-data">';
                    $html .= '<div class="name">' . esc_html($client['name']) . '</div>';
                    $html .= '<div class="email">' . esc_html($client['email']) . '</div>';
                $html .= '</div>';
                $html .= '<div class="client-actions">';
                    $html .= '<a href="' . esc_url($base_url . '&perfil-cliente=' . $client['id']) . '">Ver perfil</a>';
                    $html .= '<a href="' . esc_url($base_url . '&progreso=' . $client['id']) . '">Ver progreso</a>';
                    //$html .= '<a href="' . esc_url($base_url . '&revisiones-cliente=' . $client['id'] . '&tipo=7') . '">Progreso Semanal (' . $client['revision_7'] . ')</a>';
                    //$html .= '<a href="' . esc_url($base_url . '&revisiones-cliente=' . $client['id'] . '&tipo=8') . '">Progreso Mensual (' . $client['revision_8'] . ')</a>';
                $html .= '</div>';
            $html .= '</div>';
        }

        $html .= '</div>';
    }

    $html .= '</div>';
    return $html;
}