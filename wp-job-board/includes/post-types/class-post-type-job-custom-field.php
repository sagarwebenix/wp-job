<?php
/**
 * Post Type: Job Custom Field
 *
 * @package    wp-job-board
 * @author     Habq 
 * @license    GNU General Public License, version 3
 */

if ( ! defined( 'ABSPATH' ) ) {
  	exit;
}

class WP_Job_Board_Post_Type_Job_Custom_Fields {
	public static $field_types;
	public static $post_types;

	public static function init() {
		self::$field_types =  array(
			'text' => esc_html__( 'Text', 'wp-job-board' ),
			'textarea' => esc_html__( 'Textarea', 'wp-job-board' ),
			'select' => esc_html__( 'Select', 'wp-job-board' ),
			'pw_multiselect' => esc_html__( 'Multi Select', 'wp-job-board' ),
			'radio' => esc_html__( 'Radio Button', 'wp-job-board' ),
			'checkbox' => esc_html__( 'CheckBox', 'wp-job-board' ),
			'multicheck' => esc_html__( 'Multi CheckBox', 'wp-job-board' ),
			'file' => esc_html__( 'Image', 'wp-job-board' ),
			'file_list' => esc_html__( 'Images List', 'wp-job-board' ),
		);

		self::$post_types =  array(
			'job_cfield' => array(
				'prefix' => WP_JOB_BOARD_JOB_CUSTOM_FIELD_PREFIX,
				'for_post_type' => 'job_listing',
				'for_post_type_prefix' => WP_JOB_BOARD_JOB_LISTING_PREFIX,
			),
			'employer_cfield' => array(
				'prefix' => WP_JOB_BOARD_EMPLOYER_CUSTOM_FIELD_PREFIX,
				'for_post_type' => 'employer',
				'for_post_type_prefix' => WP_JOB_BOARD_EMPLOYER_PREFIX,
			),
			'candidate_cfield' => array(
				'prefix' => WP_JOB_BOARD_CANDIDATE_CUSTOM_FIELD_PREFIX,
				'for_post_type' => 'candidate',
				'for_post_type_prefix' => WP_JOB_BOARD_CANDIDATE_PREFIX,
			)
		);
	  	add_action( 'init', array( __CLASS__, 'register_post_type_init' ) );

	  	add_filter( 'cmb2_meta_boxes', array( __CLASS__, 'fields_init' ) );
	  	
	  	add_filter( 'cmb2_meta_boxes', array( __CLASS__, 'add_job_fields_init' ) );

	  	foreach (self::$post_types as $post_type => $args) {
	  		// columns
	  		add_filter( 'manage_edit-'.$post_type.'_columns', array( __CLASS__, 'custom_columns' ) );
			add_action( 'manage_'.$post_type.'_posts_custom_column', array( __CLASS__, 'custom_columns_manage' ) );
	  		// fields
	  		add_filter( 'wp-job-board-'.$args['for_post_type'].'-fields-front', array( __CLASS__, 'add_fields_front_'.$args['for_post_type'] ) );
	  		// filter
	  		add_filter( 'wp-job-board-default-'.$args['for_post_type'].'-filter-fields', array( __CLASS__, 'add_filter_fields_'.$args['for_post_type'] ) );
	  		add_filter( 'wp-job-board-'.$args['for_post_type'].'-filter-query', array( __CLASS__, 'filter_query_'.$args['for_post_type'] ), 10, 2 );
	  		add_filter( 'wp-job-board-'.$args['for_post_type'].'-query-args', array( __CLASS__, 'filter_query_args_'.$args['for_post_type'] ), 10, 2 );
	  	}

	}

	public static function register_post_type_init() {
		foreach (self::$post_types as $post_type => $args) {
			self::register_post_type( $post_type, $args['for_post_type'] );
		}
	}
	
	public static function fields_init( array $metaboxes ) {
		foreach (self::$post_types as $post_type => $args) {
			$metaboxes = self::fields( $metaboxes, $post_type, $args['prefix'] );
		}
		return $metaboxes;
	}

