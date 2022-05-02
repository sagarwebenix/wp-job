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

class WP_Job_Board_Indeed_Jobs_Hooks {

    private static $key = 'wp_job_board_indeed_import';

    public static function init() {
        
        add_action( 'admin_enqueue_scripts', array(__CLASS__, 'admin_enqueue_scripts') );

        // Job Fields
        add_filter( 'cmb2_meta_boxes', array( __CLASS__, 'indeed_job_meta_fields' ), 100 );

        add_action( 'admin_menu', array( __CLASS__, 'indeed_jobs_admin_menu' ) );

        add_action( 'wp_ajax_wp_job_board_ajax_indeed_job_import', array( __CLASS__, 'process_import_indeed_jobs' ) );

        add_filter( 'wp-job-board-get-company-name', array( __CLASS__, 'get_company_name' ) );

        add_filter( 'wp-job-board-get-company-name-html', array( __CLASS__, 'get_company_name_html' ) );
    }

    public static function admin_enqueue_scripts() {
        wp_enqueue_script( 'wp-job-board-indeed-jobs', WP_JOB_BOARD_PLUGIN_URL . 'assets/js/admin-indeed-job-impport.js', array( 'jquery' ), '1.0.0', true );

        $args = array(
            'ajaxurl' => admin_url('admin-ajax.php')
        );

        wp_localize_script('wp-job-board-indeed-jobs', 'wp_job_board_indeed_opts', $args);
    }

    public static function indeed_jobs_admin_menu() {
        if ( wp_job_board_get_option('indeed_job_import_enable') ) {
            add_submenu_page( 'edit.php?post_type=job_listing', esc_html__('Import Indeed Jobs', 'wp-job-board'), esc_html__('Import Indeed Jobs', 'wp-job-board'), 'manage_options', 'import-indeed-jobs', array( __CLASS__, 'import_indeed_jobs_settings' ) );
        }
    }

    public static function import_indeed_jobs_settings() {
        ?>
        <div class="wrap wp_job_board_settings_page cmb2_options_page">
            <h2><?php esc_html_e('Import Indeed Jobs', 'wp-job-board'); ?></h2>
            
            <?php cmb2_metabox_form( self::import_indeed_fields(), self::$key ); ?>

        </div>

        <?php
    }

