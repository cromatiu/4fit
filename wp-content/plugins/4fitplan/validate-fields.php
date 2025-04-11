<?php 
add_filter('frm_validate_field_entry', 'ffp_validate_forms', 10, 3);
function ffp_validate_forms($errors, $field, $value){ 
    // BLOQUEO CLIENTES #222
    // TIEMPO CLIENTES #229
    // ALTA DISTRIBUIDOR (YA CLIENTE) #259
    // SOLICITUD DE CAMBIO DE DATOS CLIENTE 263 (email a cambiar)
    // CAMBIO PLAN CLIENTES #264
    if ( $field->id == 222 || $field->id == 259  || $field->id == 229 || $field->id == 263 || $field->id == 264  || $field->id == 398 ) {  
        $user = get_user_by( 'email',  $value );
        if($user == false) {
            $errors['field' . $field->id] = 'No existe ningún usuario con el email ' . var_export($value , true) . '.';
        }
    }

    // ALTA CLIENTE #8
    // SOLICITUD DE CAMBIO DE DATOS CLIENTE 242 (email cambiado)
    // ALTA DISTRIBUIDOR #249 
    if ( $field->id == 249 ) {  
        $user = get_user_by( 'email',  $value );
        if($user) {
            $errors['field' . $field->id] = 'Ya existe un usuario creado con  el email ' . var_export($value , true) . '.';
        }
    }

    // VERIFICACIÓN PARA QUE LOS DISTRIBUIDORES SOLO PUEDAN ELIMINAR A SUS PROPIOS CLIENTES
    
    if ( $field->id == 222 || $field->id == 264 || $field->id == 263 || $field->id == 398  ) {
        $user_dist = wp_get_current_user();
        if(!in_array('administrator', $user_dist->roles)) {
            $user               = get_user_by( 'email',  $value );
            $id_distribuidor    = get_user_meta($user->ID, 'id_distribuidor', true);
            
            if($id_distribuidor != strval($user_dist->ID)) {
                $errors['field' . $field->id] = 'No puedes modificar a ' . var_export($value , true) . ' porque no parece ser tu cliente.';
            }
        }
    }
    return $errors;
}