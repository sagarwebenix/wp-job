<?php
/**
 * Abstract Form
 *
 * @package    wp-job-board
 * @author     Habq 
 * @license    GNU General Public License, version 3
 */

if ( ! defined( 'ABSPATH' ) ) {
  	exit;
}

class WP_Job_Board_Abstract_Form {
	protected $steps = array();
	public $form_name = '';
	protected $step = 0;
	protected $job_id = 0;
	public $errors = array();
	public $success_msg = array();

	public function __construct() {
		add_filter( 'cmb2_meta_boxes', array( $this, 'fields_front' ) );
	}

	public function process() {
		
		$step_key = $this->get_step_key( $this->step );

		if ( $step_key && is_callable( $this->steps[ $step_key ]['handler'] ) ) {
			call_user_func( $this->steps[ $step_key ]['handler'] );
		}

		$next_step_key = $this->get_step_key( $this->step );

		if ( $next_step_key && $step_key !== $next_step_key && isset( $this->steps[ $next_step_key ]['before_view'] ) && is_callable( $this->steps[ $next_step_key ]['before_view'] ) ) {
			call_user_func( $this->steps[ $next_step_key ]['before_view'] );
		}
		// if the step changed, but the next step has no 'view', call the next handler in sequence.
		if ( $next_step_key && $step_key !== $next_step_key && ! is_callable( $this->steps[ $next_step_key ]['view'] ) ) {
			$this->process();
		}
	}

	public function output( $atts = array() ) {
		$step_key = $this->get_step_key( $this->step );
		$output = '';
		if ( $step_key && is_callable( $this->steps[ $step_key ]['view'] ) ) {
			ob_start();
				call_user_func( $this->steps[ $step_key ]['view'], $atts );
				$output = ob_get_contents();
			ob_end_clean();
		}
		return $output;
	}

	public function get_job_id() {
		return $this->job_id;
	}

	public function set_step( $step ) {
		$this->step = absint( $step );
	}
	
	public function get_step() {
		return $this->step;
	}

	public function get_step_key( $step = '' ) {
		if ( ! $step ) {
			$step = $this->step;
		}
		$keys = array_keys( $this->steps );
		return isset( $keys[ $step ] ) ? $keys[ $step ] : '';
	}

	public function next_step() {
		$this->step ++;
	}

	public function previous_step() {
		$this->step --;
	}

	public function get_form_action() {
		return '//' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	}

	public function get_form_name() {
		return $this->form_name;
	}

	public function add_error( $error ) {
		$this->errors[] = $error;
	}
	