    public static function import_indeed_fields() {
        $fields = array(
            'id'         => 'options_page',
            'wp_job_board_title' => __( 'Indeed Jobs Import', 'wp-job-board' ),
            'show_on'    => array( 'key' => 'options-page', 'value' => array( self::$key, ), ),
            'fields'     => apply_filters( 'wp_job_board_indeed_job_import_fields', array(
                    array(
                        'name'    => __( 'Publisher Number', 'wp-job-board' ),
                        'desc'    => __( 'Enter publisher number to import search jobs from Indeed.', 'wp-job-board' ),
                        'id'      => 'indeed_job_import_number',
                        'type'    => 'text',
                        'default' => get_option('wp_job_board_publisher_number')
                    ),
                    array(
                        'name'    => __( 'Keywords', 'wp-job-board' ),
                        'desc'    => __( 'Enter job title, keywords or company name. Default keyword is all.', 'wp-job-board' ),
                        'id'      => 'indeed_job_import_keywords',
                        'type'    => 'text',
                    ),
                    array(
                        'name'    => __( 'Country', 'wp-job-board' ),
                        'desc'    => __( 'Select a country for search.', 'wp-job-board' ),
                        'id'      => 'indeed_job_import_country',
                        'type'    => 'select',
                        'options' => WP_Job_Board_Indeed_API::indeed_api_countries()
                    ),
                    array(
                        'name'    => __( 'Location', 'wp-job-board' ),
                        'desc'    => __( 'Enter a location for search.', 'wp-job-board' ),
                        'id'      => 'indeed_job_import_location',
                        'type'    => 'text',
                    ),
                    array(
                        'name'    => __( 'Type', 'wp-job-board' ),
                        'desc'    => __( 'Choose which type of job to query.', 'wp-job-board' ),
                        'id'      => 'indeed_job_import_type',
                        'type'    => 'select',
                        'options' => WP_Job_Board_Indeed_API::indeed_job_types()
                    ),
                    array(
                        'name'    => __( 'Sort By', 'wp-job-board' ),
                        'desc'    => __( 'Choose sort query results by Date/Relevance.', 'wp-job-board' ),
                        'id'      => 'indeed_job_import_sort_by',
                        'type'    => 'select',
                        'options' => array(
                            'date' => esc_html__('Date', 'wp-job-board'),
                            'relevance' => esc_html__('Relevance', 'wp-job-board'),
                        )
                    ),
                    array(
                        'name'    => __( 'Start Import Jobs', 'wp-job-board' ),
                        'desc'    => __( 'Enter start number to import jobs. Default start number is 1.', 'wp-job-board' ),
                        'id'      => 'indeed_job_import_start_number',
                        'type'    => 'text',
                        'default' => '1',
                    ),
                    array(
                        'name'    => __( 'Number of Jobs to Import (Maximum Limit 25)', 'wp-job-board' ),
                        'desc'    => __( 'Enter number of jobs to import. Default number of import jobs is 10. Maximum import jobs limit is 25.', 'wp-job-board' ),
                        'id'      => 'indeed_job_import_number_jobs',
                        'type'    => 'text',
                        'default' => '10',
                    ),
                    array(
                        'name'    => __( 'Expired on', 'wp-job-board' ),
                        'desc'    => __( 'Enter number of days (numeric format) for expiray date after job posted date.', 'wp-job-board' ),
                        'id'      => 'indeed_job_import_expired_on',
                        'type'    => 'text',
                        'default' => '0',
                    ),
                    array(
                        'name'          => __( 'Posted By', 'wp-job-board' ),
                        'id'            => 'indeed_job_import_posted_by',
                        'type'          => 'user_ajax_search',
                        'query_args'    => array(
                            'role'              => array( 'wp_job_board_employer' ),
                            'search_columns'    => array( 'user_login', 'user_email' )
                        ),
                        'desc'    => __( 'Choose which type of job to query.', 'wp-job-board' ),
                    )
                )
            )        
        );
        return $fields;
    }

    public static function indeed_job_meta_fields( $metaboxes ) {
        if ( wp_job_board_get_option('indeed_job_import_enable') ) {
            $prefix = WP_JOB_BOARD_JOB_LISTING_PREFIX;

            $metaboxes[ $prefix . 'indeed_job_fields' ] = array(
                'id'                        => $prefix . 'indeed_job_fields',
                'title'                     => __( 'Indeed Job Fields', 'wp-job-board' ),
                'object_types'              => array( 'job_listing' ),
                'context'                   => 'normal',
                'priority'                  => 'high',
                'show_names'                => true,
                'show_in_rest'              => true,
                'fields'                    => array(
                    array(
                        'name'              => __( 'Job Detail Url', 'wp-job-board' ),
                        'id'                => WP_JOB_BOARD_JOB_LISTING_PREFIX . 'indeed_detail_url',
                        'type'              => 'text',
                    ),
                    array(
                        'name'              => __( 'Company Name', 'wp-job-board' ),
                        'id'                => WP_JOB_BOARD_JOB_LISTING_PREFIX . 'indeed_company_name',
                        'type'              => 'text',
                    ),
                ),
            );
        }
        return $metaboxes;
    }

    public static function get_job_type($type) {
        switch ($type) {
            case 'fulltime' :
                $type = esc_html__('Full Time', 'wp-job-board');
                break;
            case 'parttime' :
                $type = esc_html__('Part Time', 'wp-job-board');
                break;
            case 'contract' :
                $type = esc_html__('Contract', 'wp-job-board');
                break;
            case 'internship' :
                $type = esc_html__('Internship', 'wp-job-board');
                break;
            case 'temporary' :
                $type = esc_html__('Temporary', 'wp-job-board');
                break;
        }
        return $type;
    }

