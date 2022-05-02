<?php
/**
 * Job Listing
 *
 * @package    wp-job-board
 * @author     Habq 
 * @license    GNU General Public License, version 3
 */

if ( ! defined( 'ABSPATH' ) ) {
  	exit;
}

class WP_Job_Board_Job_Listing {
	
	public static function init() {
		// loop
		add_action( 'wp_job_board_before_job_archive', array( __CLASS__, 'display_jobs_results_filters' ), 5 );
		add_action( 'wp_job_board_before_job_archive', array( __CLASS__, 'display_jobs_count_results' ), 10 );

		add_action( 'wp_job_board_before_job_archive', array( __CLASS__, 'display_jobs_alert_orderby_start' ), 15 );
		add_action( 'wp_job_board_before_job_archive', array( __CLASS__, 'display_jobs_alert_form' ), 20 );
		add_action( 'wp_job_board_before_job_archive', array( __CLASS__, 'display_job_feed' ), 22 );
		add_action( 'wp_job_board_before_job_archive', array( __CLASS__, 'display_jobs_orderby' ), 25 );
		add_action( 'wp_job_board_before_job_archive', array( __CLASS__, 'display_jobs_alert_orderby_end' ), 100 );
	}

	public static function get_post_meta($post_id, $key, $single = true) {
		return get_post_meta($post_id, WP_JOB_BOARD_JOB_LISTING_PREFIX.$key, $single);
	}
	
	public static function customer_also_viewed( $job_id ) {
	    $customer_also_viewed = get_option('wp_job_board_customer_also_viewed_'.$job_id);  
	    if ( !empty($customer_also_viewed) )        
	    {  
	        $customer_also_viewed = explode(',',$customer_also_viewed);
	        $customer_also_viewed = array_reverse($customer_also_viewed);       
	        
	        //Skip same product on product page from the list
	        if ( ($key = array_search($job_id, $customer_also_viewed)) !== false ) {
	            unset($customer_also_viewed[$key] );
	        }
	        return $customer_also_viewed;
	    }
	    return false;
	}

	// add product viewed
	public static function track_job_view() {
	    if ( ! is_singular( 'job_listing' ) ) {
	        return;
	    }

	    global $post;

	    // views count
	    $viewed_count = intval(get_post_meta($post->ID, '_viewed_count', true));
	    $viewed_count++;
	    update_post_meta($post->ID, '_viewed_count', $viewed_count);

	    // view days
	    $today = date('Y-m-d', time());
	    $views_by_date = get_post_meta($post->ID, '_views_by_date', true);

	    if( $views_by_date != '' || is_array($views_by_date) ) {
	        if (!isset($views_by_date[$today])) {
	            if ( count($views_by_date) > 60 ) {
	                array_shift($views_by_date);
	            }
	            $views_by_date[$today] = 1;
	        } else {
	            $views_by_date[$today] = intval($views_by_date[$today]) + 1;
	        }
	    } else {
	        $views_by_date = array();
	        $views_by_date[$today] = 1;
	    }
	    update_post_meta($post->ID, '_views_by_date', $views_by_date);
	    update_post_meta($post->ID, '_recently_viewed', $today);

	    // 

	    if ( empty( $_COOKIE['wp_job_board_recently_viewed'] ) ) {
	        $viewed_products = array();
	    } else {
	        $viewed_products = (array) explode( '|', $_COOKIE['wp_job_board_recently_viewed'] );
	    }

	    if ( ! in_array( $post->ID, $viewed_products ) ) {
	        $viewed_products[] = $post->ID;
	    }

	    if ( sizeof( $viewed_products ) > 15 ) {
	        array_shift( $viewed_products );
	    }

	    // Store for session only
	    setcookie( 'wp_job_board_recently_viewed', implode( '|', $viewed_products ) );


	    // also view
	    if ( ($key = array_search($post->ID, $viewed_products)) !== false ) {
	        unset($viewed_products[$key] );
	    }

	    if ( !empty($viewed_products) )
	    {
	        foreach($viewed_products as $viewed)
	        {
	            $option = 'wp_job_board_customer_also_viewed_'.$viewed;
	            $option_value = get_option($option);
	            
	            if ( isset($option_value) && !empty($option_value) ) {
	                $option_value = explode(',', $option_value);
	                if ( !in_array($post->ID, $option_value) ) {
	                    $option_value[] = $post->ID;
	                }
	            }
	            
	            $option_value = (!empty($option_value) && count($option_value) > 1) ? implode(',', $option_value) : $post->ID;

	            update_option($option, $option_value, 'no');
	        }
	    } 

	}

