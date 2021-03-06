(function( $ ) {

    $(window).load(function () {

        wp.customize.panel( 'mailtpl' ).focus();
        $('.mailtpl_range').on('input',function(){
            var val = $(this).val();
            $(this).parent().find('.font_value').html(val);
            $(this).val(val);
        });
        $('#mailtpl-send_mail').on('click', function(e){
            e.preventDefault();
            $('#mailtpl-spinner').fadeIn();
            $.ajax({
                url     : ajaxurl,
                data    : { action: 'mailtpl_send_email' }
            }).done(function(data) {
                $('#mailtpl-spinner').fadeOut();
                $('#mailtpl-success').fadeIn().delay(3000).fadeOut();
            });
        });
    });

})( jQuery );
