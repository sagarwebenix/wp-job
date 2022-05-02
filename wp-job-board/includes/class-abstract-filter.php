<?php
/**
 * Job Filter
 *
 * @package    wp-job-board
 * @author     Habq 
 * @license    GNU General Public License, version 3
 */

if ( ! defined( 'ABSPATH' ) ) {
  	exit;
}

class WP_Job_Board_Abstract_Filter {

	public static function has_filter() {
		if ( ! empty( $_GET ) && is_array( $_GET ) ) {
			foreach ( $_GET as $key => $value ) {
				if ( strrpos( $key, 'filter-', -strlen( $key ) ) !== false ) {
					return true;
				}
			}
		}
		return false;
	}

	public static function get_filters($params = null) {
		$filters = array();
		if ( empty($params) ) {
			if ( ! empty( $_GET ) && is_array( $_GET ) ) {
				$params = $_GET;
			}
		}
		if ( ! empty( $params ) && is_array( $params ) ) {
			foreach ( $params as $key => $value ) {
				if ( strrpos( $key, 'filter-', -strlen( $key ) ) !== false && !empty($value) ) {
					$filters[$key] = $value;
				}
			}
			if ( isset($filters['filter-salary-from']) && isset($filters['filter-salary-to']) ) {
				$filters['filter-salary'] = array($filters['filter-salary-from'], $filters['filter-salary-to'] );
				unset($filters['filter-salary-from']);
				unset($filters['filter-salary-to']);
			}
			if ( isset($filters['filter-center-latitude']) ) {
				unset($filters['filter-center-latitude']);
			}
			if ( isset($filters['filter-center-longitude']) ) {
				unset($filters['filter-center-longitude']);
			}
			if ( !empty($filters['filter-distance']) && !isset($filters['filter-center-location']) ) {
				unset($filters['filter-distance']);
			}
		}
		return $filters;
	}

	public static function date_posted_options() {
		return apply_filters( 'wp-job-board-date-posted-options', array(
			array(
				'value' => '1hour',
				'text'	=> __('Last Hour', 'wp-job-board'),
			),
			array(
				'value' => '24hours',
				'text'	=> __('Last 24 hours', 'wp-job-board'),
			),
			array(
				'value' => '7days',
				'text'	=> __('Last 7 days', 'wp-job-board'),
			),
			array(
				'value' => '14days',
				'text'	=> __('Last 14 days', 'wp-job-board'),
			),
			array(
				'value' => '30days',
				'text'	=> __('Last 30 days', 'wp-job-board'),
			),
			array(
				'value' => 'all',
				'text'	=> __('All', 'wp-job-board'),
			),
		) );
	}

	public static function orderby($query_vars, $params) {
		// Order
		if ( ! empty( $params['filter-orderby'] ) ) {
			switch ( $params['filter-orderby'] ) {
				case 'newest':
					$query_vars['orderby'] = 'date';
					$query_vars['order'] = 'DESC';
					break;
				case 'oldest':
					$query_vars['orderby'] = 'date';
					$query_vars['order'] = 'ASC';
					break;
				case 'random':
					$query_vars['orderby'] = 'rand';
					break;
				case 'title':
					$query_vars['orderby'] = 'title';
					break;
				case 'published':
					$query_vars['orderby'] = 'date';
					break;
				case 'price':
					$query_vars['meta_key'] = WP_JOB_BOARD_JOB_LISTING_PREFIX . 'price';
					$query_vars['orderby'] = 'meta_value_num';
					break;
			}
		} else {
			$query_vars['order'] = 'DESC';
			$query_vars['orderby'] = array(
				'menu_order' => 'ASC',
				'date'       => 'DESC',
				'ID'         => 'DESC',
			);
		}

		return $query_vars;
	}

