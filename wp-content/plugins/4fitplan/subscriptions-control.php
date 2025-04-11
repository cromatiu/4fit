<?php 
// CÓDIGO PARA PÁGINA DE CONTROL DE SUSCRIPCIONES

function agregar_pagina_ajustes() {
    add_menu_page(
        'Control de suscripciones',    // Título de la página
        'Control de suscripciones',    // Título del menú
        'manage_options',       // Capacidad requerida para acceder
        'subscriptions-control',       // Slug de la página
        'subscriptions_control_content'   // Función que mostrará la página
    );
}

add_action('admin_menu', 'agregar_pagina_ajustes');

function subscriptions_control_content() {
    ?>
    <div class="wrap">
        <h1>Control de subscripciones</h1>
        <?php
            // Verificar si se ha enviado el formulario
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit']) && isset($_POST['get_results']) ) {
                // Procesar el formulario
                // Aquí deberías realizar la lógica necesaria para procesar los datos del formulario

                // Obtener los resultados de la base de datos
                $resultados = obtener_resultados_desde_bd();
            }
            ?>

            <!-- Mostrar el formulario -->
            <form method="post">
                <!-- Agregar aquí los campos de tu formulario -->
                <input type="hidden" name="get_results" id="">
                <input type="submit" name="submit" value="Consultar campos">
            </form>

            <!-- Mostrar los resultados -->
            <?php
            if (isset($resultados) && !empty($resultados)) {
                echo '<h2>Resultados:</h2>';
                echo '<p>Encontradas ' . count($resultados) . ' incidencias';
                echo '<ul>';                
                $counter = 0;
                foreach ($resultados as $fila) {
                    $details                = comprobar_incidencia($fila);
                    
                    if($details['right_date'] && $details) {
                        $right_date = (isset($details)) ? $details['right_date'] : 'Error asignación';
                        $counter++;
                        $suscription_plan_ID    = $fila['subscription_plan_id'];
                        $suscription_plan       = get_the_title($suscription_plan_ID);
                        echo '<div class="incident-row" style="display: flex; gap: 20px; align-items: strech; justify-content: flex-start" data-id="' . $fila['id']  . '" data-date="' . $right_date . '">';
                        echo '<div style="width: 15%">';
                        echo "ID de suscripción: " . esc_html($fila['id']);
                        echo '</div>';
                        echo '<div style="width: 15%">';
                        echo "ID de suscriptor: " . esc_html($fila['user_id']);
                        echo '</div>';
                        echo '<div style="width: 15%">';
                        echo "Plan: " . esc_html($suscription_plan);
                        echo '</div>';
                        echo '<div style="width: 20%">';
                        echo "Fecha de expiración: " . esc_html($fila['expiration_date']);
                        echo "</div>";
                        echo '<div style="width: 20%">';
                        echo "Fecha para asignar: " . esc_html($right_date);
                        echo "</div>";
                        echo '<div style="width: 10%">';
                        echo '<button class="ff-change-date">Cambiar</button>';
                        echo "</div>";
                        echo "</div>";
                        /*
                        echo "<pre>";
                        echo var_export($details, true);
                        echo "</pre>";
                        */
                        echo '<hr>';
                    }
                    // Agrega más columnas según tu estructura de base de datos
                }
                echo '</ul>';
                echo '<p class="all-incidents">';
                echo 'Incidencias encontradas: ' . esc_html($counter);
                echo ' <button href="#" class="ff-update-all">Corregir todas</button>';
                echo '</p>';
            }

            
        ?>
    </div>
    <?php
}