	public static function send_admin_expiring_notice() {
		global $wpdb;

		if ( !wp_job_board_get_option('admin_notice_expiring_listing') ) {
			return;
		}
		$days_notice = wp_job_board_get_option('admin_notice_expiring_listing_days');

		$job_ids = self::get_expiring_jobs($days_notice);

		if ( $job_ids ) {
			foreach ( $job_ids as $job_id ) {
				// send email here.
				$job = get_post($job_id);
				$email_from = get_option( 'admin_email', false );
				
				$headers = sprintf( "From: %s <%s>\r\n Content-type: text/html", get_bloginfo('name'), $email_from );
				$email_to = get_option( 'admin_email', false );
				$subject = WP_Job_Board_Email::render_email_vars(array('job' => $job), 'admin_notice_expiring_listing', 'subject');
				$content = WP_Job_Board_Email::render_email_vars(array('job' => $job), 'admin_notice_expiring_listing', 'content');
				
				WP_Job_Board_Email::wp_mail( $email_to, $subject, $content, $headers );
			}
		}
	}

	public static function send_employer_expiring_notice() {
		global $wpdb;

		if ( !wp_job_board_get_option('employer_notice_expiring_listing') ) {
			return;
		}
		$days_notice = wp_job_board_get_option('employer_notice_expiring_listing_days');

		$job_ids = self::get_expiring_jobs($days_notice);

		if ( $job_ids ) {
			foreach ( $job_ids as $job_id ) {
				// send email here.
				$job = get_post($job_id);
				$email_from = get_option( 'admin_email', false );
				
				$headers = sprintf( "From: %s <%s>\r\n Content-type: text/html", get_bloginfo('name'), $email_from );
				$email_to = get_the_author_meta( 'user_email', $job->post_author );
				$subject = WP_Job_Board_Email::render_email_vars(array('job' => $job), 'employer_notice_expiring_listing', 'subject');
				$content = WP_Job_Board_Email::render_email_vars(array('job' => $job), 'employer_notice_expiring_listing', 'content');
				
				WP_Job_Board_Email::wp_mail( $email_to, $subject, $content, $headers );
				
			}
		}
	}

	public static function get_expiring_jobs($days_notice) {
		global $wpdb;
		$prefix = WP_JOB_BOARD_JOB_LISTING_PREFIX;

		$notice_before_ts = current_time( 'timestamp' ) + ( DAY_IN_SECONDS * $days_notice );
		$job_ids          = $wpdb->get_col( $wpdb->prepare(
			"
			SELECT postmeta.post_id FROM {$wpdb->postmeta} as postmeta
			LEFT JOIN {$wpdb->posts} as posts ON postmeta.post_id = posts.ID
			WHERE postmeta.meta_key = %s
			AND postmeta.meta_value = %s
			AND posts.post_status = 'publish'
			AND posts.post_type = 'job_listing'
			",
			$prefix.'expiry_date',
			date( 'Y-m-d', $notice_before_ts )
		) );

		return $job_ids;
	}

