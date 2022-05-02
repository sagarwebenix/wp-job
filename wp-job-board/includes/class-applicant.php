<?php
/**
 * Applicants
 *
 * @package    wp-job-board
 * @author     Habq 
 * @license    GNU General Public License, version 3
 */

if ( ! defined( 'ABSPATH' ) ) {
  	exit;
}

class WP_Job_Board_Applicant {
	
	public static function init() {
		
		// applicants pagination
		add_action( 'wp_ajax_wp_job_board_ajax_applicants_pagination',  array(__CLASS__, 'process_applicants_pagination') );

		// applicants reject
		add_action( 'wp_ajax_wp_job_board_ajax_reject_applied',  array(__CLASS__, 'process_applicant_reject') );

		// Undo applicants reject
		add_action( 'wp_ajax_wp_job_board_ajax_undo_reject_applied',  array(__CLASS__, 'process_undo_applicant_reject') );

		// show applicants reject
		add_action( 'wp_ajax_wp_job_board_ajax_show_rejected_applicants',  array(__CLASS__, 'show_rejected_applicants') );

		// applicants approve
		add_action( 'wp_ajax_wp_job_board_ajax_approve_applied',  array(__CLASS__, 'process_applicant_approve') );

		// Undo applicants approve
		add_action( 'wp_ajax_wp_job_board_ajax_undo_approve_applied',  array(__CLASS__, 'process_undo_applicant_approve') );

		// show applicants approve
		add_action( 'wp_ajax_wp_job_board_ajax_show_approved_applicants',  array(__CLASS__, 'show_approved_applicants') );

		// show applicants
		add_action( 'wp_ajax_wp_job_board_ajax_show_applicants',  array(__CLASS__, 'show_applicants') );
		add_action( 'wp_ajax_nopriv_wp_job_board_ajax_show_applicants',  array(__CLASS__, 'show_applicants') );

		add_action( 'template_redirect', array(__CLASS__, 'track_job_view') );
	}
	
	public static function get_post_meta($post_id, $key, $single = true) {
		return get_post_meta($post_id, WP_JOB_BOARD_APPLICANT_PREFIX.$key, $single);
	}

	public static function update_post_meta($post_id, $key, $data) {
		return update_post_meta($post_id, WP_JOB_BOARD_APPLICANT_PREFIX.$key, $data);
	}

