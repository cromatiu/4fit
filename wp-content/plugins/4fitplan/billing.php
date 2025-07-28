<?php 
/////////////////////////
// DESCARGAR INFORMES //
//////////////////////

// Función de configuración de página principal en el escritorio
function fourfit_billing_page() {
    add_menu_page(
        'Exportar facturación',    // Título de la página
        'Exportar facturación',    // Título del menú
        'manage_options',          // Capacidad requerida para acceder
        'export-billing',  // Slug de la página
        'export_billing_render' // Función de renderizado
    );
}

add_action('admin_menu', 'fourfit_billing_page');

// Función de renderizado de la página principal
function export_billing_render() {
    $default_start_date = (isset($_POST['start_date'])) ? $_POST['start_date'] : '';
    $default_end_date = (isset($_POST['end_date'])) ? $_POST['end_date'] : '';
    ?>
    <div class="wrap">
        <h2>Generador de informes</h2>
        <form method="post" action="">
            <p><strong>Selecciona fecha de principio y de fin</strong></p>
            <p>
                <label for="fecha_inicio">Fecha de Inicio:</label>
                <input type="date" id="fecha_inicio" name="start_date" value="<?php echo $default_start_date; ?>">
                
                <label for="fecha_fin">Fecha de Fin:</label>
                <input type="date" id="fecha_fin" name="end_date" value="<?php echo $default_end_date; ?>">
            </p>
            <p>
                <label for="currency"><strong>Selecciona la moneda de la que quieres obtener el achivo:</strong></label>
                <select name="currency">
                    <option value="EUR">Euro</option>
                    <option value="USD">US Dollar</option>
                    <option value="ARS">Argentine Peso</option>
                    <option value="MXN">Mexican Peso</option>
                </select>
            </p>

            <input type="submit" name="sales_report" value="Resumen de las facturas (inicio hasta hoy)">
            <input type="submit" name="billing_report" value="Resumen de las facturas (2024)" >
        </form>
    </div>
    <?php
    // Agrega el siguiente código en la misma ubicación que el código anterior.
    if (isset($_POST['sales_report']) || isset($_POST['billing_report'])) {
        // Procesa las fechas del formulario
        $start_date = sanitize_text_field($_POST['start_date']);
        $end_date   = sanitize_text_field($_POST['end_date']);
        $currency   = $_POST['currency'];

        // Realiza la consulta a la base de datos de WooCommerce
        $args = array(
            'post_type' => 'shop_order',
            'post_status' => 'wc-completed', // O cualquier estado de pedido que desees
            'date_query' => array(
                'after' => $start_date,
                'before' => $end_date,
                'inclusive' => true,
            ),
            'meta_query' => array(
                array(
                    'key'   => '_order_currency',
                    'value' => $currency,
                    'compare' => '='
                )
            ),
            'nopaging'  => true
        );
        $orders = get_posts($args);

        // Genera el CSV y guarda en la carpeta uploads
        if ($orders) {
            $csv_data = array();
            // GENERAMOS ENCABEZADOS
            // PARA BILLING REPORT
            if(isset($_POST['sales_report'])) {
                $csv_data[] = array(
                    'CLIENT',
                    'INVOICE NUMBER',
                    'REFERENCE NUMBER',
                    'INVOICE DATE',
                    'DUE DATE',
                    'DESCRIPTION',
                    'QUATITY',
                    'AMOUNT ' . $currency,
                    'RATE',
                    'EXCHANGE RATE',
                    'TOTAL',
                    'AED AMMOUNT',
                    'TAX TYPE',
                );
            }
            // PARA sales_report 
            if(isset($_POST['billing_report'])) {
                $csv_data[] = array(
                    '*InvoiceNo',
                    '*Customer',
                    '*InvoiceDate',
                    '*DueDate',
                    'Terms',
                    'Location',
                    'Memo',
                    'Item',
                    'ItemDescription',
                    'ItemQuantity',
                    'ItemRate',
                    '*ItemAmount'  . $currency,
                    '*ItemTaxCode',
                    '*ItemTaxAmount',
                    'ServiceDate',
                    'ItemRate',
                    'Exchange',
                    'ItemRateAED',
                );
            }
            foreach ($orders as $order) {
                // Personaliza esto según tus necesidades
                $order_id = $order->ID;
                $order = new WC_Order( $order->ID );
                $order_date = $order->get_date_created();
                $order_date_format = $order_date->format('d-m-Y');
                
                $post_meta = get_post_meta($order_id);
                
                $billing_number         = (isset($post_meta['_wcpdf_invoice_number'][0])) ? $post_meta['_wcpdf_invoice_number'][0] : '';
                
                $_billing_first_name    = (isset($post_meta['_billing_first_name'][0])) ? $post_meta['_billing_first_name'][0] : '';
                $_billing_last_name     = (isset($post_meta['_billing_last_name'][0])) ? $post_meta['_billing_last_name'][0] : '';
                $customer               = $_billing_first_name . ' ' . $_billing_last_name;
                
                $_billing_country       = (isset($post_meta['_billing_country'][0])) ? $post_meta['_billing_country'][0] : '';
                $location               =  WC()->countries->countries[$_billing_country];
                    
                    // Inicializa un array para almacenar los nombres de los productos
                $product_names  = array();
                $product_ids    = array();
                // Recorre los elementos del pedido
                foreach ($order->get_items() as $item_id => $item) {
                    // Obtiene el nombre del producto
                    $order_item     = new WC_Order_Item_Product($item_id);
                    $product_name   = $order_item->get_name();
                    $product_id     = $order_item->get_product_id();
                    // Agrega el nombre del producto al array
                    $product_names[] = $product_name;
                    $product_ids[]   = $product_id;
                }

                // Convierte el array de nombres de productos en una cadena
                $product_names      = implode(', ', $product_names);
                $product_id         = reset( $product_ids);

                $description        = get_product_description( $product_id );

                $total_eur          = $order->get_total();
                $currency           = get_post_meta($order_id, '_order_currency', true);

                $conversion         = convert_currency_to_aed($total_eur, $order_date, $currency);

                $exchange           = (isset($conversion['exchange'])) ? $conversion['exchange'] : 'error';
                $aed_amount         = (isset($conversion['aed_amount'])) ? $conversion['aed_amount'] : 'error';

                if(isset($_POST['sales_report'])) {
                    $csv_data[] = array(
                        $customer,              //'CLIENT',
                        $billing_number,        //'INVOICE NUMBER',
                        $order_id,              //'REFERENCE NUMBER',
                        $order_date_format,     //'INVOICE DATE',
                        $order_date_format,     //'DUE DATE',
                        $description,           //'DESCRIPTION',
                        '1',                    //'QUATITY',
                        $total_eur,             //'AMOUNT',
                        $exchange,              //'RATE',
                        $exchange,              //'EXCHANGE RATE',
                        $aed_amount,            //'TOTAL',
                        $aed_amount,            //'AED AMMOUNT',
                        '?'                     //'TAX TYPE',
                    );
                }

                // GENERAMOS DATOS PARA BILING_REPORT
                if(isset($_POST['billing_report'])) {
                    $csv_data[] = array(
                        '*InvoiceNo'    => $billing_number,     // *InvoiceNo
                        '*Customer'     => $customer,           //  *Customer
                        'InvoiceDate'   => $order_date_format,  //  InvoiceDate
                        'DueDate'       => $order_date_format,  //  DueDate
                        'Terms'         => '?',                 //  Terms
                        'Location'      => $location,           //  Location
                        'Memo'          => '?',                 //  Memo
                        'Item'          => $product_name,       //  Memo
                        'ItemDescription' => $description,      //  Item
                        'ItemQuantity'  => '1',                 //  ItemDescription
                        'ItemRate'      => $aed_amount,          //  ItemQuantity
                        'ItemAmount'    => $aed_amount,           //  Exchange
                        'ItemTaxCode'   => '?',         //  ItemRateAED
                        'ItemTaxAmount' => '?',           //  ID                // 
                        'ServiceDate'   => $order_date_format,  //  ServiceDate
                        'ItemRateEUR'   => $total_eur,
                        'Exchange'      => $exchange,
                        'ItemRateAED'   => $aed_amount,
                    );
                }
            }
            $file_name = $currency . '-' . $start_date . '-' . $end_date . '-';
            
            if(isset($_POST['sales_report'])) {
                $file_name .= 'sales-format-fta.csv';  
            }
            if(isset($_POST['billing_report'])) {
                $file_name .= 'invoice_import_tax-quickbooks.csv';  
            }

            $csv_file = fopen(WP_CONTENT_DIR . '/uploads/billing/' . $file_name, 'w');
            foreach ($csv_data as $line) {
                fputcsv($csv_file, $line);
            }
            fclose($csv_file);

            
            echo '<p>CSV generado con éxito. <a href="' . WP_CONTENT_URL . '/uploads/billing/' . $file_name . '">Descargar CSV</a></p>';
            echo '<pre>' . var_export($csv_data, true) . '</pre>';
        } else {
            echo '<p>No se encontraron pedidos en el rango de fechas especificado.</p>';
            echo '<pre>' . var_export($args) . '</pre>';
        }
    }
}

