<?php
/**
 * Post Type: Candidate
 *
 * @package    wp-job-board
 * @author     Habq 
 * @license    GNU General Public License, version 3
 */

if ( ! defined( 'ABSPATH' ) ) {
  	exit;
}

class WP_Job_Board_Post_Type_Candidate {

	public static $prefix = WP_JOB_BOARD_CANDIDATE_PREFIX;

	public static function init() {
	  	add_action( 'init', array( __CLASS__, 'register_post_type' ) );
	  	add_action( 'admin_menu', array( __CLASS__, 'add_pending_count_to_menu' ) );

	  	add_filter( 'cmb2_meta_boxes', array( __CLASS__, 'fields' ) );
	  	add_filter( 'cmb2_meta_boxes', array( __CLASS__, 'fields_front' ) );
	  	add_filter( 'cmb2_meta_boxes', array( __CLASS__, 'fields_resume_front' ) );
	  	add_filter( 'manage_edit-candidate_columns', array( __CLASS__, 'custom_columns' ) );
		add_action( 'manage_candidate_posts_custom_column', array( __CLASS__, 'custom_columns_manage' ) );

		add_action('restrict_manage_posts', array( __CLASS__, 'filter_candidate_by_type' ));

		add_action('save_post', array( __CLASS__, 'save_post' ), 10, 2 );

		add_action( 'pending_to_publish', array( __CLASS__, 'set_expiry_date' ) );
		add_action( 'pending_approve_to_publish', array( __CLASS__, 'set_expiry_date' ) );
		add_action( 'preview_to_publish', array( __CLASS__, 'set_expiry_date' ) );
		add_action( 'draft_to_publish', array( __CLASS__, 'set_expiry_date' ) );
		add_action( 'auto-draft_to_publish', array( __CLASS__, 'set_expiry_date' ) );
		add_action( 'expired_to_publish', array( __CLASS__, 'set_expiry_date' ) );


		add_action( 'wp_job_board_check_for_expired_jobs', array('WP_Job_Board_Candidate', 'check_for_expired_candidates') );

		add_action( 'wp_job_board_email_daily_notices', array( 'WP_Job_Board_Candidate', 'send_admin_expiring_notice' ) );
		add_action( 'wp_job_board_email_daily_notices', array( 'WP_Job_Board_Candidate', 'send_candidate_expiring_notice' ) );


		add_action( "cmb2_save_field_".self::$prefix."expiry_date", array( __CLASS__, 'save_expiry_date' ), 10, 3 );

		add_action( 'denied_to_publish', array( __CLASS__, 'process_denied_to_publish' ) );
		add_action( 'pending_to_publish', array( __CLASS__, 'process_pending_to_publish' ) );
	}