	public static function check_for_expired_jobs() {
		global $wpdb;

		$prefix = WP_JOB_BOARD_JOB_LISTING_PREFIX;
		
		// Change status to expired.
		$job_ids = $wpdb->get_col(
			$wpdb->prepare( "
				SELECT postmeta.post_id FROM {$wpdb->postmeta} as postmeta
				LEFT JOIN {$wpdb->posts} as posts ON postmeta.post_id = posts.ID
				WHERE postmeta.meta_key = %s
				AND postmeta.meta_value > 0
				AND postmeta.meta_value < %s
				AND posts.post_status = 'publish'
				AND posts.post_type = 'job_listing'",
				$prefix.'expiry_date',
				date( 'Y-m-d', current_time( 'timestamp' ) )
			)
		);

		if ( $job_ids ) {
			foreach ( $job_ids as $job_id ) {
				$job_data                = array();
				$job_data['ID']          = $job_id;
				$job_data['post_status'] = 'expired';
				wp_update_post( $job_data );
			}
		}

		// Delete old expired jobs.
		if ( apply_filters( 'wp_job_board_delete_expired_jobs', false ) ) {
			$job_ids = $wpdb->get_col(
				$wpdb->prepare( "
					SELECT posts.ID FROM {$wpdb->posts} as posts
					WHERE posts.post_type = 'job_listing'
					AND posts.post_modified < %s
					AND posts.post_status = 'expired'",
					date( 'Y-m-d', strtotime( '-' . apply_filters( 'wp_job_board_delete_expired_jobs_days', 30 ) . ' days', current_time( 'timestamp' ) ) )
				)
			);

			if ( $job_ids ) {
				foreach ( $job_ids as $job_id ) {
					wp_trash_post( $job_id );
				}
			}
		}
	}

	/**
	 * Deletes old previewed jobs after 30 days to keep the DB clean.
	 */
	public static function delete_old_previews() {
		global $wpdb;

		// Delete old expired jobs.
		$job_ids = $wpdb->get_col(
			$wpdb->prepare( "
				SELECT posts.ID FROM {$wpdb->posts} as posts
				WHERE posts.post_type = 'job_listing'
				AND posts.post_modified < %s
				AND posts.post_status = 'preview'",
				date( 'Y-m-d', strtotime( '-' . apply_filters( 'wp_job_board_delete_old_previews_jobs_days', 30 ) . ' days', current_time( 'timestamp' ) ) )
			)
		);

		if ( $job_ids ) {
			foreach ( $job_ids as $job_id ) {
				wp_delete_post( $job_id, true );
			}
		}
	}

	public static function job_statuses() {
		return apply_filters(
			'wp_job_board_job_listing_statuses',
			array(
				'draft'           => _x( 'Draft', 'post status', 'wp-job-board' ),
				'expired'         => _x( 'Expired', 'post status', 'wp-job-board' ),
				'preview'         => _x( 'Preview', 'post status', 'wp-job-board' ),
				'pending'         => _x( 'Pending approval', 'post status', 'wp-job-board' ),
				'pending_approve' => _x( 'Pending approval', 'post status', 'wp-job-board' ),
				'pending_payment' => _x( 'Pending payment', 'post status', 'wp-job-board' ),
				'publish'         => _x( 'Active', 'post status', 'wp-job-board' ),
			)
		);
	}

	public static function is_job_status_changing( $from_status, $to_status ) {
		return isset( $_POST['post_status'] ) && isset( $_POST['original_post_status'] ) && $_POST['original_post_status'] !== $_POST['post_status'] && ( null === $from_status || $from_status === $_POST['original_post_status'] ) && $to_status === $_POST['post_status'];
	}

	public static function calculate_job_expiry( $job_id ) {
		$duration = absint( wp_job_board_get_option( 'submission_duration' ) );
		$duration = apply_filters( 'wp-job-board-calculate-job-expiry', $duration, $job_id);

		if ( $duration ) {
			return date( 'Y-m-d', strtotime( "+{$duration} days", current_time( 'timestamp' ) ) );
		}

		return '';
	}

	public static function get_company_name( $post ) {
		$author_id = $post->post_author;
		$employer_id = WP_Job_Board_User::get_employer_by_user_id($author_id);
		$ouput = '';
		if ( $employer_id ) {
			$ouput = get_the_title($employer_id);
		}
		return apply_filters('wp-job-board-get-company-name', $ouput, $post);
	}