function comprobar_incidencia($fila) {
    $logs           = unserialize($fila['meta_value']);
    $expiration_date= $fila['expiration_date'];
    $important_logs = array();
    $verify_data    = array();
    $incidency      = false;
    $orders_id      = array();
    $error_order_id = 0;
    $right_date     = false;
    foreach ($logs as $key => $log) {
        if(empty($log['data'])) {
            continue;
        }
        if($log['type'] == 'woocommerce_new_product_subscription' || $log['type'] == 'subscription_activated'  || $log['type'] == 'admin_subscription_added_bulk' || $log['type'] == 'subscription_expired' ) {
            continue;
        }
        if(!isset($log['data']['order_id'])) {
            continue;
        }
        
        $order_id      = $log['data']['order_id'];
        if(in_array($order_id, $orders_id) && $incidency == false) {
            $incidency = true;
            $error_order_id = $order_id;
        }
        $orders_id[]   = $order_id;

        $important_logs[] = $log;

    }

    foreach ($important_logs as $important_log) {
        if (isset($important_log['data']['order_id']) && $important_log['data']['order_id'] == $error_order_id) {
            if(isset($important_log['data']['expiration_date'])) {
                $right_date = $important_log['data']['expiration_date'];
            }
            if($right_date != $expiration_date) {
                $filter_important_logs[] = $important_log;
            }
        }
    }
    if($right_date == $expiration_date) {
        $incidency = false;
    }
    if( $incidency === false ) {
        return false;
    }
    $verify_data['right_date']      = $right_date;
    $verify_data['important_logs']  = $filter_important_logs;
    $verify_data['verify']          = $incidency;

    return $verify_data;
}



// Función para obtener los resultados desde la base de datos
function obtener_resultados_desde_bd() {
    global $wpdb;

    $resultados = $wpdb->get_results("SELECT ms.id, ms.user_id, ms.subscription_plan_id, ms.expiration_date, msm.meta_value
        from {$wpdb->prefix}pms_member_subscriptions as ms
        left join {$wpdb->prefix}pms_member_subscriptionmeta as msm
        on ms.id = msm.member_subscription_id
        where msm.meta_key = 'logs'
        and msm.meta_value like '%woocommerce_product_subscription_expiration_update%'
        and ms.status != 'expired'
    ", ARRAY_A);

    return $resultados;
}


function fourfit_update_expiration_date() {
    $response = array();
    
    $response['note'] = 'Ocurrió un error';
    if(isset($_POST['suscription']) && isset($_POST['date'])) {
        global $wpdb;

        $subscription_id     = $_POST['suscription'];
        $expiration_date    = $_POST['date'];
        $now                = date('Y-m-d H:i:s');
        $update_data        = array('expiration_date' => $expiration_date);
        $url_for_check      = get_bloginfo('url') . '/wp-admin/admin.php?page=pms-members-page&subpage=edit_subscription&subscription_id=' . $subscription_id;
        if($expiration_date <= $now) {
            $update_data['status']        = 'expired';
        }

        $subscription_logs    =  $wpdb->get_var($wpdb->prepare("SELECT meta_value 
                FROM {$wpdb->prefix}pms_member_subscriptionmeta 
                WHERE meta_key = 'logs' 
                AND member_subscription_id = %d", 
            $subscription_id));
        
        $subscription_logs = unserialize($subscription_logs);

        $note = '';
        $note .= '<strong>Corregida la fecha de expiración</strong> en suscripción #' .  $subscription_id . '</a> tras detectar incidencia en renovación. ';
        $note .= 'La nueva fecha de expiración asignada es: ' . $expiration_date;

        $subscription_logs[] = array (
            'date' => $now,
            'type' => 'admin_note',
            'data' => array (
                'note' => $note,
                'correct_expirantion_date' => $expiration_date
            ),
        );

        $table = $wpdb->prefix . 'pms_member_subscriptions';

        // ACTUALIZAMOS $wpdb->update($tabla, $datos_a_actualizar, $condicion);
        $update_plan = $wpdb->update( $table,  $update_data, array( 'id' => $subscription_id  ));

        $subscription_logs = serialize($subscription_logs);
            
        $update_plan_meta = $wpdb->update( $wpdb->prefix . 'pms_member_subscriptionmeta',
            [ 'meta_value' =>  $subscription_logs ],
            [ 'meta_key' => 'logs', 'member_subscription_id' => $subscription_id ],
            [ '%s' ]
        );

        if($update_plan && $update_plan_meta) {
        }
        $response['note'] = $note;
        $response['check'] = $url_for_check;
    }
    wp_send_json( $response );
    exit;
    wp_die();


}
add_action('wp_ajax_update_expiration_date', 'fourfit_update_expiration_date');
add_action('wp_ajax_nopriv_update_expiration_date', 'fourfit_update_expiration_date');