	public static function build_post_ids( $haystack, array $ids ) {
		if ( ! is_array( $haystack ) ) {
			$haystack = array();
		}

		if ( is_array( $haystack ) && count( $haystack ) > 0 ) {
			return array_intersect( $haystack, $ids );
		} else {
			$haystack = $ids;
		}

		return $haystack;
	}
	
	public static function filter_by_date_posted($params) {
		if ( ! empty( $params['filter-date-posted'] ) ) {
            switch ($params['filter-date-posted']) {
            	case '1hour':
            		$lastdate = '-1 hour';
            		break;
            	case '24hours':
            		$lastdate = '-24 hours';
            		break;
            	case '7days':
            		$lastdate = '-7 days';
            		break;
        		case '14days':
        			$lastdate = '-14 days';
            		break;
        		case '30days':
        			$lastdate = '-30 days';
            		break;
            }

            if ( !empty($lastdate) ) {
            	return array(
                        'after'     => $lastdate,  
         				'inclusive' => true,
                    );
            }
	    }
	    return null;
	}

	public static function filter_by_distance($params, $post_stype) {
		$distance_ids = array();
		if ( ! empty( $params['filter-center-location'] ) && ! empty( $params['filter-center-latitude'] ) && ! empty( $params['filter-center-longitude'] ) ) {
			$filter_distance = 1;
			if ( ! empty( $params['filter-distance'] ) ) {
				$filter_distance = $params['filter-distance'];
			}
		    $post_ids = self::get_posts_by_distance( $params['filter-center-latitude'], $params['filter-center-longitude'], $filter_distance, $post_stype );

		    if ( $post_ids ) {
			    foreach ( $post_ids as $post ) {
					$distance_ids[] = $post->ID;
			    }
			}
			if ( empty( $distance_ids ) || ! $distance_ids ) {
	            $distance_ids = array(0);
			}
	    }
	    
	    return $distance_ids;
	}

	public static function get_posts_by_distance($latitude, $longitude, $distance, $post_stype = 'job_listing') {
		global $wpdb;
		$distance_type = apply_filters( 'wp_job_board_filter_distance_type', 'miles' );
		$earth_distance = $distance_type == 'miles' ? 3959 : 6371;

		$prefix = WP_JOB_BOARD_JOB_LISTING_PREFIX;
		switch ($post_stype) {
			case 'candidate':
				$prefix = WP_JOB_BOARD_CANDIDATE_PREFIX;
				break;
			case 'employer':
				$prefix = WP_JOB_BOARD_EMPLOYER_PREFIX;
				break;
			case 'job_listing':
			default:
				$prefix = WP_JOB_BOARD_JOB_LISTING_PREFIX;
				break;
		}
		$post_ids = false;
		$sql = $wpdb->prepare( "
			SELECT $wpdb->posts.ID, 
				( %s * acos( cos( radians(%s) ) * cos( radians( latmeta.meta_value ) ) * cos( radians( longmeta.meta_value ) - radians(%s) ) + sin( radians(%s) ) * sin( radians( latmeta.meta_value ) ) ) ) AS distance, latmeta.meta_value AS latitude, longmeta.meta_value AS longitude
			FROM $wpdb->posts
			INNER JOIN $wpdb->postmeta AS latmeta ON $wpdb->posts.ID = latmeta.post_id
			INNER JOIN $wpdb->postmeta AS longmeta ON $wpdb->posts.ID = longmeta.post_id
			WHERE $wpdb->posts.post_type = %s AND $wpdb->posts.post_status = 'publish' AND latmeta.meta_key=%s AND longmeta.meta_key=%s
			HAVING distance < %s
			ORDER BY $wpdb->posts.menu_order ASC, distance ASC",
			$earth_distance,
			$latitude,
			$longitude,
			$latitude,
			$post_stype,
			$prefix.'map_location_latitude',
			$prefix.'map_location_longitude',
			$distance
		);

		if ( apply_filters( 'wp_job_board_get_job_listings_cache_results', false ) ) {
			$to_hash         = json_encode( array($earth_distance, $latitude, $longitude, $latitude, $distance, $post_stype) );
			$query_args_hash = 'wp_job_board_' . md5( $to_hash . WP_JOB_BOARD_PLUGIN_VERSION );

			$post_ids = get_transient( $query_args_hash );
		}

		if ( ! $post_ids ) {
			$post_ids = $wpdb->get_results( $sql, OBJECT_K );
			if ( !empty($query_args_hash) ) {
				set_transient( $query_args_hash, $post_ids, DAY_IN_SECONDS );
			}
		}
		print_r($post_ids);
		exit();
		return $post_ids;

	}

