<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Devuelve un fragmento de JSON “vivo” (no escapado) con placeholders,
 * para que el modelo respete la cantidad exacta de comidas.
 */
function estructura_json_alimentacion(int $dia, int $comidas_diarias): string {
    
    $tipos = get_comidas_diarias_types($comidas_diarias);

    $lines = [];
    foreach ($tipos as $tipo) {
        $lines[] = sprintf(
            '    "%s": {"plato":"Nombre del plato","ingredientes":["..."],"preparacion":"..."}',
            $tipo
        );
    }
    $comidasBlock = implode(",\n", $lines);

    // Ahora inyectamos el día real
    return <<<JSON
    {
        "dia":  {$dia},
        "comidas": {
            {$comidasBlock}
        }
    }
    JSON;
}
function generar_prompt_alimentacion( $peso, $altura, $objetivo, $preferencias, $restricciones, $comidas_diarias, $dia ) {
    $comidasDiarias = intval($comidas_diarias);

    $estructura_json = estructura_json_alimentacion($dia, $comidasDiarias);

    $prompt = <<<TXT
    Eres un asistente que genera **exactamente** {$comidasDiarias} comidas diarias.

    Genera un plan mensual de alimentación **solo** para el día {$dia}, basado en:
    - Peso: {$peso} kg
    - Altura: {$altura} cm
    - Objetivo: {$objetivo}
    - Preferencias Alimentarias: {$preferencias}
    - Restricciones Alimentarias: {$restricciones}
    - Número de Comidas Diarias: **{$comidasDiarias}**

    Tu **respuesta** debe ser **únicamente** un **objeto JSON** con **exactamente** {$comidasDiarias} entradas en el campo `"comidas"`.  
    Usa **este** JSON de ejemplo **literalmente** como plantilla (incluyendo el mismo orden y nombres de clave):

    {$estructura_json}

    **Instrucciones adicionales:**
    1. Devuelve **solo** el objeto JSON, **sin** bloques de código ni backticks.  
    2. No cambies, añadas ni quites claves.  
    3. Cada día debe tener combinaciones únicas; no repitas platos ni ingredientes consecutivamente.  
    4. Incluye culturas y estilos de cocina variados.
    5. Incluye la cantidad en g y ml de cada ingrediente
    TXT;
    return $prompt;
}
function obtener_plan_alimentacion($peso, $altura, $objetivo, $preferencias, $restricciones, $comidas_diarias, $dia) {
    // 🔁 Redondear peso y altura
    $peso = redondear_a_multiplo_de_5($peso);
    $altura = redondear_a_multiplo_de_5($altura);
    // Buscar en la base de datos antes de hacer la solicitud a la API
    $planExistente = obtener_plan_alimentacion_de_bd($peso, $altura, $objetivo, $preferencias, $restricciones, $comidas_diarias, $dia);

    if ($planExistente) {
        return $planExistente; // Retornar el plan almacenado si existe
    }
    // Si no existe, generar uno nuevo con OpenAI
    $prompt = generar_prompt_alimentacion($peso, $altura, $objetivo, $preferencias, $restricciones, $comidas_diarias, $dia);

    $role_system = "Eres un nutricionista creativo que diseña menús variados, equilibrados y originales para evitar la monotonía.";

    $plan_json = consulta_api_openai($prompt, $role_system);
    
    if($plan_json) {
        guardar_plan_alimentacion_en_bd($peso, $altura, $objetivo, $preferencias, $restricciones, $comidas_diarias, $dia, json_decode($plan_json, true));
    }

    $plan_array = db_json_to_array($plan_json);
    if(isset( $plan_array['comidas'] )) {
        foreach ( $plan_array['comidas'] as $tipo_comida => $datos ) {
            // Nombre y slug
            $nombre = $datos['plato'] ?? '';
            if ( ! $nombre ) {
                continue;
            }
            $slug           = sanitize_title( $nombre );
            $tipo_comida    = sanitize_title( $tipo_comida );
            
            // La propia receta (incluye plato, ingredientes, preparación)
            // Podemos usar todo $datos como receta_json
            guardar_receta_en_bd(
                $objetivo,
                $preferencias,
                $restricciones,
                $tipo_comida,
                $nombre,
                $slug,
                $datos
            );
        }
        return $plan_json;
    }
}

