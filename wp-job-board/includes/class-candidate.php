<?php
/**
 * Candidate
 *
 * @package    wp-job-board
 * @author     Habq 
 * @license    GNU General Public License, version 3
 */

if ( ! defined( 'ABSPATH' ) ) {
  	exit;
}

class WP_Job_Board_Candidate {
	
	public static function init() {
		// apply job email
		add_action( 'wp_ajax_wp_job_board_ajax_apply_email',  array(__CLASS__,'process_apply_email') );
		add_action( 'wp_ajax_nopriv_wp_job_board_ajax_apply_email',  array(__CLASS__,'process_apply_email') );

		// apply job internal
		add_action( 'wp_ajax_wp_job_board_ajax_apply_internal',  array(__CLASS__,'process_apply_internal') );
		add_action( 'wp_ajax_nopriv_wp_job_board_ajax_apply_internal',  array(__CLASS__,'process_apply_internal') );

		// removed job internal
		add_action( 'wp_ajax_wp_job_board_ajax_remove_applied',  array(__CLASS__,'process_remove_applied') );
		add_action( 'wp_ajax_nopriv_wp_job_board_ajax_remove_applied',  array(__CLASS__,'process_remove_applied') );

		// add_job_shortlist
		add_action( 'wp_ajax_wp_job_board_ajax_add_job_shortlist',  array(__CLASS__,'process_add_job_shortlist') );
		add_action( 'wp_ajax_nopriv_wp_job_board_ajax_add_job_shortlist',  array(__CLASS__,'process_add_job_shortlist') );

		// remove job shortlist
		add_action( 'wp_ajax_wp_job_board_ajax_remove_job_shortlist',  array(__CLASS__,'process_remove_job_shortlist') );
		add_action( 'wp_ajax_nopriv_wp_job_board_ajax_remove_job_shortlist',  array(__CLASS__,'process_remove_job_shortlist') );

		// wp_job_board_ajax_follow_employer
		add_action( 'wp_ajax_wp_job_board_ajax_follow_employer',  array(__CLASS__,'process_follow_employer') );
		add_action( 'wp_ajax_nopriv_wp_job_board_ajax_follow_employer',  array(__CLASS__,'process_follow_employer') );

		add_action( 'wp_ajax_wp_job_board_ajax_blacklist_employer',  array(__CLASS__,'process_blacklist_employer') );
		add_action( 'wp_ajax_nopriv_wp_job_board_ajax_blacklist_employer',  array(__CLASS__,'process_blacklist_employer') );

		

		add_action( 'wp_ajax_wp_job_board_ajax_unfollow_employer',  array(__CLASS__,'process_unfollow_employer') );
		add_action( 'wp_ajax_nopriv_wp_job_board_ajax_unfollow_employer',  array(__CLASS__,'process_unfollow_employer') );

		add_action( 'wp_ajax_wp_job_board_ajax_removeblacklist_employer',  array(__CLASS__,'process_removeblacklist_employer') );
		add_action( 'wp_ajax_nopriv_wp_job_board_ajax_removeblacklist_employer',  array(__CLASS__,'process_removeblacklist_employer') );

		// download cv
		add_action('wp_ajax_wp_job_board_ajax_download_file', array( __CLASS__, 'process_download_file' ) );
		add_action('wp_ajax_nopriv_wp_job_board_ajax_download_file', array( __CLASS__, 'process_download_file' ) );

		// download cv
		add_action('wp_ajax_wp_job_board_ajax_download_cv', array( __CLASS__, 'process_download_cv' ) );
		add_action('wp_ajax_nopriv_wp_job_board_ajax_download_cv', array( __CLASS__, 'process_download_cv' ) );
		
		// download resume cv
		add_action('wp_ajax_wp_job_board_ajax_download_resume_cv', array( __CLASS__, 'process_download_resume_cv' ) );
		add_action('wp_ajax_nopriv_wp_job_board_ajax_download_resume_cv', array( __CLASS__, 'process_download_resume_cv' ) );

		// loop
		add_action( 'wp_job_board_before_candidate_archive', array( __CLASS__, 'display_candidates_results_filters' ), 5 );
		add_action( 'wp_job_board_before_candidate_archive', array( __CLASS__, 'display_candidates_count_results' ), 10 );

		add_action( 'wp_job_board_before_candidate_archive', array( __CLASS__, 'display_candidates_alert_orderby_start' ), 15 );
		add_action( 'wp_job_board_before_candidate_archive', array( __CLASS__, 'display_candidates_alert_form' ), 20 );
		add_action( 'wp_job_board_before_candidate_archive', array( __CLASS__, 'display_candidates_orderby' ), 25 );
		add_action( 'wp_job_board_before_candidate_archive', array( __CLASS__, 'display_candidates_alert_orderby_end' ), 100 );

		// restrict
		add_action( 'wp-job-board-candidate-query-args', array( __CLASS__, 'candidate_restrict_listing_query_args'), 100, 2 );
		add_action( 'wp-job-board-candidate-filter-query', array( __CLASS__, 'candidate_restrict_listing_query'), 100, 2 );

		add_action( 'wp_job_board_after_candidate_archive', array( __CLASS__, 'restrict_candidate_listing_information' ), 10 );
	}
	
	public static function send_admin_expiring_notice() {
		global $wpdb;

		if ( !wp_job_board_get_option('admin_notice_expiring_candidate') ) {
			return;
		}
		$days_notice = wp_job_board_get_option('admin_notice_expiring_candidate_days');

		$candidate_ids = self::get_expiring_candidates($days_notice);

		if ( $candidate_ids ) {
			foreach ( $candidate_ids as $job_id ) {
				// send email here.
				$job = get_post($job_id);
				$email_from = get_option( 'admin_email', false );
				
				$headers = sprintf( "From: %s <%s>\r\n Content-type: text/html", get_bloginfo('name'), $email_from );
				$email_to = get_option( 'admin_email', false );
				$subject = WP_Job_Board_Email::render_email_vars(array('job' => $job), 'admin_notice_expiring_candidate', 'subject');
				$content = WP_Job_Board_Email::render_email_vars(array('job' => $job), 'admin_notice_expiring_candidate', 'content');
				
				WP_Job_Board_Email::wp_mail( $email_to, $subject, $content, $headers );
			}
		}
	}

	public static function send_candidate_expiring_notice() {
		global $wpdb;

		if ( !wp_job_board_get_option('candidate_notice_expiring_candidate') ) {
			return;
		}
		$days_notice = wp_job_board_get_option('candidate_notice_expiring_candidate_days');

		$candidate_ids = self::get_expiring_candidates($days_notice);

		if ( $candidate_ids ) {
			foreach ( $candidate_ids as $job_id ) {
				// send email here.
				$job = get_post($job_id);
				$email_from = get_option( 'admin_email', false );
				
				$headers = sprintf( "From: %s <%s>\r\n Content-type: text/html", get_bloginfo('name'), $email_from );
				$email_to = get_the_author_meta( 'user_email', $job->post_author );
				$subject = WP_Job_Board_Email::render_email_vars(array('job' => $job), 'employer_notice_expiring_listing', 'subject');
				$content = WP_Job_Board_Email::render_email_vars(array('job' => $job), 'employer_notice_expiring_listing', 'content');
				
				WP_Job_Board_Email::wp_mail( $email_to, $subject, $content, $headers );
				
			}
		}
	}