	public static function filter_count($name, $term_id, $field) {
		$args = array(
			'post_type' => !empty($field['for_post_type']) ? $field['for_post_type'] : 'job_listing',
			'post_per_page' => 1,
			'fields' => 'ids'
		);
		$params = array();
		if ( WP_Job_Board_Abstract_Filter::has_filter() ) {
			$params = $_GET;
		}
		$params['filter-counter'] = array($name => $term_id);
		if ( !empty($params[$name]) ) {
			$values = $params[$name];
			if ( is_array($values) ) {
				$values[] = $term_id;
			} else {
				$values = $term_id;
			}
			$params[$name] = $values;
		} else {
			$params[$name] = $term_id;
		}

		$query_hash = md5( json_encode($args) ) .'-'. md5( json_encode($params) );

		$cached_counts = (array) get_transient( 'wp_job_board_filter_counts' );
		if ( ! isset( $cached_counts[ $query_hash ] ) ) {
			$loop = WP_Job_Board_Query::get_posts($args, $params);
			$cached_counts[ $query_hash ] = $loop->found_posts;
			set_transient( 'wp_job_board_filter_counts', $cached_counts, DAY_IN_SECONDS );
		}

		return $cached_counts[ $query_hash ];
	}
	
	public static function get_term_name($term_id, $tax) {
		$term = get_term($term_id, $tax);
		if ( $term ) {
			return $term->name;
		}
		return '';
	}

	public static function render_filter_tax($key, $value, $tax, $url) {
		if ( is_array($value) ) {
			foreach ($value as $val) {
				$name = self::get_term_name($val, $tax);
				$rm_url = self::remove_url_var($key . '[]=' . $val, $url);
				self::render_filter_result_item($name, $rm_url);
			}
		} else {
			$name = self::get_term_name($value, $tax);
			$rm_url = self::remove_url_var($key . '=' . $value, $url);
			self::render_filter_result_item($name, $rm_url);
		}
	}

	public static function remove_url_var($url_var, $url) {
		$str = "?" . $url_var;
		if ( strpos($url, $str) !== false ) {
		    $rm_url = str_replace($url_var, "", $url);
		    $rm_url = str_replace('?&', "?", $rm_url);
		} else {
			$rm_url = str_replace("&" . $url_var, "", $url);
		}
		return $rm_url;
	}

	public static function render_filter_result_item($value, $rm_url) {
		if ( $value ) {
		?>
			<li><a href="<?php echo esc_url($rm_url); ?>" ><span class="close-value">x</span><?php echo trim($value); ?></a></li>
			<?php
		}
	}

	public static function render_filter_tax_simple($key, $value, $tax, $label) {
		if ( is_array($value) ) {
			foreach ($value as $val) {
				$name = self::get_term_name($val, $tax);
				self::render_filter_result_item_simple($name, $label);
			}
		} else {
			$name = self::get_term_name($value, $tax);
			self::render_filter_result_item_simple($name, $label);
		}
	}

	public static function render_filter_result_item_simple($value, $label) {
		if ( $value ) {
		?>
			<li><strong class="text"><?php echo trim($label); ?>:</strong> <span class="value"><?php echo trim($value); ?></span></li>
			<?php
		}
	}

}