function convert_currency_to_aed($eur_amount, $date, $currency) {
    global $wpdb;
    switch ($currency) {
        case 'ARS':
            $tabla  = $wpdb->prefix . 'exch_ars_aed_exchanges'; // TABLA PARA PESOS ARGENTINOS
            break;
        case 'MXN':
             $tabla = $wpdb->prefix . 'exch_mxn_aed_exchanges'; // TABLA PARA PESOS ARGENTINOS
             break;
        case 'USD':
             $tabla = $wpdb->prefix . 'exch_usd_aed_exchanges'; // TABLA PARA PESOS ARGENTINOS
             break;
        default:
            $tabla  = $wpdb->prefix . 'eur_aed_exchanges'; // TABLA PARA EUROS
            break;
    }
    $date           = date('Y-m-d', strtotime($date));
    $fecha_buscada  = $date; // La fecha que estás buscando
    $conversion     = array();
    // Consulta para obtener el valor float asociado a la fecha
    $exchange = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT exchange_val FROM $tabla WHERE exc_date = %s",
            $fecha_buscada
        )
    );

    // Verificar el resultado
    if ($exchange !== null) {
        $conversion['exchange']    = $exchange;
        $conversion['aed_amount']  = $eur_amount * $exchange;
    } else {
        $conversion['exchange']    = 'No se ha registrado tasa de cambio para ese día.';
        $conversion['aed_amount']  = 'No se puede conseguir cantidad sin tasa de cambio.';
    }
    return $conversion;
}