	public static function get_expiring_candidates($days_notice) {
		$prefix = WP_JOB_BOARD_CANDIDATE_PREFIX;

		$notice_before_ts = current_time( 'timestamp' ) + ( DAY_IN_SECONDS * $days_notice );
		$candidate_ids          = $wpdb->get_col( $wpdb->prepare(
			"
			SELECT postmeta.post_id FROM {$wpdb->postmeta} as postmeta
			LEFT JOIN {$wpdb->posts} as posts ON postmeta.post_id = posts.ID
			WHERE postmeta.meta_key = %s
			AND postmeta.meta_value = %s
			AND posts.post_status = 'publish'
			AND posts.post_type = 'candidate'
			",
			$prefix.'expiry_date',
			date( 'Y-m-d', $notice_before_ts )
		) );

		return $candidate_ids;
	}

	public static function check_for_expired_candidates() {
		global $wpdb;

		$prefix = WP_JOB_BOARD_CANDIDATE_PREFIX;
		
		// Change status to expired.
		$candidate_ids = $wpdb->get_col(
			$wpdb->prepare( "
				SELECT postmeta.post_id FROM {$wpdb->postmeta} as postmeta
				LEFT JOIN {$wpdb->posts} as posts ON postmeta.post_id = posts.ID
				WHERE postmeta.meta_key = %s
				AND postmeta.meta_value > 0
				AND postmeta.meta_value < %s
				AND posts.post_status = 'publish'
				AND posts.post_type = 'candidate'",
				$prefix.'expiry_date',
				date( 'Y-m-d', current_time( 'timestamp' ) )
			)
		);

		if ( $candidate_ids ) {
			foreach ( $candidate_ids as $job_id ) {
				$job_data                = array();
				$job_data['ID']          = $job_id;
				$job_data['post_status'] = 'expired';
				wp_update_post( $job_data );
			}
		}

		// Delete old expired jobs.
		if ( apply_filters( 'wp_job_board_delete_expired_candidates', false ) ) {
			$candidate_ids = $wpdb->get_col(
				$wpdb->prepare( "
					SELECT posts.ID FROM {$wpdb->posts} as posts
					WHERE posts.post_type = 'candidate'
					AND posts.post_modified < %s
					AND posts.post_status = 'expired'",
					date( 'Y-m-d', strtotime( '-' . apply_filters( 'wp_job_board_delete_expired_candidates_days', 30 ) . ' days', current_time( 'timestamp' ) ) )
				)
			);

			if ( $candidate_ids ) {
				foreach ( $candidate_ids as $job_id ) {
					wp_trash_post( $job_id );
				}
			}
		}
	}

	public static function is_candidate_status_changing( $from_status, $to_status ) {
		return isset( $_POST['post_status'] ) && isset( $_POST['original_post_status'] ) && $_POST['original_post_status'] !== $_POST['post_status'] && ( null === $from_status || $from_status === $_POST['original_post_status'] ) && $to_status === $_POST['post_status'];
	}

	public static function calculate_candidate_expiry( $candidate_id ) {
		$duration = absint( wp_job_board_get_option( 'resume_duration' ) );
		$duration = apply_filters( 'wp-job-board-calculate-candidate-expiry', $duration, $candidate_id);

		if ( $duration ) {
			return date( 'Y-m-d', strtotime( "+{$duration} days", current_time( 'timestamp' ) ) );
		}

		return '';
	}

	public static function get_post_meta($post_id, $key, $single = true) {
		return get_post_meta($post_id, WP_JOB_BOARD_CANDIDATE_PREFIX.$key, $single);
	}

	public static function update_post_meta($post_id, $key, $data) {
		return update_post_meta($post_id, WP_JOB_BOARD_CANDIDATE_PREFIX.$key, $data);
	}

	public static function get_salary_html( $post_id = null ) {
		$min_salary = self::get_min_salary_html($post_id);
		$max_salary = self::get_max_salary_html($post_id);
		$price_html = '';
		if ( $min_salary ) {
			$price_html = $min_salary;
		}
		if ( $max_salary ) {
			$price_html .= ' - '.$max_salary;
		}
		if ( $price_html ) {
			$salary_type = self::get_post_meta( $post_id, 'salary_type' );

			switch ($salary_type) {
				case 'yearly':
					$price_html = $price_html.' '.esc_html__('per year', 'wp-job-board');
					break;
				case 'monthly':
					$price_html = $price_html.' '.esc_html__('per month', 'wp-job-board');
					break;
				case 'weekly':
					$price_html = $price_html.' '.esc_html__('per week', 'wp-job-board');
					break;
				case 'hourly':
					$price_html = $price_html.' '.esc_html__('per hour', 'wp-job-board');
					break;
			}
		}
		return apply_filters( 'wp-job-board-get-salary-html', $price_html, $post_id );
	}

	public static function get_min_salary_html( $post_id = null ) {
		if ( null == $post_id ) {
			$post_id = get_the_ID();
		}

		$price = self::get_post_meta( $post_id, 'salary' );

		if ( empty( $price ) || ! is_numeric( $price ) ) {
			return false;
		}

		$price = WP_Job_Board_Price::format_price( $price );

		return apply_filters( 'wp-job-board-get-min-salary-html', $price, $post_id );
	}

	public static function get_max_salary_html( $post_id = null ) {
		if ( null == $post_id ) {
			$post_id = get_the_ID();
		}

		$price = self::get_post_meta( $post_id, 'max_salary' );

		if ( empty( $price ) || ! is_numeric( $price ) ) {
			return false;
		}

		$price = WP_Job_Board_Price::format_price( $price );

		return apply_filters( 'wp-job-board-get-max-salary-html', $price, $post_id );
	}
	
	public static function is_featured( $post_id = null ) {
		if ( null == $post_id ) {
			$post_id = get_the_ID();
		}
		$featured = self::get_post_meta( $post_id, 'featured' );
		$return = $featured ? true : false;
		return apply_filters( 'wp-job-board-job-listing-is-featured', $return, $post_id );
	}

	public static function is_urgent( $post_id = null ) {
		if ( null == $post_id ) {
			$post_id = get_the_ID();
		}
		$urgent = self::get_post_meta( $post_id, 'urgent' );
		$return = $urgent ? true : false;
		return apply_filters( 'wp-job-board-job-listing-is-urgent', $return, $post_id );
	}

	public static function is_filled( $post_id = null ) {
		if ( null == $post_id ) {
			$post_id = get_the_ID();
		}
		$filled = self::get_post_meta( $post_id, 'filled' );
		$return = $filled ? true : false;
		return apply_filters( 'wp-job-board-job-listing-is-filled', $return, $post_id );
	}
	
	public static function display_shortlist_btn( $post_id = null ) {
		if ( null == $post_id ) {
			$post_id = get_the_ID();
		}

		if ( WP_Job_Board_Employer::check_added_shortlist($post_id) ) {
			$classes = 'btn btn-block btn-added-candidate-shortlist';
			$nonce = wp_create_nonce( 'wp-job-board-remove-candidate-shortlist-nonce' );
			$text = esc_html__('Shortlisted', 'wp-job-board');
		} else {
			$classes = 'btn btn-block btn-add-candidate-shortlist';
			$nonce = wp_create_nonce( 'wp-job-board-add-candidate-shortlist-nonce' );
			$text = esc_html__('Shortlist', 'wp-job-board');
		}
		?>
		<a href="javascript:void(0);" class="<?php echo esc_attr($classes); ?>" data-candidate_id="<?php echo esc_attr($post_id); ?>" data-nonce="<?php echo esc_attr($nonce); ?>"><?php echo esc_html($text); ?></a>
		<?php
	}
	