	public static function get_company_name_html( $post ) {
		$author_id = $post->post_author;
		$employer_id = WP_Job_Board_User::get_employer_by_user_id($author_id);
		$ouput = '';
		if ( $employer_id ) {
			$ouput = sprintf(wp_kses(__('<a href="%s" class="employer text-theme">%s</a>', 'wp-job-board'), array( 'a' => array('class' => array(), 'href' => array()) ) ), get_permalink($employer_id), get_the_title($employer_id) );
		}
		return apply_filters('wp-job-board-get-company-name-html', $ouput, $post);
	}

	public static function get_salary_html( $post_id = null, $html = true ) {
		$min_salary = self::get_min_salary_html($post_id, $html);
		$max_salary = self::get_max_salary_html($post_id, $html);
		$price_html = '';
		if ( $min_salary ) {
			$price_html = $min_salary;
		}
		if ( $max_salary ) {
			$price_html .= ' - '.$max_salary;
		}
		if ( $price_html ) {
			$salary_type = self::get_post_meta( $post_id, 'salary_type', true );

			switch ($salary_type) {
				case 'yearly':
					$price_html = $price_html.esc_html__(' per year', 'wp-job-board');
					break;
				case 'monthly':
					$price_html = $price_html.esc_html__(' per month', 'wp-job-board');
					break;
				case 'weekly':
					$price_html = $price_html.esc_html__(' per week', 'wp-job-board');
					break;
				case 'hourly':
					$price_html = $price_html.esc_html__(' per hour', 'wp-job-board');
					break;
			}
		}
		return apply_filters( 'wp-job-board-get-salary-html', $price_html, $post_id );
	}

	public static function get_min_salary_html( $post_id = null, $html = true ) {
		if ( null == $post_id ) {
			$post_id = get_the_ID();
		}

		$price = self::get_post_meta( $post_id, 'salary', true );

		if ( empty( $price ) || ! is_numeric( $price ) ) {
			return false;
		}

		if ( !$html ) {
			$price = WP_Job_Board_Price::format_price_without_html( $price );
		} else {
			$price = WP_Job_Board_Price::format_price( $price );
		}

		return apply_filters( 'wp-job-board-get-min-salary-html', $price, $post_id );
	}

	public static function get_max_salary_html( $post_id = null, $html = true ) {
		if ( null == $post_id ) {
			$post_id = get_the_ID();
		}

		$price = self::get_post_meta( $post_id, 'max_salary', true );

		if ( empty( $price ) || ! is_numeric( $price ) ) {
			return false;
		}
		if ( !$html ) {
			$price = WP_Job_Board_Price::format_price_without_html( $price );
		} else {
			$price = WP_Job_Board_Price::format_price( $price );
		}

		return apply_filters( 'wp-job-board-get-max-salary-html', $price, $post_id );
	}
	
	public static function is_featured( $post_id = null ) {
		if ( null == $post_id ) {
			$post_id = get_the_ID();
		}
		$featured = self::get_post_meta( $post_id, 'featured', true );
		$return = $featured ? true : false;
		return apply_filters( 'wp-job-board-job-listing-is-featured', $return, $post_id );
	}

	public static function is_urgent( $post_id = null ) {
		if ( null == $post_id ) {
			$post_id = get_the_ID();
		}
		$urgent = self::get_post_meta( $post_id, 'urgent', true );
		$return = $urgent ? true : false;
		return apply_filters( 'wp-job-board-job-listing-is-urgent', $return, $post_id );
	}

	public static function is_filled( $post_id = null ) {
		if ( null == $post_id ) {
			$post_id = get_the_ID();
		}
		$filled = self::get_post_meta( $post_id, 'filled', true );
		$return = $filled ? true : false;
		return apply_filters( 'wp-job-board-job-listing-is-filled', $return, $post_id );
	}

	public static function count_applicants( $post_id = null ) {
		if ( null == $post_id ) {
			$post_id = get_the_ID();
		}
		
		$query_args = array(
			'post_type'         => 'job_applicant',
			'fields' 			=> 'ids',
			'posts_per_page'    => 1,
			'post_status'       => 'publish',
			'meta_query'       	=> array(
				array(
					'key' => WP_JOB_BOARD_APPLICANT_PREFIX.'job_id',
					'value'     => $post_id,
					'compare'   => 'IN',
				)
			)
		);
		$applicants = new WP_Query( $query_args );
		
		return $applicants->found_posts;
	}

