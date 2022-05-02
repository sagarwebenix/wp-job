<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="job-rss-btn margin-left-15">
	<a class="btn btn-warning" href="<?php echo esc_url(WP_Job_Board_Job_Listing::job_feed_url(null, array('submit', 'paged'), '', '', true )); ?>" target="_blank">
		<i class="fa fa-feed"></i>
		<?php esc_html_e('RSS Feed', 'wp-job-board'); ?>
	</a>
</div>