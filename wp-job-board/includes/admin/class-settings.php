<?php
/**
 * Settings
 *
 * @package    wp-job-board
 * @author     Habq 
 * @license    GNU General Public License, version 3
 */

if ( ! defined( 'ABSPATH' ) ) {
  	exit;
}

class WP_Job_Board_Settings {

	/**
	 * Option key, and option page slug
	 * @var string
	 */
	private $key = 'wp_job_board_settings';

	/**
	 * Array of metaboxes/fields
	 * @var array
	 */
	protected $option_metabox = array();

	/**
	 * Options Page title
	 * @var string
	 */
	protected $title = '';

	/**
	 * Options Page hook
	 * @var string
	 */
	protected $options_page = '';

	/**
	 * Constructor
	 * @since 1.0
	 */
	public function __construct() {
	
		add_action( 'admin_menu', array( $this, 'admin_menu' ) , 10 );

		add_action( 'admin_init', array( $this, 'init' ) );

		//Custom CMB2 Settings Fields
		add_action( 'cmb2_render_wp_job_board_title', 'wp_job_board_title_callback', 10, 5 );

		add_action( "cmb2_save_options-page_fields", array( $this, 'settings_notices' ), 10, 3 );


		add_action( 'cmb2_render_api_keys', 'wp_job_board_api_keys_callback', 10, 5 );

		// Include CMB CSS in the head to avoid FOUC
		add_action( "admin_print_styles-wp_job_board_properties_page_job_listing-settings", array( 'CMB2_hookup', 'enqueue_cmb_css' ) );
	}

	public function admin_menu() {
		//Settings
	 	$wp_job_board_settings_page = add_submenu_page( 'edit.php?post_type=job_listing', __( 'Settings', 'wp-job-board' ), __( 'Settings', 'wp-job-board' ), 'manage_options', 'job_listing-settings',
	 		array( $this, 'admin_page_display' ) );
	}

	/**
	 * Register our setting to WP
	 * @since  1.0
	 */
	public function init() {
		register_setting( $this->key, $this->key );
	}

	/**
	 * Retrieve settings tabs
	 *
	 * @since 1.0
	 * @return array $tabs
	 */
	public function wp_job_board_get_settings_tabs() {
		$tabs             	  = array();
		$tabs['general']  	  = __( 'General', 'wp-job-board' );
		$tabs['job_submission']   = __( 'Job Submission', 'wp-job-board' );
		$tabs['pages']   = __( 'Pages', 'wp-job-board' );
		$tabs['employer_settings']   = __( 'Employer Settings', 'wp-job-board' );
		$tabs['employee_settings']   = __( 'Employee Settings', 'wp-job-board' );
		$tabs['candidate_settings']   = __( 'Candidate Settings', 'wp-job-board' );
	 	$tabs['api_settings'] = __( 'Social API', 'wp-job-board' );
	 	$tabs['email_notification'] = __( 'Email Notification', 'wp-job-board' );
	 	$tabs['indeed_job_import'] = __( 'Indeed Job Import', 'wp-job-board' );

		return apply_filters( 'wp_job_board_settings_tabs', $tabs );
	}

	/**
	 * Admin page markup. Mostly handled by CMB2
	 * @since  1.0
	 */
	public function admin_page_display() {

		$active_tab = isset( $_GET['tab'] ) && array_key_exists( $_GET['tab'], $this->wp_job_board_get_settings_tabs() ) ? $_GET['tab'] : 'general';

		?>

		<div class="wrap wp_job_board_settings_page cmb2_options_page <?php echo $this->key; ?>">
			<h2 class="nav-tab-wrapper">
				<?php
				foreach ( $this->wp_job_board_get_settings_tabs() as $tab_id => $tab_name ) {

					$tab_url = esc_url( add_query_arg( array(
						'settings-updated' => false,
						'tab'              => $tab_id
					) ) );

					$active = $active_tab == $tab_id ? ' nav-tab-active' : '';

					echo '<a href="' . esc_url( $tab_url ) . '" title="' . esc_attr( $tab_name ) . '" class="nav-tab' . $active . '">';
					echo esc_html( $tab_name );

					echo '</a>';
				}
				?>
			</h2>
			
			<?php cmb2_metabox_form( $this->wp_job_board_settings( $active_tab ), $this->key ); ?>

		</div><!-- .wrap -->

		<?php
	}

