<?php
/**
 * Job Alert
 *
 * @package    wp-job-board
 * @author     Habq 
 * @license    GNU General Public License, version 3
 */

if ( ! defined( 'ABSPATH' ) ) {
  	exit;
}

class WP_Job_Board_Job_Alert {
	public static function init() {
		add_action( 'wp_job_board_email_daily_notices', array( __CLASS__, 'send_job_alert_notice' ) );

		add_action( 'wp_ajax_wp_job_board_ajax_add_job_alert',  array(__CLASS__,'process_add_job_alert') );
		add_action( 'wp_ajax_nopriv_wp_job_board_ajax_add_job_alert',  array(__CLASS__,'process_add_job_alert') );

		add_action( 'wp_ajax_wp_job_board_ajax_remove_job_alert',  array(__CLASS__,'process_remove_job_alert') );
		add_action( 'wp_ajax_nopriv_wp_job_board_ajax_remove_job_alert',  array(__CLASS__,'process_remove_job_alert') );
	}

	public static function get_email_frequency() {
		$email_frequency = apply_filters( 'wp-job-board-job-alert-email-frequency', array(
			'daily' => array(
				'label' => __('Daily', 'wp-job-board'),
				'days' => '1',
			),
			'weekly' => array(
				'label' => __('Weekly', 'wp-job-board'),
				'days' => '7',
			),
			'fortnightly' => array(
				'label' => __('Fortnightly', 'wp-job-board'),
				'days' => '15',
			),
			'monthly' => array(
				'label' => __('Monthly', 'wp-job-board'),
				'days' => '30',
			),
			'biannually' => array(
				'label' => __('Biannually', 'wp-job-board'),
				'days' => '182',
			),
			'annually' => array(
				'label' => __('Annually', 'wp-job-board'),
				'days' => '365',
			),
		));
		return $email_frequency;
	}

	public static function send_job_alert_notice() {
		
		$email_frequency_default = self::get_email_frequency();
		if ( $email_frequency_default ) {
			foreach ($email_frequency_default as $key => $value) {
				if ( !empty($value['days']) ) {
					$meta_query = array(
						'relation' => 'OR',
						array(
							'key' => WP_JOB_BOARD_JOB_ALERT_PREFIX.'send_email_time',
							'compare' => 'NOT EXISTS',
						)
					);
					$current_time = apply_filters( 'wp-job-board-job-alert-current-'.$key.'-time', date( 'Y-m-d', strtotime( '-'.intval($value['days']).' days', current_time( 'timestamp' ) ) ) );
					$meta_query[] = array(
						'relation' => 'AND',
						array(
							'key' => WP_JOB_BOARD_JOB_ALERT_PREFIX.'send_email_time',
							'value' => $current_time,
							'compare' => '<=',
						),
						array(
							'key' => WP_JOB_BOARD_JOB_ALERT_PREFIX.'email_frequency',
							'value' => $key,
							'compare' => '=',
						),
					);

					$query_args = apply_filters( 'wp-job-board-job-alert-query-args', array(
						'post_type' => 'job_alert',
						'post_per_page' => -1,
						'post_status' => 'publish',
						'fields' => 'ids',
						'meta_query' => $meta_query
					));

					$job_alerts = new WP_Query($query_args);
					if ( !empty($job_alerts->posts) ) {
						foreach ($job_alerts->posts as $post_id) {
							$alert_query = get_post_meta($post_id, WP_JOB_BOARD_JOB_ALERT_PREFIX . 'alert_query', true);
							$params = null;
							if ( !empty($alert_query) ) {
								$params = json_decode($alert_query, true);
							}

							$query_args = array(
								'post_type' => 'job_listing',
							    'post_status' => 'publish',
							    'post_per_page' => 1,
							    'fields' => 'ids'
							);
							$jobs = WP_Job_Board_Query::get_posts($query_args, $params);
							$count_jobs = $jobs->found_posts;
							$job_alert_title = get_the_title($post_id);
							// send email action
							$job = get_post($post_id);
							$email_from = get_option( 'admin_email', false );
							
							$headers = sprintf( "From: %s <%s>\r\n Content-type: text/html", get_bloginfo('name'), $email_from );
							$email_to = get_option( 'admin_email', false );
							$subject = WP_Job_Board_Email::render_email_vars(array('alert_title' => $job_alert_title), 'job_alert_notice', 'subject');

							$email_frequency = get_post_meta($post_id, WP_JOB_BOARD_JOB_ALERT_PREFIX.'email_frequency', true);
							if ( !empty($email_frequency_default[$email_frequency]['label']) ) {
								$email_frequency = $email_frequency_default[$email_frequency]['label'];
							}
							$jobs_alert_url = WP_Job_Board_Mixes::get_jobs_page_url();
							if ( !empty($params) ) {
								foreach ($params as $key => $value) {
									if ( is_array($value) ) {
										$jobs_alert_url = remove_query_arg( $key.'[]', $jobs_alert_url );
										foreach ($value as $val) {
											$jobs_alert_url = add_query_arg( $key.'[]', $val, $jobs_alert_url );
										}
									} else {
										$jobs_alert_url = add_query_arg( $key, $value, remove_query_arg( $key, $jobs_alert_url ) );
									}
								}
							}
							$content_args = apply_filters( 'wp-job-board-job-alert-email-content-args', array(
								'alert_title' => $job_alert_title,
								'jobs_found' => $count_jobs,
								'email_frequency_type' => $email_frequency,
								'jobs_alert_url' => $jobs_alert_url
							));
							$content = WP_Job_Board_Email::render_email_vars($content_args, 'job_alert_notice', 'content');
										
							WP_Job_Board_Email::wp_mail( $email_to, $subject, $content, $headers );
							$current_time = date( 'Y-m-d', current_time( 'timestamp' ) );
							delete_post_meta($post_id, WP_JOB_BOARD_JOB_ALERT_PREFIX.'send_email_time');
							add_post_meta($post_id, WP_JOB_BOARD_JOB_ALERT_PREFIX.'send_email_time', $current_time);
						}
					}
				}
			}
		}
		
	}

