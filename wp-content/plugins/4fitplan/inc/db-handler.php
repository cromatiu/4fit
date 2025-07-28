<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function crear_tabla_planes_alimentacion() {
    global $wpdb;
    $tabla = $wpdb->prefix . 'planes_alimentacion';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $tabla (
        id INT AUTO_INCREMENT PRIMARY KEY,
        cliente_peso INT NOT NULL,
        cliente_altura INT NOT NULL,
        objetivo VARCHAR(100) NOT NULL,
        preferencias VARCHAR(100) NOT NULL,
        restricciones_list TEXT NOT NULL,
        comidas_diarias INT NOT NULL,
        dia INT NOT NULL,
        plan_json LONGTEXT NOT NULL,
        fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_plan (cliente_peso, cliente_altura, objetivo, preferencias, comidas_diarias, dia)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}

function obtener_plan_alimentacion_de_bd($peso, $altura,  $objetivo, $preferencias, $restricciones, $comidasDiarias, $dia) {
    global $wpdb;
    $tabla = $wpdb->prefix . 'planes_alimentacion';

    $resultado = $wpdb->get_var($wpdb->prepare(
        "SELECT plan_json FROM $tabla 
        WHERE cliente_peso = %f AND cliente_altura = %f
        AND objetivo = %s AND preferencias = %s 
        AND restricciones_list = %s AND comidas_diarias = %d 
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
            'cliente_peso' => $peso,
            'cliente_altura' => $altura,
            'objetivo' => $objetivo,
            'preferencias' => $preferencias,
            'restricciones_list' => $restricciones,
            'comidas_diarias' => $comidasDiarias,
            'dia' => $dia,
            'plan_json' => json_encode($plan_json, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
    ]);
}
/**
 * Crea la tabla `recetas` al activar el plugin.
 */
function crear_tabla_recetas() {
    global $wpdb;
    $tabla           = $wpdb->prefix . 'recetas';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS {$tabla} (
        id              mediumint(9) NOT NULL AUTO_INCREMENT,
        objetivo        varchar(100)  NOT NULL,
        preferencias    varchar(100)  NOT NULL,
        restricciones_list   varchar(100)    NOT NULL,
        receta_json     longtext      NOT NULL,
        tipo_comida     varchar(50)   NOT NULL,
        nombre          varchar(100)  NOT NULL,
        slug            varchar(100)  NOT NULL,
        fecha_creacion  timestamp     NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY     (id),
        UNIQUE KEY      unique_entry (objetivo, preferencias, restricciones_list, tipo_comida, slug)
    ) {$charset_collate};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
}
/**
 * Guarda una receta generada en la base de datos.
 *
 * @param string $objetivo      Objetivo (e.g. "Pérdida de peso").
 * @param string $preferencias  Preferencias alimentarias.
 * @param string $restricciones Restricciones (coma-separadas).
 * @param string $tipo_comida   Tipo de comida (e.g. "desayuno").
 * @param string $nombre        Nombre de la receta.
 * @param string $slug          Slug único para la receta.
 * @param array  $receta_json   Array con los datos de la receta.
 * @return int|false            ID insertado o false en caso de fallo.
 */
function guardar_receta_en_bd( $objetivo, $preferencias, $restricciones, $tipo_comida, $nombre, $slug, array $receta_json ) {
    global $wpdb;
    $tabla = $wpdb->prefix . 'recetas';
    // COMPRUBEBO SI EXISTE UNA RECETA CON EL MISMO SLUG
    if( receta_existe_en_bd( $objetivo, $preferencias, $restricciones, $slug ) ) {
        return false;
    }
    $data = [
        'objetivo'       => sanitize_text_field( $objetivo ),
        'preferencias'   => sanitize_text_field( $preferencias ),
        'restricciones_list'  => sanitize_text_field( $restricciones ),
        'receta_json'    => wp_json_encode( $receta_json, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ),
        'tipo_comida'    => sanitize_text_field( $tipo_comida ),
        'nombre'         => sanitize_text_field( $nombre ),
        'slug'           => sanitize_title( $slug ),
    ];

    $formats = [
        '%s', // objetivo
        '%s', // preferencias
        '%s', // restricciones
        '%s', // receta_json
        '%s', // tipo_comida
        '%s', // nombre
        '%s', // slug
    ];

    $inserted = $wpdb->insert( $tabla, $data, $formats );

    return $inserted ? $wpdb->insert_id : false;
}
/**
 * Comprueba si ya existe una receta con el mismo slug en el mismo contexto.
 *
 * @param string $objetivo
 * @param string $preferencias
 * @param string $restricciones
 * @param string $slug
 * @return bool
 */
function receta_existe_en_bd( string $objetivo, string $preferencias, string $restricciones, string $slug ): bool {
    global $wpdb;
    $tabla = $wpdb->prefix . 'recetas';

    $count = (int) $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(1)
             FROM {$tabla}
             WHERE objetivo = %s
               AND preferencias = %s
               AND restricciones_list = %s
               AND slug = %s",
            $objetivo,
            $preferencias,
            $restricciones,
            sanitize_title( $slug )
        )
    );

    return $count > 0;
}