// GUARDO AQUÍ LAS DESCRIPCIONES PARA LOS PRODUCTOS
function get_product_description( $product_id ) {

    switch ($product_id) {
        case 94564:
            $description = "Subscription to access the basic content of the 4fit platform for 24 weeks.";
            break;
        case 77642:
            $description = "Subscription to access the content of the 4fit platform as a customer for 24 weeks.";
            break;
        case 77647:
            $description = "Subscription to access the content of the 4fit platform for 1 year and manage your clients.";
            break;
        default:
            $description = "Subscription.";
    }
    return $description;
}
////////////////////////
// CARGA DE CAMBIOS  //
//////////////////////

// Función de configuración de página secundaria donde cargo los cambios
function fourfit_exchange_loader() {
    add_submenu_page(
        'export-billing',  // Slug de la página principal
        'Cargador de cambios AED - EUR',    // Título de la página
        'Cargador de cambios',    // Título del menú
        'manage_options',           // Capacidad requerida para acceder
        'exchange_loader',// Slug de la subpágina
        'exchange_loader_render' // Función de renderizado
    );
}

add_action('admin_menu', 'fourfit_exchange_loader');

// Función de renderizado de la página secundaria (subpágina)
function exchange_loader_render() {
    $mensaje = ''; // Inicializa el mensaje vacío

    ?>
    <div class="wrap">
        <h1>Cargador de cambios por fechas con documento .csv</h1>
        <?php echo $mensaje; // Mostrar el mensaje de notificación ?>
        <!-- Contenido de la subpágina con el formulario -->
        <div class="formulario-subida-csv">
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="process_csv">
                <label for="uplaoad_csv"><strong>Subir archivo CSV:</strong></label>
                <input type="file" name="uplaoad_csv" accept=".csv">
                <input type="submit" value="Subir CSV">
            </form>
        </div>
        
        <?php
            // Mostrar las fechas introducidas después de enviar el formulario
            $currencies = array(
                'eur' => 'Euros',
                'usd' => 'Dólares',
                'ars' => 'Pesos Argentinos',
                'mxn' => 'Pesos Mexicanos'
            );
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                fourfit_process_csv_file();
            }
            // Llamada a la función para mostrar el contenido de la tabla
            ?>
            <h2>Cambios cargados anteriormente</h2>
            <?php
            // Obtener la pestaña activa actual
                $tab = isset($_GET['tab']) ? $_GET['tab'] : 'eur';
            ?>
            <!-- Agregar las pestañas -->
            <h2 class="nav-tab-wrapper">
                <?php foreach($currencies as $alias => $name):?>
                <a href="?page=exchange_loader&tab=<?php echo $alias; ?>" class="nav-tab <?php echo $tab === $alias ? 'nav-tab-active' : ''; ?>"><?php echo $name; ?></a>
                
                <?php endforeach; ?>
            </h2>
            <?php
            fourfit_display_loaded_exchanges($tab, $currencies[$tab]);
        ?>
    </div>
    <?php
}

