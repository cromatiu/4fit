<?php 
/**
CAMBIAMOS EL ROL DEL USUARIO
*/

use PostSMTP\Vendor\GuzzleHttp\Psr7\Response;

add_action('frm_after_create_entry', 'inactive_to_member', 20, 2);
function inactive_to_member($entry_id, $form_id){


    // ALTA DE DISTRIBUIDOR YA CLIENTE
    if($form_id == 16) {
        $first_name = $_POST['item_meta'][257];
        $last_name  = $_POST['item_meta'][258];
        $user_mail  = $_POST['item_meta'][259];
        $telefono   = $_POST['item_meta'][260];
        $whatsapp   = $_POST['item_meta'][261];
        $user       = get_user_by( 'email',  $user_mail );
        if(!$user) {
            return; //don't continue if user doesn't exist
        }
        // ACTUALIZAMOS DATOS DE USUARIO
        $user_data = array(
            'ID'           => $user->ID,
            'first_name'   => $first_name, // Reemplaza con el nuevo nombre
            'last_name'    => $last_name, // Reemplaza con el nuevo apellido
            'user_url'     => $whatsapp, // Reemplaza con la nueva URL
        );
        
        $id = wp_update_user($user_data);

        // Actualizamos los metadatos
        $id = update_user_meta($user->ID, 'id_distribuidor', $user->ID);
        
        $id = update_user_meta($user->ID, 'telefono', $telefono);
        $id = update_user_meta($user->ID, 'whatsappweb', $whatsapp);
    
        
        // Actualizamos los roles
        $user->remove_role('suscripcin_4fitplan');
        $user->add_role('distribuidor');
        
        if(in_array('suscripcin_4fitplan_suscrito', $user->roles) || in_array('cliente_basico_suscrito', $user->roles) ) {
            $user->remove_role('suscripcin_4fitplan_suscrito');
            $user->remove_role('cliente_basico_suscrito');
            $user->add_role('distribuidor_suscrito');
        }

        // Cambiamos el plan
        global $wpdb;

        // Definir la tabla, columnas y valores
        $table_name = $wpdb->prefix . 'pms_member_subscriptions';
        $new_subscription_plan_id = 48760;

        // Actualizar el valor de la columna subscription_plan_id
        $result = $wpdb->update(
            $table_name, // Nombre de la tabla
            array(
                'subscription_plan_id' => $new_subscription_plan_id, // Datos a actualizar
            ),
            array(
                'user_id' => $user->ID, // Condición WHERE
            )
        );

        // Comprobar si la actualización fue exitosa
        if ($result !== false) {
            trazas('Se actualizó la suscripción para el usuario '. $user->ID . '.');
        } else {
            trazas('Hubo un error al actualizar la suscripción para el usuario '. $user->ID . '.');
        }
        
    }
    // FORMULARIO BLOQUEO DE CLIENTES
    if($form_id == 5){ //change 24 to the form id of the form to copy
  
   $user_mail = $_POST['item_meta'][222];
   
   $user = get_user_by( 'email',  $user_mail );
   if(!$user) {
       return; //don't continue if user doesn't exist
   }
   require_once ABSPATH . 'wp-admin/includes/user.php';

   $action = $_POST['item_meta'][101];
   if($action == 'Bloquear cuenta') {
       $user->add_role( 'bloqueado' );
       $user->remove_role( 'activo' );
       $user->remove_role( 'pausado' );
    }
    if($action == 'Activar cuenta') {
   
        $user->add_role( 'activo' );
        $user->remove_role( 'bloqueado' );
        $user->remove_role( 'pausado' );
    }
    if($action == 'Pausar cuenta') {
       $user->add_role( 'pausado' );
       $user->remove_role( 'bloqueado' );
       $user->remove_role( 'activo' );
    }
    if($action == 'Eliminar cuenta') {
        wp_delete_user($user->ID);
    }

 }
 // FORMULARIO DE ALTA Distribuidor cliente
 if( $form_id == 18 || $form_id == 27) {
     $user       = wp_get_current_user();
     // CONTROL DE 5 DIGITOS
     $control = '11111';
     if(isset($_POST['item_meta'][397])) {
         $control    = $_POST['item_meta'][397];
     }
     if(isset($_POST['item_meta'][667])) {
         $control    = $_POST['item_meta'][667];
     }
    // POSIBLE ERROR
    $nutricional_value = substr($control, 1, 3);

    assign_nutrition2($user, $nutricional_value);

    // ROLES SEPARADOS
    $nivel_ejercicio_value = substr($control, 4, 1);
    $lugar_ejercicio_value = substr($control, 5, 1);

    $sexo_value            = substr($control, 0, 1);
    assing_ejercicio_2($user, $nivel_ejercicio_value, $lugar_ejercicio_value, $sexo_value );
    
    $fit_family     = substr($control, 5, 1);
    if ($fit_family == '1') {
        $user->add_role('4fitfamily');
    }
 }
}

