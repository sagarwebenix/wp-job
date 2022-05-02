<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$page_id = wp_job_board_get_option('login_register_page_id');
$page_url = get_permalink($page_id);
?>

<div class="alert alert-warning not-allow-wrapper">
	<p class="account-sign-in"><?php esc_html_e( 'Please add your CIF Number.', 'wp-job-board' ); ?></p>
</div><!-- /.alert -->