	/**
	 * Define General Settings Metabox and field configurations.
	 *
	 * Filters are provided for each settings section to allow add-ons and other plugins to add their own settings
	 *
	 * @param $active_tab active tab settings; null returns full array
	 *
	 * @return array
	 */
	public function wp_job_board_settings( $active_tab ) {

		$pages = wp_job_board_cmb2_get_post_options( array(
			'post_type'   => 'page',
			'numberposts' => - 1
		) );
		$cv_mime_types = array();
		$mime_types = WP_Job_Board_Mixes::get_cv_mime_types();
		foreach($mime_types as $key => $mine_type) {
			$cv_mime_types[$key] = $key;
		}

		$images_file_types = array();
		$mime_types = WP_Job_Board_Mixes::get_image_mime_types();
		foreach($mime_types as $key => $mine_type) {
			$images_file_types[$key] = $key;
		}

		$wp_job_board_settings = array();

		$countries = array( '' => __('All Countries', 'wp-job-board') );
		$countries = array_merge( $countries, WP_Job_Board_Indeed_API::indeed_api_countries() );
		// General
		$wp_job_board_settings['general'] = array(
			'id'         => 'options_page',
			'wp_job_board_title' => __( 'General Settings', 'wp-job-board' ),
			'show_on'    => array( 'key' => 'options-page', 'value' => array( $this->key, ), ),
			'fields'     => apply_filters( 'wp_job_board_settings_general', array(
					array(
						'name' => __( 'General Settings', 'wp-job-board' ),
						'type' => 'wp_job_board_title',
						'id'   => 'wp_job_board_title_general_settings_1',
						'before_row' => '<hr>',
						'after_row'  => '<hr>'
					),
					array(
						'name'    => __( 'Number jobs per page', 'wp-job-board' ),
						'desc'    => __( 'Number of jobs to display per page.', 'wp-job-board' ),
						'id'      => 'number_jobs_per_page',
						'type'    => 'text',
						'default' => '10',
					),
					
					
					array(
						'name' => __( 'Currency Settings', 'wp-job-board' ),
						'desc' => '',
						'type' => 'wp_job_board_title',
						'id'   => 'wp_job_board_title_general_settings_2',
						'before_row' => '<hr>',
						'after_row'  => '<hr>'
					),

					array(
						'name'    => __( 'Currency Symbol', 'wp-job-board' ),
						'desc'    => __( 'Enter your Currency Symbol. Default $', 'wp-job-board' ),
						'id'      => 'currency_symbol',
						'type'    => 'text',
						'default' => '$',
					),
					array(
						'name'    => __( 'Currency Code', 'wp-job-board' ),
						'desc'    => __( 'Enter your Currency Code. Default USD', 'wp-job-board' ),
						'id'      => 'currency_code',
						'type'    => 'text',
						'default' => 'USD',
					),
					array(
						'name'    => __( 'Currency Position', 'wp-job-board' ),
						'desc'    => 'Choose the position of the currency sign.',
						'id'      => 'currency_position',
						'type'    => 'select',
						'options' => array(
							'before' => __( 'Before - $10', 'wp-job-board' ),
							'after'  => __( 'After - 10$', 'wp-job-board' )
						),
						'default' => 'before',
					),
					array(
						'name'    => __( 'Decimal places', 'wp-job-board' ),
						'desc'            => __( 'This sets the number of decimal points shown in displayed prices.', 'wp-job-board' ),
						'id'      => 'money_decimals',
						'type'    => 'text_small',
						'attributes' 	    => array(
							'type' 				=> 'number',
							'min'				=> 0,
							'pattern' 			=> '\d*',
						)
					),
					array(
						'name'    => __( 'Decimal Separator', 'wp-job-board' ),
						'desc'    => __( 'The symbol (usually , or .) to separate decimal points', 'wp-job-board' ),
						'id'      => 'money_dec_point',
						'type'    => 'text_small',
						'default' => '.',
					),
					array(
						'name'    => __( 'Thousands Separator', 'wp-job-board' ),
						'desc'    => __( 'If you need space, enter &nbsp;', 'wp-job-board' ),
						'id'      => 'money_thousands_separator',
						'type'    => 'text_small'
					),
					array(
						'name' => __( 'File Types', 'wp-job-board' ),
						'desc' => '',
						'type' => 'wp_job_board_title',
						'id'   => 'wp_job_board_title_general_settings_3',
						'before_row' => '<hr>',
						'after_row'  => '<hr>'
					),
					array(
						'name'    => __( 'Images File Types', 'wp-job-board' ),
						'id'      => 'image_file_types',
						'type'    => 'multicheck_inline',
						'options' => $images_file_types,
						'default' => array('jpg', 'jpeg', 'jpe', 'png')
					),
					array(
						'name'    => __( 'CV File Types', 'wp-job-board' ),
						'id'      => 'cv_file_types',
						'type'    => 'multicheck_inline',
						'options' => $cv_mime_types,
						'default' => array('doc', 'pdf', 'docx')
					),
					array(
						'name' => __( 'Map API Settings', 'wp-job-board' ),
						'desc' => '',
						'type' => 'wp_job_board_title',
						'id'   => 'wp_job_board_title_general_settings_4',
						'before_row' => '<hr>',
						'after_row'  => '<hr>'
					),
					array(
						'name'    => __( 'Map Service', 'wp-job-board' ),
						'id'      => 'map_service',
						'type'    => 'select',
						'options' => array(
							'mapbox' => __('Mapbox', 'wp-job-board'),
							'google-map' => __('Google Maps', 'wp-job-board'),
						),
					),
					array(
						'name'    => __( 'Google Map API', 'wp-job-board' ),
						'desc'    => __( 'Google requires an API key to retrieve location information for job listings. Acquire an API key from the <a href="https://developers.google.com/maps/documentation/geocoding/get-api-key">Google Maps API developer site.</a>', 'wp-job-board' ),
						'id'      => 'google_map_api_keys',
						'type'    => 'text',
						'default' => '',
					),
					array(
						'name'    => __( 'Google Maps Style', 'wp-job-board' ),
						'desc' 	  => wp_kses(__('<a href="//snazzymaps.com/">Get custom style</a> and paste it below. If there is nothing added, we will fallback to the Google Maps service.', 'wp-job-board'), array('a' => array('href' => array()))),
						'id'      => 'google_map_style',
						'type'    => 'textarea',
						'default' => '',
					),
					array(
						'name'    => __( 'Mapbox Token', 'wp-job-board' ),
						'desc' => wp_kses(__('<a href="//www.mapbox.com/help/create-api-access-token/">Get a FREE token</a> and paste it below. If there is nothing added, we will fallback to the Google Maps service.', 'wp-job-board'), array('a' => array('href' => array()))),
						'id'      => 'mapbox_token',
						'type'    => 'text',
						'default' => '',
					),
					array(
						'name'    => __( 'Mapbox Style', 'wp-job-board' ),
						'id'      => 'mapbox_style',
						'type'    => 'wp_job_board_image_select',
						'default' => 'mapbox.streets-basic',
						'options' => array(
		                    'mapbox.streets-basic' => array(
		                        'alt' => esc_html__('streets basic', 'wp-job-board'),
		                        'img' => get_template_directory_uri() . '/inc/assets/images/streets-basic.png'
		                    ),
		                    'mapbox.streets' => array(
		                        'alt' => esc_html__('streets', 'wp-job-board'),
		                        'img' => get_template_directory_uri() . '/inc/assets/images/streets.png'
		                    ),
		                    'mapbox.outdoors' => array(
		                        'alt' => esc_html__('outdoors', 'wp-job-board'),
		                        'img' => get_template_directory_uri() . '/inc/assets/images/outdoors.png'
		                    ),
		                    'mapbox.light' => array(
		                        'alt' => esc_html__('light', 'wp-job-board'),
		                        'img' => get_template_directory_uri() . '/inc/assets/images/light.png'
		                    ),
		                    'mapbox.emerald' => array(
		                        'alt' => esc_html__('emerald', 'wp-job-board'),
		                        'img' => get_template_directory_uri() . '/inc/assets/images/emerald.png'
		                    ),
		                    'mapbox.satellite' => array(
		                        'alt' => esc_html__('satellite', 'wp-job-board'),
		                        'img' => get_template_directory_uri() . '/inc/assets/images/satellite.png'
		                    ),
		                    'mapbox.pencil' => array(
		                        'alt' => esc_html__('pencil', 'wp-job-board'),
		                        'img' => get_template_directory_uri() . '/inc/assets/images/pencil.png'
		                    ),
		                    'mapbox.pirates' => array(
		                        'alt' => esc_html__('pirates', 'wp-job-board'),
		                        'img' => get_template_directory_uri() . '/inc/assets/images/pirates.png'
		                    ),
		                ),
					),
					array(
						'name'    => __( 'Geocoder Country', 'wp-job-board' ),
						'id'      => 'geocoder_country',
						'type'    => 'select',
						'options' => $countries
					),
					array(
						'name' => __( 'Default maps location', 'wp-job-board' ),
						'desc' => '',
						'type' => 'wp_job_board_title',
						'id'   => 'wp_job_board_title_general_settings_default_maps_location',
						'before_row' => '<hr>',
						'after_row'  => '<hr>'
					),
					array(
						'name'    => __( 'Latitude', 'wp-job-board' ),
						'desc'    => __( 'Enter your latitude', 'wp-job-board' ),
						'id'      => 'default_maps_location_latitude',
						'type'    => 'text_small',
						'default' => '43.6568',
					),
					array(
						'name'    => __( 'Longitude', 'wp-job-board' ),
						'desc'    => __( 'Enter your longitude', 'wp-job-board' ),
						'id'      => 'default_maps_location_longitude',
						'type'    => 'text_small',
						'default' => '-79.4512',
					),
					array(
						'name' => __( 'Distance Settings', 'wp-job-board' ),
						'desc' => '',
						'type' => 'wp_job_board_title',
						'id'   => 'wp_job_board_title_general_settings_distance',
						'before_row' => '<hr>',
						'after_row'  => '<hr>'
					),
					array(
						'name'    => __( 'Distance unit', 'wp-job-board' ),
						'id'      => 'distance_unit',
						'type'    => 'select',
						'options' => array(
							'km' => __('Kilometers', 'wp-job-board'),
							'miles' => __('Miles', 'wp-job-board'),
						),
						'default' => 'miles',
					),
					array(
						'name' => __( 'Location Settings', 'wp-job-board' ),
						'desc' => '',
						'type' => 'wp_job_board_title',
						'id'   => 'wp_job_board_title_general_settings_location',
						'before_row' => '<hr>',
						'after_row'  => '<hr>'
					),
					array(
						'name'    => __( 'Number Fields', 'wp-job-board' ),
						'id'      => 'location_nb_fields',
						'type'    => 'select',
						'options' => array(
							'1' => __('1 Field', 'wp-job-board'),
							'2' => __('2 Fields', 'wp-job-board'),
							'3' => __('3 Fields', 'wp-job-board'),
							'4' => __('4 Fields', 'wp-job-board'),
						),
						'default' => '1',
						'desc'    => __( 'You can set 4 fields for regions like: Country, State, City, District', 'wp-job-board' ),
					),
					array(
						'name'    => __( 'First Field Label', 'wp-job-board' ),
						'desc'    => __( 'First location field label', 'wp-job-board' ),
						'id'      => 'location_1_field_label',
						'type'    => 'text',
						'default' => 'Country',
					),
					array(
						'name'    => __( 'Second Field Label', 'wp-job-board' ),
						'desc'    => __( 'Second location field label', 'wp-job-board' ),
						'id'      => 'location_2_field_label',
						'type'    => 'text',
						'default' => 'State',
					),
					array(
						'name'    => __( 'Third Field Label', 'wp-job-board' ),
						'desc'    => __( 'Third location field label', 'wp-job-board' ),
						'id'      => 'location_3_field_label',
						'type'    => 'text',
						'default' => 'City',
					),
					array(
						'name'    => __( 'Fourth Field Label', 'wp-job-board' ),
						'desc'    => __( 'Fourth location field label', 'wp-job-board' ),
						'id'      => 'location_4_field_label',
						'type'    => 'text',
						'default' => 'District',
					),
				)
			)		 
		);

		// Job Submission
		$wp_job_board_settings['job_submission'] = array(
			'id'         => 'options_page',
			'wp_job_board_title' => __( 'Job Submission', 'wp-job-board' ),
			'show_on'    => array( 'key' => 'options-page', 'value' => array( $this->key, ), ),
			'fields'     => apply_filters( 'wp_job_board_settings_job_submission', array(
					array(
						'name' => __( 'Job Submission', 'wp-job-board' ),
						'type' => 'wp_job_board_title',
						'id'   => 'wp_job_board_title_job_submission_settings_1',
						'before_row' => '<hr>',
						'after_row'  => '<hr>'
					),
					array(
						'name'    => __( 'Submit Job Form Page', 'wp-job-board' ),
						'desc'    => __( 'This is page to display form for submit job. The <code>[wp_job_board_submission]</code> shortcode should be on this page.', 'wp-job-board' ),
						'id'      => 'submit_job_form_page_id',
						'type'    => 'select',
						'options' => $pages,
					),
					array(
						'name'    => __( 'Moderate New Listings', 'wp-job-board' ),
						'desc'    => __( 'Require admin approval of all new listing submissions', 'wp-job-board' ),
						'id'      => 'submission_requires_approval',
						'type'    => 'select',
						'options' => array(
							'on' 	=> __( 'Enable', 'wp-job-board' ),
							'off'   => __( 'Disable', 'wp-job-board' ),
						),
						'default' => 'on',
					),
					array(
						'name'    => __( 'Allow Published Edits', 'wp-job-board' ),
						'desc'    => __( 'Choose whether published job listings can be edited and if edits require admin approval. When moderation is required, the original job listings will be unpublished while edits await admin approval.', 'wp-job-board' ),
						'id'      => 'user_edit_published_submission',
						'type'    => 'select',
						'options' => array(
							'no' 	=> __( 'Users cannot edit', 'wp-job-board' ),
							'yes'   => __( 'Users can edit without admin approval', 'wp-job-board' ),
							'yes_moderated'   => __( 'Users can edit, but edits require admin approval', 'wp-job-board' ),
						),
						'default' => 'yes',
					),
					array(
						'name'            => __( 'Listing Duration', 'wp-job-board' ),
						'desc'            => __( 'Listings will display for the set number of days, then expire. Leave this field blank if you don\'t want listings to have an expiration date.', 'wp-job-board' ),
						'id'              => 'submission_duration',
						'type'            => 'text_small',
						'default'         => 30,
					),
				), $pages
			)		 
		);

		// Job Submission
		$wp_job_board_settings['pages'] = array(
			'id'         => 'options_page',
			'wp_job_board_title' => __( 'Pages', 'wp-job-board' ),
			'show_on'    => array( 'key' => 'options-page', 'value' => array( $this->key, ), ),
			'fields'     => apply_filters( 'wp_job_board_settings_pages', array(
					array(
						'name'    => __( 'Jobs Page', 'wp-job-board' ),
						'desc'    => __( 'This lets the plugin know the location of the jobs listing page. The <code>[wp_job_board_jobs]</code> shortcode should be on this page.', 'wp-job-board' ),
						'id'      => 'jobs_page_id',
						'type'    => 'select',
						'options' => $pages,
					),
					
					
					array(
						'name'    => __( 'Login/Register Page', 'wp-job-board' ),
						'desc'    => __( 'This lets the plugin know the location of the job listings page. The <code>[wp_job_board_login]</code> <code>[wp_job_board_register]</code> shortcode should be on this page.', 'wp-job-board' ),
						'id'      => 'login_register_page_id',
						'type'    => 'select',
						'options' => $pages,
					),
					array(
						'name'    => __( 'After Login Page', 'wp-job-board' ),
						'desc'    => __( 'This lets the plugin know the page after login/register.', 'wp-job-board' ),
						'id'      => 'after_login_page_id',
						'type'    => 'select',
						'options' => $pages,
					),
					array(
						'name'    => __( 'Approve User Page', 'wp-job-board' ),
						'desc'    => __( 'This lets the plugin know the location of the job listings page. The <code>[wp_job_board_approve_user]</code> shortcode should be on this page.', 'wp-job-board' ),
						'id'      => 'approve_user_page_id',
						'type'    => 'select',
						'options' => $pages,
					),
					array(
						'name'    => __( 'User Dashboard Page', 'wp-job-board' ),
						'desc'    => __( 'This lets the plugin know the location of the user dashboard. The <code>[wp_job_board_user_dashboard]</code> shortcode should be on this page.', 'wp-job-board' ),
						'id'      => 'user_dashboard_page_id',
						'type'    => 'select',
						'options' => $pages,
					),
					array(
						'name'    => __( 'Edit Profile Page', 'wp-job-board' ),
						'desc'    => __( 'This lets the plugin know the location of the user edit profile. The <code>[wp_job_board_change_profile]</code> shortcode should be on this page.', 'wp-job-board' ),
						'id'      => 'edit_profile_page_id',
						'type'    => 'select',
						'options' => $pages,
					),
					array(
						'name'    => __( 'Change Password Page', 'wp-job-board' ),
						'desc'    => __( 'This lets the plugin know the location of the user edit profile. The <code>[wp_job_board_change_password]</code> shortcode should be on this page.', 'wp-job-board' ),
						'id'      => 'change_password_page_id',
						'type'    => 'select',
						'options' => $pages,
					),
					array(
						'name'    => __( 'My Jobs Page', 'wp-job-board' ),
						'desc'    => __( 'This lets the plugin know the location of the job listings page. The <code>[wp_job_board_my_jobs]</code> shortcode should be on this page.', 'wp-job-board' ),
						'id'      => 'my_jobs_page_id',
						'type'    => 'select',
						'options' => $pages,
					),
					array(
						'name'    => __( 'My Resume', 'wp-job-board' ),
						'desc'    => __( 'This lets the plugin know the location of the candidate resume page. The <code>[wp_job_board_change_resume]</code> shortcode should be on this page.', 'wp-job-board' ),
						'id'      => 'my_resume_page_id',
						'type'    => 'select',
						'options' => $pages,
					),
					array(
						'name'    => __( 'Terms and Conditions Page', 'wp-job-board' ),
						'desc'    => __( 'This lets the plugin know the Terms and Conditions page.', 'wp-job-board' ),
						'id'      => 'terms_conditions_page_id',
						'type'    => 'select',
						'options' => $pages,
					),
				), $pages
			)		 
		);
		// Employer Settings
		$wp_job_board_settings['employer_settings'] = array(
			'id'         => 'options_page',
			'wp_job_board_title' => __( 'Employer Settings', 'wp-job-board' ),
			'show_on'    => array( 'key' => 'options-page', 'value' => array( $this->key, ), ),
			'fields'     => apply_filters( 'wp_job_board_settings_employer_settings', array(
				array(
					'name'    => __( 'Moderate New Employer', 'wp-job-board' ),
					'desc'    => __( 'Require admin approval of all new employers', 'wp-job-board' ),
					'id'      => 'employers_requires_approval',
					'type'    => 'select',
					'options' => array(
						'auto' 	=> __( 'Auto Approve', 'wp-job-board' ),
						'email_approve' => __( 'Email Approve', 'wp-job-board' ),
						'admin_approve' => __( 'Administrator Approve', 'wp-job-board' ),
					),
					'default' => 'auto',
				),
				array(
					'name'    => __( 'Employers Page', 'wp-job-board' ),
					'desc'    => __( 'This lets the plugin know the location of the employers listing page. The <code>[wp_job_board_employers]</code> shortcode should be on this page.', 'wp-job-board' ),
					'id'      => 'employers_page_id',
					'type'    => 'select',
					'options' => $pages,
				),
				array(
					'name'    => __( 'Number employers per page', 'wp-job-board' ),
					'desc'    => __( 'Number of employers to display per page.', 'wp-job-board' ),
					'id'      => 'number_employers_per_page',
					'type'    => 'text',
					'default' => '10',
				),


				array(
					'name' => __( 'Restrict Employer settings', 'wp-job-board' ),
					'type' => 'wp_job_board_title',
					'id'   => 'wp_job_board_title_api_settings_restrict_employer',
					'before_row' => '<hr>',
					'after_row'  => '<hr>'
				),
				array(
					'name'    => __( 'Restrict Type', 'wp-job-board' ),
					'desc'    => __( 'Select a restrict type for restrict employer', 'wp-job-board' ),
					'id'      => 'employer_restrict_type',
					'type'    => 'select',
					'options' => array(
						'' => __( 'None', 'wp-job-board' ),
						'view' => __( 'View Employer', 'wp-job-board' ),
						'view_contact_info' => __( 'View Employer Contact Info', 'wp-job-board' ),
					),
					'default' => ''
				),
				array(
					'name'    => __( 'Restrict Employer Detail', 'wp-job-board' ),
					'desc'    => __( 'Restrict Employers detail page for all users except employers.', 'wp-job-board' ),
					'id'      => 'employer_restrict_detail',
					'type'    => 'radio',
					'options' => apply_filters( 'wp-job-board-restrict-employer-detail', array(
						'all' => __( 'All (Users, Guests)', 'wp-job-board' ),
						'register_user' => __( 'All Register Users', 'wp-job-board' ),
						'only_applicants' => __( 'Only Applicants (Candidate can view only their own applicants employers.)', 'wp-job-board' ),
						'register_candidate' => __( 'Register Candidates (All registered candidates can view employers.)', 'wp-job-board' ),
						'always_hidden' => __( 'Always Hidden', 'wp-job-board' ),
					)),
					'default' => 'all',
				),
				array(
					'name'    => __( 'Restrict Employer Listing', 'wp-job-board' ),
					'desc'    => __( 'Restrict Employers Listing page for all users except employers.', 'wp-job-board' ),
					'id'      => 'employer_restrict_listing',
					'type'    => 'radio',
					'options' => apply_filters( 'wp-job-board-restrict-employer-listing', array(
						'all' => __( 'All Users (Users, Guests)', 'wp-job-board' ),
						'register_user' => __( 'All Register Users', 'wp-job-board' ),
						'only_applicants' => __( 'Only Applicants (Candidate can view only their own applicants employers.)', 'wp-job-board' ),
						'register_candidate' => __( 'Register Candidates (All registered employers can view employers.)', 'wp-job-board' ),
						'always_hidden' => __( 'Always Hidden', 'wp-job-board' ),
					)),
					'default' => 'all',
				),

				// restrict contact
				array(
					'name'    => __( 'Restrict View Contact Employer', 'wp-job-board' ),
					'desc'    => __( 'Restrict View Contact Employers detail page for all users except employers.', 'wp-job-board' ),
					'id'      => 'employer_restrict_contact_info',
					'type'    => 'radio',
					'options' => apply_filters( 'wp-job-board-restrict-employer-view-contact', array(
						'all' => __( 'All (Users, Guests)', 'wp-job-board' ),
						'register_user' => __( 'All Register Users', 'wp-job-board' ),
						'only_applicants' => __( 'Only Applicants (Candidate can see contact info only their own applicants employers.)', 'wp-job-board' ),
						'register_candidate' => __( 'Register Candidates (All registered employers can see contact info employers.)', 'wp-job-board' ),
						'always_hidden' => __( 'Always Hidden', 'wp-job-board' ),
					)),
					'default' => 'all',
				),

				array(
					'name' => __( 'Employer Review settings', 'wp-job-board' ),
					'type' => 'wp_job_board_title',
					'id'   => 'wp_job_board_title_api_settings_employer_review',
					'before_row' => '<hr>',
					'after_row'  => '<hr>'
				),
				array(
					'name'    => __( 'Restrict Review', 'wp-job-board' ),
					'id'      => 'employers_restrict_review',
					'type'    => 'radio',
					'options' => apply_filters( 'wp-job-board-restrict-employer-review', array(
						'all' => __( 'All (Users, Guests)', 'wp-job-board' ),
						'register_user' => __( 'All Register Users', 'wp-job-board' ),
						'only_applicants' => __( 'Only Applicants (Candidate can see contact info only their own applicants employers.)', 'wp-job-board' ),
						'register_candidate' => __( 'Register Candidates (All registered employers can see contact info employers.)', 'wp-job-board' ),
						'always_hidden' => __( 'Always Hidden', 'wp-job-board' ),
					)),
					'default' => 'all',
				),
			), $pages )
		);
		// Employee Settings
		$wp_job_board_settings['employee_settings'] = array(
			'id'         => 'options_page',
			'wp_job_board_title' => __( 'Employee Settings', 'wp-job-board' ),
			'show_on'    => array( 'key' => 'options-page', 'value' => array( $this->key, ), ),
			'fields'     => apply_filters( 'wp_job_board_settings_employee_settings', array(
				
				array(
					'name'    => __( 'Employee View Dashboard', 'wp-job-board' ),
					'id'      => 'employee_view_dashboard',
					'type'    => 'select',
					'options' => array(
						'on' 	=> __( 'Enable', 'wp-job-board' ),
						'off'   => __( 'Disable', 'wp-job-board' ),
					),
					'default' => 'on',
				),
				array(
					'name'    => __( 'Employee Submit Job', 'wp-job-board' ),
					'id'      => 'employee_submit_job',
					'type'    => 'select',
					'options' => array(
						'on' 	=> __( 'Enable', 'wp-job-board' ),
						'off'   => __( 'Disable', 'wp-job-board' ),
					),
					'default' => 'on',
				),
				array(
					'name'    => __( 'Employee Edit Job', 'wp-job-board' ),
					'id'      => 'employee_edit_job',
					'type'    => 'select',
					'options' => array(
						'on' 	=> __( 'Enable', 'wp-job-board' ),
						'off'   => __( 'Disable', 'wp-job-board' ),
					),
					'default' => 'on',
				),
				array(
					'name'    => __( 'Employee Edit Employer Profile', 'wp-job-board' ),
					'id'      => 'employee_edit_employer_profile',
					'type'    => 'select',
					'options' => array(
						'on' 	=> __( 'Enable', 'wp-job-board' ),
						'off'   => __( 'Disable', 'wp-job-board' ),
					),
					'default' => 'on',
				),
				array(
					'name'    => __( 'Employee View My Jobs', 'wp-job-board' ),
					'id'      => 'employee_view_my_jobs',
					'type'    => 'select',
					'options' => array(
						'on' 	=> __( 'Enable', 'wp-job-board' ),
						'off'   => __( 'Disable', 'wp-job-board' ),
					),
					'default' => 'on',
				),
				array(
					'name'    => __( 'Employee View Applications', 'wp-job-board' ),
					'id'      => 'employee_view_applications',
					'type'    => 'select',
					'options' => array(
						'on' 	=> __( 'Enable', 'wp-job-board' ),
						'off'   => __( 'Disable', 'wp-job-board' ),
					),
					'default' => 'on',
				),
				array(
					'name'    => __( 'Employee View Shortlist Candidate', 'wp-job-board' ),
					'id'      => 'employee_view_shortlist',
					'type'    => 'select',
					'options' => array(
						'on' 	=> __( 'Enable', 'wp-job-board' ),
						'off'   => __( 'Disable', 'wp-job-board' ),
					),
					'default' => 'on',
				),
				array(
					'name'    => __( 'Employee View Candidate Alerts', 'wp-job-board' ),
					'id'      => 'employee_view_candidate_alert',
					'type'    => 'select',
					'options' => array(
						'on' 	=> __( 'Enable', 'wp-job-board' ),
						'off'   => __( 'Disable', 'wp-job-board' ),
					),
					'default' => 'on',
				),
				// array(
				// 	'name'    => __( 'Employee View Packages', 'wp-job-board' ),
				// 	'id'      => 'employee_view_packages',
				// 	'type'    => 'select',
				// 	'options' => array(
				// 		'on' 	=> __( 'Enable', 'wp-job-board' ),
				// 		'off'   => __( 'Disable', 'wp-job-board' ),
				// 	),
				// 	'default' => 'on',
				// ),
				// array(
				// 	'name'    => __( 'Employee View Messages', 'wp-job-board' ),
				// 	'id'      => 'employee_view_messages',
				// 	'type'    => 'select',
				// 	'options' => array(
				// 		'on' 	=> __( 'Enable', 'wp-job-board' ),
				// 		'off'   => __( 'Disable', 'wp-job-board' ),
				// 	),
				// 	'default' => 'on',
				// ),
			), $pages )
		);
		// Candidate Settings
		$wp_job_board_settings['candidate_settings'] = array(
			'id'         => 'options_page',
			'wp_job_board_title' => __( 'Candidate Settings', 'wp-job-board' ),
			'show_on'    => array( 'key' => 'options-page', 'value' => array( $this->key, ), ),
			'fields'     => apply_filters( 'wp_job_board_settings_candidate_settings', array(
				array(
					'name'    => __( 'Candidates Page', 'wp-job-board' ),
					'desc'    => __( 'This lets the plugin know the location of the candidates listing page. The <code>[wp_job_board_candidates]</code> shortcode should be on this page.', 'wp-job-board' ),
					'id'      => 'candidates_page_id',
					'type'    => 'select',
					'options' => $pages,
				),
				array(
					'name'    => __( 'Number candidates per page', 'wp-job-board' ),
					'desc'    => __( 'Number of candidates to display per page.', 'wp-job-board' ),
					'id'      => 'number_candidates_per_page',
					'type'    => 'text',
					'default' => '10',
				),
				array(
					'name' => __( 'Register Candidate settings', 'wp-job-board' ),
					'type' => 'wp_job_board_title',
					'id'   => 'wp_job_board_title_api_settings_register_candidate',
					'before_row' => '<hr>',
					'after_row'  => '<hr>'
				),
				array(
					'name'    => __( 'Moderate New Candidate', 'wp-job-board' ),
					'desc'    => __( 'Require admin approval of all new candidates', 'wp-job-board' ),
					'id'      => 'candidates_requires_approval',
					'type'    => 'select',
					'options' => array(
						'auto' 	=> __( 'Auto Approve', 'wp-job-board' ),
						'email_approve' => __( 'Email Approve', 'wp-job-board' ),
						'admin_approve' => __( 'Administrator Approve', 'wp-job-board' ),
					),
					'default' => 'auto',
				),
				array(
					'name'    => __( 'Moderate New Resume', 'wp-job-board' ),
					'desc'    => __( 'Require admin approval of all new resume', 'wp-job-board' ),
					'id'      => 'resumes_requires_approval',
					'type'    => 'select',
					'options' => array(
						'auto' 	=> __( 'Auto Approve', 'wp-job-board' ),
						'admin_approve' => __( 'Administrator Approve', 'wp-job-board' ),
					),
					'default' => 'auto',
				),
				array(
					'name'            => __( 'Resume Duration', 'wp-job-board' ),
					'desc'            => __( 'Resumes will display for the set number of days, then expire. Leave this field blank if you don\'t want resumes to have an expiration date.', 'wp-job-board' ),
					'id'              => 'resume_duration',
					'type'            => 'text_small',
					'default'         => 30,
				),
				
				array(
					'name' => __( 'Candidate Apply settings', 'wp-job-board' ),
					'type' => 'wp_job_board_title',
					'id'   => 'wp_job_board_title_api_settings_candidate_appy',
					'before_row' => '<hr>',
					'after_row'  => '<hr>'
				),
				array(
					'name'    => __( 'Free Job Apply', 'wp-job-board' ),
					'desc'    => __( 'Allow candidates to apply jobs absolutely package free.', 'wp-job-board' ),
					'id'      => 'candidate_free_job_apply',
					'type'    => 'select',
					'options' => array(
						'on' 	=> __( 'Enable', 'wp-job-board' ),
						'off'   => __( 'Disable', 'wp-job-board' ),
					),
					'default' => 'on',
				),
				array(
					'name'    => __( 'Candidate packages Page', 'wp-job-board' ),
					'desc'    => __( 'Select Candidate Packages Page. It will redirect candidates at selected page to buy package.', 'wp-job-board' ),
					'id'      => 'candidate_package_page_id',
					'type'    => 'select',
					'options' => $pages,
				),
				array(
					'name'            => __( 'Apply Job With Complete Resume', 'wp-job-board' ),
					'desc'            => __( '% Candidate can apply job with percent number resume complete.', 'wp-job-board' ),
					'id'              => 'apply_job_with_percent_resume',
					'type'            => 'text_small',
					'default'         => 70,
					'attributes' 	    => array(
						'type' 				=> 'number',
						'min'				=> 0,
						'max'				=> 100,
						'pattern' 			=> '\d*',
					)
				),

				array(
					'name' => __( 'Restrict Candidate settings', 'wp-job-board' ),
					'type' => 'wp_job_board_title',
					'id'   => 'wp_job_board_title_api_settings_restrict_candidate',
					'before_row' => '<hr>',
					'after_row'  => '<hr>'
				),
				array(
					'name'    => __( 'Restrict Type', 'wp-job-board' ),
					'desc'    => __( 'Select a restrict type for restrict candidate', 'wp-job-board' ),
					'id'      => 'candidate_restrict_type',
					'type'    => 'select',
					'options' => array(
						'' => __( 'None', 'wp-job-board' ),
						'view' => __( 'View Candidate', 'wp-job-board' ),
						'view_contact_info' => __( 'View Candidate Contact Info', 'wp-job-board' ),
					),
					'default' => ''
				),
				array(
					'name'    => __( 'Restrict Candidate Detail', 'wp-job-board' ),
					'desc'    => __( 'Restrict Candidates detail page for all users except employers.', 'wp-job-board' ),
					'id'      => 'candidate_restrict_detail',
					'type'    => 'radio',
					'options' => apply_filters( 'wp-job-board-restrict-candidate-detail', array(
						'all' => __( 'All (Users, Guests)', 'wp-job-board' ),
						'register_user' => __( 'All Register Users', 'wp-job-board' ),
						'only_applicants' => __( 'Only Applicants (Employer can view only their own applicants candidates.)', 'wp-job-board' ),
						'register_employer' => __( 'Register Employers (All registered employers can view candidates.)', 'wp-job-board' ),
					)),
					'default' => 'all',
				),
				array(
					'name'    => __( 'Restrict Candidate Listing', 'wp-job-board' ),
					'desc'    => __( 'Restrict Candidates Listing page for all users except employers.', 'wp-job-board' ),
					'id'      => 'candidate_restrict_listing',
					'type'    => 'radio',
					'options' => apply_filters( 'wp-job-board-restrict-candidate-listing', array(
						'all' => __( 'All Users (Users, Guests)', 'wp-job-board' ),
						'register_user' => __( 'All Register Users', 'wp-job-board' ),
						'only_applicants' => __( 'Only Applicants (Employer can view only their own applicants candidates.)', 'wp-job-board' ),
						'register_employer' => __( 'Register Employers (All registered employers can view candidates.)', 'wp-job-board' ),
					)),
					'default' => 'all',
				),

				// restrict contact
				array(
					'name'    => __( 'Restrict View Contact Candidate', 'wp-job-board' ),
					'desc'    => __( 'Restrict View Contact Candidates detail page for all users except employers.', 'wp-job-board' ),
					'id'      => 'candidate_restrict_contact_info',
					'type'    => 'radio',
					'options' => apply_filters( 'wp-job-board-restrict-candidate-view-contact', array(
						'all' => __( 'All (Users, Guests)', 'wp-job-board' ),
						'register_user' => __( 'All Register Users', 'wp-job-board' ),
						'only_applicants' => __( 'Only Applicants (Employer can see contact info only their own applicants candidates.)', 'wp-job-board' ),
						'register_employer' => __( 'Register Employers (All registered employers can see contact info candidates.)', 'wp-job-board' ),
					)),
					'default' => 'all',
				),

				array(
					'name' => __( 'Candidate Review settings', 'wp-job-board' ),
					'type' => 'wp_job_board_title',
					'id'   => 'wp_job_board_title_api_settings_candidate_review',
					'before_row' => '<hr>',
					'after_row'  => '<hr>'
				),
				array(
					'name'    => __( 'Restrict Review', 'wp-job-board' ),
					'id'      => 'candidates_restrict_review',
					'type'    => 'radio',
					'options' => apply_filters( 'wp-job-board-restrict-candidate-review', array(
						'all' => __( 'All (Users, Guests)', 'wp-job-board' ),
						'register_user' => __( 'All Register Users', 'wp-job-board' ),
						'only_applicants' => __( 'Only Applicants (Employer can see contact info only their own applicants candidates.)', 'wp-job-board' ),
						'register_employer' => __( 'Register Employers (All registered employers can see contact info candidates.)', 'wp-job-board' ),
						'always_hidden' => __( 'Always Hidden', 'wp-job-board' ),
					)),
					'default' => 'all',
				),

			), $pages )
		);
		
		// ReCaaptcha
		$wp_job_board_settings['api_settings'] = array(
			'id'         => 'options_page',
			'wp_job_board_title' => __( 'Social API', 'wp-job-board' ),
			'show_on'    => array( 'key' => 'options-page', 'value' => array( $this->key, ), ),
			'fields'     => apply_filters( 'wp_job_board_settings_api_settings', array(
					// Facebook
					array(
						'name' => __( 'Facebook API settings', 'wp-job-board' ),
						'type' => 'wp_job_board_title',
						'id'   => 'wp_job_board_title_api_settings_facebook_title',
						'before_row' => '<hr>',
						'after_row'  => '<hr>',
						'desc' => sprintf(__('Callback URL is: %s', 'wp-job-board'), admin_url('admin-ajax.php?action=wp_job_board_facebook_login')),
					),
					array(
						'name'            => __( 'App ID', 'wp-job-board' ),
						'desc'            => __( 'Please enter App ID of your Facebook account.', 'wp-job-board' ),
						'id'              => 'facebook_api_app_id',
						'type'            => 'text',
					),
					array(
						'name'            => __( 'App Secret', 'wp-job-board' ),
						'desc'            => __( 'Please enter App Secret of your Facebook account.', 'wp-job-board' ),
						'id'              => 'facebook_api_app_secret',
						'type'            => 'text',
					),
					array(
						'name'    => __( 'Enable Facebook Login', 'wp-job-board' ),
						'id'      => 'enable_facebook_login',
						'type'    => 'checkbox',
					),
					array(
						'name'    => __( 'Enable Facebook Apply', 'wp-job-board' ),
						'id'      => 'enable_facebook_apply',
						'type'    => 'checkbox',
					),

					// Linkedin
					array(
						'name' => __( 'Linkedin API settings', 'wp-job-board' ),
						'type' => 'wp_job_board_title',
						'id'   => 'wp_job_board_title_api_settings_linkedin_title',
						'before_row' => '<hr>',
						'after_row'  => '<hr>',
						'desc' => sprintf(__('Callback URL is: %s', 'wp-job-board'), home_url('/')),
					),
					array(
						'name'            => __( 'Client ID', 'wp-job-board' ),
						'desc'            => __( 'Please enter Client ID of your linkedin app.', 'wp-job-board' ),
						'id'              => 'linkedin_api_client_id',
						'type'            => 'text',
					),
					array(
						'name'            => __( 'Client Secret', 'wp-job-board' ),
						'desc'            => __( 'Please enter Client Secret of your linkedin app.', 'wp-job-board' ),
						'id'              => 'linkedin_api_client_secret',
						'type'            => 'text',
					),
					array(
						'name'    => __( 'Enable Linkedin Login', 'wp-job-board' ),
						'id'      => 'enable_linkedin_login',
						'type'    => 'checkbox',
					),
					array(
						'name'    => __( 'Enable Linkedin Apply', 'wp-job-board' ),
						'id'      => 'enable_linkedin_apply',
						'type'    => 'checkbox',
					),

					// Twitter
					array(
						'name' => __( 'Twitter API settings', 'wp-job-board' ),
						'type' => 'wp_job_board_title',
						'id'   => 'wp_job_board_title_api_settings_twitter_title',
						'before_row' => '<hr>',
						'after_row'  => '<hr>',
						'desc' => sprintf(__('Callback URL is: %s', 'wp-job-board'), home_url('/')),
					),
					array(
						'name'            => __( 'Consumer Key', 'wp-job-board' ),
						'desc'            => __( 'Set Consumer Key for twitter.', 'wp-job-board' ),
						'id'              => 'twitter_api_consumer_key',
						'type'            => 'text',
					),
					array(
						'name'            => __( 'Consumer Secret', 'wp-job-board' ),
						'desc'            => __( 'Set Consumer Secret for twitter.', 'wp-job-board' ),
						'id'              => 'twitter_api_consumer_secret',
						'type'            => 'text',
					),
					array(
						'name'            => __( 'Access Token', 'wp-job-board' ),
						'desc'            => __( 'Set Access Token for twitter.', 'wp-job-board' ),
						'id'              => 'twitter_api_access_token',
						'type'            => 'text',
					),
					array(
						'name'            => __( 'Token Secret', 'wp-job-board' ),
						'desc'            => __( 'Set Token Secret for twitter.', 'wp-job-board' ),
						'id'              => 'twitter_api_token_secret',
						'type'            => 'text',
					),
					array(
						'name'    => __( 'Enable Twitter Login', 'wp-job-board' ),
						'id'      => 'enable_twitter_login',
						'type'    => 'checkbox',
					),
					array(
						'name'    => __( 'Enable Twitter Apply', 'wp-job-board' ),
						'id'      => 'enable_twitter_apply',
						'type'    => 'checkbox',
					),

					// Google API
					array(
						'name' => __( 'Google API settings Settings', 'wp-job-board' ),
						'type' => 'wp_job_board_title',
						'id'   => 'wp_job_board_title_api_settings_google_title',
						'before_row' => '<hr>',
						'after_row'  => '<hr>',
						'desc' => sprintf(__('Callback URL is: %s', 'wp-job-board'), home_url('/')),
					),
					array(
						'name'            => __( 'API Key', 'wp-job-board' ),
						'desc'            => __( 'Please enter API key of your Google account.', 'wp-job-board' ),
						'id'              => 'google_api_key',
						'type'            => 'text',
					),
					array(
						'name'            => __( 'Client ID', 'wp-job-board' ),
						'desc'            => __( 'Please enter Client ID of your Google account.', 'wp-job-board' ),
						'id'              => 'google_api_client_id',
						'type'            => 'text',
					),
					array(
						'name'            => __( 'Client Secret', 'wp-job-board' ),
						'desc'            => __( 'Please enter Client secret of your Google account.', 'wp-job-board' ),
						'id'              => 'google_api_client_secret',
						'type'            => 'text',
					),
					array(
						'name'    => __( 'Enable Google Login', 'wp-job-board' ),
						'id'      => 'enable_google_login',
						'type'    => 'checkbox',
					),
					array(
						'name'    => __( 'Enable Google Apply', 'wp-job-board' ),
						'id'      => 'enable_google_apply',
						'type'    => 'checkbox',
					),

					// Google Recaptcha
					array(
						'name' => __( 'Google reCAPTCHA API Settings', 'wp-job-board' ),
						'type' => 'wp_job_board_title',
						'id'   => 'wp_job_board_title_api_settings_google_recaptcha',
						'before_row' => '<hr>',
						'after_row'  => '<hr>',
						'desc' => __('The plugin use ReCaptcha v2', 'wp-job-board'),
					),
					array(
						'name'            => __( 'Site Key', 'wp-job-board' ),
						'desc'            => __( 'You can retrieve your site key from <a href="https://www.google.com/recaptcha/admin#list">Google\'s reCAPTCHA admin dashboard.</a>', 'wp-job-board' ),
						'id'              => 'recaptcha_site_key',
						'type'            => 'text',
					),
					array(
						'name'            => __( 'Secret Key', 'wp-job-board' ),
						'desc'            => __( 'You can retrieve your secret key from <a href="https://www.google.com/recaptcha/admin#list">Google\'s reCAPTCHA admin dashboard.</a>', 'wp-job-board' ),
						'id'              => 'recaptcha_secret_key',
						'type'            => 'text',
					),
				)
			)		 
		);
		// Email Notification
		$wp_job_board_settings['email_notification'] = array(
			'id'         => 'options_page',
			'wp_job_board_title' => __( 'Email Notification', 'wp-job-board' ),
			'show_on'    => array( 'key' => 'options-page', 'value' => array( $this->key, ), ),
			'fields'     => apply_filters( 'wp_job_board_settings_email_notification', array(
					
					array(
						'name'    => __( 'Admin Notice of New Listing', 'wp-job-board' ),
						'id'      => 'admin_notice_add_new_listing',
						'type'    => 'checkbox',
						'desc' 	=> __( 'Send a notice to the site administrator when a new job is submitted on the frontend.', 'wp-job-board' ),
					),
					array(
						'name'    => __( 'Subject', 'wp-job-board' ),
						'desc'    => sprintf(__( 'Enter email subject. You can add variables: %s', 'wp-job-board' ), WP_Job_Board_Email::display_email_vars('admin_notice_add_new_listing', 'subject') ),
						'id'      => 'admin_notice_add_new_listing_subject',
						'type'    => 'text',
						'default' => '',
					),
					array(
						'name'    => __( 'Content', 'wp-job-board' ),
						'desc'    => sprintf(__( 'Enter email content. You can add variables: %s', 'wp-job-board' ), WP_Job_Board_Email::display_email_vars('admin_notice_add_new_listing', 'content') ),
						'id'      => 'admin_notice_add_new_listing_content',
						'type'    => 'wysiwyg',
						'default' => '',
					),

					
					array(
						'name'    => __( 'Admin Notice of Updated Listing', 'wp-job-board' ),
						'id'      => 'admin_notice_updated_listing',
						'type'    => 'checkbox',
						'desc' 	=> __( 'Send a notice to the site administrator when a job is updated on the frontend.', 'wp-job-board' ),
					),
					array(
						'name'    => __( 'Subject', 'wp-job-board' ),
						'desc'    => sprintf(__( 'Enter email subject. You can add variables: %s', 'wp-job-board' ), WP_Job_Board_Email::display_email_vars('admin_notice_updated_listing', 'subject') ),
						'id'      => 'admin_notice_updated_listing_subject',
						'type'    => 'text',
						'default' => '',
					),
					array(
						'name'    => __( 'Content', 'wp-job-board' ),
						'desc'    => sprintf(__( 'Enter email content. You can add variables: %s', 'wp-job-board' ), WP_Job_Board_Email::display_email_vars('admin_notice_updated_listing', 'content') ),
						'id'      => 'admin_notice_updated_listing_content',
						'type'    => 'wysiwyg',
						'default' => '',
					),

					
					array(
						'name'    => __( 'Admin Notice of Expiring Job Listings', 'wp-job-board' ),
						'id'      => 'admin_notice_expiring_listing',
						'type'    => 'checkbox',
						'desc' 	=> __( 'Send notices to the site administrator before a job listing expires.', 'wp-job-board' ),
					),
					array(
						'name'    => __( 'Notice Period', 'wp-job-board' ),
						'desc'    => __( 'days', 'wp-job-board' ),
						'id'      => 'admin_notice_expiring_listing_days',
						'type'    => 'text_small',
						'default' => '1',
					),
					array(
						'name'    => __( 'Subject', 'wp-job-board' ),
						'desc'    => sprintf(__( 'Enter email subject. You can add variables: %s', 'wp-job-board' ), WP_Job_Board_Email::display_email_vars('admin_notice_expiring_listing', 'subject') ),
						'id'      => 'admin_notice_expiring_listing_subject',
						'type'    => 'text',
						'default' => 'Job Listing Expiring: {{job_title}}',
					),
					array(
						'name'    => __( 'Content', 'wp-job-board' ),
						'desc'    => sprintf(__( 'Enter email content. You can add variables: %s', 'wp-job-board' ), WP_Job_Board_Email::display_email_vars('admin_notice_expiring_listing', 'content') ),
						'id'      => 'admin_notice_expiring_listing_content',
						'type'    => 'wysiwyg',
						'default' => '',
					),

					
					array(
						'name'    => __( 'Employer Notice of Expiring Job Listings', 'wp-job-board' ),
						'id'      => 'employer_notice_expiring_listing',
						'type'    => 'checkbox',
						'desc' 	=> __( 'Send notices to employers before a job listing expires.', 'wp-job-board' ),
					),
					array(
						'name'    => __( 'Notice Period', 'wp-job-board' ),
						'desc'    => __( 'days', 'wp-job-board' ),
						'id'      => 'employer_notice_expiring_listing_days',
						'type'    => 'text_small',
						'default' => '1',
					),
					array(
						'name'    => __( 'Subject', 'wp-job-board' ),
						'desc'    => sprintf(__( 'Enter email subject. You can add variables: %s', 'wp-job-board' ), WP_Job_Board_Email::display_email_vars('employer_notice_expiring_listing', 'subject') ),
						'id'      => 'employer_notice_expiring_listing_subject',
						'type'    => 'text',
						'default' => 'Job Listing Expiring: {{job_title}}',
					),
					array(
						'name'    => __( 'Content', 'wp-job-board' ),
						'desc'    => sprintf(__( 'Enter email content. You can add variables: %s', 'wp-job-board' ), WP_Job_Board_Email::display_email_vars('employer_notice_expiring_listing', 'content') ),
						'id'      => 'employer_notice_expiring_listing_content',
						'type'    => 'wysiwyg',
						'default' => '',
					),


					
					array(
						'name' => __( 'Job Alert', 'wp-job-board' ),
						'desc' => '',
						'type' => 'wp_job_board_title',
						'id'   => 'wp_job_board_title_job_alert',
						'before_row' => '<hr>',
						'after_row'  => '<hr>'
					),
					array(
						'name'    => __( 'Job Alert Subject', 'wp-job-board' ),
						'desc'    => sprintf(__( 'Enter email subject. You can add variables: %s', 'wp-job-board' ), WP_Job_Board_Email::display_email_vars('job_alert_notice', 'subject') ),
						'id'      => 'job_alert_notice_subject',
						'type'    => 'text',
						'default' => sprintf(__( 'Job Alert: %s', 'wp-job-board' ), WP_Job_Board_Email::display_email_vars('job_alert_notice', 'subject') ),
					),
					array(
						'name'    => __( 'Job Alert Content', 'wp-job-board' ),
						'desc'    => sprintf(__( 'Enter email content. You can add variables: %s', 'wp-job-board' ), WP_Job_Board_Email::display_email_vars('job_alert_notice', 'content') ),
						'id'      => 'job_alert_notice_content',
						'type'    => 'wysiwyg',
						'default' => '',
					),

					array(
						'name' => __( 'Candidate Alert', 'wp-job-board' ),
						'desc' => '',
						'type' => 'wp_job_board_title',
						'id'   => 'wp_job_board_title_candidate_alert',
						'before_row' => '<hr>',
						'after_row'  => '<hr>'
					),
					array(
						'name'    => __( 'Candidate Alert Subject', 'wp-job-board' ),
						'desc'    => sprintf(__( 'Enter email subject. You can add variables: %s', 'wp-job-board' ), WP_Job_Board_Email::display_email_vars('candidate_alert_notice', 'subject') ),
						'id'      => 'candidate_alert_notice_subject',
						'type'    => 'text',
						'default' => sprintf(__( 'Candidate Alert: %s', 'wp-job-board' ), WP_Job_Board_Email::display_email_vars('candidate_alert_notice', 'subject') ),
					),
					array(
						'name'    => __( 'Candidate Alert Content', 'wp-job-board' ),
						'desc'    => sprintf(__( 'Enter email content. You can add variables: %s', 'wp-job-board' ), WP_Job_Board_Email::display_email_vars('candidate_alert_notice', 'content') ),
						'id'      => 'candidate_alert_notice_content',
						'type'    => 'wysiwyg',
						'default' => '',
					),

					// Email Apply
					array(
						'name' => __( 'Email Apply Template', 'wp-job-board' ),
						'desc' => '',
						'type' => 'wp_job_board_title',
						'id'   => 'wp_job_board_title_email_apply',
						'before_row' => '<hr>',
						'after_row'  => '<hr>'
					),
					array(
						'name'    => __( 'Email Apply Subject', 'wp-job-board' ),
						'desc'    => sprintf(__( 'Enter email subject. You can add variables: %s', 'wp-job-board' ), WP_Job_Board_Email::display_email_vars('email_apply_job_notice', 'subject') ),
						'id'      => 'email_apply_job_notice_subject',
						'type'    => 'text',
						'default' => sprintf(__( 'Apply Job: %s', 'wp-job-board' ), WP_Job_Board_Email::display_email_vars('email_apply_job_notice', 'subject') ),
					),
					array(
						'name'    => __( 'Email Apply Content', 'wp-job-board' ),
						'desc'    => sprintf(__( 'Enter email content. You can add variables: %s', 'wp-job-board' ), WP_Job_Board_Email::display_email_vars('email_apply_job_notice', 'content') ),
						'id'      => 'email_apply_job_notice_content',
						'type'    => 'wysiwyg',
						'default' => '',
					),

					// Internal Apply
					array(
						'name' => __( 'Internal Apply Template', 'wp-job-board' ),
						'desc' => '',
						'type' => 'wp_job_board_title',
						'id'   => 'wp_job_board_title_internal_apply',
						'before_row' => '<hr>',
						'after_row'  => '<hr>'
					),
					array(
						'name'    => __( 'Internal Apply Subject', 'wp-job-board' ),
						'desc'    => sprintf(__( 'Enter email subject. You can add variables: %s', 'wp-job-board' ), WP_Job_Board_Email::display_email_vars('internal_apply_job_notice', 'subject') ),
						'id'      => 'internal_apply_job_notice_subject',
						'type'    => 'text',
						'default' => sprintf(__( 'Apply Job: %s', 'wp-job-board' ), WP_Job_Board_Email::display_email_vars('internal_apply_job_notice', 'subject') ),
					),
					array(
						'name'    => __( 'Internal Apply Content', 'wp-job-board' ),
						'desc'    => sprintf(__( 'Enter email content. You can add variables: %s', 'wp-job-board' ), WP_Job_Board_Email::display_email_vars('internal_apply_job_notice', 'content') ),
						'id'      => 'internal_apply_job_notice_content',
						'type'    => 'wysiwyg',
						'default' => '',
					),

					// contact form
					array(
						'name' => __( 'Contact Form', 'wp-job-board' ),
						'desc' => '',
						'type' => 'wp_job_board_title',
						'id'   => 'wp_job_board_title_contact_form',
						'before_row' => '<hr>',
						'after_row'  => '<hr>'
					),
					array(
						'name'    => __( 'Contact Form Subject', 'wp-job-board' ),
						'desc'    => sprintf(__( 'Enter email subject. You can add variables: %s', 'wp-job-board' ), WP_Job_Board_Email::display_email_vars('contact_form_notice', 'subject') ),
						'id'      => 'contact_form_notice_subject',
						'type'    => 'text',
						'default' => sprintf(__( 'Contact Form: %s', 'wp-job-board' ), WP_Job_Board_Email::display_email_vars('contact_form_notice', 'subject') ),
					),
					array(
						'name'    => __( 'Contact Form Content', 'wp-job-board' ),
						'desc'    => sprintf(__( 'Enter email content. You can add variables: %s', 'wp-job-board' ), WP_Job_Board_Email::display_email_vars('contact_form_notice', 'content') ),
						'id'      => 'contact_form_notice_content',
						'type'    => 'wysiwyg',
						'default' => '',
					),

					// Reject interview
					array(
						'name' => __( 'Reject interview', 'wp-job-board' ),
						'desc' => '',
						'type' => 'wp_job_board_title',
						'id'   => 'wp_job_board_title_reject_interview',
						'before_row' => '<hr>',
						'after_row'  => '<hr>'
					),
					array(
						'name'    => __( 'Reject interview Subject', 'wp-job-board' ),
						'desc'    => sprintf(__( 'Enter email subject. You can add variables: %s', 'wp-job-board' ), WP_Job_Board_Email::display_email_vars('reject_interview_notice', 'subject') ),
						'id'      => 'reject_interview_notice_subject',
						'type'    => 'text',
						'default' => sprintf(__( 'Reject interview: %s', 'wp-job-board' ), WP_Job_Board_Email::display_email_vars('reject_interview_notice', 'subject') ),
					),
					array(
						'name'    => __( 'Reject interview Content', 'wp-job-board' ),
						'desc'    => sprintf(__( 'Enter email content. You can add variables: %s', 'wp-job-board' ), WP_Job_Board_Email::display_email_vars('reject_interview_notice', 'content') ),
						'id'      => 'reject_interview_notice_content',
						'type'    => 'wysiwyg',
						'default' => '',
					),

					// Undo Reject interview
					array(
						'name' => __( 'Undo Reject interview', 'wp-job-board' ),
						'desc' => '',
						'type' => 'wp_job_board_title',
						'id'   => 'wp_job_board_title_reject_interview',
						'before_row' => '<hr>',
						'after_row'  => '<hr>'
					),
					array(
						'name'    => __( 'Undo Reject interview Subject', 'wp-job-board' ),
						'desc'    => sprintf(__( 'Enter email subject. You can add variables: %s', 'wp-job-board' ), WP_Job_Board_Email::display_email_vars('undo_reject_interview_notice', 'subject') ),
						'id'      => 'undo_reject_interview_notice_subject',
						'type'    => 'text',
						'default' => sprintf(__( 'Reject interview: %s', 'wp-job-board' ), WP_Job_Board_Email::display_email_vars('undo_reject_interview_notice', 'subject') ),
					),
					array(
						'name'    => __( 'Undo Reject interview Content', 'wp-job-board' ),
						'desc'    => sprintf(__( 'Enter email content. You can add variables: %s', 'wp-job-board' ), WP_Job_Board_Email::display_email_vars('undo_reject_interview_notice', 'content') ),
						'id'      => 'undo_reject_interview_notice_content',
						'type'    => 'wysiwyg',
						'default' => '',
					),

					// Approve interview
					array(
						'name' => __( 'Approve interview', 'wp-job-board' ),
						'desc' => '',
						'type' => 'wp_job_board_title',
						'id'   => 'wp_job_board_title_approve_interview',
						'before_row' => '<hr>',
						'after_row'  => '<hr>'
					),
					array(
						'name'    => __( 'Approve interview Subject', 'wp-job-board' ),
						'desc'    => sprintf(__( 'Enter email subject. You can add variables: %s', 'wp-job-board' ), WP_Job_Board_Email::display_email_vars('approve_interview_notice', 'subject') ),
						'id'      => 'approve_interview_notice_subject',
						'type'    => 'text',
						'default' => sprintf(__( 'Approve interview: %s', 'wp-job-board' ), WP_Job_Board_Email::display_email_vars('approve_interview_notice', 'subject') ),
					),
					array(
						'name'    => __( 'Approve interview Content', 'wp-job-board' ),
						'desc'    => sprintf(__( 'Enter email content. You can add variables: %s', 'wp-job-board' ), WP_Job_Board_Email::display_email_vars('approve_interview_notice', 'content') ),
						'id'      => 'approve_interview_notice_content',
						'type'    => 'wysiwyg',
						'default' => '',
					),

					// Undo Approve interview
					array(
						'name' => __( 'Undo Approve interview', 'wp-job-board' ),
						'desc' => '',
						'type' => 'wp_job_board_title',
						'id'   => 'wp_job_board_title_approve_interview',
						'before_row' => '<hr>',
						'after_row'  => '<hr>'
					),
					array(
						'name'    => __( 'Undo Approve interview Subject', 'wp-job-board' ),
						'desc'    => sprintf(__( 'Enter email subject. You can add variables: %s', 'wp-job-board' ), WP_Job_Board_Email::display_email_vars('undo_approve_interview_notice', 'subject') ),
						'id'      => 'undo_approve_interview_notice_subject',
						'type'    => 'text',
						'default' => sprintf(__( 'Approve interview: %s', 'wp-job-board' ), WP_Job_Board_Email::display_email_vars('undo_approve_interview_notice', 'subject') ),
					),
					array(
						'name'    => __( 'Undo Approve interview Content', 'wp-job-board' ),
						'desc'    => sprintf(__( 'Enter email content. You can add variables: %s', 'wp-job-board' ), WP_Job_Board_Email::display_email_vars('undo_approve_interview_notice', 'content') ),
						'id'      => 'undo_approve_interview_notice_content',
						'type'    => 'wysiwyg',
						'default' => '',
					),

					// Approve new user register
					array(
						'name' => __( 'New user register (auto approve)', 'wp-job-board' ),
						'desc' => '',
						'type' => 'wp_job_board_title',
						'id'   => 'wp_job_board_title_user_register_auto_approve',
						'before_row' => '<hr>',
						'after_row'  => '<hr>'
					),
					array(
						'name'    => __( 'New user register Subject', 'wp-job-board' ),
						'desc'    => sprintf(__( 'Enter email subject. You can add variables: %s', 'wp-job-board' ), WP_Job_Board_Email::display_email_vars('user_register_auto_approve', 'subject') ),
						'id'      => 'user_register_auto_approve_subject',
						'type'    => 'text',
						'default' => sprintf(__( 'New user register: %s', 'wp-job-board' ), WP_Job_Board_Email::display_email_vars('user_register_auto_approve', 'subject') ),
					),
					array(
						'name'    => __( 'New user register Content', 'wp-job-board' ),
						'desc'    => sprintf(__( 'Enter email content. You can add variables: %s', 'wp-job-board' ), WP_Job_Board_Email::display_email_vars('user_register_auto_approve', 'content') ),
						'id'      => 'user_register_auto_approve_content',
						'type'    => 'wysiwyg',
						'default' => '',
					),
					// Approve new user register
					array(
						'name' => __( 'Approve new user register', 'wp-job-board' ),
						'desc' => '',
						'type' => 'wp_job_board_title',
						'id'   => 'wp_job_board_title_user_register_need_approve',
						'before_row' => '<hr>',
						'after_row'  => '<hr>'
					),
					array(
						'name'    => __( 'Approve new user register Subject', 'wp-job-board' ),
						'desc'    => sprintf(__( 'Enter email subject. You can add variables: %s', 'wp-job-board' ), WP_Job_Board_Email::display_email_vars('user_register_need_approve', 'subject') ),
						'id'      => 'user_register_need_approve_subject',
						'type'    => 'text',
						'default' => sprintf(__( 'Approve new user register: %s', 'wp-job-board' ), WP_Job_Board_Email::display_email_vars('user_register_need_approve', 'subject') ),
					),
					array(
						'name'    => __( 'Approve new user register Content', 'wp-job-board' ),
						'desc'    => sprintf(__( 'Enter email content. You can add variables: %s', 'wp-job-board' ), WP_Job_Board_Email::display_email_vars('user_register_need_approve', 'content') ),
						'id'      => 'user_register_need_approve_content',
						'type'    => 'wysiwyg',
						'default' => '',
					),
					// Approved user register
					array(
						'name' => __( 'Approved user', 'wp-job-board' ),
						'desc' => '',
						'type' => 'wp_job_board_title',
						'id'   => 'wp_job_board_title_user_register_approved',
						'before_row' => '<hr>',
						'after_row'  => '<hr>'
					),
					array(
						'name'    => __( 'Approved user Subject', 'wp-job-board' ),
						'desc'    => sprintf(__( 'Enter email subject. You can add variables: %s', 'wp-job-board' ), WP_Job_Board_Email::display_email_vars('user_register_approved', 'subject') ),
						'id'      => 'user_register_approved_subject',
						'type'    => 'text',
						'default' => sprintf(__( 'Approve new user register: %s', 'wp-job-board' ), WP_Job_Board_Email::display_email_vars('user_register_approved', 'subject') ),
					),
					array(
						'name'    => __( 'Approved user Content', 'wp-job-board' ),
						'desc'    => sprintf(__( 'Enter email content. You can add variables: %s', 'wp-job-board' ), WP_Job_Board_Email::display_email_vars('user_register_approved', 'content') ),
						'id'      => 'user_register_approved_content',
						'type'    => 'wysiwyg',
						'default' => '',
					),
					// Denied user register
					array(
						'name' => __( 'Denied user', 'wp-job-board' ),
						'desc' => '',
						'type' => 'wp_job_board_title',
						'id'   => 'wp_job_board_title_user_register_denied',
						'before_row' => '<hr>',
						'after_row'  => '<hr>'
					),
					array(
						'name'    => __( 'Denied user Subject', 'wp-job-board' ),
						'desc'    => sprintf(__( 'Enter email subject. You can add variables: %s', 'wp-job-board' ), WP_Job_Board_Email::display_email_vars('user_register_denied', 'subject') ),
						'id'      => 'user_register_denied_subject',
						'type'    => 'text',
						'default' => sprintf(__( 'Approve new user register: %s', 'wp-job-board' ), WP_Job_Board_Email::display_email_vars('user_register_denied', 'subject') ),
					),
					array(
						'name'    => __( 'Denied user Content', 'wp-job-board' ),
						'desc'    => sprintf(__( 'Enter email content. You can add variables: %s', 'wp-job-board' ), WP_Job_Board_Email::display_email_vars('user_register_denied', 'content') ),
						'id'      => 'user_register_denied_content',
						'type'    => 'wysiwyg',
						'default' => '',
					),
					// Reset Password
					array(
						'name' => __( 'Reset Password', 'wp-job-board' ),
						'desc' => '',
						'type' => 'wp_job_board_title',
						'id'   => 'wp_job_board_title_user_reset_password',
						'before_row' => '<hr>',
						'after_row'  => '<hr>'
					),
					array(
						'name'    => __( 'Reset Password Subject', 'wp-job-board' ),
						'desc'    => sprintf(__( 'Enter email subject. You can add variables: %s', 'wp-job-board' ), WP_Job_Board_Email::display_email_vars('user_reset_password', 'subject') ),
						'id'      => 'user_reset_password_subject',
						'type'    => 'text',
						'default' => 'Your new password',
					),
					array(
						'name'    => __( 'Reset Password Content', 'wp-job-board' ),
						'desc'    => sprintf(__( 'Enter email content. You can add variables: %s', 'wp-job-board' ), WP_Job_Board_Email::display_email_vars('user_reset_password', 'content') ),
						'id'      => 'user_reset_password_content',
						'type'    => 'wysiwyg',
						'default' => 'Your new password is: {{new_password}}',
					),
				)
			)		 
		);

		// Indeed Jobs Import
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
		$wp_job_board_settings['indeed_job_import'] = array(
			'id'         => 'options_page',
			'wp_job_board_title' => __( 'Indeed Jobs Import', 'wp-job-board' ),
			'show_on'    => array( 'key' => 'options-page', 'value' => array( $this->key, ), ),
			'fields'     => apply_filters( 'wp_job_board_settings_indeed_job_import', array(
					array(
						'name' => __( 'Enable Indeed Jobs Import', 'wp-job-board' ),
						'id'   => 'indeed_job_import_enable',
						'type' => 'checkbox',
					),
				)
			)		 
		);
		//Return all settings array if necessary

		if ( $active_tab === null   ) {  
			return apply_filters( 'wp_job_board_registered_settings', $wp_job_board_settings );
		}

		// Add other tabs and settings fields as needed
		return apply_filters( 'wp_job_board_registered_'.$active_tab.'_settings', isset($wp_job_board_settings[ $active_tab ])?$wp_job_board_settings[ $active_tab ]:array() );

	}

