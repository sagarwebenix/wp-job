(function ($) {
    "use strict";

    if (!$.wjbExtensions)
        $.wjbExtensions = {};
    
    function WJBMainCore() {
        var self = this;
        self.init();
    };

    WJBMainCore.prototype = {
        /**
         *  Initialize
         */
        init: function() {
            var self = this;

            self.fileUpload();

            self.userLoginRegister();

            self.userChangePass();
            
            self.removeJob();

            self.fillJob();

            self.applyEmail();
            
            self.recaptchaCallback();

            self.applyInternal();

            self.applicantsInit();

            self.addJobShortlist();

            self.removeJobShortlist();

            self.addCandidateShortlist();

            self.removeCandidateShortlist();

            self.reviewInit();

            self.jobAlert();

            self.candidateAlert();

            self.jobSocialApply();

            self.select2Init();

            self.jobSubmission();

            self.filterListing();

            self.followEmployer();

            self.blockEmployer();

            self.employerAddEmployee();
            
            self.mixesFn();

            self.loadExtension();
        },
        loadExtension: function() {
            var self = this;
            
            // if ($.wjbExtensions.ajax_upload) {
            //     $.wjbExtensions.ajax_upload.call(self);
            // }
        },
        fileUpload: function(){
            
            var isAdvancedUpload = function() {
                var div = document.createElement('div');
                return (('draggable' in div) || ('ondragstart' in div && 'ondrop' in div)) && 'FormData' in window && 'FileReader' in window;
            }();

            if (isAdvancedUpload) {

                var droppedFiles = false;
                $('.label-can-drag').each(function(){
                    var label_self = $(this);
                    label_self.on('drag dragstart dragend dragover dragenter dragleave drop', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                    }).on('dragover dragenter', function() {
                        label_self.addClass('is-dragover');
                    }).on('dragleave dragend drop', function() {
                        label_self.removeClass('is-dragover');
                    }).on('drop', function(e) {
                        droppedFiles = e.originalEvent.dataTransfer.files;
                        label_self.parent().find('input[type="file"]').prop('files', droppedFiles).trigger('change');
                    });
                });
            }
            $(document).on('click', '.label-can-drag', function(){
                $(this).parent().find('input[type="file"]').trigger('click');
            });
        },
        userLoginRegister: function() {
            var self = this;
            
            // sign in proccess
            $('body').on('submit', 'form.login-form', function(){
                var $this = $(this);
                $('.alert', this).remove();
                $this.addClass('loading');
                $.ajax({
                    url: wp_job_board_opts.ajaxurl,
                    type:'POST',
                    dataType: 'json',
                    data:  $(this).serialize()+"&action=wp_job_board_ajax_login"
                }).done(function(data) {
                    $this.removeClass('loading');
                    if ( data.status ) {
                        $this.prepend( '<div class="alert alert-info">' + data.msg + '</div>' );
                        setTimeout(function(){
                            window.location.href = wp_job_board_opts.after_login_page_url;
                        }, 500);
                    } else {
                        $this.prepend( '<div class="alert alert-warning">' + data.msg + '</div>' );
                    }
                });
                return false; 
            } );
            $('body').on('click', '.back-link', function(e){
                e.preventDefault();
                $('.form-container').hide();
                $($(this).attr('href')).show(); 
                return false;
            } );

             // lost password in proccess
            $('body').on('submit', 'form.forgotpassword-form', function(){
                var $this= $(this);
                $('.alert', this).remove();
                $this.addClass('loading');
                $.ajax({
                  url: wp_job_board_opts.ajaxurl,
                  type:'POST',
                  dataType: 'json',
                  data:  $(this).serialize()+"&action=wp_job_board_ajax_forgotpass"
                }).done(function(data) {
                     $this.removeClass('loading');
                    if ( data.status ) {
                        $this.prepend( '<div class="alert alert-info">'+data.msg+'</div>' );
                        setTimeout(function(){
                            window.location.reload(true);
                        }, 500);
                    } else {
                        $this.prepend( '<div class="alert alert-warning">'+data.msg+'</div>' );
                    }
                });
                return false; 
            } );
            $('body').on('click', '#forgot-password-form-wrapper form .btn-cancel', function(e){
                e.preventDefault();
                $('#forgot-password-form-wrapper').hide();
                $('#login-form-wrapper').show();
            } );

            // register
            $('body').on('submit', 'form.register-form', function(){
                var $this = $(this);
                $('.alert', this).remove();
                $this.addClass('loading');
                $.ajax({
                    url: wp_job_board_opts.ajaxurl,
                    type:'POST',
                    dataType: 'json',
                    data:  $(this).serialize()+"&action=wp_job_board_ajax_register"
                }).done(function(data) {
                    $this.removeClass('loading');
                    if ( data.status ) {
                        $this.prepend( '<div class="alert alert-info">'+data.msg+'</div>' );
                        if ( data.redirect ) {
                            setTimeout(function(){
                                window.location.href = wp_job_board_opts.after_login_page_url;
                            }, 500);
                        }
                    } else {
                        $this.prepend( '<div class="alert alert-warning">'+data.msg+'</div>' );
                        grecaptcha.reset();
                    }
                });
                return false;
            } );

            if ( $('form.register-form').length > 0 ) {
                $('.role-tabs input[type=radio]').on('change', function() {
                    var val = $(this).val();
                    var container = $(this).closest('.register-form');
                    if ( val == 'wp_job_board_candidate' ) {
                        container.find('.wp_job_board_candidate_show').show();
                        container.find('.wp_job_board_employer_show').hide();
                    } else {
                        container.find('.wp_job_board_candidate_show').hide();
                        container.find('.wp_job_board_employer_show').show();
                    }
                });
                var val = $('.role-tabs input[type=radio]:checked').val();
                var container = $('form.register-form').closest('.register-form');
                if ( val == 'wp_job_board_candidate' ) {
                    container.find('.wp_job_board_candidate_show').show();
                    container.find('.wp_job_board_employer_show').hide();
                } else {
                    container.find('.wp_job_board_candidate_show').hide();
                    container.find('.wp_job_board_employer_show').show();
                }
            }
            
            // wp-job-board-resend-approve-account-btn
            $(document).on('click', '.wp-job-board-resend-approve-account-btn', function(e) {
                e.preventDefault();
                var $this = $(this),
                    $container = $(this).parent();
                $this.addClass('loading');
                $.ajax({
                    url: wp_job_board_opts.ajaxurl,
                    type:'POST',
                    dataType: 'json',
                    data: {
                        action: 'wp_job_board_ajax_resend_approve_account',
                        login: $this.data('login'),
                    }
                }).done(function(data) {
                    $this.removeClass('loading');
                    if ( data.status ) {
                        $container.html( data.msg );
                    } else {
                        $container.html( data.msg );
                    }
                });
            });
        },
        userChangePass: function() {
            var self = this;
            $('body').on('submit', 'form.change-password-form', function(){
                var $this = $(this);
                $('.alert', this).remove();
                $this.addClass('loading');
                $.ajax({
                    url: wp_job_board_opts.ajaxurl,
                    type:'POST',
                    dataType: 'json',
                    data:  $(this).serialize()+"&action=wp_job_board_ajax_change_password"
                }).done(function(data) {
                    $this.removeClass('loading');
                    if ( data.status ) {
                        $this.prepend( '<div class="alert alert-info">' + data.msg + '</div>' );
                        setTimeout(function(){
                            window.location.href = wp_job_board_opts.login_register_url;
                        }, 500);
                    } else {
                        $this.prepend( '<div class="alert alert-warning">' + data.msg + '</div>' );
                    }
                });
                return false; 
            } );
        },
        removeJob: function() {
            var self = this;
            $('.job-button-delete').on('click', function() {
                var $this = $(this);
                $this.addClass('loading');
                var job_id = $(this).data('job_id');
                var nonce = $(this).data('nonce');
                $.ajax({
                    url: wp_job_board_opts.ajaxurl,
                    type:'POST',
                    dataType: 'json',
                    data: {
                        'job_id': job_id,
                        'nonce': nonce,
                        'action': 'wp_job_board_ajax_remove_job',
                    }
                }).done(function(data) {
                    $this.removeClass('loading');
                    if ( data.status ) {
                        $this.closest('tr').remove();
                    }
                    self.showMessage(data.msg, data.status);
                });
            });
        },
        fillJob: function() {
            var self = this;
            $(document).on('click', '.btn-action-icon.mark_filled', function() {
                var $this = $(this);
                $this.addClass('loading');
                var job_id = $this.data('job_id');
                var nonce = $this.data('nonce');
                $.ajax({
                    url: wp_job_board_opts.ajaxurl,
                    type:'POST',
                    dataType: 'json',
                    data: {
                        'job_id': job_id,
                        'nonce': nonce,
                        'action': 'wp_job_board_ajax_mark_filled_job',
                    }
                }).done(function(data) {
                    $this.removeClass('loading');
                    if ( data.status ) {
                        $this.attr('title', data.title);
                        $this.data('nonce', data.nonce);
                        $this.removeClass('mark_filled').addClass('mark_not_filled');
                        $this.find('i').attr('class', data.icon_class);

                        $this.closest('tr').find('.job-title-wrapper .application-status-label').remove();
                        if ( data.label ) {
                            $this.closest('tr').find('.job-title-wrapper').append(data.label);
                        }
                    }
                    self.showMessage(data.msg, data.status);
                });
            });
            $(document).on('click', '.btn-action-icon.mark_not_filled', function() {
                var $this = $(this);
                $this.addClass('loading');
                var job_id = $this.data('job_id');
                var nonce = $this.data('nonce');
                $.ajax({
                    url: wp_job_board_opts.ajaxurl,
                    type:'POST',
                    dataType: 'json',
                    data: {
                        'job_id': job_id,
                        'nonce': nonce,
                        'action': 'wp_job_board_ajax_mark_not_filled_job',
                    }
                }).done(function(data) {
                    $this.removeClass('loading');
                    if ( data.status ) {
                        $this.attr('title', data.title);
                        $this.data('nonce', data.nonce);
                        $this.removeClass('mark_not_filled').addClass('mark_filled');
                        $this.find('i').attr('class', data.icon_class);

                        $this.closest('tr').find('.job-title-wrapper .application-status-label').remove();
                        if ( data.label ) {
                            $this.closest('tr').find('.job-title-wrapper').append(data.label);
                        }
                    }
                    self.showMessage(data.msg, data.status);
                });
            });
        },
        recaptchaCallback: function() {
            if (!window.grecaptcha) {
            } else {
                setTimeout(function(){
                    var recaptchas = document.getElementsByClassName("ga-recaptcha");
                    for(var i=0; i<recaptchas.length; i++) {
                        var recaptcha = recaptchas[i];
                        var sitekey = recaptcha.dataset.sitekey;

                        grecaptcha.render(recaptcha, {
                            'sitekey' : sitekey
                        });
                    }
                }, 500);
            }
        },
        applyEmail: function() {
            var self = this;

            $('.btn-apply-job-email').magnificPopup({
                mainClass: 'wp-job-board-mfp-container popup-apply-job-email',
                type:'inline',
                midClick: true
            });

            $(document).on('submit', 'form.job-apply-email-form', function(){
                var $this = $(this);

                if ( $this.hasClass('loading') ) {
                    return false;
                }

                var form_id = $(this).attr('id');
                var error = false;

                var cv_file = $this.find('input[name="cv_file"]');
                if (cv_file.length > 0 && cv_file.val() != '') {
                    cv_file = cv_file.prop('files')[0];
                    var file_size = cv_file.size;
                    var file_type = cv_file.type;

                    var allowed_types = wp_job_board_opts.cv_file_types;
                    var filesize_allow = wp_job_board_opts.cv_file_size_allow;
                    filesize_allow = parseInt(filesize_allow);

                    if (file_size > filesize_allow) {
                        alert(wp_job_board_opts.cv_file_size_error);
                        error = true;
                    }
                    if (allowed_types.indexOf(file_type) < 0) {
                        alert(wp_job_board_opts.cv_file_types_error);
                        error = true;
                    }
                }

                if ( error == false ) {
                    $this.find('.alert').remove();

                    $this.addClass('loading');
                    var form_data = new FormData( $('#' + form_id)[0] );

                    $.ajax({
                        url: wp_job_board_opts.ajaxurl,
                        type:'POST',
                        dataType: 'json',
                        data: form_data,
                        processData: false,
                        contentType: false,
                    }).done(function(data) {
                        $this.removeClass('loading');
                        
                        if ( data.status ) {
                            $this.prepend( '<div class="alert alert-info">'+data.msg+'</div>' );
                        } else {
                            $this.prepend( '<div class="alert alert-warning">'+data.msg+'</div>' );
                        }
                    });
                }
                return false;
            });
        },
        applyInternal: function() {
            var self = this;
            $(document).on('click', '.btn-apply-job-internal-required', function() {
                var msg = $(this).parent().find('.job-apply-internal-required-wrapper .msg-inner').text();
                self.showMessage(msg, false);
            });
            $(document).on('click', '.btn-apply-job-internal', function() {
                var $this = $(this);
                $this.parent().find('.alert').remove();
                $this.addClass('loading');
                var job_id = $(this).data('job_id');
                var nonce = $(this).data('nonce');
                $.ajax({
                    url: wp_job_board_opts.ajaxurl,
                    type:'POST',
                    dataType: 'json',
                    data: {
                        'job_id': job_id,
                        'nonce': nonce,
                        'action': 'wp_job_board_ajax_apply_internal',
                    }
                }).done(function(data) {
                    $this.removeClass('loading');
                    if ( data.status ) {
                        $this.text(data.text);
                        $this.removeClass('btn-apply-job-internal').addClass('btn-applied-job-internal');
                    }
                    self.showMessage(data.msg, data.status);
                });
            });
        },
        applicantsInit: function() {
            var self = this;
            $(document).on('click', '.btn-remove-job-applied', function(e) {
                e.preventDefault();
                var $this = $(this);
                $this.addClass('loading');
                var applicant_id = $(this).data('applicant_id');
                var nonce = $(this).data('nonce');
                $.ajax({
                    url: wp_job_board_opts.ajaxurl,
                    type:'POST',
                    dataType: 'json',
                    data: {
                        'applicant_id': applicant_id,
                        'nonce': nonce,
                        'action': 'wp_job_board_ajax_remove_applied',
                    }
                }).done(function(data) {
                    $this.removeClass('loading');
                    if ( data.status ) {
                        $this.closest('.job-applied-wrapper').remove();
                        $this.closest('.job-applicant-wrapper').remove();
                    }
                    self.showMessage(data.msg, data.status);
                });
            });

            // reject applicant
            $(document).on('click', '.btn-reject-job-applied', function(e) {
                e.preventDefault();
                var $this = $(this);
                $this.addClass('loading');
                var applicant_id = $(this).data('applicant_id');
                var nonce = $(this).data('nonce');
                $.ajax({
                    url: wp_job_board_opts.ajaxurl,
                    type:'POST',
                    dataType: 'json',
                    data: {
                        'applicant_id': applicant_id,
                        'nonce': nonce,
                        'action': 'wp_job_board_ajax_reject_applied',
                    }
                }).done(function(data) {
                    $this.removeClass('loading');
                    if ( data.status ) {
                        var total_rej = parseInt($this.closest('.job-applicants').find('.rejected-applicants span').text()) + 1;
                        $this.closest('.job-applicants').find('.rejected-applicants span').text(total_rej);

                        if ( data.output ) {
                            $this.closest('.job-applicant-wrapper').replaceWith(data.output);
                        }
                    }
                    self.showMessage(data.msg, data.status);
                });
            });

            // undo reject applicant
            $(document).on('click', '.btn-undo-reject-job-applied', function(e) {
                e.preventDefault();
                var $this = $(this);
                $this.addClass('loading');
                var applicant_id = $(this).data('applicant_id');
                var nonce = $(this).data('nonce');
                $.ajax({
                    url: wp_job_board_opts.ajaxurl,
                    type:'POST',
                    dataType: 'json',
                    data: {
                        'applicant_id': applicant_id,
                        'nonce': nonce,
                        'action': 'wp_job_board_ajax_undo_reject_applied',
                    }
                }).done(function(data) {
                    $this.removeClass('loading');
                    if ( data.status ) {
                        var total_rej = parseInt($this.closest('.job-applicants').find('.rejected-applicants span').text()) - 1;
                        $this.closest('.job-applicants').find('.rejected-applicants span').text(total_rej);

                        if ( $this.closest('.job-applicants').find('.rejected-applicants').hasClass('active') ) {
                            
                            $this.closest('.job-applied-wrapper').remove();
                            $this.closest('.job-applicant-wrapper').remove();
                        } else {
                            if ( data.output ) {
                                $this.closest('.job-applicant-wrapper').replaceWith(data.output);
                            }
                        }
                    }
                    self.showMessage(data.msg, data.status);
                });
            });

            // show reject applicants
            $(document).on('click', '.show-rejected-applicants', function(e) {
                e.preventDefault();
                var $this = $(this),
                    $con = $this.closest('.job-applicants');
                
                if ( $this.hasClass('active') || $this.hasClass('loading') ) {
                    return false;
                }

                $this.addClass('loading');
                var job_id = $(this).data('job_id');
                var nonce = $(this).data('nonce');
                $.ajax({
                    url: wp_job_board_opts.ajaxurl,
                    type:'POST',
                    dataType: 'json',
                    data: {
                        'job_id': job_id,
                        'nonce': nonce,
                        'action': 'wp_job_board_ajax_show_rejected_applicants',
                    }
                }).done(function(data) {
                    $this.removeClass('loading');
                    if ( data.status ) {
                        $con.find('.applicants-wrapper').html(data.output);
                        $con.find('.show-total-applicants').removeClass('active');
                        $con.find('.show-approved-applicants').removeClass('active');
                        $this.addClass('active');
                    }
                    self.showMessage(data.msg, data.status);
                });
            });

            // approve applicant
            $(document).on('click', '.btn-approve-job-applied', function(e) {
                e.preventDefault();
                var $this = $(this);
                $this.addClass('loading');
                var applicant_id = $(this).data('applicant_id');
                var nonce = $(this).data('nonce');
                $.ajax({
                    url: wp_job_board_opts.ajaxurl,
                    type:'POST',
                    dataType: 'json',
                    data: {
                        'applicant_id': applicant_id,
                        'nonce': nonce,
                        'action': 'wp_job_board_ajax_approve_applied',
                    }
                }).done(function(data) {
                    $this.removeClass('loading');
                    if ( data.status ) {
                        var total_app = parseInt($this.closest('.job-applicants').find('.approved-applicants span').text()) + 1;
                        $this.closest('.job-applicants').find('.approved-applicants span').text(total_app);
                        
                        if ( data.output ) {
                            $this.closest('.job-applicant-wrapper').replaceWith(data.output);
                        }

                    }
                    self.showMessage(data.msg, data.status);
                });
            });

            // undo approved applicant
            $(document).on('click', '.btn-undo-approve-job-applied', function(e) {
                e.preventDefault();
                var $this = $(this);
                $this.addClass('loading');
                var applicant_id = $(this).data('applicant_id');
                var nonce = $(this).data('nonce');
                $.ajax({
                    url: wp_job_board_opts.ajaxurl,
                    type:'POST',
                    dataType: 'json',
                    data: {
                        'applicant_id': applicant_id,
                        'nonce': nonce,
                        'action': 'wp_job_board_ajax_undo_approve_applied',
                    }
                }).done(function(data) {
                    $this.removeClass('loading');
                    if ( data.status ) {

                        var total_approve = parseInt($this.closest('.job-applicants').find('.approved-applicants span').text()) - 1;
                        $this.closest('.job-applicants').find('.approved-applicants span').text(total_approve);

                        if ( $this.closest('.job-applicants').find('.approved-applicants').hasClass('active') ) {
                            $this.closest('.job-applied-wrapper').remove();
                            $this.closest('.job-applicant-wrapper').remove();
                        } else {
                            if ( data.output ) {
                                $this.closest('.job-applicant-wrapper').replaceWith(data.output);
                            }
                        }
                    }
                    self.showMessage(data.msg, data.status);
                });
            });

            // show approved applicants
            $(document).on('click', '.show-approved-applicants', function(e) {
                e.preventDefault();
                var $this = $(this),
                    $con = $this.closest('.job-applicants');
                
                if ( $this.hasClass('active') || $this.hasClass('loading') ) {
                    return false;
                }

                $this.addClass('loading');
                var job_id = $(this).data('job_id');
                var nonce = $(this).data('nonce');
                $.ajax({
                    url: wp_job_board_opts.ajaxurl,
                    type:'POST',
                    dataType: 'json',
                    data: {
                        'job_id': job_id,
                        'nonce': nonce,
                        'action': 'wp_job_board_ajax_show_approved_applicants',
                    }
                }).done(function(data) {
                    $this.removeClass('loading');
                    if ( data.status ) {
                        $con.find('.applicants-wrapper').html(data.output);
                        $con.find('.show-total-applicants').removeClass('active');
                        $con.find('.show-rejected-applicants').removeClass('active');
                        $this.addClass('active');
                    }
                    self.showMessage(data.msg, data.status);
                });
            });

            // show applicants
            $(document).on('click', '.show-total-applicants', function(e) {
                e.preventDefault();
                var $this = $(this),
                    $con = $this.closest('.job-applicants');

                if ( $this.hasClass('active') || $this.hasClass('loading') ) {
                    return false;
                }
                $this.addClass('loading');
                var job_id = $(this).data('job_id');
                var nonce = $(this).data('nonce');
                $.ajax({
                    url: wp_job_board_opts.ajaxurl,
                    type:'POST',
                    dataType: 'json',
                    data: {
                        'job_id': job_id,
                        'nonce': nonce,
                        'action': 'wp_job_board_ajax_show_applicants',
                    }
                }).done(function(data) {
                    $this.removeClass('loading');
                    if ( data.status ) {
                        $con.find('.applicants-wrapper').html(data.output);
                        $con.find('.show-rejected-applicants').removeClass('active');
                        $con.find('.show-approved-applicants').removeClass('active');
                        $this.addClass('active');
                    }
                    self.showMessage(data.msg, data.status);
                });
            });

            // pagination
            $(document).on('submit', 'form.applicants-pagination-form', function(e){
                e.preventDefault();
                var $this = $(this);
                var $container = $(this).closest('.applicants-wrapper');
                if ( $this.hasClass('loading') ) {
                    return false;
                }
                $this.addClass('loading');
                $.ajax({
                    url: wp_job_board_opts.ajaxurl,
                    type:'POST',
                    dataType: 'json',
                    data: $this.serialize()+'&action=wp_job_board_ajax_applicants_pagination'
                }).done(function(data) {
                    $this.removeClass('loading');
                    if ( data.status ) {
                        $container.find('.applicants-inner').append(data.output);
                        if ( data.paged > 0 ) {
                            $this.find('input[name=paged]').val(data.paged);
                        } else {
                            $this.remove();
                        }
                    } else {
                        self.showMessage(data.msg, data.status);
                    }
                });
                return false;
            });

        },
        addJobShortlist: function() {
            var self = this;
            $(document).on('click', '.btn-add-job-shortlist', function() {
                var $this = $(this);
                $this.addClass('loading');
                var job_id = $(this).data('job_id');
                var nonce = $(this).data('nonce');
                $.ajax({
                    url: wp_job_board_opts.ajaxurl,
                    type:'POST',
                    dataType: 'json',
                    data: {
                        'job_id': job_id,
                        'nonce': nonce,
                        'action': 'wp_job_board_ajax_add_job_shortlist',
                    }
                }).done(function(data) {
                    $this.removeClass('loading');
                    if ( data.status ) {
                        $this.removeClass('btn-add-job-shortlist').addClass('btn-added-job-shortlist');
                        $(document).trigger( "after_add_shortlist", [$this, data] );
                        $this.data('nonce', data.nonce);
                    }
                    self.showMessage(data.msg, data.status);
                });
            });
        },
        removeJobShortlist: function() {
            var self = this;
            $(document).on('click', '.btn-added-job-shortlist', function() {
                var $this = $(this);
                $this.addClass('loading');
                var job_id = $(this).data('job_id');
                var nonce = $(this).data('nonce');
                $.ajax({
                    url: wp_job_board_opts.ajaxurl,
                    type:'POST',
                    dataType: 'json',
                    data: {
                        'job_id': job_id,
                        'nonce': nonce,
                        'action': 'wp_job_board_ajax_remove_job_shortlist',
                    }
                }).done(function(data) {
                    $this.removeClass('loading');
                    if ( data.status ) {
                        $this.removeClass('btn-added-job-shortlist').addClass('btn-add-job-shortlist');
                        $(document).trigger( "after_remove_shortlist", [$this, data] );
                        $this.data('nonce', data.nonce);
                    }
                    self.showMessage(data.msg, data.status);
                });
            });

            $('.btn-remove-job-shortlist').on('click', function() {
                var $this = $(this);
                $this.addClass('loading');
                var job_id = $(this).data('job_id');
                var nonce = $(this).data('nonce');
                $.ajax({
                    url: wp_job_board_opts.ajaxurl,
                    type:'POST',
                    dataType: 'json',
                    data: {
                        'job_id': job_id,
                        'nonce': nonce,
                        'action': 'wp_job_board_ajax_remove_job_shortlist',
                    }
                }).done(function(data) {
                    $this.removeClass('loading');
                    if ( data.status ) {
                        $this.closest('.job-shortlist-wrapper').remove();
                    }
                    self.showMessage(data.msg, data.status);
                });
            });
        },
        addCandidateShortlist: function() {
            var self = this;
            $(document).on('click', '.btn-add-candidate-shortlist', function() {
                var $this = $(this);
                $this.addClass('loading');
                var candidate_id = $(this).data('candidate_id');
                var nonce = $(this).data('nonce');
                $.ajax({
                    url: wp_job_board_opts.ajaxurl,
                    type:'POST',
                    dataType: 'json',
                    data: {
                        'candidate_id': candidate_id,
                        'nonce': nonce,
                        'action': 'wp_job_board_ajax_add_candidate_shortlist',
                    }
                }).done(function(data) {
                    $this.removeClass('loading');
                    if ( data.status ) {
                        $this.removeClass('btn-add-candidate-shortlist').addClass('btn-added-candidate-shortlist');
                        $this.text(data.text);
                        $this.attr('title', data.text);
                        $this.data('nonce', data.nonce);
                        $(document).trigger( "after_add_candidate_shortlist", [$this, data] );
                    }
                    self.showMessage(data.msg, data.status);
                });
            });
        },
        removeCandidateShortlist: function() {
            var self = this;
            $(document).on('click', '.btn-added-candidate-shortlist', function() {
                var $this = $(this);
                $this.addClass('loading');
                var candidate_id = $(this).data('candidate_id');
                var nonce = $(this).data('nonce');
                $.ajax({
                    url: wp_job_board_opts.ajaxurl,
                    type:'POST',
                    dataType: 'json',
                    data: {
                        'candidate_id': candidate_id,
                        'nonce': nonce,
                        'action': 'wp_job_board_ajax_remove_candidate_shortlist',
                    }
                }).done(function(data) {
                    $this.removeClass('loading');
                    if ( data.status ) {
                        $this.removeClass('btn-added-candidate-shortlist').addClass('btn-add-candidate-shortlist');
                        $this.text(data.text);
                        $this.attr('title', data.text);
                        $this.data('nonce', data.nonce);
                        $(document).trigger( "after_remove_candidate_shortlist", [$this, data] );
                    }

                    self.showMessage(data.msg, data.status);
                });
            });

            $(document).on('click', '.btn-remove-candidate-shortlist', function() {
                var $this = $(this);
                $this.addClass('loading');
                var candidate_id = $(this).data('candidate_id');
                var nonce = $(this).data('nonce');
                $.ajax({
                    url: wp_job_board_opts.ajaxurl,
                    type:'POST',
                    dataType: 'json',
                    data: {
                        'candidate_id': candidate_id,
                        'nonce': nonce,
                        'action': 'wp_job_board_ajax_remove_candidate_shortlist',
                    }
                }).done(function(data) {
                    $this.removeClass('loading');
                    if ( data.status ) {
                        $this.closest('.candidate-shortlist-wrapper').remove();
                    }
                    self.showMessage(data.msg, data.status);
                });
            });
        },
        followEmployer: function() {
            var self = this;
            $(document).on('click', '.btn-follow-employer', function() {
                var $this = $(this);
                $this.addClass('loading');
                var employer_id = $(this).data('employer_id');
                var nonce = $(this).data('nonce');
                $.ajax({
                    url: wp_job_board_opts.ajaxurl,
                    type:'POST',
                    dataType: 'json',
                    data: {
                        'employer_id': employer_id,
                        'nonce': nonce,
                        'action': 'wp_job_board_ajax_follow_employer',
                    }
                }).done(function(data) {
                    $this.removeClass('loading');
                    if ( data.status ) {
                        $this.removeClass('btn-follow-employer').addClass('btn-unfollow-employer');
                        $this.data('nonce', data.nonce);
                        $this.find('span').text(data.text);
                        $(document).trigger( "after_follow_employer", [$this, data] );
                    }
                    self.showMessage(data.msg, data.status);
                });
            });

            // unfollow
            $(document).on('click', '.btn-unfollow-employer', function() {
                var $this = $(this);
                $this.addClass('loading');
                var employer_id = $(this).data('employer_id');
                var nonce = $(this).data('nonce');
                $.ajax({
                    url: wp_job_board_opts.ajaxurl,
                    type:'POST',
                    dataType: 'json',
                    data: {
                        'employer_id': employer_id,
                        'nonce': nonce,
                        'action': 'wp_job_board_ajax_unfollow_employer',
                    }
                }).done(function(data) {
                    $this.removeClass('loading');
                    if ( data.status ) {
                        if ( $this.closest('.following-employer-wrapper').length > 0 ) {
                            if ( $('.following-employer-wrapper').length <= 1 ) {
                                location.reload();
                            } else {
                                $this.closest('.following-employer-wrapper').remove();
                            }
                        } else {
                            $this.removeClass('btn-unfollow-employer').addClass('btn-follow-employer');
                            $this.data('nonce', data.nonce);
                            $this.find('span').text(data.text);
                            $(document).trigger( "after_unfollow_employer", [$this, data] );
                        }
                    }
                    self.showMessage(data.msg, data.status);
                });
            });
        },
        blockEmployer: function() {
            var self = this;
            $(document).on('click', '.btn-blacklist-employer', function() {
                console.log('GG');
                var $this = $(this);
                $this.addClass('loading');
                var employer_id = $(this).data('employer_id');
                var nonce = $(this).data('nonce');
                console.log('admin');
                $.ajax({
                    url: wp_job_board_opts.ajaxurl,
                    type:'POST',
                    dataType: 'json',
                    data: {
                        'employer_id': employer_id,
                        'nonce': nonce,
                        'action': 'wp_job_board_ajax_blacklist_employer',
                    }
                }).done(function(data) {
                    console.log(data);
                    $this.removeClass('loading');
                    if ( data.status ) {
                        $this.removeClass('btn-blacklist-employer').addClass('btn-removeblacklist-employer');
                        $this.data('nonce', data.nonce);
                        $this.find('span').text(data.text);
                        $(document).trigger( "after_follow_employer", [$this, data] );
                    }
                    self.showMessage(data.msg, data.status);
                });
            });

            // removeblacklist
            $(document).on('click', '.btn-removeblacklist-employer', function() {
                var $this = $(this);
                $this.addClass('loading');
                var employer_id = $(this).data('employer_id');
                var nonce = $(this).data('nonce');
                $.ajax({
                    url: wp_job_board_opts.ajaxurl,
                    type:'POST',
                    dataType: 'json',
                    data: {
                        'employer_id': employer_id,
                        'nonce': nonce,
                        'action': 'wp_job_board_ajax_removeblacklist_employer',
                    }
                }).done(function(data) {
                    $this.removeClass('loading');
                    if ( data.status ) {
                        if ( $this.closest('.following-employer-wrapper').length > 0 ) {
                            if ( $('.following-employer-wrapper').length <= 1 ) {
                                location.reload();
                            } else {
                                $this.closest('.following-employer-wrapper').remove();
                            }
                        } else {
                            $this.removeClass('btn-removeblacklist-employer').addClass('btn-blacklist-employer');
                            $this.data('nonce', data.nonce);
                            $this.find('span').text(data.text);
                            $(document).trigger( "after_removeblacklist_employer", [$this, data] );
                        }
                    }
                    self.showMessage(data.msg, data.status);
                });
            });

        },
        reviewInit: function() {
            var self = this;
            if ( $('.comment-form-rating').length > 0 ) {
                var $star = $('.comment-form-rating .filled');
                var $review = $('#_input_rating');
                $star.find('li').on('mouseover', function () {
                    $(this).nextAll().addClass('active');
                    $(this).prevAll().removeClass('active');
                    $(this).removeClass('active');
                });
                $star.on('mouseout', function(){
                    var current = $review.val() - 1;
                    var current_e = $star.find('li').eq(current);

                    current_e.nextAll().addClass('active');
                    current_e.prevAll().removeClass('active');
                    current_e.removeClass('active');
                });
                $star.find('li').on('click', function () {
                    $(this).nextAll().addClass('active');
                    $(this).prevAll().removeClass('active');
                    $(this).removeClass('active');
                    
                    $review.val($(this).index() + 1);
                } );
            }
        },
        jobAlert: function() {
            var self = this;
            $(document).on('click', '.btn-job-alert', function() {
                var form_html = $('.job-alert-form-wrapper').html();
                $.magnificPopup.open({
                    mainClass: 'wp-job-board-mfp-container',
                    items    : {
                        src : form_html,
                        type: 'inline'
                    }
                });
            });
            $(document).on('submit', 'form.job-alert-form', function() {
                var $this = $(this);
                $this.addClass('loading');
                var url_vars = self.getUrlVars();
                $.ajax({
                    url: wp_job_board_opts.ajaxurl,
                    type:'POST',
                    dataType: 'json',
                    data: $this.serialize() + '&action=wp_job_board_ajax_add_job_alert' + url_vars
                }).done(function(data) {
                    $this.removeClass('loading');
                    if ( data.status ) {
                        $this.prepend( '<div class="alert alert-info">'+data.msg+'</div>' );
                        setTimeout(function(){
                            $.magnificPopup.close();
                        }, 1500);
                    } else {
                        $this.prepend( '<div class="alert alert-warning">'+data.msg+'</div>' );
                    }
                });

                return false;
            });

            // Remove job alert
            $(document).on('click', '.btn-remove-job-alert', function() {
                var $this = $(this);
                $this.addClass('loading');
                var alert_id = $(this).data('alert_id');
                var nonce = $(this).data('nonce');
                $.ajax({
                    url: wp_job_board_opts.ajaxurl,
                    type:'POST',
                    dataType: 'json',
                    data: {
                        'alert_id': alert_id,
                        'nonce': nonce,
                        'action': 'wp_job_board_ajax_remove_job_alert',
                    }
                }).done(function(data) {
                    $this.removeClass('loading');
                    if ( data.status ) {
                        $this.closest('.job-alert-wrapper').remove();
                    }
                    self.showMessage(data.msg, data.status);
                });
            });
        },
        candidateAlert: function() {
            var self = this;
            $(document).on('click', '.btn-candidate-alert', function() {
                var form_html = $('.candidate-alert-form-wrapper').html();
                $.magnificPopup.open({
                    mainClass: 'wp-job-board-mfp-container',
                    items    : {
                        src : form_html,
                        type: 'inline'
                    }
                });
            });
            $(document).on('submit', 'form.candidate-alert-form', function() {
                var $this = $(this);
                $this.addClass('loading');
                var url_vars = self.getUrlVars();
                $.ajax({
                    url: wp_job_board_opts.ajaxurl,
                    type:'POST',
                    dataType: 'json',
                    data: $this.serialize() + '&action=wp_job_board_ajax_add_candidate_alert' + url_vars
                }).done(function(data) {
                    $this.removeClass('loading');
                    if ( data.status ) {
                        $this.prepend( '<div class="alert alert-info">'+data.msg+'</div>' );
                        setTimeout(function(){
                            $.magnificPopup.close();
                        }, 1500);
                    } else {
                        $this.prepend( '<div class="alert alert-warning">'+data.msg+'</div>' );
                    }
                });

                return false;
            });

            // Remove candidate alert
            $(document).on('click', '.btn-remove-candidate-alert', function() {
                var $this = $(this);
                $this.addClass('loading');
                var alert_id = $(this).data('alert_id');
                var nonce = $(this).data('nonce');
                $.ajax({
                    url: wp_job_board_opts.ajaxurl,
                    type:'POST',
                    dataType: 'json',
                    data: {
                        'alert_id': alert_id,
                        'nonce': nonce,
                        'action': 'wp_job_board_ajax_remove_candidate_alert',
                    }
                }).done(function(data) {
                    $this.removeClass('loading');
                    if ( data.status ) {
                        $this.closest('.candidate-alert-wrapper').remove();
                    }
                    self.showMessage(data.msg, data.status);
                });
            });
        },
        getUrlVars: function() {
            var self = this;
            var vars = '';
            var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
            for(var i = 0; i < hashes.length; i++) {
                vars += '&' +hashes[i];
            }
            return vars;
        },
        jobSocialApply: function() {
            var self = this;
            $('.facebook-apply-btn').on('click', function(){
                var job_id = $(this).data('job_id');
                var facebook_login_url = $(this).attr('href');
                self.setCookie('wp_job_board_facebook_job_id', job_id, 1);
                window.location.href = facebook_login_url;
            });
            
            // google
            $('.google-apply-btn').on('click', function(){
                var job_id = $(this).data('job_id');
                var google_login_url = $(this).attr('href');
                self.setCookie('wp_job_board_google_job_id', job_id, 1);
                window.location.href = google_login_url;
            });

            // linkedin
            $('.linkedin-apply-btn').on('click', function(){
                var job_id = $(this).data('job_id');
                var linkedin_login_url = $(this).attr('href');
                self.setCookie('wp_job_board_linkedin_job_id', job_id, 1);
                window.location.href = linkedin_login_url;
            });

            // twitter
            $('.twitter-apply-btn').on('click', function(){
                var job_id = $(this).data('job_id');
                var twitter_login_url = $(this).attr('href');
                self.setCookie('wp_job_board_twitter_job_id', job_id, 1);
                window.location.href = twitter_login_url;
            });
        },
        select2Init: function() {
            var self = this;
            if ( $.isFunction( $.fn.select2 ) && typeof wp_job_board_select2_opts !== 'undefined' ) {
                var select2_args = wp_job_board_select2_opts;
                select2_args['allowClear']              = false;
                select2_args['minimumResultsForSearch'] = 10;
                select2_args['width'] = 'auto';
                
                $('select.orderby').select2( select2_args );
                $('select.job_id').select2( select2_args );
            }
        },
        jobSubmission: function() {
            var self = this;
            // select2
            if ( $.isFunction( $.fn.select2 ) && typeof wp_job_board_select2_opts !== 'undefined' ) {
                var select2_args = wp_job_board_select2_opts;
                select2_args['allowClear']              = false;
                select2_args['minimumResultsForSearch'] = 10;
                
                $( 'select.cmb2_select' ).select2( select2_args );
            }

            $('.cmb-repeatable-group').on('cmb2_add_group_row_start', function (event, instance) {
                var $table = $(document.getElementById($(instance).data('selector')));
                var $oldRow = $table.find('.cmb-repeatable-grouping').last();

                $oldRow.find('select.cmb2_select').each(function () {
                    $(this).select2('destroy');
                });
            });

            $('.cmb-repeatable-group').on('cmb2_add_row', function (event, newRow) {
                $(newRow).find('select.cmb2_select').each(function () {
                    $('option:selected', this).removeAttr("selected");
                    $(this).select2(select2_args);
                });

                // Reinitialise the field we previously destroyed
                $(newRow).prev().find('select.cmb2_select').each(function () {
                    $(this).select2(select2_args);
                });
            });
            
            ///
            $('.cmb-add-row-button').on('click', function (event) {
                var $table = $(document.getElementById($(event.target).data('selector')));
                var $oldRow = $table.find('.cmb-row').last();

                $oldRow.find('select.cmb2_select').each(function () {
                    $(this).select2('destroy');
                });
            });

            $('.cmb-repeat-table').on('cmb2_add_row', function (event, newRow) {
                // Reinitialise the field we previously destroyed
                $(newRow).prev().find('select.cmb2_select').each(function () {
                    $('option:selected', this).removeAttr("selected");
                    $(this).select2(select2_args);
                });
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
        },
        filterListing: function() {
            var self = this;

            $(document).on('click', 'form .toggle-field .heading-label', function(){
                var container = $(this).closest('.form-group');
                container.find('.form-group-inner').slideToggle();
                if ( container.hasClass('hide-content') ) {
                    container.removeClass('hide-content');
                } else {
                    container.addClass('hide-content');
                }
            });
            $(document).on('click', '.toggle-filter-list', function() {
                var $this = $(this);
                var container = $(this).closest('.form-group');
                container.find('.terms-list .more-fields').each(function(){
                    if ( $(this).hasClass('active') ) {
                        $(this).removeClass('active');
                        $this.find('.text').text(wp_job_board_opts.show_more);
                    } else {
                        $(this).addClass('active');
                        $this.find('.text').text(wp_job_board_opts.show_less);
                    }
                });
            });

            if ( $.isFunction( $.fn.slider ) ) {
                $('.search-distance-slider').each(function(){
                    var $this = $(this);
                    var search_distance = $this.closest('.search-distance-wrapper').find('input[name^=filter-distance]');
                    var search_wrap = $this.closest('.search_distance_wrapper');
                    $(this).slider({
                        range: "min",
                        value: search_distance.val(),
                        min: 0,
                        max: 100,
                        slide: function( event, ui ) {
                            search_distance.val( ui.value );
                            $('.text-distance', search_wrap).text( ui.value );
                            $('.distance-custom-handle', $this).attr( "data-value", ui.value );
                            search_distance.trigger('change');
                        },
                        create: function() {
                            $('.distance-custom-handle', $this).attr( "data-value", $( this ).slider( "value" ) );
                        }
                    } );
                } );

                $('.main-range-slider').each(function(){
                    var $this = $(this);
                    $this.slider({
                        range: true,
                        min: $this.data('min'),
                        max: $this.data('max'),
                        values: [ $this.parent().find('.filter-from').val(), $this.parent().find('.filter-to').val() ],
                        slide: function( event, ui ) {
                            $this.parent().find('.from-text').text( ui.values[ 0 ] );
                            $this.parent().find('.filter-from').val( ui.values[ 0 ] )
                            $this.parent().find('.to-text').text( ui.values[ 1 ] );
                            $this.parent().find('.filter-to').val( ui.values[ 1 ] );
                            $this.parent().find('.filter-to').trigger('change');
                        }
                    } );
                });

                $('.salary-range-slider').each(function(){
                    var $this = $(this);
                    $this.slider({
                        range: true,
                        min: $this.data('min'),
                        max: $this.data('max'),
                        values: [ $this.parent().find('.filter-from').val(), $this.parent().find('.filter-to').val() ],
                        slide: function( event, ui ) {
                            $this.parent().find('.from-text .price-text').text( self.addCommas(ui.values[ 0 ]) );
                            $this.parent().find('.filter-from').val( ui.values[ 0 ] )
                            $this.parent().find('.to-text .price-text').text( self.addCommas(ui.values[ 1 ]) );
                            $this.parent().find('.filter-to').val( ui.values[ 1 ] );
                            $this.parent().find('.filter-to').trigger('change');
                        }
                    } );
                });
            }

            $('.find-me').on('click', function() {
                $(this).addClass('loading');
                var this_e = $(this);
                var container = $(this).closest('.form-group');

                navigator.geolocation.getCurrentPosition(function (position) {
                    container.find('input[name="filter-center-latitude"]').val(position.coords.latitude);
                    container.find('input[name="filter-center-longitude"]').val(position.coords.longitude);
                    container.find('input[name="filter-center-location"]').val('Location');
                    container.find('.clear-location').removeClass('hidden');
                    container.find('.leaflet-geocode-container').html('').removeClass('active');
                    var position = [position.coords.latitude, position.coords.longitude];

                    if ( typeof L.esri.Geocoding.geocodeService != 'undefined' ) {
                    
                        var geocodeService = L.esri.Geocoding.geocodeService();
                        geocodeService.reverse().latlng(position).run(function(error, result) {
                            container.find('input[name="filter-center-location"]').val(result.address.Match_addr);
                        });
                    }

                    return this_e.removeClass('loading');
                }, function (e) {
                    return this_e.removeClass('loading');
                }, {
                    enableHighAccuracy: true
                });
            });

            $('.clear-location').on('click', function() {
                var container = $(this).closest('.form-group');

                container.find('input[name="filter-center-latitude"]').val('');
                container.find('input[name="filter-center-longitude"]').val('');
                container.find('input[name="filter-center-location"]').val('').trigger('keyup');
                container.find('.clear-location').addClass('hidden');
            });
            $('input[name="filter-center-location"]').on('keyup', function(){
                var container = $(this).closest('.form-group');
                var val = $(this).val();
                if ( $(this).val() !== '' ) {
                    container.find('.clear-location').removeClass('hidden');
                } else {
                    container.find('.clear-location').removeClass('hidden').addClass('hidden');
                }
            });
            // search autocomplete location
            if ( typeof L.Control.Geocoder.Nominatim != 'undefined' ) {
                if ( wp_job_board_opts.geocoder_country ) {
                    var geocoder = new L.Control.Geocoder.Nominatim({
                        geocodingQueryParams: {countrycodes: wp_job_board_opts.geocoder_country}
                    });
                } else {
                    var geocoder = new L.Control.Geocoder.Nominatim();
                }

                $("input[name=filter-center-location]").attr('autocomplete', 'off').after('<div class="leaflet-geocode-container"></div>');
                $("input[name=filter-center-location]").on("keyup",function search(e) {
                    var s = $(this).val(), $this = $(this), container = $(this).closest('.form-group-inner');
                    if (s && s.length >= 3) {
                        
                        $this.parent().addClass('loading');
                        geocoder.geocode(s, function(results) {
                            var output_html = '';
                            for (var i = 0; i < results.length; i++) {
                                output_html += '<li class="result-item" data-latitude="'+results[i].center.lat+'" data-longitude="'+results[i].center.lng+'" ><i class="fa fa-map-marker" aria-hidden="true"></i> '+results[i].name+'</li>';
                            }
                            if ( output_html ) {
                                output_html = '<ul>'+ output_html +'</ul>';
                            }

                            container.find('.leaflet-geocode-container').html(output_html).addClass('active');

                            var highlight_texts = s.split(' ');

                            highlight_texts.forEach(function (item) {
                                container.find('.leaflet-geocode-container').highlight(item);
                            });

                            $this.parent().removeClass('loading');
                        });
                    } else {
                        container.find('.leaflet-geocode-container').html('').removeClass('active');
                    }
                });
                $('.form-group-inner').on('click', '.leaflet-geocode-container ul li', function() {
                    var container = $(this).closest('.form-group-inner');
                    container.find('input[name=filter-center-latitude]').val($(this).data('latitude'));
                    container.find('input[name=filter-center-longitude]').val($(this).data('longitude'));
                    container.find('input[name=filter-center-location]').val($(this).text());
                    container.find('.leaflet-geocode-container').removeClass('active').html('');
                });
            }

        },
        employerAddEmployee: function() {
            var self = this;
            
            // add
            $(document).on('submit', 'form.employer-add-employees-form', function(e) {
                e.preventDefault();
                var $this = $(this);
                if ( $this.hasClass('loading') ) {
                    return false;
                }
                $this.addClass('loading');
                $.ajax({
                    url: wp_job_board_opts.ajaxurl,
                    type:'POST',
                    dataType: 'json',
                    data: $this.serialize() + '&action=wp_job_board_ajax_employer_add_employee'
                }).done(function(data) {
                    $this.removeClass('loading');
                    if ( data.status ) {
                        $('.employer-employees-list-inner').prepend(data.html);
                        if ( $('.employer-employees-list-inner .not-found').length ) {
                            $('.employer-employees-list-inner .not-found').remove();
                        }
                        $this.find('.team-employee-inner').remove();
                    }
                    self.showMessage(data.msg, data.status);
                });
                return false;
            });
            // remove
            $(document).on('click', '.btn-employer-remove-employee', function() {
                var $this = $(this);
                var r = confirm( wp_job_board_opts.rm_item_txt );
                if ( r == true ) {
                    $this.addClass('loading');
                    var employee_id = $(this).data('employee_id');
                    var nonce = $(this).data('nonce');
                    $.ajax({
                        url: wp_job_board_opts.ajaxurl,
                        type:'POST',
                        dataType: 'json',
                        data: {
                            'employee_id': employee_id,
                            'nonce': nonce,
                            'action': 'wp_job_board_ajax_employer_remove_employee',
                        }
                    }).done(function(data) {
                        $this.removeClass('loading');
                        if ( data.status ) {
                            $this.closest('article.employee-team-wrapper').remove();
                        }
                        self.showMessage(data.msg, data.status);
                    });
                }
            });
        },
        mixesFn: function() {
            var self = this;
            
            $( '.my-jobs-ordering' ).on( 'change', 'select.orderby', function() {
                $( this ).closest( 'form' ).submit();
            });

            $('.contact-form-wrapper').on('submit', function(){
                var $this = $(this);
                $this.find('.alert').remove();
                $this.addClass('loading');
                $.ajax({
                    url: wp_job_board_opts.ajaxurl,
                    type:'POST',
                    dataType: 'json',
                    data: $this.serialize() + '&action=wp_job_board_ajax_contact_form'
                }).done(function(data) {
                    $this.removeClass('loading');
                    if ( data.status ) {
                        $this.prepend( '<div class="alert alert-info">'+data.msg+'</div>' );
                    } else {
                        $this.prepend( '<div class="alert alert-warning">'+data.msg+'</div>' );
                    }
                });

                return false;
            });

            $(document).on( 'submit', 'form.delete-profile-form', function() {
                var $this = $(this);
                $this.addClass('loading');
                $(this).find('.alert').remove();
                $.ajax({
                    url: wp_job_board_opts.ajaxurl,
                    type:'POST',
                    dataType: 'json',
                    data: $this.serialize() + '&action=wp_job_board_ajax_delete_profile'
                }).done(function(data) {
                    $this.removeClass('loading');
                    if ( data.status ) {
                        $this.prepend( '<div class="alert alert-info">'+data.msg+'</div>' );
                        window.location.href = wp_job_board_opts.home_url;
                    } else {
                        $this.prepend( '<div class="alert alert-warning">'+data.msg+'</div>' );
                    }
                });

                return false;
            });

            $('.cannot-download-cv-btn').on('click', function(e) {
                e.preventDefault();
                self.showMessage($(this).data('msg'), false);
            });
        },
        addCommas: function(str) {
            var parts = (str + "").split("."),
                main = parts[0],
                len = main.length,
                output = "",
                first = main.charAt(0),
                i;
            
            if (first === '-') {
                main = main.slice(1);
                len = main.length;    
            } else {
                first = "";
            }
            i = len - 1;
            while(i >= 0) {
                output = main.charAt(i) + output;
                if ((len - i) % 3 === 0 && i > 0) {
                    output = wp_job_board_opts.money_thousands_separator + output;
                }
                --i;
            }
            // put sign back
            output = first + output;
            // put decimal part back
            if (parts.length > 1) {
                output += wp_job_board_opts.money_dec_point + parts[1];
            }
            return output;
        },
        showMessage: function(msg, status) {
            if ( msg ) {
                var classes = 'alert bg-warning';
                if ( status ) {
                    classes = 'alert bg-info';
                }
                var $html = '<div id="wp-job-board-popup-message" class="animated fadeInRight"><div class="message-inner '+ classes +'">'+ msg +'</div></div>';
                $('body').find('#wp-job-board-popup-message').remove();
                $('body').append($html).fadeIn(500);
                setTimeout(function() {
                    $('body').find('#wp-job-board-popup-message').removeClass('fadeInRight').addClass('delay-2s fadeOutRight');
                }, 2500);
            }
        },
        setCookie: function(cname, cvalue, exdays) {
            var d = new Date();
            d.setTime(d.getTime() + (exdays*24*60*60*1000));
            var expires = "expires="+d.toUTCString();
            document.cookie = cname + "=" + cvalue + "; " + expires+";path=/";
        },
        getCookie: function(cname) {
            var name = cname + "=";
            var ca = document.cookie.split(';');
            for(var i=0; i<ca.length; i++) {
                var c = ca[i];
                while (c.charAt(0)==' ') c = c.substring(1);
                if (c.indexOf(name) == 0) return c.substring(name.length,c.length);
            }
            return "";
        },
    }

    $.wjbMainCore = WJBMainCore.prototype;
    
    $(document).ready(function() {
        // Initialize script
        new WJBMainCore();

    });
    
})(jQuery);

