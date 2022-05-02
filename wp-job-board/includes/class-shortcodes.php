<?php
/**
 * Shortcodes
 *
 * @package    wp-job-board
 * @author     Habq 
 * @license    GNU General Public License, version 3
 */

if ( ! defined( 'ABSPATH' ) ) {
  	exit;
}

class WP_Job_Board_Shortcodes {
	/**
	 * Initialize shortcodes
	 *
	 * @access public
	 * @return void
	 */
	public static function init() {
	    add_action( 'wp', array( __CLASS__, 'check_logout' ) );

	    // login | register
		add_shortcode( 'wp_job_board_logout', array( __CLASS__, 'logout' ) );
	    add_shortcode( 'wp_job_board_login', array( __CLASS__, 'login' ) );
	    add_shortcode( 'wp_job_board_register', array( __CLASS__, 'register' ) );

	    // profile
	    add_shortcode( 'wp_job_board_user_dashboard', array( __CLASS__, 'user_dashboard' ) );
	    add_shortcode( 'wp_job_board_change_password', array( __CLASS__, 'change_password' ) );
	    add_shortcode( 'wp_job_board_change_profile', array( __CLASS__, 'change_profile' ) );
	    add_shortcode( 'wp_job_board_change_resume', array( __CLASS__, 'change_resume' ) );
	    add_shortcode( 'wp_job_board_blacklist_view', array( __CLASS__, 'blocklist_view' ) );
	    add_shortcode( 'wp_job_board_delete_profile', array( __CLASS__, 'delete_profile' ) );
	    add_shortcode( 'wp_job_board_manage_account', array( __CLASS__, 'manage_account' ) );
	    add_shortcode( 'wp_job_board_approve_user', array( __CLASS__, 'approve_user' ) );
    	
    	// employer
		add_shortcode( 'wp_job_board_submission', array( __CLASS__, 'submission' ) );
	    add_shortcode( 'wp_job_board_my_jobs', array( __CLASS__, 'my_jobs' ) );

	    add_shortcode( 'wp_job_board_job_applicants', array( __CLASS__, 'job_applicants' ) );
	    add_shortcode( 'wp_job_board_my_candidates_shortlist', array( __CLASS__, 'my_candidates_shortlist' ) );
	    add_shortcode( 'wp_job_board_my_candidates_alerts', array( __CLASS__, 'my_candidates_alerts' ) );

	    add_shortcode( 'wp_job_board_employer_employees', array( __CLASS__, 'employer_employees' ) );

	    // candidate
	    add_shortcode( 'wp_job_board_my_jobs_shortlist', array( __CLASS__, 'my_jobs_shortlist' ) );
	    add_shortcode( 'wp_job_board_my_applied', array( __CLASS__, 'my_applied' ) );
	    add_shortcode( 'wp_job_board_my_jobs_alerts', array( __CLASS__, 'my_jobs_alerts' ) );
	    add_shortcode( 'wp_job_board_my_following_employers', array( __CLASS__, 'my_following_employers' ) );

	    add_shortcode( 'wp_job_board_jobs', array( __CLASS__, 'jobs' ) );
	    add_shortcode( 'wp_job_board_employers', array( __CLASS__, 'employers' ) );
	    add_shortcode( 'wp_job_board_candidates', array( __CLASS__, 'candidates' ) );
	}

	/**
	 * Logout checker
	 *
	 * @access public
	 * @param $wp
	 * @return void
	 */
	public static function check_logout( $wp ) {
		$post = get_post();

		if ( is_page() ) {
			if ( has_shortcode( $post->post_content, 'wp_job_board_logout' ) ) {
				wp_redirect( html_entity_decode( wp_logout_url( home_url( '/' ) ) ) );
				exit();
			} elseif ( has_shortcode( $post->post_content, 'wp_job_board_my_jobs' ) ) {
				self::my_jobs_hanlder();
			}
		}
	}

	/**
	 * Logout
	 *
	 * @access public
	 * @return void
	 */
	public static function logout( $atts ) {}

