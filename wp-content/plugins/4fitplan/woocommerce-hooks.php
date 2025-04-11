<?php 

//Redirigir al chekcout sin pasar por el carrito

/*
add_filter ('woocommerce_add_to_cart_redirect', 'redirect_to_checkout');

function redirect_to_checkout() {

	return wc_get_checkout_url();
}
*/

add_filter( 'woocommerce_checkout_fields' , 'hidden_woocommerce_checkout_fields' );
function hidden_woocommerce_checkout_fields( $fields ) {
     unset($fields['order']['order_comments']);
     return $fields;
}

add_action( 'woocommerce_order_status_processing', 'always_orders_complete', 10, 1 );

function always_orders_complete( $order_id ) {
    $order = wc_get_order( $order_id );

    if( !empty( $order ) ) {
        $order_status = $order->get_status();

        if( $order_status == 'processing' ) {
            $order->update_status( 'completed' );
        }
    }
}

// Función para quitar la acción
function fourfit_remove_action_on_pending_status() {
	remove_action('woocommerce_order_status_processing',    'pms_woo_handle_member_subscription');
}

// Asegúrate de que esta función se ejecute después de que el plugin de terceros haya añadido la acción
add_action('init', 'fourfit_remove_action_on_pending_status');


add_filter( 'woocommerce_product_single_add_to_cart_text', 'woo_custom_cart_button_text' );    // 2.1 +
 
function woo_custom_cart_button_text() {
 
        return __( 'Comprar Suscripción', 'woocommerce' );
 
}

add_shortcode( 'subscription_info', 'pmsc_subscription_info_shortcode' );
function pmsc_subscription_info_shortcode( $atts ){
	$atts = shortcode_atts(	array(
			'id'                => get_current_user_id(),
			'subscription_plan' => '',
			'key'               => '',
		), $atts );
 
	if (empty($atts['key']) || empty($atts['id']))
		return;
 
	$args = array( 'user_id' => $atts['id'] );
 
	if( !empty( $atts['subscription_plan'] ) )
		$args['subscription_plan_id'] = $atts['subscription_plan'];
 
	$member = pms_get_member_subscriptions( $args );
    
    if(empty($member))
        return;
    
    $renew_notification     = strtotime( $member[0]->expiration_date  . ' - 15 days');

    $renew_notification_format     = date( 'Y-m-d H:i:s', $renew_notification  );

	$renew_notification_timestamp = mktime(0, 0, 0, date( 'm', $renew_notification  ), date( 'd', $renew_notification  ), date( 'Y', $renew_notification  ) );
 
	if ( empty( $member ) )
		return '';
 
 	$subscription_plan = pms_get_subscription_plan( $member[0]->subscription_plan_id );
 
	$subscription_statuses = pms_get_member_subscription_statuses();
 
	switch ($atts['key']) {
		case 'start_date':
            
			return $member[0]->start_date;
			break;
		case 'expiration_date':
			return $member[0]->expiration_date;
			break;
		case 'renew_notification':
			return $renew_notification_format;
			break;
		case 'renew_notification_timestamp':
			return $renew_notification_timestamp;
			break;
		case 'next_payment':
			return $member[0]->billing_next_payment;
			break;
		case 'status_slug':
			return $member[0]->status;
			break;
		case 'status':
			return $subscription_statuses[$member[0]->status];
			break;
        case 'plan_id':
            return $member[0]->subscription_plan_id;
            break;
		case 'plan_name':
			return $subscription_plan->name;
			break;
		case 'plan_price':
			return $subscription_plan->price;
			break;
		case 'plan_duration':
			return $subscription_plan->duration . ' ' . $subscription_plan->duration_unit;
			break;
		case 'default':
			return;
			break;
	}
}

function has_to_renew(){
    $renew = 'YES';
    $current_user = get_current_user_id();
    $args = array( 'user_id' => $current_user);
 
 
	$member = pms_get_member_subscriptions( $args );

    if(!empty($member)) {

        $renew_notification     	= strtotime( $member[0]->expiration_date  . ' - 15 days');
        
        $renew_notification_format  = date( 'Y-m-d H:i:s', $renew_notification  );
        
        $today = date('Y-m-d') . ' 00:00:00';
        
        if($member[0]->status == 'active' && $today < $renew_notification_format  ) {
            $renew = 'NO';
        }
    }

    return $renew;
}
add_shortcode('renew', 'has_to_renew');

function fourfit_customer_type(){
	$user = wp_get_current_user();
    $customer_type = 'suscription-4fit';
    $args = array( 'user_id' => $user->ID );
	$roles = $user->roles;
	
	//$member = pms_get_member_subscriptions( $args );

	
	if ( in_array('suscripcin_4fitplan', $roles) ) {
		$customer_type = 'suscription-4fit';
	}
	if ( in_array('cliente_basico', $roles) ) {
		$customer_type = 'cliente-basico';
	} 
	if ( in_array('suscripcion_mensual', $roles) ) {
		$customer_type = 'mensual';
	} 
	if ( in_array('distribuidor', $roles) ) {
		$customer_type = 'distribuidor';
	}
	/*
	if(!empty($member)) {
        if( $member[0]->subscription_plan_id == 48760 ) {
            $customer_type = 'distribuidor';
        }
        if( $member[0]->subscription_plan_id == 48170 ) {
            $customer_type = 'suscription-4fit';
        }
        if( $member[0]->subscription_plan_id == 120793 ) {
            $customer_type = 'mensual';
        }
        if( $member[0]->subscription_plan_id == 94516 ) {
            $customer_type = 'cliente-basico';
        }
    }
	*/
    return $customer_type;
}
add_shortcode('customer_type', 'fourfit_customer_type');



add_shortcode('woocommerce_notices', 'fourfit_woocommerce_notices');


function fourfit_woocommerce_notices($attrs) {
	if (function_exists('wc_notice_count')) {
		WC()->session = new WC_Session_Handler();
		WC()->session->init();
		if(wc_notice_count() > 0) {

			?>

		<div class="woocommerce-notices-shortcode woocommerce">
			<?php wc_print_notices(); ?>
		</div>

		<?php
		}
    }

};


add_shortcode('has_cart_items', 'fourfit_has_cart_items');

function fourfit_has_cart_items() {
	$cart =	WC()->cart->get_cart();
	$has_items = 'NO';
	if(!empty($cart)) {
		$has_items = 'YES';
	}
	return $has_items;
}

// ELIMINAR MENSAJE AL AÑADIR AL CARRO
add_filter( 'wc_add_to_cart_message_html', '__return_false' );

add_action('woocommerce_after_cart_table', 'custom_code_after_cart_table');


function custom_code_after_cart_table() {
    // Your custom code or HTML

	if ( is_plugin_active( 'woocommerce-multicurrency/woocommerce-multicurrency.php' ) && $_SERVER['HTTP_HOST'] != '4fitplan.com'  ) {
		// El plugin está activo
		echo '<div class="custom-message">';
		echo '<p>¿Necesitas cambiar tu moneda?<div class="currency-selector">';
		echo do_shortcode('[woocommerce-currency-switcher format="{{code}}: {{name}}"]');
		echo '</div>';
		echo '</div>';
	}
}
