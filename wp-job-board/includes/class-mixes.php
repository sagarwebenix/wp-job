<?php
/**
 * Mixes
 *
 * @package    wp-job-board
 * @author     Habq 
 * @license    GNU General Public License, version 3
 */

if ( ! defined( 'ABSPATH' ) ) {
  	exit;
}

class WP_Job_Board_Mixes {
	
	public static function init() {
		add_action( 'wp_head', array( __CLASS__, 'track_post_views' ) );

		add_action( 'login_form', array( __CLASS__, 'social_login_before' ), 1 );
		add_action( 'login_form', array( __CLASS__, 'social_login_after' ), 30 );

		add_filter( 'wp_job_board_filter_distance_type', array( __CLASS__, 'set_distance_type' ), 10 );

		add_filter( 'wp_job_board_cmb2_field_taxonomy_location_number', array( __CLASS__, 'set_location_number' ), 10 );
		add_filter( 'wp_job_board_cmb2_field_taxonomy_location_field_name_1', array( __CLASS__, 'set_first_location_label' ), 10 );
		add_filter( 'wp_job_board_cmb2_field_taxonomy_location_field_name_2', array( __CLASS__, 'set_second_location_label' ), 10 );
		add_filter( 'wp_job_board_cmb2_field_taxonomy_location_field_name_3', array( __CLASS__, 'set_third_location_label' ), 10 );
		add_filter( 'wp_job_board_cmb2_field_taxonomy_location_field_name_4', array( __CLASS__, 'set_fourth_location_label' ), 10 );
	}

	public static function set_post_views($post_id, $prefix) {
	    $count_key = $prefix.'views_count';
	    $count = get_post_meta($post_id, $count_key, true);
	    if ( $count == '' ) {
	        $count = 0;
	        delete_post_meta($post_id, $count_key);
	        add_post_meta($post_id, $count_key, '0');
	    } else {
	        $count++;
	        update_post_meta($post_id, $count_key, $count);
	    }
	}

	public static function track_post_views() {
	    if ( is_singular('job_listing') || is_singular('employer') || is_singular('candidate') ) {
	        global $post;
	        $post_id = $post->ID;
	        $prefix = WP_JOB_BOARD_JOB_LISTING_PREFIX;
	        if ( is_singular('employer') ) {
	        	$prefix = WP_JOB_BOARD_EMPLOYER_PREFIX;
	        } elseif ( is_singular('candidate') ) {
	        	$prefix = WP_JOB_BOARD_CANDIDATE_PREFIX;
	        }
		    self::set_post_views($post_id, $prefix);
		}
	}
	
	/**
	 * Formats number by currency settings
	 *
	 * @access public
	 * @param $price
	 * @return bool|string
	 */
	public static function format_number( $price ) {
		if ( empty( $price ) || ! is_numeric( $price ) ) {
			return false;
		}
		
		$money_decimals = wp_job_board_get_option('money_decimals');
		$money_thousands_separator = wp_job_board_get_option('money_thousands_separator');
		$money_dec_point = wp_job_board_get_option('money_dec_point');

		$price_parts_dot = explode( '.', $price );
		$price_parts_col = explode( ',', $price );

		if ( count( $price_parts_dot ) > 1 || count( $price_parts_col ) > 1 ) {
			$decimals = ! empty( $money_decimals ) ? $money_decimals : '0';
		} else {
			$decimals = 0;
		}

		$dec_point = ! empty( $money_dec_point ) ? $money_dec_point : '.';
		$thousands_separator = ! empty( $money_thousands_separator ) ? $money_thousands_separator : '';

		$price = number_format( $price, $decimals, $dec_point, $thousands_separator );

		return $price;
	}

	public static function is_allowed_to_remove( $user_id, $item_id ) {
		$item = get_post( $item_id );

		if ( ! empty( $item->post_author ) ) {
			return $item->post_author == $user_id ;
		}

		return false;
	}
	
