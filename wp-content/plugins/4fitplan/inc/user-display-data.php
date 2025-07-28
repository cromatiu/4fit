<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Muestra un listado con los datos del cliente necesarios para generar el plan.
 * Las restricciones se obtienen de los roles: 'sinlactosa', 'celiaco', 'sinpescado'.
 *
 * @param WP_User|null $user El objeto WP_User. Si es null, carga el usuario actual.
 * @return string HTML con los datos en un <dl>.
 */
function display_nutrition_data( $user) {
    $fields = uf_get_user_fields( $user,  ['nutrition'], true);

    if(!isset($fields['objetivo']) && !isset($fields['cliente_peso']) && !isset($fields['cliente_altura']) && !isset($fields['comidas_diarias']) && !isset($fields['restricciones_list']) && !isset($fields['nivel'])) {
        return '';
    }
    $peso                   =  $fields['cliente_peso'];
    $altura                 =  $fields['cliente_altura'];
    $objetivo               =  get_role_name($fields['objetivo']);
    $comidas_diarias        =  get_role_name($fields['comidas_diarias']);
    $preferencias           =  get_role_name($fields['preferencias']);

    $restricciones_array    =  explode(', ', $fields['restricciones_list'] ); 
    $restricciones_names = [];
    foreach($restricciones_array as $estricion_slug) {
        $restricciones_names[] = get_role_name($estricion_slug);
    }
    $restricciones_list = (!empty($restricciones_names)) ? implode(', ', $restricciones_names) : '';
   // Rutas de iconos
   $plugin_url = plugin_dir_url( dirname(__FILE__) ) . 'assets/images/svg/';
   $icons = [
       'cliente_peso'           => 'cliente_peso.svg',
       'cliente_altura'         => 'cliente_altura.svg',
       'comidas'        => 'comidas.svg',
       'objetivo'       => 'objetivo.svg',
       'preferencias'   => 'preferencias.svg',
       'restricciones_list'  => 'restricciones_list.svg',
   ];

   ob_start();
   ?>
   <ul class="client-nutrition-data">
       <?php if ( $peso !== '' ): ?>
           <li>
                <img src="<?php echo esc_url( $plugin_url . $icons['cliente_peso'] ); ?>" 
                  alt="Icono peso" width="24" height="24">
                <strong>Peso</strong>
                <span><?php echo esc_html( $peso ); ?> kg</span>
            </li>
       <?php endif; ?>

       <?php if ( $altura !== '' ): ?>
           <li>
                <img src="<?php echo esc_url( $plugin_url . $icons['cliente_altura'] ); ?>" 
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
                <img src="<?php echo esc_url( $plugin_url . $icons['restricciones_list'] ); ?>" 
                  alt="Icono restricciones" width="24" height="24">
                <strong>Restricciones</strong>
                <span><?php echo esc_html( $restricciones_list ); ?></span>
            </li>
       <?php endif; ?>
   </ul>
   <?php
   return ob_get_clean();
}
/**
 * Muestra un listado con los datos del cliente necesarios para generar el plan.
 * Las restricciones se obtienen de los roles: 'sinlactosa', 'celiaco', 'sinpescado'.
 *
 * @param WP_User|null $user El objeto WP_User. Si es null, carga el usuario actual.
 * @return string HTML con los datos en un <dl>.
 */
function display_recetas_data( $user) {
    $fields = uf_get_user_fields( $user,  ['nutrition'], true);

    if(!isset($fields['objetivo'])  && !isset($fields['comidas_diarias']) && !isset($fields['preferencias']) && !isset($fields['restricciones_list'])) {
        return '';
    }
      
    $objetivo               =  get_role_name($fields['objetivo']);
    $comidas_diarias        =  get_role_name($fields['comidas_diarias']);
    $preferencias           =  get_role_name($fields['preferencias']);

    $restricciones_array    =  explode(', ', $fields['restricciones_list'] ); 
    $restricciones_names = [];
    foreach($restricciones_array as $estricion_slug) {
        $restricciones_names[] = get_role_name($estricion_slug);
    }
    $restricciones_list = (!empty($restricciones_names)) ? implode(', ', $restricciones_names) : '';

   // Rutas de iconos
   $plugin_url = plugin_dir_url( dirname(__FILE__) ) . 'assets/images/svg/';
   $icons = [
       'comidas'        => 'comidas.svg',
       'objetivo'       => 'objetivo.svg',
       'preferencias'   => 'preferencias.svg',
       'restricciones_list'  => 'restricciones_list.svg',
   ];

   ob_start();
   ?>
   <ul class="client-nutrition-data">

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
                <img src="<?php echo esc_url( $plugin_url . $icons['restricciones_list'] ); ?>" 
                  alt="Icono restricciones" width="24" height="24">
                <strong>Restricciones</strong>
                <span><?php echo esc_html( $restricciones_list ); ?></span>
            </li>
       <?php endif; ?>
   </ul>
   <?php
   return ob_get_clean();
}

/**
 * Muestra un listado con los datos del cliente necesarios para generar el plan de ejercicio,
 * incluyendo un icono SVG para cada elemento.
 *
 * @param string $lugar           ‘Casa’, ‘Gimnasio’, ‘Aire libre’ o ‘Mixto’
 * @param int    $dias_semana     Número de días de entrenamiento a la semana
 * @param string $tiempo_entreno  ‘15min’, ‘30min’, ‘45min’ o ‘60min+’
 * @param string $sexo            ‘Hombre’ o ‘Mujer’
 * @param string $nivel           ‘Fácil’, ‘Avanzado’ o ‘Pro’
 * @return string HTML con los datos en un <ul> con iconos
 */
