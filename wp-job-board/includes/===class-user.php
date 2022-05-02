<?php
/**
 * User
 *
 * @package    wp-job-board
 * @author     Habq 
 * @license    GNU General Public License, version 3
 */

if ( ! defined( 'ABSPATH' ) ) {
  	exit;
}

class WP_Job_Board_User {
	
	public static function init() {
		add_action( 'init', array( __CLASS__, 'add_user_roles' ) );
		add_action( 'init', array( __CLASS__, 'role_caps' ) );
		
		add_action( 'wp_ajax_nopriv_wp_job_board_ajax_login',  array( __CLASS__, 'process_login' ) );
		add_action( 'wp_ajax_nopriv_wp_job_board_ajax_forgotpass',  array( __CLASS__, 'process_forgot_password' ) );
		add_action( 'wp_ajax_nopriv_wp_job_board_ajax_register',  array( __CLASS__, 'process_register' ) );
		
		add_action( 'wp_ajax_wp_job_board_ajax_change_password',  array(__CLASS__,'process_change_password') );
		add_action( 'wp_ajax_nopriv_wp_job_board_ajax_change_password',  array( __CLASS__, 'process_change_password' ) );

		// delete profile
		add_action( 'wp_ajax_wp_job_board_ajax_delete_profile',  array(__CLASS__,'process_delete_profile') );
		add_action( 'wp_ajax_nopriv_wp_job_board_ajax_delete_profile',  array(__CLASS__,'process_delete_profile') );

		add_action( 'delete_user', array(__CLASS__,'process_delete_user'), 10, 2 );

		add_action( 'user_register', array( __CLASS__, 'registration_save' ), 10, 1 );
		add_action( 'cmb2_after_init', array( __CLASS__, 'process_change_profile' ) );
		add_action( 'cmb2_after_init', array( __CLASS__, 'process_change_resume' ) );

		add_filter( 'wp_authenticate_user', array( __CLASS__, 'admin_user_auth_callback' ), 11, 2 );

		// action
		add_action( 'load-users.php', array( __CLASS__, 'process_update_user_action' ) );
		add_filter( 'wp_job_board_new_user_approve_validate_status_update', array( __CLASS__, 'validate_status_update' ), 10, 3 );

		add_action( 'wp_job_board_new_user_approve_approve_user', array( __CLASS__, 'approve_user' ) );
		add_action( 'wp_job_board_new_user_approve_deny_user', array( __CLASS__, 'deny_user' ) );

		// resend approve account
		add_action( 'wp_ajax_wp_job_board_ajax_resend_approve_account',  array(__CLASS__,'process_resend_approve_account') );
		add_action( 'wp_ajax_nopriv_wp_job_board_ajax_resend_approve_account',  array(__CLASS__,'process_resend_approve_account') );

		// Filters
		add_filter( 'user_row_actions', array( __CLASS__, 'user_table_actions' ), 10, 2 );
		add_filter( 'manage_users_columns', array( __CLASS__, 'add_column' ) );
		add_filter( 'manage_users_custom_column', array( __CLASS__, 'status_column' ), 10, 3 );

		add_action( 'restrict_manage_users', array( __CLASS__, 'status_filter' ), 10, 1 );
		add_action( 'pre_user_query', array( __CLASS__, 'filter_by_status' ) );

		// approve user
		add_action( 'wp', array( __CLASS__, 'process_approve_user' ) );
	}

	public static function add_user_roles() {
	    add_role(
            'wp_job_board_candidate', esc_html__('Candidate', 'wp-job-board'), array(
		        'read' => false,
		        'edit_posts' => false,
		        'delete_posts' => false,
            )
	    );
	    add_role(
            'wp_job_board_employer', esc_html__('Employer', 'wp-job-board'), array(
		        'read' => false,
		        'edit_posts' => false,
		        'delete_posts' => false,
            )
	    );
	    add_role(
            'wp_job_board_employee', esc_html__('Employee', 'wp-job-board'), array(
		        'read' => false,
		        'edit_posts' => false,
		        'delete_posts' => false,
            )
	    );
	}

	public static function role_caps() {
	    if ( current_user_can('wp_job_board_candidate') ) {
	        $role = get_role('wp_job_board_candidate');
		    $role->add_cap('upload_files');
		    $role->add_cap('edit_post');
		    $role->add_cap('edit_published_pages');
		    $role->add_cap('edit_others_pages');
		    $role->add_cap('edit_others_posts');
	    }
	    
	    if ( current_user_can('wp_job_board_employer') ) {
		    $role = get_role('wp_job_board_employer');
		    $role->add_cap('upload_files');
		    $role->add_cap('edit_post');
		    $role->add_cap('edit_published_pages');
		    $role->add_cap('edit_others_pages');
		    $role->add_cap('edit_others_posts');
	    }
	}

	public static function is_employer_can_add_submission($user_id = null) {
		if ( empty($user_id) ) {
			$user_id = get_current_user_id();
		}
		$return = false;
		if ( self::is_employer($user_id) ) {
			$return = true;
		} elseif ( self::is_employee($user_id) ) {
			$return = self::is_employee_can_add_submission($user_id);
		}
    	
		return apply_filters( 'wp-job-board-is-employer-can-add-submission', $return, $user_id );
	}

	public static function is_employee_can_add_submission($employee_id) {
		$return = false;
		if ( wp_job_board_get_option('employee_submit_job') == 'on' ) {
			$employer_id = WP_Job_Board_User::get_employer_by_employee_id($employee_id);
			if ( !empty($employer_id) ) {
				$user_id = WP_Job_Board_User::get_user_by_employer_id($employer_id);

				if ( !empty($user_id) ) {
					$return = self::is_employer($user_id);
				}
			}
		}
    	
		return apply_filters( 'wp-job-board-is-employee-can-add-submission', $return, $employee_id );
	}

	public static function is_employer_can_edit_job($job_id) {
		$return = true;
		if ( ! $job_id ) {
			$return = false;
		} elseif ( !is_user_logged_in() ) {
			$return = false;
		} else {
			$user_id = get_current_user_id();
			if ( self::is_employer($user_id) ) {
				$job = get_post( $job_id );
				if ( ! $job || ( absint( $job->post_author ) !== get_current_user_id() && ! current_user_can( 'edit_post', $job_id ) ) ) {
					$return = false;
				}
			} elseif ( self::is_employee($user_id) ) {

				$return = self::is_employee_can_edit_job($job_id, $user_id);
			}
		}

		return apply_filters( 'wp-job-board-is-employer-can-edit-job', $return, $job_id );
	}

	public static function is_employee_can_edit_job($job_id, $employee_id) {
		$return = false;
		if ( wp_job_board_get_option('employee_edit_job') == 'on' ) {
			$employer_id = WP_Job_Board_User::get_employer_by_employee_id($employee_id);

			if ( $job_id ) {
				$job = get_post( $job_id );
				if ( !empty($employer_id) ) {
					$user_id = WP_Job_Board_User::get_user_by_employer_id($employer_id);

					if ( $job && absint( $job->post_author ) == $user_id ) {
						$return = true;
					}
				}
			}
		}
		
		return apply_filters( 'wp-job-board-is-employee-can-edit-job', $return, $job_id );
	}

	public static function is_employer($user_id = null) {
		global $sitepress;
		if ( empty($user_id) && is_user_logged_in() ) {
	        $user_id = get_current_user_id();
	    }
	    $employer_id = get_user_meta($user_id, 'employer_id', true);
	    $employer_id = $employer_id > 0 ? $employer_id : 0;
	    if ($employer_id > 0) {
	    	$employer_id = WP_Job_Board_WPML::get_icl_object_id($employer_id, 'employer');

	        $post = get_post($employer_id);
	        if ( !empty($post->ID) ) {
	            return true;
	        }
	    }
	    return false;
	}