	/**
	 * Login
	 *
	 * @access public
	 * @return string
	 */
	public static function login( $atts ) {
		if ( is_user_logged_in() ) {
		    return WP_Job_Board_Template_Loader::get_template_part( 'misc/loged-in' );
	    }
		return WP_Job_Board_Template_Loader::get_template_part( 'misc/login' );
	}

	/**
	 * Login
	 *
	 * @access public
	 * @return string
	 */
	public static function register( $atts ) {
		if ( is_user_logged_in() ) {
		    return '';
	    }
		return WP_Job_Board_Template_Loader::get_template_part( 'misc/register' );
	}

	/**
	 * Submission index
	 *
	 * @access public
	 * @return string|void
	 */
	public static function submission( $atts ) {
	    if ( ! is_user_logged_in() ) {
		    return WP_Job_Board_Template_Loader::get_template_part( 'misc/need-login' );
	    } else {
	    	$user_id = get_current_user_id();
	    	$meta = get_user_meta( $user_id );
	    	$key_1_value = get_post_meta( $meta['employer_id']['0'], '_employer_cif_number', true );
	    	if($key_1_value == ''){
	    		return WP_Job_Board_Template_Loader::get_template_part( 'misc/cif_need' );
	    	}else{
		    	if ( WP_Job_Board_User::is_employee($user_id) ) {
		    		if ( !WP_Job_Board_User::is_employee_can_add_submission($user_id) ) {
		    			return WP_Job_Board_Template_Loader::get_template_part( 'misc/not-allowed', array('need_role' => 'employer') );
		    		}
		    	} elseif ( !WP_Job_Board_User::is_employer($user_id) ) {
					return WP_Job_Board_Template_Loader::get_template_part( 'misc/not-allowed', array('need_role' => 'employer') );
				}
			}
	    }
	    
		$form = WP_Job_Board_Submit_Form::get_instance();

		return $form->output();
	}

	public static function edit_form( $atts ) {
		if ( ! is_user_logged_in() ) {
		    return WP_Job_Board_Template_Loader::get_template_part( 'misc/need-login' );
	    } elseif ( WP_Job_Board_User::is_employer() || (WP_Job_Board_User::is_employee() && wp_job_board_get_option('employee_edit_job') == 'on')  ) {
			$form = WP_Job_Board_Edit_Form::get_instance();

			return $form->output();
		}

		return WP_Job_Board_Template_Loader::get_template_part( 'misc/not-allowed', array('need_role' => 'employer') );
	}
	
	public static function my_jobs_hanlder() {
		$action = !empty($_REQUEST['action']) ? sanitize_title( $_REQUEST['action'] ) : '';
		$job_id = isset( $_REQUEST['job_id'] ) ? absint( $_REQUEST['job_id'] ) : 0;

		if ( $action == 'relist' || $action == 'continue' ) {
			$submit_form_page_id = wp_job_board_get_option('submit_job_form_page_id');
			if ( $submit_form_page_id ) {
				$submit_page_url = get_permalink($submit_form_page_id);
				wp_safe_redirect( add_query_arg( array( 'job_id' => absint( $job_id ) ), $submit_page_url ) );
				exit;
			}
			
		}
	}

	public static function my_jobs( $atts ) {
		if ( ! is_user_logged_in() ) {
		    return WP_Job_Board_Template_Loader::get_template_part( 'misc/need-login' );
	    } elseif ( WP_Job_Board_User::is_employer() || (WP_Job_Board_User::is_employee() && wp_job_board_get_option('employee_view_my_jobs') == 'on') ) {
			if ( ! empty( $_REQUEST['action'] ) ) {
				$action = sanitize_title( $_REQUEST['action'] );
				
				if ( $action == 'edit' ) {
					return self::edit_form($atts);
				}
			}
			return WP_Job_Board_Template_Loader::get_template_part( 'submission/my-jobs' );
		}
		return WP_Job_Board_Template_Loader::get_template_part( 'misc/not-allowed', array('need_role' => 'employer') );
	}
	