/**
 * Recupera recetas de la base de datos según filtros.
 *
 * @param string      $objetivo      Objetivo nutricional (exacto).
 * @param string      $preferencias  Preferencias alimentarias (exacto).
 * @param string      $restricciones Restricciones (coma-separadas, busca substring).
 * @param string|null $tipo_comida   (Opcional) Tipo de comida, e.g. 'desayuno'.
 * @return array                   Array de objetos con las columnas de la tabla.
 */
function obtener_recetas( string $objetivo, string $preferencias, string $restricciones, string $tipo_comida = null ): array {
    global $wpdb;
    $tabla = $wpdb->prefix . 'recetas';

    // Construir las condiciones dinámicamente
    $where   = [];
    $formats = [];

    // Objetivo exacto
    $where[]   = "objetivo = %s";
    $formats[] = $objetivo;

    // Preferencias exacto
    $where[]   = "preferencias = %s";
    $formats[] = $preferencias;

    // Restricciones (busca como substring)
    $where[]   = "restricciones_list LIKE %s";
    $formats[] = '%' . $wpdb->esc_like( $restricciones ) . '%';

    // Tipo de comida (opcional)
    if ( $tipo_comida !== null && $tipo_comida !== '' ) {
        $where[]   = "tipo_comida = %s";
        $formats[] = $tipo_comida;
    }

    // Montar la cláusula WHERE
    $where_sql = implode( ' AND ', $where );

    // Preparar y ejecutar la consulta
    $sql = $wpdb->prepare(
        "SELECT id, objetivo, preferencias, restricciones_list, tipo_comida, nombre, slug, receta_json, fecha_creacion
         FROM {$tabla}
         WHERE {$where_sql}
         ORDER BY fecha_creacion DESC",
        $formats
    );

    $results = $wpdb->get_results( $sql, ARRAY_A );
    return $results;
}



/**
 * Crea la tabla de planes de ejercicio al activar el plugin.
 */
function fe_create_exercise_plans_table() {
    global $wpdb;
    $table_name      = $wpdb->prefix . 'planes_ejercicio';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE {$table_name} (
      id            mediumint(9) NOT NULL AUTO_INCREMENT,
      objetivo      varchar(50)   NOT NULL,
      lugar         varchar(50)   NOT NULL,
      dias_entreno_semana   tinyint(2)    NOT NULL,
      tiempo        varchar(10)   NOT NULL,
      sexo          varchar(10)   NOT NULL,
      nivel         varchar(10)   NOT NULL,
      plan_json     longtext      NOT NULL,
      UNIQUE KEY unique_plan (objetivo, lugar, dias_entreno_semana, tiempo, sexo, nivel),
      PRIMARY KEY  (id)
    ) {$charset_collate};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
}



/**
 * Recupera un plan de ejercicio de la base de datos si ya existe.
 *
 * @param string $objetivo
 * @param string $lugar
 * @param int    $dias_semana
 * @param string $tiempo
 * @return string|null JSON serializado o null si no existe.
 */
function fe_get_exercise_plan_from_db( $objetivo, $lugar, $dias_semana, $tiempo, $sexo, $nivel ) {
    global $wpdb;
    $table = $wpdb->prefix . 'planes_ejercicio';

    $sql = $wpdb->prepare(
        "SELECT plan_json
         FROM {$table}
         WHERE objetivo    = %s
           AND lugar       = %s
           AND dias_entreno_semana = %d
           AND tiempo      = %s
           AND sexo        = %s
           AND nivel       = %s
         LIMIT 1",
        $objetivo, $lugar, $dias_semana, $tiempo, $sexo, $nivel
    );
    return $wpdb->get_var( $sql ) ?: null;
}


/**
 * Guarda un plan de ejercicio en la base de datos.
 *
 * @param string $objetivo
 * @param string $lugar
 * @param int    $dias_semana
 * @param string $tiempo
 * @param array  $plan      Array decodificado del plan.
 * @return int|false        ID insertado o false en fallo.
 */
function fe_save_exercise_plan_to_db( $objetivo, $lugar, $dias_semana, $tiempo, $sexo, $nivel, array $plan ) {
    global $wpdb;
    $table = $wpdb->prefix . 'planes_ejercicio';

    $data = [
        'objetivo'     => $objetivo,
        'lugar'        => $lugar,
        'dias_entreno_semana'  => $dias_semana,
        'tiempo'       => $tiempo,
        'sexo'         => $sexo,
        'nivel'        => $nivel,
        'plan_json'    => wp_json_encode( $plan, JSON_UNESCAPED_UNICODE ),
    ];
    $formats = [ 
        '%s', 
        '%s', 
        '%d', 
        '%s', 
        '%s', 
        '%s', 
        '%s', 
        '%s' ];

    $inserted = $wpdb->insert( $table, $data, $formats );
    return $inserted ? $wpdb->insert_id : false;
}