	public static function register_post_type() {
		$labels = array(
			'name'                  => __( 'Candidates', 'wp-job-board' ),
			'singular_name'         => __( 'Candidate', 'wp-job-board' ),
			'add_new'               => __( 'Add New Candidate', 'wp-job-board' ),
			'add_new_item'          => __( 'Add New Candidate', 'wp-job-board' ),
			'edit_item'             => __( 'Edit Candidate', 'wp-job-board' ),
			'new_item'              => __( 'New Candidate', 'wp-job-board' ),
			'all_items'             => __( 'All Candidates', 'wp-job-board' ),
			'view_item'             => __( 'View Candidate', 'wp-job-board' ),
			'search_items'          => __( 'Search Candidate', 'wp-job-board' ),
			'not_found'             => __( 'No Candidates found', 'wp-job-board' ),
			'not_found_in_trash'    => __( 'No Candidates found in Trash', 'wp-job-board' ),
			'parent_item_colon'     => '',
			'menu_name'             => __( 'Candidates', 'wp-job-board' ),
		);
		$has_archive = true;
		$candidate_archive = get_option('wp_job_board_candidate_archive_slug');
		if ( $candidate_archive ) {
			$has_archive = $candidate_archive;
		}
		$rewrite_slug = get_option('wp_job_board_candidate_base_slug');
		if ( empty($rewrite_slug) ) {
			$rewrite_slug = _x( 'candidate', 'Candidate slug - resave permalinks after changing this', 'wp-job-board' );
		}
		$rewrite = array(
			'slug'       => $rewrite_slug,
			'with_front' => false
		);
		register_post_type( 'candidate',
			array(
				'labels'            => $labels,
				'supports'          => array( 'title', 'editor', 'thumbnail', 'comments' ),
				'public'            => true,
				'has_archive'       => $has_archive,
				'rewrite'           => $rewrite,
				'menu_position'     => 51,
				'categories'        => array(),
				'menu_icon'         => 'dashicons-admin-post',
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
			if ( ! empty( $menu_item[5] ) && $menu_item[5] == 'menu-posts-candidate' ) {
				$menu_item_index = $index;
				break;
			}
		}

		if ( $menu_item_index ) {
			$pending_approve = wp_count_posts( 'candidate' )->pending_approve;
			$pending = wp_count_posts( 'candidate' )->pending;
			$count = $pending_approve + $pending;

			if ( $count > 0 ) {
				$menu_title = $menu[ $menu_item_index ][0];
				$menu_title = sprintf('%s <span class="awaiting-mod"><span class="pending-count">%d</span></span>', $menu_title, $count );
				$menu[ $menu_item_index ][0] = $menu_title;
			}
		}
	}

	public static function save_expiry_date($updated, $action, $obj) {
		if ( $action != 'disabled' ) {
			$key = self::$prefix.'expiry_date';
			$data_to_save = $obj->data_to_save;
			$post_id = !empty($data_to_save['post_ID']) ? $data_to_save['post_ID'] : '';
			$expiry_date = isset($data_to_save[$key]) ? $data_to_save[$key] : '';
			if ( empty( $expiry_date ) ) {
				if ( wp_job_board_get_option( 'resume_duration' ) ) {
					$expires = WP_Job_Board_Candidate::calculate_candidate_expiry( $post_id );
					update_post_meta( $post_id, $key, $expires );
				} else {
					delete_post_meta( $post_id, $key );
				}
			} else {
				update_post_meta( $post->ID, self::$prefix.'expiry_date', date( 'Y-m-d', strtotime( sanitize_text_field( $expiry_date ) ) ) );
			}

		}
	}

	public static function save_post($post_id, $post) {
		if ( $post->post_type === 'candidate' ) {
			$post_args = array( 'ID' => $post_id );
			
			if ( !empty($_POST[self::$prefix . 'urgent']) ) {
				$post_args['menu_order'] = -2;
			} elseif ( !empty($_POST[self::$prefix . 'featured']) ) {
				$post_args['menu_order'] = -1;
			} else {
				$post_args['menu_order'] = 0;
			}

			$expiry_date = get_post_meta( $post_id, self::$prefix.'expiry_date', true );
			$today_date = date( 'Y-m-d', current_time( 'timestamp' ) );
			$is_candidate_listing_expired = $expiry_date && $today_date > $expiry_date;

			if ( $is_candidate_listing_expired && ! WP_Job_Board_Candidate::is_candidate_status_changing( null, 'draft' ) ) {

				if ( !empty($_POST) ) {
					if ( WP_Job_Board_Candidate::is_candidate_status_changing( 'expired', 'publish' ) ) {
						if ( empty($_POST[self::$prefix.'expiry_date']) || strtotime( $_POST[self::$prefix.'expiry_date'] ) < current_time( 'timestamp' ) ) {
							$expires = WP_Job_Board_Candidate::calculate_candidate_expiry( $post_id );
							update_post_meta( $post_id, self::$prefix.'expiry_date', WP_Job_Board_Candidate::calculate_candidate_expiry( $post_id ) );
							if ( isset( $_POST[self::$prefix.'expiry_date'] ) ) {
								$_POST[self::$prefix.'expiry_date'] = $expires;
							}
						}
					} else {
						$post_args['post_status'] = 'expired';
					}
				}
			}

			WP_Job_Board_Mpdf::mpdf_delete_file($post);

			$profile_percents = WP_Job_Board_User::compute_profile_percent($post_id);
			if ( isset($profile_percents['percent']) ) {
				update_post_meta($post_id, self::$prefix .'profile_percent', $profile_percents['percent']);
			}

			remove_action('save_post', array( __CLASS__, 'save_post' ), 10, 2 );
			wp_update_post( $post_args );
			add_action('save_post', array( __CLASS__, 'save_post' ), 10, 2 );

			delete_transient( 'wp_job_board_filter_counts' );
			
			clean_post_cache( $post_id );
		}
	}

	public static function set_expiry_date( $post ) {

		if ( $post->post_type === 'candidate' ) {

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
				$expires = WP_Job_Board_Candidate::calculate_candidate_expiry( $post->ID );
				update_post_meta( $post->ID, self::$prefix.'expiry_date', $expires );

				// In case we are saving a post, ensure post data is updated so the field is not overridden.
				if ( isset( $_POST[self::$prefix.'expiry_date'] ) ) {
					$_POST[self::$prefix.'expiry_date'] = $expires;
				}
			}
		}
	}

	public static function process_denied_to_publish($post) {
		if ( $post->post_type === 'candidate' ) {
			$user_id = WP_Job_Board_User::get_user_by_candidate_id($post->ID);
			remove_action('denied_to_publish', array( __CLASS__, 'process_denied_to_publish' ) );
			do_action( 'wp_job_board_new_user_approve_approve_user', $user_id );
			add_action( 'denied_to_publish', array( __CLASS__, 'process_denied_to_publish' ) );
		}
	}
	
