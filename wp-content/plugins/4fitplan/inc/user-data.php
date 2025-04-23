<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Muestra un listado con los datos del cliente necesarios para generar el plan.
 * Las restricciones se obtienen de los roles: 'sinlactosa', 'celiaco', 'sinpescado'.
 *
 * @param WP_User|null $user El objeto WP_User. Si es null, carga el usuario actual.
 * @return string HTML con los datos en un <dl>.
 */
function display_nutrition_data( $user = null ) {
    if ( ! $user ) {
        $user = wp_get_current_user();
    }
    if ( ! $user || ! $user->ID ) {
        return '<p>No hay usuario logueado.</p>';
    }
    $user_id = $user->ID;

    // 1) Metadatos
    $peso            = get_user_meta( $user_id, 'cliente_peso', true );
    $altura          = get_user_meta( $user_id, 'cliente_altura', true );
    $comidas_diarias = get_user_meta( $user_id, 'comidas_diarias', true );

    // 2) Roles → objetivo, preferencias, restricciones
    $objetivo     = '';
    $preferencias = '';
    $restricciones = [];
    
    $opciones_objetivo      = role_options('objetivo');
    $opciones_preferencias  = role_options('preferencias');
    $opciones_restricciones = role_options('restricciones');

    foreach ( $user->roles as $rol ) {
        if ( array_key_exists( $rol, $opciones_objetivo ) ) {
            $objetivo = get_role_name_from_slug( $rol );
        }
        if ( array_key_exists( $rol,  $opciones_preferencias ) ) {
            $preferencias = get_role_name_from_slug( $rol );
        }
        if ( array_key_exists( $rol,  $opciones_restricciones ) ) {
            $restricciones[] = get_role_name_from_slug( $rol );
        }
    }
    // implode para mostrar como cadena separada por comas
    $restricciones_list = ! empty( $restricciones ) 
        ? implode( ', ', $restricciones ) 
        : '';

   // Rutas de iconos
   $plugin_url = plugin_dir_url( dirname(__FILE__) ) . 'assets/images/svg/';
   $icons = [
       'peso'           => 'peso.svg',
       'altura'         => 'altura.svg',
       'comidas'        => 'comidas.svg',
       'objetivo'       => 'objetivo.svg',
       'preferencias'   => 'preferencias.svg',
       'restricciones'  => 'restricciones.svg',
   ];

   ob_start();
   ?>
   <ul class="client-nutrition-data">
       <?php if ( $peso !== '' ): ?>
           <li>
                <img src="<?php echo esc_url( $plugin_url . $icons['peso'] ); ?>" 
                  alt="Icono peso" width="24" height="24">
                <strong>Peso</strong>
                <span><?php echo esc_html( $peso ); ?> kg</span>
            </li>
       <?php endif; ?>

       <?php if ( $altura !== '' ): ?>
           <li>
                <img src="<?php echo esc_url( $plugin_url . $icons['altura'] ); ?>" 
                  alt="Icono altura" width="24" height="24">
                <strong>Altura</strong>
                <span><?php echo esc_html( $altura ); ?> cm</span>
           </li>
       <?php endif; ?>

       <?php if ( $comidas_diarias !== '' ): ?>
           <li>
                <img src="<?php echo esc_url( $plugin_url . $icons['comidas'] ); ?>" 
                  alt="Icono comidas diarias" width="24" height="24">
                <strong>Comidas diarias</strong>
                <span><?php echo esc_html( $comidas_diarias ); ?></span>
           </li>
       <?php endif; ?>

       <?php if ( $objetivo !== '' ): ?>
           <li>
                <img src="<?php echo esc_url( $plugin_url . $icons['objetivo'] ); ?>" 
                  alt="Icono objetivo" width="24" height="24">
                <strong>Objetivo</strong>
                <span><?php echo esc_html( $objetivo ); ?></span>
            </li>
       <?php endif; ?>

       <?php if ( $preferencias !== '' ): ?>
           <li>
                <img src="<?php echo esc_url( $plugin_url . $icons['preferencias'] ); ?>" 
                  alt="Icono preferencias" width="24" height="24">
                <strong>Preferencias</strong>
                <span><?php echo esc_html( $preferencias ); ?></span>
            </li>
       <?php endif; ?>

       <?php if ( $restricciones_list !== '' ): ?>
           <li>
                <img src="<?php echo esc_url( $plugin_url . $icons['restricciones'] ); ?>" 
                  alt="Icono restricciones" width="24" height="24">
                <strong>Restricciones</strong>
                <span><?php echo esc_html( $restricciones_list ); ?></span>
            </li>
       <?php endif; ?>
   </ul>
   <?php
   return ob_get_clean();
}