    public static function process_import_indeed_jobs() {
        $publisher_number = !empty($_POST['indeed_job_import_number']) ? stripslashes($_POST['indeed_job_import_number']) : '';
        $search_keywords = !empty($_POST['indeed_job_import_keywords']) ? sanitize_text_field(stripslashes($_POST['indeed_job_import_keywords'])) : '';
        $search_country = !empty($_POST['indeed_job_import_country']) ? sanitize_text_field(stripslashes($_POST['indeed_job_import_country'])) : '';
        $search_location = !empty($_POST['indeed_job_import_location']) ? sanitize_text_field(stripslashes($_POST['indeed_job_import_location'])) : '';
        $job_type = !empty($_POST['indeed_job_import_job_type']) ? sanitize_text_field($_POST['indeed_job_import_job_type']) : '';
        $start = !empty($_POST['indeed_job_import_start_number']) ? sanitize_text_field($_POST['indeed_job_import_start_number']) : '';
        $limit = !empty($_POST['indeed_job_import_number_jobs']) ? sanitize_text_field($_POST['indeed_job_import_number_jobs']) : '';
        $sort_by = !empty($_POST['indeed_job_import_sort_by']) ? sanitize_text_field($_POST['indeed_job_import_sort_by']) : '';
        $posted_by = !empty($_POST['indeed_job_import_posted_by']) ? sanitize_text_field($_POST['indeed_job_import_posted_by']) : '';


        $limit = $limit ? $limit : 10;
        $limit =  $limit > 25 ? 25 :  $limit;
        $start = $start ? ($start - 1) : 0;
        $api_args = array(
            'publisher' => $publisher_number,
            'q' => $search_keywords ? $search_keywords : 'all',
            'l' => $search_location,
            'co' => $search_country,
            'jt' => $job_type,
            'sort' => $sort_by,
            'start' => $start,
            'limit' => $limit,
        );

        $indeed_jobs = WP_Job_Board_Indeed_API::get_indeed_jobs($api_args);
        
        if (isset($indeed_jobs['error']) && $indeed_jobs['error'] != '') {
            $json = array(
                'status' => false,
                'msg' => $indeed_jobs['error'],
            );
            echo json_encode($json);
            die();
        } elseif (empty($indeed_jobs)) {
            $json = array(
                'status' => false,
                'msg' => esc_html__('Sorry! There are no jobs found for your search query.', 'wp-job-board')
            );
            echo json_encode($json);
            die();
        } else {
            update_option('wp_job_board_publisher_number', $publisher_number);
            $user_id = WP_Job_Board_User::get_user_id();
            foreach ($indeed_jobs as $indeed_job) {
                $indeed_job = (object) $indeed_job;
                $date = date('Y-m-d H:i:s', strtotime($indeed_job->date));
                $post_data = array(
                    'post_type' => 'job_listing',
                    'post_title' => $indeed_job->jobtitle,
                    'post_content' => $indeed_job->snippet,
                    'post_status' => 'publish',
                    'post_date' => $date,
                    'post_author' => !empty($posted_by) ? $posted_by : $user_id
                );
                // Insert the job into the database
                $post_id = wp_insert_post($post_data);

                // Insert job expired on meta key
                $expire_days = $_POST['expire_days'];
                $expired_date = date('m-d-Y', strtotime("$expire_days days", strtotime($indeed_job->date)));
                update_post_meta($post_id, WP_JOB_BOARD_JOB_LISTING_PREFIX . 'expiry_date', strtotime($expired_date), true);

                // Insert job address meta key
                $address = $location_addrs = array();
                if ($indeed_job->city != '') {
                    $address[] = $indeed_job->city;
                }
                if ($indeed_job->state != '') {
                    $address[] = $indeed_job->state;
                }
                if ($indeed_job->country != '') {
                    $indeed_country = $indeed_job->country;
                    $countries = WP_Job_Board_Indeed_API::indeed_api_countries();
                    $address[] = isset($countries[strtolower($indeed_country)]) ? $countries[strtolower($indeed_country)] : '';
                }
                if (!empty($address)) {
                    $address = implode(', ', $address);
                    $location_addrs['address'] = $address;
                    add_post_meta($post_id, WP_JOB_BOARD_JOB_LISTING_PREFIX . 'address', $address, true);
                } else {
                    $location_addrs['address'] = '';
                }

                // Insert job latitude meta key
                add_post_meta($post_id, WP_JOB_BOARD_JOB_LISTING_PREFIX . 'map_location_latitude', esc_attr($indeed_job->latitude), true);

                // Insert job longitude meta key
                add_post_meta($post_id, WP_JOB_BOARD_JOB_LISTING_PREFIX . 'map_location_longitude', esc_attr($indeed_job->longitude), true);

                $location_addrs['latitude'] = $indeed_job->latitude;
                $location_addrs['longitude'] = $indeed_job->longitude;
                add_post_meta($post_id, WP_JOB_BOARD_JOB_LISTING_PREFIX . 'map_location', $location_addrs, true);

                // Insert job referral meta key
                add_post_meta($post_id, WP_JOB_BOARD_JOB_LISTING_PREFIX . 'job_referral', 'indeed', true);

                // Insert job detail url meta key
                add_post_meta($post_id, WP_JOB_BOARD_JOB_LISTING_PREFIX . 'indeed_detail_url', esc_url($indeed_job->url), true);

                // Insert job comapny name meta key
                add_post_meta($post_id, WP_JOB_BOARD_JOB_LISTING_PREFIX . 'indeed_company_name', $indeed_job->company, true);

                // Create and assign taxonomy to post
                if ( $job_type ) {
                    $job_type = self::get_job_type($job_type);
                    $term = get_term_by('name', $job_type, 'job_listing_type');
                    if ( $term == '' ) {
                        wp_insert_term($job_type, 'job_listing_type');
                        $term = get_term_by('name', $job_type, 'job_listing_type');
                    }
                    wp_set_post_terms($post_id, $term->term_id, 'job_listing_type');
                }
            }
            $json = array(
                'status' => false,
                'msg' => sprintf(__('%s indeed jobs are imported successfully.', 'wp-job-board'), count($indeed_jobs))
            );
            echo json_encode($json);
            die();
        }
        die();
    }

