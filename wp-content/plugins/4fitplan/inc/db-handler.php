<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function crear_tabla_planes_alimentacion() {
    global $wpdb;
    $tabla = $wpdb->prefix . 'planes_alimentacion';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $tabla (
        id INT AUTO_INCREMENT PRIMARY KEY,
        peso INT NOT NULL,
        altura INT NOT NULL,
        objetivo VARCHAR(100) NOT NULL,
        preferencias VARCHAR(100) NOT NULL,
        restricciones TEXT NOT NULL,
        comidas_diarias INT NOT NULL,
        dia INT NOT NULL,
        plan_json LONGTEXT NOT NULL,
        fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_plan (peso, altura, objetivo, preferencias, comidas_diarias, dia)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}

function obtener_plan_alimentacion_de_bd($peso, $altura,  $objetivo, $preferencias, $restricciones, $comidasDiarias, $dia) {
    global $wpdb;
    $tabla = $wpdb->prefix . 'planes_alimentacion';

    $resultado = $wpdb->get_var($wpdb->prepare(
        "SELECT plan_json FROM $tabla 
        WHERE peso = %f AND altura = %f
        AND objetivo = %s AND preferencias = %s 
        AND restricciones = %s AND comidas_diarias = %d 
        AND dia = %d 
        LIMIT 1",
        $peso, $altura,  $objetivo, $preferencias, $restricciones, $comidasDiarias, $dia
    ));

    return $resultado ? json_decode($resultado, true) : null;
}

function guardar_plan_alimentacion_en_bd($peso, $altura, $objetivo, $preferencias, $restricciones, $comidasDiarias, $dia, $plan_json) {
    global $wpdb;
    $tabla = $wpdb->prefix . 'planes_alimentacion';

    $wpdb->insert($tabla, [
            'peso' => $peso,
            'altura' => $altura,
            'objetivo' => $objetivo,
            'preferencias' => $preferencias,
            'restricciones' => $restricciones,
            'comidas_diarias' => $comidasDiarias,
            'dia' => $dia,
            'plan_json' => json_encode($plan_json, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
    ]);
}