<?php
/**
 * Locations
 *
 * @package    wp-job-board
 * @author     Habq 
 * @license    GNU General Public License, version 3
 */
 
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
class WP_Job_Board_Taxonomy_Candidate_Location{

	/**
	 *
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'definition' ), 1 );
	}

	/**
	 *
	 */
	public static function definition() {
		$labels = array(
			'name'              => __( 'Locations', 'wp-job-board' ),
			'singular_name'     => __( 'Location', 'wp-job-board' ),
			'search_items'      => __( 'Search Locations', 'wp-job-board' ),
			'all_items'         => __( 'All Locations', 'wp-job-board' ),
			'parent_item'       => __( 'Parent Location', 'wp-job-board' ),
			'parent_item_colon' => __( 'Parent Location:', 'wp-job-board' ),
			'edit_item'         => __( 'Edit', 'wp-job-board' ),
			'update_item'       => __( 'Update', 'wp-job-board' ),
			'add_new_item'      => __( 'Add New', 'wp-job-board' ),
			'new_item_name'     => __( 'New Location', 'wp-job-board' ),
			'menu_name'         => __( 'Locations', 'wp-job-board' ),
		);
		$rewrite_slug = get_option('wp_job_board_candidate_location_slug');
		if ( empty($rewrite_slug) ) {
			$rewrite_slug = _x( 'candidate-location', 'Candidate location slug - resave permalinks after changing this', 'wp-job-board' );
		}
		$rewrite = array(
			'slug'         => $rewrite_slug,
			'with_front'   => false,
			'hierarchical' => false,
		);
		register_taxonomy( 'candidate_location', 'candidate', array(
			'labels'            => apply_filters( 'wp_job_board_taxomony_candidate_location_labels', $labels ),
			'hierarchical'      => true,
			'rewrite'           => $rewrite,
			'public'            => true,
			'show_ui'           => true,
			'show_in_rest'		=> true
		) );
	}

}

WP_Job_Board_Taxonomy_Candidate_Location::init();