	/**
	 * Employer dashboard
	 *
	 * @access public
	 * @param $atts
	 * @return string
	 */
	public static function user_dashboard( $atts ) {
		if ( ! is_user_logged_in() ) {
		    return WP_Job_Board_Template_Loader::get_template_part( 'misc/need-login' );
	    } else {
			$user_id = get_current_user_id();
		    if ( WP_Job_Board_User::is_employer($user_id) ) {
				$employer_id = WP_Job_Board_User::get_employer_by_user_id($user_id);
				return WP_Job_Board_Template_Loader::get_template_part( 'misc/employer-dashboard', array( 'user_id' => $user_id, 'employer_id' => $employer_id ) );
			} elseif ( WP_Job_Board_User::is_candidate($user_id) ) {
				$candidate_id = WP_Job_Board_User::get_candidate_by_user_id($user_id);
				return WP_Job_Board_Template_Loader::get_template_part( 'misc/candidate-dashboard', array( 'user_id' => $user_id, 'candidate_id' => $candidate_id ) );
			} elseif ( WP_Job_Board_User::is_employee($user_id) && wp_job_board_get_option('employee_view_dashboard') == 'on' ) {
				$user_id = WP_Job_Board_User::get_user_id($user_id);
				$employer_id = WP_Job_Board_User::get_employer_by_user_id($user_id);
				return WP_Job_Board_Template_Loader::get_template_part( 'misc/employer-dashboard', array( 'user_id' => $user_id, 'employer_id' => $employer_id ) );
			}
	    }

    	return WP_Job_Board_Template_Loader::get_template_part( 'misc/not-allowed' );
	}

	/**
	 * Change password
	 *
	 * @access public
	 * @param $atts
	 * @return string
	 */
	public static function change_password( $atts ) {
		if ( ! is_user_logged_in() ) {
			return WP_Job_Board_Template_Loader::get_template_part( 'misc/need-login' );
		}

		return WP_Job_Board_Template_Loader::get_template_part( 'misc/password-form' );
	}

	/**
	 * Change profile
	 *
	 * @access public
	 * @param $atts
	 * @return void
	 */
	public static function change_profile( $atts ) {
		if ( ! is_user_logged_in() ) {
		    return WP_Job_Board_Template_Loader::get_template_part( 'misc/need-login' );
	    }
	    
	    $metaboxes = apply_filters( 'cmb2_meta_boxes', array() );
	    $metaboxes_form = array();
	    $user_id = get_current_user_id();
	    if ( WP_Job_Board_User::is_employer($user_id) ) {
	    	if ( ! isset( $metaboxes[ WP_JOB_BOARD_EMPLOYER_PREFIX . 'front' ] ) ) {
				return __( 'A metabox with the specified \'metabox_id\' doesn\'t exist.', 'wp-job-board' );
			}
			$metaboxes_form = $metaboxes[ WP_JOB_BOARD_EMPLOYER_PREFIX . 'front' ];
			$post_id = WP_Job_Board_User::get_employer_by_user_id($user_id);
	    } elseif( WP_Job_Board_User::is_candidate($user_id) ) {
	    	if ( ! isset( $metaboxes[ WP_JOB_BOARD_CANDIDATE_PREFIX . 'front' ] ) ) {
				return __( 'A metabox with the specified \'metabox_id\' doesn\'t exist.', 'wp-job-board' );
			}
			$metaboxes_form = $metaboxes[ WP_JOB_BOARD_CANDIDATE_PREFIX . 'front' ];
			$post_id = WP_Job_Board_User::get_candidate_by_user_id($user_id);
	    } elseif ( WP_Job_Board_User::is_employee($user_id) && wp_job_board_get_option('employee_edit_employer_profile') == 'on' ) {
	    	$user_id = WP_Job_Board_User::get_user_id($user_id);
	    	if ( ! isset( $metaboxes[ WP_JOB_BOARD_EMPLOYER_PREFIX . 'front' ] ) ) {
				return __( 'A metabox with the specified \'metabox_id\' doesn\'t exist.', 'wp-job-board' );
			}
			$metaboxes_form = $metaboxes[ WP_JOB_BOARD_EMPLOYER_PREFIX . 'front' ];
			$post_id = WP_Job_Board_User::get_employer_by_user_id($user_id);
	    } else {
	    	return WP_Job_Board_Template_Loader::get_template_part( 'misc/not-allowed' );
	    }

		if ( !$post_id ) {
			return WP_Job_Board_Template_Loader::get_template_part( 'misc/not-allowed' );
		}

		wp_enqueue_script('google-maps');
		wp_enqueue_script('select2');
		wp_enqueue_style('select2');
		
		return WP_Job_Board_Template_Loader::get_template_part( 'misc/profile-form', array('post_id' => $post_id, 'metaboxes_form' => $metaboxes_form ) );
	}