	public static function get_job_taxs( $post_id = null, $tax = 'job_listing_category' ) {
		if ( null == $post_id ) {
			$post_id = get_the_ID();
		}
		$types = get_the_terms( $post_id, $tax );
		return $types;
	}

	public static function get_job_types_html( $post_id = null ) {
		if ( null == $post_id ) {
			$post_id = get_the_ID();
		}
		$output = '';
		$types = self::get_job_taxs( $post_id, 'job_listing_type' );
		if ( $types ) {
            foreach ($types as $term) {
                $output .= '<a href="'.get_term_link($term).'">'.wp_kses_post($term->name).'</a>';
            }
        }
		return apply_filters( 'wp-job-board-get-job-types-html', $output, $post_id );
	}

	public static function check_can_apply_social( $post_id = null ) {
		$apply_type = self::get_post_meta( $post_id, 'apply_type', true );
		$application_deadline_date = self::get_post_meta( $post_id, 'application_deadline_date', true );
		if ( empty($application_deadline_date) || strtotime($application_deadline_date) >= strtotime('now') ) {
			if ( $apply_type == 'internal' && !is_user_logged_in() ) {
				return true;
			}
		}
		return false;
	}

	public static function display_apply_job_btn( $post_id = null ) {
		$apply_type = self::get_post_meta( $post_id, 'apply_type', true );
		$application_deadline_date = self::get_post_meta( $post_id, 'application_deadline_date', true );
		if ( empty($application_deadline_date) || strtotime($application_deadline_date) >= strtotime('now') ) {
			if ( $application_deadline_date ) {
				$deadline_date = strtotime($application_deadline_date);
				?>
				<div class="deadline-time"><?php echo sprintf(__('Application ends: <strong>%s</strong>', 'wp-job-board'), date_i18n(get_option('date_format'), $deadline_date)); ?></div>
				<?php
			}
			
			if ( $apply_type == 'external' ) {
				$apply_url = self::get_post_meta( $post_id, 'apply_url', true );
				if ( !empty($apply_url) ) {
					?>
					<a href="<?php echo esc_url($apply_url); ?>" target="_blank" class="btn btn-apply btn-apply-job-external"><?php esc_html_e('Apply Now', 'wp-job-board'); ?><i class="next flaticon-right-arrow"></i></a>
					<?php
				}
			} elseif ( $apply_type == 'with_email' ) {
				?>
				<a href="#job-apply-email-form-wrapper-<?php echo esc_attr($post_id); ?>" class="btn btn-apply btn-apply-job-email" data-job_id="<?php echo esc_attr($post_id); ?>"><?php esc_html_e('Apply Now', 'wp-job-board'); ?><i class="next flaticon-right-arrow"></i></a>
				<!-- email apply form here -->
				<?php
				global $job_preview;
				if ( empty($job_preview) ) {
					echo WP_Job_Board_Template_Loader::get_template_part('single-job_listing/apply-email-form');
				}
			} else {
				if ( !is_user_logged_in() || !WP_Job_Board_User::is_candidate() ) {
					?>
					<a href="javascript:void(0);" class="btn btn-apply btn-apply-job-internal-required"><?php esc_html_e('Apply Now', 'wp-job-board'); ?><i class="next flaticon-right-arrow"></i></a>
					<?php
					echo WP_Job_Board_Template_Loader::get_template_part('single-job_listing/apply-internal-required');
				} else {
					$class = 'btn-apply-job-internal';
					$text = esc_html__('Apply Now', 'wp-job-board').'<i class="next flaticon-right-arrow"></i>';
					$user_id = WP_Job_Board_User::get_user_id();
					$check_applied = WP_Job_Board_Candidate::check_applied($user_id, $post_id);
					if ( $check_applied ) {
						$class = 'btn-applied-job-internal';
						$text = esc_html__('Applied', 'wp-job-board');
					}
					?>
					<a href="javascript:void(0);" class="btn btn-apply <?php echo esc_attr($class); ?>" data-job_id="<?php echo esc_attr($post_id); ?>" data-nonce="<?php echo esc_attr(wp_create_nonce( 'wp-job-board-apply-internal-nonce' )); ?>"><?php echo trim($text); ?></a>
					<?php
				}
			}
			
		} else {
			?>
			<div class="deadline-closed"><?php esc_html_e('Application deadline closed.', 'wp-job-board'); ?></div>
			<?php
		}
	}

