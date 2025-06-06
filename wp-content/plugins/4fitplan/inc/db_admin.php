<?php 
    function agregar_menu_planes_alimentacion() {
        add_menu_page(
            'Planes de Alimentación',  // Título de la página
            'Planes de Alimentación',  // Texto del menú
            'manage_options',          // Capacidad requerida
            'planes-alimentacion',     // Slug del menú
            'mostrar_panel_planes',    // Función que renderiza la vista
            'dashicons-carrot',        // Ícono (zanahoria 🍏)
            25                         // Posición en el menú
        );
    }
    add_action('admin_menu', 'agregar_menu_planes_alimentacion');

    function mostrar_panel_planes() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'planes_alimentacion';
    
        // Obtener los filtros si se han aplicado
        $filtro_peso = isset($_GET['peso']) ? sanitize_text_field($_GET['peso']) : '';
        $filtro_altura = isset($_GET['altura']) ? sanitize_text_field($_GET['altura']) : '';
        $filtro_objetivo = isset($_GET['objetivo']) ? sanitize_text_field($_GET['objetivo']) : '';
    
        // Construcción de la consulta SQL con filtros
        $query = "SELECT * FROM $tabla WHERE 1=1";
        if (!empty($filtro_peso)) $query .= " AND peso = $filtro_peso";
        if (!empty($filtro_altura)) $query .= " AND altura = $filtro_altura";
        if (!empty($filtro_objetivo)) $query .= $wpdb->prepare(" AND objetivo LIKE %s", "%$filtro_objetivo%");
    
        $planes = $wpdb->get_results($query);
    
        // Renderizar la interfaz de administración
        ?>
        <div class="wrap">
            <h1>📋 Planes de Alimentación</h1>
            
            <!-- Formulario de filtros -->
            <form method="get" action="">
                <input type="hidden" name="page" value="planes-alimentacion">
                <input type="number" name="peso" placeholder="Peso (kg)" value="<?php echo esc_attr($filtro_peso); ?>">
                <input type="number" name="altura" placeholder="Altura (cm)" value="<?php echo esc_attr($filtro_altura); ?>">
                <input type="text" name="objetivo" placeholder="Objetivo" value="<?php echo esc_attr($filtro_objetivo); ?>">
                <button type="submit" class="button button-primary">🔍 Filtrar</button>
            </form>
    
            <h2>➕ Generar Nuevo Plan</h2>
            <form id="form-generar-plan">
                <input type="number" name="peso" placeholder="Peso (kg)" required>
                <input type="number" name="altura" placeholder="Altura (cm)" required>
                <div class="form-cont">
                    <label for="objetivo">Objetivo:</label><br>
                    <select name="objetivo" id="objetivo" required>
                        <option value="pérdida de peso">Pérdida de peso</option>
                        <option value="mantenimiento">Mantenimiento</option>
                        <option value="ganancia muscular">Ganancia muscular</option>
                    </select>
                </div>
                <div class="form-cont">
                <label for="preferencias">Preferencias Alimentarias:</label><br>
                    <select name="preferencias" id="preferencias">
                        <option value="">Ninguna</option>
                        <option value="omnívoro">Omnívoro</option>
                        <option value="vegetariano">Vegetariano</option>
                        <option value="vegano">Vegano</option>
                        <option value="cetogénico">Cetogénico</option>
                        <option value="paleo">Paleo</option>
                    </select>
                </div>
                <div class="form-cont">
                <label for="restricciones">Restricciones Alimentarias:</label><br>
                    <select name="restricciones[]" id="restricciones" multiple size="4">
                        <option value="sin gluten">Sin gluten</option>
                        <option value="sin lactosa">Sin lactosa</option>
                        <option value="sin frutos secos">Sin frutos secos</option>
                        <option value="sin mariscos">Sin mariscos</option>
                        <option value="sin huevo">Sin huevo</option>
                    </select>
                </div>
                <input type="number" name="comidas_diarias" placeholder="Comidas al día (3-5)" required>
                <input type="number" name="dia" placeholder="Día (1-30)" required>

                <button type="submit" class="button button-primary">⚡ Generar Plan</button>
            </form>


            <div id="respuesta-plan"></div>

            <h2>📂 Planes Generados</h2>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Peso</th>
                        <th>Altura</th>
                        <th>Objetivo</th>
                        <th>Preferencias</th>
                        <th>Restricciones</th>
                        <th>Comidas/Día</th>
                        <th>Día</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $planes = $wpdb->get_results("SELECT * FROM $tabla");
                    foreach ($planes as $plan) :
                    ?>
                        <tr>
                            <td><?php echo $plan->id; ?></td>
                            <td><?php echo $plan->peso; ?> kg</td>
                            <td><?php echo $plan->altura; ?> cm</td>
                            <td><?php echo esc_html($plan->objetivo); ?></td>
                        
                            <td><?php echo $plan->preferencias; ?></td>
                            <td><?php echo $plan->restricciones; ?></td>
                            <td><?php echo $plan->comidas_diarias; ?></td>
                            <td><?php echo $plan->dia; ?></td>
                            <td>
                                <a href="?page=planes-alimentacion&eliminar=<?php echo $plan->id; ?>" class="button button-danger">🗑️ Eliminar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <style>
            #form-generar-plan {
                display: flex;
                align-items: flex-end;
            }
        </style>
        <script>
            document.getElementById("form-generar-plan").addEventListener("submit", function(e) {
                e.preventDefault();
                let formData = new FormData(this);

                fetch("<?php echo admin_url('admin-ajax.php'); ?>?action=generar_plan", {
                    method: "POST",
                    body: formData
                })
                .then(response => response.json())
                .then(rawData => {
                    let data = rawData;

                    // Si llega como string JSON serializado, lo convertimos
                    if (typeof data === "string") {
                    data = JSON.parse(data);
                    }

                    if (data.dia && data.comidas) {
                    let html = `<h2>📅 Día ${data.dia}</h2>`;

                    for (let tipo in data.comidas) {
                        const comida = data.comidas[tipo];
                        html += `<h3>${iconoComida(tipo)}: ${comida.plato}</h3>`;
                        html += "<p><strong>Ingredientes:</strong></p><ul>";

                        comida.ingredientes.forEach(ing => {
                            html += `<li>${ing}</li>`;
                        });

                        html += "</ul>";
                        html += `<p><strong>Preparación:</strong> ${comida.preparacion}</p>`;
                        }

                    document.getElementById("respuesta-plan").innerHTML = html;
                    } else {
                    document.getElementById("respuesta-plan").innerHTML = "<p>❌ Datos incompletos o mal formateados.</p>";
                    }
                    function iconoComida(tipo) {
                            const iconos = {
                                desayuno: "☕ Desayuno",
                                almuerzo: "🍱 Almuerzo",
                                comida: "🍽️ Comida",
                                cena: "🌙 Cena",
                                merienda: "🧁 Merienda",
                                "media-mañana": "🥤 Media mañana",
                                mediamañana: "🥤 Media mañana",
                                snack: "🥨 Snack"
                            };

                            return iconos[tipo.toLowerCase()] || `🍽️ ${tipo}`;
                        }
                })
                .catch(error => {
                    console.error("❌ Error al cargar el plan:", error);
                    document.getElementById("respuesta-plan").innerHTML = "<p>❌ Error al procesar el plan.</p>";
                });
            });
        </script>
        <?php
    }

    function obtener_plan_ajax() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'planes_alimentacion';
        $id = intval($_GET['id']);
    
        $plan = $wpdb->get_var($wpdb->prepare("SELECT plan_json FROM $tabla WHERE id = %d", $id));
    
        wp_send_json(json_decode($plan, true));
    }
    add_action('wp_ajax_obtener_plan', 'obtener_plan_ajax');

    function eliminar_plan() {
        if (isset($_GET['eliminar'])) {
            global $wpdb;
            $tabla = $wpdb->prefix . 'planes_alimentacion';
            $id = intval($_GET['eliminar']);
    
            $wpdb->delete($tabla, ['id' => $id], ['%d']);
    
            // Redirigir para evitar reenvío del formulario
            wp_redirect(admin_url('admin.php?page=planes-alimentacion'));
            exit;
        }
    }
    add_action('admin_init', 'eliminar_plan');

    function generar_plan_ajax() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'planes_alimentacion';
        
        $peso = intval($_POST['peso']);
        $peso = redondear_a_multiplo_de_5($peso);
        $altura = intval($_POST['altura']);
        $altura = redondear_a_multiplo_de_5($altura);
        $objetivo = sanitize_text_field($_POST['objetivo']);
        $preferencias = sanitize_text_field($_POST['preferencias']);
        $restricciones = $_POST['restricciones'] ?? [];
        $comidas_diarias = intval($_POST['comidas_diarias']);
        $dia = intval($_POST['dia']);
        // Verificar si el plan ya existe
        $plan_existente = $wpdb->get_var($wpdb->prepare(
            "SELECT plan_json FROM $tabla WHERE peso = %d AND altura = %d AND objetivo = %s AND comidas_diarias = %d AND dia = %d",
            $peso, $altura, $objetivo, $comidas_diarias, $dia
        ));
    
        if ($plan_existente) {
            wp_send_json(json_decode($plan_existente, true));
            return;
        }
    
        // Si no existe, se llama a la API
        $respuesta_api = obtener_plan_alimentacion($peso, $altura, $objetivo, $preferencias, implode(", ", $restricciones), $comidas_diarias, $dia);

        wp_send_json($respuesta_api);
    }
    add_action('wp_ajax_generar_plan', 'generar_plan_ajax');
    
    function editar_plan_ajax() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'planes_alimentacion';
    
        $id = intval($_POST['id']);
        $datos_actualizados = [];
    
        foreach ($_POST as $campo => $valor) {
            if ($campo !== 'id') {
                $datos_actualizados[$campo] = sanitize_text_field($valor);
            }
        }
    
        $wpdb->update($tabla, $datos_actualizados, ['id' => $id]);
    
        wp_send_json(["mensaje" => "Plan actualizado correctamente"]);
    }
    add_action('wp_ajax_editar_plan', 'editar_plan_ajax');