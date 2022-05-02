<?php
/**
 * Candidate Filter
 *
 * @package    wp-job-board
 * @author     Habq 
 * @license    GNU General Public License, version 3
 */

if ( ! defined( 'ABSPATH' ) ) {
  	exit;
}

class WP_Job_Board_Candidate_Filter extends WP_Job_Board_Abstract_Filter {
	
	public static function init() {
		add_action( 'pre_get_posts', array( __CLASS__, 'archive' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'taxonomy' ) );
	}

	public static function get_fields() {
		return apply_filters( 'wp-job-board-default-candidate-filter-fields', array(
			'title'	=> array(
				'label' => __( 'Search Keywords', 'wp-job-board' ),
				'field_call_back' => array( 'WP_Job_Board_Mixes', 'filter_field_input'),
				'placeholder' => __( 'e.g. web design', 'wp-job-board' ),
				'toggle' => true,
				'for_post_type' => 'candidate',
			),
			'category' => array(
				'label' => __( 'Candidate Category', 'wp-job-board' ),
				'field_call_back' => array( 'WP_Job_Board_Mixes', 'filter_field_taxonomy_hierarchical_check_list'),
				'taxonomy' => 'candidate_category',
				'placeholder' => __( 'All Categories', 'wp-job-board' ),
				'toggle' => true,
				'for_post_type' => 'candidate',
			),
			'center-location' => array(
				'label' => __( 'Location', 'wp-job-board' ),
				'field_call_back' => array( 'WP_Job_Board_Mixes', 'filter_field_input_location'),
				'placeholder' => __( 'All Location', 'wp-job-board' ),
				'show_distance' => true,
				'toggle' => true,
				'for_post_type' => 'candidate',
			),
			'location' => array(
				'label' => __( 'Location List', 'wp-job-board' ),
				'field_call_back' => array( 'WP_Job_Board_Mixes', 'filter_field_taxonomy_hierarchical_check_list'),
				'taxonomy' => 'candidate_location',
				'placeholder' => __( 'All Locations', 'wp-job-board' ),
				'toggle' => true,
				'for_post_type' => 'candidate',
			),
			'date-posted' => array(
				'label' => __( 'Date Posted', 'wp-job-board' ),
				'field_call_back' => array( 'WP_Job_Board_Mixes', 'filter_field_input_date_posted'),
				'toggle' => true,
				'for_post_type' => 'candidate',
			),
			'salary' => array(
				'label' => __( 'Salary', 'wp-job-board' ),
				'field_call_back' => array( 'WP_Job_Board_Mixes', 'filter_field_candidate_salary'),
				'toggle' => true,
				'for_post_type' => 'candidate',
			),
			'featured' => array(
				'label' => __( 'Featured', 'wp-job-board' ),
				'field_call_back' => array( 'WP_Job_Board_Mixes', 'filter_field_checkbox'),
				'for_post_type' => 'candidate',
			),
			'urgent' => array(
				'label' => __( 'Urgent', 'wp-job-board' ),
				'field_call_back' => array( 'WP_Job_Board_Mixes', 'filter_field_checkbox'),
				'for_post_type' => 'candidate',
			),
		));
	}

	public static function archive($query) {
		$suppress_filters = ! empty( $query->query_vars['suppress_filters'] ) ? $query->query_vars['suppress_filters'] : '';

		if ( ! is_post_type_archive( 'candidate' ) || ! $query->is_main_query() || is_admin() || $query->query_vars['post_type'] != 'candidate' || $suppress_filters ) {
			return;
		}

		$limit = wp_job_board_get_option('number_candidates_per_page', 10);
		$query_vars = $query->query_vars;
		$query_vars['posts_per_page'] = $limit;
		$query->query_vars = $query_vars;

		return self::filter_query( $query );
	}

	public static function taxonomy($query) {
		$is_correct_taxonomy = false;
		if ( is_tax( 'candidate_category' ) || is_tax( 'candidate_location' ) || apply_filters( 'wp-job-board-candidate-query-taxonomy', false ) ) {
			$is_correct_taxonomy = true;
		}

		if ( ! $is_correct_taxonomy  || ! $query->is_main_query() || is_admin() ) {
			return;
		}

		$limit = wp_job_board_get_option('number_candidates_per_page', 10);
		$query_vars = $query->query_vars;
		$query_vars['posts_per_page'] = $limit;
		$query->query_vars = $query_vars;
		
		return self::filter_query( $query );
	}


