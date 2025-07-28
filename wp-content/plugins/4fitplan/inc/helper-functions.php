<?php 
function redondear_a_multiplo_de_5($valor) {
    if(!$valor) {
        return;
    }
    $valor = intval($valor);
    return round($valor / 5) * 5;
}
function field_options($type) {
    switch ($type) {
        case 'sexo':
            $options = array(
                'mujer'    => 'Mujer',
                'hombre'   => 'Hombre',
                
            );
            break;
        case 'nivel':
            $options = array(
                'iniciacion'    => 'Iniciación',
                'facil'         => 'Fácil',
                'avanzado'      => 'Avanzado',
                'pro'           => 'Pro',
                
            );
            break;
        case 'lugar':
            $options = array(
                'casa'       => 'Casa',
                'gym'       => 'Gym',
                'airelibre'  => 'Aire libre',
                'mixto'      => 'Mixto',
            );
            break;
        case 'comidas_diarias':
            $options = array(
                '3' => '3',
                '4' => '4',
                '5' => '5'
            );
            break;
        case 'dias_entreno_semana':
            $options = array(
                '2' => '2 días',
                '3' => '3 días',
                '4' => '4 días',
                '5' => '5 días',
                '6' => '6 días',
                '7' => '7 días',
            );
            break;
        case 'tiempo_entreno':
            $options = array(
                '15min'  => '15 minutos',
                '30min'  => '30 minutos',
                '45min'  => '45 minutos',
                '60min+' => 'Más de 60 minutos',
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
                'omnivoro'      => 'Omnívoro',
                'vegetariano'   => 'Vegetariano',
                'vegano'        => 'Vegano',
                'pescetariano'  => 'Pescetariano'
            );
            break;
        case 'restricciones_list':
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
// DEVUELVE LOS GRUPOS DE ROLES PARA PROCESARLOS
function field_role_group_slugs( string $field_slug ): array {
    $groups = ['sexo', 'nivel', 'lugar', 'objetivo', 'preferencias', 'restricciones_list'];
    if ( in_array($field_slug, $groups, true) ) {
        return array_keys(field_options($field_slug));
    }
    return [];
}


function form_fields($type) {
    switch ($type) {
        case 'medidas':
            $fields = [
                131 =>  'cliente_peso',
                132 =>  'pecho',
                151 =>  'cintura',
                133 =>  'cadera',
                134 =>  'muslo',
                135 =>  'pantorrilla'
            ]; 
            break;
        case 'imagenes' :
            $fields = [
                142 => 'frente',
                143 => 'perfil',
                144 => 'espalda'
            ];
            break;
        default:
            $fields = array();
            break;
    }
    return $fields;
} 
function display_nutrition($plan) {
    ob_start();
    echo "<div class='plan-personalizado'>";
    echo "<h2>Día {$plan['dia']} - Tu plan personalizado</h2>";
    foreach ($plan['comidas'] as $tipo => $comida) {
        echo "<h3>" . ucfirst($tipo) . ": " . esc_html($comida['plato']) . "</h3>";
        echo display_receta($comida);
    }
    echo "</div>";
    return ob_get_clean();
}
/**
 * Muestra una receta (ya sea un array de datos o un registro de BD con receta_json).
 *
 * @param array $receta Registro devuelto por $wpdb->get_results() o bien el propio array decodificado.
 * @return string       HTML de la receta.
 */
function display_receta( $receta, $title = false) {
    ob_start();

    // 1) Si viene de la BD, decodificamos la columna receta_json
    if ( isset( $receta['receta_json'] ) ) {
        $datos = json_decode( $receta['receta_json'], true );
    } else {
        // si ya es el array plano
        $datos = $receta;
    }

    // 2) Compruebo que al menos haya un 'plato'
    if ( empty( $datos['plato'] ) ) {
        return ''; // nada que mostrar
    }

    // 3) Pinto la receta
    echo '<article class="receta-item">';
    if($title) {
        echo '<h3>' . $datos['plato'] . '</h3>';
    }
    if ( ! empty( $datos['ingredientes'] ) && is_array( $datos['ingredientes'] ) ) {
        echo "<div class='text-content'><strong>Ingredientes:</strong><ul>";
        foreach ( $datos['ingredientes'] as $ing ) {
            echo '<li><i class="icon icon-check_circle" aria-hidden="true"></i> '
                 . esc_html( $ing ) . '</li>';
        }
        echo "</ul>";
    }

    if ( ! empty( $datos['preparacion'] ) ) {
        printf(
            '<p><strong>Preparación:</strong> %s</p>',
            esc_html( $datos['preparacion'] )
        );
    }
    echo '</div></article>';

    return ob_get_clean();
}

function render_weekly_exercise_plan( array $semana ) {
    if ( empty( $semana ) ) {
        return '<p>No hay plan de ejercicio para esta semana.</p>';
    }

    ob_start(); ?>
    <div class="plan-personalizado">
      <h2>Tu Plan Semanal de Ejercicio</h2>

      <?php foreach ( $semana as $dia => $ejercicios ) :
        if ( ! is_array( $ejercicios ) ) {
            continue;
        }
      ?>
        <section class="plan-dia">
          <h3><?php echo esc_html( $dia ); ?></h3>
          <div class="text-content">
            <ul class="ejercicio-list">
              <?php foreach ( $ejercicios as $ej ) :
                // Extraemos solo los campos que no estén vacíos
                $nombre      = ! empty( $ej['Ejercicio'] )    ? esc_html( $ej['Ejercicio'] )    : null;
                $series      = ! empty( $ej['Series'] )       ? esc_html( $ej['Series'] )       : null;
                $reps        = ! empty( $ej['Repeticiones'] ) ? esc_html( $ej['Repeticiones'] ) : null;
                $duracion    = ! empty( $ej['Duración'] )     ? esc_html( $ej['Duración'] )     : null;
                $explicacion = ! empty( $ej['Explicación'] )  ? esc_html( $ej['Explicación'] )  : null;
                $equip       = ! empty( $ej['Equipamiento'] ) ? esc_html( $ej['Equipamiento'] ) : null;

                // Si ni siquiera el nombre existe, lo saltamos
                if ( ! $nombre ) {
                    continue;
                }
              ?>
                <li class="ejercicio-item"><i class="icon icon-check_circle" aria-hidden="true"></i> 
                  <strong><?php echo $nombre; ?>. </strong>
                  <?php if ( $series && $reps ) : ?>
                    <?php echo $series; ?>×<?php echo $reps; ?>
                  <?php endif; ?>

                  <?php if ( $duracion ) : ?>
                    <em>(<?php echo $duracion; ?>)</em>
                  <?php endif; ?>

                  <?php if ( $explicacion ) : ?>
                    <p><?php echo $explicacion; ?></p>
                  <?php endif; ?>

                  <?php if ( $equip ) : ?>
                    <small>Equipamiento: <?php echo $equip; ?></small>
                  <?php endif; ?>
                </li>
              <?php endforeach; ?>
            </ul>
          </div>
        </section>
      <?php endforeach; ?>
    </div>
    <?php
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

function get_comidas_diarias_types($comidas_diarias) {
    $tipos = array();
    switch ($comidas_diarias) {
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
    
    return $tipos;
}

function uf_get_user_fields( WP_User $user, array $sections = ['nutrition','exercise', 'personal'], bool $with_values = false ): array {
    $user_id = $user->ID;
    $roles   = $user->roles;
    $fields  = [];

    if ( in_array( 'personal', $sections, true ) ) {
        $fields += [
            'billing_first_name' => get_user_meta( $user_id, 'billing_first_name', true ),
            'cliente_edad'       => get_user_meta( $user_id, 'cliente_edad', true ),
        ];
    }

    if ( in_array( 'nutrition', $sections, true ) ) {
        $objetivo_array      = array_intersect( $roles, array_keys( field_options('objetivo') ) );
        $preferencias_array  = array_intersect( $roles, array_keys( field_options('preferencias') ) );
        $restricciones_array = array_intersect( $roles, array_keys( field_options('restricciones_list') ) );
        
        $fields += [
            'cliente_peso'        => get_user_meta( $user_id, 'cliente_peso', true ),
            'cliente_altura'      => get_user_meta( $user_id, 'cliente_altura', true ),
            'comidas_diarias'     => get_user_meta( $user_id, 'comidas_diarias', true ),
            'objetivo'            => reset($objetivo_array)     ?: '',
            'preferencias'        => reset($preferencias_array) ?: '',
            'restricciones_list'  => $restricciones_array
            ? implode(', ', $restricciones_array)
            : '',
        ];
    }
    
    if ( in_array( 'exercise', $sections, true ) ) {
        $objetivo_array      = array_intersect( $roles, array_keys( field_options('objetivo') ) );
        $sexo_array   = array_intersect( $roles, array_keys( field_options('sexo') ) );
        $nivel_array  = array_intersect( $roles, array_keys( field_options('nivel') ) );
        $lugar_array  = array_intersect( $roles, array_keys( field_options('lugar') ) );

        $fields += [
            // 'objetivo' ya viene de nutrition, no lo ponemos aquí
            'objetivo'            => reset( $objetivo_array ) ?: '',
            'sexo'                => reset( $sexo_array ) ?: '',
            'nivel'               => reset( $nivel_array ) ?: '',
            'lugar'               => reset( $lugar_array ) ?: '',
            'dias_entreno_semana' => get_user_meta( $user_id, 'dias_entreno_semana', true ),
            'tiempo_entreno'      => get_user_meta( $user_id, 'tiempo_entreno', true ),
        ];
    }

    if ( $with_values ) {
        return $fields;
    }

    // Ahora sí detectamos correctamente solo los que están realmente vacíos
    return array_keys( array_filter(
        $fields,
        fn( $v ) => $v === '' || $v === null || $v === false
    ) );
}



/**
 * Devuelve un array slug=>etiqueta legible para cada campo.
 *
 * @return array
 */
function uf_get_field_labels(): array {
    return [
        'billing_first_name'    => 'Nombre',
        'cliente_edad'          => 'Edad',
        'cliente_peso'          => 'Peso (kg)',
        'cliente_altura'        => 'Altura (cm)',
        'comidas_diarias'       => 'Comidas diarias',
        'objetivo'              => 'Objetivo',
        'preferencias'          => 'Preferencias',
        'restricciones_list'         => 'Restricciones',
        'sexo'                  => 'Género',
        'nivel'                 => 'Nivel físico',
        'lugar'                 => 'Lugar de entrenamiento',
        'dias_entreno_semana'   => 'Días de entrenamiento/semana',
        'tiempo_entreno'        => 'Tiempo por sesión',
    ];
}

/**
 * Renderiza en HTML un array slug=>valor obtenido de uf_get_user_fields(…, true).
 *
 * @param array $fields Array slug=>valor (puede ser scalar o array).
 * @return string       Lista <ul> con cada campo presente.
 */
function uf_render_user_fields( array $fields, WP_User $user, bool $self = true): string {
    if ( empty( $fields ) ) return '<p>No hay datos para mostrar.</p>';

    $labels = uf_get_field_labels();
    $user_id = $user->ID;
    $user_name = get_user_meta($user_id, 'billing_first_name', true);

    $data_header = ($self) ? 'Mis datos 4fit' : 'Los datos de ' . $user_name;

    ob_start();
    ?>
    <div class="user-display-data">
        <h2><?php echo $data_header; ?></h2>
        <ul class="uf-user-fields" data-user-id="<?php echo esc_attr($user_id); ?>">
            <?php foreach ( $fields as $slug => $value ):
                if ( empty( $labels[ $slug ] ) ) continue;
                
                $label = esc_html( $labels[ $slug ] );
                $val = '';

                // Si el campo corresponde a un grupo guardado como roles
                if ( in_array($slug, ['sexo','nivel','lugar','preferencias','objetivo','restricciones']) ) {
                    $roles = $user->roles;
              
                    $options = field_options($slug);

                    // Filtra los roles del usuario que pertenecen a este grupo
                    $selected = array_filter($roles, fn($r) => array_key_exists($r, $options));

                    // Convierte a nombres legibles
                    $display = array_map(function($r) use ($options) {
                        return esc_html($options[$r] ?? $r);
                    }, $selected);

                    $val = implode(', ', $display);
                } 
                // Si es array (como algunos otros campos)
                elseif ( is_array( $value ) ) {
                    $opts = field_options( $slug );
                    $display = array_map(fn($v) => esc_html($opts[$v] ?? $v), $value);
                    $val = implode(', ', $display);
                } 
                // O simplemente el valor plano
                else {
                    $val = esc_html( $value );
                }
                
                
                
                ?>
                <li data-field="<?php echo esc_attr($slug); ?>">
                    <strong><?php echo $label; ?>:</strong>
                    <span class="uf-field-value"><?php echo $val ?: '<em>Sin completar</em>'; ?></span>
                    <button class="uf-edit-btn" data-slug="<?php echo esc_attr($slug); ?>" data-user-id="<?php echo esc_attr($user_id); ?>"><i aria-hidden="true" class="icon icon-edit"></i></button>
                    <div class="uf-field-form" style="display:none;"></div>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php
    return ob_get_clean();
}



function get_user_suscription_status($user) {

    $member                 = pms_get_member( $user->ID );
    
    if(isset($member->subscriptions)) {
        $subscription           = reset($member->subscriptions);
        if(isset($subscription['status'] )) {
            $status = $subscription['status'];
            return $status;
        }
    }
    return 'none';
}

function get_user_id_from_affiliate_id( $affiliate_id ) {
    global $wpdb;

    $table = $wpdb->prefix . 'wpam_affiliates';

    $user_id = $wpdb->get_var(
        $wpdb->prepare( "SELECT userId FROM $table WHERE affiliateId = %d", $affiliate_id )
    );

    return $user_id ? intval( $user_id ) : null;
}