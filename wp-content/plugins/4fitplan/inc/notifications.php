<?php function get_user_notifications( $user_id ) {
    $notifications = [];

    // --- Ejemplo 1: No ha registrado métricas de ayer ---
    if ( ! fm_has_metrics_for_date( $user_id ) ) {
        $notifications[] = [
            'message' => 'Recuerda registrar tu seguimiento de ayer. Haz clic aquí para hacerlo.',
            'link'    => get_permalink( 124737 ),
            'type'    => 'warning',
        ];
    }

    // Aquí puedes añadir más notificaciones en el futuro, por ejemplo:
    /*
    if ( some_other_condition( $user_id ) ) {
        $notifications[] = [
            'message' => 'Tienes una nueva clase disponible.',
            'link'    => get_permalink( 999 ),
            'type'    => 'info',
        ];
    }
    */

    return $notifications;
}