	public static function add_job_fields_init( array $metaboxes ) {
		foreach (self::$post_types as $post_type => $args) {
			$metaboxes = self::add_job_fields( $metaboxes, $post_type, $args['prefix'], $args['for_post_type_prefix'], $args['for_post_type'] );
		}
		return $metaboxes;
	}

	public static function register_post_type($post_type, $for_post_type) {
		$labels = array(
			'name'                  => __( 'Custom Fields', 'wp-job-board' ),
			'singular_name'         => __( 'Custom Field', 'wp-job-board' ),
			'add_new'               => __( 'Add New Custom Field', 'wp-job-board' ),
			'add_new_item'          => __( 'Add New Custom Field', 'wp-job-board' ),
			'edit_item'             => __( 'Edit Custom Field', 'wp-job-board' ),
			'new_item'              => __( 'New Custom Field', 'wp-job-board' ),
			'all_items'             => __( 'Custom Fields', 'wp-job-board' ),
			'view_item'             => __( 'View Custom Field', 'wp-job-board' ),
			'search_items'          => __( 'Search Custom Field', 'wp-job-board' ),
			'not_found'             => __( 'No Custom Fields found', 'wp-job-board' ),
			'not_found_in_trash'    => __( 'No Custom Fields found in Trash', 'wp-job-board' ),
			'parent_item_colon'     => '',
			'menu_name'             => __( 'Custom Fields', 'wp-job-board' ),
		);

		register_post_type( $post_type,
			array(
				'labels'            => $labels,
				'supports'          => array( 'title' ),
				'public'            => true,
		        'has_archive'       => false,
		        'publicly_queryable' => false,
				'show_in_rest'		=> false,
				'show_in_menu'		=> 'edit.php?post_type='.$for_post_type,
			)
		);
	}

	/**
	 * Defines custom fields
	 *
	 * @access public
	 * @param array $metaboxes
	 * @return array
	 */
	public static function fields( array $metaboxes, $post_type, $prefix ) {
		$metaboxes[ $prefix . 'general' ] = array(
			'id'                        => $prefix . 'general',
			'title'                     => __( 'General Options', 'wp-job-board' ),
			'object_types'              => array( $post_type ),
			'context'                   => 'normal',
			'priority'                  => 'high',
			'show_names'                => true,
			'show_in_rest'				=> true,
			'fields'                    => array(
				array(
					'name' => esc_html__( 'Description', 'wp-job-board' ),
					'id'   => $prefix."description",
					'type' => 'text',
				),
				array(
					'name' => esc_html__( 'Placeholder', 'wp-job-board' ),
					'id'   => $prefix."placeholder",
					'type' => 'text',
				),
				array(
					'name' => esc_html__( 'Field Type', 'wp-job-board' ),
					'id'   => $prefix."field_type",
					'type' => 'select',
					'options' => self::$field_types
				),
				array(
					'name' => esc_html__( 'Required', 'wp-job-board' ),
					'id'   => $prefix.'required',
					'type' => 'checkbox',
				),
			),
		);

		$metaboxes[ $prefix . 'options' ] = array(
			'id'                        => $prefix . 'options',
			'title'                     => esc_html__( 'Options', 'wp-job-board' ),
			'object_types'              => array( $post_type ),
			'context'                   => 'normal',
			'priority'                  => 'high',
			'show_names'                => true,
			'fields'                    => array(
				array(
					'id'                => $prefix . 'options',
					'type'              => 'group',
					'options'     => array(
						'group_title'   => esc_html__( 'Option {#}', 'wp-job-board' ),
						'add_button'    => esc_html__( 'Add Another Option', 'wp-job-board' ),
						'remove_button' => esc_html__( 'Remove Option', 'wp-job-board' ),
						'sortable'      => true,
					),
					'fields'            => array(
						array(
							'id'                => 'value',
							'name'              => esc_html__( 'Value', 'wp-job-board' ),
							'type'              => 'text',
						),
						array(
							'id'                => 'text',
							'name'              => esc_html__( 'Text', 'wp-job-board' ),
							'type'              => 'text',
						),
					),
				),
			)
		);

		$metaboxes[ $prefix . 'more_info' ] = array(
			'id'                        => $prefix . 'more_info',
			'title'                     => esc_html__( 'More Information', 'wp-job-board' ),
			'object_types'              => array( $post_type ),
			'context'                   => 'normal',
			'priority'                  => 'high',
			'show_names'                => true,
			'fields'                    => array(
				array(
					'id'                => $prefix.'priority',
					'name'              => esc_html__( 'Priority', 'wp-job-board' ),
					'type'              => 'text',
					'attributes' 	    => array(
						'type' 				=> 'number',
						'pattern' 			=> '\d*',
					)
				),
				array(
					'id'                => $prefix.'icon_class',
					'name'              => esc_html__( 'Icon Font Class', 'wp-job-board' ),
					'type'              => 'text',
				),
				array(
					'name' => esc_html__( 'Show in filter form', 'wp-job-board' ),
					'id'   => $prefix.'show_filter',
					'type' => 'checkbox',
				)
			)
		);
		return $metaboxes;
	}

