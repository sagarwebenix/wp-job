<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="register-form-wrapper">
  	<div class="container-form">
      	<form name="registerForm" method="post" class="register-form">
      		<div class="form-group">
				<ul class="role-tabs">
					<li class="active"><input type="radio" name="role" value="wp_job_board_candidate" checked="checked"> <?php esc_html_e('Candidate', 'wp-job-board'); ?></li>
					<li><input type="radio" name="role" value="wp_job_board_employer"> <?php esc_html_e('Employer', 'wp-job-board'); ?></li>
					<li><input type="radio" name="role" value="wp_job_board_employee"> <?php esc_html_e('Employee', 'wp-job-board'); ?></li>
				</ul>
			</div>
			<div class="form-group">
				<label for="register-username"><?php esc_html_e('Username', 'wp-job-board'); ?></label>
				<sup class="required-field">*</sup>
				<input type="text" class="form-control" name="username" id="register-username" placeholder="<?php esc_attr_e('Enter Username','wp-job-board'); ?>">
			</div>
			<div class="form-group">
				<label for="register-email"><?php esc_html_e('Email', 'wp-job-board'); ?></label>
				<sup class="required-field">*</sup>
				<input type="text" class="form-control" name="email" id="register-email" placeholder="<?php esc_attr_e('Enter Email','wp-job-board'); ?>">
			</div>
			<div class="form-group">
				<label for="password"><?php esc_html_e('Password', 'wp-job-board'); ?></label>
				<sup class="required-field">*</sup>
				<input type="password" class="form-control" name="password" id="password" placeholder="<?php esc_attr_e('Enter Password','wp-job-board'); ?>">
			</div>
			<div class="form-group">
				<label for="confirmpassword"><?php esc_html_e('Confirm Password', 'wp-job-board'); ?></label>
				<sup class="required-field">*</sup>
				<input type="password" class="form-control" name="confirmpassword" id="confirmpassword" placeholder="<?php esc_attr_e('Enter Password','wp-job-board'); ?>">
			</div>

			<div class="form-group wp_job_board_employer_show">
				<label for="register-company-name"><?php esc_html_e('Company Name', 'wp-job-board'); ?></label>
				<input type="text" class="form-control" name="company_name" id="register-company-name" placeholder="<?php esc_attr_e('Company Name','wp-job-board'); ?>">
			</div>

			<div class="form-group" style="display:none;">
				<label for="register-phone"><?php esc_html_e('Phone', 'wp-job-board'); ?></label>
                <sup class="required-field">*</sup>
				<input type="text" class="form-control" name="phone" id="register-phone" placeholder="<?php esc_attr_e('Phone','wp-job-board'); ?>">
			</div>
			<?php
				$candidate_args = array(
		            'taxonomy' => 'candidate_category',
		            'orderby' => 'name',
		            'order' => 'ASC',
		            'hide_empty' => false,
		            'number' => false,
			    );
			    $terms = get_terms($candidate_args);

			    if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
			    	?>
			    	<div class="form-group wp_job_board_candidate_show">
						<label for="register-candidate-category"><?php esc_html_e('Category', 'wp-job-board'); ?></label>
						<select id="register-candidate-category" name="candidate_category">
							<option value=""><?php esc_html_e('Select Category', 'wp-job-board'); ?></option>
							<?php foreach ($terms as $term) { ?>
								<option class="<?php echo esc_attr($term->term_id); ?>"><?php echo esc_html($term->name); ?></option>
							<?php } ?>
						</select>
					</div>
			    	<?php
			    }
			?>
			<?php
				$employer_args = array(
		            'taxonomy' => 'employer_category',
		            'orderby' => 'name',
		            'order' => 'ASC',
		            'hide_empty' => false,
		            'number' => false,
			    );
			    $terms = get_terms($employer_args);

			    if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
			    	?>
			    	<div class="form-group wp_job_board_employer_show">
						<label for="register-employer-category"><?php esc_html_e('Category', 'wp-job-board'); ?></label>
						<select id="register-employer-category" name="employer_category">
							<option value=""><?php esc_html_e('Select Category', 'wp-job-board'); ?></option>
							<?php foreach ($terms as $term) { ?>
								<option class="<?php echo esc_attr($term->term_id); ?>"><?php echo esc_html($term->name); ?></option>
							<?php } ?>
						</select>
					</div>
			    	<?php
			    }
			?>
			<?php wp_nonce_field('ajax-register-nonce', 'security_register'); ?>

			<?php if ( WP_Job_Board_Recaptcha::is_recaptcha_enabled() ) { ?>
	            <div id="recaptcha-contact-form" class="ga-recaptcha" data-sitekey="<?php echo esc_attr(wp_job_board_get_option( 'recaptcha_site_key' )); ?>"></div>
	      	<?php } ?>
	      	
			<?php
			$page_id = wp_job_board_get_option('terms_conditions_page_id');
			if ( !empty($page_id) ) {
				$page_url = $page_id ? get_permalink($page_id) : home_url('/');
			?>
				<div class="form-group">
					<label for="register-terms-and-conditions">
						<input type="checkbox" name="terms_and_conditions" value="on" id="register-terms-and-conditions" required>
						<?php
							echo sprintf(__('You accept our <a href="%s">Terms and Conditions and Privacy Policy</a>', 'wp-job-board'), esc_url($page_url));
						?>
					</label>
				</div>
			<?php } ?>

			<div class="form-group">
				<button type="submit" class="btn btn-second btn-block" name="submitRegister">
					<?php echo esc_html__('Register now', 'wp-job-board'); ?>
				</button>
			</div>

			<?php do_action('register_form'); ?>
      	</form>
    </div>

</div>