	public static function redirect($redirect_url) {
		if ( ! $redirect_url ) {
			$redirect_url = home_url( '/' );
		}

		wp_redirect( $redirect_url );
		exit();
	}

	public static function sort_array_by_priority( $a, $b ) {
		if ( $a['priority'] == $b['priority'] ) {
			return 0;
		}

		return ( $a['priority'] < $b['priority'] ) ? - 1 : 1;
	}

	public static function filter_field_input($instance, $args, $key, $field) {
		$name = 'filter-'.$key;
		$selected = !empty( $_GET[$name] ) ? $_GET[$name] : '';

		include WP_Job_Board_Template_Loader::locate( 'widgets/filter-fields/text' );
	}

	public static function filter_field_input_location($instance, $args, $key, $field) {
		$name = 'filter-'.$key;
		$selected = !empty( $_GET[$name] ) ? $_GET[$name] : '';

		include WP_Job_Board_Template_Loader::locate( 'widgets/filter-fields/text_location' );
	}

	public static function filter_field_input_date_posted($instance, $args, $key, $field) {
		$name = 'filter-'.$key;
		$selected = !empty( $_GET[$name] ) ? $_GET[$name] : 'all';
		$options = WP_Job_Board_Abstract_Filter::date_posted_options();
		
		include WP_Job_Board_Template_Loader::locate( 'widgets/filter-fields/radios' );
	}

	public static function filter_field_found_date_range_slider($instance, $args, $key, $field) {
		$name = 'filter-'.$key;
		$selected = !empty( $_GET[$name] ) ? $_GET[$name] : '';
		$min_max = WP_Job_Board_Query::get_min_max_meta_value(WP_JOB_BOARD_EMPLOYER_PREFIX.'founded_date', 'employer');
		if ( empty($min_max) ) {
			return;
		}
		$min    = floor( $min_max->min );
		$max    = ceil( $min_max->max );

		if ( $min == $max ) {
			return;
		}
		include WP_Job_Board_Template_Loader::locate( 'widgets/filter-fields/range_slider' );
	}

	public static function filter_field_job_salary($instance, $args, $key, $field) {
		$name = 'filter-'.$key;
		$selected = !empty( $_GET[$name] ) ? $_GET[$name] : '';

		$condition = array();
		if ( !empty($_GET['filter-salary-type']) ) {
			$condition = array(
				'key' => WP_JOB_BOARD_JOB_LISTING_PREFIX.'salary_type',
				'value' => $_GET['filter-salary-type']
			);
		}

		$salary_min = WP_Job_Board_Query::get_min_max_meta_value(WP_JOB_BOARD_JOB_LISTING_PREFIX.'salary', 'job_listing', $condition);
		$salary_max = WP_Job_Board_Query::get_min_max_meta_value(WP_JOB_BOARD_JOB_LISTING_PREFIX.'max_salary', 'job_listing', $condition);
		if ( empty($salary_min) && empty($salary_max) ) {
			return;
		}
		$min = $max = 0;
		$min = $salary_min->min < $salary_max->min ? $salary_min->min : $salary_max->min;
		$max = $salary_min->max > $salary_max->max ? $salary_min->max : $salary_max->max;
		
		if ( $min >= $max ) {
			return;
		}
		include WP_Job_Board_Template_Loader::locate( 'widgets/filter-fields/salary_range_slider' );
	}

	public static function filter_field_candidate_salary($instance, $args, $key, $field) {
		$name = 'filter-'.$key;
		$selected = !empty( $_GET[$name] ) ? $_GET[$name] : '';

		$condition = array();
		if ( !empty($_GET['filter-salary-type']) ) {
			$condition = array(
				'key' => WP_JOB_BOARD_CANDIDATE_PREFIX.'salary_type',
				'value' => $_GET['filter-salary-type']
			);
		}

		$min_max = WP_Job_Board_Query::get_min_max_meta_value(WP_JOB_BOARD_CANDIDATE_PREFIX.'salary', 'candidate', $condition);
		
		if ( empty($min_max) ) {
			return;
		}
		$min    = floor( $min_max->min );
		$max    = ceil( $min_max->max );
		
		if ( $min >= $max ) {
			return;
		}
		include WP_Job_Board_Template_Loader::locate( 'widgets/filter-fields/salary_range_slider' );
	}