	public static function get_custom_fields($post_type = 'job_cfield') {
		switch ($post_type) {
			case 'employer_cfield':
				$prefix = WP_JOB_BOARD_EMPLOYER_CUSTOM_FIELD_PREFIX;
				break;
			case 'candidate_cfield':
				$prefix = WP_JOB_BOARD_CANDIDATE_CUSTOM_FIELD_PREFIX;
				break;
			case 'job_cfield':
			default:
				$prefix = WP_JOB_BOARD_JOB_CUSTOM_FIELD_PREFIX;
				break;
		}
    	
		$args = array(
            'posts_per_page'   => -1,
            'orderby'          => 'date',
            'order'            => 'ASC',
            'meta_key' 			=> $prefix.'priority',
            'orderby'   		=> 'meta_value_num',
            'post_type'        => $post_type,
            'post_status'      => 'publish',
        );
		return get_posts($args);
	}

	public static function add_job_fields( array $metaboxes, $post_type, $prefix, $for_post_type_prefix, $for_post_type ) {
		
		$fields = self::generate_fields($post_type, $prefix, $for_post_type_prefix);
		if ( !empty($fields) ) {
            $metaboxes[ $for_post_type_prefix . 'custom_fields' ] = array(
                'id'                        => $for_post_type_prefix . 'custom_fields',
                'title'                     => esc_html__( 'Other Information', 'wp-job-board' ),
                'object_types'              => array( $for_post_type ),
                'context'                   => 'normal',
                'priority'                  => 'high',
                'show_names'                => true,
                'fields'                    => $fields
            );
        }
		return $metaboxes;
	}

	public static function add_fields_front_job_listing($fields) {
		$post_type = 'job_cfield';
		$prefix = WP_JOB_BOARD_JOB_CUSTOM_FIELD_PREFIX;
		$for_post_type_prefix = WP_JOB_BOARD_JOB_LISTING_PREFIX;

		$cfields = self::generate_fields($post_type, $prefix, $for_post_type_prefix);
		if ( !empty($cfields) ) {
			$fields = array_merge($fields, $cfields);
		}
		return $fields;
	}

	public static function add_fields_front_employer($fields) {
		$post_type = 'employer_cfield';
		$prefix = WP_JOB_BOARD_EMPLOYER_CUSTOM_FIELD_PREFIX;
		$for_post_type_prefix = WP_JOB_BOARD_EMPLOYER_PREFIX;

		$cfields = self::generate_fields($post_type, $prefix, $for_post_type_prefix);
		if ( !empty($cfields) ) {
			$fields = array_merge($fields, $cfields);
		}
		return $fields;
	}

