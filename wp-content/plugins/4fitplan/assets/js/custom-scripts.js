jQuery(document).ready( function ($) {
    // AJAX URL
    var wp_url = WPURLS.siteurl;
    var ajax_url = wp_url + '/wp-admin/admin-ajax.php';

    var manage_notes   = '.manage-notes';
    var $open_form_link = $('.open-form-link');
    var open_form_link = $('.open-form-link');
    var open_form = $('.open-form');
    var form_content   = '.form-content';
    var note_content   = '.note-content';
    var modify = $open_form_link.data('modify');
    var empty = $open_form_link.data('empty');

    $open_form_link.on('click', function(e) {
        
        e.preventDefault();
        var $this_closest = $(this).closest( manage_notes );
      
        $this_closest.find( open_form ).slideUp();
        $this_closest.find( note_content ).slideUp();
        $this_closest.find( form_content ).slideDown();
    })


    $note_form = $('.note-form');
    $note_form.on('submit', function(e) {
        e.preventDefault();
        var form_data;

        var form_data = $( this ).serializeArray();
        var $this_closest = $( this ).closest( '.manage-notes' );

        
        $.ajax({
            type: 'POST',
            url: ajax_url,
            dataType: "qjson",
            data: form_data, 
            beforeSend: function (){
                
                $this_closest.addClass('ff-loading');
                
            }, complete: function(xhr) {

                var response    = $.parseJSON(xhr.responseText);
                var link_text;
                $this_closest.removeClass('ff-loading');
                $this_closest.find( open_form ).slideDown();
                $this_closest.find( note_content ).html(response.dist_note).slideDown();
                $this_closest.find( 'textarea' ).html(response.dist_note);
                $this_closest.find( form_content ).slideUp();
                if( response.dist_note == '' ) {
                    link_text = empty;
                } else {
                    link_text = modify;
                }
                $this_closest.find( open_form_link ).html( link_text );

            }
        });
    });

        

    // ACCIÓN PARA COPIAR LINK
    $(document).on('click', '.copy-link', function() {
        var field_id = $(this).closest('.copy-content').find('.copy-input').attr('id');
        let copyText = document.getElementById(field_id);
        let message = $(this).closest('.copy-content').find('.message');
        copyText.select();
        copyText.setSelectionRange(0, 99999); 
        navigator.clipboard.writeText(copyText.value);
        
        message.addClass('copied');
        
        setTimeout(function(){ message.removeClass('copied') }, 1000);
    });

    // PASOS DE FORMULARIO MULTIPART
    var $steps = $('.form-step'),
        currentStep = 0;

    function showStep(n) {
        $steps.hide();
        $($steps[n]).fadeIn(500);
        $($steps[n]).find('.verification').html(''); // Limpia mensaje al entrar
    }
    showStep(currentStep);

    function validateVisibleInputs($step) {
        var isValid = true;
        var firstErrorMessage = '';

        $step.find(':input:visible').each(function(){
            const $field = $(this);
            const tipo = $field.attr('type');
            const esTexto = tipo === 'text' || tipo === 'email' || tipo === 'number' || tipo === 'tel' || this.tagName === 'TEXTAREA' || this.tagName === 'SELECT';
            const valor = $.trim($field.val());
            const pattern = $field.attr('pattern');
            const inputmode = $field.attr('inputmode');

            let campoValido = true;

            if (esTexto) {
                if (valor === '') {
                    campoValido = false;
                }

                // Validar pattern si existe
                if (campoValido && pattern) {
                    const regex = new RegExp('^' + pattern + '$');
                    if (!regex.test(valor)) {
                        campoValido = false;
                    }
                }

                // Validar inputmode numérico
                if (campoValido && inputmode === 'numeric') {
                    if (!/^\d+$/.test(valor)) {
                        campoValido = false;
                    }
                }
            }

            if (!campoValido) {
                isValid = false;
                $field.css('border', '1px solid red');

                if (!firstErrorMessage) {
                    firstErrorMessage = $field.attr('title') || 'Por favor, completa correctamente este campo.';
                }
            } else {
                $field.css('border', '');
            }
        });

        if (!isValid) {
            $step.find('.verification').html(firstErrorMessage);
        }

        return isValid;
    }


    function validateGroupedInputs($step) {
        let isValid = true;
        let firstErrorMessage = '';

        const tieneRestricciones = $step.find('input[name="tiene_restricciones"]:checked').val();

        const $groupedInputs = $step.find('input[type="radio"], input[type="checkbox"]');
        const groups = {};

        $groupedInputs.each(function () {
            const name = $(this).attr('name');
            if (name === 'restricciones_list[]' && tieneRestricciones === 'no') {
                return;
            }
            if (!groups[name]) {
                groups[name] = true;
            }
        });

        $.each(groups, function (name, _) {
            if ($step.find('input[name="' + name + '"]:checked').length === 0) {
                isValid = false;

                $step.find('input[name="' + name + '"]')
                    .closest('.custom-radio, .custom-checkbox')
                    .css('border', '1px solid #990000');

                if (!firstErrorMessage) {
                    // Buscamos el title del primero
                    const title = $step.find('input[name="' + name + '"]').attr('title');
                    firstErrorMessage = title || 'Por favor, selecciona una opción.';
                }
            } else {
                $step.find('input[name="' + name + '"]')
                    .closest('.custom-radio, .custom-checkbox')
                    .css('border', 'none');
            }
        });

        if (!isValid) {
            $step.find('.verification').html(firstErrorMessage);
        }

        return isValid;
    }

    function validateStep($step) {
        return validateVisibleInputs($step) & validateGroupedInputs($step);
    }

    $('.next-btn').on('click', function(e){
        e.preventDefault();
        var $currentStep = $($steps[currentStep]);
        if (validateStep($currentStep)) {
            if (currentStep < $steps.length - 1) {
                currentStep++;
                showStep(currentStep);
            }
        }
    });

    $('#multiStepForm').on('submit', function(e) {
        var isValid = true;

        $steps.each(function(){
            var $step = $(this);
            if (!validateVisibleInputs($step) || !validateGroupedInputs($step)) {
                isValid = false;
                showStep($steps.index($step)); // saltar al paso con error
                return false; // break
            }
        });

        if (!isValid) {
            e.preventDefault();
        }
    });

    $('.prev-btn').on('click', function(e){
        e.preventDefault();
        if (currentStep > 0) {
            currentStep--;
            showStep(currentStep);
        }
    });

    function having_restrictions_behavior() {
        $('input[name="tiene_restricciones"]').on('change', function () {
            const seleccion = $(this).val();
            if (seleccion === 'yes') {
                $('#restricciones_container_prev').hide();
                $('#restricciones_container').fadeIn();
            } else {
                $('#restricciones_container_prev').fadeIn();
                $('#restricciones_container').hide();
                $('#restricciones_container input[type="checkbox"]').prop('checked', false)
                    .closest('.custom-checkbox').css('border', 'none');
            }
        });
    }
    having_restrictions_behavior();



    // FIN MULTIPART

    var container_ejercicio = $('#plan-ejercicio-semanal-container');
    if(container_ejercicio.length) {
    
    function loadWeeklyPlan() {
        showSpinner(container_ejercicio, 'Cargando tu plan de ejercicio...');
        $.get(ajax_url, {
            action: 'cargar_plan_ejercicio'
        })
        .done(function(html){
            container_ejercicio
            .html(html)
            .addClass('fade-in')
            .get(0)
            .scrollIntoView({ behavior: 'smooth' });
        })
        .fail(function(){
            container_ejercicio.html('<p>Error al cargar el plan semanal.</p>');
        });
    }
        // Carga inicial
        loadWeeklyPlan();
    }

    
    // CARGADOR DE PLAN DE ALIMENTACIÓN Y CALENDARIO
    var $planContainer = $('#plan-container'),
        $picker        = $('#plan-day-picker');


    if($planContainer.length !== 0) {

        // Función para cargar el plan vía AJAX
        function cargarPlan(dia) {
            showSpinner($planContainer, 'Cargando tu plan de alimentación...');
            $.get(ajax_url, {
                action: 'cargar_plan_usuario',
                dia: dia
            })
            .done(function(html){
                $planContainer
                .html(html)
                .addClass('fade-in')
                .get(0)
                .scrollIntoView({ behavior: 'smooth' });
            })
            .fail(function(){
                $planContainer.html('<p>Error al cargar el plan.</p>');
            });
        }
    }


    if ( $('#plan-selector').length !== 0 ) {
        // Inicializar Flatpickr
        flatpickr($picker.get(0), {
            inline:      true,
            altFormat:   'd-m-Y',
            dateFormat:  'Y-m-d',
            minDate:     'today',
            maxDate:     new Date().fp_incr(7),
            defaultDate: 'today',
            locale:      'es',
            onChange:    function(_, dateStr){
                var dia = new Date(dateStr).getDate();
                cargarPlan(dia);
            }
        });
            // Carga inicial para hoy
        var initialDate = $picker.val() || new Date().toISOString().slice(0,10);
        cargarPlan( new Date(initialDate).getDate() );
    }   

    var $recipesContainer = $('#recetas-container');

    function loadRecetas(tipo){
        showSpinner($recipesContainer, 'Cargando tus recetas...');
        $.get(ajax_url, {
            action: 'cargar_recetas',
            tipo_comida: tipo
        })
        .done(function(html){
            $recipesContainer.html(html);
        })
        .fail(function(){
            $recipesContainer.html('<p>Error al cargar las recetas.</p>');
        });
    }

   // Escuchamos el evento aunque los inputs se carguen con AJAX
    $(document).on('change', 'input[name="tipo_comida"]', function(){
        loadRecetas( $(this).val() );
    });

    // Carga inicial (todas)
    loadRecetas('');
     
    // Función para mostrar el spinner
    function showSpinner(container, message = 'Cargando...') {
        container.html(
        '<div class="spinner-container">'+
            '<div class="spinner" id="spinner"></div>'+
            '<p>' + message + '</p>'+
        '</div>'
        );
    }
    function showInlineSpinner(container, mensaje = 'Cargando...') {
        container.html(
            '<div class="inline-spinner-container">' +
                '<div class="spinner"></div>' +
                '<span>' + mensaje + '</span>' +
            '</div>'
        );
    }
    var container_motivational = $('#mensaje-motivacional-container');
    function loadMotivation() {
        showSpinner(container_motivational, 'Cargando tu consejo...')
        $.get(ajax_url, {
            action: 'cargar_mensaje_motivacional'
        })
        .done(function(html){
            container_motivational.html(html);
        })
        .fail(function(){
            container_motivational.html('<p>⚠️ Error al cargar el mensaje motivacional.</p>');
        });
    }

    loadMotivation();


    Chart.defaults.font.family = 'Montserrat';

jQuery(function($){
  var $form_metrics;
  var $chartsContainer;

  // Initialize or reinitialize selectors
  function initSelectors() {
    $form_metrics    = $('#fm-metrics-form');
    $chartsContainer = $('#fm-metrics-charts');
  }
  initSelectors();

  // Validate form fields
  function validateForm() {
    var steps = $form_metrics.find('[name="steps"]').val();
    var water = $form_metrics.find('[name="water"]').val();
    var training = $form_metrics.find('[name="training_time"]').val();
    var diet = $form_metrics.find('[name="diet_rating"]:checked').val();
    var errors = [];

    // Steps: max 5 digits
    if (!/^[0-9]{1,5}$/.test(steps)) {
      errors.push('Pasos diarios debe tener máximo 5 dígitos.');
    }
    // Water: 3 or 4 digits
    if (!/^[0-9]{3,4}$/.test(water)) {
      errors.push('Agua bebida debe tener entre 3 y 4 dígitos.');
    }
    // Training: max 3 digits
    if (!/^[0-9]{1,3}$/.test(training)) {
      errors.push('Tiempo de entrenamiento debe tener máximo 3 dígitos.');
    }
    // Diet: radio required
    if (typeof diet === 'undefined') {
      errors.push('Debes seleccionar un valor para seguimiento de dieta (1-5).');
    }

    if (errors.length) {
      return false;
    }

    return true;
  }

  // Delegate form submit to handle dynamic replacement
  $(document).on('submit', '#fm-metrics-form', function(e) {
    e.preventDefault();
    initSelectors();
    if (!validateForm()) return;
    var $form = $form_metrics.addClass('ff-loading');
    var payload = {
      action:        'fm_save_metrics',
      security:      FMmetrics.nonce,
      date:          $form.find('[name="date"]').val(),
      steps:         $form.find('[name="steps"]').val(),
      water:         $form.find('[name="water"]').val(),
      training_time: $form.find('[name="training_time"]').val(),
      diet_rating:   $form.find('[name="diet_rating"]:checked').val()
    };

    $.post(FMmetrics.ajax_url, payload, null, 'json')
      .always(function(){ $form.removeClass('ff-loading'); })
      .done(function(res){
        if (res.success) {
          $form.replaceWith('<div id="fm-metrics-charts"></div>');
          initSelectors();
          fetchHistory();
        } else {
          $('#fm-metrics-message').text('Error al guardar datos.').show();
        }
      })
      .fail(function(){
        $('#fm-metrics-message').text('Error al guardar datos.').show();
      });
  });

  // Render charts into container
  function renderCharts(history) {
    initSelectors();
    if($chartsContainer.length  ) {

        $chartsContainer.addClass('ff-loading').empty();
        
        var charts = [
            { id: 'chart-steps',    title: 'Pasos diarios',         type: 'line', dataKey: 'steps',         options: { scales: { y: { beginAtZero: true } } } },
            { id: 'chart-water',    title: 'Agua bebida (ml)',      type: 'line', dataKey: 'water_ml',      options: { scales: { y: { beginAtZero: true } } } },
            { id: 'chart-training', title: 'Tiempo de entrenamiento',type: 'bar',  dataKey: 'training_time', options: { scales: { y: { beginAtZero: true } } } },
            { id: 'chart-diet',     title: 'Seguimiento de dieta',   type: 'line', dataKey: 'diet_rating',   options: { scales: { y: { beginAtZero: true, min:1, max:5, ticks:{stepSize:1} } } } }
        ];
        
        charts.forEach(function(cfg) {
            $chartsContainer.append(
                '<div style="margin-bottom:40px;"><h3>' + cfg.title + '</h3><canvas id="' + cfg.id + '"></canvas></div>'
            );
            //var labels = history.map(item => item.met_date);
            var labels = history.map(item => {
                var parts = item.met_date.split('-'); // ["2025", "07", "04"]
                return `${parts[2]}-${parts[1]}-${parts[0]}`; // "04-07-2025"
            });
            var data   = history.map(item => item[cfg.dataKey]);
            var ctx    = document.getElementById(cfg.id).getContext('2d');
            new Chart(ctx, {
                type: cfg.type,
                data: { labels: labels, datasets: [{ label: cfg.title, data: data, borderColor: '#82C3BF', backgroundColor: '#82C3BF', fill: false, tension: 0, borderWidth: 2, pointRadius: 3 }] },
                options: Object.assign({ responsive: true }, cfg.options)
            });
        });
        
        $chartsContainer.removeClass('ff-loading');
    }
  }

  // Fetch history via AJAX
  function fetchHistory() {
    initSelectors();
    var userId = $chartsContainer.data('user');
    var payload = { action: 'fm_get_history', security: FMmetrics.nonce };
    if (userId) payload.user_id = userId;

    $chartsContainer.addClass('ff-loading');
    $.post(FMmetrics.ajax_url, payload, function(res) {
      if (res.success) renderCharts(res.data.history);
    }, 'json')
    .fail(function(){ console.error('Error al cargar historial.'); })
    .always(function(){ $chartsContainer.removeClass('ff-loading'); });
  }

  // On load, decide to fetch charts or wait for form
  if (!$('#fm-metrics-form').length) {
    fetchHistory();
  }
});

// FORMULARIO FACTURACIÓN AFILIADOS
const form = $('.wpam-profile-form');

if (form.length) {
    const requiredFields = form.find('[required]');
    const statesByCountry = window.wpamStatesByCountry || {};
    const stateSelect = $('#billing_state');
    const countrySelect = $('#billing_country');
    let currentState = stateSelect.data('current');

    // Inicializar Select2
    countrySelect.select2({
        placeholder: 'Selecciona un país',
        allowClear: true
    });
    stateSelect.select2({
        placeholder: 'Selecciona un estado/provincia',
        allowClear: true
    });

    function populateStates(countryCode) {
        stateSelect.empty();
        const states = statesByCountry[countryCode] || {};
        const keys = Object.keys(states);

        if (keys.length === 0) {
            stateSelect.append('<option value="">-- Sin provincias --</option>');
        } else {
            keys.forEach(function (code) {
                const selected = code === currentState ? 'selected' : '';
                stateSelect.append(`<option value="${code}" ${selected}>${states[code]}</option>`);
            });
        }

        // Actualizar Select2 con nuevas opciones
        stateSelect.trigger('change');
    }

    populateStates(countrySelect.val());

    countrySelect.on('change', function () {
        currentState = ''; // Resetear estado actual
        populateStates($(this).val());
    });
    /*
    form.on('submit', function (e) {
        let valid = true;
        requiredFields.each(function () {
            const field = $(this);
            if (!field.val().trim()) {
                alert(`El campo "${field.attr('name').replace('billing_', '').replace('_', ' ')}" es obligatorio.`);
                field.focus();
                valid = false;
                e.preventDefault();
                return false;
            }
        });
        return valid;
    });
    */
}


// SPINNER PARA PRECARGA EN DE CAMPO EN LÍNEA
function showFieldSpinner($li) {
    $li.css('position', 'relative');
    $li.append(`
        <div class="field-spinner-overlay">
            <div class="spinner tiny-spinner"></div>
        </div>
    `);
}

$(document).on('click', '.uf-edit-btn', function (e) {
    e.preventDefault();

    const $button = $(this);
    const slug = $button.data('slug');
    const userId = $button.data('user-id');
    const $li = $button.closest('li');
    const $container = $li.find('.uf-field-form');
    const $fieldValue = $li.find('.uf-field-value');

    $fieldValue.hide();
    $button.hide();

    showFieldSpinner($li); // spinner overlay para edición

    $.get(ajax_url, {
        action: 'uf_render_edit_field',
        field: slug,
        user_id: userId
    })
    .done(function (html) {
        $li.find('.field-spinner-overlay').remove();
        $container.hide().html(html).slideDown();
        having_restrictions_behavior();
    })
    .fail(function () {
        $li.find('.field-spinner-overlay').remove();
        alert('Error al cargar el formulario de edición.');
        $fieldValue.show();
        $button.show();
    });
});




$(document).on('submit', '.uf-inline-form', function (e) {
    e.preventDefault();

    const $form = $(this);
    const formData = new FormData(this);
    const $li = $form.closest('li');
    const $fieldValue = $li.find('.uf-field-value');
    const $editButton = $li.find('.uf-edit-btn');

    showFieldSpinner($li);

    $.ajax({
        url: ajax_url,
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false
    })
    .done(function (response) {
        $li.find('.field-spinner-overlay').remove();

        if (response.success) {
            const newValue = response.data.label || response.data.slug || '✓ Guardado';
            $fieldValue.text(newValue).show();
            $form.slideUp();
            $editButton.show();
        } else {
            alert(response.data?.message || 'Error al guardar');
        }
    })
    .fail(function () {
        $li.find('.field-spinner-overlay').remove();
        alert('Error inesperado al guardar el campo.');
    });
});



    document.querySelectorAll('form.js-validate-form').forEach(function(form) {
        // Validación al enviar
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            let isValid = validateForm(form);
            if (isValid) {
                form.submit();
            }
        });

        // Validación instantánea mientras escribe o cambia
        form.querySelectorAll('input, select, textarea').forEach(function(field) {
            if (field.type === 'radio' || field.type === 'checkbox') {
                field.addEventListener('change', () => validateSingleField(form, field));
            } else {
                field.addEventListener('input', () => validateSingleField(form, field));
            }
        });
    });

    function validateForm(form) {
        let valid = true;
        let processedNames = new Set();

        form.querySelectorAll('.input-error').forEach(el => el.classList.remove('input-error'));
        form.querySelectorAll('.error-message').forEach(el => el.textContent = '');

        form.querySelectorAll('input, select, textarea').forEach(function(field) {
            if (field.disabled || field.type === 'hidden') return;

            if ((field.type === 'radio' || field.type === 'checkbox') && field.name) {
                if (processedNames.has(field.name)) return;
                processedNames.add(field.name);
                if (!validateGroupField(form, field)) valid = false;
            } else {
                if (!validateSingleField(form, field)) valid = false;
            }
        });

        return valid;
    }

    function validateSingleField(form, field) {
        let value = field.value.trim();
        let container = field.closest('.form-group, .input-group') || field.parentNode;
        let errorDiv = container.querySelector('.error-message');
        let titleMsg = field.getAttribute('title') || 'Campo inválido.';

        clearFieldError(field, errorDiv);

        // Required
        if (field.hasAttribute('required') && value === '') {
            showError(field, errorDiv, titleMsg);
            return false;
        }

        // Type email
        if (field.type === 'email' && value !== '') {
            let emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                showError(field, errorDiv, titleMsg);
                return false;
            }
        }
        if (field.type === 'tel' && value !== '') {
            let telRegex = /^[0-9]{9}$/; // o el pattern que tú quieras
            if (!telRegex.test(value)) {
                showError(field, errorDiv, titleMsg);
                return false;
            }
        }
        // Minlength
        if (field.hasAttribute('minlength')) {
            let min = parseInt(field.getAttribute('minlength'), 10);
            if (value.length < min) {
                showError(field, errorDiv, titleMsg);
                return false;
            }
        }

        // Maxlength
        if (field.hasAttribute('maxlength')) {
            let max = parseInt(field.getAttribute('maxlength'), 10);
            if (value.length > max) {
                showError(field, errorDiv, titleMsg);
                return false;
            }
        }

        // Pattern
        if (field.hasAttribute('pattern') && value !== '') {
            let pattern = new RegExp('^' + field.getAttribute('pattern') + '$');
            if (!pattern.test(value)) {
                showError(field, errorDiv, titleMsg);
                return false;
            }
        }

        return true;
    }

    function validateGroupField(form, field) {
        let group = form.querySelectorAll(`input[name="${field.name}"]`);
        let container = field.closest('.form-group, .input-group') || field.parentNode;
        let errorDiv = container.querySelector('.error-message');
        let titleMsg = field.getAttribute('title') || 'Debes seleccionar una opción.';

        clearFieldError(field, errorDiv);

        let isChecked = Array.from(group).some(el => el.checked);
        if (!isChecked && field.required) {
            showError(field, errorDiv, titleMsg);
            return false;
        }
        return true;
    }

    function showError(field, errorDiv, message) {
        field.classList.add('input-error');
        if (errorDiv) {
            errorDiv.textContent = message;
        }
    }

    function clearFieldError(field, errorDiv) {
        field.classList.remove('input-error');
        if (errorDiv) errorDiv.textContent = '';
    }




      
});