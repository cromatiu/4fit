<?php 

/*
Plugin Name: 4 Fit PLan
Plugin URI: https://croamtiu.net
Description: Plugin personalizado para 4fitplan.com
Version:1.0
Author: https://croamtiu.net
Author URI: Francisco Rodríguez
License: No tiene
*/

use FileBird\Classes\Tree;

function forfit_wp_enqueue_scripts() {
    $version = '005';
    wp_enqueue_style( 'custom-styles', plugin_dir_url( __FILE__ ) . 'assets/css/custom-styles.css', array(), $version );
    
    wp_register_script( 'custom-scripts', plugin_dir_url( __FILE__ ) . 'assets/js/custom-scripts.js', array(), $version );
    wp_register_script( 'flatpikr', plugin_dir_url( __FILE__ ) . 'assets/js/flatpickr.min.js', array(), $version );
    wp_register_script( 'flatpikr-es', plugin_dir_url( __FILE__ ) . 'assets/js/fatpickr.es.js', array(), $version );
    wp_localize_script( 'custom-scripts', 'WPURLS', array( 'siteurl' => get_option('siteurl') )); 
    

    wp_enqueue_script( 'localize-url' );    
    wp_enqueue_script( 'custom-scripts' );
    wp_enqueue_script( 'flatpikr' );
    wp_enqueue_script( 'flatpikr-es' );
}

add_action( 'wp_enqueue_scripts', 'forfit_wp_enqueue_scripts', 20 );

function forfit_admin_wp_enqueue_scripts() {
    // Registrar jQuery desde la biblioteca de WordPress
    wp_enqueue_style( 'ff-admin-styles', plugin_dir_url( __FILE__ ) . 'assets/css/admin-styles.css', array(), '0.1' );
    wp_enqueue_script('jquery');
    wp_register_script('ff-script-admin', plugin_dir_url( __FILE__ ) . 'assets/js/admin-scripts.js', array('jquery'), '1.0', true);
    wp_localize_script( 'ff-script-admin', 'WPURLS', array( 'siteurl' => get_option('siteurl') )); 
    wp_enqueue_script( 'localize-url' );    
    // Añadir tu script personalizado para el área de administración
    wp_enqueue_script('ff-script-admin');
}

add_action('admin_enqueue_scripts', 'forfit_admin_wp_enqueue_scripts');


function process_revision_note () {
    $response['success']     = false;
    $response['dist_note']  = '';
    if(isset($_POST['field_id']) && isset($_POST['entry_id']) && isset($_POST['dist_note']) ) {

        $entry_meta = new FrmEntryMeta;
        $field_id   = intval( $_POST['field_id'] );
        $entry_id   = intval( $_POST['entry_id'] );
        $dist_note  = $_POST['dist_note'];

        $response['success']   = $entry_meta::update_entry_meta( $entry_id, $field_id, null, $dist_note );
        
        if ( $response['success'] == 0 ) {
            $response['success'] = $entry_meta::add_entry_meta( $entry_id, $field_id, null, $dist_note );
        } 

        global $wpdb;
        $dist_note_array        = $wpdb->get_col($wpdb->prepare("SELECT meta_value FROM {$wpdb->prefix}frm_item_metas WHERE field_id = {$field_id} AND item_id = {$entry_id} LIMIT 1"));

        if(isset( $dist_note_array[0] ) && $dist_note_array[0] != NULL ) {
            $response['dist_note']  = $dist_note_array[0];
        }
     
    } 

    wp_send_json(   $response   );
    exit;
    wp_die();

}
add_action('wp_ajax_process_revision_note', 'process_revision_note');
add_action('wp_ajax_nopriv_process_revision_note', 'process_revision_note');


function display_form_notes ($field_id, $entry_id, $dist_note = '') {
    $html = '';
    
    $link_text = (empty($dist_note)) ? 'Crear nota' : 'Modificar';
    
    ob_start();
    ?>  
        <div class="manage-notes">

            <div class="form-content" style="display: none;">
                <form action="" class="note-form">
                    <input type="hidden" name="field_id" value="<?php echo $field_id ?>">
                    <input type="hidden" name="entry_id" value="<?php echo $entry_id ?>">
                    <input type="hidden" name="action" value="process_revision_note" >
                    <textarea name="dist_note" id="" cols="30" rows="10"><?php echo $dist_note ?></textarea>
                    <div class="submit-content">
                        <input type="submit" value="Publicar nota">
                    </div>
                </form>
            </div>
            <div class="note-content">
                <?php echo $dist_note; ?>
            </div>
            <div class="open-form" >
                <a href="#" class="open-form-link" data-empty="Crear nota" data-modify="Modificar"><?php echo $link_text; ?></a></div>
            </div>
        <?php

    $html .= ob_get_clean();
    ob_flush();
    wp_reset_postdata();

    return $html;
}
/*
function form_for_tests () {
    echo display_form_notes (472, 51760, 'hay nota');
}
add_action('wp_body_open', 'form_for_tests');
*/