	public static function add_fields_front_candidate($fields) {
		$post_type = 'candidate_cfield';
		$prefix = WP_JOB_BOARD_CANDIDATE_CUSTOM_FIELD_PREFIX;
		$for_post_type_prefix = WP_JOB_BOARD_CANDIDATE_PREFIX;

		$cfields = self::generate_fields($post_type, $prefix, $for_post_type_prefix);
		if ( !empty($cfields) ) {
			$fields = array_merge($fields, $cfields);
		}
		return $fields;
	}

	public static function generate_fields($post_type, $prefix, $for_post_type_prefix) {
		$custom_fields = self::get_custom_fields($post_type);
		$cfields = array();
		foreach ($custom_fields as $post) {

			$field_type = get_post_meta( $post->ID, $prefix . 'field_type', true );
            $description = get_post_meta( $post->ID, $prefix . 'description', true );
            $placeholder = get_post_meta( $post->ID, $prefix . 'placeholder', true );
            $required = get_post_meta( $post->ID, $prefix . 'required', true );
            $priority = get_post_meta( $post->ID, $prefix . 'priority', true );

            $name = $post->post_name;

            $cfields[$for_post_type_prefix.$name] = array(
                'id'                => self::generate_key_id($for_post_type_prefix, $name),
                'name'              => $post->post_title,
                'type'              => $field_type,
                'description'       => !empty($description) ? $description : '',
                
                'priority'       => !empty($priority) ? intval($priority) : 100,
            );
            $attributes = array();
            if ( !empty($placeholder) ) {
            	$attributes['placeholder'] = $placeholder;
            }
            if ( $required && $field_type != 'multicheck' && $field_type != 'radio'  ) {
            	$attributes['required'] = 'required';
            	
	        }
	        if ( !empty($attributes) ) {
	        	$cfields[$for_post_type_prefix.$name]['attributes'] = $attributes;
	        }
            if ( in_array($field_type, array( 'select', 'pw_multiselect', 'radio', 'multicheck')) ) {
            	$options = get_post_meta( $post->ID, $prefix . 'options', true );
                $cfields[$for_post_type_prefix.$name]['options'] = self::generate_options($options);
            }
		}
		return $cfields;
	}

	public static function generate_key_id($prefix, $name) {
        return $prefix .'custom_'. $name;
    }

	public static function generate_options($options) {
        $return = array();
        if ( !empty($options) ) {
            foreach ($options as $option) {
                $return[esc_attr($option['value'])] = $option['text'];
            }
        }
        return $return;
    }

    public static function add_filter_fields_job_listing($fields) {
    	$post_type = 'job_cfield';
    	$prefix = WP_JOB_BOARD_JOB_CUSTOM_FIELD_PREFIX;
		
		$cfields = self::generate_filter_fields($post_type, $prefix, 'job_listing');
		if ( $cfields ) {
			$fields = array_merge($fields, $cfields);
		}
		return $fields;
    }

    public static function add_filter_fields_employer($fields) {
    	$post_type = 'employer_cfield';
    	$prefix = WP_JOB_BOARD_EMPLOYER_CUSTOM_FIELD_PREFIX;
		
		$cfields = self::generate_filter_fields($post_type, $prefix, 'employer');
		if ( $cfields ) {
			$fields = array_merge($fields, $cfields);
		}
		return $fields;
    }

    public static function add_filter_fields_candidate($fields) {
    	$post_type = 'candidate_cfield';
    	$prefix = WP_JOB_BOARD_CANDIDATE_CUSTOM_FIELD_PREFIX;
		
		$cfields = self::generate_filter_fields($post_type, $prefix, 'candidate');
		if ( $cfields ) {
			$fields = array_merge($fields, $cfields);
		}
		return $fields;
    }