/**
 * Crea la tabla de perfiles de usuario para ejercicio
 */
function fe_create_motivational_messages_table() {
    global $wpdb;
    $table_name      = $wpdb->prefix . 'motivational_messages';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE {$table_name} (
      id             mediumint(9) NOT NULL AUTO_INCREMENT,
      user_id        bigint(20)   NOT NULL,
      nombre         varchar(100) NOT NULL,
      edad           tinyint(3)   NOT NULL,
      sexo           varchar(50)  NOT NULL,
      objetivo       varchar(50)  NOT NULL,
      lugar          varchar(50)  NOT NULL,
      api_response   varchar(1000) NOT NULL,
      PRIMARY KEY    (id),
      KEY user_idx   (user_id)
    ) {$charset_collate};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
}

/**
 * Guarda un perfil de ejercicio de usuario en la base de datos.
 *
 * @param int    $user_id      ID del usuario de WordPress.
 * @param string $nombre       Nombre para este perfil.
 * @param int    $edad         Edad del usuario.
 * @param string $objetivo     Objetivo de entrenamiento.
 * @param string $lugar        Lugar de entrenamiento.
 * @param string $api_response Texto (hasta 1000 chars) con la respuesta de la API.
 * @return int|false           Insert ID o false en fallo.
 */
function save_motivational_messages( $user_id, $nombre, $edad, $sexo, $objetivo, $lugar, $api_response ) {
    global $wpdb;

    $tabla = $wpdb->prefix . 'motivational_messages';

    // Prepara los datos
    $data = [
        'user_id'      => intval( $user_id ),
        'nombre'       => sanitize_text_field( $nombre ),
        'edad'         => intval( $edad ),
        'objetivo'     => sanitize_text_field( $objetivo ),
        'lugar'        => sanitize_text_field( $lugar ),
        'api_response' => sanitize_textarea_field( $api_response ),
    ];

    // Formatos de cada campo para seguridad de tipos
    $formats = [
        '%d',  // user_id
        '%s',  // nombre
        '%d',  // edad
        '%s',  // objetivo
        '%s',  // lugar
        '%s',  // api_response
    ];

    $inserted = $wpdb->insert( $tabla, $data, $formats );

    return $inserted ? $wpdb->insert_id : false;
}

function obtener_metricas_usuario_formidable($user_id, $field_ids) {
    global $wpdb;
    $user_id = 21247;
    // Validación básica
    if (empty($user_id) || empty($field_ids)) {
        return [];
    }
    // 1. Obtener un array con todas las claves
    $keys = array_keys($field_ids); 
    $campos = implode(',', $keys);
    // Construimos la consulta SQL
    $query = "
        SELECT meta_value, created_at, field_id
        FROM {$wpdb->prefix}frm_item_metas 

        WHERE field_id IN ({$campos})
        AND item_id IN (
            SELECT item_id 
            FROM {$wpdb->prefix}frm_item_metas 
            WHERE field_id = 152 
            AND meta_value = %d
        )
        ORDER BY created_at DESC
    ";

    // Ejecutar consulta
    $resultados = $wpdb->get_results( $wpdb->prepare($query, $user_id) );

    return $resultados;
}
function get_number_of_revisions($user_id) {
     global $wpdb;

    // Validación básica
    if (empty($user_id)) {
        return 0;
    }

    // Construimos la consulta SQL
    $query = "
        SELECT id 
        FROM {$wpdb->prefix}frm_items 
        WHERE user_id = 21247
        AND form_id = 7
    ";

    // Ejecutar consulta
    $resultados = $wpdb->get_results( $wpdb->prepare($query, $user_id) );

    return count($resultados);
}