	public function fields_front($metaboxes) {
		$post_id = $this->job_id;

		if ( ! empty( $post_id ) ) {
			$post = get_post( $post_id );
			$featured_image = get_post_thumbnail_id( $post_id );
			$tags_default = implode( ', ', wp_get_object_terms( $post_id, 'job_listing_tag', array( 'fields' => 'names' ) ) );
		}
		$user_id = WP_Job_Board_User::get_user_id();
		$user_obj = get_user_by('ID', $user_id);
		$currency_symbol = wp_job_board_get_option('currency_symbol', '$');

		$fields = apply_filters( 'wp-job-board-job_listing-fields-front', array(
			array(
				'id'                => WP_JOB_BOARD_JOB_LISTING_PREFIX . 'post_type',
				'type'              => 'hidden',
				'default'           => 'job_listing',
				'priority'           => 0,
			),
			array(
				'name'              => __( 'Title', 'wp-job-board' ),
				'id'                => WP_JOB_BOARD_JOB_LISTING_PREFIX . 'title',
				'type'              => 'text',
				'default'           => ! empty( $post ) ? $post->post_title : '',
				'attributes'		=> array(
					'required'			=> 'required',
					'placeholder' 		=> __( 'Job Title', 'wp-job-board' ),
				),
				'priority'           => 1,
			),
			array(
				'name'      		=> __( 'Types', 'wp-job-board' ),
				'id'        		=> WP_JOB_BOARD_JOB_LISTING_PREFIX . 'type',
				'type'      		=> 'pw_taxonomy_select',
				'taxonomy'  		=> 'job_listing_type',
				'priority'           => 2,
			),
			
			array(
				'name'              => __( 'Description', 'wp-job-board' ),
				'id'                => WP_JOB_BOARD_JOB_LISTING_PREFIX . 'description',
				'type'              => 'wysiwyg',
				'default'           => ! empty( $post ) ? $post->post_content : '',
				'priority'           => 3,
				'attributes'		=> array(
					'required'			=> 'required'
				),
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
				'name'              => __( 'Featured Image', 'wp-job-board' ),
				'id'                => WP_JOB_BOARD_JOB_LISTING_PREFIX . 'featured_image',
				'type'              => 'wp_job_board_file',
				'ajax'				=> true,
				'file_multiple'		=> false,
				'default'           => ! empty( $featured_image ) ? $featured_image : '',
				'priority'           => 4,
			),
			array(
				'name'      		=> __( 'Categories', 'wp-job-board' ),
				'id'        		=> WP_JOB_BOARD_JOB_LISTING_PREFIX . 'category',
				'type'      		=> 'pw_taxonomy_multiselect',
				'taxonomy'  		=> 'job_listing_category',
				'priority'           => 5,
				'attributes'		=> array(
					'placeholder' 	=> __( 'Select Categories', 'wp-job-board' ),
				),
			),
			
			
			
			array(
				'name'              => __( 'Application Deadline Date', 'wp-job-board' ),
				'id'                => WP_JOB_BOARD_JOB_LISTING_PREFIX . 'application_deadline_date',
				'type'              => 'text_date',
				'date_format' 		=> 'Y-m-d',
				'priority'           => 9,
				'attributes'		=> array(
					'autocomplete'	=> 'off',
					'placeholder' 	=> date('Y-m-d', strtotime("+10 days")),
					'data-datepicker' => json_encode(array(
						'yearRange' => '-10:+5',
					))
				),
			),
			array(
				'name'              => __( 'Job Apply Type', 'wp-job-board' ),
				'id'                => WP_JOB_BOARD_JOB_LISTING_PREFIX . 'apply_type',
				'type'              => 'select',
				'options'			=> array(
					'internal' => __( 'Internal', 'wp-job-board' ),
					'external' => __( 'External URL', 'wp-job-board' ),
					'with_email' => __( 'By Email', 'wp-job-board' ),
				),
				'priority'           => 10,
			),
			array(
				'name'              => __( 'External URL for Apply Job', 'wp-job-board' ),
				'id'                => WP_JOB_BOARD_JOB_LISTING_PREFIX . 'apply_url',
				'type'              => 'text',
				'priority'           => 11,
				'attributes'		=> array(
					'autocomplete'		=> 'off',
					'placeholder' 		=> __( 'e.g. apustheme.com', 'wp-job-board' ),
				),
			),
			array(
				'name'              => __( 'Job Apply Email', 'wp-job-board' ),
				'id'                => WP_JOB_BOARD_JOB_LISTING_PREFIX . 'apply_email',
				'default'			=> !empty($user_obj->user_email) ? $user_obj->user_email : '',
				'type'              => 'text',
				'priority'           => 12,
				'attributes'		=> array(
					'autocomplete'		=> 'off',
				),
			),
			array(
				'name'              => sprintf(__( 'Min. Salary (%s)', 'wp-job-board' ), $currency_symbol),
				'id'                => WP_JOB_BOARD_JOB_LISTING_PREFIX . 'salary',
				'type'              => 'text',
				'priority'           => 13,
				'attributes'		=> array(
					'autocomplete'		=> 'off',
					'placeholder' 		=> __( 'e.g. 1000', 'wp-job-board' ),
				),
			),
			array(
				'name'              => sprintf(__( 'Max. Salary (%s)', 'wp-job-board' ), $currency_symbol),
				'id'                => WP_JOB_BOARD_JOB_LISTING_PREFIX . 'max_salary',
				'type'              => 'text',
				'priority'           => 14,
				'attributes'		=> array(
					'autocomplete'		=> 'off',
					'placeholder' 		=> __( 'e.g. 2000', 'wp-job-board' ),
				),
			),
			array(
				'name'              => __( 'Salary Type', 'wp-job-board' ),
				'id'                => WP_JOB_BOARD_JOB_LISTING_PREFIX . 'salary_type',
				'type'              => 'select',
				'options'			=> WP_Job_Board_Mixes::get_default_salary_types(),
				'priority'           => 15,
			),
			array(
				'name'      		=> __( 'Tags', 'wp-job-board' ),
				'id'        		=> WP_JOB_BOARD_JOB_LISTING_PREFIX . 'tags',
				'type'      		=> 'wp_job_board_tags',
				'taxonomy'  		=> 'job_listing_tag',
				'default'			=> !empty($tags_default) ? $tags_default : '',
				'priority'           => 17,
				'attributes'		=> array(
					'autocomplete'		=> 'off',
					'placeholder' 		=> __( 'e.g. PHP, Developer, CSS', 'wp-job-board' ),
				),
			),
			array(
				'name'      		=> __( 'Location', 'wp-job-board' ),
				'id'        		=> WP_JOB_BOARD_JOB_LISTING_PREFIX . 'location',
				'type'      		=> 'wpjb_taxonomy_location',
				'taxonomy'  		=> 'job_listing_location',
				'priority'           => 40,
				'attributes'		=> array(
					'placeholder' 	=> __( 'Select %s', 'wp-job-board' ),
				),
			),
			array(
				'name'              => __( 'Friendly Address', 'wp-job-board' ),
				'id'                => WP_JOB_BOARD_JOB_LISTING_PREFIX . 'address',
				'type'              => 'text',
				'priority'           => 41,
			),
			array(
				'id'                => WP_JOB_BOARD_JOB_LISTING_PREFIX . 'map_location',
				'name'              => __( 'Map Location', 'wp-job-board' ),
				'type'              => 'pw_map',
				'sanitization_cb'   => 'pw_map_sanitise',
				'split_values'      => true,
				'priority'           => 42,
			),
		));
		
		uasort( $fields, array( 'WP_Job_Board_Mixes', 'sort_array_by_priority') );

		$metaboxes[ WP_JOB_BOARD_JOB_LISTING_PREFIX . 'front' ] = array(
			'id'                        => WP_JOB_BOARD_JOB_LISTING_PREFIX . 'front',
			'title'                     => __( 'General Options', 'wp-job-board' ),
			'object_types'              => array( 'job_listing' ),
			'context'                   => 'normal',
			'priority'                  => 'high',
			'show_names'                => true,
			'fields'                    => $fields
		);

		return $metaboxes;
	}

