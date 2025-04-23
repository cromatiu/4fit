<?php 
function redondear_a_multiplo_de_5($valor) {
    if(!$valor) {
        return;
    }
    $valor = intval($valor);
    return round($valor / 5) * 5;
}
function role_options($type) {
    switch ($type) {
        case 'comidas_diarias':
            $options = array(
                '3' => '3',
                '4' => '4',
                '5' => '5'
            );
            break;
        case 'objetivo':
            $options = array(
                'perdidapeso'   => 'Pérdida de peso',
                'mantenimiento' => 'Mantenimiento',
                'ganancia'      => 'Ganancia muscular'
            );
            break;
        case 'preferencias':
            $options = array(
                'vegano'        => 'Vegano',
                'vegetariano'   => 'Vegetariano',
                'omnivoro'      => 'Omnívoro',
                'pescetariano'  => 'Pescetariano'
            );
            break;
        case 'restricciones':
            $options = array(
                'ninguna'       => 'Ninguna',
                'sinlactosa'    => 'Sin lactosa',
                'celiaco'       => 'Celiaco',
                'sinpescado'    => 'Sin pescado',

                'frutosconcascara' => 'F. cáscara',
                'soja'          => 'Soja',
                'crustaceos'    => 'Crustáceos',
                'moluscos'      => 'Moluscos',
                'huevos'        => 'Huevos',
                'cacahuetes'    => 'Cacahuetes',
                'apio'          => 'Apio',
                'mostaza'       => 'Mostaza',
                'sesamo'        => 'Sésamo',
                'altramuces'    => 'Altramuces',
                'sulfitos'      => 'Sulfitos'
            );
            break;
        default:
        $options = array();
        break;
    }
    return $options;
}
function display_nutrition($plan) {
    ob_start();
    echo "<div class='plan-personalizado'>";
    echo "<h2>Día {$plan['dia']} - Tu plan personalizado</h2>";
    foreach ($plan['comidas'] as $tipo => $comida) {
        echo "<h3>" . ucfirst($tipo) . ": " . esc_html($comida['plato']) . "</h3>";
        echo "<div class='text-content'><strong>Ingredientes:</strong><ul>";
        foreach ($comida['ingredientes'] as $ing) {
            echo "<li><i class='icon icon-check_circle' aria-hidden='true'></i> " 
                 . esc_html($ing) . "</li>";
        }
        echo "</ul>";
        echo "<p><strong>Preparación:</strong> " . esc_html($comida['preparacion']) . "</p>";
        echo "</div>";
    }
    echo "</div>";
    return ob_get_clean();
}

function db_json_to_array($plan_json) {
    // Log del raw para depuración
    error_log(">> Raw plan (pre-JSON): " . var_export($plan_json, true));

    // 5) Decodificar JSON de forma robusta
    if (is_string($plan_json)) {
        // Quitar posibles backticks o markdown
        $sanitized = preg_replace('/^```json\s*|```$/', '', trim($plan_json));
        $plan = json_decode($sanitized, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("JSON decode error: " . json_last_error_msg());
            $plan = null;
        }
    } else {
        $plan = $plan_json;
    }
    return $plan;
}