	public static function is_candidate($user_id = 0) {
	    if ( empty($user_id) && is_user_logged_in() ) {
	        $user_id = get_current_user_id();
	    }
	    $candidate_id = get_user_meta($user_id, 'candidate_id', true);
	    $candidate_id = $candidate_id > 0 ? $candidate_id : 0;
	    if ($candidate_id > 0) {
	    	$candidate_id = WP_Job_Board_WPML::get_icl_object_id($candidate_id, 'candidate');

	        $post = get_post($candidate_id);
	        if ( !empty($post->ID) ) {
	            return true;
	        }
	    }
	    return false;
	}

	public static function is_employee($user_id = 0) {
	    if ( empty($user_id) && is_user_logged_in() ) {
	        $user_id = get_current_user_id();
	    }
	    $user_meta = get_userdata($user_id);
	    if (!empty($user_meta->roles) && in_array('wp_job_board_employee', $user_meta->roles)) {
	    	return true;
	    }

	    return false;
	}

	public static function get_user_id($user_id = 0) {
		if ( empty($user_id) && is_user_logged_in() ) {
	        $user_id = get_current_user_id();
	    }
	    $return = $user_id;
	    if ( self::is_employee($user_id) ) {
	    	$employer_id = self::get_employer_by_employee_id($user_id);
	    	$return = WP_Job_Board_User::get_user_by_employer_id($employer_id);
	    }
	    return $return;
	}

	public static function get_user_by_employer_id($employer_id = 0) {
	    $user_id = get_post_meta($employer_id, WP_JOB_BOARD_EMPLOYER_PREFIX . 'user_id', true);
	    $user_id = $user_id > 0 ? $user_id : 0;
	    $wp_user = get_user_by('ID', $user_id);
	    if ($wp_user) {
	        return $wp_user->ID;
	    }
	    return false;
	}

	public static function get_employer_by_user_id($user_id = 0) {
		global $sitepress;
	    $employer_id = get_user_meta($user_id, 'employer_id', true);
	    $employer_id = $employer_id > 0 ? $employer_id : 0;
	    if ($employer_id > 0) {
	    	$employer_id = WP_Job_Board_WPML::get_icl_object_id($employer_id, 'employer');

	        $post = get_post($employer_id);
	        if ( !empty($post->ID) ) {
	            return $post->ID;
	        }
	    }
	    return false;
	}

	public static function get_employer_by_employee_id($employee_user_id = 0) {
		global $sitepress;
	    $employer_ids = WP_Job_Board_Query::get_employee_employers($employee_user_id, array('post_per_page' => 1));
	    if ( !empty($employer_ids) ) {

		    $employer_id = $employer_ids[0];
		    
	    	$employer_id = WP_Job_Board_WPML::get_icl_object_id($employer_id, 'employer');
	        $post = get_post($employer_id);
	        if ( !empty($post->ID) ) {
	            return $post->ID;
	        }
	    }
	    return false;
	}

	public static function get_user_by_candidate_id($candidate_id = 0) {
	    $user_id = get_post_meta($candidate_id, WP_JOB_BOARD_CANDIDATE_PREFIX.'user_id', true);
	    $user_id = $user_id > 0 ? $user_id : 0;
	    $wp_user = get_user_by('ID', $user_id);
	    if ($wp_user) {
	        return $wp_user->ID;
	    }
	    return false;
	}

	public static function get_candidate_by_user_id($user_id = 0) {
		global $sitepress;
	    $candidate_id = get_user_meta($user_id, 'candidate_id', true);
	    $candidate_id = $candidate_id > 0 ? $candidate_id : 0;
	    if ($candidate_id > 0) {
	    	$candidate_id = WP_Job_Board_WPML::get_icl_object_id($candidate_id, 'candidate');

	        $post = get_post($candidate_id);
	        if ( !empty($post->ID) ) {
	            return $post->ID;
	        }
	    }
	    return false;
	}

	public static function get_author_employers() {
		global $wpdb;
		if ( false === ( $author_ids = get_transient( 'wp-job-board-get-filter-employers' ) ) ) {
			$min_posts = 1;
			$author_ids = $wpdb->get_col(
				"SELECT `post_author` FROM
			    (SELECT `post_author`, COUNT(*) AS `count` FROM {$wpdb->posts}
			        WHERE `post_type`='job_listing' AND `post_status`='publish' GROUP BY `post_author`) AS `stats`
			    WHERE `count` >= {$min_posts} ORDER BY `count` DESC;"
			);
			
			set_transient( 'wp-job-board-get-filter-employers', $author_ids );
		}

		return $author_ids;
	}
	
	public static function get_employers() {
        $args = array(
            'posts_per_page' => "-1",
            'post_type' => 'employer',
            'post_status' => 'publish',
            'fields' => 'ids',
            'meta_query' => array(),
        );
        $loop = new WP_Query($args);
        $employers = $loop->posts;
        
        return apply_filters('wp-job-board-get-employers', $employers);
    }

    public static function get_candidates() {
        $args = array(
            'posts_per_page' => "-1",
            'post_type' => 'candidate',
            'post_status' => 'publish',
            'fields' => 'ids',
            'meta_query' => array(),
        );
        $loop = new WP_Query($args);
        $candidates = $loop->posts;
        
        return apply_filters('wp-job-board-get-candidates', $candidates);
    }

	public static function process_login() {
   		check_ajax_referer( 'ajax-login-nonce', 'security_login' );

   		$info = array();
   		
   		$info['user_login'] = isset($_POST['username']) ? $_POST['username'] : '';
	    $info['user_password'] = isset($_POST['password']) ? $_POST['password'] : '';
	    $info['remember'] = isset($_POST['remember']) ? true : false;
		
		if ( empty($info['user_login']) || empty($info['user_password']) ) {
            echo json_encode(array(
            	'status' => false,
            	'msg' => __('Please fill all form fields', 'wp-job-board')
            ));
            die();
        }

		if (filter_var($info['user_login'], FILTER_VALIDATE_EMAIL)) {
            $user_obj = get_user_by('email', $info['user_login']);
        } else {
            $user_obj = get_user_by('login', $info['user_login']);
        }
        $user_id = isset($user_obj->ID) ? $user_obj->ID : '0';
        $user_login_auth = self::get_user_status($user_id);
        if ( $user_login_auth == 'pending' && isset($user_obj->ID) ) {
            echo json_encode(array(
            	'status' => false,
            	'msg' => self::login_msg($user_obj)
            ));
            die();
        } elseif ( $user_login_auth == 'denied' && isset($user_obj->ID) ) {
        	echo json_encode(array(
            	'status' => false,
            	'msg' => __('Your account denied', 'wp-job-board')
            ));
            die();
        }

		$secure_cookie   = '';
        if ( get_user_option( 'use_ssl', $user->ID ) ) {
			$secure_cookie = true;
		}
		$user_signon = wp_signon( $info, $secure_cookie );
	    if ( is_wp_error($user_signon) ){
			$result = json_encode(array('status' => false, 'msg' => esc_html__('Wrong username or password. Please try again!!!', 'wp-job-board')));
	    } else {
			wp_set_current_user($user_signon->ID); 
			$role = 'wp_job_board_candidate';
			if ( self::is_employer($user_signon->ID) ) {
				$role = 'wp_job_board_employer';
			}
	        $result = json_encode( array('status' => true, 'msg' => esc_html__('Signin successful, redirecting...', 'wp-job-board'), 'role' => $role) );
	    }

   		echo trim($result);
   		die();
	}