	public static function filter_field_checkbox($instance, $args, $key, $field) {
		$name = 'filter-'.$key;
		$selected = !empty( $_GET[$name] ) ? $_GET[$name] : '';

		include WP_Job_Board_Template_Loader::locate( 'widgets/filter-fields/checkbox' );
	}

	public static function filter_field_taxonomy_radio_list($instance, $args, $key, $field) {
		$name = 'filter-'.$key;
		$selected = !empty( $_GET[$name] ) ? $_GET[$name] : '';

		$options = array();
		$query_args = apply_filters('wp-job-board-filter-field-taxonomy-radio-list-query-args', array( 'hierarchical' => 1, 'hide_empty' => true  ), $field);
		$terms = get_terms($field['taxonomy'], $query_args);

		if ( ! is_wp_error( $terms ) && ! empty ( $terms ) ) {
			foreach ($terms as $term) {
				$options[] = array(
					'value' => $term->term_id,
					'text' => $term->name,
					'count' => WP_Job_Board_Abstract_Filter::filter_count($name, $term->term_id, $field)
				);
			}
		}
		include WP_Job_Board_Template_Loader::locate( 'widgets/filter-fields/radios' );
	}

	public static function filter_field_taxonomy_check_list($instance, $args, $key, $field) {
		$name = 'filter-'.$key;
		$selected = !empty( $_GET[$name] ) ? $_GET[$name] : '';

		$options = array();
		$query_args = apply_filters('wp-job-board-filter-field-taxonomy-check-list-query-args', array( 'hierarchical' => 1, 'hide_empty' => true  ), $field);
		$terms = get_terms($field['taxonomy'], $query_args);

		if ( ! is_wp_error( $terms ) && ! empty ( $terms ) ) {
			foreach ($terms as $term) {
				$options[] = array(
					'value' => $term->term_id,
					'text' => $term->name,
					'count' => WP_Job_Board_Abstract_Filter::filter_count($name, $term->term_id, $field)
				);
			}
		}
		include WP_Job_Board_Template_Loader::locate( 'widgets/filter-fields/check_list' );
	}

	public static function filter_field_taxonomy_select($instance, $args, $key, $field) {
		$name = 'filter-'.$key;
		$selected = !empty( $_GET[$name] ) ? $_GET[$name] : '';

		$options = array();
		$query_args = apply_filters('wp-job-board-filter-field-taxonomy-taxonomy-select-query-args', array( 'hierarchical' => 1, 'hide_empty' => true  ), $field);
		$terms = get_terms($field['taxonomy'], $query_args);

		if ( ! is_wp_error( $terms ) && ! empty ( $terms ) ) {
			foreach ($terms as $term) {
				$options[] = array(
					'value' => $term->term_id,
					'text' => $term->name,
					'count' => WP_Job_Board_Abstract_Filter::filter_count($name, $term->term_id, $field)
				);
			}
		}
		include WP_Job_Board_Template_Loader::locate( 'widgets/filter-fields/select' );
	}

	public static function filter_field_taxonomy_hierarchical_radio_list($instance, $args, $key, $field) {
		$name = 'filter-'.$key;
		$selected = !empty( $_GET[$name] ) ? $_GET[$name] : '';

		include WP_Job_Board_Template_Loader::locate( 'widgets/filter-fields/tax_radios' );
	}

	public static function filter_field_taxonomy_hierarchical_check_list($instance, $args, $key, $field) {
		$name = 'filter-'.$key;
		$selected = !empty( $_GET[$name] ) ? $_GET[$name] : '';

		include WP_Job_Board_Template_Loader::locate( 'widgets/filter-fields/tax_check_list' );
	}