	public function form_output() {
		$metaboxes = apply_filters( 'cmb2_meta_boxes', array() );
		if ( ! isset( $metaboxes[ WP_JOB_BOARD_JOB_LISTING_PREFIX . 'front' ] ) ) {
			return __( 'A metabox with the specified \'metabox_id\' doesn\'t exist.', 'wp-job-board' );
		}
		$metaboxes_form = $metaboxes[ WP_JOB_BOARD_JOB_LISTING_PREFIX . 'front' ];

		// if ( ! $this->job_id ) {
		// 	unset( $_POST );
		// }
		
		if ( ! empty( $this->job_id ) && ! empty( $_POST['object_id'] ) ) {
			$this->job_id = intval( $_POST['object_id'] );
		}

		$submit_button_text = __( 'Save & Preview', 'wp-job-board' );
		if ( ! empty( $this->job_id ) ) {
			$submit_button_text = __( 'Update', 'wp-job-board' );
			// Check post author permission
			$post = get_post( $this->job_id );
		}
		wp_enqueue_script('google-maps');
		wp_enqueue_script('select2');
		wp_enqueue_style('select2');

		echo WP_Job_Board_Template_Loader::get_template_part( 'submission/job-submit-form', array(
			'post_id' => $this->job_id,
			'metaboxes_form' => $metaboxes_form,
			'job_id'         => $this->job_id,
			'step'           => $this->get_step(),
			'form_obj'       => $this,
			'submit_button_text' => apply_filters( 'wp_job_board_submit_job_form_submit_button_text', $submit_button_text ),
		) );
	}
}