	public static function change_resume( $atts ) {
		if ( ! is_user_logged_in() ) {
		    return WP_Job_Board_Template_Loader::get_template_part( 'misc/need-login' );
	    } elseif ( !WP_Job_Board_User::is_candidate() ) {
		    return WP_Job_Board_Template_Loader::get_template_part( 'misc/not-allowed', array('need_role' => 'candidate') );
	    }
	    
	    $metaboxes = apply_filters( 'cmb2_meta_boxes', array() );
	    $metaboxes_form = array();
	    $user_id = WP_Job_Board_User::get_user_id();
	    
    	if ( ! isset( $metaboxes[ WP_JOB_BOARD_CANDIDATE_PREFIX . 'resume_front' ] ) ) {
			return __( 'A metabox with the specified \'metabox_id\' doesn\'t exist.', 'wp-job-board' );
		}
		$metaboxes_form = $metaboxes[ WP_JOB_BOARD_CANDIDATE_PREFIX . 'resume_front' ];
		$post_id = WP_Job_Board_User::get_candidate_by_user_id($user_id);
		
		if ( !$post_id ) {
			return WP_Job_Board_Template_Loader::get_template_part( 'misc/not-allowed', array('need_role' => 'candidate') );
		}

		wp_enqueue_script('google-maps');
		wp_enqueue_script('select2');
		wp_enqueue_style('select2');

		return WP_Job_Board_Template_Loader::get_template_part( 'misc/resume-form', array('post_id' => $post_id, 'metaboxes_form' => $metaboxes_form ) );
	}

	public static function blocklist_view($atts){

		$emp_args = array('post_type' => 'employer','post_status' => 'publish',);
		$emp_id = new WP_Query($emp_args);
		$user_id = get_current_user_id();
		echo '<div class="widget box-employer"><div class="block_list"><h3 class="widget-title">Blocklist Employers</h3><div class="list_emp"><table><thead><tr><th>Employers List</th><th width="10%" style="text-align:center;">Action</th></tr></thead><tbody>';
		if ( $emp_id->have_posts() ) :
		  while ( $emp_id->have_posts() ) : $emp_id->the_post(); 

		  	//the_id();

		  	$user_auth = get_the_author_ID();
		  	$blacklist_data = get_user_meta($user_auth);
		  	$black_array = unserialize($blacklist_data['_blacklist_employer'][0]);

		  	foreach($black_array as $black){
				$blacks[] = $black;
				if($user_id = $blacks) {
					echo '<tr><td><a href="'. get_permalink() .'">'. get_the_title() . '</a></td><td style="text-align:center;"><a href="'. get_permalink() .'"><i class="fa fa-eye" aria-hidden="true"></i></a></td>';
				}
			}
			

		 endwhile;
		 endif;
		 wp_reset_postdata();
		echo '</tbody></table></div></div></div>';
		?>
		<style type="text/css">
			.list_emp ul {
				padding: 0;
				margin: 0;
				list-style-type: none;
			}
		</style>
		<?php
	}

	public static function manage_account($atts){

		if ( ! is_user_logged_in() ) {
		    return WP_Job_Board_Template_Loader::get_template_part( 'misc/need-login' );
	    } elseif ( WP_Job_Board_User::is_employee() ) {
		    return WP_Job_Board_Template_Loader::get_template_part( 'misc/not-allowed' );
	    }
	    $user_id = get_current_user_id();
	    return WP_Job_Board_Template_Loader::get_template_part( 'misc/delete-profile-form', array('user_id' => $user_id) );
	    

	}