	public static function filter_field_taxonomy_hierarchical_select($instance, $args, $key, $field) {
		$name = 'filter-'.$key;
		$selected = !empty( $_GET[$name] ) ? $_GET[$name] : '';

		include WP_Job_Board_Template_Loader::locate( 'widgets/filter-fields/tax_select' );
	}

	public static function hierarchical_tax_tree($catId = 0, $depth = 0, $input_name, $key, $field, $selected, $input_type = 'checkbox'){
	    $output = $return = '';
	    $next_depth = $depth + 1;
	    if ( empty($field['taxonomy']) ) {
	    	return;
	    }

	    $query_args = apply_filters('wp-job-board-filter-field-taxonomy-tax-tree-query-args', array( 'hierarchical' => 1, 'hide_empty' => true  ), $field);
	    $query_args['parent'] = $catId;
		$terms = get_terms($field['taxonomy'], $query_args);
 
	    if ( ! is_wp_error( $terms ) && ! empty ( $terms ) ) {
	        foreach ($terms as $term) {
	            $checked = '';
	        	if ( !empty($selected) ) {
		            if ( is_array($selected) ) {
		                if ( in_array($term->term_id, $selected) ) {
		                    $checked = ' checked="checked"';
		                }
		            } elseif ( $term->term_id == $selected ) {
		                $checked = ' checked="checked"';
		            }
		        }
		        $count = WP_Job_Board_Abstract_Filter::filter_count($input_name, $term->term_id, $field);
	         	if ( $count <= 0 ) {
		            continue;
		        }
		        $output .= '<li class="list-item">';
		        	$output .= '<div class="list-item-inner">';
		        	if ( $input_type == 'checkbox' ) {
			        	$output .= '<input id="'.esc_attr($term->name).'" type="checkbox" name="'.esc_attr($input_name).'[]" value="'.esc_attr($term->term_id).'" '.$checked.'>';
			        } else {
			        	$output .= '<input id="'.esc_attr($term->name).'" type="radio" name="'.esc_attr($input_name).'" value="'.esc_attr($term->term_id).'" '.$checked.'>';
			        }
		        	$output .= '<label for="'.esc_attr($term->name).'">'.wp_kses_post($term->name).'</label>';

		            if ( isset($count) ) {
		                $output .= '<span class="count">'.sprintf(__('(%d)', 'wp-job-board'), $count).'</span>';
		            }
		        	
		        	$child_output = self::hierarchical_tax_tree($term->term_id, $next_depth, $input_name, $key, $field, $selected, $input_type);
		        	if ( $child_output ) {
		        		$output .= '<span class="caret-wrapper"><span class="caret"></span></span>';
		        	}
		        	$output .= '</div>';

	            	$output .= $child_output;

	            $output .= '</li>';
	        }
	        if ( $output ) {
	        	$return = '<ul class="terms-list circle-check level-'.$depth.'">'.$output.'</ul>';
	        }
	    }

	    return $return;
	}