/**
CAMBIAMOS EL ROL DEL USUARIO
*/
add_action('frm_after_create_entry', 'change_user_email', 20, 2);
function change_user_email($entry_id, $form_id){
    // ACCIÓN PARA FORMULARIO DE 
    // FORMULARIO DE CAMBIO DE DATOS DE CLIENTE
    if($form_id == 14){ 

        $user_mail = $_POST['item_meta'][222];
        $user = get_user_by( 'email',  $user_mail );
        if(!$user) {
            return;
        }
    }
    // FORMULARIO CAMBIO DE PLAN DE CLIENTE
    if( $form_id == 6 ) {
        // RECOGEMOS USUARIO SEGÚN EMAIL INTRODUCIDO
        $user_mail = $_POST['item_meta'][264];
        $user = get_user_by( 'email',  $user_mail );
        if(!$user) {
            return;
        }
        
        // CAMBIO DE ROLES NUTRICIONALES
        $nutricional_value = intval($_POST['item_meta'][266]);
        
        if(isset($nutricional_value) && !empty($nutricional_value)) {
            assign_nutrition2($user, $nutricional_value);
        }
        
        // CAMBIO DE ROLES EJERCICIO
        $ejercicio_value = intval($_POST['item_meta'][265]);

        if(isset($ejercicio_value)  && !empty($ejercicio_value)) {

            // ROLES SEPARADOS
            $sexo_value            = substr($ejercicio_value, 0, 1);

            $nivel_ejercicio_value = substr($ejercicio_value, 1, 1);
            $lugar_ejercicio_value = substr($ejercicio_value, 2, 1);

            assing_ejercicio_2($user, $nivel_ejercicio_value, $lugar_ejercicio_value, $sexo_value );
        }
        // CAMBIO DE ROL FORFIT PLAN
        $plan_value = $_POST['item_meta'][213];

        if(isset($plan_value)) {
            switch ($plan_value) {
                case 'Sí':
                    $user->add_role('4fitfamily');
                    break;
                case 'No':
                    $user->remove_role('4fitfamily');
                    break;
            }
        }
        
    }

    // FORMULARIO RENOVACIÓN CLIENTE
    if ( $form_id == 19 ) {

        $user_mail = $_POST['item_meta'][398];
        $user = get_user_by( 'email',  $user_mail );
        
        if(!$user) {
            return;
        }
        
        // ASIGNACIÓN DE ROLES
        $control    = intval( FrmEntryMeta::get_entry_meta_by_field( $entry_id, 413 ) );
        assigns_roles_five_digits_control($user, $control);

        // UPDATE POST META

        $productos_comprados    = $_POST['item_meta'][416];
        $codigo_pedido          = $_POST['item_meta'][417];
        $cliente_requisitos     = $_POST['item_meta'][418];
        $importe_pedido         = $_POST['item_meta'][420];

        $update = update_user_meta( $user->ID, 'productos_comprados', $productos_comprados );
        $update = update_user_meta( $user->ID, 'codigo_pedido', $codigo_pedido );
        $update = update_user_meta( $user->ID, 'cliente_requisitos', $cliente_requisitos );
        $update = update_user_meta( $user->ID, 'importe_pedido', $importe_pedido );
        
        // UPDATE
        
        $update = update_user_meta( $user->ID, 'dia', 0 );
        $update = update_user_meta( $user->ID, 'dias_pausado', 0 );
        $update = update_user_meta( $user->ID, 'dias_totales', 0 );
    }

}

add_action( 'frmreg_after_create_user', 'do_custom_action_after_registration', 10, 2 );

function do_custom_action_after_registration( $user_id, $args ) {

    $form_id    = $args['entry']->form_id;
    $entry_id   = $args['entry']->id;

    // FORMULARIO ALTA CLIENTES #2 Y ALTA CLIENTE BÁSICO #24
    if ( $form_id == 2 || $form_id == 24 ) {
        $user       = get_user_by( 'ID', $user_id );

        if( $form_id == 2  ) {
            $control    = FrmEntryMeta::get_entry_meta_by_field( $entry_id, 91 );
            $user->add_role( 'suscripcin_4fitplan' );
            $fourhair = FrmEntryMeta::get_entry_meta_by_field( $entry_id, 671 );
            error_log($fourhair);
            if($fourhair == 'Sí - 1') {
                $user->add_role( '4hair' );
            }
        }
        if( $form_id == 24 ) {
            $control    = FrmEntryMeta::get_entry_meta_by_field( $entry_id, 568 );
            $user_type  = FrmEntryMeta::get_entry_meta_by_field( $entry_id, 584 );
            $add_role   = FrmEntryMeta::get_entry_meta_by_field( $entry_id, 669 ); // Parámetro ?add_role=4hair
            if($user_type == 'basico') { //para cliente basico
                $user->add_role( 'cliente_basico' );
            } elseif($user_type == 'normal') { // Para suscripcion normal
                $user->add_role( 'suscripcin_4fitplan' );
            }  elseif($user_type == 'mensual') { // Para suscripcion mensual
                $user->add_role( 'suscripcion_mensual' );
            }
            if(!empty($add_role)) {
                $user->add_role( $add_role );
            }
        }
        assigns_roles_five_digits_control($user, $control);
    }
    
    //ALTA NUEVO DISTRIBUIDOR
    if( $form_id  == 15 || $form_id  == 25) {
        $user   = get_user_by( 'ID', $user_id );
        if($form_id  == 25) {
            $origin_distributor  = FrmEntryMeta::get_entry_meta_by_field( $entry_id, 580 );
            $user_phone          = FrmEntryMeta::get_entry_meta_by_field( $entry_id, 574 );
            $id     = update_user_meta($user_id, 'origin_distributor', $origin_distributor);
            fourfit_add_whatsapp_meta( $user_id, $user_phone );
        }
        $id     = update_user_meta($user_id, 'id_distribuidor', $user_id);
        
        $user->add_role('distribuidor');
    }
    $user->add_role( 'nuevousuario' );
    $user->add_role( 'activo' );
}


// ASIGNAR ROLES A USUARIO SEGÚN DÍGITO DE CONTROL DE 5 DÍGITOS

function assigns_roles_five_digits_control($user, $control) {
    // ANTES ELIMINAMOS ROLES ASIGNADOS SI LOS HAY
   
    $nutricional_value = substr($control, 1, 3);
 

    // NUTRICION POR ROLES SEPARADOS
    assign_nutrition2($user, $nutricional_value);
    
    // ROLES SEPARADOS
    $nivel_ejercicio_value = substr($control, 4, 1);
    $lugar_ejercicio_value = substr($control, 5, 1);

    $sexo_value            = substr($control, 0, 1);

    assing_ejercicio_2($user, $nivel_ejercicio_value, $lugar_ejercicio_value, $sexo_value );
    
    $fit_family     = substr($control, 6, 1);

    if(isset($fit_family)) {
        switch ($fit_family) {
            case '1':
                $user->add_role('4fitfamily');
                break;
            case '2':
                $user->remove_role('4fitfamily');
                break;
            case '0':
                $user->remove_role('4fitfamily');
                break;
        }
    }
}