	/**
	 * Show Settings Notices
	 *
	 * @param $object_id
	 * @param $updated
	 * @param $cmb
	 */
	public function settings_notices( $object_id, $updated, $cmb ) {

		//Sanity check
		if ( $object_id !== $this->key ) {
			return;
		}

		if ( did_action( 'cmb2_save_options-page_fields' ) === 1 ) {
			settings_errors( 'wp_job_board-notices' );
		}

		add_settings_error( 'wp_job_board-notices', 'global-settings-updated', __( 'Settings updated.', 'wp-job-board' ), 'updated' );

	}


	/**
	 * Public getter method for retrieving protected/private variables
	 *
	 * @since  1.0
	 *
	 * @param  string $field Field to retrieve
	 *
	 * @return mixed          Field value or exception is thrown
	 */
	public function __get( $field ) {

		// Allowed fields to retrieve
		if ( in_array( $field, array( 'key', 'fields', 'wp_job_board_title', 'options_page' ), true ) ) {
			return $this->{$field};
		}
		if ( 'option_metabox' === $field ) {
			return $this->option_metabox();
		}

		throw new Exception( 'Invalid property: ' . $field );
	}


}

// Get it started
$WP_Job_Board_Settings = new WP_Job_Board_Settings();

/**
 * Wrapper function around cmb2_get_option
 * @since  0.1.0
 *
 * @param  string $key Options array key
 *
 * @return mixed        Option value
 */