    public static function generate_filter_fields($post_type, $prefix, $for_post_type = 'job_listing') {
		$custom_fields = self::get_custom_fields($post_type);
		$fields = array();
		foreach ($custom_fields as $post) {
			$field_type = get_post_meta( $post->ID, $prefix . 'field_type', true );
            $show_filter = get_post_meta( $post->ID, $prefix . 'show_filter', true );

            if ( $show_filter && in_array($field_type, array('text', 'select', 'radio', 'checkbox', 'multicheck', 'pw_multiselect')) ) {
            	$toggle = true;
            	if ( $field_type == 'checkbox' ) {
            		$toggle = false;
            	}
            	$fields[$post->post_name] = array(
            		'label' => $post->post_title,
            		'post_type' => $post_type,
            		'for_post_type' => $for_post_type,
            		'prefix' => $prefix,
            		'hide_field_content' => $toggle,
            		'toggle' => $toggle,
            		'field_call_back' => array( __CLASS__, 'filter_field_'.$field_type ),
            	);
	        }
		}
		return $fields;
    }

    public static function filter_field_text($instance, $args, $key, $field) {
		$name = 'filter-custom-'.$key;
		$selected = !empty( $_GET[$name] ) ? $_GET[$name] : '';

		include WP_Job_Board_Template_Loader::locate( 'widgets/filter-fields/text' );
    }

    public static function filter_field_checkbox($instance, $args, $key, $field) {
		$name = 'filter-custom-'.$key;
		$selected = !empty( $_GET[$name] ) ? $_GET[$name] : '';

		include WP_Job_Board_Template_Loader::locate( 'widgets/filter-fields/checkbox' );
    }

    public static function filter_field_select($instance, $args, $key, $field) {
		$options = self::get_options_by_name($key, $field);
        $name = 'filter-custom-'.$key;
        $selected = ! empty( $_GET[$name] ) ? $_GET[$name] : '';
		if ( $options ) {
			foreach ($options as $key => $option) {
				$options[$key]['count'] = WP_Job_Board_Abstract_Filter::filter_count($name, $option['value'], $field);
			}
		}
		include WP_Job_Board_Template_Loader::locate( 'widgets/filter-fields/radios' );
    }

    public static function filter_field_radio($instance, $args, $key, $field) {
		$options = self::get_options_by_name($key, $field);
        $name = 'filter-custom-'.$key;
        $selected = ! empty( $_GET[$name] ) ? $_GET[$name] : '';
		if ( $options ) {
			foreach ($options as $key => $option) {
				$options[$key]['count'] = WP_Job_Board_Abstract_Filter::filter_count($name, $option['value'], $field);
			}
		}
		include WP_Job_Board_Template_Loader::locate( 'widgets/filter-fields/radios' );
    }

    public static function filter_field_multicheck($instance, $args, $key, $field) {
		$options = self::get_options_by_name($key, $field);
        $name = 'filter-custom-'.$key;
        $selected = ! empty( $_GET[$name] ) ? $_GET[$name] : '';
		if ( $options ) {
			foreach ($options as $key => $option) {
				$options[$key]['count'] = WP_Job_Board_Abstract_Filter::filter_count($name, $option['value'], $field);
			}
		}

		include WP_Job_Board_Template_Loader::locate( 'widgets/filter-fields/check_list' );
    }

    public static function filter_field_pw_multiselect($instance, $args, $key, $field) {
		$options = self::get_options_by_name($key, $field);
        $name = 'filter-custom-'.$key;
        $selected = ! empty( $_GET[$name] ) ? $_GET[$name] : '';
		if ( $options ) {
			foreach ($options as $key => $option) {
				$options[$key]['count'] = WP_Job_Board_Abstract_Filter::filter_count($name, $option['value'], $field);
			}
		}

		include WP_Job_Board_Template_Loader::locate( 'widgets/filter-fields/check_list' );
    }