	public static function hierarchical_tax_option_tree($catId = 0, $depth = 0, $input_name, $key, $field, $selected ){
	    $output = $show_depth = '';
	    $next_depth = $depth + 1;
	    for ($i = 1; $i <= $depth; $i++) {
		    $show_depth .= '-';
		}
	    if ( empty($field['taxonomy']) ) {
	    	return;
	    }

	    $query_args = apply_filters('wp-job-board-filter-field-taxonomy-option-tree-query-args', array( 'hierarchical' => 1, 'hide_empty' => true  ), $field);
	    $query_args['parent'] = $catId;
		$terms = get_terms($field['taxonomy'], $query_args);
 
	    if ( ! is_wp_error( $terms ) && ! empty ( $terms ) ) {
	        foreach ($terms as $term) {
	            $checked = '';
	        	if ( !empty($selected) ) {
		            if ( is_array($selected) ) {
		                if ( in_array($term->term_id, $selected) ) {
		                    $checked = ' checked="checked"';
		                }
		            } elseif ( $term->term_id == $selected ) {
		                $checked = ' checked="checked"';
		            }
		        }
		        $count = WP_Job_Board_Abstract_Filter::filter_count($input_name, $term->term_id, $field);
	         	if ( $count <= 0 ) {
		            continue;
		        }
		        $output .= '<option value="'.esc_attr($term->term_id).'" '.selected($selected, $term->term_id).'>';
		        	
		        	$output .= $show_depth.' '.wp_kses_post($term->name);

		            if ( isset($count) ) {
		                $output .= sprintf(__('(%d)', 'wp-job-board'), $count);
		            }
		        	
	            $output .= '</option>';

	            $output .= self::hierarchical_tax_option_tree($term->term_id, $next_depth, $input_name, $key, $field, $selected);
	        }
	    }

	    return $output;
	}

	public static function filter_field_employers($instance, $args, $key, $field) {
		$name = 'filter-'.$key;
		$selected = !empty( $_GET[$name] ) ? $_GET[$name] : '';

		$options = array();
		$employer_ids = WP_Job_Board_User::get_author_employers();

		if ( ! empty ( $employer_ids ) ) {
			foreach ($employer_ids as $user_id) {
				$employer_id = WP_Job_Board_User::get_employer_by_user_id($user_id);

				if ( $employer_id ) {
					$options[] = array(
						'value' => $user_id,
						'text' => get_the_title($employer_id),
						'count' => WP_Job_Board_Abstract_Filter::filter_count($name, $user_id, $field)
					);
				}
			}
		}
		include WP_Job_Board_Template_Loader::locate( 'widgets/filter-fields/check_list' );
	}

	public static function get_default_salary_types() {
		return apply_filters( 'wp-job-board-get-default-salary-types', array(
			'monthly' => __( 'Monthly', 'wp-job-board' ),
			'weekly' => __( 'Weekly', 'wp-job-board' ),
			'hourly' => __( 'Hourly', 'wp-job-board' ),
			'yearly' => __( 'Yearly', 'wp-job-board' ),
		));
	}

	public static function get_image_mime_types() {
		return apply_filters( 'wp-job-board-get-image-mime-types', array(
			'jpg'         => 'image/jpeg',
			'jpeg'        => 'image/jpeg',
			'jpe'         => 'image/jpeg',
			'gif'         => 'image/gif',
			'png'         => 'image/png',
			'bmp'         => 'image/bmp',
			'tif|tiff'    => 'image/tiff',
			'ico'         => 'image/x-icon',
		));
	}

	public static function get_cv_mime_types() {
		return apply_filters( 'wp-job-board-get-cv-mime-types', array(
			'txt'         => 'text/plain',
			'doc'         => 'application/msword',
			'docx'        => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			'xlsx'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
			'xls'         => 'application/vnd.ms-excel',
			'pdf'         => 'application/pdf',
		));
	}

	public static function get_socials_network() {
		return apply_filters( 'wp-job-board-get-socials-network', array(
			'facebook' => esc_html__('Facebook', 'wp-job-board'),
			'twitter' => esc_html__('Twitter', 'wp-job-board'),
			'linkedin' => esc_html__('Linkedin', 'wp-job-board'),
			'dribbble' => esc_html__('Dribbble', 'wp-job-board'),
		));
	}

	public static function get_jobs_page_url() {
		if ( is_post_type_archive('job_listing') ) {
			$url = get_post_type_archive_link( 'job_listing' );
		} else {
			global $post;
			if ( is_page() && is_object($post) && basename( get_page_template() ) == 'page-jobs.php' ) {
				$url = get_permalink($post->ID);
			} else {
				$jobs_page_id = wp_job_board_get_option('jobs_page_id');
				if ( $jobs_page_id ) {
					$url = get_permalink($jobs_page_id);
				} else {
					$url = get_post_type_archive_link( 'job_listing' );
				}
			}
		}
		return apply_filters( 'wp-job-board-get-jobs-page-url', $url );
	}