	public static function display_shortlist_link( $post_id = null ) {
		if ( null == $post_id ) {
			$post_id = get_the_ID();
		}

		if ( WP_Job_Board_Employer::check_added_shortlist($post_id) ) {
			$classes = 'btn-added-candidate-shortlist';
			$nonce = wp_create_nonce( 'wp-job-board-remove-candidate-shortlist-nonce' );
			$text = esc_html__('Shortlisted', 'wp-job-board');
		} else {
			$classes = 'btn-add-candidate-shortlist';
			$nonce = wp_create_nonce( 'wp-job-board-add-candidate-shortlist-nonce' );
			$text = esc_html__('Shortlist', 'wp-job-board');
		}
		?>
		<a href="javascript:void(0);" class="<?php echo esc_attr($classes); ?>" data-candidate_id="<?php echo esc_attr($post_id); ?>" data-nonce="<?php echo esc_attr($nonce); ?>"><?php echo esc_html($text); ?></a>
		<?php
	}

	public static function display_download_cv_btn( $post_id = null, $classes = 'btn btn-theme btn-block btn-download-cv' ) {
		if ( null == $post_id ) {
			$post_id = get_the_ID();
		}
		$admin_url = admin_url( 'admin-ajax.php' );
		$download_url = add_query_arg(array('action' => 'wp_job_board_ajax_download_resume_cv', 'post_id' => $post_id), $admin_url);

		$check_can_download = true;
		if ( !is_user_logged_in() ) {
			$check_can_download = false;
		} else {
			if ( !WP_Job_Board_User::is_employer() ) {
				$check_can_download = false;
				$user_id = WP_Job_Board_User::get_user_id();
				if ( get_post_field( 'post_author', $post_id ) == $user_id ) {
					$check_can_download = true;
				}
			}
		}
		$msg = '';
		$additional_class = $classes;
		if ( !$check_can_download ) {
			$additional_class .= ' cannot-download-cv-btn ';
			$msg = esc_html__('Please login with "Employer" to download CV.', 'wp-job-board');
		}

		?>
		<a href="<?php echo esc_url($download_url); ?>" class="<?php echo esc_attr($additional_class); ?>" data-msg="<?php echo esc_attr($msg); ?>"><?php esc_html_e('Download CV', 'wp-job-board'); ?></a>
		<?php
	}

	
	public static function process_apply_email() {
		$return = array();
		if (  !isset( $_POST['wp-job-board-apply-email-nonce'] ) || ! wp_verify_nonce( $_POST['wp-job-board-apply-email-nonce'], 'wp-job-board-apply-email' )  ) {
			$return = array( 'status' => false, 'msg' => esc_html__('Your nonce did not verify.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}
		if ( WP_Job_Board_Recaptcha::is_recaptcha_enabled() ) {
			$is_recaptcha_valid = array_key_exists( 'g-recaptcha-response', $_POST ) ? WP_Job_Board_Recaptcha::is_recaptcha_valid( sanitize_text_field( $_POST['g-recaptcha-response'] ) ) : false;
			if ( !$is_recaptcha_valid ) {
				$return = array( 'status' => false, 'msg' => esc_html__('Your recaptcha did not verify.', 'wp-job-board') );
			   	echo wp_json_encode($return);
			   	exit;
			}
		}

		$fullname = !empty($_POST['fullname']) ? $_POST['fullname'] : '';
		$email = !empty($_POST['email']) ? $_POST['email'] : '';
		$phone = !empty($_POST['phone']) ? $_POST['phone'] : '';
		$message = !empty($_POST['message']) ? $_POST['message'] : '';
		$job_id = !empty($_POST['job_id']) ? $_POST['job_id'] : '';

		if ( empty($fullname) || empty($email) || empty($message) || empty($job_id) ) {
			$return = array( 'status' => false, 'msg' => esc_html__('Form has been not filled correctly.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}
		$post = get_post($job_id);
		if ( !$post || empty($post->ID) ) {
			$return = array( 'status' => false, 'msg' => esc_html__('Job did not exists.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}
		
		do_action('wp-job-board-process-apply-email', $_POST);

		// cv file
        $cv_file_url = '';
        if ( !empty($_FILES['cv_file']) && !empty($_FILES['cv_file']['name']) ) {
            $file_data = WP_Job_Board_Image::upload_cv_file($_FILES['cv_file']);
            if ( $file_data && !empty($file_data->url) ) {
            	$attach_id = WP_Job_Board_Image::create_attachment( $file_data->url, 0 );

            	$admin_url = admin_url( 'admin-ajax.php' );
            	$cv_file_url = add_query_arg(array('action' => 'wp_job_board_ajax_download_file', 'file_id' => $attach_id), $admin_url);
            }
        }

        $email_subject = WP_Job_Board_Email::render_email_vars( array('job_title' => $post->post_title), 'email_apply_job_notice', 'subject');
        $email_content_args = array(
        	'job_title' => $post->post_title,
        	'message' => sanitize_text_field($message),
        	'fullname' => $fullname,
        	'email' => $email,
        	'phone' => $phone,
        	'cv_file_url' => $cv_file_url,
        );
        $email_content = WP_Job_Board_Email::render_email_vars( $email_content_args, 'email_apply_job_notice', 'content');
		
        $headers = sprintf( "From: %s <%s>\r\n Content-type: text/html", $fullname, $email );
        
        $author_email = get_post_meta( $post->ID, WP_JOB_BOARD_JOB_LISTING_PREFIX.'apply_email', true);
		if ( empty($author_email) ) {
			$author_email = get_post_meta( $post->ID, WP_JOB_BOARD_JOB_LISTING_PREFIX.'email', true);
		}
		if ( empty($author_email) ) {
			$author_email = get_the_author_meta( 'user_email', $post->post_author );
		}

		$result = WP_Job_Board_Email::wp_mail( $author_email, $email_subject, $email_content, $headers );
		if ( $result ) {
			$return = array( 'status' => true, 'msg' => esc_html__('Apply job successfully.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		} else {
			$return = array( 'status' => false, 'msg' => esc_html__('Apply job error.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}
	}

	public static function process_apply_internal() {
		$return = array();
		if ( !isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'wp-job-board-apply-internal-nonce' )  ) {
			$return = array( 'status' => false, 'msg' => esc_html__('Your nonce did not verify.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}
		if ( !is_user_logged_in() || !WP_Job_Board_User::is_candidate() ) {
			$return = array( 'status' => false, 'msg' => esc_html__('Please login with "Candidate" to apply.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}
		$job_id = !empty($_POST['job_id']) ? $_POST['job_id'] : '';
		$job = get_post($job_id);

		if ( !$job || empty($job->ID) ) {
			$return = array( 'status' => false, 'msg' => esc_html__('Job did not exists.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}
		$filled = WP_Job_Board_Job_Listing::get_post_meta($job->ID, 'filled', true);
		if ( $filled ) {
			$return = array( 'status' => false, 'msg' => esc_html__('This job is filled and no longer accepting applications.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}

		$free_apply = self::check_candidate_can_apply();
		if ( !$free_apply ) {
			$candidate_package_page_id = wp_job_board_get_option('candidate_package_page_id', true);
			$package_page_url = $candidate_package_page_id ? get_permalink($candidate_package_page_id) : home_url('/');
			$return = array(
				'status' => false,
				'msg' => sprintf(__('You have no package. <a href="%s" class="text-theme">Click here</a> to subscribe a package.', 'wp-job-board'), $package_page_url)
			);
		   	echo wp_json_encode($return);
		   	exit;
		}

		$user_id = WP_Job_Board_User::get_user_id();

		// apply_job_with_percent_resume
		$min_percent_resume = wp_job_board_get_option('apply_job_with_percent_resume', 70);
		if ( !empty($min_percent_resume) && $min_percent_resume > 0 ) {
			$candidate_id = WP_Job_Board_User::get_candidate_by_user_id($user_id);
			$percent = self::get_post_meta($candidate_id, 'profile_percent', true);
			$profile_percent = !empty($percent) ? $percent*100 : 0;
			if ( $min_percent_resume > 100 ) {
				$min_percent_resume = 100;
			}

			if ( $profile_percent < $min_percent_resume ) {
				$return = array( 'status' => false, 'msg' => esc_html__('You need complete your resume data to apply job.', 'wp-job-board') );
			   	echo wp_json_encode($return);
			   	exit;
			}
		}

		do_action('wp-job-board-process-apply-internal', $_POST);

		$applicant_id = self::insert_applicant($user_id, $job);
		
        if ( $applicant_id ) {
	        $return = array( 'status' => true, 'msg' => esc_html__('Apply job successfully.', 'wp-job-board'), 'text' => esc_html__('Applied.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
	    } else {
			$return = array( 'status' => false, 'msg' => esc_html__('Apply job error.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}
	}

	public static function insert_applicant($user_id, $job) {
		$candidate_id = WP_Job_Board_User::get_candidate_by_user_id($user_id);
		$job_id = $job->ID;

		$post_args = array(
            'post_title' => get_the_title($candidate_id),
            'post_type' => 'job_applicant',
            'post_content' => '',
            'post_status' => 'publish',
            'post_author' => $user_id,
        );
		$post_args = apply_filters('wp-job-board-add-job-applicant-data', $post_args);
		do_action('wp-job-board-before-add-job-applicant');

        // Insert the post into the database
        $applicant_id = wp_insert_post($post_args);
        if ( $applicant_id ) {
	        update_post_meta($applicant_id, WP_JOB_BOARD_APPLICANT_PREFIX . 'candidate_id', $candidate_id);
	        update_post_meta($applicant_id, WP_JOB_BOARD_APPLICANT_PREFIX . 'job_id', $job_id);
	        update_post_meta($applicant_id, WP_JOB_BOARD_APPLICANT_PREFIX . 'job_name', $job->post_title);
	        
	        // send email
	        $email = self::get_post_meta($candidate_id, 'email', true);
	        $phone = self::get_post_meta($candidate_id, 'phone', true);
	        $email_subject = WP_Job_Board_Email::render_email_vars( array('job_title' => $job->post_title), 'internal_apply_job_notice', 'subject');
	        $email_content_args = array(
	        	'job_title' => $job->post_title,
	        	'email' => $email,
	        	'phone' => $phone,
	        	'resume_url' => get_permalink($candidate_id),
	        );
	        $email_content = WP_Job_Board_Email::render_email_vars( $email_content_args, 'internal_apply_job_notice', 'content');

	        $headers = sprintf( "From: %s <%s>\r\n Content-type: text/html", get_bloginfo('name'), get_option( 'admin_email', false ) );
	        
			if ( empty($author_email) ) {
				$author_email = get_the_author_meta( 'user_email', $job->post_author );
			}

			$result = WP_Job_Board_Email::wp_mail( $author_email, $email_subject, $email_content, $headers );
			// end send email

	        do_action('wp-job-board-before-after-job-applicant', $applicant_id, $job_id, $candidate_id, $user_id);
	    }

	    return $applicant_id;
	}

	public static function check_candidate_can_apply() {
		$free_apply = wp_job_board_get_option('candidate_free_job_apply', 'on');
		$return = true;
		if ( $free_apply == 'off' ) {
			$return = false;
		}
		return apply_filters('wp-job-board-check-candidate-can-apply', $return);
	}
	
	public static function check_applied( $user_id, $job_id ) {
		if ( !is_user_logged_in() || !WP_Job_Board_User::is_candidate() ) {
			return false;
		}
		$candidate_id = WP_Job_Board_User::get_candidate_by_user_id($user_id);
		$posts = get_posts(array(
			'post_type' => 'job_applicant',
			'post_status' => 'publish',
			'meta_query' => array(
				array(
					'key' => WP_JOB_BOARD_APPLICANT_PREFIX . 'candidate_id',
			    	'value' => $candidate_id,
			    	'compare' => '=',
				),
				array(
					'key' => WP_JOB_BOARD_APPLICANT_PREFIX . 'job_id',
			    	'value' => $job_id,
			    	'compare' => '=',
				)
			)
		));
		if ( $posts && is_array($posts) ) {
			return true;
		}
		
		return false;
	}

	public static function process_remove_applied() {
		$return = array();
		if ( !isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'wp-job-board-remove-applied-nonce' )  ) {
			$return = array( 'status' => false, 'msg' => esc_html__('Your nonce did not verify.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}
		if ( !is_user_logged_in() ) {
			$return = array( 'status' => false, 'msg' => esc_html__('Please login to remove shortlist.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}
		$applicant_id = !empty($_POST['applicant_id']) ? $_POST['applicant_id'] : '';

		if ( empty($applicant_id) ) {
			$return = array( 'status' => false, 'msg' => esc_html__('Applicant did not exists.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}
		$user_id = WP_Job_Board_User::get_user_id();
		$is_allowed = WP_Job_Board_Mixes::is_allowed_to_remove( $user_id, $applicant_id );
		$job_id = get_post_meta($applicant_id, WP_JOB_BOARD_APPLICANT_PREFIX . 'job_id', true);
		$is_allowed_job = WP_Job_Board_Mixes::is_allowed_to_remove( $user_id, $job_id );

		if ( !$is_allowed && !$is_allowed_job ) {
	        $return = array( 'status' => false, 'msg' => esc_html__('You can not remove this applied.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}

		$candidate_id = get_post_meta($applicant_id, WP_JOB_BOARD_APPLICANT_PREFIX . 'candidate_id', true);
		$candidate_package_id = get_post_meta($applicant_id, WP_JOB_BOARD_APPLICANT_PREFIX . 'candidate_package_id', true);

		do_action('wp-job-board-process-remove-applied', $_POST);

		if ( wp_delete_post( $applicant_id ) ) {
	        $return = array( 'status' => true, 'msg' => esc_html__('Remove applied successfully.', 'wp-job-board') );

	        do_action('wp-job-board-after-remove-applied', $applicant_id, $job_id, $candidate_id, $candidate_package_id, $_POST);
		   	echo wp_json_encode($return);
		   	exit;
	    } else {
			$return = array( 'status' => false, 'msg' => esc_html__('Remove applied error.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}
	}

	public static function process_add_job_shortlist() {
		$return = array();
		if ( !isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'wp-job-board-add-job-shortlist-nonce' )  ) {
			$return = array( 'status' => false, 'msg' => esc_html__('Your nonce did not verify.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}
		if ( !is_user_logged_in() || !WP_Job_Board_User::is_candidate() ) {
			$return = array( 'status' => false, 'msg' => esc_html__('Please login with "Candidate" to add shortlist.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}
		$job_id = !empty($_POST['job_id']) ? $_POST['job_id'] : '';
		$post = get_post($job_id);

		if ( !$post || empty($post->ID) ) {
			$return = array( 'status' => false, 'msg' => esc_html__('Job did not exists.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}

		do_action('wp-job-board-process-add-job-shortlist', $_POST);

		$user_id = WP_Job_Board_User::get_user_id();
		$candidate_id = WP_Job_Board_User::get_candidate_by_user_id($user_id);

		$shortlist = self::get_post_meta($candidate_id, 'shortlist', true);

		if ( !empty($shortlist) && is_array($shortlist) ) {
			if ( !in_array($job_id, $shortlist) ) {
				$shortlist[] = $job_id;
			}
		} else {
			$shortlist = array( $job_id );
		}
		$result = self::update_post_meta( $candidate_id, 'shortlist', $shortlist );

		if ( $result ) {
	        $return = array( 'status' => true, 'nonce' => wp_create_nonce( 'wp-job-board-remove-job-shortlist-nonce' ), 'msg' => esc_html__('Add shortlist successfully.', 'wp-job-board'), 'text' => esc_html__('Shortlisted', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
	    } else {
			$return = array( 'status' => false, 'msg' => esc_html__('Add shortlist error.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}
	}

	public static function check_added_shortlist($job_id) {
		if ( empty($job_id) || !is_user_logged_in() || !WP_Job_Board_User::is_candidate() ) {
			return false;
		}

		$user_id = WP_Job_Board_User::get_user_id();
		$candidate_id = WP_Job_Board_User::get_candidate_by_user_id($user_id);

		$shortlist = self::get_post_meta($candidate_id, 'shortlist', true);

		if ( !empty($shortlist) && is_array($shortlist) && in_array($job_id, $shortlist) ) {
			return true;
		} else {
			return false;
		}
	}

	public static function process_remove_job_shortlist() {
		$return = array();
		if ( !isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'wp-job-board-remove-job-shortlist-nonce' )  ) {
			$return = array( 'status' => false, 'msg' => esc_html__('Your nonce did not verify.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}
		if ( !is_user_logged_in() ) {
			$return = array( 'status' => false, 'msg' => esc_html__('Please login to remove shortlist.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}
		$job_id = !empty($_POST['job_id']) ? $_POST['job_id'] : '';

		if ( empty($job_id) ) {
			$return = array( 'status' => false, 'msg' => esc_html__('Job did not exists.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}

		do_action('wp-job-board-process-remove-job-shortlist', $_POST);

		$user_id = WP_Job_Board_User::get_user_id();
		$candidate_id = WP_Job_Board_User::get_candidate_by_user_id($user_id);

		$result = true;
		$shortlist = self::get_post_meta($candidate_id, 'shortlist', true);
		if ( !empty($shortlist) && is_array($shortlist) ) {
			if ( in_array($job_id, $shortlist) ) {
				$key = array_search( $job_id, $shortlist );
				unset($shortlist[$key]);
				$result = self::update_post_meta( $candidate_id, 'shortlist', $shortlist );
			}
		}

		if ( $result ) {
	        $return = array( 'status' => true, 'nonce' => wp_create_nonce( 'wp-job-board-add-job-shortlist-nonce' ), 'msg' => esc_html__('Remove job from shortlist successfully.', 'wp-job-board'), 'text' => esc_html__('Shortlist', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
	    } else {
			$return = array( 'status' => false, 'msg' => esc_html__('Remove job from shortlist error.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}
	}

	public static function process_follow_employer() {
		$return = array();
		if ( !isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'wp-job-board-follow-employer-nonce' )  ) {
			$return = array( 'status' => false, 'msg' => esc_html__('Your nonce did not verify.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}
		if ( !is_user_logged_in() || !WP_Job_Board_User::is_candidate() ) {
			$return = array( 'status' => false, 'msg' => esc_html__('Please login with "Candidate" to follow employer.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}
		$employer_id = !empty($_POST['employer_id']) ? $_POST['employer_id'] : '';

		if ( empty($employer_id) ) {
			$return = array( 'status' => false, 'msg' => esc_html__('Employer did not exists.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}

		do_action('wp-job-board-process-follow-employer', $_POST);

		$user_id = WP_Job_Board_User::get_user_id();
		$following = get_user_meta($user_id, '_following_employer', true);
		if ( !empty($following) && is_array($following) ) {
			if ( !in_array($employer_id, $following) ) {
				$following[] = $employer_id;
			}
		} else {
			$following = array($employer_id);
		}
		$result = update_user_meta($user_id, '_following_employer', $following);
		if ( $result ) {
	        $return = array( 'status' => true, 'nonce' => wp_create_nonce( 'wp-job-board-unfollow-employer-nonce' ), 'text' => esc_html__('Following', 'wp-job-board'), 'msg' => sprintf(__('Follow "%s" successfully.', 'wp-job-board'), get_post_field('post_title', $employer_id)) );
		   	echo wp_json_encode($return);
		   	exit;
	    } else {
			$return = array( 'status' => false, 'msg' => esc_html__('Follow employer error.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}
	}


	public static function process_blacklist_employer() {

		$return = array();
		if ( !isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'wp-job-board-blacklist-employer-nonce' )  ) {
			$return = array( 'status' => false, 'msg' => esc_html__('Your nonce did not verify.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}
		if ( !is_user_logged_in() || !WP_Job_Board_User::is_candidate() ) {
			$return = array( 'status' => false, 'msg' => esc_html__('Please login with "Candidate" to blacklist employer.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}
		$employer_id = !empty($_POST['employer_id']) ? $_POST['employer_id'] : '';
		$emp_user_id = !empty($_POST['user_id']) ? $_POST['user_id'] : '';
		
		if ( empty($employer_id) ) {
			$return = array( 'status' => false, 'msg' => esc_html__('Blacklist did not exists.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}

		do_action('wp-job-board-process-follow-employer', $_POST);
		
		$user_id = WP_Job_Board_User::get_user_id();
		//$following = array();
		$following = get_user_meta($emp_user_id, '_blacklist_employer', true);
		//print_r($following);
		if ( !empty($following) /*&& is_array($following)*/ ) {
			//echo 'ad';
			$following[] = $user_id;
		} else {
			//echo 'ad2';
			$following = array( $user_id );
		}

		$result = update_user_meta($emp_user_id, '_blacklist_employer', $following);
		
		if ( $result ) {
	        $return = array( 'status' => true, 'nonce' => wp_create_nonce( 'wp-job-board-removeblacklist-employer-nonce' ), 'text' => esc_html__('Remove To Blacklist', 'wp-job-board'), 'msg' => sprintf(__('Blacklist "%s" successfully.', 'wp-job-board'), get_post_field('post_title', $employer_id)) );
		   	echo wp_json_encode($return);
		   	exit;
	    } else {
			$return = array( 'status' => false, 'msg' => esc_html__('blacklist employer error.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}
	}

	public static function process_unfollow_employer() {
		$return = array();
		if ( !isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'wp-job-board-unfollow-employer-nonce' )  ) {
			$return = array( 'status' => false, 'msg' => esc_html__('Your nonce did not verify.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}
		if ( !is_user_logged_in() ) {
			$return = array( 'status' => false, 'msg' => esc_html__('Please login to unfollow employer.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}
		$employer_id = !empty($_POST['employer_id']) ? $_POST['employer_id'] : '';

		if ( empty($employer_id) ) {
			$return = array( 'status' => false, 'msg' => esc_html__('Employer did not exists.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}

		do_action('wp-job-board-process-unfollow-employer', $_POST);

		$result = true;
		$user_id = WP_Job_Board_User::get_user_id();
		$following = get_user_meta($user_id, '_following_employer', true);
		if ( !empty($following) && is_array($following) ) {
			foreach ($following as $key => $id) {
				if ( $employer_id == $id ) {
					unset($following[$key]);
				}
			}
			$result = update_user_meta($user_id, '_following_employer', $following);
		}

		if ( $result ) {
	        $return = array( 'status' => true, 'nonce' => wp_create_nonce( 'wp-job-board-follow-employer-nonce' ), 'text' => esc_html__('Follow us', 'wp-job-board'), 'msg' => sprintf(__('Unfollow "%s" successfully.', 'wp-job-board'), get_post_field('post_title', $employer_id)) );
		   	echo wp_json_encode($return);
		   	exit;
	    } else {
			$return = array( 'status' => false, 'msg' => esc_html__('Unfollow employer error.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}
	}

	
	public static function process_removeblacklist_employer() {
		$return = array();
		if ( !isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'wp-job-board-removeblacklist-employer-nonce' )  ) {
			$return = array( 'status' => false, 'msg' => esc_html__('Your nonce did not verify.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}
		if ( !is_user_logged_in() ) {
			$return = array( 'status' => false, 'msg' => esc_html__('Please login to removeblacklist employer.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}
		$employer_id = !empty($_POST['employer_id']) ? $_POST['employer_id'] : '';
		$auth_id = !empty($_POST['uid']) ? $_POST['uid'] : '';
		
		if ( empty($employer_id) ) {
			$return = array( 'status' => false, 'msg' => esc_html__('removeblacklist did not exists.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}

		do_action('wp-job-board-process-removeblacklist-employer', $_POST);

		$result = true;
		$user_id = WP_Job_Board_User::get_user_id();
		$following = get_user_meta($auth_id, '_blacklist_employer', true);		
		if ( !empty($following) && is_array($following) ) {
			foreach ($following as $key => $id) {
				if ( $user_id == $id ) {
					unset($following[$key]);
				}
			}
			$result = update_user_meta($auth_id, '_blacklist_employer', $following);
		}

		if ( $result ) {
	        $return = array( 'status' => true, 'nonce' => wp_create_nonce( 'wp-job-board-blacklist-employer-nonce' ), 'text' => esc_html__('Add To blacklist', 'wp-job-board'), 'msg' => sprintf(__('Remove From Blacklist "%s" successfully.', 'wp-job-board'), get_post_field('post_title', $employer_id)) );
		   	echo wp_json_encode($return);
		   	exit;
	    } else {
			$return = array( 'status' => false, 'msg' => esc_html__('removeblacklist employer error.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}
	}

	public static function check_following($employer_id) {
		if ( empty($employer_id) || !is_user_logged_in() || !WP_Job_Board_User::is_candidate() ) {
			return false;
		}

		$user_id = WP_Job_Board_User::get_user_id();

		$following = get_user_meta($user_id, '_following_employer', true);

		if ( !empty($following) && is_array($following) && in_array($employer_id, $following) ) {
			return true;
		} else {
			return false;
		}
	}

	public static function check_blocklist($employer_id) {
		if ( empty($employer_id) || !is_user_logged_in() || !WP_Job_Board_User::is_candidate() ) {
			return false;
		}

		$user_id = WP_Job_Board_User::get_user_id();
		//print_r(get_the_ID());
		$authid = get_post_field( 'post_author', get_the_ID() );
		//print_r($authid);
		$following = get_user_meta($authid, '_blacklist_employer', true);
		if ( !empty($following) && is_array($following) && in_array($user_id, $following) ) {
			return true;
		} else {
			return false;
		}
	}

	public static function process_download_file() {
	    $attachment_id = isset($_GET['file_id']) ? $_GET['file_id'] : '';
	    $attachment_id = absint($attachment_id);

	    $error_page_url = home_url('/404-error');

	    if ( $attachment_id > 0 ) {

	        $file_post = get_post($attachment_id);
	        $file_path = get_attached_file($attachment_id);

	        if ( !$file_post || !$file_path || !file_exists($file_path) ) {
	            wp_redirect($error_page_url);
	        } else {
	            
	            header('Content-Description: File Transfer');
	            header("Expires: 0");
				header("Cache-Control: no-cache, no-store, must-revalidate"); 
				header('Cache-Control: pre-check=0, post-check=0, max-age=0', false); 
				header("Pragma: no-cache");	
				header("Content-type: " . $file_post->post_mime_type);
				header('Content-Disposition:attachment; filename="'. basename($file_path) .'"');
				header("Content-Type: application/force-download");
				header('Content-Length: ' . @filesize($file_path));

	            @readfile($file_path);
	            exit;
	        }
	    } else {
	        wp_redirect($error_page_url);
	    }

	    die;
	}

	public static function process_download_cv() {
	    $attachment_id = isset($_GET['file_id']) ? $_GET['file_id'] : '';
	    $attachment_id = absint($attachment_id);

	    $error_page_url = home_url('/404-error');

	    if ( $attachment_id > 0 ) {

	        $file_post = get_post($attachment_id);
	        $file_path = get_attached_file($attachment_id);

	        if ( !$file_post || !$file_path || !file_exists($file_path) ) {
	            wp_redirect($error_page_url);
	        } else {

	            $attch_parnt = get_post_ancestors($attachment_id);
	            if (isset($attch_parnt[0])) {
	                $attch_parnt = $attch_parnt[0];
	            }
	            
	            $error = true;
	            if (!is_user_logged_in()) {
	                wp_redirect($error_page_url);
	                exit;
	            }
	            $user_id = WP_Job_Board_User::get_user_id();
	            $cur_user_obj = wp_get_current_user();

	            if ( WP_Job_Board_User::is_employer($user_id) ) {
	                $error = false;
	            }

	            if ( WP_Job_Board_User::is_candidate($user_id) ) {
	                $user_cand_id = WP_Job_Board_User::get_candidate_by_user_id($user_id);
	                if ($user_cand_id == $attch_parnt) {
	                    $error = false;
	                }
	            }

	            if ( in_array('administrator', (array) $cur_user_obj->roles) ) {
	                $error = false;
	            }

	            if ( $error ) {
	                wp_redirect($error_page_url);
	                exit;
	            }
	            
	            header('Content-Description: File Transfer');
	            header("Expires: 0");
				header("Cache-Control: no-cache, no-store, must-revalidate"); 
				header('Cache-Control: pre-check=0, post-check=0, max-age=0', false); 
				header("Pragma: no-cache");	
				header("Content-type: " . $file_post->post_mime_type);
				header('Content-Disposition:attachment; filename="'. basename($file_path) .'"');
				header("Content-Type: application/force-download");
				header('Content-Length: ' . @filesize($file_path));

	            @readfile($file_path);
	            exit;
	        }
	    } else {
	        wp_redirect($error_page_url);
	    }

	    die;
	}

	public static function process_download_resume_cv() {
		$post_id = isset($_GET['post_id']) ? $_GET['post_id'] : '';
	    $post_id = absint($post_id);

	    $error_page_url = home_url('/404-error');

	    if ( $post_id > 0 ) {

	        $resume_post = get_post($post_id);

	        if ( !$resume_post ) {
	            wp_redirect($error_page_url);
	        } else {

	            $error = true;
	            if (!is_user_logged_in()) {
	                wp_redirect($error_page_url);
	                exit;
	            }
	            $user_id = WP_Job_Board_User::get_user_id();
	            $cur_user_obj = wp_get_current_user();

	            if ( WP_Job_Board_User::is_employer($user_id) ) {
	                $error = false;
	            }

	            if ( WP_Job_Board_User::is_candidate($user_id) ) {
	                $user_cand_id = WP_Job_Board_User::get_candidate_by_user_id($user_id);
	                if ($user_cand_id == $attch_parnt) {
	                    $error = false;
	                }
	            }

	            if ( in_array('administrator', (array) $cur_user_obj->roles) ) {
	                $error = false;
	            }

	            $error = apply_filters('wp-job-board-download-resume-cv-check', $error, $resume_post);

	            $file_path = WP_Job_Board_Mpdf::mpdf_exec($resume_post);
	            if ( empty($file_path) ) {
	            	$error = false;
	            }

	            if ( $error ) {
	                wp_redirect($error_page_url);
	                exit;
	            }

	            header('Content-Description: File Transfer');
	            header("Expires: 0");
				header("Cache-Control: no-cache, no-store, must-revalidate"); 
				header('Cache-Control: pre-check=0, post-check=0, max-age=0', false); 
				header("Pragma: no-cache");	
				header("Content-type: application/pdf");
				header('Content-Disposition:attachment; filename="'. basename($file_path) .'"');
				header("Content-Type: application/force-download");
				header('Content-Length: ' . @filesize($file_path));

	            @readfile($file_path);
	            exit;
	        }
	    } else {
	        wp_redirect($error_page_url);
	    }

	    die;
	}

	public static function candidate_only_applicants($post) {
		$return = false;
		if ( is_user_logged_in() ) {
			$user_id = WP_Job_Board_User::get_user_id();
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
						    	'key' => WP_JOB_BOARD_APPLICANT_PREFIX . 'candidate_id',
						    	'value' => $post->ID,
						    	'compare' => '=',
						    ),
						    array(
						    	'key' => WP_JOB_BOARD_APPLICANT_PREFIX . 'job_id',
						    	'value' => $jobs->posts,
						    	'compare' => 'IN',
						    ),
						)
					);

					$applicants = WP_Job_Board_Query::get_posts($query_vars);
					if ( !empty($applicants) && !empty($applicants->posts) ) {
						$return = true;
					}
				}
			}
		}
		return $return;
	}

	// check view
	public static function check_view_candidate_detail() {
		global $post;
		$restrict_type = wp_job_board_get_option('candidate_restrict_type', '');
		$view = wp_job_board_get_option('candidate_restrict_detail', 'all');
		
		$return = true;
		if ( $restrict_type == 'view' ) {
			$author_id = WP_Job_Board_User::get_user_by_candidate_id($post->ID);
			if ( get_current_user_id() == $author_id ) {
				$return = true;
			} else {
				switch ($view) {
					case 'register_user':
						$return = false;
						if ( is_user_logged_in() ) {
							$show_profile = self::get_post_meta($post->ID, 'show_profile');
							if ( empty($show_profile) || $show_profile == 'show' ) {
								$return = true;
							}
						}
						break;
					case 'register_employer':
						$return = false;
						if ( is_user_logged_in() ) {
							$user_id = WP_Job_Board_User::get_user_id();
							if ( WP_Job_Board_User::is_employer($user_id) ) {
								$show_profile = self::get_post_meta($post->ID, 'show_profile');
								if ( empty($show_profile) || $show_profile == 'show' ) {
									$return = true;
								}
							}
						}
						break;
					case 'only_applicants':
						$return = self::candidate_only_applicants($post);
						break;
					default:
						$return = false;
						$show_profile = self::get_post_meta($post->ID, 'show_profile');
						if ( empty($show_profile) || $show_profile == 'show' ) {
							$return = true;
						}
						break;
				}
			}

		} else {
			$return = self::candidate_only_applicants($post);
			if ( !$return ) {
				$show_profile = self::get_post_meta($post->ID, 'show_profile');
				if ( empty($show_profile) || $show_profile == 'show' ) {
					$return = true;
				}
			}
		}

		return apply_filters('wp-job-board-check-view-candidate-detail', $return, $post);
	}

	public static function candidate_restrict_listing_query($query, $filter_params) {
		$query_vars = $query->query_vars;
		$query_vars = self::candidate_restrict_listing_query_args($query_vars, $filter_params);
		$query->query_vars = $query_vars;
		
		return apply_filters('wp-job-board-check-view-candidate-listing-query', $query);
	}

	public static function candidate_restrict_listing_query_args($query_args, $filter_params) {
		$restrict_type = wp_job_board_get_option('candidate_restrict_type', '');
		/*print_r($query_args);
		print_r($filter_params);*/
		if ( $restrict_type == 'view' ) {
			$view = wp_job_board_get_option('candidate_restrict_listing', 'all');
			
			switch ($view) {
				case 'register_user':
					if ( !is_user_logged_in() ) {
						$meta_query = !empty($query_args['meta_query']) ? $query_args['meta_query'] : array();
						$meta_query[] = array(
							'key'       => 'candidate_restrict_listing',
							'value'     => 'register_user',
							'compare'   => '==',
						);
						$query_args['meta_query'] = $meta_query;
					}
					break;
				case 'register_employer':
					$return = false;
					if ( is_user_logged_in() ) {
						$user_id = WP_Job_Board_User::get_user_id();
						if ( WP_Job_Board_User::is_employer($user_id) ) {
							$return = true;
						}
					}
					if ( !$return ) {
						$meta_query = !empty($query_args['meta_query']) ? $query_args['meta_query'] : array();
						$meta_query[] = array(
							'key'       => 'candidate_restrict_listing',
							'value'     => 'register_employer',
							'compare'   => '==',
						);
						$query_args['meta_query'] = $meta_query;
					}
					break;
				case 'only_applicants':
					$ids = array(0);
					if ( is_user_logged_in() ) {
						$user_id = WP_Job_Board_User::get_user_id();

						$applicants = WP_Job_Board_Applicant::get_all_applicants_by_employer($user_id);
						foreach ($applicants as $applicant_id) {
							$candidate_id = get_post_meta($applicant_id, WP_JOB_BOARD_APPLICANT_PREFIX.'candidate_id', true );
							if ( $candidate_id ) {
								$return[] = $candidate_id;
							}
						}
					}
					if ( !empty($return) ) {
						$post__in = !empty($query_args['post__in']) ? $query_args['post__in'] : array();
						if ( !empty($post__in) ) {
							$ids = array_intersect($return, $post__in);
						} else {
							$ids = $return;
						}
						$ids[] = 0;
					}
					$query_args['post__in'] = $ids;
					break;
			}
		} else {
			$meta_query = !empty($query_args['meta_query']) ? $query_args['meta_query'] : array();
			$meta_query[] = array(
				array(
					'relation' => 'OR',
					array(
						'key'       => WP_JOB_BOARD_CANDIDATE_PREFIX.'show_profile',
						'value'     => 'show',
						'compare'   => '==',
					),
					array(
						'key'       => WP_JOB_BOARD_CANDIDATE_PREFIX.'show_profile',
						'compare' => 'NOT EXISTS',
					),
				)
			);
			$query_args['meta_query'] = $meta_query;
		}
		return apply_filters('wp-job-board-check-view-candidate-listing-query-args', $query_args);
	}

	public static function check_restrict_view_contact_info($post) {
		$return = true;
		$restrict_type = wp_job_board_get_option('candidate_restrict_type', '');
		if ( $restrict_type == 'view_contact_info' ) {
			$view = wp_job_board_get_option('candidate_restrict_contact_info', 'all');

			$author_id = WP_Job_Board_User::get_user_by_candidate_id($post->ID);
			if ( get_current_user_id() == $author_id ) {
				$return = true;
			} else {
				switch ($view) {
					case 'register_user':
						$return = false;
						if ( is_user_logged_in() ) {
							$return = true;
						}
						break;
					case 'register_employer':
						$return = false;
						if ( is_user_logged_in() ) {
							$user_id = WP_Job_Board_User::get_user_id();
							if ( WP_Job_Board_User::is_employer($user_id) ) {
								$return = true;
							}
						}
						break;
					case 'only_applicants':
						$return = self::candidate_only_applicants($post);
						break;
					default:
						$return = true;
						break;
				}
			}
		}
		return apply_filters('wp-job-board-check-view-candidate-contact-info', $return, $post);
	}

	public static function check_restrict_review($post) {
		$return = true;
		
		$view = wp_job_board_get_option('candidates_restrict_review', 'all');
		
		switch ($view) {
			case 'always_hidden':
				$return = false;
				break;
			case 'register_user':
				$return = false;
				if ( is_user_logged_in() ) {
					$return = true;
				}
				break;
			case 'register_employer':
				$return = false;
				if ( is_user_logged_in() ) {
					$user_id = WP_Job_Board_User::get_user_id();
					if ( WP_Job_Board_User::is_employer($user_id) ) {
						$return = true;
					}
				}
				break;
			case 'only_applicants':
				$return = self::candidate_only_applicants($post);
				break;
			default:
				$return = true;
				break;
		}

		return apply_filters('wp-job-board-check-restrict-candidate-review', $return, $post);
	}

	public static function display_candidates_results_filters() {
		$filters = WP_Job_Board_Abstract_Filter::get_filters();

		echo WP_Job_Board_Template_Loader::get_template_part('loop/candidate/results-filters', array('filters' => $filters));
	}

	public static function display_candidates_count_results($wp_query) {
		$total = $wp_query->found_posts;
		$per_page = $wp_query->query_vars['posts_per_page'];
		$current = max( 1, $wp_query->get( 'paged', 1 ) );
		$args = array(
			'total' => $total,
			'per_page' => $per_page,
			'current' => $current,
		);
		echo WP_Job_Board_Template_Loader::get_template_part('loop/candidate/results-count', $args);
	}

	public static function display_candidates_alert_form() {
		echo WP_Job_Board_Template_Loader::get_template_part('loop/candidate/candidates-alert-form');
	}

	public static function display_candidates_orderby() {
		echo WP_Job_Board_Template_Loader::get_template_part('loop/candidate/orderby');
	}

	public static function display_candidates_alert_orderby_start() {
		echo WP_Job_Board_Template_Loader::get_template_part('loop/candidate/alert-orderby-start');
	}

	public static function display_candidates_alert_orderby_end() {
		echo WP_Job_Board_Template_Loader::get_template_part('loop/candidate/alert-orderby-end');
	}

	public static function get_display_email($post) {
		if ( is_object($post) ) {
			$post_id = $post->ID;
		} else {
			$post_id = $post;
			$post = get_post($post_id);
		}
		$email = '';
		if ( self::check_restrict_view_contact_info($post) ) {
			$email = self::get_post_meta( $post_id, 'email', true );
		}
		return apply_filters('wp-job-board-get-display-candidate-email', $email, $post_id);
	}

	public static function get_display_phone($post) {
		if ( is_object($post) ) {
			$post_id = $post->ID;
		} else {
			$post_id = $post;
			$post = get_post($post_id);
		}
		$phone = '';
		if ( self::check_restrict_view_contact_info($post) ) {
			$phone = self::get_post_meta( $post_id, 'phone', true );
		}
		return apply_filters('wp-job-board-get-display-candidate-phone', $phone, $post_id);
	}

	public static function get_display_cv_download($post) {
		if ( is_object($post) ) {
			$post_id = $post->ID;
		} else {
			$post_id = $post;
			$post = get_post($post_id);
		}
		$cv_attachment = '';
		if ( self::check_restrict_view_contact_info($post) ) {
			$cv_attachment = self::get_post_meta( $post_id, 'cv_attachment', true );
		}
		return apply_filters('wp-job-board-get-display-candidate-cv_attachment', $cv_attachment, $post_id);
	}

	public static function restrict_candidate_listing_information($query) {
		$restrict_type = wp_job_board_get_option('candidate_restrict_type', '');
		if ( $restrict_type == 'view' ) {
			$view =  wp_job_board_get_option('candidate_restrict_listing', 'all');
			$output = '';
			switch ($view) {
				case 'register_user':
					if ( !is_user_logged_in() ) {
						$output = '
						<div class="candidate-listing-info">
							<h2 class="restrict-title">'.__( 'The page is restricted only for register user.', 'wp-job-board' ).'</h2>
							<div class="restrict-content">'.__( 'You need login to view this page', 'wp-job-board' ).'</div>
						</div>';
					}
					break;
				case 'register_employer':
					$return = false;
					if ( is_user_logged_in() ) {
						$user_id = WP_Job_Board_User::get_user_id();
						if ( WP_Job_Board_User::is_employer($user_id) ) {
							$return = true;
						}
					}
					if ( !$return ) {
						$output = '<div class="candidate-listing-info"><h2 class="restrict-title">'.__( 'The page is restricted only for employers.', 'wp-job-board' ).'</h2></div>';
					}
					break;
				case 'only_applicants':
					$return = array();
					if ( is_user_logged_in() ) {
						$user_id = WP_Job_Board_User::get_user_id();

						$applicants = WP_Job_Board_Applicant::get_all_applicants_by_employer($user_id);
						if ( !empty($applicants) ) {
							foreach ($applicants as $applicant_id) {
								$candidate_id = get_post_meta($applicant_id, WP_JOB_BOARD_APPLICANT_PREFIX.'candidate_id', true );
								if ( $candidate_id ) {
									$return[] = $candidate_id;
								}
							}
						}
					}
					if ( empty($return) ) {
						$output = '<div class="candidate-listing-info"><h2 class="restrict-title">'.__( 'The page is restricted only for employers view his applicants.', 'wp-job-board' ).'</h2></div>';
					}
					break;
				default:
					$output = apply_filters('wp-job-board-restrict-candidate-listing-default-information', '', $query);
					break;
			}

			echo apply_filters('wp-job-board-restrict-candidate-listing-information', $output, $query);
		}
	}
}

WP_Job_Board_Candidate::init();