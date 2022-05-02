<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


$jobs = WP_Job_Board_Query::get_posts(array(
    'post_type' => 'job_listing',
    'post_status' => 'publish',
    'author' => $user_id,
    'fields' => 'ids'
));
$count_jobs = $jobs->post_count;
$shortlist = get_post_meta($employer_id, WP_JOB_BOARD_EMPLOYER_PREFIX.'shortlist', true);
$shortlist = is_array($shortlist) ? count($shortlist) : 0;
$total_reviews = WP_Job_Board_Review::get_total_reviews($employer_id);
$views = get_post_meta($employer_id, WP_JOB_BOARD_EMPLOYER_PREFIX.'views_count', true);
?>

<div class="employer-dashboard-wrapper">
	<h3 class="title"><?php esc_html_e('Applications statistics', 'wp-job-board'); ?></h3>
	<div class="statistics">
		<div class="posted-jobs">
			<h4><?php esc_html_e('Posted Jobs', 'wp-job-board'); ?></h4>
			<div class="jobs-count"><?php echo WP_Job_Board_Mixes::format_number($count_jobs); ?></div>
		</div>
		<div class="shortlist">
			<h4><?php esc_html_e('Shortlisted', 'wp-job-board'); ?></h4>
			<div class="jobs-count"><?php echo WP_Job_Board_Mixes::format_number($shortlist); ?></div>
		</div>
		<div class="review-count-wrapper">
			<h4><?php esc_html_e('Review', 'wp-job-board'); ?></h4>
			<div class="review-count"><?php echo WP_Job_Board_Mixes::format_number($total_reviews); ?></div>
		</div>
		<div class="views-count-wrapper">
			<h4><?php esc_html_e('Views', 'wp-job-board'); ?></h4>
			<div class="views-count"><?php echo WP_Job_Board_Mixes::format_number($views); ?></div>
		</div>
	</div>

	<h3 class="title"><?php esc_html_e('Recent Applicants', 'wp-job-board'); ?></h3>
	<div class="applicants">
		<?php
			$jobs_loop = WP_Job_Board_Query::get_posts(array(
				'post_type' => 'job_listing',
				'fields' => 'ids',
				'author' => $user_id,
			));
			$job_ids = array();
			if ( !empty($jobs_loop) && !empty($jobs_loop->posts) ) {
				$job_ids = $jobs_loop->posts;
			}

			if ( $job_ids ) {
				$jids = array();
				foreach ($job_ids as $id) {
					$jids = array_merge($jids, WP_Job_Board_WPML::get_all_translations_object_id($id));
				}
				$query_args = array(
					'post_type'         => 'job_applicant',
					'posts_per_page'    => 5,
					'post_status'       => 'publish',
					'meta_query'       => array(
						array(
							'key' => WP_JOB_BOARD_APPLICANT_PREFIX.'job_id',
							'value'     => $jids,
							'compare'   => 'IN',
						)
					)
				);

				$jobs = new WP_Query($query_args);
				if ( $jobs->have_posts() ) {
					while ( $jobs->have_posts() ) : $jobs->the_post();
						global $post;
						$rejected = WP_Job_Board_Applicant::get_post_meta($post->ID, 'rejected', true);
	                    $approved = WP_Job_Board_Applicant::get_post_meta($post->ID, 'approved', true);
	                    if ( $rejected ) {
							echo WP_Job_Board_Template_Loader::get_template_part( 'content-rejected-applicant' );
						} elseif ( $approved ) {
							echo WP_Job_Board_Template_Loader::get_template_part( 'content-approved-applicant' );
						} else {
							echo WP_Job_Board_Template_Loader::get_template_part( 'content-applicant' );
						}
					endwhile;
					wp_reset_postdata();
				} else {
					?>
					<div class="no-found"><?php esc_html_e('No applicants found.', 'wp-job-board'); ?></div>
					<?php
				}
			} else {
				?>
				<div class="no-found"><?php esc_html_e('No applicants found.', 'wp-job-board'); ?></div>
				<?php
			}
		?>
	</div>
</div>