// ACCIÓN PARA PROCESAR EL CSV Y GUARDAR LOS CAMBIOS EN DDBB
function fourfit_process_csv_file() {
    // Verificar si se envió el formulario y si hay un archivo
    if (isset($_POST['action']) && $_POST['action'] === 'process_csv' && isset($_FILES['uplaoad_csv'])) {
        $archivo = $_FILES['uplaoad_csv'];

        // Verificar si el archivo es un archivo CSV
        $tipo_permitido = 'text/csv';
        if ($archivo['type'] === $tipo_permitido) {
            // Directorio de carga de archivos

            $upload_dir         = wp_upload_dir();
            $destination_dir    = $upload_dir['basedir'];
            $upload_path        = $destination_dir . '/billing/uploaded';
            // Crear directorio si no existe
            if (!file_exists($upload_path)) {
                mkdir($upload_path, 0755, true);
            }
            $file_name = $archivo['name'] . '-' . uniqid() . '.csv';
            $csv_path = $upload_path . '/' . $file_name;

            // Mover el archivo a la carpeta "uploads"
            move_uploaded_file($archivo['tmp_name'], $csv_path);

            // Procesar el archivo y guardar en la base de datos
            fourfit_get_and_set_exchanges_by_csv($csv_path);

            echo 'Archivo CSV subido y procesado correctamente.';
        } else {
            echo 'Por favor, sube un archivo CSV válido.';
        }
    
    }
}
// Manejar la acción de WordPress para guardar la información del CSV en la base de datos
add_action('admin_post_process_csv', 'fourfit_process_csv_file');
add_action('admin_post_nopriv_process_csv', 'fourfit_process_csv_file');