/**
 * Genera una plantilla JSON de ejemplo para un plan semanal de ejercicio,
 * con múltiples ejercicios por día, incluyendo el wrapper "semana".
 *
 * @param int    $dias_semana       Número de días en la semana.
 * @param string $tiempo_dedicacion Tiempo por sesión ('15min','30min','45min','60min+').
 * @return string                   Cadena JSON de ejemplo con clave "semana".
 */
function estructura_json_ejercicio( int $dias_semana, string $tiempo_dedicacion ): string {
    // Mapear tiempo a número de ejercicios por día
    $map = [
        '15min'  => 2,
        '30min'  => 4,
        '45min'  => 6,
        '60min+' => 8,
    ];
    $count = $map[ $tiempo_dedicacion ] ?? 4;

    $dias = [];
    for ( $i = 1; $i <= $dias_semana; $i++ ) {
        $ejercicios = [];
        for ( $j = 1; $j <= $count; $j++ ) {
            $ejercicios[] = <<<EJ
            {
              "Ejercicio": "Nombre ejercicio #{$j}",
              "Explicación": "Breve descripción del ejercicio #{$j}"
     
            }
EJ;
        }
        $lista = implode(",
", $ejercicios);
        $dias[] = <<<DAY
        "Día {$i}": [
{$lista}
        ]
DAY;
    }

    $semana = implode(",
", $dias);

    return <<<JSON
{
  "semana": {
{$semana}
  }
}
JSON;
}



/**
 * Obtiene un plan de ejercicio para un día concreto:
 *   - Primero intenta de la BD (fe_get_exercise_plan_from_db)
 *   - Si no existe, llama a la API (obtener_plan_ejercicio_api)
 *   - Cuando lo recibe, lo guarda (fe_save_exercise_plan_to_db)
 *
 * @param string $objetivo
 * @param string $lugar
 * @param int    $dias_semana
 * @param string $tiempo
 * @param int    $dia
 * @return array|null  El plan para ese día (ejercicio, series, repeticiones, duración, equipamiento) o null.
 */
function obtener_plan_ejercicio( $objetivo, $lugar, $dias_semana, $tiempo, $sexo, $nivel ) {
    // 1) Intentar BD
    $cached = fe_get_exercise_plan_from_db( $objetivo, $lugar, $dias_semana, $tiempo, $sexo, $nivel );
    if ( $cached ) {
        $plan = json_decode( $cached, true );
        if ( is_array( $plan ) ) {
            return $plan;
        }
    }

    // 2) Si no hay en BD, generarlo por API
    // Aquí llamamos a tu función que monta el prompt y descodifica
    $full_plan = obtener_plan_ejercicio_api( [
        'objetivo'          => $objetivo,
        'lugar'             => $lugar,
        'dias_entreno_semana'       => $dias_semana,
        'tiempo_dedicacion' => $tiempo,
        'sexo'              => $sexo,
        'nivel'             => $nivel,
    ] );

    if ( ! $full_plan || ! isset( $full_plan['semana'] ) ) {
        return null;
    }

    // 3) Guardar el día concreto en BD
    fe_save_exercise_plan_to_db(
        $objetivo,
        $lugar,
        $dias_semana,
        $tiempo,
        $sexo,
        $nivel,
        $full_plan['semana']
    );

    return $full_plan['semana'];
}

/**
 * Llama a la API de OpenAI para generar un plan semanal de ejercicio y devuelve siempre un array con clave 'semana'.
 * Normaliza la salida convirtiendo arrays de strings en arrays de objetos con clave 'Ejercicio'.
 *
 * @param array $params Parámetros:
 *   - objetivo
 *   - lugar
 *   - dias_semana
 *   - tiempo_dedicacion
 *   - sexo
 *   - nivel
 * @return array|null   Array con clave 'semana' o null en caso de error.
 */
function obtener_plan_ejercicio_api( array $params ): ?array {
    // 1) Plantilla JSON de ejemplo
    if ( ! function_exists( 'estructura_json_ejercicio' ) ) {
        require_once plugin_dir_path( __FILE__ ) . 'helper-functions.php';
    }
    $estructura = estructura_json_ejercicio( $params['dias_entreno_semana'], $params['tiempo_dedicacion'] );

    // 2) Construir prompt
    $prompt = <<<TXT
Eres un entrenador personal experto. Genera un plan de ejercicio semanal completo basado en:

- Objetivo: {$params['objetivo']}
- Lugar: {$params['lugar']}
- Días/semana: {$params['dias_entreno_semana']}
- Tiempo por sesión: {$params['tiempo_dedicacion']}
- Género: {$params['sexo']}
- Nivel: {$params['nivel']}

Tu respuesta debe ser **solo JSON** con esta estructura de ejemplo:
{$estructura}

Requisitos:
1. Cada día debe ser clave "Día N" con un array de ejercicios.
2. Todos los valores ir entre comillas dobles.
3. Sin backticks ni bloques de código.
TXT;

    // 3) Llamar API
    $raw = consulta_api_openai( $prompt, 'system' );
    if ( ! $raw ) {
        error_log('[API Ejercicio] respuesta vacía');
        return null;
    }

    // 4) Limpiar Markdown fences
    $clean = preg_replace('/^```(?:json)?\s*|```$/i', '', trim($raw));
    $start = strpos($clean, '{');
    $end   = strrpos($clean, '}');
    if ($start === false || $end === false) {
        error_log("[API Ejercicio] formato inesperado, no encuentro llaves: {$clean}");
        return null;
    }
    $json_text = substr($clean, $start, $end - $start + 1);

    // 5) Decodificar JSON
    $data = json_decode( $json_text, true );
    if ( json_last_error() !== JSON_ERROR_NONE ) {
        error_log('[API Ejercicio] JSON error: '.json_last_error_msg());
        error_log('[API Ejercicio] Texto a decodificar: '. $json_text);
        return null;
    }
    error_log(var_export($data, true));
    // 6) Asegurar array top-level
    $semana = [];
    if ( isset( $data['semana'] ) && is_array( $data['semana'] ) ) {
        $semana = $data['semana'];
    } elseif ( is_array( $data ) ) {
        $semana = $data;
    }

    // 7) Normalizar días que vienen como array de strings
    foreach ( $semana as $dia => $ejArr ) {
        if ( is_array( $ejArr ) && count( $ejArr ) > 0 && is_string( current( $ejArr ) ) ) {
            $converted = [];
            foreach ( $ejArr as $str ) {
                $converted[] = [
                    'Ejercicio'    => $str,
                    'Explicación'  => '',
                    'Series'       => '',
                    'Repeticiones' => '',
                    'Duración'     => '',
                    'Equipamiento' => []
                ];
            }
            $semana[ $dia ] = $converted;
        }
    }

    return [ 'semana' => $semana ];
}



/**
 * Genera el prompt para obtener un mensaje motivacional en HTML con iconos.
 *
 * @param string $nombre   Nombre del usuario.
 * @param int    $edad     Edad del usuario.
 * @param string $objetivo Objetivo del usuario.
 * @param string $lugar    Lugar de entrenamiento.
 * @return string          Prompt listo para enviar a la API.
 */
function build_motivational_prompt( string $nombre, int $edad, string $sexo, string $objetivo, string $lugar ): string {
    // Escapar variables
    $nombre   = esc_html( $nombre );
    $edad     = intval( $edad );
    $objetivo = esc_html( $objetivo );
    $lugar    = esc_html( $lugar );

    return trim( <<<TXT
Eres un coach motivacional experto en bienestar. Crea un mensaje de **hasta 500 caracteres** que:
- Salude al usuario por su nombre (<strong>{$nombre}</strong>), háblele según su sexo ({$sexo}) y mencione su edad ({$edad} años).
- Refuerce su objetivo: "<em>{$objetivo}</em>".
- Incluya consejos breves de alimentación y ejercicio para entrenar en "<em>{$lugar}</em>".
- Use **HTML** para marcar secciones (`<strong>`, `<em>`, `<ul>`, `<li>`).
- Incorpore iconos Unicode (por ejemplo ✅, 🥗, 💪).
- Termine con un mensaje de ánimo (“¡Tú puedes!”).

Devuélvelo **solo** como HTML válido, sin etiquetas `<html>` ni `<body>`, en un solo bloque de texto.
TXT
    );
}

/**
 * Llama a la API de OpenAI para obtener el mensaje motivacional.
 *
 * @param string $nombre
 * @param int    $edad
 * @param string $objetivo
 * @param string $lugar
 * @return string|false  HTML motivacional o false en error.
 */
function get_motivational_message( string $nombre, int $edad, string $sexo, string $objetivo, string $lugar ) {
    // 1) Construir prompt
    $prompt = build_motivational_prompt( $nombre, $edad, $sexo, $objetivo, $lugar );

    // 2) Llamar a la API (reutiliza tu función existente)
    // Asegúrate que consulta_api_openai devuelva el contenido del mensaje
    $raw = consulta_api_openai( $prompt, 'system' );
    if ( ! $raw ) {
        error_log( '[Motivation] API returned empty response' );
        return false;
    }

    // 3) Limpiar posibles backticks o código markdown
    $sanitized = preg_replace( '/^```(?:html)?\s*|```$/i', '', trim( $raw ) );
    // 4) Devolver el HTML (no decodificamos JSON, es texto con etiquetas)
    return trim( $sanitized );
}
/**
 * Obtiene o genera un mensaje motivacional para el usuario.
 *
 * @param WP_User $user
 * @return string|null HTML del mensaje o null si hay error.
 */
function obtener_mensaje_motivacional( $user ) {
    $user_id   = $user->ID;
    
    // USER_META
    $nombre    = get_user_meta( $user_id, 'billing_first_name', true )  ?: $user->display_name;
    $edad      = intval( get_user_meta( $user_id, 'cliente_edad', true ) ?: 0 );

    // USER_ROLES
    $opciones_objetivo  = field_options('objetivo');
    $opciones_lugar     = field_options('lugar');
    $opciones_sexo      = field_options('sexo');

    // 3) Roles → objetivo y lugar de entrenamiento
    $objetivo = '';
    $lugar    = '';
    $sexo    = '';
    foreach ( $user->roles as $rol ) {
        if ( array_key_exists($rol,  $opciones_objetivo ) ) {
            $objetivo = get_role_name_from_slug( $rol );
        }
        if ( array_key_exists($rol,  $opciones_lugar ) ) {
            $lugar = get_role_name_from_slug( $rol );
        }
        if ( array_key_exists($rol,  $opciones_sexo ) ) {
            $sexo = get_role_name_from_slug( $rol );
        }
    }

    // 1) Intentar BD
    /*
    $cached = fe_get_motivational_from_db( $user_id, $nombre, $edad, $objetivo, $lugar, $sexo, $nivel );
    if ( $cached ) {
        return $cached;
    }
    */

    // 2) Generar con API
    $api_response = get_motivational_message( $nombre, $edad, $sexo, $objetivo, $lugar ); 
    if ( ! $api_response ) {
        return null;
    }

    // 3) Guardar en BD
    save_motivational_messages( $user_id, $nombre, $edad, $sexo, $objetivo, $lugar, $api_response );

    // 4) Devolver
    return $api_response;
}



function consulta_api_openai($prompt, $role_system) {
    $data = [
        "model" => "gpt-3.5-turbo",
        "messages" => [
            ["role" => "system", "content" => $role_system],
            ["role" => "user", "content" => $prompt]
        ],
        "temperature" => 1.0
    ];
    $ch = curl_init("https://api.openai.com/v1/chat/completions");
    $api_key = OPENAI_API_KEY;
    // Prompt para enviar a ChatGPT
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer $api_key"
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        $error_msg = "❌ cURL Error: " . curl_error($ch);
        error_log($error_msg); // Registra en error_log de WordPress
        curl_close($ch);
        return false;
    }

    curl_close($ch);

    $decoded_response = json_decode($response, true);

    // Si la API devuelve un error, lo registramos en el log
    if (!$decoded_response || isset($decoded_response["error"])) {
        $error_msg = "❌ OpenAI API Error: " . json_encode($decoded_response);
        error_log($error_msg); // Registra en el error_log de WordPress
        return false;
    }
    return $decoded_response["choices"][0]["message"]["content"] ?? false;
}
