<?php 

// GET CLIENTS FROM DITRIBUIDO

use function Clue\StreamFilter\fun;

add_shortcode('clients_from_distributor', 'get_clients_ids_from_distributor');
function get_clients_ids_from_distributor ($atts) {
    $atts = shortcode_atts(
		array(
			'role' => '',
		),
		$atts,
		'clients_from_distributor'
	);

    $role = $atts['role'];


    $current_user = wp_get_current_user();
    $current_user_id = strval($current_user->ID);

    $args = array(
        'meta_key' => 'id_distribuidor',
        'meta_value' => $current_user_id,
        //'orderby'   => 'user_email'
    );

    if(!empty($role)) {
        $args['role__in'][] = $role;
    }

    if(isset($_GET['cliente'])) {

        $args['search'] =  '*' . $_GET['cliente'] . '*';

    }
    $clients = get_users($args);
    
    $user_ids = '0,';
    if(!empty($clients )) {
        foreach($clients as $key => $client) { 
            $user_ids .= $client->ID;
            if(count($clients) - 1 != $key ) {
                $user_ids .= ',';
            }
        }
    }
    return var_export($user_ids,true);
}
add_shortcode('clients_list_from_distributor', 'get_clients_list_from_distributor');
function get_clients_list_from_distributor ($atts) {
    $html = '';
    if(!isset($_GET['perfil-cliente'])) {
        $atts = shortcode_atts(
            array(
                'role' => '',
            ),
            $atts,
            'clients_from_distributor'
        );

        $role = $atts['role'];


        $current_user = wp_get_current_user();
        $current_user_id = strval($current_user->ID);

        $args = array(
            'meta_key' => 'id_distribuidor',
            'meta_value' => $current_user_id,
            //'orderby'   => 'user_email'
        );

        if(!empty($role)) {
            $args['role__in'][] = $role;
        }

        if(isset($_GET['cliente'])) {

            $args['search'] =  '*' . $_GET['cliente'] . '*';

        }
        $clients = get_users($args);
        
        $url = get_bloginfo('url');
        if(!empty($clients )) {
            $html .= '<div class="clients">';
            foreach($clients as $key => $client) { 
                $user_id        = $client->ID;
                $user_name      = $client->first_name;
                $user_lastname  = $client->last_name;
                $user_email     = $client->user_email;
                $entries_7_array = FrmEntry::getAll(array('it.form_id' => 7, 'it.user_id' => $user_id ));
                $revision_count_7 = count($entries_7_array);
                
                $entries_8_array = FrmEntry::getAll(array('it.form_id' => 8, 'it.user_id' => $user_id ));
                $revision_count_8 = count($entries_8_array);
                $plan_status    = 'No se ha suscrito aún.';
                $member = pms_get_member( $user_id );
                $member_plans  =  (isset($member->subscriptions[0]['status'])) ? $member->subscriptions[0]['status'] : false;
                
                if($member_plans) {
                    switch ($member_plans) {
                        case 'active':
                            $plan_status = 'Suscripción activa';
                            break;
                        case 'expired':
                            $plan_status =  'Suscripción expirada';
                            break;
                        case 'canceled':
                            $plan_status =  'Suscripción cancelada';
                            break;
                        case 'pending':
                            $plan_status =  'Suscripción pendiente';
                            break;
                        case 'avandoned':
                            $plan_status =  'Suscripción abandonada';
                            break;
                        default:
                            $plan_status =  'No tiene suscripción';
                    }    
                }

                $html .= '<div class="client">';
                    $html .= '<div class="client-data">';
                        $html .= '<div class="name">';
                        $html .= $user_name . ' ' .$user_lastname;
                        $html .= '</div>';
                        $html .= '<div class="email">';
                        $html .= $user_email;
                        $html .= '</div>';
                        $html .= '<div class="email">';
                        $html .= '<strong><em>';
                        $html .= $plan_status;
                        $html .= '</em></strong>';
                        $html .= '</div>';
                    $html .= '</div>';
                    $html .= '<div class="client-actions">';
                        $html .= '<a href="' . $url . '/distribuidor-mis-clientes/?perfil-cliente=' . $user_id  . '">';
                        $html .= 'Ver ficha';
                        $html .= '</a>';
                        $html .= '<a href="' . $url . '/revisiones/?perfil-cliente=' . $user_id  . '&tipo=7">';
                        $html .= 'Progreso Semanal (' . $revision_count_7 . ')';
                        $html .= '</a>';
                        $html .= '<a href="' . $url . '/revisiones/?perfil-cliente=' . $user_id  . '&tipo=8">';
                        $html .= 'Progreso Mensual (' . $revision_count_8 . ')';
                        $html .= '</a>';
                        $html .= '<a href="' . $url . '/distribuidor-actualizacion-datos-cliente/?id-cliente=' . $user_id  . '&tipo=8">';
                        $html .= 'Cambiar datos';
                        $html .= '</a>';
                    $html .= '</div>';
                $html .= '</div>';
            }
            $html .= '</div>';
        }
    }
    return $html;
}
/**
 * Shortcode [busca_cliente]
 * Genera un formulario mínimo que recarga la página actual con ?cliente=…
 */