// GUARDAMOS LOS LOS DATOS EN LA BBDD PROCEDENTES DEL ARCHIVO CSV
function fourfit_get_and_set_exchanges_by_csv($csv_path) {

    // Leer el contenido del archivo CSV
    $contenido_csv = file_get_contents($csv_path);
    // Convertir el contenido CSV a un array
    $dates = explode("\n", $contenido_csv);

    

    global $wpdb;
    // Iterar sobre los datos y guardar en la base de datos
    foreach ($dates as $key => $date) {
        $date_tax = str_getcsv($date);
        if(isset($date_tax[0]) && isset($date_tax[1]) && isset($date_tax[2])) {
            $moneda = $date_tax[1];
            switch ($moneda) {
                case 'US Dollar':
                    $table = $wpdb->prefix . 'exch_usd_aed_exchanges'; // CONSULTA LA TABLA DE DÓLARES
                    break;
                case 'Argentine Peso':
                    $table = $wpdb->prefix . 'exch_ars_aed_exchanges'; // CONSULTA LA TABLA EN PESOS ARGENTINOS
                    break;
                case 'Mexican Peso':
                    $table = $wpdb->prefix . 'exch_mxn_aed_exchanges'; // CONSULTA LA TABLA EN PESOS MEXICANOS
                    break;
                case 'Euro':
                    $table = $wpdb->prefix . 'eur_aed_exchanges'; // CONSULTA LA TABLA EN EUROS
                    break;
                default:
                    continue 2; // SI NO ESTÁ CLARA LA MONEDA SALTAMOS AL SIGUIENTE
            }
            echo 'moneda - ' . $moneda . '<br>';
            if($date_tax[0] != 'Fecha') {
                
                $date = $date_tax[0];
                
                $exchange = $date_tax[2]; // Ajusta la obtención del valor según la estructura de los datos de la API
                echo 'Date - ' . $date . '<br>';
                echo 'Exchange - ' . $exchange . '<br>';
                // Insertar o actualizar el registro en la tabla de la base de datos
                
                // Verificar si ya existe un registro con la misma fecha
                $registro_existente = $wpdb->get_row("SELECT * FROM $table WHERE exc_date = '$date'", ARRAY_A);
                
                if ($registro_existente) {
                    // Actualizar el registro existente
                    $wpdb->update(
                        $table,
                        array('exchange_val' => $exchange),
                        array('exc_date' => $date),
                        array('%f'),
                        array('%s')
                    );
                } else {
                    // Insertar un nuevo registro
                    $wpdb->insert(
                        $table,
                        array(
                            'exc_date' => $date,
                            'exchange_val' => $exchange,
                            // Agrega más campos y valores según sea necesario
                        ),
                        array('%s', '%f') // Ajusta los formatos según los tipos de datos de tus columnas
                    );
                }
                echo var_export($date[0], true);
            }
        }
    }
    echo 'La ruta es correcta.';
    // PARA COMPROBAR LOS DATOS SUBIDOS
    // echo '<pre>' . var_export($dates, true) . '</pre>';

}

// FUNCIÓN PARA MOSTRAR LOS CAMBIOS GUARDADOS EN LAS PESTAÑAS DE CADA MONEDA
function fourfit_display_loaded_exchanges($currency = 'eur') {
    global $wpdb;

    switch ($currency) {
        case 'usd':
            $table = $wpdb->prefix . 'exch_usd_aed_exchanges'; // CONSULTA LA TABLA DE DÓLARES
            break;
        case 'ars':
            $table = $wpdb->prefix . 'exch_ars_aed_exchanges'; // CONSULTA LA TABLA EN PESOS ARGENTINOS
            break;
        case 'mxn':
            $table = $wpdb->prefix . 'exch_mxn_aed_exchanges'; // CONSULTA LA TABLA EN PESOS MEXICANOS
            break;
        default:
            $table = $wpdb->prefix . 'eur_aed_exchanges'; // CONSULTA LA TABLA EN EUROS
    }
    

    // Realizar la consulta a la base de datos
    $exchanges = $wpdb->get_results("SELECT * FROM $table ORDER BY exc_date DESC", ARRAY_A);

    // Organizar los resultados por mes y año
    $results_by_month = array();

    foreach ($exchanges as $exchange) {
        $date = new DateTime($exchange['exc_date']);
        $month_year = $date->format('F Y'); // Formato: mes y año

        // Almacena los resultados en un array organizado por mes y año
        $results_by_month[$month_year][] = $exchange;
    }
    echo '<h3>Cambio en: ' . $currency . '</h3>'; 
    // Muestra los resultados organizados por mes y año
    foreach ($results_by_month as $month_year => $exchanges) {
        echo '<h4>' . esc_html($month_year) . ' (' . count($exchanges) . ')</h4>';

        echo '<ul>';
        foreach ($exchanges as $exchange) {
            echo '<li><strong>' . esc_html($exchange['exc_date']) . ': </strong> ' . esc_html($exchange['exchange_val']) . '</li>';
            // Ajusta los campos según tu estructura de base de datos
        }
        echo '</ul>';
    }
}

///////////////////////////////////
// LIMPIEZA DE CSV DESCARGADOS  //
/////////////////////////////////