	public static function process_forgot_password() {
	 	
		// First check the nonce, if it fails the function will break
	    check_ajax_referer( 'ajax-lostpassword-nonce', 'security_lostpassword' );
		
		global $wpdb;
		
		$account = isset($_POST['user_login']) ? $_POST['user_login'] : '';
		
		if( empty( $account ) ) {
			$error = esc_html__( 'Enter an username or e-mail address.', 'wp-job-board' );
		} else {
			if(is_email( $account )) {
				if( email_exists($account) ) {
					$get_by = 'email';
				} else {
					$error = esc_html__( 'There is no user registered with that email address.', 'wp-job-board' );			
				}
			} else if (validate_username( $account )) {
				if( username_exists($account) ) {
					$get_by = 'login';
				} else {
					$error = esc_html__( 'There is no user registered with that username.', 'wp-job-board' );				
				}
			} else {
				$error = esc_html__(  'Invalid username or e-mail address.', 'wp-job-board' );		
			}
		}	
		
		do_action('wp-job-board-process-forgot-password', $_POST);

		if (empty ($error)) {
			if (filter_var($account, FILTER_VALIDATE_EMAIL)) {
	            $user_obj = get_user_by('email', $account);
	        } else {
	            $user_obj = get_user_by('login', $account);
	        }
	        $user_id = isset($user_obj->ID) ? $user_obj->ID : '0';
	        $user_login_auth = self::get_user_status($user_id);
	        if ( $user_login_auth == 'pending' && isset($user_obj->ID) ) {
	            echo json_encode(array(
	            	'status' => false,
	            	'msg' => self::login_msg($user_obj)
	            ));
	            die();
	        } elseif ( $user_login_auth == 'denied' && isset($user_obj->ID) ) {
	            echo json_encode(array(
	            	'status' => false,
	            	'msg' => __('Your account denied.', 'wp-job-board')
	            ));
	            die();
	        }

			$random_password = wp_generate_password();

			$user = get_user_by( $get_by, $account );
				
			$update_user = wp_update_user( array ( 'ID' => $user->ID, 'user_pass' => $random_password ) );
				
			if( $update_user ) {
				
				$from = get_option('admin_email');
				
				$email_to = $user->user_email;
				$subject = wp_job_board_get_option('user_reset_password_subject');
				$subject = str_replace('{{user_name}}', $user->display_name, $subject);

				$sender = 'From: '.get_option('name').' <'.$from.'>' . "\r\n";
				
				$content = wp_job_board_get_option('user_reset_password_content');
				$content = str_replace('{{new_password}}', $random_password, $content);
				$content = str_replace('{{user_name}}', $user_name, $content);
				$content = str_replace('{{user_email}}', $email_to, $content);
				$content = str_replace('{{website_name}}', get_bloginfo( 'name' ), $content);
				$content = str_replace('{{website_url}}', home_url(), $content);

					
				$headers[] = 'MIME-Version: 1.0' . "\r\n";
				$headers[] = 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
				$headers[] = "X-Mailer: PHP \r\n";
				$headers[] = $sender;
					
				$mail = WP_Job_Board_Email::wp_mail( $email_to, $subject, $content, $headers );
				
				if( $mail ) {
					$success = esc_html__( 'Check your email address for you new password.', 'wp-job-board' );
				} else {
					$error = esc_html__( 'System is unable to send you mail containg your new password.', 'wp-job-board' );						
				}
			} else {
				$error =  esc_html__( 'Oops! Something went wrong while updating your account.', 'wp-job-board' );
			}
		}
	
		if ( ! empty( $error ) ) {
			echo json_encode(array('status'=> false, 'msg'=> ($error)));
		}
				
		if ( ! empty( $success ) ) {
			echo json_encode(array('status' => true, 'msg'=> $success ));	
		}
		die();
	}

	public static function process_register() {
		global $reg_errors;
		check_ajax_referer( 'ajax-register-nonce', 'security_register' );
        self::registration_validation( $_POST['username'], $_POST['email'], $_POST['password'], $_POST['confirmpassword'] );
        if ( 1 > count( $reg_errors->get_error_messages() ) ) {

	 		$userdata = array(
		        'user_login' => sanitize_user( $_POST['username'] ),
		        'user_email' => sanitize_email( $_POST['email'] ),
		        'user_pass' => $_POST['password'],
	        );
	        if ( isset($_POST['role']) ) {
	        	$userdata['role'] = $_POST['role'];
	        }
	        $user_id = wp_insert_user( $userdata );
	        if ( ! is_wp_error( $user_id ) ) {
	        	if ( (self::is_employer($user_id) && wp_job_board_get_option('employers_requires_approval', 'auto') != 'auto') ) {
	        		$user_data = get_userdata($user_id);
	        		$jsondata = array(
	            		'status' => true,
	            		'msg' => WP_Job_Board_User::register_msg($user_data),
	            		'redirect' => false
	            	);
	        	} elseif ( (self::is_candidate($user_id) && wp_job_board_get_option('candidates_requires_approval', 'auto') != 'auto') ) {
	        		$user_data = get_userdata($user_id);
	        		$jsondata = array(
	            		'status' => true,
	            		'msg' => WP_Job_Board_User::register_msg($user_data),
	            		'redirect' => false
	            	);
	        	} else {
	        		$jsondata = array(
	        			'status' => true,
	        			'msg' => esc_html__( 'You have registered, redirecting ...', 'wp-job-board' ),
	        			'redirect' => true
	        		);
	        		wp_set_auth_cookie($user_id, true, is_ssl());
	        	}
				
	        } else {
		        $jsondata = array('status' => false, 'msg' => esc_html__( 'Register user error!', 'wp-job-board' ) );
		    }
	    } else {
	    	$jsondata = array('status' => false, 'msg' => implode('<br>', $reg_errors->get_error_messages()) );
	    }
	    echo json_encode($jsondata);
	    exit;
	}

	public static function registration_validation( $username, $email, $password, $confirmpassword, $captcha = true ) {
		global $reg_errors;
		$reg_errors = new WP_Error;

		if ( $captcha && WP_Job_Board_Recaptcha::is_recaptcha_enabled() ) {
			$is_recaptcha_valid = array_key_exists( 'g-recaptcha-response', $_POST ) ? WP_Job_Board_Recaptcha::is_recaptcha_valid( sanitize_text_field( $_POST['g-recaptcha-response'] ) ) : false;
			if ( !$is_recaptcha_valid ) {
				$reg_errors->add('field', esc_html__( 'Captcha is not valid', 'wp-job-board' ) );
			}
		}

		if ( empty( $username ) || empty( $password ) || empty( $email ) || empty( $confirmpassword ) ) {
		    $reg_errors->add('field', esc_html__( 'Required form field is missing', 'wp-job-board' ) );
		}

		if ( 4 > strlen( $username ) ) {
		    $reg_errors->add( 'username_length', esc_html__( 'Username too short. At least 4 characters is required', 'wp-job-board' ) );
		}

		if ( username_exists( $username ) ) {
	    	$reg_errors->add('user_name', esc_html__( 'The username already exists!', 'wp-job-board' ) );
		}

		if ( ! validate_username( $username ) ) {
		    $reg_errors->add( 'username_invalid', esc_html__( 'The username you entered is not valid', 'wp-job-board' ) );
		}

		if ( 5 > strlen( $password ) ) {
	        $reg_errors->add( 'password', esc_html__( 'Password length must be greater than 5', 'wp-job-board' ) );
	    }

	    if ( $password != $confirmpassword ) {
	        $reg_errors->add( 'password', esc_html__( 'Password must be equal Confirm Password', 'wp-job-board' ) );
	    }

	    if ( !is_email( $email ) ) {
		    $reg_errors->add( 'email_invalid', esc_html__( 'Email is not valid', 'wp-job-board' ) );
		}

		if ( email_exists( $email ) ) {
		    $reg_errors->add( 'email', esc_html__( 'Email Already in use', 'wp-job-board' ) );
		}

		if ( !empty($_POST['role']) && $_POST['role'] == 'wp_job_board_employer' ) {
			if ( !empty($_POST['company_name']) ) {
				$post = get_page_by_title($_POST['company_name'], OBJECT, 'employer');
				if ( !empty($post) && !is_wp_error($post) ) {
					$reg_errors->add( 'company', esc_html__( 'Company already exists', 'wp-job-board' ) );
				}
			}
		}
	}