	public static function process_applicants_pagination() {
		$return = array();
		
		$job_id = !empty($_POST['job_id']) ? $_POST['job_id'] : '';

		if ( empty($job_id) ) {
			$return = array( 'status' => false, 'msg' => esc_html__('Job did not exists.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}

		$current_page = !empty($_POST['paged']) ? $_POST['paged'] : 1;
		$query_vars = array(
			'post_type'         => 'job_applicant',
			'posts_per_page'    => get_option('posts_per_page'),
			'paged'    			=> $current_page,
			'post_status'       => 'publish',
			'meta_query'       => array(
				array(
					'key' => WP_JOB_BOARD_APPLICANT_PREFIX.'job_id',
					'value'     => $job_id,
					'compare'   => '=',
				)
			)
		);

		if ( isset($_POST['job_type']) ) {
			switch ($_POST['job_type']) {
				case 'rejected':
					$query_vars['meta_query'][] = array(
						array(
							'relation' => 'OR',
							array(
								'key' => WP_JOB_BOARD_APPLICANT_PREFIX.'rejected',
								'value'     => '',
								'compare'   => '=',
							),
							array(
								'key' => WP_JOB_BOARD_APPLICANT_PREFIX.'rejected',
								'compare' => 'NOT EXISTS',
							)
						)
					);
					break;
				
				case 'approved':
					$query_vars['meta_query'][] = array(
						array(
							'relation' => 'OR',
							array(
								'key' => WP_JOB_BOARD_APPLICANT_PREFIX.'approved',
								'value'     => '',
								'compare'   => '=',
							),
							array(
								'key' => WP_JOB_BOARD_APPLICANT_PREFIX.'approved',
								'compare' => 'NOT EXISTS',
							)
						)
					);
					break;
			}
		}
		if ( isset($_POST['search']) ) {
			$query_vars['s'] = $_POST['search'];
		}
		if ( isset($_POST['orderby']) ) {
			switch ($_POST['orderby']) {
				case 'menu_order':
					$query_vars['orderby'] = array(
						'menu_order' => 'ASC',
						'date'       => 'DESC',
						'ID'         => 'DESC',
					);
					break;
				case 'newest':
					$query_vars['orderby'] = 'date';
					$query_vars['order'] = 'DESC';
					break;
				case 'oldest':
					$query_vars['orderby'] = 'date';
					$query_vars['order'] = 'ASC';
					break;
			}
		}

		$applicants = new WP_Query($query_vars);
		
		$output = '';
		if ( $applicants->have_posts() ) {
			while ( $applicants->have_posts() ) : $applicants->the_post();
				global $post;
				$rejected = WP_Job_Board_Applicant::get_post_meta($post->ID, 'rejected', true);
		        $approved = WP_Job_Board_Applicant::get_post_meta($post->ID, 'approved', true);
		        if ( $rejected ) {
					$output .= WP_Job_Board_Template_Loader::get_template_part( 'content-rejected-applicant' );
				} elseif ( $approved ) {
					$output .= WP_Job_Board_Template_Loader::get_template_part( 'content-approved-applicant' );
				} else {
					$output .= WP_Job_Board_Template_Loader::get_template_part( 'content-applicant' );
				}
			endwhile;
			wp_reset_postdata();
		}
		$paged = $current_page + 1;
		if ( $applicants->max_num_pages <= $current_page ) {
			$paged = 0;
		}
		$return = array( 'status' => true, 'output' => $output, 'paged' => $paged );
		echo wp_json_encode($return);
	   	exit;
	}

	public static function process_applicant_reject() {
		$return = array();
		if ( !isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'wp-job-board-reject-applied-nonce' )  ) {
			$return = array( 'status' => false, 'msg' => esc_html__('Your nonce did not verify.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}
		$user_id = WP_Job_Board_User::get_user_id();
		if ( !is_user_logged_in() || !WP_Job_Board_User::is_employer($user_id) ) {
			$return = array( 'status' => false, 'msg' => esc_html__('Please login with "Employer" to reject.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}
		
		$applicant_id = !empty($_POST['applicant_id']) ? $_POST['applicant_id'] : '';
		$applicant = get_post($applicant_id);
		if ( !$applicant || empty($applicant->ID) ) {
			$return = array( 'status' => false, 'msg' => esc_html__('Applicant did not exists.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}
		$job_id = self::get_post_meta( $applicant_id, 'job_id', true );
		$author_id = get_post_field ('post_author', $job_id);

		
		if ( $author_id != $user_id ) {
			$return = array( 'status' => false, 'msg' => esc_html__('You can not to reject this applicant.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}

		do_action('wp-job-board-process-applicant-reject', $_POST);

		// process
		self::update_post_meta($applicant_id, 'rejected', 1);

		// send email
		$job = get_post($job_id);
		$candidate_id = self::get_post_meta( $applicant_id, 'candidate_id', true );
		$candidate_name = get_the_title($candidate_id);
		$email_to = WP_Job_Board_Candidate::get_post_meta($candidate_id, 'email', true);
		if ( empty($email_to) ) {
			$email_to = get_option( 'admin_email', false );
		}

		$employer_id = WP_Job_Board_User::get_employer_by_user_id($author_id);

		$employer_name = get_the_title($employer_id);

		$email_from = WP_Job_Board_Employer::get_post_meta($employer_id, 'email', true);
		if ( empty($email_from) ) {
			$email_from = get_option( 'admin_email', false );
		}
		
		$headers = sprintf( "From: %s <%s>\r\n Content-type: text/html", get_bloginfo('name'), $email_from );
		//$email_to = get_option( 'admin_email', false );
		$subject = WP_Job_Board_Email::render_email_vars(array('job' => $job, 'candidate_name' => $candidate_name, 'employer_name' => $employer_name), 'reject_interview_notice', 'subject');
		$content = WP_Job_Board_Email::render_email_vars(array('job' => $job, 'candidate_name' => $candidate_name, 'employer_name' => $employer_name), 'reject_interview_notice', 'content');

		WP_Job_Board_Email::wp_mail( $email_to, $subject, $content, $headers );

		// return
		ob_start();
		setup_postdata( $GLOBALS['post'] =& $applicant );
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
		wp_reset_postdata();

		$output = ob_get_clean();

		$return = array( 'status' => true, 'msg' => esc_html__('Reject this applicant successful.', 'wp-job-board'), 'output' => $output );
	   	echo wp_json_encode($return);
	   	exit;
	}

	public static function process_undo_applicant_reject() {
		$return = array();
		if ( !isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'wp-job-board-undo-reject-applied-nonce' )  ) {
			$return = array( 'status' => false, 'msg' => esc_html__('Your nonce did not verify.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}
		$user_id = WP_Job_Board_User::get_user_id();
		if ( !is_user_logged_in() || !WP_Job_Board_User::is_employer($user_id) ) {
			$return = array( 'status' => false, 'msg' => esc_html__('Please login with "Employer" to reject.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}
		
		$applicant_id = !empty($_POST['applicant_id']) ? $_POST['applicant_id'] : '';
		$applicant = get_post($applicant_id);
		if ( !$applicant || empty($applicant->ID) ) {
			$return = array( 'status' => false, 'msg' => esc_html__('Applicant did not exists.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}
		$job_id = self::get_post_meta( $applicant_id, 'job_id', true );
		$author_id = get_post_field ('post_author', $job_id);

		if ( $author_id != $user_id ) {
			$return = array( 'status' => false, 'msg' => esc_html__('You can not to undo this applicant.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}

		do_action('wp-job-board-process-undo-applicant-reject', $_POST);

		// process
		self::update_post_meta($applicant_id, 'rejected', '');

		// send email
		$job = get_post($job_id);
		$candidate_id = self::get_post_meta( $applicant_id, 'candidate_id', true );
		$candidate_name = get_the_title($candidate_id);
		$email_to = WP_Job_Board_Candidate::get_post_meta($candidate_id, 'email', true);
		if ( empty($email_to) ) {
			$email_to = get_option( 'admin_email', false );
		}

		$employer_id = WP_Job_Board_User::get_employer_by_user_id($author_id);

		$employer_name = get_the_title($employer_id);

		$email_from = WP_Job_Board_Employer::get_post_meta($employer_id, 'email', true);
		if ( empty($email_from) ) {
			$email_from = get_option( 'admin_email', false );
		}
		
		$headers = sprintf( "From: %s <%s>\r\n Content-type: text/html", get_bloginfo('name'), $email_from );
		//$email_to = get_option( 'admin_email', false );
		$subject = WP_Job_Board_Email::render_email_vars(array('job' => $job, 'candidate_name' => $candidate_name, 'employer_name' => $employer_name), 'undo_reject_interview_notice', 'subject');
		$content = WP_Job_Board_Email::render_email_vars(array('job' => $job, 'candidate_name' => $candidate_name, 'employer_name' => $employer_name), 'undo_reject_interview_notice', 'content');

		WP_Job_Board_Email::wp_mail( $email_to, $subject, $content, $headers );

		// return
		ob_start();
		setup_postdata( $GLOBALS['post'] =& $applicant );
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
		wp_reset_postdata();

		$output = ob_get_clean();
		$return = array( 'status' => true, 'msg' => esc_html__('Undo Reject this applicant successful.', 'wp-job-board'), 'output' => $output );
	   	echo wp_json_encode($return);
	   	exit;
	}

	public static function show_rejected_applicants() {
		$return = array();
		if ( !isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'wp-job-board-show-rejected-applicants-nonce' )  ) {
			$return = array( 'status' => false, 'msg' => esc_html__('Your nonce did not verify.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}
		$user_id = WP_Job_Board_User::get_user_id();
		if ( !is_user_logged_in() || !WP_Job_Board_User::is_employer($user_id) ) {
			$return = array( 'status' => false, 'msg' => esc_html__('Please login with "Employer" to reject.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}

		$job_id = isset( $_POST['job_id'] ) ? $_POST['job_id'] : 0;
		$author_id = get_post_field ('post_author', $job_id);

		
		if ( $author_id != $user_id ) {
			$return = array( 'status' => false, 'msg' => esc_html__('Can not to show rejected applicants.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}

		do_action('wp-job-board-process-show-rejected-applicants', $_POST);

		$jids = array();
		$jids = array_merge($jids, WP_Job_Board_WPML::get_all_translations_object_id($job_id));
		
		$current_page = 1;
		$query_vars = array(
			'post_type'         => 'job_applicant',
			'posts_per_page'    => get_option('posts_per_page'),
			'paged'    			=> $current_page,
			'post_status'       => 'publish',
			'meta_query'       => array(
				array(
					'key' => WP_JOB_BOARD_APPLICANT_PREFIX.'job_id',
					'value'     => $jids,
					'compare'   => 'IN',
				),
				array(
					'key' => WP_JOB_BOARD_APPLICANT_PREFIX.'rejected',
					'value'     => '1',
					'compare'   => '=',
				)
			)
		);

		$applicants = new WP_Query($query_vars);
		ob_start();
		if ( $applicants->have_posts() ) {
			?>
			<div class="applicants-inner">
				<?php
				while ( $applicants->have_posts() ) : $applicants->the_post();
					echo WP_Job_Board_Template_Loader::get_template_part( 'content-rejected-applicant' );
				endwhile;
				?>
			</div>
			<?php if ( $applicants->max_num_pages > $current_page ) { ?>
				<form class="applicants-pagination-form">
					<button class="apus-loadmore-btn"><?php esc_html_e( 'Load more', 'wp-job-board' ); ?></button>
					<input type="hidden" name="paged" value="<?php echo esc_attr($current_page + 1); ?>">
					<input type="hidden" name="job_id" value="<?php echo esc_attr($job_id); ?>">
					<input type="hidden" name="job_type" value="rejected">
					<?php WP_Job_Board_Mixes::query_string_form_fields( null, array( 'job_id', 'submit', 'paged' ) ); ?>
				</form>
			<?php } ?>
			
			<?php wp_reset_postdata();
		} else {
			?>
			<div class="no-found"><?php esc_html_e('No rejected applicants found.', 'wp-job-board'); ?></div>
			<?php
		}
		$output = ob_get_clean();
		$return = array( 'status' => true, 'output' => $output );
	   	echo wp_json_encode($return);
	   	exit;
	}

	public static function process_applicant_approve() {
		$return = array();
		if ( !isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'wp-job-board-approve-applied-nonce' )  ) {
			$return = array( 'status' => false, 'msg' => esc_html__('Your nonce did not verify.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}
		$user_id = WP_Job_Board_User::get_user_id();
		if ( !is_user_logged_in() || !WP_Job_Board_User::is_employer($user_id) ) {
			$return = array( 'status' => false, 'msg' => esc_html__('Please login with "Employer" to approve.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}
		
		$applicant_id = !empty($_POST['applicant_id']) ? $_POST['applicant_id'] : '';
		$applicant = get_post($applicant_id);
		if ( !$applicant || empty($applicant->ID) ) {
			$return = array( 'status' => false, 'msg' => esc_html__('Applicant did not exists.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}
		$job_id = self::get_post_meta( $applicant_id, 'job_id', true );
		$author_id = get_post_field ('post_author', $job_id);

		if ( $author_id != $user_id ) {
			$return = array( 'status' => false, 'msg' => esc_html__('You can not to approve this applicant.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}

		do_action('wp-job-board-process-applicant-approve', $_POST);

		// process
		self::update_post_meta($applicant_id, 'approved', 1);

		// send email
		$job = get_post($job_id);
		$candidate_id = self::get_post_meta( $applicant_id, 'candidate_id', true );
		$candidate_name = get_the_title($candidate_id);
		$email_to = WP_Job_Board_Candidate::get_post_meta($candidate_id, 'email', true);
		if ( empty($email_to) ) {
			$email_to = get_option( 'admin_email', false );
		}

		$employer_id = WP_Job_Board_User::get_employer_by_user_id($author_id);

		$employer_name = get_the_title($employer_id);

		$email_from = WP_Job_Board_Employer::get_post_meta($employer_id, 'email', true);
		if ( empty($email_from) ) {
			$email_from = get_option( 'admin_email', false );
		}
		
		$headers = sprintf( "From: %s <%s>\r\n Content-type: text/html", get_bloginfo('name'), $email_from );
		//$email_to = get_option( 'admin_email', false );
		$subject = WP_Job_Board_Email::render_email_vars(array('job' => $job, 'candidate_name' => $candidate_name, 'employer_name' => $employer_name), 'approve_interview_notice', 'subject');
		$content = WP_Job_Board_Email::render_email_vars(array('job' => $job, 'candidate_name' => $candidate_name, 'employer_name' => $employer_name), 'approve_interview_notice', 'content');

		WP_Job_Board_Email::wp_mail( $email_to, $subject, $content, $headers );

		// return
		ob_start();
		setup_postdata( $GLOBALS['post'] =& $applicant );
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
		wp_reset_postdata();

		$output = ob_get_clean();
		$return = array( 'status' => true, 'msg' => esc_html__('Approve this applicant successful.', 'wp-job-board'), 'output' => $output );
	   	echo wp_json_encode($return);
	   	exit;
	}

	public static function process_undo_applicant_approve() {
		$return = array();
		if ( !isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'wp-job-board-undo-approve-applied-nonce' )  ) {
			$return = array( 'status' => false, 'msg' => esc_html__('Your nonce did not verify.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}
		$user_id = WP_Job_Board_User::get_user_id();
		if ( !is_user_logged_in() || !WP_Job_Board_User::is_employer($user_id) ) {
			$return = array( 'status' => false, 'msg' => esc_html__('Please login with "Employer" to approve.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}
		
		$applicant_id = !empty($_POST['applicant_id']) ? $_POST['applicant_id'] : '';
		$applicant = get_post($applicant_id);
		if ( !$applicant || empty($applicant->ID) ) {
			$return = array( 'status' => false, 'msg' => esc_html__('Applicant did not exists.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}
		$job_id = self::get_post_meta( $applicant_id, 'job_id', true );
		$author_id = get_post_field ('post_author', $job_id);

		if ( $author_id != $user_id ) {
			$return = array( 'status' => false, 'msg' => esc_html__('You can not to undo approve this applicant.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}

		do_action('wp-job-board-process-undo-applicant-approve', $_POST);

		// process
		self::update_post_meta($applicant_id, 'approved', '');

		// send email
		$job = get_post($job_id);
		$candidate_id = self::get_post_meta( $applicant_id, 'candidate_id', true );
		$candidate_name = get_the_title($candidate_id);
		$email_to = WP_Job_Board_Candidate::get_post_meta($candidate_id, 'email', true);
		if ( empty($email_to) ) {
			$email_to = get_option( 'admin_email', false );
		}

		$employer_id = WP_Job_Board_User::get_employer_by_user_id($author_id);

		$employer_name = get_the_title($employer_id);

		$email_from = WP_Job_Board_Employer::get_post_meta($employer_id, 'email', true);
		if ( empty($email_from) ) {
			$email_from = get_option( 'admin_email', false );
		}
		
		$headers = sprintf( "From: %s <%s>\r\n Content-type: text/html", get_bloginfo('name'), $email_from );
		//$email_to = get_option( 'admin_email', false );
		$subject = WP_Job_Board_Email::render_email_vars(array('job' => $job, 'candidate_name' => $candidate_name, 'employer_name' => $employer_name), 'undo_approve_interview_notice', 'subject');
		$content = WP_Job_Board_Email::render_email_vars(array('job' => $job, 'candidate_name' => $candidate_name, 'employer_name' => $employer_name), 'undo_approve_interview_notice', 'content');

		WP_Job_Board_Email::wp_mail( $email_to, $subject, $content, $headers );

		// return
		ob_start();
		setup_postdata( $GLOBALS['post'] =& $applicant );
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
		wp_reset_postdata();

		$output = ob_get_clean();
		$return = array( 'status' => true, 'msg' => esc_html__('Undo Approve this applicant successful.', 'wp-job-board'), 'output' => $output );
	   	echo wp_json_encode($return);
	   	exit;
	}

	public static function show_approved_applicants() {
		$return = array();
		if ( !isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'wp-job-board-show-approved-applicants-nonce' )  ) {
			$return = array( 'status' => false, 'msg' => esc_html__('Your nonce did not verify.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}
		$user_id = WP_Job_Board_User::get_user_id();
		if ( !is_user_logged_in() || !WP_Job_Board_User::is_employer($user_id) ) {
			$return = array( 'status' => false, 'msg' => esc_html__('Please login with "Employer" to approve.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}

		$job_id = isset( $_POST['job_id'] ) ? $_POST['job_id'] : 0;
		$author_id = get_post_field ('post_author', $job_id);

		if ( $author_id != $user_id ) {
			$return = array( 'status' => false, 'msg' => esc_html__('Can not to show approved applicants.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}

		do_action('wp-job-board-process-show-approved-applicants', $_POST);

		$jids = array();
		$jids = array_merge($jids, WP_Job_Board_WPML::get_all_translations_object_id($job_id));
		
		$current_page = 1;
		$query_vars = array(
			'post_type'         => 'job_applicant',
			'posts_per_page'    => get_option('posts_per_page'),
			'paged'    			=> $current_page,
			'post_status'       => 'publish',
			'meta_query'       => array(
				array(
					'key' => WP_JOB_BOARD_APPLICANT_PREFIX.'job_id',
					'value'     => $jids,
					'compare'   => 'IN',
				),
				array(
					'key' => WP_JOB_BOARD_APPLICANT_PREFIX.'approved',
					'value'     => '1',
					'compare'   => '=',
				)
			)
		);

		$applicants = new WP_Query($query_vars);
		ob_start();
		if ( $applicants->have_posts() ) {
			?>
			<div class="applicants-inner">
				<?php
				while ( $applicants->have_posts() ) : $applicants->the_post();
					echo WP_Job_Board_Template_Loader::get_template_part( 'content-approved-applicant' );
				endwhile;
				?>
			</div>
			<?php if ( $applicants->max_num_pages > $current_page ) { ?>
				<form class="applicants-pagination-form">
					<button class="apus-loadmore-btn"><?php esc_html_e( 'Load more', 'wp-job-board' ); ?></button>
					<input type="hidden" name="paged" value="<?php echo esc_attr($current_page + 1); ?>">
					<input type="hidden" name="job_id" value="<?php echo esc_attr($job_id); ?>">
					<input type="hidden" name="job_type" value="approved">
					<?php WP_Job_Board_Mixes::query_string_form_fields( null, array( 'job_id', 'submit', 'paged' ) ); ?>
				</form>
			<?php } ?>
			
			<?php wp_reset_postdata();
		} else {
			?>
			<div class="no-found"><?php esc_html_e('No approved applicants found.', 'wp-job-board'); ?></div>
			<?php
		}
		$output = ob_get_clean();
		$return = array( 'status' => true, 'output' => $output );
	   	echo wp_json_encode($return);
	   	exit;
	}

	public static function show_applicants() {
		$return = array();
		if ( !isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'wp-job-board-show-applicants-nonce' )  ) {
			$return = array( 'status' => false, 'msg' => esc_html__('Your nonce did not verify.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}
		$user_id = WP_Job_Board_User::get_user_id();
		if ( !is_user_logged_in() || !WP_Job_Board_User::is_employer($user_id) ) {
			$return = array( 'status' => false, 'msg' => esc_html__('Please login with "Employer" to show applications.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}

		$job_id = isset( $_POST['job_id'] ) ? $_POST['job_id'] : 0;
		$author_id = get_post_field ('post_author', $job_id);

		if ( $author_id != $user_id ) {
			$return = array( 'status' => false, 'msg' => esc_html__('Can not to show applications.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}

		do_action('wp-job-board-process-show-applicants', $_POST);

		$jids = array();
		$jids = array_merge($jids, WP_Job_Board_WPML::get_all_translations_object_id($job_id));
		
		$current_page = 1;
		$query_vars = array(
			'post_type'         => 'job_applicant',
			'posts_per_page'    => get_option('posts_per_page'),
			'paged'    			=> $current_page,
			'post_status'       => 'publish',
			'meta_query'       => array(
				array(
					'key' => WP_JOB_BOARD_APPLICANT_PREFIX.'job_id',
					'value'     => $jids,
					'compare'   => 'IN',
				)
			)
		);

		$applicants = new WP_Query($query_vars);
		ob_start();
		if ( $applicants->have_posts() ) {
			?>
			<div class="applicants-inner">
				<?php
				while ( $applicants->have_posts() ) : $applicants->the_post();
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
				?>
			</div>
			<?php if ( $applicants->max_num_pages > $current_page ) { ?>
				<form class="applicants-pagination-form">
					<button class="apus-loadmore-btn"><?php esc_html_e( 'Load more', 'wp-job-board' ); ?></button>
					<input type="hidden" name="paged" value="<?php echo esc_attr($current_page + 1); ?>">
					<input type="hidden" name="job_id" value="<?php echo esc_attr($job_id); ?>">
					<?php WP_Job_Board_Mixes::query_string_form_fields( null, array( 'job_id', 'submit', 'paged' ) ); ?>
				</form>
			<?php } ?>
			
			<?php wp_reset_postdata();
		} else {
			?>
			<div class="no-found"><?php esc_html_e('No applicants found.', 'wp-job-board'); ?></div>
			<?php
		}
		$output = ob_get_clean();
		$return = array( 'status' => true, 'output' => $output );
	   	echo wp_json_encode($return);
	   	exit;
	}

	public static function track_job_view() {
	    if ( !is_singular( 'candidate' ) ) {
	        return;
	    }

	    if ( empty($_GET['applicant_id']) || empty($_GET['candidate_id']) || empty($_GET['action']) || $_GET['action'] != 'view-profile' ) {
	    	return;
	    }
	    global $post;
	    
	    if ( $_GET['candidate_id'] == $post->ID ) {
	    	self::update_post_meta($_GET['applicant_id'], 'viewed', 1);
	    }
	}

	public static function get_all_applicants_by_employer($user_id) {
		$user_id = WP_Job_Board_User::get_user_id($user_id);
		if ( WP_Job_Board_User::is_employer($user_id) ) {
			$query_vars = array(
				'post_type'     => 'job_listing',
				'post_status'   => 'publish',
				'paged'         => 1,
				'author'        => $user_id,
				'fields' => 'ids',
				'posts_per_page'    => -1,
			);
			$jobs = WP_Job_Board_Query::get_posts($query_vars);

			if ( !empty($jobs) && !empty($jobs->posts) ) {
				$query_vars = array(
				    'post_type' => 'job_applicant',
				    'posts_per_page'    => -1,
				    'paged'    			=> 1,
				    'post_status' => 'publish',
				    'fields' => 'ids',
				    'meta_query' => array(
					    array(
					    	'key' => WP_JOB_BOARD_APPLICANT_PREFIX . 'job_id',
					    	'value' => $jobs->posts,
					    	'compare' => 'IN',
					    ),
					)
				);
				$applicants = WP_Job_Board_Query::get_posts($query_vars);

				if ( !empty($applicants) && !empty($applicants->posts) ) {
					return $applicants->posts;
				}
			}
		}
		return array();
	}

	public static function get_all_applicants_by_candidate($user_id) {
		if ( WP_Job_Board_User::is_candidate($user_id) ) {
			$query_vars = array(
			    'post_type' => 'job_applicant',
			    'posts_per_page'    => -1,
			    'paged'    			=> 1,
			    'post_status' => 'publish',
			    'fields' => 'ids',
			    'author' => $user_id
			);
			$applicants = WP_Job_Board_Query::get_posts($query_vars);

			if ( !empty($applicants) && !empty($applicants->posts) ) {
				return $applicants->posts;
			}
		}
		return array();
	}
}

WP_Job_Board_Applicant::init();