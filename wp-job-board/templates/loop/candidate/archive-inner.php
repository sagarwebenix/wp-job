<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="candidates-listing-wrapper">
	<?php
	/**
	 * wp_job_board_before_candidate_archive
	 */
	do_action( 'wp_job_board_before_candidate_archive', $candidates );
	?>
	<?php
	if ( !empty($candidates) && !empty($candidates->posts) ) {

		/**
		 * wp_job_board_before_loop_candidate
		 */
		do_action( 'wp_job_board_before_loop_candidate', $candidates );
		?>

		<div class="candidates-wrapper">
			<?php while ( $candidates->have_posts() ) : $candidates->the_post(); ?>
				<?php print_r($candidates); ?>
				<?php echo WP_Job_Board_Template_Loader::get_template_part( 'candidates-styles/inner-grid' ); ?>
			<?php endwhile;?>
		</div>

		<?php
		/**
		 * wp_job_board_after_loop_candidate
		 */
		do_action( 'wp_job_board_after_loop_candidate', $candidates );

		WP_Job_Board_Mixes::custom_pagination( array(
			'max_num_pages' => $candidates->max_num_pages,
			'prev_text'          => __( 'Previous page', 'wp-job-board' ),
			'next_text'          => __( 'Next page', 'wp-job-board' ),
			'wp_query' => $candidates
		));

		wp_reset_postdata();
	?>

	<?php } else { ?>
		<div class="not-found"><?php esc_html_e('No candidate found.', 'wp-job-board'); ?></div>
	<?php } ?>

	<?php
	/**
	 * wp_job_board_after_candidate_archive
	 */
	do_action( 'wp_job_board_after_candidate_archive', $candidates );
	?>
</div>