    public static function get_company_name($ouput, $post) {
        $job_referral = get_post_meta($post->ID, WP_JOB_BOARD_JOB_LISTING_PREFIX . 'job_referral', true);
        $indeed_company_name = get_post_meta($post->ID, WP_JOB_BOARD_JOB_LISTING_PREFIX . 'indeed_company_name', true);
        if ($job_referral == 'indeed' && $indeed_detail_url != '') {
            $ouput = $indeed_company_name;
        }
        return $ouput;
    }

    public static function get_company_name_html($ouput, $post) {
        $job_referral = get_post_meta($post->ID, WP_JOB_BOARD_JOB_LISTING_PREFIX . 'job_referral', true);
        $indeed_company_name = get_post_meta($post->ID, WP_JOB_BOARD_JOB_LISTING_PREFIX . 'indeed_company_name', true);
        if ($job_referral == 'indeed' && $indeed_detail_url != '') {
            $ouput = sprintf(wp_kses(__('<span class="employer text-theme">%s</span>', 'wp-job-board'), array( 'span' => array('class' => array()) ) ), $indeed_company_name );
        }
        return $ouput;
    }

    public static function view_more_btn($post) {
        $ouput = '';
        $job_referral = get_post_meta($post->ID, WP_JOB_BOARD_JOB_LISTING_PREFIX . 'job_referral', true);
        $indeed_detail_url = get_post_meta($post->ID, WP_JOB_BOARD_JOB_LISTING_PREFIX . 'indeed_detail_url', true);
        if ($job_referral == 'indeed' && $indeed_detail_url != '') {
            $ouput = '<div class="view-more-link"><a class="btn btn-theme" href="' . $indeed_detail_url . '">' . esc_html__('view more', 'wp-job-board') . '</a></div>';
        }
        return $ouput;
    }
}

add_filter( 'cmb2_get_metabox_form_format', 'wp_job_board_indeed_modify_cmb2_form_output', 10, 3 );

function wp_job_board_indeed_modify_cmb2_form_output( $form_format, $object_id, $cmb ) {
    if ( 'wp_job_board_indeed_import' == $object_id && 'options_page' == $cmb->cmb_id ) {

        return '<form class="cmb-form" method="post" id="%1$s" enctype="multipart/form-data" encoding="multipart/form-data"><input type="hidden" name="object_id" value="%2$s">%3$s<div class="wp_job_board-submit-wrap"><input type="button" name="submit-cmb-indeed-job-import" value="' . __( 'Import Indeed Jobs', 'wp-job-board' ) . '" class="button-primary"></div></form>';
    }

    return $form_format;
}

WP_Job_Board_Indeed_Jobs_Hooks::init();