	public static function get_employers_page_url() {
		if ( is_post_type_archive('employer') ) {
			$url = get_post_type_archive_link( 'employer' );
		} else {
			global $post;
			if ( is_page() && is_object($post) && basename( get_page_template() ) == 'page-employers.php' ) {
				$url = get_permalink($post->ID);
			} else {
				$employers_page_id = wp_job_board_get_option('employers_page_id');
				if ( $employers_page_id ) {
					$url = get_permalink($employers_page_id);
				} else {
					$url = get_post_type_archive_link( 'employer' );
				}
			}
		}
		return apply_filters( 'wp-job-board-get-employers-page-url', $url );
	}

	public static function get_candidates_page_url() {
		if ( is_post_type_archive('candidate') ) {
			$url = get_post_type_archive_link( 'candidate' );
		} else {
			global $post;
			if ( is_page() && is_object($post) && basename( get_page_template() ) == 'page-candidates.php' ) {
				$url = get_permalink($post->ID);
			} else {
				$candidates_page_id = wp_job_board_get_option('candidates_page_id');
				if ( $candidates_page_id ) {
					$url = get_permalink($candidates_page_id);
				} else {
					$url = get_post_type_archive_link( 'candidate' );
				}
			}
		}
		return apply_filters( 'wp-job-board-get-candidates-page-url', $url );
	}

	public static function custom_pagination( $args = array() ) {
    	global $wp_rewrite;
        
        $args = wp_parse_args( $args, array(
			'prev_text' => '<i class="flaticon-left-arrow"></i>'.esc_html__('Prev', 'wp-job-board'),
			'next_text' => esc_html__('Next','wp-job-board').'<i class="flaticon-right-arrow"></i>',
			'max_num_pages' => 10,
			'echo' => true,
			'class' => '',
		));

        if ( !empty($args['wp_query']) ) {
        	$wp_query = $args['wp_query'];
        } else {
        	global $wp_query;
        }

        if ( $wp_query->max_num_pages < 2 ) {
			return;
		}

    	$pages = $args['max_num_pages'];

    	$current = !empty($wp_query->query_vars['paged']) && $wp_query->query_vars['paged'] > 1 ? $wp_query->query_vars['paged'] : 1;
        if ( empty($pages) ) {
            global $wp_query;
            $pages = $wp_query->max_num_pages;
            if ( !$pages ) {
                $pages = 1;
            }
        }
        $pagination = array(
            'base' => @add_query_arg('paged','%#%'),
            'format' => '',
            'total' => $pages,
            'current' => $current,
            'prev_text' => $args['prev_text'],
            'next_text' => $args['next_text'],
            'type' => 'array'
        );

		$pagenum_link = html_entity_decode( get_pagenum_link() );
		$query_args   = array();
		$url_parts    = explode( '?', $pagenum_link );

		if ( isset( $url_parts[1] ) ) {
			wp_parse_str( $url_parts[1], $query_args );
		}

		$pagenum_link = remove_query_arg( array_keys( $query_args ), $pagenum_link );
		$pagenum_link = trailingslashit( $pagenum_link ) . '%_%';

		$format  = $wp_rewrite->using_index_permalinks() && ! strpos( $pagenum_link, 'index.php' ) ? 'index.php/' : '';
		$format .= $wp_rewrite->using_permalinks() ? user_trailingslashit( $wp_rewrite->pagination_base . '/%#%', 'paged' ) : '?paged=%#%';

		$add_args = array();
		if ( !empty($query_args) ) {
			foreach ($query_args as $key => $value) {
				if ( is_array($value) ) {
					$add_args[$key] = array_map( 'urlencode', $value );
				} else {
					$add_args[$key] = $value;
				}
			}
		}

		$pagination['base'] = $pagenum_link;
		$pagination['format'] = $format;
		$pagination['add_args'] = $add_args;
        

        $sq = '';
        if ( isset($_GET['s']) ) {
            $cq = $_GET['s'];
            $sq = str_replace(" ", "+", $cq);
        }
        
        if ( !empty($wp_query->query_vars['s']) ) {
            $pagination['add_args'] = array( 's' => $sq);
        }
        $pagination = apply_filters( 'wp-job-board-custom-pagination', $pagination );

        $paginations = paginate_links( $pagination );
        $output = '';
        if ( !empty($paginations) ) {
            $output .= '<ul class="pagination '.esc_attr( $args["class"] ).'">';
                foreach ($paginations as $key => $pg) {
                    $output .= '<li>'. $pg .'</li>';
                }
            $output .= '</ul>';
        }
    	
        if ( $args["echo"] ) {
        	echo wp_kses_post($output);
        } else {
        	return $output;
        }
    }

