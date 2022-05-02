<?php
/**
 * Post Type: Job Listing
 *
 * @package    wp-job-board
 * @author     Habq 
 * @license    GNU General Public License, version 3
 */

if ( ! defined( 'ABSPATH' ) ) {
  	exit;
}

class WP_Job_Board_Post_Type_Job_Listing {
	public static $prefix = WP_JOB_BOARD_JOB_LISTING_PREFIX;
	public static function init() {
	  	add_action( 'init', array( __CLASS__, 'register_post_type' ) );
	  	add_action( 'admin_menu', array( __CLASS__, 'add_pending_count_to_menu' ) );
	  	add_filter( 'cmb2_meta_boxes', array( __CLASS__, 'fields' ) );
	  	
	  	add_filter( 'wp_insert_post_data', array( __CLASS__, 'fix_post_name' ), 10, 2 );
	  	
	  	add_filter( 'manage_edit-job_listing_columns', array( __CLASS__, 'custom_columns' ) );
		add_action( 'manage_job_listing_posts_custom_column', array( __CLASS__, 'custom_columns_manage' ) );
		add_action('restrict_manage_posts', array( __CLASS__, 'filter_job_listing_by_type' ));
		add_action('parse_query', array( __CLASS__, 'filter_job_listing_by_type_in_query' ));

		add_action('save_post', array( __CLASS__, 'save_post' ), 10, 2 );

		add_action( 'pending_to_publish', array( __CLASS__, 'set_expiry_date' ) );
		add_action( 'pending_payment_to_publish', array( __CLASS__, 'set_expiry_date' ) );
		add_action( 'preview_to_publish', array( __CLASS__, 'set_expiry_date' ) );
		add_action( 'draft_to_publish', array( __CLASS__, 'set_expiry_date' ) );
		add_action( 'auto-draft_to_publish', array( __CLASS__, 'set_expiry_date' ) );
		add_action( 'expired_to_publish', array( __CLASS__, 'set_expiry_date' ) );

		add_action( 'wp_job_board_check_for_expired_jobs', array('WP_Job_Board_Job_Listing', 'check_for_expired_jobs') );
		add_action( 'wp_job_board_delete_old_previews', array('WP_Job_Board_Job_Listing', 'delete_old_previews') );

		add_action( 'wp_job_board_email_daily_notices', array( 'WP_Job_Board_Job_Listing', 'send_admin_expiring_notice' ) );
		add_action( 'wp_job_board_email_daily_notices', array( 'WP_Job_Board_Job_Listing', 'send_employer_expiring_notice' ) );
		add_action( 'template_redirect', array( 'WP_Job_Board_Job_Listing', 'track_job_view' ), 20 );

		add_action( "cmb2_save_field_".self::$prefix."expiry_date", array( __CLASS__, 'save_expiry_date' ), 10, 3 );

		add_action( 'wp_ajax_wp_job_board_ajax_remove_job',  array(__CLASS__,'process_remove_job') );
		add_action( 'wp_ajax_wp_job_board_ajax_mark_filled_job',  array(__CLASS__,'process_mark_filled_job') );
		add_action( 'wp_ajax_wp_job_board_ajax_mark_not_filled_job',  array(__CLASS__,'process_mark_not_filled_job') );
	}