	public static function filter_query( $query = null, $params = array() ) {
		global $wpdb, $wp_query;

		if ( empty( $query ) ) {
			$query = $wp_query;
		}

		if ( empty( $params ) ) {
			$params = $_GET;
		}

		// Filter params
		$params = apply_filters( 'wp_job_board_candidate_filter_params', $params );

		// Initialize variables
		$query_vars = $query->query_vars;
		$query_vars = self::get_query_var_filter($query_vars, $params);
		$query->query_vars = $query_vars;

		// Meta query
		$meta_query = self::get_meta_filter($params);
		if ( $meta_query ) {
			$query->set( 'meta_query', $meta_query );
		}

		// Tax query
		$tax_query = self::get_tax_filter($params);
		if ( $tax_query ) {
			$query->set( 'tax_query', $tax_query );
		}
	
		return apply_filters('wp-job-board-candidate-filter-query', $query, $params);
	}

	public static function get_query_var_filter($query_vars, $params) {
		/*print_r($query_vars);*/
		$ids = null;
		$query_vars = self::orderby($query_vars, $params);

		// Property title
		if ( ! empty( $params['filter-title'] ) ) {
			global $wp_job_board_candidate_keyword;
			$wp_job_board_candidate_keyword = $params['filter-title'];
			$query_vars['s'] = $params['filter-title'];
			add_filter( 'posts_search', array( __CLASS__, 'get_candidates_keyword_search' ) );
		}

		$distance_ids = self::filter_by_distance($params, 'candidate');
		if ( !empty($distance_ids) ) {
			$ids = self::build_post_ids( $ids, $distance_ids );
		}

		// Post IDs
		if ( is_array( $ids ) && count( $ids ) > 0 ) {
			$query_vars['post__in'] = $ids;
		}
		
		//date posted
		$date_query = self::filter_by_date_posted($params);
		if ( !empty($date_query) ) {
			$query_vars['date_query'] = $date_query;
		}

		//$query_vars['post_status'] = 'publish';

		return $query_vars;
	}

	public static function get_meta_filter($params) {
		$meta_query = array();
		if ( isset( $params['filter-salary-from'] ) && isset( $params['filter-salary-to'] ) ) {
			if ( intval($params['filter-salary-from']) >= 0 && intval($params['filter-salary-to']) > 0) {
				$meta_query[] = array(
		           	'key' => WP_JOB_BOARD_CANDIDATE_PREFIX . 'salary',
		           	'value' => array( intval($params['filter-salary-from']), intval($params['filter-salary-to']) ),
		           	'compare'   => 'BETWEEN',
					'type'      => 'NUMERIC',
		       	);
			}
			
    	}

    	if ( ! empty( $params['filter-salary-type'] ) ) {
			$meta_query[] = array(
				'key'       => WP_JOB_BOARD_CANDIDATE_PREFIX . 'salary_type',
				'value'     => $params['filter-salary-type'],
				'compare'   => '==',
			);
		}

		if ( ! empty( $params['filter-featured'] ) ) {
			$meta_query[] = array(
				'key'       => WP_JOB_BOARD_CANDIDATE_PREFIX . 'featured',
				'value'     => 'on',
				'compare'   => '==',
			);
		}

		if ( ! empty( $params['filter-urgent'] ) ) {
			$meta_query[] = array(
				'key'       => WP_JOB_BOARD_CANDIDATE_PREFIX . 'urgent',
				'value'     => 'on',
				'compare'   => '==',
			);
		}

		return $meta_query;
	}

	public static function get_tax_filter($params) {
		$tax_query = array();
		if ( ! empty( $params['filter-category'] ) ) {
			if ( is_array($params['filter-category']) ) {
				$tax_query[] = array(
					'taxonomy'  => 'candidate_category',
					'field'     => 'id',
					'terms'     => $params['filter-category'],
					'compare'   => 'IN',
				);
			} else {
				$tax_query[] = array(
					'taxonomy'  => 'candidate_category',
					'field'     => 'id',
					'terms'     => $params['filter-category'],
					'compare'   => '==',
				);
			}
		}

		if ( ! empty( $params['filter-location'] ) ) {
			if ( is_array($params['filter-location']) ) {
				$tax_query[] = array(
					'taxonomy'  => 'candidate_location',
					'field'     => 'id',
					'terms'     => $params['filter-location'],
					'compare'   => 'IN',
				);
			} else {
				$tax_query[] = array(
					'taxonomy'  => 'candidate_location',
					'field'     => 'id',
					'terms'     => $params['filter-location'],
					'compare'   => '==',
				);
			}
		}

		return $tax_query;
	}