function assing_ejercicio_2($user, $nivel_ejercicio_value, $lugar_ejercicio_value, $sexo_value ) {
    
    $ejercicios_roles = ejercicios_roles();
    foreach($user->roles as $user_role) {
        if(in_array($user_role, $ejercicios_roles)) {
            $user->remove_role($user_role);
        }
    }

    switch ($nivel_ejercicio_value) {          
        case 1:
            $user->add_role('facil');
            break;
        case 2:
            $user->add_role('avanzado');
            break;
        case 3:
            $user->add_role('iniciacion');
            break;
        case 4:
            $user->add_role('pro');
            break;
    }
    switch ($lugar_ejercicio_value) {
                
        case 1:
            $user->add_role('casa');
            break;
        case 2:
            $user->add_role('gym');
            break;
    }
    switch ($sexo_value) { 
        case 1:
            $user->add_role('mujer');
            break;
        case 2:
            $user->add_role('hombre');
            break;
    }
}

// FUNCIÓN PARA ASIGNAR ROLES NUTRICIONALES
function assign_nutrition2($user, $nutricional_value) {

    $nutricional_objetivo_value = substr($nutricional_value, 0, 1);
    $nutricional_dieta_value    = substr($nutricional_value, 1, 1);
    $nutricional_tipo_value     = substr($nutricional_value, 2, 1);

    $nutricional_roles = alimentacion_all_roles();
    foreach($user->roles as $user_role) {
        if(in_array($user_role, $nutricional_roles)) {
            $user->remove_role($user_role);
        }
    }
    switch ($nutricional_objetivo_value) {
        // PERDIDA DE PESO
        case 1:
            $user->add_role('perdidapeso');
            break;
        case 2:
            $user->add_role('mantenimiento');
            break;
        case 3:
            $user->add_role('ganancia');
            break;
        case 4:
            $user->add_role('bajoencalorias');
            break;
        
    }
    switch ($nutricional_dieta_value) {
        // PERDIDA DE PESO
        case 1:
        $user->add_role('omnivoro');
        break;
        case 2:
        $user->add_role('sinlactosa');
        break;
        case 3:
        $user->add_role('celiaco');
        break;
        case 4:
        $user->add_role('vegano');
        break;
        case 5:
        $user->add_role('vegetariano');
        break;
        case 6:
        $user->add_role('sinpescado');
        break;
        case 7:
        $user->add_role('pescetariano');
        break;
                
    }
    switch ($nutricional_tipo_value) {
        // PERDIDA DE PESO
        case 1:
            $user->add_role('abierto');
            break;
        case 2:
            $user->add_role('cerrado');
            break;
        
    }
}


add_action( 'init', 'accept_conditions' );

function accept_conditions() {
    if( isset( $_GET['accept-conditions'] ) ) {
        $user = wp_get_current_user();
        $user->remove_role('nuevousuario');
    }
}

/**
CAMBIAMOS EL ROL DEL USUARIO
*/
add_action('frm_after_create_entry', 'accept_conditions_after_form', 20, 2);
function accept_conditions_after_form($entry_id, $form_id){
 if($form_id == 8){ //change 24 to the form id of the form to copy
    $user = wp_get_current_user();
    $user->remove_role('nuevousuario');
 }
}

//AÑADIR ROLES A USUARIOS

add_filter('init', 'fourfit_endpoints');