	public static function process_delete_profile() {
        if ( !isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'wp-job-board-delete-profile-nonce' )  ) {
			$return = array( 'status' => false, 'msg' => esc_html__('Your nonce did not verify.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}

		$password = isset($_POST['password']) ? $_POST['password'] : '';
        $user_id = get_current_user_id();
        $userdata = get_user_by('ID', $user_id);

        if ( empty($password) ) {
        	$return = array( 'status' => false, 'msg' => esc_html__('Please Enter the password.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
        }
        if ( !is_object($userdata) || !wp_check_password($password, $userdata->data->user_pass, $user_id) ) {
            $return = array( 'status' => false, 'msg' => esc_html__('Please Enter the correct password.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
        }
        
        do_action( 'wp-job-board-before-delete-profile', $user_id, $userdata );

        wp_delete_user($user_id);
        
        $return = array( 'status' => true, 'msg' => esc_html__('Your profile deleted successfully.', 'wp-job-board') );
	   	echo wp_json_encode($return);
	   	exit;
	}

	public static function process_delete_user($user_id, $reassign) {
		if ( self::is_employer($user_id) ) {
        	$employer_id = self::get_employer_by_user_id($user_id);

            wp_delete_post($employer_id);
        } elseif ( self::is_candidate($user_id) ) {
        	$candidate_id = self::get_candidate_by_user_id($user_id);

        	$candidate_post = get_post($candidate_id);
        	WP_Job_Board_Mpdf::mpdf_delete_file($candidate_post);

            wp_delete_post($candidate_id);
        }
	}

	public static function registration_save($user_id) {
        $action = isset($_POST['action']) && $_POST['action'] != '' ? $_POST['action'] : '';
        $user_role = isset($_POST['role']) && $_POST['role'] != '' ? $_POST['role'] : '';
        $user_obj = get_user_by('ID', $user_id);

        if ($user_role == 'wp_job_board_employer') {
        	$post_title = str_replace(array('-', '_'), array(' ', ' '), $user_obj->display_name);
        	$display_name = $user_obj->display_name;
        	if ( !empty($_POST['company_name']) ) {
                $post_title = $display_name = sanitize_text_field($_POST['company_name']);
                
                $user_def_array = array(
                    'ID' => $user_id,
                    'display_name' => $display_name,
                );
                wp_update_user($user_def_array);
            }

            $post_args = array(
                'post_title' => $post_title,
                'post_type' => 'employer',
                'post_content' => '',
                'post_status' => 'publish',
                'post_author' => $user_id,
            );

            if ( wp_job_board_get_option('employers_requires_approval', 'auto') != 'auto' && $action == 'wp_job_board_ajax_register' ) {
            	$post_args['post_status'] = 'pending';
            }

            // Insert the post into the database
            $employer_id = wp_insert_post($post_args);

            update_post_meta($employer_id, WP_JOB_BOARD_EMPLOYER_PREFIX . 'user_id', $user_id);
            update_post_meta($employer_id, WP_JOB_BOARD_EMPLOYER_PREFIX . 'display_name', $display_name);
            update_post_meta($employer_id, WP_JOB_BOARD_EMPLOYER_PREFIX . 'email', $user_obj->user_email);

            update_post_meta($employer_id, 'post_date', strtotime(current_time('d-m-Y H:i:s')));

            if ( wp_job_board_get_option('employers_requires_approval', 'auto') != 'auto' && $action == 'wp_job_board_ajax_register' ) {
            	$code = WP_Job_Board_Mixes::random_key();
                update_user_meta($user_id, 'account_approve_key', $code);
            	update_user_meta($user_id, 'user_account_status', 'pending');

            	if ( wp_job_board_get_option('employers_requires_approval', 'auto') == 'email_approve' ) {
					$user_email = stripslashes( $user_obj->user_email );
				} else {
					$user_email = get_option( 'admin_email', false );
				}

				$subject = WP_Job_Board_Email::render_email_vars(array('user_obj' => $user_obj), 'user_register_need_approve', 'subject');
				$content = WP_Job_Board_Email::render_email_vars(array('user_obj' => $user_obj), 'user_register_need_approve', 'content');
				
				$email_from = get_option( 'admin_email', false );
				$headers = sprintf( "From: %s <%s>\r\n Content-type: text/html", get_bloginfo('name'), $email_from );
				// send the mail
				WP_Job_Board_Email::wp_mail( $user_email, $subject, $content, $headers );
            } else {
            	$user_email = stripslashes( $user_obj->user_email );
            	$subject = WP_Job_Board_Email::render_email_vars(array('user_obj' => $user_obj), 'user_register_auto_approve', 'subject');
				$content = WP_Job_Board_Email::render_email_vars(array('user_obj' => $user_obj), 'user_register_auto_approve', 'content');

				$email_from = get_option( 'admin_email', false );
				$headers = sprintf( "From: %s <%s>\r\n Content-type: text/html", get_bloginfo('name'), $email_from );
				// send the mail
				WP_Job_Board_Email::wp_mail( $user_email, $subject, $content, $headers );
            }

            if ( isset($_POST['phone']) ) {
	            update_post_meta($employer_id, WP_JOB_BOARD_EMPLOYER_PREFIX . 'phone', $_POST['phone']);
	        }

            if (isset($_POST['employer_category'])) {
                $employer_category = sanitize_text_field($_POST['employer_category']);
                wp_set_post_terms($employer_id, array($employer_category), 'employer_category', false);
            }

            // custom fields saving
            do_action('wp_job_board_signup_custom_fields_save', 'employer', $employer_id);

            //
            update_user_meta($user_id, 'employer_id', $employer_id);
        } elseif ( $user_role == 'wp_job_board_employee' ) {
        	
        	$user_email = stripslashes( $user_obj->user_email );
        	$subject = WP_Job_Board_Email::render_email_vars(array('user_obj' => $user_obj), 'user_register_auto_approve', 'subject');
			$content = WP_Job_Board_Email::render_email_vars(array('user_obj' => $user_obj), 'user_register_auto_approve', 'content');

			$email_from = get_option( 'admin_email', false );
			$headers = sprintf( "From: %s <%s>\r\n Content-type: text/html", get_bloginfo('name'), $email_from );
			// send the mail
			WP_Job_Board_Email::wp_mail( $user_email, $subject, $content, $headers );
        

            do_action('wp_job_board_signup_custom_fields_save', 'employee');

        } elseif ( $user_role != 'administrator' ) {
            $post_args = array(
                'post_title' => str_replace(array('-', '_'), array(' ', ' '), $user_obj->display_name),
                'post_type' => 'candidate',
                'post_content' => '',
                'post_status' => 'publish',
                'post_author' => $user_id,
            );
            $post_status = 'publish';
            if ( wp_job_board_get_option('candidates_requires_approval', 'auto') != 'auto' && $action == 'wp_job_board_ajax_register' ) {
            	$post_status = 'pending';
            }
            if ( wp_job_board_get_option('resumes_requires_approval', 'auto') != 'auto' && $action == 'wp_job_board_ajax_register' ) {
            	$post_status = 'pending_approve';
            }
            $post_args['post_status'] = $post_status;
            
            // Insert the post into the database
            $candidate_id = wp_insert_post($post_args);
            
            update_post_meta($candidate_id, WP_JOB_BOARD_CANDIDATE_PREFIX . 'user_id', $user_id);
            update_post_meta($candidate_id, WP_JOB_BOARD_CANDIDATE_PREFIX . 'display_name', $user_obj->display_name);
            update_post_meta($candidate_id, WP_JOB_BOARD_CANDIDATE_PREFIX . 'email', $user_obj->user_email);

            if ( wp_job_board_get_option('candidates_requires_approval', 'auto') != 'auto' && $action == 'wp_job_board_ajax_register' ) {
            	$code = WP_Job_Board_Mixes::random_key();
                update_user_meta($user_id, 'account_approve_key', $code);
            	update_user_meta($user_id, 'user_account_status', 'pending');

            	if ( wp_job_board_get_option('candidates_requires_approval', 'auto') == 'email_approve' ) {
					$user_email = stripslashes( $user_obj->user_email );
				} else {
					$user_email = get_option( 'admin_email', false );
				}

				$subject = WP_Job_Board_Email::render_email_vars(array('user_obj' => $user_obj), 'user_register_need_approve', 'subject');
				$content = WP_Job_Board_Email::render_email_vars(array('user_obj' => $user_obj), 'user_register_need_approve', 'content');

				$email_from = get_option( 'admin_email', false );
				$headers = sprintf( "From: %s <%s>\r\n Content-type: text/html", get_bloginfo('name'), $email_from );
				// send the mail
				WP_Job_Board_Email::wp_mail( $user_email, $subject, $content, $headers );
            } else {
            	$user_email = stripslashes( $user_obj->user_email );
            	$subject = WP_Job_Board_Email::render_email_vars(array('user_obj' => $user_obj), 'user_register_auto_approve', 'subject');
				$content = WP_Job_Board_Email::render_email_vars(array('user_obj' => $user_obj), 'user_register_auto_approve', 'content');

				$email_from = get_option( 'admin_email', false );
				$headers = sprintf( "From: %s <%s>\r\n Content-type: text/html", get_bloginfo('name'), $email_from );
				// send the mail
				WP_Job_Board_Email::wp_mail( $user_email, $subject, $content, $headers );
            }

            update_post_meta($candidate_id, 'post_date', strtotime(current_time('d-m-Y H:i:s')));

            if ( isset($_POST['phone']) ) {
	            update_post_meta($candidate_id, WP_JOB_BOARD_CANDIDATE_PREFIX . 'phone', $_POST['phone']);
	        }
            if (isset($_POST['candidate_category'])) {
                $candidate_category = sanitize_text_field($_POST['candidate_category']);
                wp_set_post_terms($candidate_id, array($candidate_category), 'candidate_category', false);
            }

            // custom fields saving
            do_action('wp_job_board_signup_custom_fields_save', 'candidate', $candidate_id);
            
            update_user_meta($user_id, 'candidate_id', $candidate_id);
        }

        do_action('wp_job_board_member_after_making_cand_or_emp', $user_id, $user_role);

        //remove user admin bar
        update_user_meta($user_id, 'show_admin_bar_front', false);
	}

	public static function process_change_password() {
		$old_password = sanitize_text_field( $_POST['old_password'] );
		$new_password = sanitize_text_field( $_POST['new_password'] );
		$retype_password = sanitize_text_field( $_POST['retype_password'] );

		if ( empty( $old_password ) || empty( $new_password ) || empty( $retype_password ) ) {
			echo json_encode(array('status' => false, 'msg'=> __( 'All fields are required.', 'wp-job-board' ) ));
			die();
		}

		if ( $new_password != $retype_password ) {
			echo json_encode(array('status' => false, 'msg'=> __( 'New and retyped password are not same.', 'wp-job-board' ) ));
			die();
		}

		$user = wp_get_current_user();
		if ( ! wp_check_password( $old_password, $user->data->user_pass, $user->ID ) ) {
			echo json_encode(array('status' => false, 'msg'=> __( 'Your old password is not correct.', 'wp-job-board' ) ));
			die();
		}

		do_action('wp-job-board-process-change-password', $_POST);

		wp_set_password( $new_password, $user->ID );
		echo json_encode(array('status' => true, 'msg'=> __( 'Your password has been successfully changed.', 'wp-job-board' ) ));
		die();
	}

	public static function process_change_profile() {
		$user_id = self::get_user_id();
		$prefix = '';
		if ( self::is_employer($user_id) ) {
	    	$prefix = WP_JOB_BOARD_EMPLOYER_PREFIX;
	    	$post_id = self::get_employer_by_user_id($user_id);
	    } elseif( self::is_candidate($user_id) ) {
	    	$prefix = WP_JOB_BOARD_CANDIDATE_PREFIX;
	    	$post_id = self::get_candidate_by_user_id($user_id);
	    } else {
	    	return;
	    }

		if ( ! isset( $_POST['submit-cmb-profile'] ) || empty( $_POST[$prefix.'post_type'] ) || !in_array($_POST[$prefix.'post_type'], array('candidate', 'employer') ) ) {
			return;
		}

		$redirect_url = get_permalink( wp_job_board_get_option('edit_profile_page_id') );

		$cmb = cmb2_get_metabox( $prefix . 'front', $post_id );
		if ( ! isset( $_POST[ $cmb->nonce() ] ) || ! wp_verify_nonce( $_POST[ $cmb->nonce() ], $cmb->nonce() ) ) {
			return;
		}
		$data = array(
			'ID'			=> $post_id,
			'post_title'    => sanitize_text_field( $_POST[ $prefix . 'title' ] ),
			'post_content'  => wp_kses( $_POST[ $prefix . 'description' ], '<b><strong><i><em><h1><h2><h3><h4><h5><h6><pre><code><span>' ),
		);

		do_action( 'wp-job-board-process-profile-before-change', $post_id, $prefix );

		$data = apply_filters('wp-job-board-process-profile-data', $data, $post_id, $prefix);
		
		$post_id = wp_update_post( $data );

		if ( ! empty( $post_id ) && ! empty( $_POST['object_id'] ) ) {
			$_POST['object_id'] = $post_id; // object_id in POST contains page ID instead of job ID

			$cmb->save_fields( $post_id, 'post', $_POST );

			if ( self::is_employer($user_id) ) {
				if ( !empty($_POST[$prefix.'team_members']) ) {
					$team_members = $_POST[$prefix.'team_members'];
					if ( isset($_POST['current_'.$prefix.'team_members']) ) {
						foreach ($_POST['current_'.$prefix.'team_members'] as $gkey => $ar_value) {
							foreach ($ar_value as $ikey => $value) {
								if ( is_numeric($value) ) {
									$url = wp_get_attachment_url( $value );
									$team_members[$gkey][$ikey.'_id'] = $value;
									$team_members[$gkey][$ikey] = $url;
								} elseif ( ! empty( $value ) ) {
									$attach_id = WP_Job_Board_Image::create_attachment( $value, $post_id );
									$url = wp_get_attachment_url( $attach_id );
									$team_members[$gkey][$ikey.'_id'] = $attach_id;
									$team_members[$gkey][$ikey] = $url;
								}
							}
						}
						update_post_meta( $post_id, $prefix.'team_members', $team_members );
					}
				}
			}

			// Create featured image
			$featured_image = get_post_meta( $post_id, $prefix . 'featured_image', true );
			if ( ! empty( $_POST[ 'current_' . $prefix . 'featured_image' ] ) ) {
				if ( !empty($featured_image) ) {
					if ( is_array($featured_image) ) {
						$img_id = $featured_image[0];
					} elseif ( is_integer($featured_image) ) {
						$img_id = $featured_image;
					} else {
						$img_id = WP_Job_Board_Image::get_attachment_id_from_url($featured_image);
					}
					set_post_thumbnail( $post_id, $img_id );
				} else {
					update_post_meta( $post_id, $prefix . 'featured_image', null );
					delete_post_thumbnail( $post_id );
				}
			} else {
				update_post_meta( $post_id, $prefix . 'featured_image', null );
				delete_post_thumbnail( $post_id );
			}

			do_action( 'wp-job-board-process-profile-after-change', $post_id, $prefix );

			$_SESSION['messages'][] = array( 'success', __( 'Profile has been successfully updated.', 'wp-job-board' ) );

		} else {
			$_SESSION['messages'][] = array( 'danger', __( 'Can not update profile', 'wp-job-board' ) );
		}

		WP_Job_Board_Mixes::redirect( $redirect_url );
		exit();
	}

	public static function process_change_resume() {
		$user_id = self::get_user_id();
		$prefix = '';
		if( WP_Job_Board_User::is_candidate($user_id) ) {
	    	$prefix = WP_JOB_BOARD_CANDIDATE_PREFIX;
	    	$post_id = WP_Job_Board_User::get_candidate_by_user_id($user_id);
	    } else {
	    	return;
	    }

		if ( ! isset( $_POST['submit-cmb-resume'] ) || empty( $_POST[$prefix.'post_type'] ) || !in_array($_POST[$prefix.'post_type'], array('candidate') ) ) {
			return;
		}

		$redirect_url = esc_url_raw('//' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);

		$cmb = cmb2_get_metabox( $prefix . 'resume_front', $post_id );
		if ( ! isset( $_POST[ $cmb->nonce() ] ) || ! wp_verify_nonce( $_POST[ $cmb->nonce() ], $cmb->nonce() ) ) {
			return;
		}
		$data = array(
			'ID' => $post_id
		);

		do_action( 'wp-job-board-process-resume-before-change', $post_id, $prefix );

		$data = apply_filters('wp-job-board-process-resume-data', $data, $post_id, $prefix);
		
		$post_id = wp_update_post( $data );

		if ( ! empty( $post_id ) && ! empty( $_POST['object_id'] ) ) {
			$_POST['object_id'] = $post_id; // object_id in POST contains page ID instead of job ID

			$cmb->save_fields( $post_id, 'post', $_POST );
			
			do_action( 'wp-job-board-process-resume-after-change', $post_id, $prefix );

			$_SESSION['messages'][] = array( 'success', __( 'Resume has been successfully updated.', 'wp-job-board' ) );

		} else {
			$_SESSION['messages'][] = array( 'danger', __( 'Can not update resume', 'wp-job-board' ) );
		}

		WP_Job_Board_Mixes::redirect( $redirect_url );
		exit();
	}

	public static function compute_profile_percent($post_id) {
		if ( empty($post_id) ) {
			return;
		}
		$prefix = WP_JOB_BOARD_CANDIDATE_PREFIX;
		$metaboxes = apply_filters( 'cmb2_meta_boxes', array() );
		$fields = array();
		if ( !empty($metaboxes[$prefix . 'front']['fields']) ) {
			$fields = array_merge($fields, $metaboxes[$prefix . 'front']['fields']);
		}
		if ( !empty($metaboxes[$prefix . 'resume_front']['fields']) ) {
			$fields = array_merge($fields, $metaboxes[$prefix . 'resume_front']['fields']);
		}
		if ( empty($fields) ) {
			return;
		}

		$profile_percents = $profile_fields = array();
		
		foreach ($fields as $field) {
			if ( empty($field['type']) || empty($field['id']) || $field['type'] == 'hidden' || $field['id'] == $prefix.'profile_url' ) {
				continue;
			}
			switch ($field['id']) {
				case '_candidate_socials':
					$values = get_post_meta($post_id, $field['id'], true);
					if ( !empty($values) ) {
						foreach ($values as $value) {
							if ( !empty($value['network']) && !empty($value['url']) ) {
								$profile_percents[$field['id']] = $field['name'];
								break;
							}
						}
					}
					break;
				case '_candidate_education':
				case '_candidate_experience':
				case '_candidate_award':
				case '_candidate_skill':
					$values = get_post_meta($post_id, $field['id'], true);
					if ( !empty($values) ) {
						foreach ($values as $value) {
							if ( !empty($value['title']) ) {
								$profile_percents[$field['id']] = $field;
								break;
							}
						}
					}
					break;
				case '_candidate_featured_image':
					if ( has_post_thumbnail($post_id) ) {
						$profile_percents[$field['id']] = $field;
					}
					break;
				case '_candidate_title':
					if ( get_the_title($post_id) ) {
						$profile_percents[$field['id']] = $field;
					}
					break;
				case '_candidate_description':
					if ( get_the_content('', false, $post_id) ) {
						$profile_percents[$field['id']] = $field;
					}
					break;
				case '_candidate_category':
					$terms = get_the_terms( $post_id, 'candidate_category' );
					if ( !empty($terms) ) {
						$profile_percents[$field['id']] = $field;
					}
					break;
				case '_candidate_location':
					$terms = get_the_terms( $post_id, 'candidate_location' );
					if ( !empty($terms) ) {
						$profile_percents[$field['id']] = $field;
					}
					break;
				default:
					$value = get_post_meta($post_id, $field['id'], true);
					if ( !empty($value) ) {
						$profile_percents[$field['id']] = $field;
					}
					break;
			}
			$profile_fields[$field['id']] = !empty($field['name']) ? $field['name'] : '';
		}
		$profile_percent = !empty($profile_percents) ? count($profile_percents) : 0;
		$total_fields = !empty($profile_fields) ? count($profile_fields) : 0;
		
		$empty_fields = array();
		foreach ($profile_fields as $key => $name) {
			if ( !isset($profile_percents[$key]) ) {
				$empty_fields[$key] = $name;
			}
		}

		$percent = round($profile_percent/$total_fields, 2);

		return array('percent' => $percent, 'empty_fields' => $empty_fields, 'profile_fields' => $profile_fields);
	}

	public static function process_resend_approve_account() {
		$user_login = isset($_POST['login']) ? $_POST['login'] : '';
		
		if ( empty($user_login) ) {
            echo json_encode(array(
            	'status' => false,
            	'msg' => __('Username or Email not exactly.', 'wp-job-board')
            ));
            die();
        }

		if (filter_var($user_login, FILTER_VALIDATE_EMAIL)) {
            $user_obj = get_user_by('email', $user_login);
        } else {
            $user_obj = get_user_by('login', $user_login);
        }
        if ( !empty($user_obj->ID) ) {
	        $user_login_auth = self::get_user_status($user_obj->ID);
	        if ( $user_login_auth == 'pending' ) {

	        	if ( (self::is_employer($user_obj->ID) && wp_job_board_get_option('employers_requires_approval', 'auto') == 'email_approve') ) {
	        		$user_email = stripslashes( $user_obj->user_email );
	        	} elseif ( (self::is_candidate($user_obj->ID) && wp_job_board_get_option('candidates_requires_approval', 'auto') == 'email_approve') ) {
	        		$user_email = stripslashes( $user_obj->user_email );
	        	} else {
	        		$user_email = get_option( 'admin_email', false );
	        	}

				$subject = WP_Job_Board_Email::render_email_vars(array('user_obj' => $user_obj), 'user_register_need_approve', 'subject');
				$content = WP_Job_Board_Email::render_email_vars(array('user_obj' => $user_obj), 'user_register_need_approve', 'content');

				$email_from = get_option( 'admin_email', false );
				$headers = sprintf( "From: %s <%s>\r\n Content-type: text/html", get_bloginfo('name'), $email_from );

				// send the mail
				$result = WP_Job_Board_Email::wp_mail( $user_email, $subject, $content, $headers );
				if ( $result ) {
					echo json_encode(array(
		            	'status' => true,
		            	'msg' => __('Sent a email successfully.', 'wp-job-board')
		            ));
		            die();
				} else {
					echo json_encode(array(
		            	'status' => false,
		            	'msg' => __('Send a email error.', 'wp-job-board')
		            ));
		            die();
		        }
	        }
        }
        echo json_encode(array(
        	'status' => false,
        	'msg' => __('Your account is not available.', 'wp-job-board')
        ));
        die();
	}

	public static function admin_user_auth_callback($user, $password = '') {
    	global $pagenow;
	    
	    $status = self::get_user_status($user->ID);
	    $message = false;
		switch ( $status ) {
			case 'pending':
				$pending_message = self::login_msg($user);
				$message = new WP_Error( 'pending_approval', $pending_message );
				break;
			case 'denied':
				$denied_message = __('Your account denied.', 'wp-job-board');
				$message = new WP_Error( 'denied_access', $denied_message );
				break;
			case 'approved':
				$message = $user;
				break;
		}

	    return $message;
	}

	public static function process_approve_user() {
		$post = get_post();

		if ( is_object( $post ) ) {
			if ( strpos( $post->post_content, '[wp_job_board_approve_user]' ) !== false ) {
				
				$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : 0;
				$code = isset($_GET['approve-key']) ? $_GET['approve-key'] : 0;
				if ( !$user_id ) {
					$error = array(
						'error' => true,
						'msg' => __('The user is not exists.', 'wp-job-board')
					);

				}
				$user = get_user_by('ID', $user_id);
				if ( empty($user) ) {
					$error = array(
						'error' => true,
						'msg' => __('The user is not exists.', 'wp-job-board')
					);
				} else {
					$user_code = get_user_meta($user_id, 'account_approve_key', true);
					if ( $code != $user_code ) {
						$error = array(
							'error' => true,
							'msg' => __('Code is not exactly.', 'wp-job-board')
						);
					}
				}

				if ( empty($error) ) {
					$return = self::update_user_status($user_id, 'approve');
					$error = array(
						'error' => false,
						'msg' => __('Your account approved.', 'wp-job-board')
					);
					$_SESSION['approve_user_msg'] = $error;
				} else {
					$_SESSION['approve_user_msg'] = $error;
				}
			}
		}
	}

	public static function approve_user( $user_id ) {
		$user = get_user_by('ID', $user_id);

		wp_cache_delete( $user->ID, 'users' );
		wp_cache_delete( $user->data->user_login, 'userlogins' );

		$user_email = stripslashes( $user->data->user_email );

		$subject = WP_Job_Board_Email::render_email_vars(array('user_obj' => $user), 'user_register_approved', 'subject');
		$content = WP_Job_Board_Email::render_email_vars(array('user_obj' => $user), 'user_register_approved', 'content');

		$email_from = get_option( 'admin_email', false );
		$headers = sprintf( "From: %s <%s>\r\n Content-type: text/html", get_bloginfo('name'), $email_from );
		// send the mail
		WP_Job_Board_Email::wp_mail( $user_email, $subject, $content, $headers );

		// change usermeta tag in database to approved
		update_user_meta( $user->ID, 'user_account_status', 'approved' );
		update_user_meta( $user->ID, 'account_approve_key', '' );

		// employer | candidate
		if ( self::is_employer($user->ID) ) {
			$employer_id = self::get_employer_by_user_id($user->ID);
			$data_args = array(
				'post_status' => 'publish',
				'ID' => $employer_id
			);
			remove_action( 'wp_job_board_new_user_approve_approve_user', array( __CLASS__, 'approve_user' ) );
			remove_action('denied_to_publish', array( 'WP_Job_Board_Post_Type_Employer', 'process_denied_to_publish' ) );
			remove_action('pending_to_publish', array( 'WP_Job_Board_Post_Type_Employer', 'process_denied_to_publish' ) );
			$employer_id = wp_update_post( $data_args, true );
			add_action( 'denied_to_publish', array( 'WP_Job_Board_Post_Type_Employer', 'process_pending_to_publish' ) );
			add_action( 'pending_to_publish', array( 'WP_Job_Board_Post_Type_Employer', 'process_pending_to_publish' ) );
			add_action( 'wp_job_board_new_user_approve_approve_user', array( __CLASS__, 'approve_user' ) );
		} elseif ( self::is_candidate($user->ID) ) {
			$post_status = 'publish';
			if ( wp_job_board_get_option('resumes_requires_approval', 'auto') != 'auto' ) {
				$post_status = 'pending_approve';
			}
			$candidate_id = self::get_candidate_by_user_id($user->ID);
			$data_args = array(
				'post_status' => $post_status,
				'ID' => $candidate_id
			);
			remove_action( 'wp_job_board_new_user_approve_approve_user', array( __CLASS__, 'approve_user' ) );
			remove_action('denied_to_publish', array( 'WP_Job_Board_Post_Type_Candidate', 'process_denied_to_publish' ) );
			remove_action('pending_to_publish', array( 'WP_Job_Board_Post_Type_Candidate', 'process_denied_to_publish' ) );
			$candidate_id = wp_update_post( $data_args, true );
			add_action( 'denied_to_publish', array( 'WP_Job_Board_Post_Type_Candidate', 'process_pending_to_publish' ) );
			add_action( 'pending_to_publish', array( 'WP_Job_Board_Post_Type_Candidate', 'process_pending_to_publish' ) );
			add_action( 'wp_job_board_new_user_approve_approve_user', array( __CLASS__, 'approve_user' ) );
		}

		do_action( 'wp-job-board-new_user_approve_user_approved', $user );
	}

	public static function deny_user( $user_id ) {
		$user = get_user_by('ID', $user_id);

		$user_email = stripslashes( $user->data->user_email );

		$subject = WP_Job_Board_Email::render_email_vars(array('user_obj' => $user), 'user_register_denied', 'subject');
		$content = WP_Job_Board_Email::render_email_vars(array('user_obj' => $user), 'user_register_denied', 'content');

		$email_from = get_option( 'admin_email', false );
		$headers = sprintf( "From: %s <%s>\r\n Content-type: text/html", get_bloginfo('name'), $email_from );
		// send the mail
		WP_Job_Board_Email::wp_mail( $user_email, $subject, $content, $headers );

		update_user_meta( $user->ID, 'user_account_status', 'denied' );

		// employer | candidate
		if ( self::is_employer($user->ID) ) {
			$employer_id = self::get_employer_by_user_id($user->ID);
			$data_args = array(
				'post_status' => 'denied',
				'ID' => $employer_id
			);
			$employer_id = wp_update_post( $data_args, true );
		} elseif ( self::is_candidate($user->ID) ) {
			$candidate_id = self::get_candidate_by_user_id($user->ID);

			$data_args = array(
				'post_status' => 'denied',
				'ID' => $candidate_id
			);
			$candidate_id = wp_update_post( $data_args, true );
		}

		do_action( 'wp-job-board-new_user_approve_user_denied', $user );
	}

	public static function get_user_status( $user_id ) {
		$user_status = get_user_meta( $user_id, 'user_account_status', true );

		if ( empty( $user_status ) ) {
			$user_status = 'approved';
		}

		return $user_status;
	}

	public static function update_user_status( $user, $status ) {
		$user_id = absint( $user );
		if ( !$user_id ) {
			return false;
		}

		if ( !in_array( $status, array( 'approve', 'deny' ) ) ) {
			return false;
		}

		$do_update = apply_filters( 'wp_job_board_new_user_approve_validate_status_update', true, $user_id, $status );
		if ( !$do_update ) {
			return false;
		}

		// where it all happens
		do_action( 'wp_job_board_new_user_approve_' . $status . '_user', $user_id );
		do_action( 'wp_job_board_new_user_approve_user_status_update', $user_id, $status );

		return true;
	}

	public static function process_update_user_action() {
		if ( isset( $_GET['action'] ) && in_array( $_GET['action'], array( 'approve', 'deny' ) ) && !isset( $_GET['new_role'] ) ) {
			check_admin_referer( 'wp-job-board' );

			$sendback = remove_query_arg( array( 'approved', 'denied', 'deleted', 'ids', 'wp-job-board-status-query-submit', 'new_role' ), wp_get_referer() );
			if ( !$sendback ) {
				$sendback = admin_url( 'users.php' );
			}

			$wp_list_table = _get_list_table( 'WP_Users_List_Table' );
			$pagenum = $wp_list_table->get_pagenum();
			$sendback = add_query_arg( 'paged', $pagenum, $sendback );

			$status = sanitize_key( $_GET['action'] );
			$user = absint( $_GET['user'] );

			self::update_user_status( $user, $status );

			if ( $_GET['action'] == 'approve' ) {
				$sendback = add_query_arg( array( 'approved' => 1, 'ids' => $user ), $sendback );
			} else {
				$sendback = add_query_arg( array( 'denied' => 1, 'ids' => $user ), $sendback );
			}

			wp_redirect( $sendback );
			exit;
		}
	}

	public static function validate_status_update( $do_update, $user_id, $status ) {
		$current_status = self::get_user_status( $user_id );

		if ( $status == 'approve' ) {
			$new_status = 'approved';
		} else {
			$new_status = 'denied';
		}

		if ( $current_status == $new_status ) {
			$do_update = false;
		}

		return $do_update;
	}

	/**
	 * Add the approve or deny link where appropriate.
	 *
	 * @uses user_row_actions
	 * @param array $actions
	 * @param object $user
	 * @return array
	 */
	public static function user_table_actions( $actions, $user ) {
		if ( $user->ID == get_current_user_id() ) {
			return $actions;
		}

		if ( is_super_admin( $user->ID ) ) {
			return $actions;
		}

		$user_status = self::get_user_status( $user->ID );

		$approve_link = add_query_arg( array( 'action' => 'approve', 'user' => $user->ID ) );
		$approve_link = remove_query_arg( array( 'new_role' ), $approve_link );
		$approve_link = wp_nonce_url( $approve_link, 'wp-job-board' );

		$deny_link = add_query_arg( array( 'action' => 'deny', 'user' => $user->ID ) );
		$deny_link = remove_query_arg( array( 'new_role' ), $deny_link );
		$deny_link = wp_nonce_url( $deny_link, 'wp-job-board' );

		$approve_action = '<a href="' . esc_url( $approve_link ) . '">' . __( 'Approve', 'wp-job-board' ) . '</a>';
		$deny_action = '<a href="' . esc_url( $deny_link ) . '">' . __( 'Deny', 'wp-job-board' ) . '</a>';

		if ( $user_status == 'pending' ) {
			$actions[] = $approve_action;
			$actions[] = $deny_action;
		} else if ( $user_status == 'approved' ) {
			$actions[] = $deny_action;
		} else if ( $user_status == 'denied' ) {
			$actions[] = $approve_action;
		}

		return $actions;
	}

	/**
	 * Add the status column to the user table
	 *
	 * @uses manage_users_columns
	 * @param array $columns
	 * @return array
	 */
	public static function add_column( $columns ) {
		$the_columns['user_status'] = __( 'Status', 'wp-job-board' );

		$newcol = array_slice( $columns, 0, -1 );
		$newcol = array_merge( $newcol, $the_columns );
		$columns = array_merge( $newcol, array_slice( $columns, 1 ) );

		return $columns;
	}

	/**
	 * Show the status of the user in the status column
	 *
	 * @uses manage_users_custom_column
	 * @param string $val
	 * @param string $column_name
	 * @param int $user_id
	 * @return string
	 */
	public static function status_column( $val, $column_name, $user_id ) {
		switch ( $column_name ) {
			case 'user_status' :
				$status = self::get_user_status( $user_id );
				if ( $status == 'approved' ) {
					$status_i18n = __( 'approved', 'wp-job-board' );
				} else if ( $status == 'denied' ) {
					$status_i18n = __( 'denied', 'wp-job-board' );
				} else if ( $status == 'pending' ) {
					$status_i18n = __( 'pending', 'wp-job-board' );
				}
				return $status_i18n;
				break;

			default:
		}

		return $val;
	}

	/**
	 * Add a filter to the user table to filter by user status
	 *
	 * @uses restrict_manage_users
	 */
	public static function status_filter( $which ) {
		$id = 'wp_job_board_filter-' . $which;

		$filter_button = submit_button( __( 'Filter', 'wp-job-board' ), 'button', 'wp-job-board-status-query-submit', false, array( 'id' => 'wp-job-board-status-query-submit' ) );
		$filtered_status = null;
		if ( ! empty( $_REQUEST['wp_job_board_filter-top'] ) || ! empty( $_REQUEST['wp_job_board_filter-bottom'] ) ) {
			$filtered_status = esc_attr( ( ! empty( $_REQUEST['wp_job_board_filter-top'] ) ) ? $_REQUEST['wp_job_board_filter-top'] : $_REQUEST['wp_job_board_filter-bottom'] );
		}
		$statuses = array('pending', 'approved', 'denied');
		?>
		<label class="screen-reader-text" for="<?php echo $id ?>"><?php _e( 'View all users', 'wp-job-board' ); ?></label>
		<select id="<?php echo $id ?>" name="<?php echo $id ?>" style="float: none; margin: 0 0 0 15px;">
			<option value=""><?php _e( 'View all users', 'wp-job-board' ); ?></option>
		<?php foreach ( $statuses as $status ) : ?>
			<option value="<?php echo esc_attr( $status ); ?>"<?php selected( $status, $filtered_status ); ?>><?php echo esc_html( $status ); ?></option>
		<?php endforeach; ?>
		</select>
		<?php echo apply_filters( 'wp_job_board_filter_button', $filter_button ); ?>
		<style>
			#wp-job-board-status-query-submit {
				float: right;
				margin: 2px 0 0 5px;
			}
		</style>
	<?php
	}

	/**
	 * Modify the user query if the status filter is being used.
	 *
	 * @uses pre_user_query
	 * @param $query
	 */
    public static function filter_by_status( $query ) {
		global $wpdb;

		if ( !is_admin() ) {
			return;
		}
		
		if ( ! function_exists( 'get_current_screen' ) ) {
			return false;
		}

		$screen = get_current_screen();
		if ( isset( $screen ) && 'users' != $screen->id ) {
			return;
		}
		$filter = null;
		if ( ! empty( $_REQUEST['wp_job_board_filter-top'] ) || ! empty( $_REQUEST['wp_job_board_filter-bottom'] ) ) {
			$filter = esc_attr( ( ! empty( $_REQUEST['wp_job_board_filter-top'] ) ) ? $_REQUEST['wp_job_board_filter-top'] : $_REQUEST['wp_job_board_filter-bottom'] );
		}
		if ( $filter != null ) {

			$query->query_from .= " INNER JOIN {$wpdb->usermeta} ON ( {$wpdb->users}.ID = $wpdb->usermeta.user_id )";

			if ( 'approved' == $filter ) {
				$query->query_fields = "DISTINCT SQL_CALC_FOUND_ROWS {$wpdb->users}.ID";
				$query->query_from .= " LEFT JOIN {$wpdb->usermeta} AS mt1 ON ({$wpdb->users}.ID = mt1.user_id AND mt1.meta_key = 'user_account_status')";
				$query->query_where .= " AND ( ( $wpdb->usermeta.meta_key = 'user_account_status' AND CAST($wpdb->usermeta.meta_value AS CHAR) = 'approved' ) OR mt1.user_id IS NULL )";
			} else {
				$query->query_where .= " AND ( ($wpdb->usermeta.meta_key = 'user_account_status' AND CAST($wpdb->usermeta.meta_value AS CHAR) = '{$filter}') )";
			}
		}
	}

	public static function register_msg($user) {
		$requires_approval = 'auto';
		if ( in_array('wp_job_board_candidate', $user->roles) ) {
			$requires_approval = wp_job_board_get_option('candidates_requires_approval', 'auto');
		} else {
			$requires_approval = wp_job_board_get_option('employers_requires_approval', 'auto');
		}

		if ( $requires_approval == 'email_approve' ) {
			return __('Registration complete. Before you can login, you must active your account sent to your email address.', 'wp-job-board');
		} elseif ( $requires_approval == 'admin_approve' ) {
			return __('Registration complete. Your account has to be confirmed by an administrator before you can login', 'wp-job-board');
		} else {
			return __('Your account has to be confirmed yet.', 'wp-job-board');
		}
	}
	
	public static function login_msg($user) {
		$requires_approval = 'auto';
		if ( in_array('wp_job_board_candidate', $user->roles) ) {
			$requires_approval = wp_job_board_get_option('candidates_requires_approval', 'auto');
		} else {
			$requires_approval = wp_job_board_get_option('employers_requires_approval', 'auto');
		}
		
		if ( $requires_approval == 'email_approve' ) {
			return sprintf(__('Account account has not confirmed yet, you must active your account with the link sent to your email address. If you did not receive this email, please check your junk/spam folder. <a href="javascript:void(0);" class="wp-job-board-resend-approve-account-btn" data-login="%s">Click here</a> to resend the activation email.', 'wp-job-board'), $user->user_login );
		} elseif ( $requires_approval == 'admin_approve' ) {
			return __('Your account has to be confirmed by an administrator before you can login.', 'wp-job-board');
		} else {
			return __('Your account has to be confirmed yet.', 'wp-job-board');
		}
	}

}

WP_Job_Board_User::init();