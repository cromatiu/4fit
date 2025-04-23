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


    $form = $('.note-form');
    $form.on('submit', function(e) {
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

    // Función para mostrar el paso actual
    function showStep(n) {
        $steps.hide();
        $($steps[n]).fadeIn(500);
    }
    
    // Mostrar el primer paso
    showStep(currentStep);

    // VALIDACIÓN DEL FORMULARIO
    function validateVisibleInputs($step) {
        var isValid = true;

        $step.find(':input:visible').each(function(){
            const tipo = $(this).attr('type');
            const esTexto = tipo === 'text' || tipo === 'email' || tipo === 'number' || tipo === 'tel' || this.tagName === 'TEXTAREA' || this.tagName === 'SELECT';

            if (esTexto && $.trim($(this).val()) === '') {
                isValid = false;
                $(this).css('border', '1px solid red');
            } else {
                $(this).css('border', '');
            }
        });
        return isValid;
    }

    function validateGroupedInputs($step) {
        let isValid = true;
    
        // Detectar si el usuario marcó "no" en restricciones
        const tieneRestricciones = $step.find('input[name="tiene_restricciones"]:checked').val();
    
        // Reunir todos los inputs radio/checkbox visibles
        const $groupedInputs = $step.find('input[type="radio"], input[type="checkbox"]');
        const groups = {};
    
        $groupedInputs.each(function () {
            const name = $(this).attr('name');
    
            // Omitimos validación de restricciones[] si el usuario marcó "no"
            if (name === 'restricciones[]' && tieneRestricciones === 'no') {
                return; // salta este grupo
            }
    
            if (!groups[name]) {
                groups[name] = true;
            }
        });
    
        // Validamos todos los grupos relevantes
        $.each(groups, function (name, _) {
            if ($step.find('input[name="' + name + '"]:checked').length === 0) {
                isValid = false;
    
                $step.find('input[name="' + name + '"]')
                    .closest('.custom-radio, .custom-checkbox')
                    .css('border', '1px solid red');
            } else {
                $step.find('input[name="' + name + '"]')
                    .closest('.custom-radio, .custom-checkbox')
                    .css('border', 'none');
            }
        });
    
        return isValid;
    }
    
  
    // Función unificada de validación para el paso actual
    function validateStep($step) {
        return validateVisibleInputs($step) && validateGroupedInputs($step);
    }
  
    // Acciones para botones "Siguiente"
    $('.next-btn').on('click', function(e){
        e.preventDefault();
        var $currentStep = $($steps[currentStep]);
        if (validateStep($currentStep)) {
            if (currentStep < $steps.length - 1) {
                currentStep++;
                showStep(currentStep);
            }
        } else {
            alert('Por favor, completa todos los campos obligatorios antes de continuar.');
        }
    });
    // VALIDACIÓN DEL FORMULARIO ANTES DE ENVIAR
    $('#multiStepForm').on('submit', function(e) {
        var $currentStep = $($steps[currentStep]);

        if (!validateStep($currentStep)) {
            e.preventDefault(); // Cancela el envío del formulario
            alert('Por favor, completa todos los campos obligatorios antes de continuar.');
        }
    });

    // Acciones para botones "Anterior"
    $('.prev-btn').on('click', function(e){
        e.preventDefault();
        if (currentStep > 0) {
            currentStep--;
            showStep(currentStep);
        }
    });
  
  // CONDICIONALES PARA RESTRICCIONES (DOBLE PASO)
  $('input[name="tiene_restricciones"]').on('change', function () {
    const seleccion = $(this).val();

    if (seleccion === 'yes') {
        $('#restricciones_container_prev').hide();
        $('#restricciones_container').fadeIn();
    } else {
        $('#restricciones_container_prev').fadeIn();
        $('#restricciones_container').hide();

        // Limpiar selección de los checkboxes
        $('#restricciones_container input[type="checkbox"]').prop('checked', false);

        // Quitar bordes de error si los hubo
        $('#restricciones_container input[type="checkbox"]').closest('.custom-checkbox').css('border', 'none');
    }
});


    // CARGADOR DE PLAN DE ALIMENTACIÓN Y CALENDARIO
    var $planContainer = $('#plan-container'),
        $picker        = $('#plan-day-picker');

    // Función para mostrar el spinner
    function showSpinner() {
        $planContainer.html(
        '<div class="spiner-container">'+
            '<div class="spinner" id="spinner"></div>'+
            '<p>Cargando tu plan de alimentación...</p>'+
        '</div>'
        );
    }

    // Función para cargar el plan vía AJAX
    function cargarPlan(dia) {
        showSpinner();
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
            console.error('Error al cargar plan AJAX');
        });
    }

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
});