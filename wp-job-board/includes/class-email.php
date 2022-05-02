<?php
/**
 * Price
 *
 * @package    wp-job-board
 * @author     Habq 
 * @license    GNU General Public License, version 3
 */

if ( ! defined( 'ABSPATH' ) ) {
  	exit;
}

class WP_Job_Board_Email {
	
	public static $emails_vars;

	public static function init() {
		add_action( 'wp_ajax_wp_job_board_ajax_contact_form',  array(__CLASS__,'process_send_contact') );
		add_action( 'wp_ajax_nopriv_wp_job_board_ajax_contact_form',  array(__CLASS__,'process_send_contact') );
	}

	public static function wp_mail( $author_email, $subject, $content, $headers, $attachments = null) {
		if ( !preg_match( '%<html[>\s].*</html>%is', $content ) ) {
			$header = apply_filters( 'wp-job-board-mail-html-header',
				'<!doctype html>
			<html xmlns="http://www.w3.org/1999/xhtml">
			<head>
			<title>' . esc_html( $subject ) . '</title>
			</head>
			<body>
			', $subject );

			$footer = apply_filters( 'wp-job-board-mail-html-footer',
						'</body>
			</html>' );

			$content = $header . wpautop( $content ) . $footer;
		}
		
		return wp_mail( $author_email, $subject, $content, $headers, $attachments );
	}

	public static function process_send_contact() {
		$is_form_filled = ! empty( $_POST['email'] ) && ! empty( $_POST['subject'] ) && ! empty( $_POST['message'] ) && ! empty( $_POST['post_id'] );

		if ( WP_Job_Board_Recaptcha::is_recaptcha_enabled() ) {
			$is_recaptcha_valid = array_key_exists( 'g-recaptcha-response', $_POST ) ? WP_Job_Board_Recaptcha::is_recaptcha_valid( sanitize_text_field( $_POST['g-recaptcha-response'] ) ) : false;
			if ( !$is_recaptcha_valid ) {
				$is_form_filled = false;
			}
		}
		
		$post_type = get_post_type( $_POST['post_id'] );
		if ( $post_type == 'employer' ) {
			$author_email = get_post_meta( $_POST['post_id'], WP_JOB_BOARD_EMPLOYER_PREFIX.'email', true );
		} elseif ( $post_type == 'candidate' ) {
			$author_email = get_post_meta( $_POST['post_id'], WP_JOB_BOARD_CANDIDATE_PREFIX.'email', true );
		}
		
		if ( $is_form_filled && !empty($author_email) ) {
			$post = get_post($_POST['post_id']);
			if ( !WP_Job_Board_Candidate::check_restrict_view_contact_info($post) ) {
				$return = array(
					'status' => false,
					'msg' => esc_html__('You have no package.', 'wp-job-board')
				);
				echo wp_json_encode($return);
	   			exit;
			}
			// contact email check
			do_action('wp-job-board-before-process-send-contact', $post_type, $_POST);

	        $email = sanitize_text_field( $_POST['email'] );
	        $phone = sanitize_text_field( $_POST['phone'] );
	        $subject = sanitize_text_field( $_POST['subject'] );
	        $message = sanitize_text_field( $_POST['message'] );

	        $subject = str_replace('{{subject}}', $subject, wp_job_board_get_option('contact_form_notice_subject'));
	        $content = wp_job_board_get_option('contact_form_notice_content');
	        $content = str_replace('{{subject}}', $subject, $content);
	        $content = str_replace('{{website_url}}', home_url(), $content);
	        $content = str_replace('{{website_name}}', get_bloginfo( 'name' ), $content);
	        $content = str_replace('{{email}}', $email, $content);
	        $content = str_replace('{{phone}}', $phone, $content);
	        $content = str_replace('{{message}}', $message, $content);
	        
	        $headers = sprintf( "From: %s <%s>\r\n Content-type: text/html", $email, $email );
	        
	        $result = false;
			$result = self::wp_mail( $author_email, $subject, $content, $headers );
	        if ( $result ) {
	        	$return = array( 'status' => true, 'msg' => esc_html__('Your message has been successfully sent.', 'wp-job-board') );

	        	do_action('wp-job-board-after-process-send-contact', $post_type, $_POST);
	        } else {
	        	$return = array( 'status' => false, 'msg' => esc_html__('An error occurred when sending an email.', 'wp-job-board') );
	        }
	    } else {
	    	$return = array( 'status' => false, 'msg' => esc_html__('Form has been not filled correctly.', 'wp-job-board') );
	    }
	    echo wp_json_encode($return);
	   	exit;
	}

	public static function emails_vars() {
		self::$emails_vars = apply_filters( 'wp-job-board-emails-vars', array(
			'admin_notice_add_new_listing' => array(
				'subject' => array( 'job_title' ),
				'content' => array( 'job_title', 'job_url', 'author', 'website_url', 'website_name' )
			),
			'admin_notice_updated_listing' => array(
				'subject' => array( 'job_title' ),
				'content' => array( 'job_title', 'job_url', 'author', 'website_url', 'website_name' )
			),
			'admin_notice_expiring_listing' => array(
				'subject' => array( 'job_title' ),
				'content' => array( 'job_title', 'job_url', 'website_url', 'website_name', 'job_admin_edit_url' )
			),
			'employer_notice_expiring_listing' => array(
				'subject' => array( 'job_title' ),
				'content' => array( 'job_title', 'job_url', 'website_url', 'website_name', 'dashboard_url', 'my_jobs' )
			),
			'email_apply_job_notice' => array(
				'subject' => array( 'job_title' ),
				'content' => array( 'job_title', 'fullname', 'email', 'phone', 'message', 'cv_file_url', 'website_name', 'website_url' )
			),
			'internal_apply_job_notice' => array(
				'subject' => array( 'job_title' ),
				'content' => array( 'job_title', 'email', 'phone', 'resume_url', 'website_name', 'website_url' )
			),

			'job_alert_notice' => array(
				'subject' => array( 'alert_title' ),
				'content' => array( 'alert_title', 'jobs_found', 'website_url', 'website_name', 'email_frequency_type', 'jobs_alert_url' )
			),
			'candidate_alert_notice' => array(
				'subject' => array( 'alert_title' ),
				'content' => array( 'alert_title', 'candidates_found', 'website_url', 'website_name', 'email_frequency_type', 'candidates_alert_url' )
			),
			'contact_form_notice' => array(
				'subject' => array( 'subject' ),
				'content' => array( 'subject', 'message', 'email', 'phone', 'website_url', 'website_name' )
			),
			'reject_interview_notice' => array(
				'subject' => array( 'job_title' ),
				'content' => array( 'candidate_name', 'employer_name', 'job_title', 'job_url', 'website_url', 'website_name' )
			),
			'undo_reject_interview_notice' => array(
				'subject' => array( 'job_title' ),
				'content' => array( 'candidate_name', 'employer_name', 'job_title', 'job_url', 'website_url', 'website_name' )
			),
			'approve_interview_notice' => array(
				'subject' => array( 'job_title' ),
				'content' => array( 'candidate_name', 'employer_name', 'job_title', 'job_url', 'website_url', 'website_name' )
			),
			'undo_approve_interview_notice' => array(
				'subject' => array( 'job_title' ),
				'content' => array( 'candidate_name', 'employer_name', 'job_title', 'job_url', 'website_url', 'website_name' )
			),

			'user_register_auto_approve' => array(
				'subject' => array( 'user_name' ),
				'content' => array( 'user_name', 'user_email', 'login_url', 'website_url', 'website_name' )
			),
			'user_register_need_approve' => array(
				'subject' => array( 'user_name' ),
				'content' => array( 'user_name', 'user_email', 'approve_url', 'website_url', 'website_name' )
			),
			'user_register_approved' => array(
				'subject' => array( 'user_name' ),
				'content' => array( 'user_name', 'user_email', 'login_url', 'dashboard_url', 'website_url', 'website_name' )
			),
			'user_register_denied' => array(
				'subject' => array( 'user_name' ),
				'content' => array( 'user_name', 'user_email', 'website_url', 'website_name' )
			),
			'user_reset_password' => array(
				'subject' => array( 'user_name' ),
				'content' => array( 'user_name', 'user_email', 'new_password', 'website_url', 'website_name' )
			),
		));
		return self::$emails_vars;
	}

	public static function display_email_vars($key, $type = 'subject') {
		self::emails_vars();
		$output = '';
		if ( !empty(self::$emails_vars[$key][$type]) ) {
			$i = 1;
			foreach (self::$emails_vars[$key][$type] as $value) {
				$output .= '{{'.$value.'}}'.($i < count(self::$emails_vars[$key][$type]) ? ', ' : '');
				$i++;
			}
		}
		return $output;
	}

	public static function render_email_vars($args, $key, $type = 'subject') {
		self::emails_vars();
		$output = wp_job_board_get_option($key.'_'.$type);
		if ( !empty(self::$emails_vars[$key][$type]) ) {
			$vars = self::$emails_vars[$key][$type];
			foreach ($vars as $var) {
				if ( strpos($output, '{{'.$var.'}}') !== false ) {
					if ( isset($args[$var]) ) {
						$value = $args[$var];
					} elseif ( is_callable( array('WP_Job_Board_Email', $var) ) ) {
						$value = call_user_func( array('WP_Job_Board_Email', $var), $args );
					} else {
						$value = apply_filters('wp-job-board-render-email-var-'.$var, '', $args);
					}
					$output = str_replace('{{'.$var.'}}', $value, $output);
				}
			}
		}
		return apply_filters( 'wp-job-board-render-emails-vars', $output, $args, $key, $type );
	}

	public static function job_title($args) {
		$output = '';
		if ( isset($args['job']) && !empty($args['job']->post_title) ) {
			$output = $args['job']->post_title;
		}
		return $output;
	}

	public static function job_url($args) {
		$output = '';
		if ( !empty($args['job']) ) {
			$output = get_permalink($args['job']);
		}
		return $output;
	}

	public static function website_url($args) {
		$output = home_url();
		
		return $output;
	}

	public static function website_name($args) {
		$output = get_bloginfo( 'name' );
		
		return $output;
	}

	public static function dashboard_url($args) {
		$output = '';
		$dashboard_page_id = wp_job_board_get_option('user_dashboard_page_id');
		$output = get_permalink($dashboard_page_id);
		return $output;
	}

	public static function my_jobs($args) {
		$output = '';
		$my_jobs_page_id = wp_job_board_get_option('my_jobs_page_id');
		$output = get_permalink($my_jobs_page_id);
		return $output;
	}

	public static function job_admin_edit_url($args) {
		$output = '';
		if ( !empty($args['job']) ) {
			$output = admin_url( sprintf( 'post.php?post=%d&amp;action=edit', $args['job']->ID ) );
		}
		return $output;
	}

	public static function author($job) {
		$output = '';
		if ( !empty($args['job']) && !empty($args['job']->post_author) ) {
			$output = get_the_author_meta( 'display_name', $args['job']->post_author );
		}
		return $output;
	}

	public static function candidate_name($job) {
		$output = '';
		if ( isset($args['candidate']) && !empty($args['candidate']->post_title) ) {
			$output = $args['candidate']->post_title;
		}
		return $output;
	}

	public static function employer_name($job) {
		$output = '';
		if ( isset($args['employer']) && !empty($args['employer']->post_title) ) {
			$output = $args['employer']->post_title;
		}
		return $output;
	}

	public static function login_url($args) {
		$output = '';
		$login_page_id = wp_job_board_get_option('login_register_page_id');
		$output = get_permalink($login_page_id);
		return $output;
	}

	public static function user_name($args) {
		$output = '';
		if ( isset($args['user_obj']) && !empty($args['user_obj']->data->display_name) ) {
			$output = $args['user_obj']->data->display_name;
		}
		return $output;
	}

	public static function user_email($args) {
		$output = '';
		if ( isset($args['user_obj']) && !empty($args['user_obj']->data->user_email) ) {
			$output = $args['user_obj']->data->user_email;
		}
		return $output;
	}

	public static function approve_url($args) {
		$output = '';
		if ( isset($args['user_obj']) && !empty($args['user_obj']->ID) ) {
			$approve_user_page_id = wp_job_board_get_option('approve_user_page_id');
			$admin_url = get_permalink($approve_user_page_id);

			$user_id = $args['user_obj']->ID;
            $code = get_user_meta($user_id, 'account_approve_key', true);
			$output = add_query_arg(array('action' => 'wp_job_board_approve_user', 'user_id' => $user_id, 'approve-key' => $code), $admin_url);
		}
		return $output;
	}
}

WP_Job_Board_Email::init();