    public static function get_options_by_name($key, $field) {
    	$options = array();
    	$query_args = array(
            'name'        => $key,
            'post_type'   => $field['post_type'],
            'post_status' => 'publish',
            'numberposts' => 1
        );
        $posts = get_posts($query_args);
        if ( $posts ) {
            $post = $posts[0];
            $options = get_post_meta( $post->ID, $field['prefix'].'options', true );
        }
        return $options;
    }

    public static function filter_query_job_listing($query, $params) {
    	$post_type = 'job_cfield';
    	$prefix = WP_JOB_BOARD_JOB_CUSTOM_FIELD_PREFIX;
    	$for_post_type_prefix = WP_JOB_BOARD_JOB_LISTING_PREFIX;
    	
    	$query = self::filter_query($query, $params, $post_type, $prefix, $for_post_type_prefix);
    	return $query;
    }

    public static function filter_query_args_job_listing($query_vars, $params) {
    	$post_type = 'job_cfield';
    	$prefix = WP_JOB_BOARD_JOB_CUSTOM_FIELD_PREFIX;
    	$for_post_type_prefix = WP_JOB_BOARD_JOB_LISTING_PREFIX;
    	
    	$query_vars = self::filter_query_args($query_vars, $params, $post_type, $prefix, $for_post_type_prefix);

    	return $query_vars;
    }

    public static function filter_query_employer($query, $params) {
    	$post_type = 'employer_cfield';
    	$prefix = WP_JOB_BOARD_EMPLOYER_CUSTOM_FIELD_PREFIX;
    	$for_post_type_prefix = WP_JOB_BOARD_EMPLOYER_PREFIX;
    	$query = self::filter_query($query, $params, $post_type, $prefix, $for_post_type_prefix);
    	return $query;
    }

    public static function filter_query_args_employer($query_vars, $params) {
    	$post_type = 'employer_cfield';
    	$prefix = WP_JOB_BOARD_EMPLOYER_CUSTOM_FIELD_PREFIX;
    	$for_post_type_prefix = WP_JOB_BOARD_EMPLOYER_PREFIX;
    	$query_vars = self::filter_query_args($query_vars, $params, $post_type, $prefix, $for_post_type_prefix);
    	return $query_vars;
    }

    public static function filter_query_candidate($query, $params) {
    	$post_type = 'candidate_cfield';
    	$prefix = WP_JOB_BOARD_CANDIDATE_CUSTOM_FIELD_PREFIX;
    	$for_post_type_prefix = WP_JOB_BOARD_CANDIDATE_PREFIX;
    	$query = self::filter_query($query, $params, $post_type, $prefix, $for_post_type_prefix);
    	return $query;
    }

    public static function filter_query_args_candidate($query_vars, $params) {
    	$post_type = 'candidate_cfield';
    	$prefix = WP_JOB_BOARD_CANDIDATE_CUSTOM_FIELD_PREFIX;
    	$for_post_type_prefix = WP_JOB_BOARD_CANDIDATE_PREFIX;
    	$query_vars = self::filter_query_args($query_vars, $params, $post_type, $prefix, $for_post_type_prefix);
    	
    	return $query_vars;
    }

    public static function filter_query($query, $params, $post_type, $prefix, $for_post_type_prefix) {
    	$query_vars = $query->query_vars;

		$meta_query = self::filter_meta($query_vars, $params, $post_type, $prefix, $for_post_type_prefix);

		$query->set('meta_query', $meta_query);
		return $query;
    }

    public static function filter_query_args($query_args, $params, $post_type, $prefix, $for_post_type_prefix) {
		$meta_query = self::filter_meta($query_args, $params, $post_type, $prefix, $for_post_type_prefix);

		$query_args['meta_query'] = $meta_query;
		return $query_args;
    }

