(function ($) {
    "use strict";

    if (!$.wjbAdminExtensions)
        $.wjbAdminExtensions = {};
    
    function WJBAdminMainCore() {
        var self = this;
        self.init();
    };

    WJBAdminMainCore.prototype = {
        /**
         *  Initialize
         */
        init: function() {
            var self = this;

            self.jobCustomFields();

            self.employerCustomFields();

            self.candidateCustomFields();
            
            self.taxInit();

            self.emailSettings();

            self.mixes();
        },
        jobCustomFields: function() {
            if ( $('#_job_cfield_field_type').length > 0 ) {
                $('#_job_cfield_field_type').change(function(){
                    var value = $(this).val();
                    if ( value === 'radio' || value === 'multicheck' || value === 'select' || value === 'pw_multiselect' ) {
                        $('#_job_cfield_options').css({
                            'display': 'block'
                        });
                    } else {
                        $('#_job_cfield_options').css({
                            'display': 'none'
                        });
                    }

                    if ( value === 'text' || value === 'select' || value === 'checkbox' || value === 'radio' || value === 'multicheck' || value === 'pw_multiselect' ) {
                        $('.cmb2-id--job-cfield-show-filter').css({
                            'display': 'block'
                        });
                    } else {
                        $('.cmb2-id--job-cfield-show-filter').css({
                            'display': 'none'
                        });
                    }
                });
                var value = $('#_job_cfield_field_type').val();
                if ( value === 'radio' || value === 'multicheck' || value === 'select' || value === 'pw_multiselect' ) {
                    $('#_job_cfield_options').css({
                        'display': 'block'
                    });
                } else {
                    $('#_job_cfield_options').css({
                        'display': 'none'
                    });
                }

                if ( value === 'text' || value === 'select' || value === 'checkbox' || value === 'radio' || value === 'multicheck' || value === 'pw_multiselect' ) {
                    $('.cmb2-id--job-cfield-show-filter').css({
                        'display': 'block'
                    });
                } else {
                    $('.cmb2-id--job-cfield-show-filter').css({
                        'display': 'none'
                    });
                }
            }
        },
        employerCustomFields: function() {
            if ( $('#_employer_cfield_field_type').length > 0 ) {
                $('#_employer_cfield_field_type').change(function(){
                    var value = $(this).val();
                    if ( value === 'radio' || value === 'multicheck' || value === 'select' || value === 'pw_multiselect' ) {
                        $('#_employer_cfield_options').css({
                            'display': 'block'
                        });
                    } else {
                        $('#_employer_cfield_options').css({
                            'display': 'none'
                        });
                    }

                    if ( value === 'text' || value === 'select' || value === 'checkbox' || value === 'radio' || value === 'multicheck' || value === 'pw_multiselect' ) {
                        $('.cmb2-id--employer-cfield-show-filter').css({
                            'display': 'block'
                        });
                    } else {
                        $('.cmb2-id--employer-cfield-show-filter').css({
                            'display': 'none'
                        });
                    }
                });
                var value = $('#_employer_cfield_field_type').val();
                if ( value === 'radio' || value === 'multicheck' || value === 'select' || value === 'pw_multiselect' ) {
                    $('#_employer_cfield_options').css({
                        'display': 'block'
                    });
                } else {
                    $('#_employer_cfield_options').css({
                        'display': 'none'
                    });
                }

                if ( value === 'text' || value === 'select' || value === 'checkbox' || value === 'radio' || value === 'multicheck' || value === 'pw_multiselect' ) {
                    $('.cmb2-id--employer-cfield-show-filter').css({
                        'display': 'block'
                    });
                } else {
                    $('.cmb2-id--employer-cfield-show-filter').css({
                        'display': 'none'
                    });
                }
            }
        },
        candidateCustomFields: function() {
            if ( $('#_candidate_cfield_field_type').length > 0 ) {
                $('#_candidate_cfield_field_type').change(function(){
                    var value = $(this).val();
                    if ( value === 'radio' || value === 'multicheck' || value === 'select' || value === 'pw_multiselect' ) {
                        $('#_candidate_cfield_options').css({
                            'display': 'block'
                        });
                    } else {
                        $('#_candidate_cfield_options').css({
                            'display': 'none'
                        });
                    }

                    if ( value === 'text' || value === 'select' || value === 'checkbox' || value === 'radio' || value === 'multicheck' || value === 'pw_multiselect' ) {
                        $('.cmb2-id--candidate-cfield-show-filter').css({
                            'display': 'block'
                        });
                    } else {
                        $('.cmb2-id--candidate-cfield-show-filter').css({
                            'display': 'none'
                        });
                    }
                });
                var value = $('#_candidate_cfield_field_type').val();
                if ( value === 'radio' || value === 'multicheck' || value === 'select' || value === 'pw_multiselect' ) {
                    $('#_candidate_cfield_options').css({
                        'display': 'block'
                    });
                } else {
                    $('#_candidate_cfield_options').css({
                        'display': 'none'
                    });
                }

                if ( value === 'text' || value === 'select' || value === 'checkbox' || value === 'radio' || value === 'multicheck' || value === 'pw_multiselect' ) {
                    $('.cmb2-id--candidate-cfield-show-filter').css({
                        'display': 'block'
                    });
                } else {
                    $('.cmb2-id--candidate-cfield-show-filter').css({
                        'display': 'none'
                    });
                }
            }
        },
        taxInit: function() {
            $('#_tax_color_input').wpColorPicker();
        },
        emailSettings: function() {
            var show_hiden_action = function(key, checked) {
                if ( checked ) {
                    $('.cmb2-id-' + key + '-subject').show();
                    $('.cmb2-id-' + key + '-content').show();
                } else {
                    $('.cmb2-id-' + key + '-subject').hide();
                    $('.cmb2-id-' + key + '-content').hide();
                }
            }
            $('#admin_notice_add_new_listing').on('change', function(){
                var key = 'admin-notice-add-new-listing';
                var checked = $(this).is(":checked");
                show_hiden_action(key, checked);
            });
            var checked = $('#admin_notice_add_new_listing').is(":checked");
            var key = 'admin-notice-add-new-listing';
            show_hiden_action(key, checked);

            // updated
            $('#admin_notice_updated_listing').on('change', function(){
                var key = 'admin-notice-updated-listing';
                var checked = $(this).is(":checked");
                show_hiden_action(key, checked);
            });
            var checked = $('#admin_notice_updated_listing').is(":checked");
            var key = 'admin-notice-updated-listing';
            show_hiden_action(key, checked);

            // admin expiring
            $('#admin_notice_expiring_listing').on('change', function(){
                var key = 'admin-notice-expiring-listing';
                var checked = $(this).is(":checked");
                show_hiden_action(key, checked);
            });
            var checked = $('#admin_notice_expiring_listing').is(":checked");
            var key = 'admin-notice-expiring-listing';
            show_hiden_action(key, checked);

            // employer expiring
            $('#employer_notice_expiring_listing').on('change', function(){
                var key = 'employer-notice-expiring-listing';
                var checked = $(this).is(":checked");
                show_hiden_action(key, checked);
            });
            var checked = $('#employer_notice_expiring_listing').is(":checked");
            var key = 'employer-notice-expiring-listing';
            show_hiden_action(key, checked);
        },
        mixes: function() {
            var map_service = $('.cmb2-id-map-service select').val();
            if ( map_service == 'mapbox' ) {
                $('.cmb2-id-google-map-api-keys').hide();
                $('.cmb2-id-google-map-style').hide();
                $('.cmb2-id-mapbox-token').show();
                $('.cmb2-id-mapbox-style').show();
            } else {
                $('.cmb2-id-google-map-api-keys').show();
                $('.cmb2-id-google-map-style').show();
                $('.cmb2-id-mapbox-token').hide();
                $('.cmb2-id-mapbox-style').hide();
            }

            $('.cmb2-id-map-service select').on('change', function() {
                var map_service = $(this).val();
                if ( map_service == 'mapbox' ) {
                    $('.cmb2-id-google-map-api-keys').hide();
                    $('.cmb2-id-google-map-style').hide();
                    $('.cmb2-id-mapbox-token').show();
                    $('.cmb2-id-mapbox-style').show();
                } else {
                    $('.cmb2-id-google-map-api-keys').show();
                    $('.cmb2-id-google-map-style').show();
                    $('.cmb2-id-mapbox-token').hide();
                    $('.cmb2-id-mapbox-style').hide();
                }
            });

            // free
            var free_apply = $('#candidate_free_job_apply').val();
            if ( free_apply == 'off' ) {
                $('.cmb2-id-candidate-package-page-id').css({'display': 'block'});
            } else {
                $('.cmb2-id-candidate-package-page-id').css({'display': 'none'});
            }
            $('#candidate_free_job_apply').on('change', function() {
                var free_apply = $(this).val();
                if ( free_apply == 'off' ) {
                    $('.cmb2-id-candidate-package-page-id').css({'display': 'block'});
                } else {
                    $('.cmb2-id-candidate-package-page-id').css({'display': 'none'});
                }
            });

            // restrict candidate
            var restrict_type = $('#candidate_restrict_type').val();
            if ( restrict_type == 'view' ) {
                $('.cmb2-id-candidate-restrict-detail').css({'display': 'block'});
                $('.cmb2-id-candidate-restrict-listing').css({'display': 'block'});
                $('.cmb2-id-candidate-restrict-contact-info').css({'display': 'none'});
            } else if ( restrict_type == 'view_contact_info' ) {
                $('.cmb2-id-candidate-restrict-detail').css({'display': 'none'});
                $('.cmb2-id-candidate-restrict-listing').css({'display': 'none'});
                $('.cmb2-id-candidate-restrict-contact-info').css({'display': 'block'});
            } else {
                $('.cmb2-id-candidate-restrict-detail').css({'display': 'none'});
                $('.cmb2-id-candidate-restrict-listing').css({'display': 'none'});
                $('.cmb2-id-candidate-restrict-contact-info').css({'display': 'none'});
            }
            $('#candidate_restrict_type').on('change', function() {
                var restrict_type = $(this).val();
                if ( restrict_type == 'view' ) {
                    $('.cmb2-id-candidate-restrict-detail').css({'display': 'block'});
                    $('.cmb2-id-candidate-restrict-listing').css({'display': 'block'});
                    $('.cmb2-id-candidate-restrict-contact-info').css({'display': 'none'});
                } else if ( restrict_type == 'view_contact_info' ) {
                    $('.cmb2-id-candidate-restrict-detail').css({'display': 'none'});
                    $('.cmb2-id-candidate-restrict-listing').css({'display': 'none'});
                    $('.cmb2-id-candidate-restrict-contact-info').css({'display': 'block'});
                } else {
                    $('.cmb2-id-candidate-restrict-detail').css({'display': 'none'});
                    $('.cmb2-id-candidate-restrict-listing').css({'display': 'none'});
                    $('.cmb2-id-candidate-restrict-contact-info').css({'display': 'none'});
                }
            });

            // restrict employer
            var restrict_type = $('#employer_restrict_type').val();
            if ( restrict_type == 'view' ) {
                $('.cmb2-id-employer-restrict-detail').css({'display': 'block'});
                $('.cmb2-id-employer-restrict-listing').css({'display': 'block'});
                $('.cmb2-id-employer-restrict-contact-info').css({'display': 'none'});
            } else if ( restrict_type == 'view_contact_info' ) {
                $('.cmb2-id-employer-restrict-detail').css({'display': 'none'});
                $('.cmb2-id-employer-restrict-listing').css({'display': 'none'});
                $('.cmb2-id-employer-restrict-contact-info').css({'display': 'block'});
            } else {
                $('.cmb2-id-employer-restrict-detail').css({'display': 'none'});
                $('.cmb2-id-employer-restrict-listing').css({'display': 'none'});
                $('.cmb2-id-employer-restrict-contact-info').css({'display': 'none'});
            }
            $('#employer_restrict_type').on('change', function() {
                var restrict_type = $(this).val();
                if ( restrict_type == 'view' ) {
                    $('.cmb2-id-employer-restrict-detail').css({'display': 'block'});
                    $('.cmb2-id-employer-restrict-listing').css({'display': 'block'});
                    $('.cmb2-id-employer-restrict-contact-info').css({'display': 'none'});
                } else if ( restrict_type == 'view_contact_info' ) {
                    $('.cmb2-id-employer-restrict-detail').css({'display': 'none'});
                    $('.cmb2-id-employer-restrict-listing').css({'display': 'none'});
                    $('.cmb2-id-employer-restrict-contact-info').css({'display': 'block'});
                } else {
                    $('.cmb2-id-employer-restrict-detail').css({'display': 'none'});
                    $('.cmb2-id-employer-restrict-listing').css({'display': 'none'});
                    $('.cmb2-id-employer-restrict-contact-info').css({'display': 'none'});
                }
            });

            //
            var apply_type = $('#_job_apply_type').val();
            if ( apply_type == 'internal' ) {
                $('.cmb2-id--job-apply-url').hide();
                $('.cmb2-id--job-apply-email').hide();
            } else if ( apply_type == 'external' ) {
                $('.cmb2-id--job-apply-url').show();
                $('.cmb2-id--job-apply-email').hide();
            } else if ( apply_type == 'with_email' ) {
                $('.cmb2-id--job-apply-url').hide();
                $('.cmb2-id--job-apply-email').show();
            }
            $('#_job_apply_type').change(function(){
                var apply_type = $('#_job_apply_type').val();
                if ( apply_type == 'internal' ) {
                    $('.cmb2-id--job-apply-url').hide();
                    $('.cmb2-id--job-apply-email').hide();
                } else if ( apply_type == 'external' ) {
                    $('.cmb2-id--job-apply-url').show();
                    $('.cmb2-id--job-apply-email').hide();
                } else if ( apply_type == 'with_email' ) {
                    $('.cmb2-id--job-apply-url').hide();
                    $('.cmb2-id--job-apply-email').show();
                }
            });
        }
    }

    $.wjbAdminMainCore = WJBAdminMainCore.prototype;
    
    $(document).ready(function() {
        // Initialize script
        new WJBAdminMainCore();
    });
    
})(jQuery);