	public static function register_post_type() {
		$labels = array(
			'name'                  => __( 'Jobs', 'wp-job-board' ),
			'singular_name'         => __( 'Job', 'wp-job-board' ),
			'add_new'               => __( 'Add New Job', 'wp-job-board' ),
			'add_new_item'          => __( 'Add New Job', 'wp-job-board' ),
			'edit_item'             => __( 'Edit Job', 'wp-job-board' ),
			'new_item'              => __( 'New Job', 'wp-job-board' ),
			'all_items'             => __( 'All Jobs', 'wp-job-board' ),
			'view_item'             => __( 'View Job', 'wp-job-board' ),
			'search_items'          => __( 'Search Job', 'wp-job-board' ),
			'not_found'             => __( 'No Jobs found', 'wp-job-board' ),
			'not_found_in_trash'    => __( 'No Jobs found in Trash', 'wp-job-board' ),
			'parent_item_colon'     => '',
			'menu_name'             => __( 'Jobs', 'wp-job-board' ),
		);
		$has_archive = true;
		$job_archive = get_option('wp_job_board_job_archive_slug');
		if ( $job_archive ) {
			$has_archive = $job_archive;
		}
		$job_rewrite_slug = get_option('wp_job_board_job_base_slug');
		if ( empty($job_rewrite_slug) ) {
			$job_rewrite_slug = _x( 'job', 'Job slug - resave permalinks after changing this', 'wp-job-board' );
		}
		$rewrite = array(
			'slug'       => $job_rewrite_slug,
			'with_front' => false
		);
		register_post_type( 'job_listing',
			array(
				'labels'            => $labels,
				'supports'          => array( 'title', 'editor', 'thumbnail', 'comments', 'author' ),
				'public'            => true,
				'has_archive'       => $has_archive,
				'rewrite'           => $rewrite,
				'menu_position'     => 51,
				'categories'        => array(),
				'menu_icon'         => 'dashicons-megaphone',
				'show_in_rest'		=> true,
			)
		);
	}

	/**
	 * Adds pending count to WP admin menu label
	 *
	 * @access public
	 * @return void
	 */
	public static function add_pending_count_to_menu() {
		global $menu;
		$menu_item_index = null;

		foreach( $menu as $index => $menu_item ) {
			if ( ! empty( $menu_item[5] ) && $menu_item[5] == 'menu-posts-job_listing' ) {
				$menu_item_index = $index;
				break;
			}
		}

		if ( $menu_item_index ) {
			$pending = wp_count_posts( 'job_listing' )->pending;
			$count = $pending;

			if ( $count > 0 ) {
				$menu_title = $menu[ $menu_item_index ][0];
				$menu_title = sprintf('%s <span class="awaiting-mod"><span class="pending-count">%d</span></span>', $menu_title, $count );
				$menu[ $menu_item_index ][0] = $menu_title;
			}
		}
	}

	public static function fix_post_name( $data, $postarr ) {
		if ( 'job_listing' === $data['post_type']
			&& 'pending' === $data['post_status']
			&& ! current_user_can( 'publish_posts' )
			&& isset( $postarr['post_name'] )
		) {
			$data['post_name'] = $postarr['post_name'];
		}
		return $data;
	}

	public static function save_expiry_date($updated, $action, $obj) {
		if ( $action != 'disabled' ) {
			$key = self::$prefix.'expiry_date';
			$data_to_save = $obj->data_to_save;
			$post_id = !empty($data_to_save['post_ID']) ? $data_to_save['post_ID'] : '';
			$expiry_date = isset($data_to_save[$key]) ? $data_to_save[$key] : '';
			if ( empty( $expiry_date ) ) {
				if ( wp_job_board_get_option( 'submission_duration' ) ) {
					$expires = WP_Job_Board_Job_Listing::calculate_job_expiry( $post_id );
					update_post_meta( $post_id, $key, $expires );
				} else {
					delete_post_meta( $post_id, $key );
				}
			} else {
				update_post_meta( $post_id, self::$prefix.'expiry_date', date( 'Y-m-d', strtotime( sanitize_text_field( $expiry_date ) ) ) );
			}

		}
	}

