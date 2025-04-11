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
      // Variables para el formulario multiparte
      var $steps = $('.form-step'),
      currentStep = 0;

      var $steps = $('.form-step'),
      currentStep = 0;

  // Función para mostrar el paso actual
  function showStep(n) {
      $steps.hide();
      $($steps[n]).fadeIn(500);
  }
  
  // Mostrar el primer paso
  showStep(currentStep);

  // Función unificada para validar radios y checkboxes requeridos
  function validateGroupedInputs($step) {
      var isValid = true;
      // Buscar todos los inputs de tipo radio o checkbox que tengan required (no se filtra por visible)
      var $groupedInputs = $step.find('input[type="radio"][required], input[type="checkbox"][required]');
      // Agruparlos por 'name'
      var groups = {};
      $groupedInputs.each(function(){
          var name = $(this).attr('name');
          groups[name] = true;
      });
      // Para cada grupo, verificar que al menos uno esté seleccionado
      $.each(groups, function(name, _){
          if ($step.find('input[name="' + name + '"]:checked').length === 0) {
              isValid = false;
              // Resaltar todos los inputs del grupo (los contenedores personalizados)
              $step.find('input[name="' + name + '"]').closest('.custom-radio, .custom-checkbox').css('border', '1px solid red');
          } else {
              $step.find('input[name="' + name + '"]').closest('.custom-radio, .custom-checkbox').css('border', 'none');
          }
      });
      return isValid;
  }
  
  // Función para validar inputs visibles (texto, select, etc.)
  function validateVisibleInputs($step) {
      var isValid = true;
      $step.find(':input[required]:visible').each(function(){
          if ($.trim($(this).val()) === "") {
              isValid = false;
              $(this).css('border', '1px solid red'); // Resalta el campo vacío
          } else {
              $(this).css('border', '');
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
  
  // Acciones para botones "Anterior"
  $('.prev-btn').on('click', function(e){
      e.preventDefault();
      if (currentStep > 0) {
          currentStep--;
          showStep(currentStep);
      }
  });
  
  // Toggle del contenedor de restricciones (para los botones personalizados)
  $('input[name="tiene_restricciones"]').on('change', function(){
      if ($(this).val() === 'yes'){
          $('#restricciones_container').slideDown();
      } else {
          $('#restricciones_container').slideUp();
      }
  });
});