function fourfit_endpoints() {
    /*
    // AÑADO ROLES A USUSARIO SEGÚN ROLES QUE YA TIENE
    if(isset($_GET['rol-from']) && isset($_GET['rol-to'])) {
        $rol_from   = $_GET['rol-from'];
        $rol_to     = $_GET['rol-to'];

        $args = array(
            'number' => -1,
            'role' =>  $rol_from
        );

        $users = get_users($args);
        $message = '';
        
        foreach($users as $key => $user) {
            $user->add_role($rol_to);
            $message .= '[' . $key . '] Email ' . $user->user_email . ' añadido rol ' . $rol_to . PHP_EOL;

        }


        if(!empty($message)) {
            trazas($message);
        }
    }

    // ELIMINAR ROLES PARA USUARIOS

    if( isset($_GET['remove_rol']) ) {
        $remove_rol   = $_GET['remove_rol'];
 

        $args = array(
            'number' => -1,
            'role' =>  $remove_rol
        );

        $users = get_users($args);
        $message = '';
        
        foreach($users as $key => $user) {
            $user->remove_role($remove_rol);
            $message .= '[' . $key . '] Email ' . $user->user_email . ' eliminado rol ' . $remove_rol . PHP_EOL;
        }

        if(!empty($message)) {
            trazas($message);
        }
    }

    if(isset($_GET['set_dist_id'])) {

        $args = array(
            'number' => -1,
            'role__not_in' =>  'distribuidor'
        );
        $users = get_users($args);
        $message = '';

        foreach($users as $key => $user) {
            $distribuidor_email = get_user_meta($user->ID, 'distribuidor_4fit', true);
            $distribuidor_id    = get_user_meta($user->ID, 'id_distribuidor', true);
            if(empty($distribuidor_id) && !empty($distribuidor_email)) {
                $distribuidor = get_user_by('email', $distribuidor_email);
                
                if( $distribuidor ) {
                    $update = update_post_meta($user->ID, 'id_distribuidor', $distribuidor->ID , true);
                   // $message .= '[' . $key . ' - ' . $user->ID . '] Asignada la id de distribuidor al usuario' . $user->user_email . PHP_EOL;
                } else {
                    //$message .= '[' . $key .  ' - ' . $user->ID . '] Sin id de distribuidor no se ha encotrado id de distribuidor para el email: ' . $distribuidor_email . PHP_EOL;

                }  
            } else {

                if(empty($distribuidor_email)) {
                   // $message .= '[' . $key .  ' - ' . $user->ID . '] No tiene email de distribuidor'  . PHP_EOL ;
                   $message .= '['  . $user->ID . ' - ' . $user->user_email  . '] No tiene email de distribuidor'  . PHP_EOL ;
                } 
                if(!empty($distribuidor_id)) {
                   // $message .= '[' . $key .  ' - ' . $user->ID . '] Ya tiene id el de distribuidor: ' . $distribuidor_id . PHP_EOL ;
                } 
            }

        }
        if(!empty($message)) {
            trazas($message);
        }
    }

    // Ajustar activos
    if(isset($_GET['active_users'])) {
        $args = array(
            'number' => -1,
            'role__not_in' =>  array('bloqueado', 'pausado')
        );
        $users = get_users($args);
        foreach($users as $key => $user) {
            $user->add_role('activo');
        }
    }
    // Ajustar activos no bloqueados
    if(isset($_GET['duplicate_roles'])) {
        $args = array(
            'number' => -1,
            'role' =>  'activo'
        );
        $users = get_users($args);
        foreach($users as $key => $user) {
            if(in_array('bloqueado', $user->roles)) {
                $user->remove_role('bloqueado');
            }
        }
    }

    
    if ( isset($_GET['init_duration']) ) {
        global $wpdb;
        $table = $wpdb->prefix . 'pms_member_subscriptions';
        $members = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT ms.user_id, ms.expiration_date
                FROM wp_pms_member_subscriptions ms
                LEFT JOIN wp_usermeta um
                ON ms.user_id = um.user_id
                WHERE 1=1
                AND um.meta_key = 'wp_capabilities'
                AND ( um.meta_value LIKE '%bloqueado%' OR um.meta_value LIKE '%pausado%' )
                AND ms.status = 'active';"
            )
        );


        foreach($members as $key => $member ) {

            $expiration_date          = new DateTime(  $member->expiration_date );
            $expiration_date_modify   = $expiration_date->modify("+1day");
            $expiration_date_format   = $expiration_date_modify->format("Y-m-d H:i:s");
            
            $wpdb->update( $table, array('expiration_date' => $expiration_date_format ), array('user_id' => $member->user_id), array('%s'), array('%d') );
  
        }
    }
    if(isset($_GET['update_dist'])) {
        $current_user_id = get_current_user_id();
        $args = array(
            'number' => -1,
            'role' =>  'distribuidor',
            
        );
        
        $users = get_users($args);
        
        foreach($users as $key => $user) {
            
            $id_distribuidor = get_user_meta($user->ID , 'id_distribuidor', true); 
            if($id_distribuidor != $user->ID  && !empty($id_distribuidor) ) {
                $id = update_user_meta($user->ID, 'id_distribuidor', $user->ID);
                $message = 'usuario con id ' . $user->ID . ' tiene el id de distribuidor ' . $id_distribuidor;
                trazas($message );
            }
        }
    }
    // Ajustar activos no bloqueados
    if(isset($_GET['duplicate_roles'])) {
        $args = array(
            'number' => -1,
            'role' =>  'activo'
        );
        $users = get_users($args);
        foreach($users as $key => $user) {
            if(in_array('bloqueado', $user->roles)) {
                $user->remove_role('bloqueado');
            }
        }
    }
    */

    // antes ajustar 
    if(isset($_GET['fix_roles_to_member_1'])) {
        // 48760 distribuidor
        // 48170 Suscripción 4fit
        $users = get_users_id_by_suscription_plan( 48760, false );
        foreach ($users as $key => $user_id ) {
            $user = get_user_by('ID', $user_id );
            if( in_array('suscripcin_4fitplan', $user->roles ) || in_array('suscripcin_4fitplan_suscrito', $user->roles )   ) {
                $user->remove_role('suscripcin_4fitplan');
                $user->remove_role('suscripcin_4fitplan_suscrito');
            }
        }
    }
    if(isset($_GET['fix_roles_to_member_1_1'])) {
        // 48760 distribuidor
        // 48170 Suscripción 4fit
        $users = get_users_id_by_suscription_plan( 48760, false );
        foreach ($users as $key => $user_id ) {
            $user = get_user_by('ID', $user_id );
            if(is_array($user->roles )) {
                if( !in_array('distribuidor', $user->roles ) ) {
                    $user->add_role('distribuidor');
                }
            }
        }
    }
    if(isset($_GET['fix_roles_to_member_2'])) {
        // 48760 distribuidor
        // 48170 Suscripción 4fit
        $users = get_users_id_by_suscription_plan( 48760, 'is_active' );
        foreach ($users as $key => $user_id ) {
            $user = get_user_by('ID', $user_id );

            $user->add_role('distribuidor_suscrito');
            
        }
    }
    if(isset($_GET['fix_roles_to_member_3'])) {
        // 48760 distribuidor
        // 48170 Suscripción 4fit
        $users = get_users_id_by_suscription_plan( 48170, false );
        foreach ($users as $key => $user_id ) {
            $user = get_user_by('ID', $user_id );
            if(is_array($user->roles )) {
                if( in_array('distribuidor', $user->roles ) || in_array('distribuidor_suscrito', $user->roles )   ) {
                    $user->remove_role('distribuidor');
                    $user->remove_role('distribuidor_suscrito');
                }
            }
        }
    }
    if(isset($_GET['fix_roles_to_member_3_1'])) {
        // 48760 distribuidor
        // 48170 Suscripción 4fit
        $users = get_users_id_by_suscription_plan( 48170, false );
        foreach ($users as $key => $user_id ) {
            $user = get_user_by('ID', $user_id );
            if(is_array($user->roles )) {
                if( !in_array('suscripcin_4fitplan', $user->roles ) ) {
                    $user->add_role('suscripcin_4fitplan');
                }
            }
        }
    }
    if(isset($_GET['fix_roles_to_member_4'])) {
        // 48760 distribuidor
        // 48170 Suscripción 4fit
        $users = get_users_id_by_suscription_plan( 48170, 'is_active' );
        foreach ($users as $key => $user_id ) {
            $user = get_user_by('ID', $user_id );

            $user->add_role('suscripcin_4fitplan_suscrito');
            
        }
    }
    if(isset($_GET['fix_roles_to_member_5'])) {
        // 48760 distribuidor
        // 48170 Suscripción 4fit
        $users = get_users_id_by_suscription_plan( 48760, 'is_not_active' );
        foreach ($users as $key => $user_id ) {
            $user = get_user_by('ID', $user_id );
            if( in_array('distribuidor_suscrito', $user->roles ) ) {
                $user->remove_role('distribuidor_suscrito');
            }
        }
    }
    if(isset($_GET['fix_roles_to_member_6'])) {
        // 48760 distribuidor
        // 48170 Suscripción 4fit
        $users = get_users_id_by_suscription_plan( 48170, 'is_not_active' );
        foreach ($users as $key => $user_id ) {
            $user = get_user_by('ID', $user_id );
            if( in_array('suscripcin_4fitplan_suscrito', $user->roles ) ) {
                $user->remove_role('suscripcin_4fitplan_suscrito');
            }
        }
    }


    /*

    // Añadir respectivos roles para suscripción 
    if(isset($_GET['suscription_roles'])) {
        $args = array(
            'number' => -1,
            'role__in' =>  array('distribuidor', 'suscripcin_4fitplan')
        );
        $users = get_users($args);
        foreach($users as $key => $user) {
            if(in_array('distribuidor', $user->roles)) {
                $user->add_role('distribuidor_suscrito');
            }
            if(in_array('suscripcin_4fitplan', $user->roles)) {
                $user->add_role('suscripcin_4fitplan_suscrito');
            }
        }
    }
    // Añadir respectivos roles para suscripción 
    if(isset($_GET['suscription_roles_revert'])) {
        $args = array(
            'number' => -1,
            'role__in' =>  array('distribuidor_suscrito')
        );
        $users = get_users($args);
        foreach($users as $key => $user) {
            if(in_array('distribuidor_suscrito', $user->roles) && !in_array('distribuidor', $user->roles)) {
                $user->add_role('distribuidor');
                $message = 'usuario con id ' . $user->ID . ' se le ha asignado el id de distribuidor ';
                trazas($message );
            }
        }
    }
     // Añadir respectivos roles para suscripción 
     if(isset($_GET['remove_distribuidores'])) {
        $args = array(
            'number' => -1,
            'role__in' =>  array('distribuidor', 'suscripcin_4fitplan')
        );
        $users = get_users($args);
        $i = 1;
        foreach($users as $key => $user) {
            $roles = $user->roles;
            if(in_array('distribuidor', $roles) && in_array('suscripcin_4fitplan', $roles)) {
                $user->remove_role('distribuidor');
                $user->remove_role('distribuidor_suscrito');
                $message = $i . ' - usuario con id ' . $user->ID . ' se le ha quitado el rol de distribuidor ';
                $i++;
                trazas($message );
            }
        }
    }
   */
}


