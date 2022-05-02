<?php

if ( ! defined( 'ABSPATH' ) ) {
  	exit;
}
global $post;
// get our custom meta
$author_email = WP_Job_Board_Job_Listing::get_post_meta( $post->ID, 'apply_email', true);
if ( empty($author_email) ) {
	$author_email = WP_Job_Board_Job_Listing::get_post_meta( $post->ID, 'email', true);
}
if ( empty($author_email) ) {
	$author_email = get_the_author_meta( 'user_email', $post->post_author );
}
?>

<?php if ( ! empty( $author_email ) ) : ?>
	<div id="job-apply-email-form-wrapper-<?php echo esc_attr($post->ID); ?>" class="job-apply-email-form-wrapper mfp-hide">
		<div class="inner">
		<h2 class="widget-title">
			<span><?php echo __('Apply for this job', 'wp-job-board'); ?></span>
		</h2>

	    <form id="job-apply-email-form-<?php echo esc_attr($post->ID); ?>" class="job-apply-email-form" method="post" action="" enctype="multipart/form-data">
	    	<div class="row">
		        <div class="col-sm-12">
			        <div class="form-group">
			            <input type="text" class="form-control style2" name="fullname" placeholder="<?php esc_attr_e( 'Full Name', 'wp-job-board' ); ?>" required="required">
			        </div><!-- /.form-group -->
			    </div>
			    <div class="col-sm-12">
			        <div class="form-group">
			            <input type="email" class="form-control style2" name="email" placeholder="<?php esc_attr_e( 'E-mail', 'wp-job-board' ); ?>" required="required">
			        </div><!-- /.form-group -->
			    </div>
			    <div class="col-sm-12">
			        <div class="form-group">
			            <input type="text" class="form-control style2" name="phone" placeholder="<?php esc_attr_e( 'Phone', 'wp-job-board' ); ?>">
			        </div><!-- /.form-group -->
			    </div>
			    <div class="col-sm-12">
			     	<div class="form-group space-30">
			            <textarea class="form-control style2" name="message" placeholder="<?php esc_attr_e( 'Message', 'wp-job-board' ); ?>" required="required"></textarea>
			        </div>
		        </div><!-- /.form-group -->
		        <div class="col-sm-12">
			     	<div class="form-group">
			            <input type="file" name="cv_file" placeholder="<?php esc_attr_e( 'Curriculum Vitae', 'wp-job-board' ); ?>" >
			        </div>
		        </div><!-- /.form-group -->
	        </div>
	       	

	        <?php if ( WP_Job_Board_Recaptcha::is_recaptcha_enabled() ) { ?>
	            <div id="recaptcha-contact-form" class="ga-recaptcha" data-sitekey="<?php echo esc_attr(wp_job_board_get_option( 'recaptcha_site_key' )); ?>"></div>
	      	<?php } ?>

	      	<?php wp_nonce_field( 'wp-job-board-apply-email', 'wp-job-board-apply-email-nonce' ); ?>
	      	<input type="hidden" name="action" value="wp_job_board_ajax_apply_email">
	      	<input type="hidden" name="job_id" value="<?php echo esc_attr($post->ID); ?>">
	        <button class="button btn btn-theme btn-block" name="apply-email"><?php echo esc_html__( 'Apply Job', 'wp-job-board' ); ?></button>
	    </form>
	</div>
	</div>
<?php endif; ?>