    public static function custom_pagination2( $args = array() ) {
    	global $wp_rewrite;
        
        $args = wp_parse_args( $args, array(
			'prev_text' => '<i class="flaticon-left-arrow"></i>'.esc_html__('Prev', 'wp-job-board'),
			'next_text' => esc_html__('Next','wp-job-board').'<i class="flaticon-right-arrow"></i>',
			'echo' => true,
			'class' => '',
			'per_page' => '',
			'max_num_pages' => '',
			'current' => '',
		));

        if ( $args['max_num_pages'] < 2 ) {
			return;
		}

    	$pages = $args['max_num_pages'];

    	$current = !empty($args['current']) && $args['current'] > 1 ? $args['current'] : 1;
        
        $pagination = array(
            'base' => @add_query_arg('paged','%#%'),
            'format' => '',
            'total' => $pages,
            'current' => $current,
            'prev_text' => $args['prev_text'],
            'next_text' => $args['next_text'],
            'type' => 'array'
        );

		$pagenum_link = html_entity_decode( get_pagenum_link() );
		$query_args   = array();
		$url_parts    = explode( '?', $pagenum_link );

		if ( isset( $url_parts[1] ) ) {
			wp_parse_str( $url_parts[1], $query_args );
		}

		$pagenum_link = remove_query_arg( array_keys( $query_args ), $pagenum_link );
		$pagenum_link = trailingslashit( $pagenum_link ) . '%_%';

		$format  = $wp_rewrite->using_index_permalinks() && ! strpos( $pagenum_link, 'index.php' ) ? 'index.php/' : '';
		$format .= $wp_rewrite->using_permalinks() ? user_trailingslashit( $wp_rewrite->pagination_base . '/%#%', 'paged' ) : '?paged=%#%';

		$add_args = array();
		if ( !empty($query_args) ) {
			foreach ($query_args as $key => $value) {
				if ( is_array($value) ) {
					$add_args[$key] = array_map( 'urlencode', $value );
				} else {
					$add_args[$key] = $value;
				}
			}
		}

		$pagination['base'] = $pagenum_link;
		$pagination['format'] = $format;
		$pagination['add_args'] = $add_args;
        
        $sq = '';
        if ( isset($_GET['s']) ) {
            $cq = $_GET['s'];
            $sq = str_replace(" ", "+", $cq);
        }
        
        $pagination = apply_filters( 'wp-job-board-custom-pagination2', $pagination );

        $paginations = paginate_links( $pagination );
        $output = '';
        if ( !empty($paginations) ) {
            $output .= '<ul class="pagination '.esc_attr( $args["class"] ).'">';
                foreach ($paginations as $key => $pg) {
                    $output .= '<li>'. $pg .'</li>';
                }
            $output .= '</ul>';
        }
    	
        if ( $args["echo"] ) {
        	echo wp_kses_post($output);
        } else {
        	return $output;
        }
    }