// Función de configuración de página para limpiar los csv descargados del banco central
function fourfit_csv_cleaner() {
    add_submenu_page(
        'export-billing',  // Slug de la página principal
        'Limpiador CSV',    // Título de la página
        'Limpiador CSV',    // Título del menú
        'manage_options',   // Capacidad requerida para acceder
        'csv_cleaner',  // Slug de la subpágina
        'csv_cleaner_render' // Función de renderizado
    );
}
add_action('admin_menu', 'fourfit_csv_cleaner');

// Función de renderizado de la página de limpieza de los CSV descargados
function csv_cleaner_render() {
    $mensaje = ''; // Inicializa el mensaje vacío

    ?>
    <div class="wrap">
        <h1>Limpiador de CSV para eliminar monedas no utilizadas</h1>
        <?php echo $mensaje; // Mostrar el mensaje de notificación ?>
        <!-- Contenido de la subpágina con el formulario -->
        <div class="formulario-subida-csv">
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="clean_csv">
                <p>

                    <label for="uplaoad_csv"><strong>Subir archivo CSV:</strong></label>
                    <input type="file" name="uplaoad_csv" accept=".csv">
                </p>
                <p>

                    <label for="currency"><strong>Selecciona la moneda de la que quieres obtener el achivo:</strong></label>
                    <select name="currency">
                        <option value="Euro">Euro</option>
                        <option value="US Dollar">US Dollar</option>
                        <option value="Argentine Peso">Argentine Peso</option>
                        <option value="Mexican Peso">Mexican Peso</option>
                    </select>
                </p>
                <p>
                    <input type="submit" value="Subir CSV">
                </p>
            </form>
        </div>
        
        <?php
            // Mostrar las fechas introducidas después de enviar el formulario
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                fourfit_clean_csv_file();
            }
            // Llamada a la función para mostrar el contenido de la tabla
        ?>
            
    </div>
    <?php
}

// ACCIÓN PARA GUARDAR EL CSV Y GUARDAR LOS CAMBIOS EN DDBB
function fourfit_clean_csv_file() {
    // Verificar si se envió el formulario y si hay un archivo
    if (isset($_POST['action']) && $_POST['action'] === 'clean_csv' && isset($_FILES['uplaoad_csv']) && isset($_POST['currency'])) {
        $archivo = $_FILES['uplaoad_csv'];
        $currency = $_POST['currency'];
        // Verificar si el archivo es un archivo CSV
        $tipo_permitido = 'text/csv';
        if ($archivo['type'] === $tipo_permitido) {
            // Directorio de carga de archivos

            $upload_dir         = wp_upload_dir();
            $destination_dir    = $upload_dir['basedir'];
            $upload_path        = $destination_dir . '/billing/uploaded';
            // Crear directorio si no existe
            if (!file_exists($upload_path)) {
                mkdir($upload_path, 0755, true);
            }
            $file_name = $archivo['name'] . '-' . uniqid() . '.csv';
            $csv_path = $upload_path . '/' . $file_name;

            // Mover el archivo a la carpeta "uploads"
            move_uploaded_file($archivo['tmp_name'], $csv_path);

            // Procesar el archivo y guardar en la base de datos
            fourfit_cleaner_csv($csv_path, $currency);

            echo 'Archivo CSV subido y procesado correctamente.';
        } else {
            echo 'Por favor, sube un archivo CSV válido.';
        }
    
    }
}

// Manejar la acción de WordPress para limpiar el CSV
add_action('admin_post_clean_csv', 'fourfit_clean_csv_file');
add_action('admin_post_nopriv_clean_csv', 'fourfit_clean_csv_file');