	public static function get_candidates_keyword_search( $search ) {
		global $wpdb, $wp_job_board_candidate_keyword;

		// Searchable Meta Keys: set to empty to search all meta keys.
		$searchable_meta_keys = array(
			WP_JOB_BOARD_CANDIDATE_PREFIX.'address',
			WP_JOB_BOARD_CANDIDATE_PREFIX.'phone',
			WP_JOB_BOARD_CANDIDATE_PREFIX.'job_title',
		);

		$searchable_meta_keys = apply_filters( 'wp_job_board_searchable_meta_keys', $searchable_meta_keys );

		// Set Search DB Conditions.
		$conditions = array();

		// Search Post Meta.
		if ( apply_filters( 'wp_job_board_search_post_meta', true ) ) {

			// Only selected meta keys.
			if ( $searchable_meta_keys ) {
				$conditions[] = "{$wpdb->posts}.ID IN ( SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key IN ( '" . implode( "','", array_map( 'esc_sql', $searchable_meta_keys ) ) . "' ) AND meta_value LIKE '%" . esc_sql( $wp_job_board_candidate_keyword ) . "%' )";
			} else {
				// No meta keys defined, search all post meta value.
				$conditions[] = "{$wpdb->posts}.ID IN ( SELECT post_id FROM {$wpdb->postmeta} WHERE meta_value LIKE '%" . esc_sql( $wp_job_board_candidate_keyword ) . "%' )";
			}
		}

		// Search taxonomy.
		$conditions[] = "{$wpdb->posts}.ID IN ( SELECT object_id FROM {$wpdb->term_relationships} AS tr LEFT JOIN {$wpdb->term_taxonomy} AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id LEFT JOIN {$wpdb->terms} AS t ON tt.term_id = t.term_id WHERE t.name LIKE '%" . esc_sql( $wp_job_board_candidate_keyword ) . "%' )";

		$conditions = apply_filters( 'wp_job_board_search_conditions', $conditions, $wp_job_board_candidate_keyword );
		if ( empty( $conditions ) ) {
			return $search;
		}

		$conditions_str = implode( ' OR ', $conditions );

		if ( ! empty( $search ) ) {
			$search = preg_replace( '/^ AND /', '', $search );
			$search = " AND ( {$search} OR ( {$conditions_str} ) )";
		} else {
			$search = " AND ( {$conditions_str} )";
		}
		remove_filter( 'posts_search', array( __CLASS__, 'get_candidates_keyword_search' ) );
		return $search;
	}

	public static function display_filter_value($key, $value, $filters) {
		$url = urldecode(WP_Job_Board_Mixes::get_full_current_url());
		
		switch ($key) {
			case 'filter-category':
				self::render_filter_tax($key, $value, 'candidate_category', $url);
				break;
			case 'filter-location':
				self::render_filter_tax($key, $value, 'candidate_location', $url);
				break;
			case 'filter-salary':
				if ( isset($value[0]) && isset($value[1]) ) {
					$from = WP_Job_Board_Price::format_price($value[0]);
					$to = WP_Job_Board_Price::format_price($value[1]);
					$rm_url = self::remove_url_var($key . '-from=' . $value[0], $url);
					$rm_url = self::remove_url_var($key . '-to=' . $value[1], $rm_url);
					self::render_filter_result_item( $from.' - '.$to, $rm_url );
				}
				break;
			case 'filter-salary-type':
				$types = WP_Job_Board_Mixes::get_default_salary_types();
				$title = $value;
				if ( in_array($value, $types) ) {
					$title = $types[$value];
				}
				$rm_url = self::remove_url_var(  $key . '=' . $value, $url);
				self::render_filter_result_item( $title, $rm_url );
				break;
			case 'filter-date-posted':
				$options = self::date_posted_options();
				foreach ($options as $option) {
					if ( !empty($option['value']) && $option['value'] == $value ) {
						$title = $option['text'];
						$rm_url = self::remove_url_var(  $key . '=' . $value, $url);
						self::render_filter_result_item( $title, $rm_url );
						break;
					}
				}
				break;
			case 'filter-distance':
				if ( !empty($filters['filter-center-location']) ) {
					$distance_type = apply_filters( 'wp_job_board_filter_distance_type', 'miles' );
					$title = $value.' '.$distance_type;
					$rm_url = self::remove_url_var(  $key . '=' . $value, $url);
					self::render_filter_result_item( $title, $rm_url );
				}
				break;
			case 'filter-orderby':
				$orderby_options = apply_filters( 'wp-job-board-jobs-orderby', array(
					'menu_order' => esc_html__('Default', 'wp-job-board'),
					'newest' => esc_html__('Newest', 'wp-job-board'),
					'oldest' => esc_html__('Oldest', 'wp-job-board'),
					'random' => esc_html__('Random', 'wp-job-board'),
				));
				$title = $value;
				if ( !empty($orderby_options[$value]) ) {
					$title = $orderby_options[$value];
				}
				$rm_url = self::remove_url_var(  $key . '=' . $value, $url);
				self::render_filter_result_item( $title, $rm_url );
				break;
			case 'filter-featured':
				$title = esc_html__('Featured', 'wp-job-board');
				$rm_url = self::remove_url_var(  $key . '=' . $value, $url);
				self::render_filter_result_item( $title, $rm_url );
				break;
			case 'filter-urgent':
				$title = esc_html__('Urgent', 'wp-job-board');
				$rm_url = self::remove_url_var(  $key . '=' . $value, $url);
				self::render_filter_result_item( $title, $rm_url );
				break;
			default:
				if ( is_array($value) ) {
					foreach ($value as $val) {
						$rm_url = self::remove_url_var( $key . '[]=' . $val, $url);
						self::render_filter_result_item( $val, $rm_url);
					}
				} else {
					$rm_url = self::remove_url_var( $key . '=' . $value, $url);
					self::render_filter_result_item( $value, $rm_url);
				}
				break;
		}
	}