function wp_job_board_get_option( $key = '', $default = false ) {
	global $wp_job_board_options;
	$value = isset( $wp_job_board_options[ $key ] ) ? $wp_job_board_options[ $key ] : $default;
	$value = apply_filters( 'wp_job_board_get_option', $value, $key, $default );

	return apply_filters( 'wp_job_board_get_option_' . $key, $value, $key, $default );
}



/**
 * Get Settings
 *
 * Retrieves all WP_Job_Board plugin settings
 *
 * @since 1.0
 * @return array WP_Job_Board settings
 */
function wp_job_board_get_settings() {
	return apply_filters( 'wp_job_board_get_settings', get_option( 'wp_job_board_settings' ) );
}


/**
 * WP_Job_Board Title
 *
 * Renders custom section titles output; Really only an <hr> because CMB2's output is a bit funky
 *
 * @since 1.0
 *
 * @param       $field_object , $escaped_value, $object_id, $object_type, $field_type_object
 *
 * @return void
 */
function wp_job_board_title_callback( $field_object, $escaped_value, $object_id, $object_type, $field_type_object ) {

	$id                = $field_type_object->field->args['id'];
	$title             = $field_type_object->field->args['name'];
	$field_description = $field_type_object->field->args['desc'];
	if ( $field_description ) {
		echo '<div class="desc">'.$field_description.'</div>';
	}
}


