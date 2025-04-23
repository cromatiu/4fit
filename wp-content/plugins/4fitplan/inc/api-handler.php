<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Devuelve un fragmento de JSON “vivo” (no escapado) con placeholders,
 * para que el modelo respete la cantidad exacta de comidas.
 */
function estructura_json_alimentacion(int $dia, int $comidasDiarias): string {
    switch ($comidasDiarias) {
        case 4:
            $tipos = ['desayuno','media mañana','almuerzo','cena'];
            break;
        case 5:
            $tipos = ['desayuno','media mañana','almuerzo','merienda','cena'];
            break;
        case 3:
        default:
            $tipos = ['desayuno','almuerzo','cena'];
            break;
    }

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

    return $plan_json;
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