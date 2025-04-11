<?php 
    // RECETEA LOS USUARIOS CON ROL PERDIDA DE PESO A 0

function fit_add_days_to_users_function_2() {
    $message = '';
    $data =  'done';
    $args = array(
        'number' => -1,
        'role__in' => alimentacion_roles()        
    );
    $today_date = new DateTime( date( 'Y-m-d', strtotime( 'today' ) ) );
    $users      = get_users($args);

    foreach($users as $key => $user) {
        
        $registered = new DateTime( date( 'Y-m-d', strtotime( $user->user_registered ) ) );
        $interval_date      = $today_date->diff( $registered );

        $dias_registrado    = intval($interval_date->days);
        $dias_pausado       = intval(get_user_meta( $user->ID, 'dias_pausado', true ));
        $dias_pausado_hoy   = intval($dias_pausado) + 1;   
        $dias_totales       = $dias_registrado - $dias_pausado;
        if(!empty( get_user_meta($user->ID, 'dia') ) ) {
            update_user_meta($user->ID, 'dia', $dias_registrado );
        } else {
            add_user_meta($user->ID, 'dia', $dias_registrado );
        }
        
        if(in_array('bloqueado', $user->roles) || in_array('pausado', $user->roles)) {
            
             if(!empty( get_user_meta( $user->ID, 'dias_pausado' ) ) ) {
                update_user_meta( $user->ID, 'dias_pausado', $dias_pausado_hoy );
            } else {
                add_user_meta( $user->ID, 'dias_pausado', $dias_pausado_hoy );
            }

        }
        if(!empty( get_user_meta($user->ID, 'dias_totales' ) ) ) {
            update_user_meta( $user->ID, 'dias_totales', $dias_totales ); 
        } else {
            add_user_meta( $user->ID, 'dias_totales', $dias_totales );
        }

        
        
        $message .= '[' . $key . '] User id ' . $user->ID . ' Roles: ' . var_export($user->roles, true) . PHP_EOL;
        
    }
    /*
    // AJUSTE DE BLOQUEADOS (BORRAR)
    foreach($users as $key => $user) {
        
        if(in_array('bloqueado', $user->roles)) {
            $registered = new DateTime( date( 'Y-m-d', strtotime( $user->user_registered ) ) );
            $interval_date   = $today_date->diff( $registered );
            
            $dias_registrado = $interval_date->days;
            
            
            
            update_user_meta($user->ID, 'dia', $dias_registrado); // PARA AJUSTAR LOS BLOQUEADOS
            update_user_meta($user->ID, 'dias_pausado', $dias_registrado); // PARA AJUSTAR LOS BLOQUEADOS
        }
        
        $message .= '[' . $key . '] User id ' . $user->ID . ' Roles: ' . var_export($user->roles, true) . PHP_EOL;
        
    }
    */
    
    if(!empty($message)) {
        trazas($message);
    } else {
        trazas('no hay usuarios');
    }
    
    return $data;
}
add_action( 'fit_add_days_to_users_2', 'fit_add_days_to_users_function_2' );

/*
if (! wp_next_scheduled('fit_add_days_to_users_2')) {
    wp_schedule_event((strtotime('02:30:00')), 'daily', 'fit_add_days_to_users_2');
}
*/
/*
function fourfit_add_days_to_suscription() {
    global $wpdb;
    $members = $wpdb->get_results($wpdb->prepare("SELECT user_id, start_date FROM {$wpdb->prefix}pms_member_subscriptions WHERE status = 'active' AND  ORDER BY user_id ASC;"));
    
    $table = $wpdb->prefix . 'pms_member_subscriptions';

    foreach($members as $key => $member) {

        
        $dias_pausado           = get_post_meta($member->user_id, 'dias_pausado', true);
        
        if(!empty($dias_pausado) &&  $dias_pausado != '0') {
            $init_duration_plan     = get_post_meta($member->user_id, 'init_duration_plan', true);

            $dias_para_aplicar      = intval($init_duration_plan ) + intval($dias_pausado );
            
            $stardate               = new DateTime(  $member->start_date  );
            $expiration_date        = $stardate->modify('+ ' . $dias_para_aplicar . ' days');
            $expiration_date_format = $expiration_date->format('Y-m-d H:i:s');
            
            $wpdb->update( $table, array('expiration_date' => $expiration_date_format ), array('user_id' => $member->user_id), array('%s'), array('%d') );
        }

    }
}

add_action( 'add_days_to_suscription', 'fourfit_add_days_to_suscription' );

if (! wp_next_scheduled('add_days_to_suscription')) {
    wp_schedule_event((strtotime('02:10:00')), 'daily', 'add_days_to_suscription');
}
*/


// AÑADE DURACIÓN A LOS CLIENTES CADA DÍA
add_filter( 'fit_daily_users_meta', 'fourfit_update_daily_users_meta' );
function fourfit_update_daily_users_meta () {
    $args = array(
        'number' => -1,
        'role__in' => alimentacion_roles(),
    );
    $message = '';
    $users = get_users($args);

    foreach($users as $key => $user) {
       // if($user->user_registered <= '2022-04-30 01:00:00') {
        $message        .= $key . ' - ' . $user->user_email . PHP_EOL;

        $dias           = get_user_meta( $user->ID, 'dia', true );

        $dias_pausado   = get_user_meta( $user->ID, 'dias_pausado', true );
        
        $dias_hoy       = intval($dias) + 1;

        $dias_pausado_hoy = intval($dias_pausado);   
        
        if(in_array('bloqueado', $user->roles)) {    
            $dias_pausado_hoy = intval($dias_pausado) + 1;            
        }

        $dias_totales_hoy = intval($dias_hoy) - intval($dias_pausado_hoy);
        
        update_user_meta($user->ID, 'dia', $dias_hoy);
        update_user_meta($user->ID, 'dias_pausado', $dias_pausado_hoy);
        update_user_meta($user->ID, 'dias_totales', $dias_totales_hoy );

    }
    if(!empty($message)) {
        trazas($message);
    }
}