	public static function display_shortlist_btn( $post_id = null ) {
		if ( null == $post_id ) {
			$post_id = get_the_ID();
		}
		
		if ( WP_Job_Board_Candidate::check_added_shortlist($post_id) ) {
			$classes = 'btn-added-job-shortlist btn btn-block btn-shortlist';
			$nonce = wp_create_nonce( 'wp-job-board-remove-job-shortlist-nonce' );
			$text = esc_html__('Shortlisted', 'wp-job-board');
		} else {
			$classes = 'btn-add-job-shortlist btn btn-block btn-shortlist';
			$nonce = wp_create_nonce( 'wp-job-board-add-job-shortlist-nonce' );
			$text = esc_html__('Shortlist', 'wp-job-board');
		}
		?>
		<a href="javascript:void(0);" class="btn <?php echo esc_attr($classes); ?>" data-job_id="<?php echo esc_attr($post_id); ?>" data-nonce="<?php echo esc_attr($nonce); ?>"><i class="pre flaticon-favorites"></i><?php echo trim($text); ?></a>
		<?php
	}

	public static function display_jobs_results_filters() {
		$filters = WP_Job_Board_Abstract_Filter::get_filters();

		echo WP_Job_Board_Template_Loader::get_template_part('loop/job/results-filters', array('filters' => $filters));
	}

	public static function display_jobs_count_results($wp_query) {
		$total = $wp_query->found_posts;
		$per_page = $wp_query->query_vars['posts_per_page'];
		$current = max( 1, $wp_query->get( 'paged', 1 ) );
		$args = array(
			'total' => $total,
			'per_page' => $per_page,
			'current' => $current,
		);

		echo WP_Job_Board_Template_Loader::get_template_part('loop/job/results-count', $args);
	}

	public static function display_jobs_alert_form() {
		echo WP_Job_Board_Template_Loader::get_template_part('loop/job/jobs-alert-form');
	}

	public static function display_jobs_orderby() {
		echo WP_Job_Board_Template_Loader::get_template_part('loop/job/orderby');
	}

	public static function display_jobs_alert_orderby_start() {
		echo WP_Job_Board_Template_Loader::get_template_part('loop/job/alert-orderby-start');
	}

	public static function display_jobs_alert_orderby_end() {
		echo WP_Job_Board_Template_Loader::get_template_part('loop/job/alert-orderby-end');
	}

	public static function job_feed_url($values = null, $exclude = array(), $current_key = '', $page_rss_url = '', $return = false) {
		if ( empty($page_rss_url) ) {
			$page_rss_url = home_url('/') . '?feed=job_listing_feed';
		}
		if ( is_null( $values ) ) {
			$values = $_GET; // WPCS: input var ok, CSRF ok.
		} elseif ( is_string( $values ) ) {
			$url_parts = wp_parse_url( $values );
			$values    = array();

			if ( ! empty( $url_parts['query'] ) ) {
				parse_str( $url_parts['query'], $values );
			}
		}
		foreach ( $values as $key => $value ) {
			if ( in_array( $key, $exclude, true ) ) {
				continue;
			}
			if ( $current_key ) {
				$key = $current_key . '[' . $key . ']';
			}
			if ( is_array( $value ) ) {
				$page_rss_url = self::job_feed_url( $value, $exclude, $key, $page_rss_url, true );
			} else {
				$page_rss_url = add_query_arg($key, wp_unslash( $value ), $page_rss_url);
			}
		}

		if ( $return ) {
			return $page_rss_url;
		}

		echo $page_rss_url;
	}

	public static function display_job_feed(){
		echo WP_Job_Board_Template_Loader::get_template_part('loop/job/jobs-rss-btn');
	}
}
WP_Job_Board_Job_Listing::init();