	public static function save_post($post_id, $post) {
		if ( $post->post_type === 'job_listing' ) {
			$post_args = array();
			if ( !empty($_POST[self::$prefix . 'posted_by']) ) {
				$post_args['post_author'] = $_POST[self::$prefix . 'posted_by'];
			}
			
			if ( !empty($_POST[self::$prefix . 'urgent']) ) {
				$post_args['menu_order'] = -2;
			} elseif ( !empty($_POST[self::$prefix . 'featured']) ) {
				$post_args['menu_order'] = -1;
			} else {
				$post_args['menu_order'] = 0;
			}

			$expiry_date = get_post_meta( $post_id, self::$prefix.'expiry_date', true );
			$today_date = date( 'Y-m-d', current_time( 'timestamp' ) );
			$is_job_listing_expired = $expiry_date && $today_date > $expiry_date;

			if ( $is_job_listing_expired && ! WP_Job_Board_Job_Listing::is_job_status_changing( null, 'draft' ) ) {

				if ( !empty($_POST) ) {
					if ( WP_Job_Board_Job_Listing::is_job_status_changing( 'expired', 'publish' ) ) {
						if ( empty($_POST[self::$prefix.'expiry_date']) || strtotime( $_POST[self::$prefix.'expiry_date'] ) < current_time( 'timestamp' ) ) {
							$expires = WP_Job_Board_Job_Listing::calculate_job_expiry( $post_id );
							update_post_meta( $post_id, self::$prefix.'expiry_date', WP_Job_Board_Job_Listing::calculate_job_expiry( $post_id ) );
							if ( isset( $_POST[self::$prefix.'expiry_date'] ) ) {
								$_POST[self::$prefix.'expiry_date'] = $expires;
							}
						}
					} else {
						$post_args['post_status'] = 'expired';
					}
				}
			}
			if ( !empty($post_args) ) {
				$post_args['ID'] = $post_id;

				remove_action('save_post', array( __CLASS__, 'save_post' ), 10, 2 );
				wp_update_post( $post_args );
				add_action('save_post', array( __CLASS__, 'save_post' ), 10, 2 );
			}

			delete_transient( 'wp_job_board_filter_counts' );
			delete_transient( 'wp-job-board-get-filter-employers' );
			
			clean_post_cache( $post_id );
		}
	}

	public static function set_expiry_date( $post ) {

		if ( $post->post_type === 'job_listing' ) {

			// See if it is already set.
			if ( metadata_exists( 'post', $post->ID, self::$prefix.'expiry_date' ) ) {
				$expires = get_post_meta( $post->ID, self::$prefix.'expiry_date', true );

				// if ( $expires && strtotime( $expires ) < current_time( 'timestamp' ) ) {
				// 	update_post_meta( $post->ID, self::$prefix.'expiry_date', '' );
				// }
			}

			// See if the user has set the expiry manually.
			if ( ! empty( $_POST[self::$prefix.'expiry_date'] ) ) {
				update_post_meta( $post->ID, self::$prefix.'expiry_date', date( 'Y-m-d', strtotime( sanitize_text_field( $_POST[self::$prefix.'expiry_date'] ) ) ) );
			} elseif ( ! isset( $expires ) ) {
				// No manual setting? Lets generate a date if there isn't already one.
				$expires = WP_Job_Board_Job_Listing::calculate_job_expiry( $post->ID );
				update_post_meta( $post->ID, self::$prefix.'expiry_date', $expires );

				// In case we are saving a post, ensure post data is updated so the field is not overridden.
				if ( isset( $_POST[self::$prefix.'expiry_date'] ) ) {
					$_POST[self::$prefix.'expiry_date'] = $expires;
				}
			}
		}
	}

	public static function submission_validate( $data ) {
		$error = array();
		if ( empty($data['post_author']) ) {
			$error[] = array( 'danger', __( 'Please login to submit job', 'wp-job-board' ) );
		}
		if ( empty($data['post_title']) ) {
			$error[] = array( 'danger', __( 'Title is required.', 'wp-job-board' ) );
		}
		if ( empty($data['post_content']) ) {
			$error[] = array( 'danger', __( 'Description is required.', 'wp-job-board' ) );
		}
		return $error;
	}

