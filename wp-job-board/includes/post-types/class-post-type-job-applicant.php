<?php
/**
 * Post Type: Job Applicant
 *
 * @package    wp-job-board
 * @author     Habq 
 * @license    GNU General Public License, version 3
 */

if ( ! defined( 'ABSPATH' ) ) {
  	exit;
}

class WP_Job_Board_Post_Type_Job_Applicant {
	
	public static function init() {
	  	add_action( 'init', array( __CLASS__, 'register_post_type' ) );

	  	add_filter( 'manage_edit-job_applicant_columns', array( __CLASS__, 'custom_columns' ) );
		add_action( 'manage_job_applicant_posts_custom_column', array( __CLASS__, 'custom_columns_manage' ) );
	}

	public static function register_post_type() {
		$labels = array(
			'name'                  => __( 'Job Applicants', 'wp-job-board' ),
			'singular_name'         => __( 'Job Applicant', 'wp-job-board' ),
			'add_new'               => __( 'Add New Job Applicant', 'wp-job-board' ),
			'add_new_item'          => __( 'Add New Job Applicant', 'wp-job-board' ),
			'edit_item'             => __( 'Edit Job Applicant', 'wp-job-board' ),
			'new_item'              => __( 'New Job Applicant', 'wp-job-board' ),
			'all_items'             => __( 'Job Applicants', 'wp-job-board' ),
			'view_item'             => __( 'View Job Applicant', 'wp-job-board' ),
			'search_items'          => __( 'Search Job Applicant', 'wp-job-board' ),
			'not_found'             => __( 'No Job Applicants found', 'wp-job-board' ),
			'not_found_in_trash'    => __( 'No Job Applicants found in Trash', 'wp-job-board' ),
			'parent_item_colon'     => '',
			'menu_name'             => __( 'Job Applicants', 'wp-job-board' ),
		);

		register_post_type( 'job_applicant',
			array(
				'labels'            => $labels,
				'supports'          => array( 'title' ),
				'public'            => true,
		        'has_archive'       => false,
		        'publicly_queryable' => false,
				'show_in_rest'		=> false,
				'show_in_menu'		=> 'edit.php?post_type=job_listing',
			)
		);
	}

	/**
	 * Custom admin columns for post type
	 *
	 * @access public
	 * @return array
	 */
	public static function custom_columns($columns) {
		if ( isset($columns['comments']) ) {
			unset($columns['comments']);
		}
		if ( isset($columns['date']) ) {
			unset($columns['date']);
		}
		$fields = array_merge($columns, array(
			'thumbnail' 		=> __( 'Thumbnail', 'wp-job-board' ),
			'title' 			=> __( 'Title', 'wp-job-board' ),
			'job_title' 		=> __( 'Job Title', 'wp-job-board' ),
			'candidate' 		=> __( 'View Profile', 'wp-job-board' ),
			'author' 			=> __( 'Author', 'wp-job-board' ),
			'status' 			=> __( 'Status', 'wp-job-board' ),
		));
		return $fields;
	}

	/**
	 * Custom admin columns implementation
	 *
	 * @access public
	 * @param string $column
	 * @return array
	 */
	public static function custom_columns_manage( $column ) {
		switch ( $column ) {
			case 'thumbnail':
				$candidate_id = get_post_meta( get_the_ID(), WP_JOB_BOARD_APPLICANT_PREFIX . 'candidate_id', true );
				if ( has_post_thumbnail($candidate_id) ) {
					echo get_the_post_thumbnail( $candidate_id, 'thumbnail', array(
						'class' => 'attachment-thumbnail attachment-thumbnail-small logo-thumnail',
					) );
				} else {
					echo '-';
				}
				break;
			case 'job_title':
				$job_id = get_post_meta( get_the_ID(), WP_JOB_BOARD_APPLICANT_PREFIX . 'job_id', true );
				?>
				<a href="<?php echo esc_url(get_permalink($job_id)); ?>" target="_blank"><?php echo get_the_title($job_id); ?></a>
				<?php
				break;
			case 'candidate':
				$candidate_id = get_post_meta( get_the_ID(), WP_JOB_BOARD_APPLICANT_PREFIX . 'candidate_id', true );
				?>
				<a href="<?php echo esc_url(get_permalink($candidate_id)); ?>" target="_blank"><?php esc_html_e('View profile', 'wp-job-board'); ?></a>
				<?php
				break;
			case 'status':
				$rejected = WP_Job_Board_Applicant::get_post_meta(get_the_ID(), 'rejected', true);
                $approved = WP_Job_Board_Applicant::get_post_meta(get_the_ID(), 'approved', true);

                if ( $approved ) {
                    echo '<div class="application-status-label approved" style="background: #007cba;color: #fff;border-radius: 3px;padding: 5px 10px; display: inline-block;">'.esc_html__('Approved', 'wp-job-board').'</div>';
                } elseif ( $rejected ) {
                    echo '<div class="application-status-label rejected" style="background: #ca4a1f;color: #fff;border-radius: 3px;padding: 5px 10px;display: inline-block;">'.esc_html__('Rejected', 'wp-job-board').'</div>';
                } else {
                    echo '<div class="application-status-label pending" style="background: #39b54a;color: #fff;border-radius: 3px;padding: 5px 10px;display: inline-block;">'.esc_html__('Pending', 'wp-job-board').'</div>';
                }
				break;

		}
	}

}
WP_Job_Board_Post_Type_Job_Applicant::init();