	public static function delete_profile($atts) {
		if ( ! is_user_logged_in() ) {
		    return WP_Job_Board_Template_Loader::get_template_part( 'misc/need-login' );
	    } elseif ( WP_Job_Board_User::is_employee() ) {
		    return WP_Job_Board_Template_Loader::get_template_part( 'misc/not-allowed' );
	    }
	    $user_id = get_current_user_id();
	    return WP_Job_Board_Template_Loader::get_template_part( 'misc/delete-profile-form', array('user_id' => $user_id) );
	}

	public static function approve_user($atts) {
	    return WP_Job_Board_Template_Loader::get_template_part( 'misc/approve-user' );
	}

	public static function job_applicants( $atts ) {
		if ( ! is_user_logged_in() ) {
		    return WP_Job_Board_Template_Loader::get_template_part( 'misc/need-login' );
	    } elseif ( WP_Job_Board_User::is_employer() || (WP_Job_Board_User::is_employee() && wp_job_board_get_option('employee_view_applications') == 'on') ) {
		   
		    $user_id = WP_Job_Board_User::get_user_id();
			$jobs_loop = WP_Job_Board_Query::get_posts(array(
				'post_type' => 'job_listing',
				'fields' => 'ids',
				'author' => $user_id,
				'orderby' => 'date',
				'order' => 'DESC',
			));
			$job_ids = array();
			if ( !empty($jobs_loop) && !empty($jobs_loop->posts) ) {
				$job_ids = $jobs_loop->posts;
			}

			return WP_Job_Board_Template_Loader::get_template_part( 'misc/job-applicants', array( 'job_ids' => $job_ids ) );

	    }
	    return WP_Job_Board_Template_Loader::get_template_part( 'misc/not-allowed', array('need_role' => 'employer') );
	}

	public static function my_candidates_shortlist( $atts ) {
		if ( ! is_user_logged_in() ) {
		    return WP_Job_Board_Template_Loader::get_template_part( 'misc/need-login' );
	    } elseif ( WP_Job_Board_User::is_employer() || (WP_Job_Board_User::is_employee() && wp_job_board_get_option('employee_view_shortlist') == 'on') ) {
		    
		    $candidate_ids_list = array();

		    $user_id = WP_Job_Board_User::get_user_id();
			$employer_id = WP_Job_Board_User::get_employer_by_user_id($user_id);
			$employer_ids = WP_Job_Board_WPML::get_all_translations_object_id($employer_id);
			if ( empty($employer_ids) ) {
				$employer_ids = array($employer_id);
			}

			foreach ($employer_ids as $employer_id) {
				$candidate_ids = get_post_meta( $employer_id, WP_JOB_BOARD_EMPLOYER_PREFIX.'shortlist', true );
				if ( !empty($candidate_ids) ) {
					foreach ($candidate_ids as $candidate_id) {
						$ids = WP_Job_Board_WPML::get_all_translations_object_id($candidate_id);
						if ( !empty($ids) ) {
							$candidate_ids_list = array_merge($candidate_ids_list, $ids);
						} else {
							$candidate_ids_list = array_merge($candidate_ids_list, array($candidate_id));
						}
					}
				}
			}
			
		    return WP_Job_Board_Template_Loader::get_template_part( 'misc/candidates-shortlist', array( 'candidate_ids' => $candidate_ids_list ) );
		}

		return WP_Job_Board_Template_Loader::get_template_part( 'misc/not-allowed', array('need_role' => 'employer') );
	}