// GUARDAMOS LOS CAMBIOS MEDIANTE UN ARCHIVO CSV
function fourfit_cleaner_csv($csv_path, $currency = 'Euro') {
    // Leer el contenido del archivo CSV
    $contenido_csv = file_get_contents($csv_path);
    // Convertir el contenido CSV a un array
    $rows = explode("\n", $contenido_csv);

    $csv_data = array();
    $csv_data[] = array(
        'Fecha',
        'Cambio',
        'Moneda'
    );
    foreach ($rows as $key => $row) {
        $row_contents = str_getcsv($row);
        if(isset($row_contents[0]) && isset($row_contents[1]) && isset($row_contents[2])) {
            if($row_contents[1] == $currency) {
                $date = $row_contents[0];
                if (strpos($date, 'ene') !== false) {
                    $date = str_replace('ene', 'jan', $date);
                }
                if (strpos($date, 'abr') !== false) {
                    $date = str_replace('abr', 'apr', $date);
                }
                if (strpos($date, 'ago') !== false) {
                    $date = str_replace('ago', 'aug', $date);
                }
                if (strpos($date, 'dic') !== false) {
                    $date = str_replace('dic', 'dec', $date);
                }
                $fecha_timestamp    = DateTime::createFromFormat('d-M-Y', $date);
                // Formatear la fecha en el formato deseado
                $fecha_formateada   = ($fecha_timestamp !== false) ? $fecha_timestamp->format('Y-m-d') : 'fecha no valida';
                
                $cambio = str_replace(',','.',$row_contents[2]);
                $csv_data[] = array(
                    $fecha_formateada, //$fecha_formateada, // FECHA
                    $cambio, // CAMBIO
                    $row_contents[1] // MONEDA
                );
            }
        }
    }
    $now = date("Ymd-His");
    $file_name = $now . '-' . $currency . '-';
    $file_name .= 'clean.csv';

    $csv_file = fopen(WP_CONTENT_DIR . '/uploads/billing/clean-files/' . $file_name, 'w');
    foreach ($csv_data as $line) {
        fputcsv($csv_file, $line);
    }
    fclose($csv_file);
    
    echo '<p>CSV generado con éxito. <a href="' . WP_CONTENT_URL . '/uploads/billing/clean-files/' . $file_name . '">Descargar CSV</a></p>';
    echo '<pre>' . var_export($csv_data, true) . '</pre>';
}
// EJEMPLO DE FUNCIÓN PARA OBTENER CAMBIOS MEDIANTE API (NO SE USA)
function fourfit_get_and_set_exchanges_by_dateranges($start_date, $end_date) {
    if($start_date && $end_date) {

        // URL de la API
        $api_url = 'https://api.currencybeacon.com/v1/timeseries/?api_key=fen8TmmxQrTveQB4qXUZgb6SUDJOsAAJ&base=eur&start_date=' . $start_date . '&symbols=aed&end_date=' . $end_date;
        
        // Realizar la llamada a la API
        $api_request = wp_remote_get($api_url);
        
        // Verificar si la llamada a la API fue exitosa
        if (is_wp_error($api_request)) {
            echo 'Error al obtener datos de la API.';
            return;
        }
        
        // Decodificar los datos JSON obtenidos de la API
        $api_data = json_decode(wp_remote_retrieve_body($api_request), true);
        
        // Verificar si se obtuvieron datos válidos
        if (!$api_data || empty($api_data['response'])) {
            echo 'No se obtuvieron datos válidos de la API.';
            return;
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'eur_aed_exchanges'; 
        // Iterar sobre los datos y guardar en la base de datos
        foreach ($api_data['response'] as $date => $taxes) {
            
            $exchange = $taxes['AED']; // Ajusta la obtención del valor según la estructura de los datos de la API
            // Insertar o actualizar el registro en la tabla de la base de datos

            // Verificar si ya existe un registro con la misma fecha
            $registro_existente = $wpdb->get_row("SELECT * FROM $table WHERE exc_date = '$date'", ARRAY_A);

            if ($registro_existente) {
                // Actualizar el registro existente
                $wpdb->update(
                    $table,
                    array('exchange_val' => $exchange),
                    array('exc_date' => $date),
                    array('%f'),
                    array('%s')
                );
            } else {
                // Insertar un nuevo registro
                $wpdb->insert(
                    $table,
                    array(
                        'exc_date' => $date,
                        'exchange_val' => $exchange,
                        // Agrega más campos y valores según sea necesario
                    ),
                    array('%s', '%f') // Ajusta los formatos según los tipos de datos de tus columnas
                );
            }
        }
    }

    echo 'Datos obtenidos de la API y guardados en la base de datos correctamente.';
}