	public static function process_remove_job() {
		if ( !isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'wp-job-board-delete-job-nonce' )  ) {
			$return = array( 'status' => false, 'msg' => esc_html__('Your nonce did not verify.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}

		if ( ! is_user_logged_in() ) {
	        $return = array( 'status' => false, 'msg' => esc_html__('Please login to remove this job', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}
		$job_id = empty( $_POST['job_id'] ) ? false : intval( $_POST['job_id'] );
		if ( !$job_id ) {
			$return = array( 'status' => false, 'msg' => esc_html__('Job not found', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}
		$user_id = WP_Job_Board_User::get_user_id();
		$is_allowed = WP_Job_Board_Mixes::is_allowed_to_remove( $user_id, $job_id );

		if ( ! $is_allowed ) {
	        $return = array( 'status' => false, 'msg' => esc_html__('You can not remove this job.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}

		if ( wp_delete_post( $job_id ) ) {
			$return = array( 'status' => true, 'msg' => esc_html__('Job has been successfully removed.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		} else {
			$return = array( 'status' => false, 'msg' => esc_html__('An error occured when removing an item.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}
	}

	public static function process_mark_filled_job() {
		if ( !isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'wp-job-board-mark-filled-nonce' )  ) {
			$return = array( 'status' => false, 'msg' => esc_html__('Your nonce did not verify.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}

		if ( ! is_user_logged_in() ) {
	        $return = array( 'status' => false, 'msg' => esc_html__('Please login to mark filled this job', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}
		$job_id = empty( $_POST['job_id'] ) ? false : intval( $_POST['job_id'] );
		if ( !$job_id ) {
			$return = array( 'status' => false, 'msg' => esc_html__('Job not found', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}
		$user_id = WP_Job_Board_User::get_user_id();
		$is_allowed = WP_Job_Board_Mixes::is_allowed_to_remove( $user_id, $job_id );

		if ( ! $is_allowed ) {
	        $return = array( 'status' => false, 'msg' => esc_html__('You can not mark filled this job.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}

		update_post_meta($job_id, self::$prefix.'filled', 'on');

		$return = array( 'status' => true, 'msg' => esc_html__('Job has been successfully mark filled job.', 'wp-job-board'), 'title' => esc_html__('Mark not filled', 'wp-job-board'), 'nonce' => wp_create_nonce( 'wp-job-board-mark-not-filled-nonce' ), 'icon_class' => 'fa fa-lock',
			'label' => '<span class="application-status-label label label-success">'.esc_html__('Filled', 'wp-job-board').'</span>' );
		$return = apply_filters('wp-job-board-mark-filled-job-return', $return);

	   	echo wp_json_encode($return);
	   	exit;
	}

	public static function process_mark_not_filled_job() {
		if ( !isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'wp-job-board-mark-not-filled-nonce' )  ) {
			$return = array( 'status' => false, 'msg' => esc_html__('Your nonce did not verify.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}

		if ( ! is_user_logged_in() ) {
	        $return = array( 'status' => false, 'msg' => esc_html__('Please login to mark not filled this job', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}
		$job_id = empty( $_POST['job_id'] ) ? false : intval( $_POST['job_id'] );
		if ( !$job_id ) {
			$return = array( 'status' => false, 'msg' => esc_html__('Job not found', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}
		$user_id = WP_Job_Board_User::get_user_id();
		$is_allowed = WP_Job_Board_Mixes::is_allowed_to_remove( $user_id, $job_id );

		if ( ! $is_allowed ) {
	        $return = array( 'status' => false, 'msg' => esc_html__('You can not mark not filled this job.', 'wp-job-board') );
		   	echo wp_json_encode($return);
		   	exit;
		}

		delete_post_meta($job_id, self::$prefix.'filled', 'on');

		$return = array( 'status' => true, 'msg' => esc_html__('Job has been successfully mark not filled job.', 'wp-job-board'), 'title' => esc_html__('Mark filled', 'wp-job-board'), 'nonce' => wp_create_nonce( 'wp-job-board-mark-filled-nonce' ), 'icon_class' => 'fa fa-unlock', 'label' => '' );
		$return = apply_filters('wp-job-board-mark-filled-job-return', $return);

	   	echo wp_json_encode($return);
	   	exit;
	}

	/**
	 * Defines custom fields
	 *
	 * @access public
	 * @param array $metaboxes
	 * @return array
	 */
	public static function fields( array $metaboxes ) {
		$employers = array( '' => __('Choose a employer', 'wp-job-board') );
		if ( is_admin() ) {
			$employer_ids = WP_Job_Board_User::get_employers();
			if ( !empty($employer_ids) ) {
				foreach ($employer_ids as $id) {
					$user_id = WP_Job_Board_User::get_user_by_employer_id($id);
					if ( $user_id ) {
						$employers[$user_id] = get_the_title($id);
					}
				}
			}
		}

		$post_author_id = '';
		if ( !empty($_GET['post']) ) {
			$post_author_id = get_post_field( 'post_author', $_GET['post'] );
		}

		$metaboxes[ self::$prefix . 'general' ] = array(
			'id'                        => self::$prefix . 'general',
			'title'                     => __( 'General Options', 'wp-job-board' ),
			'object_types'              => array( 'job_listing' ),
			'context'                   => 'normal',
			'priority'                  => 'high',
			'show_names'                => true,
			'show_in_rest'				=> true,
			'fields'                    => array(
				array(
					'name'              => __( 'Expiry Date', 'wp-job-board' ),
					'id'                => self::$prefix . 'expiry_date',
					'type'              => 'text_date',
					'date_format' 		=> 'Y-m-d',
				),
				array(
					'name'              => __( 'Application Deadline Date', 'wp-job-board' ),
					'id'                => self::$prefix . 'application_deadline_date',
					'type'              => 'text_date',
					'date_format' 		=> 'Y-m-d',
				),
				array(
					'name'              => __( 'Job Apply Type', 'wp-job-board' ),
					'id'                => self::$prefix . 'apply_type',
					'type'              => 'select',
					'options'			=> array(
						'internal' => __( 'Internal', 'wp-job-board' ),
						'external' => __( 'External URL', 'wp-job-board' ),
						'with_email' => __( 'By Email', 'wp-job-board' ),
					)
				),
				array(
					'name'              => __( 'External URL for Apply Job', 'wp-job-board' ),
					'id'                => self::$prefix . 'apply_url',
					'type'              => 'text',
				),
				array(
					'name'              => __( 'Job Apply Email', 'wp-job-board' ),
					'id'                => self::$prefix . 'apply_email',
					'type'              => 'text',
				),
				array(
					'name'              => __( 'Min. Salary', 'wp-job-board' ),
					'id'                => self::$prefix . 'salary',
					'type'              => 'text',
				),
				array(
					'name'              => __( 'Max. Salary', 'wp-job-board' ),
					'id'                => self::$prefix . 'max_salary',
					'type'              => 'text',
				),
				array(
					'name'              => __( 'Salary Type', 'wp-job-board' ),
					'id'                => self::$prefix . 'salary_type',
					'type'              => 'select',
					'options'			=> WP_Job_Board_Mixes::get_default_salary_types()
				),
				array(
					'name'              => __( 'Featured', 'wp-job-board' ),
					'id'                => self::$prefix . 'featured',
					'type'              => 'checkbox',
					'description'		=> __( 'Featured jobs will be sticky during searches, and can be styled differently.', 'wp-job-board' )
				),
				array(
					'name'              => __( 'Urgent Job', 'wp-job-board' ),
					'id'                => self::$prefix . 'urgent',
					'type'              => 'checkbox',
					'description'		=> __( 'Urgent jobs will be sticky during searches, and can be styled differently.', 'wp-job-board' )
				),
				array(
					'name'              => __( 'Filled', 'wp-job-board' ),
					'id'                => self::$prefix . 'filled',
					'type'              => 'checkbox',
					'description'		=> __( 'Filled listings will no longer accept applications.', 'wp-job-board' )
				),
				
				array(
					'name'          => __( 'Posted By', 'wp-job-board' ),
					'id'            => self::$prefix . 'posted_by',
					'type'          => 'user_ajax_search',
					'default'		=> $post_author_id,
					'query_args'	=> array(
						'role'				=> array( 'wp_job_board_employer' ),
						'search_columns' 	=> array( 'user_login', 'user_email' )
					)
				)
			),
		);


		$metaboxes[ self::$prefix . 'map_location' ] = array(
			'id'                        => self::$prefix . 'map_location',
			'title'                     => __( 'Location', 'wp-job-board' ),
			'object_types'              => array( 'job_listing' ),
			'context'                   => 'normal',
			'priority'                  => 'high',
			'show_names'                => true,
			'show_in_rest'				=> true,
			'fields'                    => array(
				array(
					'name'              => __( 'Friendly Address', 'wp-job-board' ),
					'id'                => WP_JOB_BOARD_JOB_LISTING_PREFIX . 'address',
					'type'              => 'text',
				),
				array(
					'id'                => self::$prefix . 'map_location',
					'name'              => __( 'Location', 'wp-job-board' ),
					'type'              => 'pw_map',
					'sanitization_cb'   => 'pw_map_sanitise',
					'split_values'      => true,
				),
			),
		);

		return $metaboxes;
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
		$c_fields = array();
		foreach ($columns as $key => $column) {
			if ( $key == 'title' ) {
				$c_fields['employer-logo'] = __( 'Logo', 'wp-job-board' );
			}
			$c_fields[$key] = $column;
		}
		$fields = array_merge($c_fields, array(
			'type' 				=> __( 'Type', 'wp-job-board' ),
			'location' 			=> __( 'Location', 'wp-job-board' ),
			'posted' 			=> __( 'Posted', 'wp-job-board' ),
			'expires' 			=> __( 'Expires', 'wp-job-board' ),
			'category' 			=> __( 'Category', 'wp-job-board' ),
			'urgent' 			=> __( 'Urgent Job', 'wp-job-board' ),
			'featured' 			=> __( 'Featured', 'wp-job-board' ),
			'filled' 			=> __( 'Filled', 'wp-job-board' ),
			'job_status' 		=> __( 'Status', 'wp-job-board' ),
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
		global $post;
		switch ( $column ) {
			case 'employer-logo':
				if ( $post->post_author ) {
					$employer_id = WP_Job_Board_User::get_employer_by_user_id($post->post_author);
					if ( has_post_thumbnail($employer_id) ) {
						echo get_the_post_thumbnail( $employer_id, 'thumbnail', array(
							'class' => 'attachment-thumbnail attachment-thumbnail-small',
						) );
					} else {
						echo '-';
					}
				}
				break;
			case 'type':
				$terms = get_the_terms( get_the_ID(), 'job_listing_type' );
				if ( is_array( $terms ) ) {
					$job_listing_type = array_shift( $terms );
					$color_value = get_term_meta( $job_listing_type->term_id, '_color', true );
					$style = '';
					if ( $color_value ) {
						$style = 'style="background-color: '.$color_value.'; color: #fff;"';
					}
					echo sprintf( '<a href="?post_type=job_listing&job_listing_type=%s" class="job-type-bg" '.$style.'>%s</a>', $job_listing_type->slug, $job_listing_type->name );
				} else {
					echo '-';
				}
				break;
			case 'location':
				$terms = get_the_terms( get_the_ID(), 'job_listing_location' );
				if ( ! empty( $terms ) ) {
					$location = array_shift( $terms );
					echo sprintf( '<a href="?post_type=job_listing&job_listing_location=%s">%s</a>', $location->slug, $location->name );
				} else {
					echo '-';
				}
				break;
			
			case 'posted':
				echo '<strong>' . esc_html( date_i18n( get_option( 'date_format' ), strtotime( $post->post_date ) ) ) . '</strong><span><br>';
				echo ( empty( $post->post_author ) ? esc_html__( 'by a guest', 'wp-job-board' ) : sprintf( esc_html__( 'by %s', 'wp-job-board' ), '<a href="' . esc_url( add_query_arg( 'author', $post->post_author ) ) . '">' . esc_html( get_the_author() ) . '</a>' ) ) . '</span>';
				break;
			case 'expires':
				$expires = get_post_meta( $post->ID, self::$prefix.'expiry_date', true);
				if ( $expires ) {
					echo '<strong>' . esc_html( date_i18n( get_option( 'date_format' ), strtotime( $expires ) ) ) . '</strong>';
				} else {
					echo '&ndash;';
				}
				break;
			case 'category':
				$terms = get_the_terms( get_the_ID(), 'job_listing_category' );
				if ( ! empty( $terms ) ) {
					$category = array_shift( $terms );
					echo sprintf( '<a href="?post_type=job_listing&job_listing_category=%s">%s</a>', $category->slug, $category->name );
				} else {
					echo '-';
				}
				break;
			case 'urgent':
				$urgent = get_post_meta( get_the_ID(), self::$prefix . 'urgent', true );

				if ( ! empty( $urgent ) ) {
					echo '&#10004;';
				} else {
					echo '&ndash;';
				}
				break;
			case 'featured':
				$featured = get_post_meta( get_the_ID(), self::$prefix . 'featured', true );

				if ( ! empty( $featured ) ) {
					echo '<div class="dashicons dashicons-star-filled"></div>';
				} else {
					echo '<div class="dashicons dashicons-star-empty"></div>';
				}
				break;
			case 'filled':
				$urgent = get_post_meta( get_the_ID(), self::$prefix . 'filled', true );

				if ( ! empty( $urgent ) ) {
					echo '&#10004;';
				} else {
					echo '&ndash;';
				}
				break;
			case 'job_status':
				$status   = $post->post_status;
				$statuses = WP_Job_Board_Job_Listing::job_statuses();

				$status_text = $status;
				if ( !empty($statuses[$status]) ) {
					$status_text = $statuses[$status];
				}
				echo sprintf( '<a href="?post_type=job_listing&post_status=%s">%s</a>', esc_attr( $post->post_status ), '<span class="status-' . esc_attr( $post->post_status ) . '">' . esc_html( $status_text ) . '</span>' );
				break;
		}
	}

	public static function filter_job_listing_by_type() {
		global $typenow;
		if ($typenow == 'job_listing') {
			$selected = isset($_GET['job_listing_type']) ? $_GET['job_listing_type'] : '';
			$terms = get_terms( 'job_listing_type', array('hide_empty' => false,) );
			if ( ! empty( $terms ) && ! is_wp_error( $terms ) ){
				?>
				<select name="job_listing_type">
					<option value=""><?php esc_html_e('All types', 'wp-job-board'); ?></option>
				<?php
				foreach ($terms as $term) {
					?>
					<option value="<?php echo esc_attr($term->slug); ?>" <?php echo trim($term->slug == $selected ? ' selected="selected"' : '') ; ?>><?php echo esc_html($term->name); ?></option>
					<?php
				}
				?>
				</select>
				<?php
			}
			// categories
			$selected = isset($_GET['job_listing_category']) ? $_GET['job_listing_category'] : '';
			$terms = get_terms( 'job_listing_category', array('hide_empty' => false,) );
			if ( ! empty( $terms ) && ! is_wp_error( $terms ) ){
				?>
				<select name="job_listing_category">
					<option value=""><?php esc_html_e('All categories', 'wp-job-board'); ?></option>
				<?php
				foreach ($terms as $term) {
					?>
					<option value="<?php echo esc_attr($term->slug); ?>" <?php echo trim($term->slug == $selected ? ' selected="selected"' : '') ; ?>><?php echo esc_html($term->name); ?></option>
					<?php
				}
				?>
				</select>
				<?php
			}
		}
	}

	public static function filter_job_listing_by_type_in_query($query) {
		global $pagenow;

		$type_id = isset($_GET['job_listing_type']) ? $_GET['job_listing_type'] : '';
		$category_id = isset($_GET['job_listing_category']) ? $_GET['job_listing_category'] : '';
		$location_id = isset($_GET['job_listing_location']) ? $_GET['job_listing_location'] : '';
		$post_author = isset($_GET['post_author']) ? $_GET['post_author'] : '';
		$q_vars    = &$query->query_vars;

		if ( $pagenow == 'edit.php' && isset($q_vars['post_type']) && $q_vars['post_type'] == 'job_listing' ) {
			if ( !empty($post_author) ) {
				$q_vars['author'] = $post_author;
			}
		}
		
	}
}
WP_Job_Board_Post_Type_Job_Listing::init();