function display_exercise_data( $user ) {

    $fields = uf_get_user_fields( $user,  ['exercise'], true);

    if(!isset($fields['objetivo']) && !isset($fields['lugar']) && !isset($fields['dias_entreno_semana']) && !isset($fields['tiempo_entreno']) && !isset($fields['sexo']) && !isset($fields['nivel'])) {
        return '';
    }
    $objetivo       =  get_role_name($fields['objetivo']);
    $lugar          =  get_role_name($fields['lugar']);
    $dias_semana    =  $fields['dias_entreno_semana'];
    $tiempo_entreno =  $fields['tiempo_entreno'];
    $sexo           =  get_role_name($fields['sexo']);
    $nivel          =  get_role_name($fields['nivel']);

    // Directorio de los iconos
    $plugin_url = plugin_dir_url( dirname(__FILE__) ) . 'assets/images/svg/';
    $icons = [
        'objetivo'   => 'objetivo.svg',
        'lugar'      => 'lugar.svg',
        'dias'       => 'dias.svg',
        'tiempo'     => 'tiempo.svg',
        'sexo'       => 'sexo.svg',
        'nivel'      => 'nivel.svg',
    ];

    ob_start();
    ?>
    <ul class="client-exercise-data">
        <?php if ( $objetivo !== '' ): ?>
        <li>
            <img src="<?php echo esc_url( $plugin_url . $icons['objetivo'] ); ?>"
                 alt="Icono ubicación" width="24" height="24">
            <strong>Objetivo</strong>
            <span><?php echo esc_html( $objetivo ); ?></span>
        </li>
        <?php endif; ?>
        <?php if ( $lugar !== '' ): ?>
        <li>
            <img src="<?php echo esc_url( $plugin_url . $icons['lugar'] ); ?>"
                 alt="Icono ubicación" width="24" height="24">
            <strong>Ubicación</strong>
            <span><?php echo esc_html( $lugar ); ?></span>
        </li>
        <?php endif; ?>

        <?php if ( $dias_semana ): ?>
        <li>
            <img src="<?php echo esc_url( $plugin_url . $icons['dias'] ); ?>"
                 alt="Icono días" width="24" height="24">
            <strong>Días/semana</strong>
            <span><?php echo intval( $dias_semana ); ?></span>
        </li>
        <?php endif; ?>

        <?php if ( $tiempo_entreno !== '' ): ?>
        <li>
            <img src="<?php echo esc_url( $plugin_url . $icons['tiempo'] ); ?>"
                 alt="Icono tiempo" width="24" height="24">
            <strong>Tiempo sesión</strong>
            <span><?php echo esc_html( $tiempo_entreno ); ?></span>
        </li>
        <?php endif; ?>

        <?php if ( $sexo !== '' ): ?>
        <li>
            <img src="<?php echo esc_url( $plugin_url . $icons['sexo'] ); ?>"
                 alt="Icono sexo" width="24" height="24">
            <strong>Sexo</strong>
            <span><?php echo esc_html( $sexo ); ?></span>
        </li>
        <?php endif; ?>

        <?php if ( $nivel !== '' ): ?>
        <li>
            <img src="<?php echo esc_url( $plugin_url . $icons['nivel'] ); ?>"
                 alt="Icono nivel" width="24" height="24">
            <strong>Nivel</strong>
            <span><?php echo esc_html( $nivel ); ?></span>
        </li>
        <?php endif; ?>
    </ul>
    <?php
    return ob_get_clean();
}

function display_suscription_data( $user, bool $self = true ) {
    $user_id = $user->ID;

    $subscriptions = pms_get_member_subscriptions( array( 'user_id' => $user_id ) );

    $data_header = ($self) ? 'Mi suscripción' : 'Su suscripción';

    ob_start();
    ?>
    <div class="suscription-data">
        <h3><?php echo $data_header; ?></h3>
        <?php if ( ! empty( $subscriptions ) ) : ?>
            <?php foreach ( $subscriptions as $subscription ) : ?>
                <?php 
                    $plan = pms_get_subscription_plan( $subscription->subscription_plan_id );
                    $status_slug = $subscription->status; 
                    $statuses = pms_get_member_subscription_statuses( $status_slug );
                    $status_name = $statuses[$status_slug];
                ?>
                <ul class="user-suscription-data">
                    <li><strong>Plan:</strong> <?php echo esc_html( $plan->name ); ?></li>
                    <li><strong>Estado:</strong> <?php echo esc_html($status_name ); ?></li>
                    <li><strong>Fecha de inicio:</strong> <?php echo esc_html( date_i18n( 'd/m/Y', strtotime( $subscription->start_date ) ) ); ?></li>
                    <li><strong>Fecha de finalización:</strong> <?php echo esc_html( date_i18n( 'd/m/Y', strtotime( $subscription->expiration_date ) ) ); ?></li>
                </ul>
            <?php endforeach; ?>
        <?php else : ?>
            <p>No tienes suscripciones activas.</p>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
} 
