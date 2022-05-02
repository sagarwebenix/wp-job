<?php
/**
 * Job RSS Feed
 *
 * @package    wp-job-board
 * @author     Habq 
 * @license    GNU General Public License, version 3
 */

if ( ! defined( 'ABSPATH' ) ) {
  	exit;
}

class WP_Job_Board_Job_RSS_Feed {

	public static function init() {
		 add_action( 'init', array( __CLASS__, 'custom_rss' ) );
	}

	public static function custom_rss() {
        add_feed( 'job_listing_feed', array( __CLASS__, 'custom_feed_template' ) );
    }

    public static function custom_feed_template() {
        echo WP_Job_Board_Template_Loader::get_template_part( 'rss-feed-jobs' );
    }
}

WP_Job_Board_Job_RSS_Feed::init();