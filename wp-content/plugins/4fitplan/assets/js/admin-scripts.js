jQuery(document).ready( function ($) {
    // AJAX URL
    var wp_url = WPURLS.siteurl;
    var ajax_url = wp_url + '/wp-admin/admin-ajax.php';

    $('.ff-change-date').on('click', function(e) {
        e.preventDefault();
        var $row = $(this).closest('.incident-row');
        update_suscription_row($row)
    });
    
    $('.ff-update-all').on('click', function(e) {
        e.preventDefault();
        $incident_row = $('.incident-row').not('.updated');
        var $first_50_incident_rows = $incident_row.slice(0, 50);

        $first_50_incident_rows.each(function() {
            $row = $(this);
            update_suscription_row($row)
        });
    });

    function update_suscription_row($row) { 
        var suscription_id = $row.data('id');
        var right_date      = $row.data('date');        
        $.ajax({
            type: 'POST',
            url: ajax_url,
            dataType: "qjson",
            data: {
                action: 'update_expiration_date',
                suscription: suscription_id,
                date: right_date
            }, 
            beforeSend: function (){
                $('.all-incidents').addClass('updating');
                $row.addClass('updating');
                
            }, complete: function(xhr) {
                
                var response    = $.parseJSON(xhr.responseText);
                var note        = response.note;
                if(response.check.lengh != 0 ) {

                    var details     = note + ' <a href="' +  response.check + '" target="_blank">Ver más</a>';
                } else {
                    var details     = note;

                }
                $row.html(details);
                $row.addClass('updated');
                $('.all-incidents').removeClass('updating');
                $row.removeClass('updating');
            }
        });
    }
});