    public static function filter_meta($query_args, $params, $post_type, $prefix, $for_post_type_prefix) {
    	if ( isset($query_args['meta_query']) ) {
			$meta_query = $query_args['meta_query'];
		} else {
			$meta_query = array();;
		}
		if ( empty($params) || !is_array($params) ) {
			return $meta_query;
		}
    	foreach ( $params as $key => $value ) {
			if ( !empty($value) && strrpos( $key, 'filter-custom-', -strlen( $key ) ) !== false ) {
				$custom_key = str_replace( 'filter-custom-', '', $key );

				$query_args = array(
		            'name'        => $custom_key,
		            'post_type'   => $post_type,
		            'post_status' => 'publish',
		            'numberposts' => 1
		        );
		        $posts = get_posts($query_args);
		        if ( $posts ) {
		            $post = $posts[0];

		            $field_type = get_post_meta( $post->ID, $prefix.'field_type', true );

		            $meta_key = self::generate_key_id($for_post_type_prefix, $custom_key);
		            switch ($field_type) {
		            	
		            	case 'text':
		            		$meta_query[] = array(
								'key'       => $meta_key,
								'value'     => $value,
								'compare'   => 'LIKE',
							);
		            		break;
	            		case 'select':
		            		$meta_query[] = array(
								'key'       => $meta_key,
								'value'     => $value,
								'compare'   => '=',
							);
		            		break;
	            		case 'checkbox':
	            			$meta_query[] = array(
								'key'       => $meta_key,
								'value'     => 'on',
								'compare'   => '=',
							);
							break;
	            		case 'radio':
		            		$meta_query[] = array(
								'key'       => $meta_key,
								'value'     => $value,
								'compare'   => '=',
							);
		            		break;
	            		case 'pw_multiselect':
	            		case 'multicheck':
	            			if ( is_array($value) ) {
	            				$multi_meta = array( 'relation' => 'OR' );
	            				foreach ($value as $val) {
	            					$multi_meta[] = array(
	            						'key'       => $meta_key,
										'value'     => '"'.$val.'"',
										'compare'   => 'LIKE',
	            					);
	            				}
	            				$meta_query[] = $multi_meta;
	            			} else {
	            				$meta_query[] = array(
									'key'       => $meta_key,
									'value'     => '"'.$value.'"',
									'compare'   => 'LIKE',
								);
	            			}
	            			break;
		            }
		        }
			}
		}
		if ( !empty($params['filter-counter']) ) {
			foreach ( $params['filter-counter'] as $key => $value ) {
				if ( !empty($value) && strrpos( $key, 'filter-custom-', -strlen( $key ) ) !== false ) {
					$custom_key = str_replace( 'filter-custom-', '', $key );

					$query_args = array(
			            'name'        => $custom_key,
			            'post_type'   => $post_type,
			            'post_status' => 'publish',
			            'numberposts' => 1
			        );
			        $posts = get_posts($query_args);
			        if ( $posts ) {
			            $post = $posts[0];

			            $field_type = get_post_meta( $post->ID, $prefix.'field_type', true );

			            $meta_key = self::generate_key_id($for_post_type_prefix, $custom_key);
			            switch ($field_type) {
			            	
			            	case 'text':
			            		$meta_query[] = array(
									'key'       => $meta_key,
									'value'     => $value,
									'compare'   => 'LIKE',
								);
			            		break;
		            		case 'select':
			            		$meta_query[] = array(
									'key'       => $meta_key,
									'value'     => $value,
									'compare'   => '=',
								);
			            		break;
		            		case 'checkbox':
		            			$meta_query[] = array(
									'key'       => $meta_key,
									'value'     => 'on',
									'compare'   => '=',
								);
								break;
		            		case 'radio':
			            		$meta_query[] = array(
									'key'       => $meta_key,
									'value'     => $value,
									'compare'   => '=',
								);
			            		break;
		            		case 'pw_multiselect':
		            		case 'multicheck':
		            			if ( is_array($value) ) {
		            				$multi_meta = array( 'relation' => 'OR' );
		            				foreach ($value as $val) {
		            					$multi_meta[] = array(
		            						'key'       => $meta_key,
											'value'     => '"'.$val.'"',
											'compare'   => 'LIKE',
		            					);
		            				}
		            				$meta_query[] = $multi_meta;
		            			} else {
		            				$meta_query[] = array(
										'key'       => $meta_key,
										'value'     => '"'.$value.'"',
										'compare'   => 'LIKE',
									);
		            			}
		            			break;
			            }
			        }
				}
			}
		}
		
		return $meta_query;
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
			'cb' 				=> '<input type="checkbox" />',
			'title' 			=> __( 'Title', 'wp-job-board' ),
			'field_type' 		=> __( 'Field Type', 'wp-job-board' ),
			'meta_key' 		=> __( 'Meta Key', 'wp-job-board' ),
			'author' 			=> __( 'Author', 'wp-job-board' ),
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
		$prefix = '';
		if ( $post->post_type == 'job_cfield' ) {
			$prefix = WP_JOB_BOARD_JOB_CUSTOM_FIELD_PREFIX;
			$for_post_type_prefix = WP_JOB_BOARD_JOB_LISTING_PREFIX;
		} elseif( $post->post_type == 'employer_cfield' ) {
			$prefix = WP_JOB_BOARD_EMPLOYER_CUSTOM_FIELD_PREFIX;
			$for_post_type_prefix = WP_JOB_BOARD_EMPLOYER_PREFIX;
		} elseif( $post->post_type == 'candidate_cfield' ) {
			$prefix = WP_JOB_BOARD_CANDIDATE_CUSTOM_FIELD_PREFIX;
			$for_post_type_prefix = WP_JOB_BOARD_CANDIDATE_PREFIX;
		}
		switch ( $column ) {
			case 'field_type':
				$field_type = get_post_meta( get_the_ID(), $prefix.'field_type', true );
				if ( ! empty( $field_type ) && !empty(self::$field_types[$field_type])) {
					echo self::$field_types[$field_type];
				} else {
					echo '-';
				}
				break;
			case 'meta_key':
				echo self::generate_key_id( $for_post_type_prefix, $post->post_name );
				break;
		}
	}
	
