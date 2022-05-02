(function ($) {
    "use strict";

    function WJBAjaxFileUpload() {
        var self = this;
        self.init();
    };

    WJBAjaxFileUpload.prototype = {
        init: function() {
            var self = this;

            $('.wp-job-board-file-upload').each(function(){
                self.upload_file($(this));
            });
            $(document).on('click', '.cmb-add-group-row', function(e) {
                setTimeout(function(){
                    $('.wp-job-board-file-upload').each(function(){
                        self.upload_file($(this));
                    });
                }, 50);
                
            });
            $(document).on('click', '.wp-job-board-uploaded-files .wp-job-board-remove-uploaded-file', function(e) {
                e.preventDefault();
                $(this).closest('.wp-job-board-uploaded-file').remove();
            });
        },
        upload_file: function($element) {
            $element.fileupload({
                dataType: 'json',
                dropZone: $(this),
                url: wp_job_board_file_upload.ajax_url,
                maxNumberOfFiles: 1,
                formData: {
                    script: true,
                    action: 'wp_job_board_ajax_upload_file'
                },
                add: function (e, data) {
                    var $file_field     = $( this );
                    var $form           = $file_field.closest( 'form' );
                    var $uploaded_files = $file_field.parent().find('.wp-job-board-uploaded-files');
                    var uploadErrors    = [];

                    // Validate type
                    var allowed_types = $(this).data('file_types');

                    if ( allowed_types ) {
                        var acceptFileTypes = new RegExp( '(\.|\/)(' + allowed_types + ')$', 'i' );

                        if ( data.originalFiles[0].name.length && ! acceptFileTypes.test( data.originalFiles[0].name ) ) {
                            uploadErrors.push( wp_job_board_file_upload.i18n_invalid_file_type + ' ' + allowed_types );
                        }
                    }

                    if ( uploadErrors.length > 0 ) {
                        window.alert( uploadErrors.join( '\n' ) );
                    } else {
                        $form.find(':input[type="submit"]').attr( 'disabled', 'disabled' );
                        data.context = $('<progress value="" max="100"></progress>').appendTo( $uploaded_files );
                        data.submit();
                    }
                },
                progress: function (e, data) {
                    var progress        = parseInt(data.loaded / data.total * 100, 10);
                    data.context.val( progress );
                },
                fail: function (e, data) {
                    var $file_field     = $( this );
                    var $form           = $file_field.closest( 'form' );

                    if ( data.errorThrown ) {
                        window.alert( data.errorThrown );
                    }

                    data.context.remove();

                    $form.find(':input[type="submit"]').removeAttr( 'disabled' );
                },
                done: function (e, data) {
                    var $file_field     = $( this );
                    var $form           = $file_field.closest( 'form' );
                    var $uploaded_files = $file_field.parent().find('.wp-job-board-uploaded-files');
                    var multiple        = $file_field.attr( 'multiple' ) ? 1 : 0;
                    var image_types     = [ 'jpg', 'gif', 'png', 'jpeg', 'jpe' ];

                    $file_field.val("");

                    data.context.remove();

                    $.each(data.result.files, function(index, file) {
                        if ( file.error ) {
                            window.alert( file.error );
                        } else {
                            var html;
                            if ( $.inArray( file.extension, image_types ) >= 0 ) {
                                html = $.parseHTML( wp_job_board_file_upload.js_field_html_img );
                                $( html ).find('.wp-job-board-uploaded-file-preview img').attr( 'src', file.url );
                            } else {
                                html = $.parseHTML( wp_job_board_file_upload.js_field_html );
                                $( html ).find('.wp-job-board-uploaded-file-name code').text( file.name );
                            }

                            $( html ).find('.input-text').val( file.url );
                            $( html ).find('.input-text').attr( 'name', 'current_' + $file_field.attr( 'name' ) );

                            if ( multiple ) {
                                $uploaded_files.append( html );
                            } else {
                                $uploaded_files.html( html );
                            }
                        }
                    });

                    $form.find(':input[type="submit"]').removeAttr( 'disabled' );
                }
            });
        }
    }

    $.wjbAjaxFileUpload = WJBAjaxFileUpload.prototype;
    
    $(document).ready(function() {
        // Initialize script
        new WJBAjaxFileUpload();

    });

})(jQuery);