	public static function display_filter_value_simple($key, $value, $filters) {
		
		switch ($key) {
			case 'filter-category':
				self::render_filter_tax_simple($key, $value, 'candidate_category', esc_html__('Category', 'wp-job-board'));
				break;
			case 'filter-location':
				self::render_filter_tax_simple($key, $value, 'candidate_location', esc_html__('Location', 'wp-job-board'));
				break;
			case 'filter-salary':
				if ( isset($value[0]) && isset($value[1]) ) {
					$from = WP_Job_Board_Price::format_price($value[0]);
					$to = WP_Job_Board_Price::format_price($value[1]);
					self::render_filter_result_item_simple( $from.' - '.$to, esc_html__('Salary', 'wp-job-board') );
				}
				break;
			case 'filter-salary-type':
				$types = WP_Job_Board_Mixes::get_default_salary_types();
				$title = $value;
				if ( in_array($value, $types) ) {
					$title = $types[$value];
				}
				self::render_filter_result_item_simple( $title, esc_html__('Salary Type', 'wp-job-board') );
				break;
			case 'filter-date-posted':
				$options = self::date_posted_options();
				foreach ($options as $option) {
					if ( !empty($option['value']) && $option['value'] == $value ) {
						$title = $option['text'];
						self::render_filter_result_item_simple( $title, esc_html__('Posted Date', 'wp-job-board') );
						break;
					}
				}
				break;
			case 'filter-distance':
				if ( !empty($filters['filter-center-location']) ) {
					$distance_type = apply_filters( 'wp_job_board_filter_distance_type', 'miles' );
					$title = $value.' '.$distance_type;
					self::render_filter_result_item_simple( $title, esc_html__('Distance', 'wp-job-board') );
				}
				break;
			case 'filter-orderby':
				$orderby_options = apply_filters( 'wp-job-board-jobs-orderby', array(
					'menu_order' => esc_html__('Default', 'wp-job-board'),
					'newest' => esc_html__('Newest', 'wp-job-board'),
					'oldest' => esc_html__('Oldest', 'wp-job-board'),
					'random' => esc_html__('Random', 'wp-job-board'),
				));
				$title = $value;
				if ( !empty($orderby_options[$value]) ) {
					$title = $orderby_options[$value];
				}
				self::render_filter_result_item_simple( $title, esc_html__('Orderby', 'wp-job-board') );
				break;
			case 'filter-featured':
				$title = esc_html__('Yes', 'wp-job-board');
				self::render_filter_result_item_simple( $title, esc_html__('Featured', 'wp-job-board') );
				break;
			case 'filter-urgent':
				$title = esc_html__('Yes', 'wp-job-board');
				self::render_filter_result_item_simple( $title, esc_html__('Urgent', 'wp-job-board') );
				break;
			default:
				$label = str_replace('filter-custom-', '', $key);
				$label = str_replace('filter-', '', $label);
				$label = str_replace('-', ' ', $label);
				if ( is_array($value) ) {
					foreach ($value as $val) {
						self::render_filter_result_item_simple( $val, $label );
					}
				} else {
					self::render_filter_result_item_simple( $value, $label );
				}
				break;
		}
	}
}

WP_Job_Board_Candidate_Filter::init();