function fourfit_user_count() { 
    global $wpdb;
    // Consulta SQL para contar el número de usuarios
    $usercount = $wpdb->get_var("SELECT COUNT(ID) FROM $wpdb->users");

    $total_usercount = $usercount * 9;
    $usercont_format = str_pad($total_usercount, 6, "0", STR_PAD_LEFT); 
    $array_numeros = str_split($usercont_format, 1);
        $contador = '<ul class="cont-content">';
        
        foreach ($array_numeros as $key => $string_numero) {
            
        $contador .= '<li class="cont-number" data-number="'.$string_numero.'">'.$string_numero.'</li>';
        }
        $contador .= '</ul>';
        
        return $contador;
    return $contador; 
} 
// Creating a shortcode to display user count
add_shortcode('users_count', 'fourfit_user_count');


add_filter('frm_setup_new_fields_vars', 'fourfit_get_distribuidores_options', 20, 2);
function fourfit_get_distribuidores_options($values, $field){
    if($field->id == 465 || $field->id == 466){  // ID DEL CAMPO
        $args =    array(
            'role__in' => 'distribuidor',
            'role__not_in' => 'bloqueado',
            'fields'        => array ('ID','user_email')
        );
        $users = get_users($args);
        $values['use_key'] = true;
    
        foreach($users as $key => $user) {
            $values['options'][$user->ID] = $user->user_email;
        }
    }
    return $values;
}

/// CONFIGURACIÓN DE SELECTOR PARA QUE DEVUELVA OPCIONES CON LOS CLIENTES DE UN DISTRIBUIDOR
add_filter('frm_setup_new_fields_vars', 'fourfit_get_clientes_options', 20, 2);
function fourfit_get_clientes_options($values, $field){
    //if($field->id == 264 || $field->id == 222 || $field->id == 398){  // ID DEL CAMPO
    if($field->id == 585){  // ID DEL CAMPO
        $current_user   = wp_get_current_user();
        $user_id        = $current_user->ID;

        $args           = array(
            'role__in' => alimentacion_roles(),
        );
        if(in_array('administrator', $current_user->roles )) {
            $args['meta_key']   = 'id_distribuidor';
            $args['meta_value'] = $user_id;
        }

        $users          = get_users($args);

        $values['use_key'] = true;
    
        foreach($users as $key => $user) {
            $first_name     = get_user_meta($user->ID, 'first_name', true);
            $last_name      = get_user_meta($user->ID, 'last_name', true);
            $values['options'][$user->user_email] = array(
                'value' => $user->user_email,
                //'label' => var_export(get_user_meta($user_id), true)
                'label' => $first_name . ' ' . $last_name . ' (' . $user->user_email . ')'
            );
        }
    }
    return $values;
}