	public static function process_pending_to_publish($post) {
		if ( $post->post_type === 'candidate' ) {
			$user_id = WP_Job_Board_User::get_user_by_candidate_id($post->ID);
			remove_action('pending_to_publish', array( __CLASS__, 'process_pending_to_publish' ) );
			do_action( 'wp_job_board_new_user_approve_approve_user', $user_id );
			add_action( 'pending_to_publish', array( __CLASS__, 'process_pending_to_publish' ) );
		}
	}

	/**
	 * Defines custom fields
	 *
	 * @access public
	 * @param array $metaboxes
	 * @return array
	 */
	public static function fields( array $metaboxes ) {
		
		$cv_file_types = array();
		if ( is_admin() ) {
			$cv_file_type_keys = wp_job_board_get_option('cv_file_types', array('doc', 'docx', 'pdf'));
			$all_cv_file_types = WP_Job_Board_Mixes::get_cv_mime_types();
			
			foreach ($cv_file_type_keys as $mime) {
				if ( !empty($all_cv_file_types[$mime]) ) {
					$cv_file_types[] = $all_cv_file_types[$mime];
				}
			}
			if ( !empty($cv_file_types) ) {
				$cv_file_types = array_unique($cv_file_types);
			} else {
		        $cv_file_types = array(
		            'application/msword',
		            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		            'application/pdf',
		        );
		    }
	    }
	    $currency_symbol = wp_job_board_get_option('currency_symbol', '$');

		$metaboxes[ self::$prefix . 'general' ] = array(
			'id'                        => self::$prefix . 'general',
			'title'                     => __( 'General Options', 'wp-job-board' ),
			'object_types'              => array( 'candidate' ),
			'context'                   => 'normal',
			'priority'                  => 'high',
			'show_names'                => true,
			'show_in_rest'				=> true,
			'fields'                    => array(
				array(
					'name'              => __( 'Attached User', 'wp-job-board' ),
					'id'                => self::$prefix . 'attached_user',
					'type'              => 'wp_job_board_attached_user',
				),
				array(
					'name'              => __( 'Expiry Date', 'wp-job-board' ),
					'id'                => self::$prefix. 'expiry_date',
					'type'              => 'text_date',
					'date_format' 		=> 'Y-m-d',
				),
				array(
					'name'              => __( 'Show my profile', 'wp-job-board' ),
					'id'                => self::$prefix . 'show_profile',
					'type'              => 'select',
					'options'			=> array(
						'show'	=> __( 'Show', 'wp-job-board' ),
						'hide'	=> __( 'Hide', 'wp-job-board' ),
					),
				),
				array(
					'name'              => __( 'Date of Birth', 'wp-job-board' ),
					'id'                => self::$prefix . 'founded_date',
					'type'              => 'text_date',
					'attributes'		=> array(
						'data-datepicker' => json_encode(array(
							'yearRange' => '-100:+5',
						))
					),
				),
				array(
					'name'              => __( 'Phone Number', 'wp-job-board' ),
					'id'                => self::$prefix . 'phone',
					'type'              => 'text',
				),
				array(
					'name'              => __( 'Urgent Candidate', 'wp-job-board' ),
					'id'                => self::$prefix . 'urgent',
					'type'              => 'checkbox',
					'description'		=> __( 'Urgent candidate will be sticky during searches, and can be styled differently.', 'wp-job-board' )
				),
				array(
					'name'              => __( 'Featured Candidate', 'wp-job-board' ),
					'id'                => self::$prefix . 'featured',
					'type'              => 'checkbox',
					'description'		=> __( 'Featured candidate will be sticky during searches, and can be styled differently.', 'wp-job-board' )
				),
				array(
					'name'              => __( 'Job Title', 'wp-job-board' ),
					'id'                => self::$prefix . 'job_title',
					'type'              => 'text',
				),
				array(
					'name'              => sprintf(__( 'Salary (%s)', 'wp-job-board' ), $currency_symbol),
					'id'                => self::$prefix . 'salary',
					'type'              => 'text',
				),
				array(
					'name'              => __( 'Salary Type', 'wp-job-board' ),
					'id'                => self::$prefix . 'salary_type',
					'type'              => 'select',
					'options'			=> WP_Job_Board_Mixes::get_default_salary_types()
				),
				array(
					'name'              => __( 'Portfolio Photos', 'wp-job-board' ),
					'id'                => self::$prefix . 'portfolio_photos',
					'type'              => 'file_list',
					'query_args' => array( 'type' => 'image' ), // Only images attachment
					'text' => array(
						'add_upload_files_text' => __( 'Add or Upload Images', 'wp-job-board' ),
					),
				),
				array(
					'name'              => __( 'CV Attachment', 'wp-job-board' ),
					'id'                => self::$prefix . 'cv_attachment',
					'type'              => 'file_list',
					'query_args' => array(
						'type' => $cv_file_types
					),
					'description' => __('Upload file .pdf, .doc, .docx', 'wp-job-board')
				),
				array(
					'name'              => __( 'Introduction Video URL (Youtube/Vimeo)', 'wp-job-board' ),
					'id'                => self::$prefix . 'video_url',
					'type'              => 'text',
				),
			),
		);

		$metaboxes[ self::$prefix . 'socials' ] = array(
			'id'                        => self::$prefix . 'socials',
			'title'                     => __( 'Socials', 'wp-job-board' ),
			'object_types'              => array( 'candidate' ),
			'context'                   => 'normal',
			'priority'                  => 'high',
			'show_names'                => true,
			'show_in_rest'				=> true,
			'fields'                    => array(
				array(
					'name'              => __( 'Socials', 'wp-job-board' ),
					'id'                => self::$prefix . 'socials',
					'type'              => 'group',
					'options'     		=> array(
						'group_title'       => __( 'Network {#}', 'wp-job-board' ),
						'add_button'        => __( 'Add Another Network', 'wp-job-board' ),
						'remove_button'     => __( 'Remove Network', 'wp-job-board' ),
						'sortable'          => false,
						'closed'         => true,
					),
					'fields'			=> array(
						array(
							'name'      => __( 'Network', 'wp-job-board' ),
							'id'        => 'network',
							'type'      => 'select',
							'options'   => WP_Job_Board_Mixes::get_socials_network()
						),
						array(
							'name'      => __( 'Url', 'wp-job-board' ),
							'id'        => 'url',
							'type'      => 'text',
						),
					),
				),
			),
		);

		$metaboxes[ self::$prefix . 'map_location' ] = array(
			'id'                        => self::$prefix . 'map_location',
			'title'                     => __( 'Location', 'wp-job-board' ),
			'object_types'              => array( 'candidate' ),
			'context'                   => 'normal',
			'priority'                  => 'high',
			'show_names'                => true,
			'show_in_rest'				=> true,
			'fields'                    => array(
				array(
					'name'              => __( 'Friendly Address', 'wp-job-board' ),
					'id'                => self::$prefix . 'address',
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

		$metaboxes[ self::$prefix . 'education' ] = array(
			'id'                        => self::$prefix . 'education',
			'title'                     => __( 'Education', 'wp-job-board' ),
			'object_types'              => array( 'candidate' ),
			'context'                   => 'normal',
			'priority'                  => 'high',
			'show_names'                => true,
			'show_in_rest'				=> true,
			'fields'                    => array(
				array(
					'name'              => __( 'Education', 'wp-job-board' ),
					'id'                => self::$prefix . 'education',
					'type'              => 'group',
					'options'     		=> array(
						'group_title'       => __( 'Education {#}', 'wp-job-board' ),
						'add_button'        => __( 'Add Another Education', 'wp-job-board' ),
						'remove_button'     => __( 'Remove Education', 'wp-job-board' ),
						'sortable'          => false,
						'closed'         => true,
					),
					'fields'			=> array(
						array(
							'name'      => __( 'Title', 'wp-job-board' ),
							'id'        => 'title',
							'type'      => 'text',
						),
						array(
							'name'      => __( 'Academy', 'wp-job-board' ),
							'id'        => 'academy',
							'type'      => 'text',
						),
						array(
							'name'      => __( 'Year', 'wp-job-board' ),
							'id'        => 'year',
							'type'      => 'text',
						),
						array(
							'name'      => __( 'Description', 'wp-job-board' ),
							'id'        => 'description',
							'type'      => 'textarea',
						),
					)
				),
			),
		);

		$metaboxes[ self::$prefix . 'experience' ] = array(
			'id'                        => self::$prefix . 'experience',
			'title'                     => __( 'Experience', 'wp-job-board' ),
			'object_types'              => array( 'candidate' ),
			'context'                   => 'normal',
			'priority'                  => 'high',
			'show_names'                => true,
			'show_in_rest'				=> true,
			'fields'                    => array(
				array(
					'name'              => __( 'Experience', 'wp-job-board' ),
					'id'                => self::$prefix . 'experience',
					'type'              => 'group',
					'options'     		=> array(
						'group_title'       => __( 'Experience {#}', 'wp-job-board' ),
						'add_button'        => __( 'Add Another Experience', 'wp-job-board' ),
						'remove_button'     => __( 'Remove Experience', 'wp-job-board' ),
						'sortable'          => false,
						'closed'         => true,
					),
					'fields'			=> array(
						array(
							'name'      => __( 'Title', 'wp-job-board' ),
							'id'        => 'title',
							'type'      => 'text',
						),
						array(
							'name'      => __( 'Start Date', 'wp-job-board' ),
							'id'        => 'start_date',
							'type'      => 'text_date',
							'attributes'		=> array(
								'data-datepicker' => json_encode(array(
									'yearRange' => '-100:+5',
								))
							),
						),
						array(
							'name'      => __( 'End Date', 'wp-job-board' ),
							'id'        => 'end_date',
							'type'      => 'text_date',
							'attributes'		=> array(
								'data-datepicker' => json_encode(array(
									'yearRange' => '-100:+5',
								))
							),
						),
						array(
							'name'      => __( 'Company', 'wp-job-board' ),
							'id'        => 'company',
							'type'      => 'text',
						),
						array(
							'name'      => __( 'Description', 'wp-job-board' ),
							'id'        => 'description',
							'type'      => 'textarea',
						),
					)
				),
			),
		);

		$metaboxes[ self::$prefix . 'award' ] = array(
			'id'                        => self::$prefix . 'award',
			'title'                     => __( 'Award', 'wp-job-board' ),
			'object_types'              => array( 'candidate' ),
			'context'                   => 'normal',
			'priority'                  => 'high',
			'show_names'                => true,
			'show_in_rest'				=> true,
			'fields'                    => array(
				array(
					'name'              => __( 'Award', 'wp-job-board' ),
					'id'                => self::$prefix . 'award',
					'type'              => 'group',
					'options'     		=> array(
						'group_title'       => __( 'Award {#}', 'wp-job-board' ),
						'add_button'        => __( 'Add Another Award', 'wp-job-board' ),
						'remove_button'     => __( 'Remove Award', 'wp-job-board' ),
						'sortable'          => false,
						'closed'         => true,
					),
					'fields'			=> array(
						array(
							'name'      => __( 'Title', 'wp-job-board' ),
							'id'        => 'title',
							'type'      => 'text',
						),
						array(
							'name'      => __( 'Year', 'wp-job-board' ),
							'id'        => 'year',
							'type'      => 'text',
						),
						array(
							'name'      => __( 'Description', 'wp-job-board' ),
							'id'        => 'description',
							'type'      => 'textarea',
						),
					)
				),
			),
		);

		$metaboxes[ self::$prefix . 'skill' ] = array(
			'id'                        => self::$prefix . 'skill',
			'title'                     => __( 'Skill', 'wp-job-board' ),
			'object_types'              => array( 'candidate' ),
			'context'                   => 'normal',
			'priority'                  => 'high',
			'show_names'                => true,
			'show_in_rest'				=> true,
			'fields'                    => array(
				array(
					'name'              => __( 'Skill', 'wp-job-board' ),
					'id'                => self::$prefix . 'skill',
					'type'              => 'group',
					'options'     		=> array(
						'group_title'       => __( 'Skill {#}', 'wp-job-board' ),
						'add_button'        => __( 'Add Another Skill', 'wp-job-board' ),
						'remove_button'     => __( 'Remove Skill', 'wp-job-board' ),
						'sortable'          => false,
						'closed'         => true,
					),
					'fields'			=> array(
						array(
							'name'      => __( 'Title', 'wp-job-board' ),
							'id'        => 'title',
							'type'      => 'text',
						),
						array(
							'name'      => __( 'Percentage', 'wp-job-board' ),
							'id'        => 'percentage',
							'type'      => 'text',
							'attributes' 	    => array(
								'type' 				=> 'number',
								'min'				=> 0,
								'pattern' 			=> '\d*',
							)
						),
					)
				),
			),
		);

		return $metaboxes;
	}

	public static function fields_front( array $metaboxes ) {
		if ( ! is_admin() ) {
			$user_id = WP_Job_Board_User::get_user_id();
			if ( WP_Job_Board_User::is_candidate($user_id) ) {
				$post_id = WP_Job_Board_User::get_candidate_by_user_id($user_id);
				if ( !empty($post_id) ) {
					$post = get_post( $post_id );
					$featured_image = get_post_thumbnail_id( $post_id );
					$tags_default = implode( ', ', wp_get_object_terms( $post_id, 'candidate_tag', array( 'fields' => 'names' ) ) );
				}
			}
			$cv_file_type_keys = wp_job_board_get_option('cv_file_types', array('doc', 'docx', 'pdf'));
			$currency_symbol = wp_job_board_get_option('currency_symbol', '$');
			
			$fields = apply_filters( 'wp-job-board-candidate-fields-front', array(
					array(
						'id'                => self::$prefix . 'post_type',
						'type'              => 'hidden',
						'default'           => 'candidate',
						'priority'           => 0,
					),
					array(
						'name'              => __( 'Show my profile', 'wp-job-board' ),
						'id'                => self::$prefix . 'show_profile',
						'type'              => 'select',
						'options'			=> array(
							'show'	=> __( 'Show', 'wp-job-board' ),
							'hide'	=> __( 'Hide', 'wp-job-board' ),
						),
						'priority'           => 0.2,
					),
					array(
						'name'              => __( 'Profile url', 'wp-job-board' ),
						'id'                => self::$prefix . 'profile_url',
						'type'              => 'wp_job_board_profile_url',
						'priority'           => 0.5,
					),
					array(
						'name'              => __( 'Featured Image', 'wp-job-board' ),
						'id'                => self::$prefix . 'featured_image',
						'type'              => 'wp_job_board_file',
						'multiple'			=> false,
						'default'           => ! empty( $featured_image ) ? $featured_image : '',
						'ajax'				=> true,
						'mime_types' => array( 'gif', 'jpeg', 'jpg', 'png' ),
						'priority'           => 1,
					),

					array(
						'name'              => __( 'Full Name', 'wp-job-board' ),
						'id'                => self::$prefix . 'title',
						'type'              => 'text',
						'default'           => ! empty( $post ) ? $post->post_title : '',
						'attributes'		=> array(
							'required'			=> 'required'
						),
						'priority'           => 2,
					),

					
					array(
						'name'              => __( 'Date of Birth', 'wp-job-board' ),
						'id'                => self::$prefix . 'founded_date',
						'type'              => 'text_date',
						'priority'           => 3,
						'attributes'		=> array(
							'data-datepicker' => json_encode(array(
								'yearRange' => '-100:+5',
							))
						),
					),
					array(
						'name'              => __( 'Phone Number', 'wp-job-board' ),
						'id'                => self::$prefix . 'phone',
						'type'              => 'text',
						'priority'           => 4,
					),
					array(
						'name'              => __( 'Job Title', 'wp-job-board' ),
						'id'                => self::$prefix . 'job_title',
						'type'              => 'text',
						'priority'           => 5,
					),
					array(
						'name'              => sprintf(__( 'Salary (%s)', 'wp-job-board' ), $currency_symbol),
						'id'                => self::$prefix . 'salary',
						'type'              => 'text',
						'priority'           => 6,
					),
					array(
						'name'              => __( 'Salary Type', 'wp-job-board' ),
						'id'                => self::$prefix . 'salary_type',
						'type'              => 'select',
						'options'			=> WP_Job_Board_Mixes::get_default_salary_types(),
						'priority'           => 7,
					),


					array(
						'name'      		=> __( 'Categories', 'wp-job-board' ),
						'id'        		=> self::$prefix . 'category',
						'type'      		=> 'pw_taxonomy_multiselect',
						'taxonomy'  		=> 'candidate_category',
						'priority'           => 30,
					),
					array(
						'name'              => __( 'Introduction Video URL (Youtube/Vimeo)', 'wp-job-board' ),
						'id'                => self::$prefix . 'video_url',
						'type'              => 'text',
						'priority'           => 30.1,
					),

					array(
						'name'              => __( 'Description', 'wp-job-board' ),
						'id'                => self::$prefix . 'description',
						'type'              => 'wysiwyg',
						'default'           => ! empty( $post ) ? $post->post_content : '',
						'priority'           => 31,
						'options' => array(
						    'media_buttons' => false,
						    'textarea_rows' => 8,
						    'tinymce'       => array(
								'plugins'                       => 'lists,paste,tabfocus,wplink,wordpress',
								'paste_as_text'                 => true,
								'paste_auto_cleanup_on_paste'   => true,
								'paste_remove_spans'            => true,
								'paste_remove_styles'           => true,
								'paste_remove_styles_if_webkit' => true,
								'paste_strip_class_attributes'  => true,
								'toolbar1'                      => 'bold,italic,|,bullist,numlist,|,link,unlink,|,undo,redo',
								'toolbar2'                      => '',
								'toolbar3'                      => '',
								'toolbar4'                      => ''
							),
						    'quicktags' => false
						),
					),

					array(
						'name'      		=> __( 'Location', 'wp-job-board' ),
						'id'        		=> self::$prefix . 'location',
						'type'      		=> 'wpjb_taxonomy_location',
						'taxonomy'  		=> 'candidate_location',
						'priority'           => 32,
						'attributes'		=> array(
							'placeholder' 	=> __( 'Select %s', 'wp-job-board' ),
						),
					),

					array(
						'name'              => __( 'Friendly Address', 'wp-job-board' ),
						'id'                => self::$prefix . 'address',
						'type'              => 'text',
						'priority'           => 33,
					),
					array(
						'id'                => self::$prefix . 'map_location',
						'name'              => __( 'Location', 'wp-job-board' ),
						'type'              => 'pw_map',
						'sanitization_cb'   => 'pw_map_sanitise',
						'split_values'      => true,
						'priority'           => 34,
					),

					// socials
					array(
						'name'              => __( 'Socials', 'wp-job-board' ),
						'id'                => self::$prefix . 'socials',
						'type'              => 'group',
						'options'     		=> array(
							'group_title'       => __( 'Network {#}', 'wp-job-board' ),
							'add_button'        => __( 'Add Another Network', 'wp-job-board' ),
							'remove_button'     => __( 'Remove Network', 'wp-job-board' ),
							'sortable'          => false,
							'closed'         => true,
						),
						'fields'			=> array(
							array(
								'name'      => __( 'Network', 'wp-job-board' ),
								'id'        => 'network',
								'type'      => 'select',
								'options'   => WP_Job_Board_Mixes::get_socials_network()
							),
							array(
								'name'      => __( 'Url', 'wp-job-board' ),
								'id'        => 'url',
								'type'      => 'text',
							),
						),
						'priority'           => 35,
					),
					array(
						'name'      		=> __( 'Tags', 'wp-job-board' ),
						'id'        		=> self::$prefix . 'tags',
						'type'      		=> 'wp_job_board_tags',
						'taxonomy'  		=> 'candidate_tag',
						'default'			=> !empty($tags_default) ? $tags_default : '',
						'priority'           => 36,
						'attributes'		=> array(
							'autocomplete'		=> 'off',
							'placeholder' 		=> __( 'e.g. PHP, Developer, CSS', 'wp-job-board' ),
						),
					),
				)
			);

			uasort( $fields, array( 'WP_Job_Board_Mixes', 'sort_array_by_priority') );

			$metaboxes[ self::$prefix . 'front' ] = array(
				'id'                        => self::$prefix . 'front',
				'title'                     => __( 'General Options', 'wp-job-board' ),
				'object_types'              => array( 'candidate' ),
				'context'                   => 'normal',
				'priority'                  => 'high',
				'show_names'                => true,
				'fields'                    => $fields
			);
		}
		return $metaboxes;
	}

	public static function fields_resume_front( array $metaboxes ) {
		if ( ! is_admin() ) {
			$cv_file_type_keys = wp_job_board_get_option('cv_file_types', array('doc', 'docx', 'pdf'));

			$fields = apply_filters( 'wp-job-board-candidate-fields-resume-front', array(
					array(
						'id'                => self::$prefix . 'post_type',
						'type'              => 'hidden',
						'default'           => 'candidate',
						'priority'           => 0,
					),
					array(
						'name'              => __( 'CV Attachment', 'wp-job-board' ),
						'id'                => self::$prefix . 'cv_attachment',
						'type'              => 'wp_job_board_file',
						'ajax'				=> true,
						'file_multiple'		=> true,
						'mime_types' 		=> $cv_file_type_keys,
						'description' 		=> sprintf(__('Upload file %s', 'wp-job-board'), implode(', ', $cv_file_type_keys)),
						'priority'           => 14,
					),
					array(
						'name'              => __( 'Portfolio Photos', 'wp-job-board' ),
						'id'                => self::$prefix . 'portfolio_photos',
						'type'              => 'wp_job_board_file',
						'ajax'				=> true,
						'file_multiple'		=> true,
						'mime_types' => array( 'gif', 'jpeg', 'jpg', 'png' ),
						'priority'           => 13,
					),
					// education
					array(
						'name'              => __( 'Education', 'wp-job-board' ),
						'id'                => self::$prefix . 'education',
						'type'              => 'group',
						'options'     		=> array(
							'group_title'       => __( 'Education {#}', 'wp-job-board' ),
							'add_button'        => __( 'Add Another Education', 'wp-job-board' ),
							'remove_button'     => __( 'Remove Education', 'wp-job-board' ),
							'sortable'          => false,
							'closed'         => true,
						),
						'fields'			=> array(
							array(
								'name'      => __( 'Title', 'wp-job-board' ),
								'id'        => 'title',
								'type'      => 'text',
							),
							array(
								'name'      => __( 'Academy', 'wp-job-board' ),
								'id'        => 'academy',
								'type'      => 'text',
							),
							array(
								'name'      => __( 'Year', 'wp-job-board' ),
								'id'        => 'year',
								'type'      => 'text',
							),
							array(
								'name'      => __( 'Description', 'wp-job-board' ),
								'id'        => 'description',
								'type'      => 'textarea',
							),
						),
						'priority'           => 24,
					),
					// Experience
					array(
						'name'              => __( 'Experience', 'wp-job-board' ),
						'id'                => self::$prefix . 'experience',
						'type'              => 'group',
						'options'     		=> array(
							'group_title'       => __( 'Experience {#}', 'wp-job-board' ),
							'add_button'        => __( 'Add Another Experience', 'wp-job-board' ),
							'remove_button'     => __( 'Remove Experience', 'wp-job-board' ),
							'sortable'          => false,
							'closed'         => true,
						),
						'fields'			=> array(
							array(
								'name'      => __( 'Title', 'wp-job-board' ),
								'id'        => 'title',
								'type'      => 'text',
							),
							array(
								'name'      => __( 'Start Date', 'wp-job-board' ),
								'id'        => 'start_date',
								'type'      => 'text_date',
								'attributes'		=> array(
									'data-datepicker' => json_encode(array(
										'yearRange' => '-100:+5',
									))
								),
							),
							array(
								'name'      => __( 'End Date', 'wp-job-board' ),
								'id'        => 'end_date',
								'type'      => 'text_date',
								'attributes'		=> array(
									'data-datepicker' => json_encode(array(
										'yearRange' => '-100:+5',
									))
								),
							),
							array(
								'name'      => __( 'Company', 'wp-job-board' ),
								'id'        => 'company',
								'type'      => 'text',
							),
							array(
								'name'      => __( 'Description', 'wp-job-board' ),
								'id'        => 'description',
								'type'      => 'textarea',
							),
						),
						'priority'           => 25,
					),
					// Award
					array(
						'name'              => __( 'Award', 'wp-job-board' ),
						'id'                => self::$prefix . 'award',
						'type'              => 'group',
						'options'     		=> array(
							'group_title'       => __( 'Award {#}', 'wp-job-board' ),
							'add_button'        => __( 'Add Another Award', 'wp-job-board' ),
							'remove_button'     => __( 'Remove Award', 'wp-job-board' ),
							'sortable'          => false,
							'closed'         => true,
						),
						'fields'			=> array(
							array(
								'name'      => __( 'Title', 'wp-job-board' ),
								'id'        => 'title',
								'type'      => 'text',
							),
							array(
								'name'      => __( 'Year', 'wp-job-board' ),
								'id'        => 'year',
								'type'      => 'text',
							),
							array(
								'name'      => __( 'Description', 'wp-job-board' ),
								'id'        => 'description',
								'type'      => 'textarea',
							),
						),
						'priority'           => 26,
					),
					// Skill
					array(
						'name'              => __( 'Skill', 'wp-job-board' ),
						'id'                => self::$prefix . 'skill',
						'type'              => 'group',
						'options'     		=> array(
							'group_title'       => __( 'Skill {#}', 'wp-job-board' ),
							'add_button'        => __( 'Add Another Skill', 'wp-job-board' ),
							'remove_button'     => __( 'Remove Skill', 'wp-job-board' ),
							'sortable'          => false,
							'closed'         => true,
						),
						'fields'			=> array(
							array(
								'name'      => __( 'Title', 'wp-job-board' ),
								'id'        => 'title',
								'type'      => 'text',
							),
							array(
								'name'      => __( 'Percentage', 'wp-job-board' ),
								'id'        => 'percentage',
								'type'      => 'text',
								'attributes' 	    => array(
									'type' 				=> 'number',
									'min'				=> 0,
									'pattern' 			=> '\d*',
								)
							),
						),
						'priority'           => 27,
					),
				)
			);

			uasort( $fields, array( 'WP_Job_Board_Mixes', 'sort_array_by_priority') );

			$metaboxes[ self::$prefix . 'resume_front' ] = array(
				'id'                        => self::$prefix . 'resume_front',
				'title'                     => __( 'General Options', 'wp-job-board' ),
				'object_types'              => array( 'candidate' ),
				'context'                   => 'normal',
				'priority'                  => 'high',
				'show_names'                => true,
				'fields'                    => $fields
			);
		}
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
		$fields = array_merge($columns, array(
			'thumbnail' 		=> __( 'Thumbnail', 'wp-job-board' ),
			'posted' 			=> __( 'Posted', 'wp-job-board' ),
			'expires' 			=> __( 'Expires', 'wp-job-board' ),
			'category' 			=> __( 'Category', 'wp-job-board' ),
			'location' 			=> __( 'Location', 'wp-job-board' ),
			'urgent' 			=> __( 'Urgent', 'wp-job-board' ),
			'featured' 			=> __( 'Featured', 'wp-job-board' ),
			'candidate_status'  => __( 'Status', 'wp-job-board' ),
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
			case 'thumbnail':
				if ( has_post_thumbnail() ) {
					the_post_thumbnail( 'thumbnail', array(
						'class' => 'attachment-thumbnail attachment-thumbnail-small logo-thumnail',
					) );
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
				$terms = get_the_terms( get_the_ID(), 'candidate_category' );
				if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
					$category = array_shift( $terms );
					echo sprintf( '<a href="?post_type=candidate&candidate_category=%s">%s</a>', $category->slug, $category->name );
				} else {
					echo '-';
				}
				break;
			case 'location':
				$terms = get_the_terms( get_the_ID(), 'candidate_location' );
				if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
					$location = array_shift( $terms );
					echo sprintf( '<a href="?post_type=candidate&candidate_location=%s">%s</a>', $location->slug, $location->name );
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
			case 'candidate_status':
				$status   = $post->post_status;
				$statuses = WP_Job_Board_Job_Listing::job_statuses();

				$status_text = $status;
				if ( !empty($statuses[$status]) ) {
					$status_text = $statuses[$status];
				}
				echo sprintf( '<a href="?post_type=candidate&post_status=%s">%s</a>', esc_attr( $post->post_status ), '<span class="status-' . esc_attr( $post->post_status ) . '">' . esc_html( $status_text ) . '</span>' );
				break;
		}
	}

	public static function filter_candidate_by_type() {
		global $typenow;
		if ($typenow == 'candidate') {
			// categories
			$selected = isset($_GET['candidate_category']) ? $_GET['candidate_category'] : '';
			$terms = get_terms( 'candidate_category', array('hide_empty' => false,) );
			if ( ! empty( $terms ) && ! is_wp_error( $terms ) ){
				?>
				<select name="candidate_category">
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
	
}
WP_Job_Board_Post_Type_Candidate::init();