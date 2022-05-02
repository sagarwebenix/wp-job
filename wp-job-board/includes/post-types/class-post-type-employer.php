<?php
/**
 * Post Type: Employer
 *
 * @package    wp-job-board
 * @author     Habq 
 * @license    GNU General Public License, version 3
 */

if ( ! defined( 'ABSPATH' ) ) {
  	exit;
}

class WP_Job_Board_Post_Type_Employer {

	public static $prefix = WP_JOB_BOARD_EMPLOYER_PREFIX;

	public static function init() {
	  	add_action( 'init', array( __CLASS__, 'register_post_type' ) );

	  	add_filter( 'cmb2_meta_boxes', array( __CLASS__, 'fields' ) );
	  	add_filter( 'cmb2_meta_boxes', array( __CLASS__, 'fields_front' ) );

	  	add_filter( 'manage_edit-employer_columns', array( __CLASS__, 'custom_columns' ) );
		add_action( 'manage_employer_posts_custom_column', array( __CLASS__, 'custom_columns_manage' ) );

		add_action('restrict_manage_posts', array( __CLASS__, 'filter_employer_by_type' ));

		add_action('save_post', array( __CLASS__, 'save_post' ), 10, 2 );

		add_action( 'denied_to_publish', array( __CLASS__, 'process_denied_to_publish' ) );
		add_action( 'pending_to_publish', array( __CLASS__, 'process_pending_to_publish' ) );

		add_action( 'cmb2_save_field_'.self::$prefix . 'employees', array( __CLASS__, 'save_field_employees' ), 10, 3 );
	}