function fourfit_get_users_confirmation() {
    $html = '';
    if( isset($_GET['dist-from']) && isset($_GET['dist-to']) && isset($_GET['previo'])) {
        $dist_from_id   =  $_GET['dist-from'];
        $dist_from      = get_userdata($dist_from_id);
        $dist_to_id     =  $_GET['dist-to'];
        $dist_to        = get_userdata($dist_to_id);
        $previo         = intval($_GET['previo']);

        $users_from = fourfit_get_clientes($dist_from_id);
        $users_to   = fourfit_get_clientes($dist_to_id);

        $back_link = get_the_permalink();
        $confirm_link = get_the_permalink() . '?dist-from=' . $dist_from_id . '&dist-to=' . $dist_to_id . '&previo=0';

        if ($previo == 1) {
            if(!empty($users_from)) {
                
                $html .= '<h4>Tomaremos los usuarios de ' . $dist_from->user_email . ':</h4>';
                
                $html .= '<ul>';
                foreach ($users_from as $user_from ) {
                    $html .= '<li>' . $user_from->user_email . '</li>';
                }
                $html .= '</ul>';
            } else {
                $html .= '<h4>El distribuidor ' . $dist_from->user_email . ' no tiene clientes</h4>';
            }
            
            if(!empty($users_to)) {
                
                $html .= '<h4>Y los asignaremos a ' . $dist_to->user_email . ' que ya tiene estos clientes:</h4>';
                $html .= '<ul>';
                foreach ($users_to as $user_to ) {
                    $html .= '<li>' . $user_to->user_email . '</li>';
                }
                $html .= '</ul>';
            } else {
                $html .= '<h4>El distribuidor ' . $dist_to->user_email . ' no tiene clientes</h4>';
            }
            $html .= '<h4>¿Es correcto?</h4>';
            
            
            $html .= '<a href="' . $back_link . '" class="elementor-button">No, quiero volver</a>';
            if(!empty($users_from) && !empty($users_to)) {
                
                $html .= ' - <a href="' . $confirm_link . '" class="elementor-button">¡Correcto! Continuamos</a>';
            }
        }
        if ($previo == 0 && !empty($users_from) && !empty($users_to)) {
            foreach($users_from as $user_from) {
                $result[] = update_user_meta( $user_from->ID, 'id_distribuidor' , $dist_to->ID );
            }
            if(!in_array(false, $result)) {

                $users_to   = fourfit_get_clientes($dist_to_id);
                $html .= '<h4>Ahora ' . $dist_to->user_email . ' tiene estos clientes:</h4>';
                $html .= '<ul>';
                foreach ($users_to as $user_to ) {
                    $html .= '<li>' . $user_to->user_email . '</li>';
                }
                $html .= '</ul>';
            } else {
                $html .= '<h4>Ocurrió un error comprueba los datos</h4>';

            }
            
            $html .= '<h4>¿Qué hacemos ahora?</h4>';
            $html .= '<a href="' . $back_link . '" class="elementor-button">¿Hacemos otro cambio?</a> - ';
            $html .= '<a href="' . get_bloginfo('url') . '" class="elementor-button">Volvemos al inicio</a>';
        }
    }
    return $html;
}
add_shortcode('users_confirmation', 'fourfit_get_users_confirmation');

function fourfit_get_clientes($distribuidor_id) {
    $args = array(
        'role__not_in' => 'distribuidor',
        'meta_key'      => 'id_distribuidor',
        'fields'        => array('ID', 'user_email'),
        'meta_value'    => $distribuidor_id
    );
    $users_id = get_users($args);

    return $users_id;
}

function mg_dashboard_redirect(){
    
	// Obtengo el usuario actual
	$user = wp_get_current_user();

    $exceptions = array(
        3407,
        48126,
        44956,
        3379,
        3347,
        3189
    );
	// Pregunto por un rol específico
	if( in_array( 'bloqueado', $user->roles ) && !in_array(get_the_ID(), $exceptions) ) {
        // En caso que se encuentre en el array de roles lo redirecciono.
		// En este caso lo estoy enviado al listado de páginas.
		wp_redirect(get_the_permalink(3407) );
		exit;   
    }
}
add_action('template_redirect', 'mg_dashboard_redirect');


function fourfit_set_suscription_duration_meta ( $subscription_id , $new_data ) {

    if( empty( $subscription_id ) || empty( $new_data ) )
        return;

    if( empty( $new_data['start_date'] ) )
        return;
    if( empty( $new_data['expiration_date'] ) )
        return;

    if( empty( $new_data['status'] ) || $new_data['status'] != 'active' )
        return;

    $member_subscription = pms_get_member_subscription( $subscription_id );

    
    $stardate           = new DateTime(  $new_data['start_date']  );
    $expiration_date    = new DateTime(  $new_data['expiration_date'] );
    
    $interval_date   = $stardate->diff( $expiration_date );
    
    update_user_meta($member_subscription->user_id, 'init_duration_plan', intval($interval_date->days));
  
}
add_action( 'pms_member_subscription_insert', 'fourfit_set_suscription_duration_meta', 11, 2 );
add_action( 'pms_member_subscription_update', 'fourfit_set_suscription_duration_meta', 11, 2 );



function fourfit_cancel_subscription_on_order_status_failed( $order_id, $order ) {
    $user_id = $order->get_user_id();

    $member = pms_get_member( $user_id );


    foreach($member->subscriptions as $subscription ) {
            global $wpdb;
            $table = $wpdb->prefix . 'pms_member_subscriptions';
    
            $wpdb->update($table, array('status' => 'abandoned'), array('id' =>  intval($subscription['id']) ), array('%s'), array('%d') );
    }

    // Do something here
}
add_action('woocommerce_order_status_failed', 'fourfit_cancel_subscription_on_order_status_failed', 11, 2);

// Código para oferta temporal de ampliacion de plan + 6 semanas para los que compren "Essential + omega"