/**
 * Gets a number of posts and displays them as options
 *
 * @param  array $query_args Optional. Overrides defaults.
 * @param  bool  $force      Force the pages to be loaded even if not on settings
 *
 * @see: https://github.com/WebDevStudios/CMB2/wiki/Adding-your-own-field-types
 * @return array An array of options that matches the CMB2 options array
 */
function wp_job_board_cmb2_get_post_options( $query_args, $force = false ) {

	$post_options = array( '' => '' ); // Blank option

	if ( ( ! isset( $_GET['page'] ) || 'job_listing-settings' != $_GET['page'] ) && ! $force ) {
		return $post_options;
	}

	$args = wp_parse_args( $query_args, array(
		'post_type'   => 'page',
		'numberposts' => 10,
	) );

	$posts = get_posts( $args );

	if ( $posts ) {
		foreach ( $posts as $post ) {

			$post_options[ $post->ID ] = $post->post_title;

		}
	}

	return $post_options;
}


/**
 * Modify CMB2 Default Form Output
 *
 * @param string @args
 *
 * @since 1.0
 */

add_filter( 'cmb2_get_metabox_form_format', 'wp_job_board_modify_cmb2_form_output', 10, 3 );

function wp_job_board_modify_cmb2_form_output( $form_format, $object_id, $cmb ) {

	//only modify the wp_job_board settings form
	if ( 'wp_job_board_settings' == $object_id && 'options_page' == $cmb->cmb_id ) {

		return '<form class="cmb-form" method="post" id="%1$s" enctype="multipart/form-data" encoding="multipart/form-data"><input type="hidden" name="object_id" value="%2$s">%3$s<div class="wp_job_board-submit-wrap"><input type="submit" name="submit-cmb" value="' . __( 'Save Settings', 'wp-job-board' ) . '" class="button-primary"></div></form>';
	}

	return $form_format;

}
