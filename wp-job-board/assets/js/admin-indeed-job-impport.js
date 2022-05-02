(function ($) {
    "use strict";

    function WJBAdminIndeedJobCore() {
        var self = this;
        self.init();
    };

    WJBAdminIndeedJobCore.prototype = {
        /**
         *  Initialize
         */
        init: function() {
            $('input[name=submit-cmb-indeed-job-import]').on('click', function() {
                var form = $(this).closest('form');
                var $this = $(this);
                if (  form.hasClass('loading') ) {
                    return false;
                }
                form.find('.alert').remove();
                form.addClass('loading');
                $.ajax({
                    url: wp_job_board_indeed_opts.ajaxurl,
                    type:'POST',
                    dataType: 'json',
                    data:  form.serialize()+"&action=wp_job_board_ajax_indeed_job_import"
                }).done(function(data) {
                    form.removeClass('loading');
                    if ( data.status ) {
                        form.prepend( '<div class="alert alert-info">' + data.msg + '</div>' );
                    } else {
                        form.prepend( '<div class="alert alert-warning">' + data.msg + '</div>' );
                    }
                });
            });
        },
        
    }

    $.wjbAdminIndeedJobCore = WJBAdminIndeedJobCore.prototype;
    
    $(document).ready(function() {
        // Initialize script
        new WJBAdminIndeedJobCore();
    });
    
})(jQuery);