	public static function register_post_type() {
		$labels = array(
			'name'                  => __( 'Employers', 'wp-job-board' ),
			'singular_name'         => __( 'Employer', 'wp-job-board' ),
			'add_new'               => __( 'Add New Employer', 'wp-job-board' ),
			'add_new_item'          => __( 'Add New Employer', 'wp-job-board' ),
			'edit_item'             => __( 'Edit Employer', 'wp-job-board' ),
			'new_item'              => __( 'New Employer', 'wp-job-board' ),
			'all_items'             => __( 'All Employers', 'wp-job-board' ),
			'view_item'             => __( 'View Employer', 'wp-job-board' ),
			'search_items'          => __( 'Search Employer', 'wp-job-board' ),
			'not_found'             => __( 'No Employers found', 'wp-job-board' ),
			'not_found_in_trash'    => __( 'No Employers found in Trash', 'wp-job-board' ),
			'parent_item_colon'     => '',
			'menu_name'             => __( 'Employers', 'wp-job-board' ),
		);
		$has_archive = true;
		$employer_archive = get_option('wp_job_board_employer_archive_slug');
		if ( $employer_archive ) {
			$has_archive = $employer_archive;
		}
		$rewrite_slug = get_option('wp_job_board_employer_base_slug');
		if ( empty($rewrite_slug) ) {
			$rewrite_slug = _x( 'employer', 'Employer slug - resave permalinks after changing this', 'wp-job-board' );
		}
		$rewrite = array(
			'slug'       => $rewrite_slug,
			'with_front' => false
		);
		register_post_type( 'employer',
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

	public static function save_post($post_id, $post) {
		if ( $post->post_type === 'employer' ) {
			$arg = array( 'ID' => $post_id );
			if ( !empty($_POST[self::$prefix . 'featured']) ) {
				$arg['menu_order'] = -1;
			} else {
				$arg['menu_order'] = 0;
			}
			
			remove_action('save_post', array( __CLASS__, 'save_post' ), 10, 2 );
			wp_update_post( $arg );
			add_action('save_post', array( __CLASS__, 'save_post' ), 10, 2 );

			delete_transient( 'wp_job_board_filter_counts' );
			
			clean_post_cache( $post_id );
		}
	}

	public static function process_denied_to_publish($post) {
		if ( $post->post_type === 'employer' ) {
			$user_id = WP_Job_Board_User::get_user_by_employer_id($post->ID);
			remove_action('denied_to_publish', array( __CLASS__, 'process_denied_to_publish' ) );
			do_action( 'wp_job_board_new_user_approve_approve_user', $user_id );
			add_action( 'denied_to_publish', array( __CLASS__, 'process_denied_to_publish' ) );
		}
	}

	public static function process_pending_to_publish($post) {
		if ( $post->post_type === 'employer' ) {
			$user_id = WP_Job_Board_User::get_user_by_employer_id($post->ID);
			remove_action('pending_to_publish', array( __CLASS__, 'process_pending_to_publish' ) );
			do_action( 'wp_job_board_new_user_approve_approve_user', $user_id );
			add_action( 'pending_to_publish', array( __CLASS__, 'process_pending_to_publish' ) );
		}
	}

	public static function save_field_employees($updated, $action, $obj) {
		if ( $action != 'disabled' ) {
			$key = self::$prefix.'employees';
			$data_to_save = $obj->data_to_save;
			$post_id = !empty($data_to_save['post_ID']) ? $data_to_save['post_ID'] : '';
			$employees = isset($data_to_save[$key]) ? $data_to_save[$key] : '';

			// remove employee employer
			$employee_users = WP_Job_Board_Query::get_employee_users($post_id, array('fields' => 'ids'));
			if ( !empty($employee_users) ) {
				foreach ($employee_users as $employee_user_id) {
					delete_user_meta($employee_user_id, 'employee_employer_id');
				}
			}
			if ( !empty( $employees ) ) {
				if ( is_array($employees) ) {
					foreach ($employees as $employee_id) {
						update_user_meta($employee_id, 'employee_employer_id', $post_id);
					}
				}
			}

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
		
		$metaboxes[ self::$prefix . 'general' ] = array(
			'id'                        => self::$prefix . 'general',
			'title'                     => __( 'General Options', 'wp-job-board' ),
			'object_types'              => array( 'employer' ),
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
					'name'              => __( 'Founded Date', 'wp-job-board' ),
					'id'                => self::$prefix . 'founded_date',
					'type'              => 'text_small',
					'attributes' 	    => array(
						'type' 				=> 'number',
						'min'				=> 0,
						'pattern' 			=> '\d*',
					)
				),
				array(
					'name'              => __( 'CIF Number', 'wp-job-board' ),
					'id'                => self::$prefix . 'cif_number',
					'type'              => 'text',
				),
				array(
					'name'              => __( 'Website', 'wp-job-board' ),
					'id'                => self::$prefix . 'website',
					'type'              => 'text',
				),
				array(
					'name'              => __( 'Phone Number', 'wp-job-board' ),
					'id'                => self::$prefix . 'phone',
					'type'              => 'text',
				),
				array(
					'name'              => __( 'Featured Employer', 'wp-job-board' ),
					'id'                => self::$prefix . 'featured',
					'type'              => 'checkbox',
					'description'		=> __( 'Featured employer will be sticky during searches, and can be styled differently.', 'wp-job-board' )
				),
				array(
					'name'              => __( 'Cover Photo', 'wp-job-board' ),
					'id'                => self::$prefix . 'cover_photo',
					'type'              => 'file',
					'query_args' => array( 'type' => 'image' ),
				),
				array(
					'name'              => __( 'Profile Photos', 'wp-job-board' ),
					'id'                => self::$prefix . 'profile_photos',
					'type'              => 'file_list',
					'query_args' => array( 'type' => 'image' ), // Only images attachment
					'text' => array(
						'add_upload_files_text' => __( 'Add or Upload Images', 'wp-job-board' ),
					),
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
			'object_types'              => array( 'employer' ),
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
			'object_types'              => array( 'employer' ),
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
					'name'              => __( 'Map Location', 'wp-job-board' ),
					'type'              => 'pw_map',
					'sanitization_cb'   => 'pw_map_sanitise',
					'split_values'      => true,
				),
			),
		);

		$metaboxes[ self::$prefix . 'team_members' ] = array(
			'id'                        => self::$prefix . 'team_members',
			'title'                     => __( 'Team Members', 'wp-job-board' ),
			'object_types'              => array( 'employer' ),
			'context'                   => 'normal',
			'priority'                  => 'high',
			'show_names'                => true,
			'show_in_rest'				=> true,
			'fields'                    => array(
				array(
					'name'              => __( 'Members', 'wp-job-board' ),
					'id'                => self::$prefix . 'team_members',
					'type'              => 'group',
					'options'     		=> array(
						'group_title'       => __( 'Member {#}', 'wp-job-board' ),
						'add_button'        => __( 'Add Another Member', 'wp-job-board' ),
						'remove_button'     => __( 'Remove Member', 'wp-job-board' ),
						'sortable'          => true,
					),
					'fields'			=> array(
						array(
							'name'      => __( 'Name', 'wp-job-board' ),
							'id'        => 'name',
							'type'      => 'text',
						),
						array(
							'name'      => __( 'Designation', 'wp-job-board' ),
							'id'        => 'designation',
							'type'      => 'text',
						),
						array(
							'name'      => __( 'Experience', 'wp-job-board' ),
							'id'        => 'experience',
							'type'      => 'text',
						),
						array(
							'name'      => __( 'Profile Image', 'wp-job-board' ),
							'id'        => 'profile_image',
							'type'      => 'file',
							'options' => array(
								'url' => false, // Hide the text input for the url
							),
							'text'    => array(
								'add_upload_file_text' => __( 'Add Image', 'wp-job-board' ),
							),
							'query_args' => array(
								'type' => array(
									'image/gif',
									'image/jpeg',
									'image/png',
								),
							),
						),
						array(
							'name'              => __( 'Facebook URL', 'wp-job-board' ),
							'id'                => 'facebook',
							'type'              => 'text',
						),
						array(
							'name'              => __( 'Twitter URL', 'wp-job-board' ),
							'id'                => 'twitter',
							'type'              => 'text',
						),
						array(
							'name'              => __( 'Google Plus URL', 'wp-job-board' ),
							'id'                => 'google_plus',
							'type'              => 'text',
						),
						array(
							'name'              => __( 'Linkedin URL', 'wp-job-board' ),
							'id'                => 'linkedin',
							'type'              => 'text',
						),
						array(
							'name'              => __( 'Dribbble URL', 'wp-job-board' ),
							'id'                => 'dribbble',
							'type'              => 'text',
						),
						array(
							'name'              => __( 'description', 'wp-job-board' ),
							'id'                => 'description',
							'type'              => 'textarea',
						),
					)
				),
			),
		);

		$metaboxes[ self::$prefix . 'employees' ] = array(
			'id'              	=> self::$prefix . 'employees',
			'title'           	=> __( 'Employees', 'wp-job-board' ),
			'object_types'      => array( 'employer' ),
			'context'           => 'normal',
			'priority'          => 'high',
			'show_names'        => true,
			'show_in_rest'		=> true,
			'fields'          	=> array(
				array(
					'name'          => __( 'Employees', 'wp-job-board' ),
					'id'            => self::$prefix . 'employees',
					'type'          => 'user_ajax_search',
					'multiple'      => true,
					'query_args'	=> array(
						'role'				=> array( 'wp_job_board_employee' ),
						'search_columns' 	=> array( 'user_login', 'user_email' ),
						'meta_query'		=> array(
							'relation' => 'OR',
							array(
								'key'       => 'employee_employer_id',
								'value'     => '',
							),
							array(
								'key'       => 'employee_employer_id',
								'compare' => 'NOT EXISTS',
							)
						)
					)
				)
			),
		);

		return $metaboxes;
	}

	public static function fields_front( array $metaboxes ) {
		if ( ! is_admin() ) {
			$user_id = WP_Job_Board_User::get_user_id();
			if ( WP_Job_Board_User::is_employer($user_id) ) {
				$post_id = WP_Job_Board_User::get_employer_by_user_id($user_id);
				if ( !empty($post_id) ) {
					$post = get_post( $post_id );
					$featured_image = get_post_thumbnail_id( $post_id );
				}
			}
			
			$fields = apply_filters( 'wp-job-board-employer-fields-front', array(
					array(
						'id'                => self::$prefix . 'post_type',
						'type'              => 'hidden',
						'default'           => 'employer',
						'priority'           => 0,
					),
					array(
						'name'              => __( 'Profile url', 'wp-job-board' ),
						'id'                => self::$prefix . 'profile_url',
						'type'              => 'wp_job_board_profile_url',
						'priority'           => 0.5,
					),
					array(
						'name'              => __( 'Logo Image', 'wp-job-board' ),
						'id'                => self::$prefix . 'featured_image',
						'type'              => 'wp_job_board_file',
						'file_multiple'			=> false,
						'default'           => ! empty( $featured_image ) ? $featured_image : '',
						'ajax'				=> true,
						'mime_types' 		=> array( 'gif', 'jpeg', 'jpg', 'png' ),
						'priority'           => 1,
					),
					array(
						'name'              => __( 'Cover Photo', 'wp-job-board' ),
						'id'                => self::$prefix . 'cover_photo',
						'type'              => 'wp_job_board_file',
						'file_multiple'			=> false,
						'ajax'				=> true,
						'mime_types' 		=> array( 'gif', 'jpeg', 'jpg', 'png' ),
						'priority'           => 2,
					),
					array(
						'name'              => __( 'Employer name', 'wp-job-board' ),
						'id'                => self::$prefix . 'title',
						'type'              => 'text',
						'default'           => ! empty( $post ) ? $post->post_title : '',
						'attributes'		=> array(
							'required'			=> 'required'
						),
						'priority'           => 3,
					),

					
					array(
						'name'              => __( 'Founded Date', 'wp-job-board' ),
						'id'                => self::$prefix . 'founded_date',
						'type'              => 'text_small',
						'attributes' 	    => array(
							'type' 				=> 'number',
							'min'				=> 0,
							'pattern' 			=> '\d*',
						),
						'priority'           => 6,
					),
					array(
						'name'              => __( 'CIF Number', 'wp-job-board' ),
						'id'                => self::$prefix . 'cif_number',
						'type'              => 'text',
						'priority'           => 6,
					),
					array(
						'name'              => __( 'Website', 'wp-job-board' ),
						'id'                => self::$prefix . 'website',
						'type'              => 'text',
						'priority'           => 7,
					),
					array(
						'name'              => __( 'Phone Number', 'wp-job-board' ),
						'id'                => self::$prefix . 'phone',
						'type'              => 'text',
						'priority'           => 8,
					),
					array(
						'name'      		=> __( 'Categories', 'wp-job-board' ),
						'id'        		=> self::$prefix . 'category',
						'type'      		=> 'pw_taxonomy_multiselect',
						'taxonomy'  		=> 'employer_category',
						'priority'           => 20,
					),
					array(
						'name'              => __( 'Description', 'wp-job-board' ),
						'id'                => self::$prefix . 'description',
						'type'              => 'wysiwyg',
						'default'           => ! empty( $post ) ? $post->post_content : '',
						'priority'           => 21,
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
						'taxonomy'  		=> 'employer_location',
						'priority'           => 22,
						'attributes'		=> array(
							'placeholder' 	=> __( 'Select %s', 'wp-job-board' ),
						),
					),
					array(
						'name'              => __( 'Friendly Address', 'wp-job-board' ),
						'id'                => self::$prefix . 'address',
						'type'              => 'text',
						'priority'           => 23,
					),
					array(
						'id'                => self::$prefix . 'map_location',
						'name'              => __( 'Map Location', 'wp-job-board' ),
						'type'              => 'pw_map',
						'sanitization_cb'   => 'pw_map_sanitise',
						'split_values'      => true,
						'priority'           => 24,
					),
					array(
						'name'              => __( 'Profile Photos', 'wp-job-board' ),
						'id'                => self::$prefix . 'profile_photos',
						'type'              => 'wp_job_board_file',
						//'default'           => ! empty( $profile_photos ) ? $profile_photos : '',
						'file_multiple'			=> true,
						'ajax'				=> true,
						'mime_types' 		=> array( 'gif', 'jpeg', 'jpg', 'png' ),
						'priority'           => 25,
					),
					array(
						'name'              => __( 'Introduction Video URL (Youtube/Vimeo)', 'wp-job-board' ),
						'id'                => self::$prefix . 'video_url',
						'type'              => 'text',
						'priority'           => 25.1,
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
						'priority'           => 26,
					),

					array(
						'name'              => __( 'Members', 'wp-job-board' ),
						'id'                => self::$prefix . 'team_members',
						'type'              => 'group',
						'options'     		=> array(
							'group_title'       => __( 'Member {#}', 'wp-job-board' ),
							'add_button'        => __( 'Add Another Member', 'wp-job-board' ),
							'remove_button'     => __( 'Remove Member', 'wp-job-board' ),
							'sortable'          => false,
							'closed'         => true,
						),
						'fields'			=> array(
							array(
								'name'      => __( 'Name', 'wp-job-board' ),
								'id'        => 'name',
								'type'      => 'text',
							),
							array(
								'name'      => __( 'Designation', 'wp-job-board' ),
								'id'        => 'designation',
								'type'      => 'text',
							),
							array(
								'name'      => __( 'Experience', 'wp-job-board' ),
								'id'        => 'experience',
								'type'      => 'text',
							),
							array(
								'name'      => __( 'Profile Image', 'wp-job-board' ),
								'id'        => 'profile_image',
								'type'              => 'wp_job_board_file',
								'file_multiple'			=> false,
								'ajax'				=> true,
								'mime_types' 		=> array( 'gif', 'jpeg', 'jpg', 'png' ),
							),
							array(
								'name'              => __( 'Facebook URL', 'wp-job-board' ),
								'id'                => 'facebook',
								'type'              => 'text',
							),
							array(
								'name'              => __( 'Twitter URL', 'wp-job-board' ),
								'id'                => 'twitter',
								'type'              => 'text',
							),
							array(
								'name'              => __( 'Google Plus URL', 'wp-job-board' ),
								'id'                => 'google_plus',
								'type'              => 'text',
							),
							array(
								'name'              => __( 'Linkedin URL', 'wp-job-board' ),
								'id'                => 'linkedin',
								'type'              => 'text',
							),
							array(
								'name'              => __( 'Dribbble URL', 'wp-job-board' ),
								'id'                => 'dribbble',
								'type'              => 'text',
							),
							array(
								'name'              => __( 'Description', 'wp-job-board' ),
								'id'                => 'description',
								'type'              => 'textarea',
							),
						),
						'priority'           => 27,
					),
				)
			);
			
			uasort( $fields, array( 'WP_Job_Board_Mixes', 'sort_array_by_priority') );

			$metaboxes[ self::$prefix . 'front' ] = array(
				'id'                        => self::$prefix . 'front',
				'title'                     => __( 'General Options', 'wp-job-board' ),
				'object_types'              => array( 'employer' ),
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
			'title' 			=> __( 'Title', 'wp-job-board' ),
			'thumbnail' 		=> __( 'Thumbnail', 'wp-job-board' ),
			'category' 			=> __( 'Category', 'wp-job-board' ),
			'location' 			=> __( 'Location', 'wp-job-board' ),
			'featured' 			=> __( 'Featured', 'wp-job-board' ),
			'author' 			=> __( 'Author', 'wp-job-board' ),
			'date' 				=> __( 'Date', 'wp-job-board' ),
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
				if ( has_post_thumbnail() ) {
					the_post_thumbnail( 'thumbnail', array(
						'class' => 'attachment-thumbnail attachment-thumbnail-small ',
					) );
				} else {
					echo '-';
				}
				break;
			case 'category':
				$terms = get_the_terms( get_the_ID(), 'employer_category' );
				if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
					$category = array_shift( $terms );
					echo sprintf( '<a href="?post_type=employer&employer_category=%s">%s</a>', $category->slug, $category->name );
				} else {
					echo '-';
				}
				break;
			case 'location':
				$terms = get_the_terms( get_the_ID(), 'employer_location' );
				if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
					$location = array_shift( $terms );
					echo sprintf( '<a href="?post_type=employer&employer_location=%s">%s</a>', $location->slug, $location->name );
				} else {
					echo '-';
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
		}
	}

	public static function filter_employer_by_type() {
		global $typenow;
		if ($typenow == 'employer') {
			// categories
			$selected = isset($_GET['employer_category']) ? $_GET['employer_category'] : '';
			$terms = get_terms( 'employer_category', array('hide_empty' => false,) );
			if ( ! empty( $terms ) && ! is_wp_error( $terms ) ){
				?>
				<select name="employer_category">
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
WP_Job_Board_Post_Type_Employer::init();