	public static function my_candidates_alerts( $atts ) {
		if ( ! is_user_logged_in() ) {
		    return WP_Job_Board_Template_Loader::get_template_part( 'misc/need-login' );
	    } elseif ( WP_Job_Board_User::is_employer() || (WP_Job_Board_User::is_employee() && wp_job_board_get_option('employee_view_candidate_alert') == 'on') ) {
		    
		    $user_id = WP_Job_Board_User::get_user_id();
		    if ( get_query_var( 'paged' ) ) {
			    $paged = get_query_var( 'paged' );
			} elseif ( get_query_var( 'page' ) ) {
			    $paged = get_query_var( 'page' );
			} else {
			    $paged = 1;
			}
			$query_vars = array(
			    'post_type' => 'candidate_alert',
			    'posts_per_page'    => get_option('posts_per_page'),
			    'paged'    			=> $paged,
			    'post_status' => 'publish',
			    'fields' => 'ids',
			    'author' => $user_id,
			);
			if ( isset($_GET['search']) ) {
				$query_vars['s'] = $_GET['search'];
			}
			if ( isset($_GET['orderby']) ) {
				switch ($_GET['orderby']) {
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

			$alerts = WP_Job_Board_Query::get_posts($query_vars);

			return WP_Job_Board_Template_Loader::get_template_part( 'misc/my-candidates-alerts', array( 'alerts' => $alerts ) );
		}
		return WP_Job_Board_Template_Loader::get_template_part( 'misc/not-allowed', array('need_role' => 'employer') );
	}

	public static function employer_employees( $atts ) {
		if ( ! is_user_logged_in() ) {
		    return WP_Job_Board_Template_Loader::get_template_part( 'misc/need-login' );
	    } elseif ( !WP_Job_Board_User::is_employer() ) {
		    return WP_Job_Board_Template_Loader::get_template_part( 'misc/not-allowed', array('need_role' => 'employer') );
	    }

	    return WP_Job_Board_Template_Loader::get_template_part( 'misc/employer-employees' );
	}

	public static function my_jobs_shortlist( $atts ) {
		if ( ! is_user_logged_in() ) {
		    return WP_Job_Board_Template_Loader::get_template_part( 'misc/need-login' );
	    } elseif ( !WP_Job_Board_User::is_candidate() ) {
		    return WP_Job_Board_Template_Loader::get_template_part( 'misc/not-allowed', array('need_role' => 'candidate') );
	    }
	    $user_id = WP_Job_Board_User::get_user_id();
		$candidate_id = WP_Job_Board_User::get_candidate_by_user_id($user_id);

		$job_ids = get_post_meta( $candidate_id, WP_JOB_BOARD_CANDIDATE_PREFIX.'shortlist', true );

	    return WP_Job_Board_Template_Loader::get_template_part( 'misc/jobs-shortlist', array( 'job_ids' => $job_ids ) );
	}

	public static function my_applied( $atts ) {
		if ( ! is_user_logged_in() ) {
		    return WP_Job_Board_Template_Loader::get_template_part( 'misc/need-login' );
	    } elseif ( !WP_Job_Board_User::is_candidate() ) {
		    return WP_Job_Board_Template_Loader::get_template_part( 'misc/not-allowed', array('need_role' => 'candidate') );
	    }

	    $user_id = WP_Job_Board_User::get_user_id();
		$candidate_id = WP_Job_Board_User::get_candidate_by_user_id($user_id);

		if ( get_query_var( 'paged' ) ) {
		    $paged = get_query_var( 'paged' );
		} elseif ( get_query_var( 'page' ) ) {
		    $paged = get_query_var( 'page' );
		} else {
		    $paged = 1;
		}
		$candidate_ids = WP_Job_Board_WPML::get_all_translations_object_id($candidate_id);
		if ( empty($candidate_ids) ) {
			$candidate_ids = array($candidate_id);
		}
		$query_vars = array(
		    'post_type' => 'job_applicant',
		    'posts_per_page'    => get_option('posts_per_page'),
		    'paged'    			=> $paged,
		    'post_status' => 'publish',
		    'fields' => 'ids',
		    'meta_query' => array(
		    	array(
			    	'key' => WP_JOB_BOARD_APPLICANT_PREFIX . 'candidate_id',
			    	'value' => $candidate_ids,
			    	'compare' => 'IN',
			    ),
			    
			)
		);
		if ( isset($_GET['search']) ) {
			$meta_query = $query_vars['meta_query'];
			$meta_query[] = array(
		    	'key' => WP_JOB_BOARD_APPLICANT_PREFIX . 'job_name',
		    	'value' => $_GET['search'],
		    	'compare' => 'LIKE',
		    );
			$query_vars['meta_query'] = $meta_query;
		}
		if ( isset($_GET['orderby']) ) {
			switch ($_GET['orderby']) {
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
		$applicants = WP_Job_Board_Query::get_posts($query_vars);

		return WP_Job_Board_Template_Loader::get_template_part( 'misc/jobs-applied', array( 'applicants' => $applicants ) );
	}

	public static function my_jobs_alerts( $atts ) {
		if ( ! is_user_logged_in() ) {
		    return WP_Job_Board_Template_Loader::get_template_part( 'misc/need-login' );
	    } elseif ( !WP_Job_Board_User::is_candidate() ) {
		    return WP_Job_Board_Template_Loader::get_template_part( 'misc/not-allowed', array('need_role' => 'candidate') );
	    }

	    $user_id = WP_Job_Board_User::get_user_id();
	    if ( get_query_var( 'paged' ) ) {
		    $paged = get_query_var( 'paged' );
		} elseif ( get_query_var( 'page' ) ) {
		    $paged = get_query_var( 'page' );
		} else {
		    $paged = 1;
		}

		$query_vars = array(
		    'post_type' => 'job_alert',
		    'posts_per_page'    => get_option('posts_per_page'),
		    'paged'    			=> $paged,
		    'post_status' => 'publish',
		    'fields' => 'ids',
		    'author' => $user_id,
		);
		if ( isset($_GET['search']) ) {
			$query_vars['s'] = $_GET['search'];
		}
		if ( isset($_GET['orderby']) ) {
			switch ($_GET['orderby']) {
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
		$alerts = WP_Job_Board_Query::get_posts($query_vars);

		return WP_Job_Board_Template_Loader::get_template_part( 'misc/my-jobs-alerts', array( 'alerts' => $alerts ) );
	}

	public static function my_following_employers( $atts ) {
		if ( ! is_user_logged_in() ) {
		    return WP_Job_Board_Template_Loader::get_template_part( 'misc/need-login' );
	    } elseif ( !WP_Job_Board_User::is_candidate() ) {
		    return WP_Job_Board_Template_Loader::get_template_part( 'misc/not-allowed', array('need_role' => 'candidate') );
	    }

	    $user_id = WP_Job_Board_User::get_user_id();
	    $ids = get_user_meta($user_id, '_following_employer', true);
	    $employers = array();
	    if ( !empty($ids) && is_array($ids) ) {
		    if ( get_query_var( 'paged' ) ) {
			    $paged = get_query_var( 'paged' );
			} elseif ( get_query_var( 'page' ) ) {
			    $paged = get_query_var( 'page' );
			} else {
			    $paged = 1;
			}
			$query_vars = array(
			    'post_type' => 'employer',
			    'posts_per_page'    => get_option('posts_per_page'),
			    'paged'    			=> $paged,
			    'post_status' => 'publish',
			    'post__in' => $ids,
			);
			if ( isset($_GET['search']) ) {
				$query_vars['s'] = $_GET['search'];
			}
			if ( isset($_GET['orderby']) ) {
				switch ($_GET['orderby']) {
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
			$employers = WP_Job_Board_Query::get_posts($query_vars);
		}
		return WP_Job_Board_Template_Loader::get_template_part( 'misc/my-following-employers', array( 'employers' => $employers ) );
	}

	public static function jobs( $atts ) {
		$atts = wp_parse_args( $atts, array(
			'limit' => wp_job_board_get_option('number_jobs_per_page', 10),
			'post__in' => array(),
			'categories' => array(),
			'types' => array(),
			'locations' => array(),
		));

		if ( get_query_var( 'paged' ) ) {
		    $paged = get_query_var( 'paged' );
		} elseif ( get_query_var( 'page' ) ) {
		    $paged = get_query_var( 'page' );
		} else {
		    $paged = 1;
		}

		$query_args = array(
			'post_type' => 'job_listing',
		    'post_status' => 'publish',
		    'post_per_page' => $atts['limit'],
		    'paged' => $paged,
		);
		$params = true;
		if ( WP_Job_Board_Job_Filter::has_filter() ) {
			$params = $_GET;
		}
		$jobs = WP_Job_Board_Query::get_posts($query_args, $params);
		return WP_Job_Board_Template_Loader::get_template_part( 'misc/jobs', array( 'jobs' => $jobs, 'atts' => $atts ) );
	}

	public static function employers( $atts ) {
		$atts = wp_parse_args( $atts, array(
			'limit' => wp_job_board_get_option('number_employers_per_page', 10),
			'post__in' => array(),
			'categories' => array(),
			'types' => array(),
			'locations' => array(),
		));

		if ( get_query_var( 'paged' ) ) {
		    $paged = get_query_var( 'paged' );
		} elseif ( get_query_var( 'page' ) ) {
		    $paged = get_query_var( 'page' );
		} else {
		    $paged = 1;
		}

		$query_args = array(
			'post_type' => 'employer',
		    'post_status' => 'publish',
		    'post_per_page' => $atts['limit'],
		    'paged' => $paged,
		);
		$params = true;
		if ( WP_Job_Board_Employer_Filter::has_filter() ) {
			$params = $_GET;
		}
		$employers = WP_Job_Board_Query::get_posts($query_args, $params);
		
		return WP_Job_Board_Template_Loader::get_template_part( 'misc/employers', array( 'employers' => $employers, 'atts' => $atts ) );
	}

	public static function candidates( $atts ) {
		$atts = wp_parse_args( $atts, array(
			'limit' => wp_job_board_get_option('number_candidates_per_page', 10),
			'post__in' => array(),
			'categories' => array(),
			'types' => array(),
			'locations' => array(),
		));

		if ( get_query_var( 'paged' ) ) {
		    $paged = get_query_var( 'paged' );
		} elseif ( get_query_var( 'page' ) ) {
		    $paged = get_query_var( 'page' );
		} else {
		    $paged = 1;
		}



		// Get all user meta data for $user_id
		$user_id = get_current_user_id();
		$meta = get_user_meta( $user_id );
		$auther_id = unserialize($meta['_blacklist_employer'][0]);
		$auth_ids = array();
		foreach($auther_id as $auth_id){
			$auth_ids[] = $auth_id;
		}
		if($auth_ids){
			$argse = array(
				'post_type' => 'candidate',
				'author__in' => $auth_ids
			);
			$author_posts = new WP_Query( $argse );
			//$mydata = unserialize($meta['_blacklist_employer']);
			//$author_id = get_post_field( 'post_author', $mydata[0] );
			$block_post_id = array();
			if( $author_posts->have_posts() ) :  while ($author_posts->have_posts()) : $author_posts->the_post();
				$block_post_id[] = get_the_id();
			endwhile; endif; wp_reset_postdata();
		}
		//print_r(get_field('_employer_cif_number', $meta['employer_id']['0']))
		$key_1_value = get_post_meta( $meta['employer_id']['0'], '_employer_cif_number', true );
		if($user_id){
			if($key_1_value){
				$query_args = array(
					'post_type' => 'candidate',
				    'post_status' => 'publish',
				    'post_per_page' => $atts['limit'],
				    'post__not_in' => $block_post_id,
				    'paged' => $paged,
				);
				$params = true;
				if ( WP_Job_Board_Candidate_Filter::has_filter() ) {
					$params = $_GET;
				}
				
				$candidates = WP_Job_Board_Query::get_posts($query_args, $params);

			}else{		

				$candidates = '';
			}
		}else{
		
			$query_args = array(
				'post_type' => 'candidate',
			    'post_status' => 'publish',
			    'post_per_page' => $atts['limit'],
			    'post__not_in' => $block_post_id,
			    'paged' => $paged,
			);
			$params = true;
			if ( WP_Job_Board_Candidate_Filter::has_filter() ) {
				$params = $_GET;
			}
			
			$candidates = WP_Job_Board_Query::get_posts($query_args, $params);
		}
		return WP_Job_Board_Template_Loader::get_template_part( 'misc/candidates', array( 'candidates' => $candidates, 'atts' => $atts ) );
	}
}

WP_Job_Board_Shortcodes::init();