function busca_cliente_form_shortcode( $atts ) {
    // Valor actual (para mantenerlo en el input tras la búsqueda)
    $valor = isset( $_GET['cliente'] ) ? sanitize_text_field( $_GET['cliente'] ) : '';

    // URL actual sin el parámetro cliente (para no duplicarlo)
    $action = remove_query_arg( 'cliente' );

    ob_start(); 
    ?>
    <form action="<?php echo esc_url( $action ); ?>" method="get">
        <input
            type="text"
            name="cliente"
            value="<?php echo esc_attr( $valor ); ?>"
            placeholder="Busca cliente…"
            required
        />
        <button type="submit">Buscar</button>
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode( 'buscador_cliente', 'busca_cliente_form_shortcode' );



// OBTENEMOS URL DE PÁGINA DESDE SU ID
add_shortcode('get_pages_link', 'get_pages_link_function');
function get_pages_link_function ($atts) {
    $atts = shortcode_atts(
		array(
			'post_id' => '',
		),
		$atts,
		'get_pages_link'
	);
    $page_link = '';
    $post_id = $atts['post_id'];
    if(empty($post_id)) {

    } else {
        $page_link .= get_the_permalink($page_link);
        
    }
    $page_link = get_home_url();

    return $page_link;
}

// LINK HACIA EL PERFIL DE UN CLIENTE
add_shortcode('users_profile_link', 'users_profile_link_function');
function users_profile_link_function () {  
    $page_link = '';
    $page_link .= get_the_permalink(2498);
    return $page_link;
}

// OBTENEMOS DATOS DE CLIENTE
add_shortcode('customer_data', 'display_customer_data');
function display_customer_data ($atts) {

    $data = '';
    $atts = shortcode_atts(
		array(
			'data' => '',
		),
		$atts,
		'customer_data'
	);

    
    if(isset($_GET['perfil-cliente'])) {
        
        $user_id = $_GET['perfil-cliente'];
    } else {
        
        $jet_engine = (object) jet_engine();
        $user_id = $jet_engine->listings->data->get_queried_user_object()->ID;
    }
    if($user_id) {

  
        $user = get_userdata( $user_id );
        
        switch($atts['data']) {
         
            case 'display_name':
                $data = $user->display_name;
                break;
            case 'user_email':
                $data = $user->user_email;
                break;
            case 'user_registered':
                $registered = $user->user_registered;
                $data = date('d/m/Y', strtotime($registered)) . ' a las ' . date('H:i', strtotime($registered));
                break;
            case 'avatar':
                $args = array (
                    'default'       => 'monsterid',
                    'force_default' => true
                );
                $data = get_avatar( $user_id, $args);
   
                break;
        
            case 'caps':
                $data = render_user_roles($user);
                break;
        
            default:
                $data = var_export(get_userdata($user_id), true);
                break;
            
        }
    }

    return $data;

}



// OBTENEMOS METADATOS DE CLIENTE
add_shortcode('customer_meta', 'display_customer_meta');
function display_customer_meta ($atts) {

    $data = '';
    $atts = shortcode_atts(
		array(
			'key' => '',
		),
		$atts,
		'customer_meta'
	);

    if(isset($_GET['perfil-cliente'])) {
        $user_id = intval($_GET['perfil-cliente']);        
        $data = get_user_meta( $user_id, $atts['key'], true);
    }

    return $data;
}


function fourfit_get_users_data () {
    $data =  '';
    $args = array(
        'number' => 100
    );

    $users = get_users($args);

    $today_date      = new DateTime( date( 'Y-m-d', strtotime( 'today' ) ) );
    
    $data .= '<ul>';
    foreach($users as $key => $user) {
        
        $registered = new DateTime( date( 'Y-m-d', strtotime( $user->user_registered ) ) );
        $interval_date   = $today_date->diff( $registered );

        $data .= '<li>';
        if(in_array('bloqueado', $user->roles)) {

            $data .= 'Usuario bloqueado: '  . var_export($user->roles, true);
        } else {
            $data .= 'Fecha registro: ' . $user->user_registered;
            $data .= ' Intervalo: ' . var_export($interval_date->days, true);
            $data .= ' roles: ' . var_export($user->roles, true);

        }

        $data .= '</li>';
    }
    $data .= '</ul>';


    return $data;
    
}
add_shortcode('user_data', 'fourfit_get_users_data');

// RECETEA LOS USUARIOS CON ROL PERDIDA DE PESO A 0
add_filter( 'fit_reset_users_to_zero', 'fourfit_reset_users_to_zero' );

function fourfit_reset_users_to_zero() {
    $data =  '';
    $args = array(
        'number' => -1
    );
    $users = get_users($args);
    foreach($users as $key => $user) {
        if( in_array('perdidapeso', $user->roles ) ) {  
      
                update_user_meta($user->ID, 'dia', 0);
                update_user_meta($user->ID, 'dias_pausado', 0);
                update_user_meta($user->ID, 'dias_totales', 0);
    
        }
    }
    return $data;
}


function fourfit_get_ejecricio_plan() {
    $plan           = 1;
    $dias           = intval(forfit_get_days_total());
    $duration       = intval(28);
    $steps_per_cycle = intval(8);
    if($dias != 0 ) {
        $step           = ceil( $dias / $duration);
        $cycle          = ceil($step / $steps_per_cycle);
        $plan           = intval($step - ($cycle-1) * $steps_per_cycle);
    }
    $user = wp_get_current_user();
    $user_roles = $user->roles;
    if(in_array('supervisor', $user_roles) || in_array('administrator', $user_roles)) {
        $plan = 99;
    }

    return $plan;
}
add_shortcode('ejercicio_plan', 'fourfit_get_ejecricio_plan');

function fourfit_get_alimentacion_plan() {
    $plan           = 0;
    $dias           = intval(forfit_get_days_total());
    $initiation     = intval(6);
    $duration       = intval(21);
    $steps_per_cycle = intval(8);
    if($dias > $initiation) {
        $total_days     = $dias - $initiation;
        $step           = ceil( $total_days / $duration);
        $cycle          = ceil($step / $steps_per_cycle);
        $plan           = intval($step - ($cycle-1) * $steps_per_cycle);
    }
    $user = wp_get_current_user();
    $user_roles = $user->roles;
    if(in_array('supervisor', $user_roles) || in_array('administrator', $user_roles)) {
        $plan = 99;
    }
    return $plan;
}
add_shortcode('alimentacion_plan', 'fourfit_get_alimentacion_plan');

function forfit_get_days_total() {
    $user_ID        = get_current_user_id();
    $dias_totales   = get_user_meta( $user_ID, 'dias_totales', true );
    return $dias_totales;

}
add_shortcode('dias', 'forfit_get_days_total');


function forfit_get_current_month() {
    $user_ID = get_current_user_id();
    $dias   = get_user_meta( $user_ID, 'dias_totales', true );
    $option = 'Comienzo hoy';
    if($dias >= 34 && $dias <= 61) {
        $option = 'Primer mes';
    }
    if($dias >=62  && $dias <= 89) {
        $option = 'Segundo mes';
    }
    if($dias >= 90 && $dias <= 110) {
        $option = 'Tercer mes';
    }
    if($dias >= 111 && $dias <= 132 ) {
        $option = 'Cuarto mes';
    }
    if($dias >= 133 && $dias <= 153) {
        $option = 'Quinto mes';
    }
    if($dias >= 154 && $dias <= 174) {
        $option = 'Sexto mes';
    }
    if($dias >= 175 && $dias <= 195) {
        $option = 'Séptimo mes';
    }
    if($dias >= 196) {
        $option = 'Octavo mes';
    }
    return $option;

}
add_shortcode('meses', 'forfit_get_current_month');

function forfit_get_current_week() {
    $user_ID = get_current_user_id();
    $dias   = intval(get_user_meta( $user_ID, 'dias_totales', true ));
    $semana = intval($dias / 7) + 1;
    $option = 'Semana 1';
    if($dias != '0') {
        $option = 'Semana ' . $semana;
    }
    return $option;
}
add_shortcode('semanas', 'forfit_get_current_week');

function forfit_get_whatsappweb() {
    $user_ID = get_current_user_id();
    $whatsappweb   = get_user_meta( $user_ID, 'whatsappweb', true );
    return $whatsappweb;

}
add_shortcode('whatsappweb', 'forfit_get_whatsappweb');

function forfit_get_whatsappweb_distribuidor() {
    $user_ID            = get_current_user_id();
    $id_distribuidor    = get_user_meta( $user_ID, 'id_distribuidor', true );
    $whatsappweb        = get_user_meta( $id_distribuidor, 'whatsappweb', true );
    return $whatsappweb;

}
add_shortcode('whatsappweb_distribuidor', 'forfit_get_whatsappweb_distribuidor');




add_shortcode('queried_email', 'get_queried_email');
function get_queried_email () {
    $client_email = '';
    if(isset($_GET['distribuidor'])) {
       $user =  get_userdata($_GET['distribuidor']);
       $client_email = $user->user_email;
    }
    return $client_email;
}

add_shortcode('queried_whatsapp', 'queried_whatsapp');
function queried_whatsapp () {
    $distribuidor_whatsapp = '';
    if(isset($_GET['distribuidor'])) {
        $distribuidor_whatsapp =  get_user_meta( $_GET['distribuidor'], 'whatsappweb', true );
    }
    return $distribuidor_whatsapp;
}

add_shortcode('email_by_id', 'get_email_by_id');

function fourfit_get_distribuidor_email_by_current_user () {
    $client_email = '';
    $user_id = get_current_user_id();
    $current_user_distribuidor_id = get_user_meta($user_id, 'id_distribuidor', true);
    $id_for_email = (!empty($current_user_distribuidor_id )) ? $current_user_distribuidor_id : $user_id;
    $user_data = get_userdata($id_for_email);
    $distribuidor_email = $user_data->user_email;
    
    return $distribuidor_email;
}
add_shortcode('distribuidor_email', 'fourfit_get_distribuidor_email_by_current_user');

function get_email_by_id ($atts) {
    $atts = shortcode_atts(
		array(
			'id' => '',
		),
		$atts,
		'email_by_id'
	);
    $client_email = '';
    if(isset($atts['id'])) {
        $user =  get_userdata($atts['id']);
        $client_email = $user->user_email;
    }
    
    return $client_email;
}
add_shortcode('author_url', 'get_author_url');

function get_author_url ($atts) {
    $atts = shortcode_atts(
		array(
			'id' => '',
		),
		$atts,
		'email_by_id'
	);
    $author_url = '';
    if(isset($atts['id'])) {
        
  
        $author_url = get_author_posts_url( $atts['id'] );
    }
    
    return $author_url;
}

add_shortcode('profile_url', 'get_profile_url');
function get_profile_url($atts) {
    $atts = shortcode_atts(
		array(
			'link' => '',
		),
		$atts,
		'profile_url'
	);

    $url = '';
    if(!empty($atts['link'])) {

        
        if(isset($_GET['perfil-cliente'])) {
            
            $user_ID = $_GET['perfil-cliente'];
        } else {
            
            $jet_engine = (object) jet_engine();
            $user_ID = $jet_engine->listings->data->get_queried_user_object()->ID;
        }
  
        switch($atts['link']) {

            case 'ficha':
                $url = get_the_permalink(2498) . '?perfil-cliente=' . $user_ID;
                break;
            case 'r-mensual':
                // $url = get_bloginfo('url') . '/?author_name=' . get_user_meta($user_ID, 'nickname', true);
                $url = get_the_permalink(80030) . '?perfil-cliente=' . $user_ID . '&tipo=8';
                break;
            case 'r-semanal':
                // $url = get_bloginfo('url') . '/?author_name=' . get_user_meta($user_ID, 'nickname', true);
                $url = get_the_permalink(80030) . '?perfil-cliente=' . $user_ID . '&tipo=7';
                break;

            case 'progreso':
               // $url = get_bloginfo('url') . '/?author_name=' . get_user_meta($user_ID, 'nickname', true);
                $url = get_author_posts_url( $user_ID );
                break;
            case 'cambiar':
                $url = get_the_permalink(17175) . '/?id-cliente=' . $user_ID;
                break;
        }

    }
    
    return $url;
}

function import_csv_form() {
    if (isset($_POST['submit'])) {
      $csv_file = $_FILES['csv_file'];
      $csv_to_array = array_map(function($v){return str_getcsv($v, "\t", '', '\\');}, file($csv_file['tmp_name'])); 
      $client_data = array();
      $metadatos    = array();
      foreach ($csv_to_array as $key => $value) {
        if ($key == 0) {
            $metadatos = $value;
        }else {
            foreach($value as $meta_key => $meta_value) {
                $literal_key        = $metadatos[$meta_key];
                $client_data[$key][$literal_key] = $meta_value;
            }
        }

        // custom action goes here...
      }

        if(!empty($client_data)) {
            foreach( $client_data as $key => $value ) {
                $user_email = strtolower($value['user_email']);
                $user = get_user_by( 'email', $user_email );
                $message = '';
                if($user) {
                    $clean_display          = eliminar_acentos($user->display_name);
                    $lower_display_string   = strtolower( $clean_display );
                    $lower_display_array    = explode( ' ', $lower_display_string );

                    $clean_name         = eliminar_acentos($value['first_name']);
                    $lower_name         = strtolower( $clean_name );
                    $lower_name_array   = explode(  ' ', $lower_name );

                   $message .= 'Usuario: ' . $user->ID . ' - ';

                    if( in_array($lower_name_array[0], $lower_display_array ) ) {
                        //echo '<h4>' . $user->ID . '</h4>';
                        
                        foreach($value as $meta_key => $meta_value) {
                            $user_meta = get_metadata( 'user', $user->ID, $meta_key, true);
                            //$message .= 'meta key: ' . $meta_key . ' : ';

                            if( $meta_key != 'user_email' && $meta_key != 'distribuidor_4fit' ) {
                                if( empty($user_meta) ) {
                                    $new_meta = add_user_meta( $user->ID, $meta_key, $meta_value, true );
                                    //$new_meta = true;
                                    if ($new_meta) {
                                       // $message .= 'Actulizado: ' . $meta_key . ' : ' . $meta_value . ' , ';
                                    } else {
                                        $message .= 'Error al actualizar' . $meta_key . ' , ';
                                    }
                                } else {
                                    if($meta_key != '') {
                                        //$message .= 'Ya existe valor para: ' . var_export($meta_key, true) . ' con valor: ' . $meta_value . ' : ' . var_export($user_meta, true) . ' , ';
                                    }
                                }
                            }
                            if($meta_key == 'distribuidor_4fit') {

                                $distribuidor = get_user_by('email', $meta_value);
                                
                                if($distribuidor) {
                                    $new_id_dist = add_user_meta( $user->ID, 'id_distribuidor', $distribuidor->ID, true );
                                    if($new_id_dist) {
                                        
                                        $message .= 'Añadida la id de distribuidor: ' . $distribuidor->ID . ' email: ' . $meta_value . ' . ';
                                    }
                                   
                                } else {
                                   // $message .= 'Email distribuidor erroneo: ' . $meta_value . ' para cliente: ' . $user->user_email;
                                }
                            }
                        }

                    } else {
                      //  $message .= 'Nombre erroneo: ' . $user_email . ' - Nombre usuario: ' . $user->display_name . ' Nombre registrado ' . $value['first_name'];
                    }

                } else {
                  $message .= 'Email no valido: ' . $user_email . ' . ';
                }
                if(!empty($message)) {
                    trazas($message);
                }
            }
        }
    } else {
      echo '<form action="" method="post" enctype="multipart/form-data">';
      echo '<input type="file" name="csv_file">';
      echo '<input type="submit" name="submit" value="submit">';
      echo '</form>';
    }
  }
  
  add_shortcode('import_csv_form', 'import_csv_form');


  add_shortcode('current_user_data', 'fit_get_current_user_data');
  
  function fit_get_current_user_data($atts) {
    $atts = shortcode_atts(
		array(
			'key' => '',
		),
		$atts,
		'current_user_data'
	);
    $data = '';
    $user = wp_get_current_user();
    if($atts['key']) {
        $data = $user->email;
    }
    return $data;
}
add_shortcode('concurso', 'fourfit_concurso');

function fourfit_concurso() {
    global $wpdb;
 
    
    //$plan_id = 35977;
    $plan_id = 48170;
    //Should probably validate input and throw up error. In any case, the following ensures the query is safe.

    // HORA EN UTC (-2 CON LA HORA LOCAL)
    $start_dt = new DateTime('2022-05-16 16:00:00');
    //$start_dt = new DateTime('2022-03-29 22:00:00');
    $s = $start_dt->format('Y-m-d H:i:s');

    $end_dt = new DateTime('2022-05-18 21:59:00');
    $e = $end_dt->format('Y-m-d H:i:s');

    //$sql = $wpdb->prepare("SELECT {$wpdb->prefix}pms_member_subscriptions.* FROM {$wpdb->prefix}pms_member_subscriptions WHERE 1=1 AND CAST(start_date AS DATE) BETWEEN %s AND %s", $s,$e);
    $sql = $wpdb->prepare("SELECT {$wpdb->prefix}pms_payments.* FROM {$wpdb->prefix}pms_payments WHERE 1=1 AND CAST(date AS DATETIME) BETWEEN %s AND %s", $s, $e);

    $members = $wpdb->get_results($sql);
    $members_ids = array();
    foreach($members as $key => $member) {
        if($member->subscription_plan_id == $plan_id && $member->status == 'completed') {
            $members_ids[] = $member->user_id;
        }
    };
    foreach ($members_ids as $key => $member_id) {
        $email_distribuidor = get_user_meta($member_id, 'distribuidor_4fit', true);
        if(!empty($email_distribuidor)) {
            $distribuidores[] = $email_distribuidor;
        }
    }
    $recuento = array();
    foreach ($distribuidores as $key => $distribuidor ) {
        $dist = get_user_by('email', $distribuidor);
        if(!array_key_exists($dist->ID, $recuento)) {
            $recuento[$dist->ID]['email'] = $distribuidor;
            $recuento[$dist->ID]['count'] = 1;
        } else {
            $increment = $recuento[$dist->ID]['count'] + 1;
            $recuento[$dist->ID]['count'] =  $increment;
        }
    }

   $message = '';
   
   $message .= '<h2>Recuento de ventas de distribuidores</h2>';
   
   $message .= '<p>De ' . date('d-m-Y H:i:s', strtotime($s  . ' +2 hours')) . ' a ' . date('d-m-Y H:i:s', strtotime($e  . ' +2 hours')) . '</p>';
   $message .= '<table>';
   $message .= '<tr>';
   $message .= '<th>Recuento</th>';
   $message .= '<th>Email distribuidor</th>';
   $message .= '</tr>';
   foreach($recuento as $key => $recuento_item) {
       $message .= '<tr>';
       $message .= '<td>' . $recuento_item['count']. '</td>';
       $message .= '<td>' . $recuento_item['email'] . '</td>';
       $message .= '</tr>';
   }
   $message .= '</table>';

   

    $user_data = '';
    //$user_data .= '<pre>' . var_export($user_object->data->user_registered, true) . '</pre><br>';
    $user_data .= '<pre>' . var_export($recuento, true) . '</pre><br>';



    return $message;
}; 

function fourfit_get_revision_type () {
    $form_id = '';
    $revision_type = '';
    if( isset( $_GET['tipo'] ) ) {
        $form_id = intval( $_GET['tipo'] );

        switch( $form_id ) {
            case ( 8 ):
                $revision_type = 'Revisión Mensual';
                break;    
            case ( 7 ):
                $revision_type = 'Revisión Semanal';
                break;    
        }
    }

    return $revision_type;
}
add_shortcode('revision_type', 'fourfit_get_revision_type');

// MOSTRAMOS EL PROGRESO SEMANAL Y MENSUAL

function fourfit_get_revisions ($attrs) {
    $attrs = shortcode_atts(
		array(
			'tipo' => false,
		),
		$attrs,
		'revisions_count'
	);
    $return = '';
    $user_id = false;
    $current_user = false;  

    

    if( isset( $_GET['perfil-cliente'] ) ) {
        $user_id = intval( $_GET['perfil-cliente'] );
    } else {
        $current_user = true;
    }
    $form_id = 8;
    if( isset( $_GET['tipo'] ) ) {
        $form_id = intval( $_GET['tipo'] );
    }
    if($attrs['tipo']) {
        $form_id = intval( $attrs['tipo'] );
    }

    $results = fourfit_get_entries_list ($form_id, $user_id);
    if( empty($results) ) {
        if($form_id == 8 ) {
            $tipo = 'mensuales';
        } else {
            $tipo = 'semanales';
        }
        if($current_user) {

            $return = '<h4>Aún no tienes revisiones ' . $tipo . '.</h4>';
        } else {
            $return = '<h4>Este usuario no tiene revisiones ' . $tipo . '.</h4>';
        }
    } else {
        $return .= '<div style="overflow-x: scroll; max-width: 100%">';
        $return .= '<table>';
        $return .= '<tr>';

        foreach (reset($results)['table'] as $table_head) {
            
            $return .= '<th style="min-width: 150px">' . $table_head['field_key_name'] . '</th>';
        }
        foreach ($results as $entry_id => $form_entry) {
            $return .= '<tr>';
            foreach($form_entry['table'] as $field_id => $field ) {
                $value = $field['value'];
                $field_key = $field['field_key'];

                if(is_array($value)) {
                    $value = implode(', ', $value);
                }
                if($value == '0' || $value == '1' || $value == '2' || $value == '3' || $value == '4' || $value == '5' ) {
                    $value = forfit_rating ( $value);
                    
                }
                if(str_contains($value, 'uploads')) {
                    $value = '<img src="' . $value . '" style="max-width:100px; height: auto;"  />';
                

                }
                if($field_key == 'notas' || $field_key == 'notas2' ) {
                   
                    $value =  display_form_notes ($field_id, $entry_id, $value);
                    $field_style = 'style="min-width: 300px"';
                } else {
                    $field_style = 'style="min-width: 150px"';
                }

                

                $return .= '<td ' .  $field_style  .'>' . $value . '</td>';

            }
            $return .= '</tr>';
        }
        
        $return .= '</tr>';
        $return .= '</table>';
        $return .= '</div>';
    }

    return $return;
}
add_shortcode('form-entries', 'fourfit_get_revisions');


function fourfit_revisions_count ($attrs) {
    $attrs = shortcode_atts(
		array(
			'tipo' => 7,
		),
		$attrs,
		'revisions_count'
	);

    $form_id = intval($attrs['tipo']);

    $entries_array = array();

    
    if(isset($_GET['perfil-cliente'])) {
        $user_id = $_GET['perfil-cliente'];
    } else {
        $jet_engine = (object) jet_engine();
        $user_id = $jet_engine->listings->data->get_queried_user_object()->ID;
    }

    $user_id = ($user_id ) ? $user_id : get_current_user_id();

    $entries_array = FrmEntry::getAll(array('it.form_id' => $form_id, 'it.user_id' => $user_id ));
    $entries_count = count($entries_array);
    
    return $entries_count;

}
add_shortcode('revisions_count', 'fourfit_revisions_count');
    
function forfit_get_last_revision_date() {
    if(isset($_GET['perfil-cliente'])) {
        
        $user_ID = $_GET['perfil-cliente'];
    } else {
        
        $jet_engine = (object) jet_engine();
        $user_ID = $jet_engine->listings->data->get_queried_user_object()->ID;
    }

    $form_id = 7;
    
    $entries = FrmEntry::getAll(array('it.form_id' => $form_id, 'it.user_id' => $user_ID ), ' ORDER BY it.created_at ASC');
    
    $date = '';
    foreach($entries as $entry_id => $value ) {
        $date = FrmProEntriesController::get_field_value_shortcode(array('field_id' => 130, 'entry' => $entry_id));
    }
 
    return $date;
}
add_shortcode('last_revision', 'forfit_get_last_revision_date');

function fourfit_customer_tracing () {
    $html = '';
    
    if(isset($_GET['perfil-cliente']) && isset($_GET['tipo'])) {
        $user = get_userdata($_GET['perfil-cliente']);
        $html .= '<h3>' . fourfit_get_revision_type () . '</h3>';
        $html .= '<h5><strong>' . $user->display_name .  '</strong> - ' . $user->user_email . '</h5>';
        
        $html .= fourfit_get_revisions( array('tipo' => $_GET['tipo'] ));
        $html .= '<a href="' . get_the_permalink() . '" class="elementor-button">Ver todas las revisiones</a>';


    } else {

        $my_id = get_current_user_id();
        $args = array (
            'meta_key' => 'id_distribuidor', 
            'meta_value' => $my_id,
            'fields'    => 'ids'
        );
        $users_array = get_users($args);
        
        $form = 7;
        
        global $wpdb;
        
        $users = '(' . implode( ',', $users_array) . ')';
        
        $tablename = $wpdb->prefix . 'frm_items';
        
        $prepare = $wpdb->prepare("SELECT id, created_at, user_id FROM {$tablename} WHERE user_id IN {$users} AND form_id = {$form} ORDER BY created_at DESC" );
        
        $revisions = $wpdb->get_results( $prepare , ARRAY_A );
        if(!empty($revisions)) {
            $html .= '<ul class="revisions-list">';
            foreach($revisions as $key => $revision ) {
                $user = get_userdata($revision['user_id']);
                $day = date('d-m-Y', strtotime($revision['created_at']));
                $link = get_the_permalink() . '?revision=' . $revision['id'];
                $linkprofile = get_the_permalink() . '?perfil-cliente= ' . $revision['user_id'] .  '&tipo=' . $form;
                $html .= '<li>';
                $html .= '<div class="left">';
                $html .=  '<p><strong>' . $day . '</strong></p>';
                $html .=  '<h3>' . $user->display_name . '</h3>';
                
                $html .=  '<p>' . $user->user_email . '</p>';
                $html .= '</div>';
                $html .= '<div class="right">';
                $html .=  '<p><a class="elementor-button" href="' .  $linkprofile . '">Ver revisiones</a></p>';
                $html .= '</div>';
            }
            $html .= '</ul>';
            
        } else {
            
            $html .= '<h4 style="text-align: center">Aún no tienes revisiones de tus clientes.</h4>';
        }

    }
        
        return $html;
    }

add_shortcode('seguimiento', 'fourfit_customer_tracing');

function get_geolocation_data () {
    $geo_instance  = new WC_Geolocation();
    // Get geolocated user geo data.
    $user_geodata = $geo_instance->geolocate_ip();
    $country    = (isset($user_geodata['country'])) ? $user_geodata['country'] : '';
    return  $country;
}
add_shortcode('geolocalizacion', 'get_geolocation_data');

// SHORTCODE CON LINK PARA CREAR USUARIO BÁSICO
function fourfit_register_client_user_link($attrs) {
    $attrs = shortcode_atts(
		array(
			'tipo' => 'basico', // VALORES: 'basico', 'normal', 'distribuidor'
            'add_role' => ''
		),
		$attrs,
		'register_link'
	);
    $html = '';
    $type = $attrs['tipo'];
    $current_user_id    = get_current_user_id();
    $add_role = $attrs['add_role'];


    switch ($type) {
        case 'basico':
            // pagina para registro clientes "Mi alta 4forfit"
            $link  = get_the_permalink(94526);
            break;
        case 'normal':
            // pagina para registro clientes "Mi alta 4forfit"
            $link  = get_the_permalink(94526); 
            break;
        case 'mensual':
            // pagina para registro clientes "Mi alta 4forfit"
            $link  = get_the_permalink(94526); 
            break;
         case 'distribuidor':
            // pagina para registro distribuidores "Mi alta cómo distribuidor"
            $link  = get_the_permalink(97151);
            break;
    }
    $add_role_parameter = '';
    if(!empty($add_role)) {
        $add_role_parameter = '&add_role=4hair';
    }

    $key = fourfit_encode_key($current_user_id, $type);
    $register_link       = $link . '?distribuidor=' . $current_user_id . '&tipo='. $type . $add_role_parameter . '&key=' . $key;

    $rand = mt_rand(0, 999);

    // Añadimos wp_nonce 
    $html .= '<div class="copy-content">';
    $html .= '<input class="copy-input" value="' . $register_link . '" id="copyClipboard-' . $rand . '" readonly>';
    $html .= '<button class="btn btn-default btn-xs copy-link" title="Copiar enlace" link="' . $register_link . '"><i class="fa fa-copy"></i></button>';
    $html .= '<div class="message"><span>Enlace copiado!<span></div>';
    $html .= '</div>';
    return $html;
}
add_shortcode('register_link', 'fourfit_register_client_user_link');

function validate_with_wpnonce() {
    $valid = 'NO';
    if(isset($_GET['key']) && isset($_GET['distribuidor']) && isset($_GET['tipo']) ) {
        
        if( fourfit_valid_key($_GET['key'], $_GET['distribuidor'], $_GET['tipo'])  ) {
            $valid      = 'YES';
        }
    }
    return $valid;
}
add_shortcode('valid_nonce', 'validate_with_wpnonce');

function fourfit_encode_key($user_id, $string) {
    $user_phone = get_user_meta($user_id, 'telefono', true);
    $string_to_encode = $user_phone . '-' . $string;
    $key = base64_encode($string_to_encode);
    return $key;
}
function fourfit_valid_key($key, $user_id, $string) {
    $valid = false;
    $user_phone = get_user_meta($user_id, 'telefono', true);
    $string_to_decode = $user_phone . '-' . $string;
    $key_decoded = base64_decode($key);
    if($string_to_decode == $key_decoded) {
        $valid = true;
    }
    return $valid;
}

function pms_display_subscription_details() {
    // Verifica si el usuario está logueado
    if ( is_user_logged_in() ) {
        // Obtén el ID del usuario actual
        $user_id = get_current_user_id();

        // Obtén las suscripciones activas del usuario
        $subscriptions = pms_get_member_subscriptions( array( 'user_id' => $user_id, 'status' => 'active' ) );

        // Recorre las suscripciones activas
        foreach ( $subscriptions as $subscription ) {
            // Obtén la fecha de finalización/renovación
            $expiration_date = date_i18n( 'j \d\e F \d\e Y', strtotime( $subscription->expiration_date ) );

            // Muestra la fecha de renovación
            echo '<p>Fecha de renovación: <strong>' . esc_html( $expiration_date ) . '</strong></p>';

            // Verifica si la suscripción tiene renovación automática activa
            if ( $subscription->auto_renew ) {
                // Muestra un botón para cancelar la renovación automática
                echo '<form method="post" action="">
                        <input type="hidden" name="subscription_id" value="' . esc_attr( $subscription->id ) . '">
                        <button type="submit" name="toggle_auto_renewal">Cancelar renovación automática</button>
                      </form>';
            } else {
                // Si la renovación automática no está activa, muestra un botón para activarla
                echo '<form method="post" action="">
                        <input type="hidden" name="subscription_id" value="' . esc_attr( $subscription->id ) . '">
                        <button type="submit" name="toggle_auto_renewal">Activar renovación automática</button>
                      </form>';
            }
        }
    }
}
add_shortcode( 'subscription_details', 'pms_display_subscription_details' );
/*
function pms_process_auto_renewal_toggle() {
    if ( isset( $_POST['toggle_auto_renewal'] ) && isset( $_POST['subscription_id'] ) ) {
        // Obtén el ID de la suscripción
        $subscription_id = intval( $_POST['subscription_id'] );

        // Obtén la suscripción
        $subscription = new PMS_Member_Subscription( $subscription_id );

        // Alterna el estado de la renovación automática
        if ( $subscription->auto_renew ) {
            // Si la renovación automática está activa, desactívala
            $subscription->update( array( 'auto_renew' => 0 ) );
        } else {
            // Si la renovación automática no está activa, actívala
            $subscription->update( array( 'auto_renew' => 1 ) );
        }

        // Redirecciona para evitar reenvío de formularios
        wp_redirect( add_query_arg( 'auto_renewal_toggled', 'true', wp_get_referer() ) );
        exit;
    }
}
add_action( 'init', 'pms_process_auto_renewal_toggle' );
*/