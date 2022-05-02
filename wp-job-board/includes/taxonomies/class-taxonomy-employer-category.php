<?php
/**
 * Employer Categories
 *
 * @package    wp-job-board
 * @author     Habq 
 * @license    GNU General Public License, version 3
 */
 
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
class WP_Job_Board_Taxonomy_Employer_Category{

	/**
	 *
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'definition' ), 1 );
	}

	/**
	 *
	 */
	public static function definition($args) {
		$labels = array(
			'name'              => __( 'Categories', 'wp-job-board' ),
			'singular_name'     => __( 'Category', 'wp-job-board' ),
			'search_items'      => __( 'Search Categories', 'wp-job-board' ),
			'all_items'         => __( 'All Categories', 'wp-job-board' ),
			'parent_item'       => __( 'Parent Category', 'wp-job-board' ),
			'parent_item_colon' => __( 'Parent Category:', 'wp-job-board' ),
			'edit_item'         => __( 'Edit', 'wp-job-board' ),
			'update_item'       => __( 'Update', 'wp-job-board' ),
			'add_new_item'      => __( 'Add New', 'wp-job-board' ),
			'new_item_name'     => __( 'New Category', 'wp-job-board' ),
			'menu_name'         => __( 'Categories', 'wp-job-board' ),
		);
		$rewrite_slug = get_option('wp_job_board_employer_category_slug');
		if ( empty($rewrite_slug) ) {
			$rewrite_slug = _x( 'employer-category', 'Employer category slug - resave permalinks after changing this', 'wp-job-board' );
		}
		$rewrite = array(
			'slug'         => $rewrite_slug,
			'with_front'   => false,
			'hierarchical' => false,
		);
		register_taxonomy( 'employer_category', 'employer', array(
			'labels'            => apply_filters( 'wp_job_board_taxomony_employer_category_labels', $labels ),
			'hierarchical'      => true,
			'rewrite'           => $rewrite,
			'public'            => true,
			'show_ui'           => true,
			'show_in_rest'		=> true
		) );
	}

}

WP_Job_Board_Taxonomy_Employer_Category::init();