function pms_member_add_extra_time_subscription_inserted( $subscription_id, $new_data ) {

    if( empty( $subscription_id ) || empty( $new_data ) )
        return;

    if( empty( $new_data['subscription_plan_id'] ) )
        return;

    if( empty( $new_data['status'] ) || $new_data['status'] != 'active' )
        return;

    $member_subscription = pms_get_member_subscription( $subscription_id );
    if( isset($member_subscription->user_id ) ) {

        $user_id = $member_subscription->user_id;
            
        global $wpdb;
            
        $has_product = $wpdb->get_var(
            $wpdb->prepare( "SELECT * 
            FROM {$wpdb->prefix}usermeta
            WHERE meta_key = 'productos_comprados' 
            AND meta_value
            LIKE '%Essential + omega%' 
            AND user_id = %d", $user_id )
        );
        $now = date('Y-m-d');

        
        if( !empty( $has_product ) && $now <= '2023-02-01' ) {

            $stardate           = new DateTime();
            
            $startdate_modified = $stardate->modify('+24 week');
            
            $expiration_date = $startdate_modified->format('Y-m-d H:i:s');

            $wpdb->update( 'wp_pms_member_subscriptions', array('expiration_date' => $expiration_date ), array('id' => $subscription_id) , '%s' );

            trazas( 'Nueva fecha de expiración ' . $expiration_date );


        } else {
            trazas('el usuario no ha comprado el producto o la fecha ' . $now . '  es mayor a 2023-01-31');
        }
    } else {
        trazas('usuario no valido');
    } 
}
add_action( 'pms_member_subscription_insert', 'pms_member_add_extra_time_subscription_inserted', 5, 2 );



add_action( 'show_user_profile', 'fourfit_display_user_meta_on_profile' );
add_action( 'edit_user_profile', 'fourfit_display_user_meta_on_profile' );

function fourfit_display_user_meta_on_profile($user) { 
    $user_id = $user->ID;
    $productos_comprados = get_user_meta($user_id, 'productos_comprados', true);
    echo '<h3>Productos comprados: </h3>';
    if(empty($productos_comprados) || !is_array($productos_comprados) ) {
        echo '<p>Este usuario no ha comprado productos.</p>';
    } else {
        echo '<ul>';
        foreach($productos_comprados as $producto) {
            echo '<li>' . $producto . '.</li>';
        }
        echo '</ul>';
    }

}

// DADO QUE SE HAN PERDIDO ROLES NECESARIOS AL CUMPLIR LA SUSCRIPCIÓN, LOS VOLVEMOS A ASIGNAR

function execute_on_add_user_role_event($user_id, $role){
    if($role == 'suscripcin_4fitplan_suscrito' || $role == 'distribuidor_suscrito' ) {
        $user = new WP_User( $user_id );
        if( $role == 'suscripcin_4fitplan_suscrito' && !in_array('suscripcin_4fitplan', $user->roles)) {
            $user->add_role('suscripcin_4fitplan');
        }
        if( $role == 'distribuidor_suscrito' && !in_array('distribuidor', $user->roles)) {
            $user->add_role('distribuidor');
        }
    }
 }

 add_action( "add_user_role", "execute_on_add_user_role_event" , 999, 2);

 function get_users_id_by_suscription_plan ($subscription_id, $active) {
    global $wpdb;
    if($active == 'is_active') {
        $users = $wpdb->get_col(
            $wpdb->prepare("SELECT user_id
            FROM {$wpdb->prefix}pms_member_subscriptions
            WHERE subscription_plan_id = %d
            AND status = 'active'", $subscription_id)
        );
    } elseif($active == 'is_not_active') {
        $users = $wpdb->get_col(
            $wpdb->prepare("SELECT user_id
            FROM {$wpdb->prefix}pms_member_subscriptions
            WHERE subscription_plan_id = %d
            AND status <> 'active'", $subscription_id)
        );
    } else {
        $users = $wpdb->get_col(
            $wpdb->prepare("SELECT user_id
            FROM {$wpdb->prefix}pms_member_subscriptions
            WHERE subscription_plan_id = %d", $subscription_id)
        );
    }
    return $users;
 }
 //add_shortcode('users', 'get_users_id_by_suscription_plan');
 
 function fourfit_change_subscription_verificacion($attrs){
    $attrs = shortcode_atts(
		array(
			'to'    => 0,
		),
		$attrs,
		'verification_message'
	);
    $message = '';

    if(isset($_GET['perfil-cliente']) && $attrs['to']) {
        $cliente_id             = intval($_GET['perfil-cliente']);
        $cliente_name           = get_user_meta($cliente_id, 'first_name', true);
        $cliente_last_name      = get_user_meta($cliente_id, 'last_name', true);


        $plan_to                = intval($attrs['to']);
        $plan_to_name           = get_the_title($plan_to);
        $message       .= '<h3>¡Atención!</h3> ';
        
        $message       .= '<p>';
        $message       .= '<strong>';
        $message       .= 'Estás a punto de cambiar el plan de ';
        $message       .= '</strong>';
        $message       .= $cliente_name . ' ' . $cliente_last_name . ' a <strong>' .  $plan_to_name . '</strong>';
        $message       .= '</p>';
    } else {
        $soporte_link   = get_the_permalink(78476);
        $message       .= '<h3>Enlace no válido</h3>';
        $message       .= '<p>';
        $message       .= 'Contacta con el <a href="' . $soporte_link . '">Soporte 4fit</a> e intentaremos resolverlo lo antes posible.';
        $message       .= '</p>';
        
    }
    return $message;
 }
 add_shortcode('verification_message', 'fourfit_change_subscription_verificacion');

 function fourfit_change_subscription(){
    $response = '';
    if(isset($_GET['_wpnonce']) && isset($_GET['cliente'])) {
        $verify     = wp_verify_nonce($_GET['_wpnonce'], 'cambiar-cliente-' . $_GET['cliente']);
    }
    if(isset($_GET['distribuidor']) && isset($_GET['from']) && isset($_GET['to']) && $verify) {
        $distribuidor_id        = intval($_GET['distribuidor']);
        $distribuidor_name      = get_user_meta($distribuidor_id, 'first_name', true);
        $distribuidor_last_name = get_user_meta($distribuidor_id, 'last_name', true);

        $cliente_id             = intval($_GET['cliente']);
        $cliente_name           = get_user_meta($cliente_id, 'first_name', true);
        $cliente_last_name      = get_user_meta($cliente_id, 'last_name', true);

        $plan_from              = intval($_GET['from']);
        $plan_from_name         = get_the_title($plan_from);

        $plan_to                = intval($_GET['to']);
        $plan_to_name           = get_the_title($plan_to);

        $member                 = pms_get_member( $cliente_id );    
        $subscription           = reset($member->subscriptions);

        $subscription_id        = $subscription['id'];
        $subscription_start_date= $subscription['start_date'];

        $plan_to_duration       = get_post_meta($plan_to, 'pms_subscription_plan_duration', true);
        $plan_to_duration_unit  = get_post_meta($plan_to, 'pms_subscription_plan_duration_unit', true);


        $datetime               = new DateTime($subscription_start_date);
        $modify_datetime        = $datetime->modify('+' . $plan_to_duration . $plan_to_duration_unit);
        $subscription_end_date  = $modify_datetime->format('Y-m-d H:i:s');
        
        global $wpdb;

        $update_plan = $wpdb->update( $wpdb->prefix . 'pms_member_subscriptions',
            [ 'subscription_plan_id' =>  $plan_to, 'expiration_date' => $subscription_end_date ],
            [ 'user_id' => $cliente_id  ],
        );

        if( $update_plan ) {
            
            $user = get_userdata($cliente_id);

            $remove_roles = array(
                'cliente_basico', 
                'cliente_basico_suscrito', 
                'suscripcin_4fitplan', 
                'suscripcin_4fitplan_suscrito',
                'distribuidor', 
                'distribuidor_suscrito'
            );
            
            foreach($remove_roles as $role) {
                $user->remove_role($role);
            }

            $add_roles = array();
            switch ($plan_to) {
                case 94516: // Basico
                    $add_roles = array('cliente_basico', 'cliente_basico_suscrito');
                    break;
                case 48170: // Suscripción 4fit
                    $add_roles = array('suscripcin_4fitplan', 'suscripcin_4fitplan_suscrito');
                    break;
                case 48760: // Distribuidor
                    $add_roles      = array('distribuidor', 'distribuidor_suscrito');
                    $user_phone     = get_user_meta( $cliente_id , 'telefono', true);
                    fourfit_add_whatsapp_meta( $cliente_id, $user_phone );
                break;
            }
            foreach($add_roles as $role) {
                $user->add_role($role);
            }
            

            
            
            $subscription_logs    =  $wpdb->get_var($wpdb->prepare("SELECT meta_value 
                FROM {$wpdb->prefix}pms_member_subscriptionmeta 
                WHERE meta_key = 'logs' 
                AND member_subscription_id = %d", 
            $subscription_id));
        
            $subscription_logs = unserialize($subscription_logs);       

            $response       .= '<h3>Suscripción cambiada</h3> ';
            $response       .= '<strong>';
            $response       .= 'Distribuidor: ';
            $response       .= '</strong>';
            $response       .= $distribuidor_name . ' ' . $distribuidor_last_name . ' <br>';
            $response       .= '<strong>';
            $response       .= 'Ha cambiado el plan del cliente: ';
            $response       .= '</strong>';
            $response       .= $cliente_name . ' ' . $cliente_last_name . ' de <strong>' . $plan_from_name . '</strong> a <strong>' .  $plan_to_name . '</strong>';
            
            $subscription_logs[] = array (
                
                'date' => date('Y-m-d H:i:s'),
                'type' => 'admin_note',
                'data' => array (
                    'note' => $response,
                    'who' => $distribuidor_id
                ),
            );
            
            $subscription_logs = serialize($subscription_logs);
            
            $update_plan = $wpdb->update( $wpdb->prefix . 'pms_member_subscriptionmeta',
                [ 'meta_value' =>  $subscription_logs ],
                [ 'meta_key' => 'logs', 'member_subscription_id' => $subscription_id ],
                [ '%s' ]
            );
        } else {

            $response       .= 'Ocurrió un error';
        }

    } else {
        $response       .= 'Enlace no válido';
    }
    return $response;
 }
 add_shortcode('change_page', 'fourfit_change_subscription');

 function fourfit_change_subscription_url ($attrs) {
    $attrs = shortcode_atts(
		array(
			'to' => 0,
		),
		$attrs,
		'change_url'
	);
    if(isset($_GET['perfil-cliente'])) {
        if($_GET['perfil-cliente'] && $attrs['to']) {
            $url_final          = '';
            $site_url           = get_the_permalink(96432);
            $distribuidor_id    = get_current_user_id();
            $cliente_id         = intval($_GET['perfil-cliente']);
            
            $member             = pms_get_member( $cliente_id );
            
            $subscription       = reset($member->subscriptions);
            $plan_from          = $subscription['subscription_plan_id'];
            $plan_to            = $attrs['to'];
            
            if(isset($plan_from) ) {
                $url        = $site_url . '?distribuidor=' . $distribuidor_id . '&cliente=' .  $cliente_id . '&from=' . $plan_from . '&to=' . $plan_to;
                $url_final  = wp_nonce_url($url, 'cambiar-cliente-' . $cliente_id);
            } else {
                $url_final  = '#';
            }
            return $url_final;
        }
    }
 }
 add_shortcode('change_url', 'fourfit_change_subscription_url' );

 function fourfit_client_suscription_name() {
    $message = '';
    if(isset($_GET['perfil-cliente'])) {
        $user_id            = intval($_GET['perfil-cliente']);
        $member             = pms_get_member( $user_id );

        $subscription       = reset($member->subscriptions);
        $subscription_id    = $subscription['subscription_plan_id'];
        $subscription_name  = get_the_title($subscription_id);
        if(!empty($subscription_id)) {
            $message .= 'Este cliente está suscrito como <strong>' . $subscription_name . '</strong>';
        } else {
            $message .= 'Este cliente no tiene ninguna suscripción aún';
        }
    }
    return $message;
 }
 add_shortcode('subscription_name', 'fourfit_client_suscription_name');

 function fourfit_client_suscription_id() {
    $suscription = 0;
    if(isset($_GET['perfil-cliente'])) {
        $user_id            = intval($_GET['perfil-cliente']);
        $member             = pms_get_member( $user_id );

        $subscription       = reset($member->subscriptions);
        $subscription_id    = $subscription['subscription_plan_id'];

        if(!empty($subscription_id)) {
            $suscription = intval($subscription_id);
        } else {
            $suscription = 0;
        }
    }
    return $suscription;
 }
 add_shortcode('subscription_id', 'fourfit_client_suscription_id');

 function fourfit_add_whatsapp_meta( $user_id, $user_phone ){

    $user_phone     = preg_replace("/[^0-9]/", "", $user_phone); // Elimina caracteres no numéricos
    $whatsapp_link  = 'https://wa.me/' . $user_phone;
    // Actualizar el metadato del usuario.
    update_user_meta( $user_id, 'whatsappweb', $whatsapp_link );

    // ACTUALIZAMOS DATOS DE USUARIO
    $user_data = array(
        'user_url' => $whatsapp_link  // Nueva URL de whatsapp
    );
    wp_update_user( array_merge( array( 'ID' => $user_id ), $user_data ) );
}