// ARCHIVOS INCLUÍDOS

require('keys.php');
require('manage-users.php');
require('validate-fields.php');
require('woocommerce-hooks.php');
require('shortcodes.php');
require('cronjobs.php');
require('subscriptions-control.php');
require('billing.php');

require('inc/ajax-handler.php');
require('inc/helper-functions.php');
require('inc/api-handler.php');
require('inc/db-handler.php');
require('inc/form-handler.php');
require('inc/shortcodes.php');
require('inc/signup-form.php');
require('inc/db_admin.php');
require('inc/user-data.php');

register_activation_hook(__FILE__, 'crear_tabla_planes_alimentacion');

function get_role_name($role_slug) {
    $role_name = '';

    global $wp_roles;

    foreach($wp_roles->roles as $key => $role)  {
        
        if($key == $role_slug ) {
            
            $role_name = $role['name'];
            
        }
            
    }
    return $role_name;
}

function render_user_roles($user) {
    $html = '';
    $html = var_export($user->caps, true);

    if(isset($user->caps)) {
        $caps = $user->caps;
        $html = '<ul>';
        foreach ($caps as $key => $cap) {
            if($key != 'subscriber' && $key != 'bloqueado' && $key != 'nuevousuario' && $key != 'wfls-active' && $key != 'wfls-inactive' ) {
                $group = '';
                if(in_array($key, alimentacion_all_roles())) {
                    $group = '<strong>Plan de alimentaci&oacute;n asignado:</strong> ';
                }
                if(in_array($key, ejercicios_roles())) {
                    $group = '<strong>Plan de ejercicio asignado:</strong> ';
                }
                $html .= '<li>';
                $html .= $group . get_role_name($key);
                $html .= '</li>';
            }
        }
        $html .= '</ul>';
    }

    return $html;
}

function alimentacion_all_roles() {
    $clients_roles = array(
        'perdidapeso',
        'mantenimiento',
        'ganancia',
        'omnivoro',
        'sinlactosa',
        'celiaco',
        'vegano',
        'vegetariano',
        'sinpescado',
        'bajoencalorias',
        'pescetariano',
        'abierto',
        'cerrado',
    );
    return $clients_roles;
}

function alimentacion_roles() {
    $clients_roles = array(
        'abierto',
        'cerrado',
    );
    return $clients_roles;
}

function in_array_any($aguja, $pajar) {
    return (bool)array_intersect($aguja, $pajar);
}

function ejercicios_roles() {
    $clients_roles = array(

        'facil',
        'iniciacion',
        'avanzado',
        'pro',

        'casa',
        'gym',

        'hombre',
        'mujer'

    );
    return $clients_roles;
}

function has_client_role ($user_roles) {
    $has_client_role = false;
    $clients_roles = ejercicios_roles();
        
    foreach($user_roles as $key => $client_role) {
        if($has_client_role === false) {
            if(in_array($client_role, $clients_roles)) {
                $has_client_role = true;

            }
        }
    }
    return $has_client_role;

}

function eliminar_acentos($cadena){
		
    //Reemplazamos la A y a
    $cadena = str_replace(
    array('Á', 'À', 'Â', 'Ä', 'á', 'à', 'ä', 'â', 'ª'),
    array('A', 'A', 'A', 'A', 'a', 'a', 'a', 'a', 'a'),
    $cadena
    );

    //Reemplazamos la E y e
    $cadena = str_replace(
    array('É', 'È', 'Ê', 'Ë', 'é', 'è', 'ë', 'ê'),
    array('E', 'E', 'E', 'E', 'e', 'e', 'e', 'e'),
    $cadena );

    //Reemplazamos la I y i
    $cadena = str_replace(
    array('Í', 'Ì', 'Ï', 'Î', 'í', 'ì', 'ï', 'î'),
    array('I', 'I', 'I', 'I', 'i', 'i', 'i', 'i'),
    $cadena );

    //Reemplazamos la O y o
    $cadena = str_replace(
    array('Ó', 'Ò', 'Ö', 'Ô', 'ó', 'ò', 'ö', 'ô'),
    array('O', 'O', 'O', 'O', 'o', 'o', 'o', 'o'),
    $cadena );

    //Reemplazamos la U y u
    $cadena = str_replace(
    array('Ú', 'Ù', 'Û', 'Ü', 'ú', 'ù', 'ü', 'û'),
    array('U', 'U', 'U', 'U', 'u', 'u', 'u', 'u'),
    $cadena );

    //Reemplazamos la N, n, C y c
    $cadena = str_replace(
    array('Ñ', 'ñ', 'Ç', 'ç'),
    array('N', 'n', 'C', 'c'),
    $cadena
    );
    
    return $cadena;
}

function trazas( $note ) {
    $now = date('d-M-Y-H:i');
    $today = date('Ymd-i');

    $uploads_path = wp_upload_dir();
    
    $folder = $uploads_path['basedir'] . '/registros/';
    $filename =  'cambio-rol-' . $today . '.txt';
    
    $data = $now . ' | ' . $note . PHP_EOL;
    if(!is_dir($folder)) mkdir($folder, 0755, true);
    
    file_put_contents($folder . $filename, $data,  FILE_APPEND );
}