	public static function display_field($post, $value) {
		$prefix = '';
		if ( $post->post_type == 'job_cfield' ) {
			$prefix = WP_JOB_BOARD_JOB_CUSTOM_FIELD_PREFIX;
		} elseif( $post->post_type == 'employer_cfield' ) {
			$prefix = WP_JOB_BOARD_EMPLOYER_CUSTOM_FIELD_PREFIX;
		} elseif( $post->post_type == 'candidate_cfield' ) {
			$prefix = WP_JOB_BOARD_CANDIDATE_CUSTOM_FIELD_PREFIX;
		}

        $field_type = get_post_meta( $post->ID, $prefix . 'field_type', true );
        switch ( $field_type ) {
            case 'text':
            case 'textarea':
            case 'checkbox':
                return $value;
                break;
            case 'select':
            case 'radio':
                $return = '';
                $options = get_post_meta( $post->ID, $prefix . 'options', true );
                if ( !empty($options) ) {
                    foreach ($options as $option) {
                        if ( $value == $option['value'] ) {
                            return $option['text'];
                        }
                    }
                }
                return $return;
                break;
            case 'multicheck':
            case 'pw_multiselect':
                $return = WP_Job_Board_Template_Loader::get_template_part('custom-fields/multicheck', array(
                    'post' => $post,
                    'value' => $value,
                    'prefix' => $prefix
                ));
                
                return $return;
                break;
            case 'file':
                $return = WP_Job_Board_Template_Loader::get_template_part('custom-fields/file', array(
                    'post' => $post,
                    'value' => $value,
                    'prefix' => $prefix
                ));
                return $return;
            case 'file_list':
                $return = WP_Job_Board_Template_Loader::get_template_part('custom-fields/file_list', array(
                    'post' => $post,
                    'value' => $value,
                    'prefix' => $prefix
                ));
                return $return;
                break;
            default:
                return $value;
                break;
        }
    }

}
WP_Job_Board_Post_Type_Job_Custom_Fields::init();