    public static function query_string_form_fields( $values = null, $exclude = array(), $current_key = '', $return = false ) {
		if ( is_null( $values ) ) {
			$values = $_GET; // WPCS: input var ok, CSRF ok.
		} elseif ( is_string( $values ) ) {
			$url_parts = wp_parse_url( $values );
			$values    = array();

			if ( ! empty( $url_parts['query'] ) ) {
				parse_str( $url_parts['query'], $values );
			}
		}
		$html = '';

		foreach ( $values as $key => $value ) {
			if ( in_array( $key, $exclude, true ) ) {
				continue;
			}
			if ( $current_key ) {
				$key = $current_key . '[' . $key . ']';
			}
			if ( is_array( $value ) ) {
				$html .= self::query_string_form_fields( $value, $exclude, $key, true );
			} else {
				$html .= '<input type="hidden" name="' . esc_attr( $key ) . '" value="' . esc_attr( wp_unslash( $value ) ) . '" />';
			}
		}

		if ( $return ) {
			return $html;
		}

		echo $html; // WPCS: XSS ok.
	}

	public static function is_ajax_request() {
	    if ( ! empty( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && strtolower( $_SERVER['HTTP_X_REQUESTED_WITH'] ) == 'xmlhttprequest' ) {
	        return true;
	    }
	    return false;
	}

	public static function get_full_current_url() {
		if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
		    $link = "https"; 
		} else {
		    $link = "http"; 
		}
		  
		// Here append the common URL characters. 
		$link .= "://"; 
		  
		// Append the host(domain name, ip) to the URL. 
		$link .= $_SERVER['HTTP_HOST']; 
		  
		// Append the requested resource location to the URL 
		$link .= $_SERVER['REQUEST_URI']; 
		      
		// Print the link 
		return $link; 
	}

	public static function check_social_login_enable() {
		$facebook = WP_Job_Board_Social_Facebook::get_instance();
		$google = WP_Job_Board_Social_Google::get_instance();
		$linkedin = WP_Job_Board_Social_Linkedin::get_instance();
		$twitter = WP_Job_Board_Social_Twitter::get_instance();
		if ( $facebook->is_facebook_login_enabled() || $google->is_google_login_enabled() || $linkedin->is_linkedin_login_enabled() || $twitter->is_twitter_login_enabled() ) {
			return true;
		}
		return false;
	}

	public static function social_login_before(){
		if ( self::check_social_login_enable() ) {
	        echo '<div class="wrapper-social-login"><div class="line-header"><span>'.esc_html__('or', 'wp-job-board').'</span></div><div class="inner-social">';
	    }
    }
	
	public static function social_login_after(){
		if ( self::check_social_login_enable() ) {
	        echo '</div></div>';
	    }
    }

    public static function set_distance_type($distance_unit) {
    	$unit = wp_job_board_get_option('distance_unit', 'miles');
    	if ( in_array($unit, array('miles', 'km')) ) {
    		$distance_unit = $unit;
    	}
    	return $distance_unit;
    }

    public static function random_key($length = 5) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $return = '';
        for ($i = 0; $i < $length; $i++) {
            $return .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $return;
    }

    public static function set_location_number($nb) {
    	$nb_fields = wp_job_board_get_option('location_nb_fields', 1);
    	return $nb_fields;
    }

    public static function set_first_location_label($nb) {
    	return wp_job_board_get_option('location_1_field_label', 'Country');
    }

    public static function set_second_location_label($nb) {
    	return wp_job_board_get_option('location_2_field_label', 'State');
    }

    public static function set_third_location_label($nb) {
    	return wp_job_board_get_option('location_3_field_label', 'City');
    }

    public static function set_fourth_location_label($nb) {
    	return wp_job_board_get_option('location_4_field_label', 'District');
    }
}

WP_Job_Board_Mixes::init();