	public static function process_add_job_alert() {
		$return = array();
		if ( !isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'wp-job-board-add-job-alert-nonce' )  ) {
			$return = array( 'status' => false, 'msg' => esc_html__('Your nonce did not verify.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}
		if ( !is_user_logged_in() || !WP_Job_Board_User::is_candidate() ) {
			$return = array( 'status' => false, 'msg' => esc_html__('Please login with "Candidate" to add job alert.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}
		$user_id = WP_Job_Board_User::get_user_id();
		$candidate_id = WP_Job_Board_User::get_candidate_by_user_id($user_id);

		$errors = self::validate_add_job_alert();
		if ( sizeof($errors) > 0 ) {
			$return = array( 'status' => false, 'msg' => implode(', ', $errors) );
		   	echo wp_json_encode($return);
		   	exit;
		}
		$name = !empty($_POST['name']) ? $_POST['name'] : '';
		
		$post_args = array(
            'post_title' => $name,
            'post_type' => 'job_alert',
            'post_content' => '',
            'post_status' => 'publish',
            'user_id' => $user_id
        );
		$post_args = apply_filters('wp-job-board-add-job-alert-data', $post_args);
		
		do_action('wp-job-board-before-add-job-alert');

        // Insert the post into the database
        $alert_id = wp_insert_post($post_args);
        if ( $alert_id ) {
	        update_post_meta($alert_id, WP_JOB_BOARD_JOB_ALERT_PREFIX . 'candidate_id', $candidate_id);
	        $email_frequency = !empty($_POST['email_frequency']) ? $_POST['email_frequency'] : '';
	        update_post_meta($alert_id, WP_JOB_BOARD_JOB_ALERT_PREFIX . 'email_frequency', $email_frequency);

	        $alert_query = array();
			if ( ! empty( $_POST ) && is_array( $_POST ) ) {
				foreach ( $_POST as $key => $value ) {
					if ( strrpos( $key, 'filter-', -strlen( $key ) ) !== false ) {
						$alert_query[$key] = $value;
					}
				}
			}
	        if ( !empty($alert_query) ) {
	        	$alert_query = json_encode($alert_query);
	        	update_post_meta($alert_id, WP_JOB_BOARD_JOB_ALERT_PREFIX . 'alert_query', $alert_query);	
	        }
	        
	        do_action('wp-job-board-after-add-job-alert', $alert_id);

	        $return = array( 'status' => true, 'msg' => esc_html__('Add job alert successfully.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
	    } else {
			$return = array( 'status' => false, 'msg' => esc_html__('Add job alert error.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}
	}

	public static function validate_add_job_alert() {
		$name = !empty($_POST['name']) ? $_POST['name'] : '';
		if ( empty($name) ) {
			$return[] = esc_html__('Name is required.', 'wp-job-board');
		}
		$email_frequency = !empty($_POST['email_frequency']) ? $_POST['email_frequency'] : '';
		if ( empty($email_frequency) ) {
			$return[] = esc_html__('Email frequency is required.', 'wp-job-board');
		}
		return $return;
	}

	public static function process_remove_job_alert() {
		$return = array();
		if ( !isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'wp-job-board-remove-job-alert-nonce' )  ) {
			$return = array( 'status' => false, 'msg' => esc_html__('Your nonce did not verify.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}
		if ( !is_user_logged_in() ) {
			$return = array( 'status' => false, 'msg' => esc_html__('Please login to remove job alert.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}
		$alert_id = !empty($_POST['alert_id']) ? $_POST['alert_id'] : '';

		if ( empty($alert_id) ) {
			$return = array( 'status' => false, 'msg' => esc_html__('Applicant did not exists.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}
		$user_id = WP_Job_Board_User::get_user_id();
		$is_allowed = WP_Job_Board_Mixes::is_allowed_to_remove( $user_id, $alert_id );

		if ( ! $is_allowed ) {
	        $return = array( 'status' => false, 'msg' => esc_html__('You can not remove this job alert.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}

		do_action('wp-job-board-before-remove-job-alert', $alert_id);

		if ( wp_delete_post( $alert_id ) ) {
	        $return = array( 'status' => true, 'msg' => esc_html__('Remove job alert successfully.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
	    } else {
			$return = array( 'status' => false, 'msg' => esc_html__('Remove job alert error.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}
	}
}

WP_Job_Board_Job_Alert::init();