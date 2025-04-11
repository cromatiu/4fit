<?php 
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

function obtener_plan_desde_bd($peso, $altura,  $objetivo, $preferencias, $restricciones, $comidasDiarias, $dia) {
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

function guardar_plan_en_bd($peso, $altura, $objetivo, $preferencias, $restricciones, $comidasDiarias, $dia, $plan_json) {
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

function generar_estructura_json_alimentacion($comidasDiarias) {

    $tipos_comida = match ($comidasDiarias) {
        3 => ['desayuno', 'almuerzo', 'cena'],
        4 => ['desayuno', 'media-mañana', 'almuerzo', 'cena'],
        5 => ['desayuno', 'media-mañana', 'almuerzo', 'merienda', 'cena'],
        default => ['desayuno', 'almuerzo', 'cena'],
    };

    $estructura = "{
    \\\"dia\\\": \$dia,
    \\\"comidas\\\": {\n";

    foreach ($tipos_comida as $tipo) {
        $estructura .= "        \\\"$tipo\\\": { \\\"plato\\\": \\\"Nombre del plato\\\", \\\"ingredientes\\\": [...], \\\"preparacion\\\": \\\"...\\\" },\n";
    }

    $estructura = rtrim($estructura, ",\n") . "\n    }\n}";

    return $estructura;
}

function obtener_plan_alimentacion($peso, $altura, $objetivo, $preferencias, $restricciones, $comidasDiarias, $dia) {
    // 🔁 Redondear peso y altura
    $peso = redondear_a_multiplo_de_5($peso);
    $altura = redondear_a_multiplo_de_5($altura);
    // Buscar en la base de datos antes de hacer la solicitud a la API
    $planExistente = obtener_plan_desde_bd($peso, $altura, $objetivo, $preferencias, $restricciones, $comidasDiarias, $dia);

    if ($planExistente) {
        return $planExistente; // Retornar el plan almacenado si existe
    }
    $estructura_json = generar_estructura_json_alimentacion($comidasDiarias);
    // Si no existe, generar uno nuevo con OpenAI
    $prompt = "
    Genera un plan mensual de alimentación para el día $dia basado en los siguientes datos del usuario:
    - Peso: $peso kg
    - Altura: $altura cm
    - Objetivo: $objetivo
    - Preferencias Alimentarias: $preferencias
    - Restricciones Alimentarias: $restricciones
    - Número de Comidas Diarias: $comidasDiarias

    Devuelve el resultado en formato JSON, estructurado de la siguiente forma:
    $estructura_json
    Devuelve solo un objeto JSON sin envolverlo en bloques de código ni usar backticks (```).
    Ajusta la cantidad de comidas diarias entre 3 y 5 según la preferencia del usuario.
    Asegúrate de que cada día tenga un menú variado y equilibrado según las necesidades del usuario.
    Evita repetir platos o ingredientes durante los días del mes. Asegúrate de que cada día tenga combinaciones únicas de comidas y preparación.
    Crea menús variados, incluyendo recetas de diferentes culturas y estilos de cocina. Evita repetir ingredientes principales en días consecutivos.

    ";
    $role_system = "Eres un nutricionista creativo que diseña menús variados, equilibrados y originales para evitar la monotonía.";

    $plan_json = consulta_api_openai ($prompt, $role_system);

    guardar_plan_en_bd($peso, $altura, $objetivo, $preferencias, $restricciones, $comidasDiarias, $dia, json_decode($plan_json, true));

    return $plan_json;
}

function consulta_api_openai ($prompt, $role_system) {
    $data = [
        "model" => "gpt-3.5-turbo",
        "messages" => [
            ["role" => "system", "content" => $role_system],
            ["role" => "user", "content" => $prompt]
        ],
        "temperature" => 1.0
    ];
    $ch = curl_init("https://api.openai.com/v1/chat/completions");
    $api_key = 'AI_KEY';
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

function redondear_a_multiplo_de_5($valor) {
    return round($valor / 5) * 5;
}