// DESCARTADA
// PARA AGREGAR ROL suscripcin_4fitplan A USUARIOS CON ROLES DE EJERCIO
/*
add_action( 'rest_api_init', function () {
    register_rest_route( 'update/v1', '/users-rol/', array(
      'methods' => 'GET',
      'callback' => 'set_users_rol',
    ) );
  } );

  function set_users_rol( ) {

    $args = array(
        'role__in' => ejercicios_roles(),
        'fields'    => 'ID'
    );
    $users = get_users( $args );
   
    if ( !empty( $users) ) {
        foreach($users as $key => $user_id) {
            $user = get_user_by('ID', $user_id );
            $roles = get_userdata($user_id);
            if(!in_array('suscripcin_4fitplan', $roles->roles)) {

                $user->add_role('suscripcin_4fitplan');
                echo '<pre>' . var_export($users, true) . ' con nuevo rol </pre>';
            }
        }
      
    } else {
        echo 'no hay usuarios';
    }
    
  }
  */





function fourfit_get_entries_list ($form_id, $user_id = false ) {
    $entries_array = array();
    $user_id = ($user_id ) ? $user_id : get_current_user_id();

    $entries = FrmEntry::getAll(array('it.form_id' => $form_id, 'it.user_id' => $user_id ), ' ORDER BY it.created_at DESC', 80);
    $entry = array();
    // get all entries array
    foreach($entries as $key_sup => $value ) {
        $entry = FrmProEntriesController::show_entry_shortcode( 
            array( 
                'id'                => $key_sup, 
                'format'            => 'array', 
                'include_blank'     => 1, 
                'exclude_fields'    => '198,152,148,150,146,147,153,154,156,157,463,462'
            ) );
        //$entries_array['data']              = $entry;
        foreach ($entry as $key => $value ) {
            $field_id                   = FrmField::get_id_by_key($key);
            if( NULL !== FrmField::getOne( $field_id )) {
                $entries_array[$key_sup]['table'][$field_id]   = array( 
                    'value' => $value,
                    'value' => $value,
                    'field_key' => FrmField::getOne( $field_id )->field_key,
                    'field_key_id' => FrmField::getOne( $field_id )->id,
                    'field_key_name' => FrmField::getOne( $field_id )->name
                );
            } else {
                $entries_array[$key_sup]['data'][$key] = $value;
            }
                
        }
    }
    return $entries_array;

}




function forfit_rating ( $value) {
    $value = intval($value);
    $stars = '';
   switch($value ) {
       case 0:
        $stars = '&star; &star; &star; &star; &star;';
        break;
       case 1:
        $stars = '&starf; &star; &star; &star; &star;';
        break;
       case 2:
        $stars = '&starf; &starf; &star; &star; &star;';
        break;
       case 3:
        $stars = '&starf; &starf; &starf; &star; &star;';
        break;
       case 4:
        $stars = '&starf; &starf; &starf; &starf; &star;';
        break;
       case 5:
        $stars = '&starf; &starf; &starf; &starf; &starf;';
        break;

   }
   return $stars;
}

// ESTABLECER ÓRDENES MÁS CON MEJOR COMPORTAMIENTO MYSQUL
function set_post_order_in_admin( $wp_query ) {
    if(is_admin()  ) {
        global $pagenow;
        if ( 'edit.php' == $pagenow && !isset($_GET['orderby'])) {
            $wp_query->set( 'orderby', 'date' );
            $wp_query->set( 'order', 'DESC' );       
        }
    }
}

add_filter('pre_get_posts', 'set_post_order_in_admin', 5 );

// PRECARGA DE FUENTES PARA MEJORAR LA VISUALIZACIÓN 
add_action('wp_head' , function(){
    $uploads_folder = wp_upload_dir();
    $uploads_url    = $uploads_folder['baseurl'];
    echo '<link rel="preload" href="' . $uploads_url . '/2021/08/gello-regular.woff" as="font" type="font/woff" crossorigin="anonymous">';
    echo '<link rel="preload" href="' . $uploads_url . '/2021/08/Broken-Dark.woff" as="font" type="font/woff" crossorigin="anonymous">';
});

function remove_errors_acctions() {
    remove_action('wp_footer', 'pms_in_dc_add_frontend_scripts');
}

// Asegúrate de que esta función se ejecute después de que el plugin de terceros haya añadido la acción
add_action('init', 'remove_errors_acctions');

function get_role_name_from_slug( $role_slug ) {
    if ( ! function_exists( 'get_editable_roles' ) ) {
        require_once ABSPATH . 'wp-admin/includes/user.php';
    }

    $roles = get_editable_roles();

    if ( isset( $roles[ $role_slug ] ) ) {
        return $roles[ $role_slug ]['name'];
    }

    return false;
}