function get_user_metrics($user_id) {

    $field_ids = form_fields('medidas');
    $raw = obtener_metricas_usuario_formidable($user_id, $field_ids); 
    if (empty($raw)) {
        return array();
      }
      $field_ids = form_fields('medidas');
      // 3) Agrupar por fecha
      $agrupado = [];
      foreach ($raw as $key => $row) {
        $fecha = $row->created_at;
        if (!isset($agrupado[$fecha])) {
          $agrupado[$fecha] = ['fecha' => $fecha];
        }
        if (isset($field_ids[$row->field_id])) {
          $clave = $field_ids[$row->field_id];
          $agrupado[$fecha][$clave] = floatval($row->meta_value);
        }
      }
  
      // 4) Convertir a array indexado y ordenar cronológicamente
      $entries = array_values($agrupado);
      usort($entries, function($a, $b){
        return strtotime($a['fecha']) <=> strtotime($b['fecha']);
      });
  
      // 5) Preparar etiquetas y datasets para Chart.js
      $labels = [];
      // inicializa con llave para cada métrica
      $datasets_map = array_fill_keys(array_values($field_ids), []);
      
      foreach ($entries as $e) {
        // formatea la fecha como quieras (por ej. "14 Mar 2025")
        $labels[] = date_i18n('d.m.y', strtotime($e['fecha']));
        
        foreach ($field_ids as $id => $nombre) {
          // si no llegó valor para esa métrica, pon null
          $datasets_map[$nombre][] = $e[$nombre] ?? null;
        }
      }
  
      // convierte a la estructura que espera Chart.js
      $datasets = [];
      $i = 0;
      foreach ($datasets_map as $nombre => $valores) {
        $datasets[] = [
          'label'       => ucfirst($nombre),
          'data'        => $valores,
          'fill'        => false,
          'tension'     => 0.2,
          'borderWidth' => 2,
          'id'    => "metricasChart{$i}",
        ];
        $i++;
      }
      
      // 6) Envía JSON
      $chart = [
        'labels'   => $labels,
        'datasets' => $datasets,
      ];
      return $chart;
} 

function get_user_images($user_id) {
        $field_ids = form_fields('imagenes');
        $raw = obtener_metricas_usuario_formidable($user_id, $field_ids); 
        // Agrupar las metas por fecha
        $agrupado = [];
        foreach ( $raw as $row ) {
            $dt = $row->created_at;
            if ( ! isset( $agrupado[ $dt ] ) ) {
                $agrupado[ $dt ] = [ 'created_at' => $dt ];
            }
            if ( isset( $field_ids[ $row->field_id ] ) ) {
                // meta_value es attachment ID
                $att_id = absint( $row->meta_value );
                $url    = wp_get_attachment_url( $att_id );
                $agrupado[ $dt ][ $field_ids[ $row->field_id ] ] = esc_url( $url );
            }
        }
    
        // Convertir a array indexado y ordenar cronológicamente
        $entries = array_values( $agrupado );
        usort( $entries, function( $a, $b ) {
            return strtotime( $a['created_at'] ) <=> strtotime( $b['created_at'] );
        } );
    
        // Preparar array de slides con fecha y URLs
        $slides = [];
        foreach ( $entries as $e ) {
            $fecha  = new DateTime( $e['created_at'] );
            $fmt    = new IntlDateFormatter(
                'es_ES', 
                IntlDateFormatter::NONE, 
                IntlDateFormatter::NONE,
                $fecha->getTimezone()->getName(), // o 'Europe/Madrid'
                IntlDateFormatter::GREGORIAN,
                'd MMM yyyy'  // d = día, MMM = abreviatura mes, yyyy = año
            );
    
            $fecha_format = $fmt->format( $fecha );
            $slides[] = [
                'fecha'   => $fecha_format,
                'frente'  => $e['frente']  ?? '',
                'perfil'  => $e['perfil']  ?? '',
                'espalda' => $e['espalda'] ?? '',
            ];
        }
        return $slides;
}

// 1) Create custom table on init
function fm_create_metrics_table() {
    global $wpdb;
    $table = $wpdb->prefix . 'user_daily_metrics';
    $charset_collate = $wpdb->get_charset_collate();

    if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) !== $table ) {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            met_date DATE NOT NULL,
            steps INT UNSIGNED DEFAULT 0,
            water_ml INT UNSIGNED DEFAULT 0,
            training_time INT UNSIGNED DEFAULT 0,
            diet_rating TINYINT UNSIGNED DEFAULT 0,
            PRIMARY KEY  (id),
            UNIQUE KEY user_date (user_id, met_date)
        ) {$charset_collate};";
        dbDelta( $sql );
    }
}

// 2) Helper: check if metrics exist for a date
function fm_has_metrics_for_date( $user_id ) {
    $date = date('Y-m-d', strtotime('-1 day'));
    global $wpdb;
    $table = $wpdb->prefix . 'user_daily_metrics';
    return (bool) $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM {$table} WHERE user_id = %d AND met_date = %s",
        $user_id, $date
    ) );
}

// 3) Helper: fetch user history
function fm_get_metrics_history( $user_id ) {
    global $wpdb;
    $table = $wpdb->prefix . 'user_daily_metrics';
    return $wpdb->get_results( $wpdb->prepare(
        "SELECT met_date, steps, water_ml, training_time, diet_rating
         FROM {$table}
         WHERE user_id = %d
         ORDER BY met_date ASC",
        $user_id
    ), ARRAY_A );
}