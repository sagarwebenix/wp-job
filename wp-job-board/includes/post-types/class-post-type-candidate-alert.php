<?php
/**
 * Post Type: Candidate Alert
 *
 * @package    wp-job-board
 * @author     Habq 
 * @license    GNU General Public License, version 3
 */

if ( ! defined( 'ABSPATH' ) ) {
  	exit;
}

class WP_Job_Board_Post_Type_Candidate_Alert {
	public static function init() {
	  	add_action( 'init', array( __CLASS__, 'register_post_type' ) );
	  	add_filter( 'cmb2_meta_boxes', array( __CLASS__, 'fields' ) );

	  	add_filter( 'manage_edit-candidate_alert_columns', array( __CLASS__, 'custom_columns' ) );
		add_action( 'manage_candidate_alert_posts_custom_column', array( __CLASS__, 'custom_columns_manage' ) );
	}

	public static function register_post_type() {
		$labels = array(
			'name'                  => __( 'Candidate Alerts', 'wp-job-board' ),
			'singular_name'         => __( 'Candidate Alert', 'wp-job-board' ),
			'add_new'               => __( 'Add New Candidate Alert', 'wp-job-board' ),
			'add_new_item'          => __( 'Add New Candidate Alert', 'wp-job-board' ),
			'edit_item'             => __( 'EditCandidate  Alert', 'wp-job-board' ),
			'new_item'              => __( 'New Candidate Alert', 'wp-job-board' ),
			'all_items'             => __( 'Candidate Alerts', 'wp-job-board' ),
			'view_item'             => __( 'View Candidate Alert', 'wp-job-board' ),
			'search_items'          => __( 'SearchCandidate  Alert', 'wp-job-board' ),
			'not_found'             => __( 'No Candidate Alerts found', 'wp-job-board' ),
			'not_found_in_trash'    => __( 'No Candidate Alerts found in Trash', 'wp-job-board' ),
			'parent_item_colon'     => '',
			'menu_name'             => __( 'Candidate Alerts', 'wp-job-board' ),
		);

		register_post_type( 'candidate_alert',
			array(
				'labels'            => $labels,
				'supports'          => array( 'title' ),
				'public'            => true,
		        'has_archive'       => false,
		        'publicly_queryable' => false,
				'show_in_rest'		=> false,
				'show_in_menu'		=> 'edit.php?post_type=candidate',
			)
		);
	}

	/**
	 * Defines custom fields
	 *
	 * @access public
	 * @param array $metaboxes
	 * @return array
	 */
	public static function fields( array $metaboxes ) {
		$email_frequency_default = WP_Job_Board_Job_Alert::get_email_frequency();
		$email_frequency = array();
		if ( $email_frequency_default && is_admin() ) {
			foreach ($email_frequency_default as $key => $value) {
				if ( !empty($value['label']) && !empty($value['days']) ) {
					$email_frequency[$key] = $value['label'];
				}
			}
		}
		$fields = array();
		if ( isset($_GET['post']) && $_GET['post'] && is_admin() ) {
			$post = get_post($_GET['post']);
			if ( $post && $post->post_type == 'candidate_alert' ) {
				$author_name = get_the_author_meta('display_name', $post->post_author);
				$author_email = get_the_author_meta('user_email', $post->post_author);
				$fields[] = array(
					'name' => sprintf( __('Author: %s (%s)', 'wp-job-board'), $author_name, $author_email ),
					'type' => 'title',
					'id'   => WP_JOB_BOARD_CANDIDATE_ALERT_PREFIX . 'author'
				);
			}
		}
		$fields[] = array(
			'name'              => __( 'Alert Query', 'wp-job-board' ),
			'id'                => WP_JOB_BOARD_CANDIDATE_ALERT_PREFIX . 'alert_query',
			'type'              => 'textarea',
		);
		$fields[] = array(
			'name'              => __( 'Email Frequency', 'wp-job-board' ),
			'id'                => WP_JOB_BOARD_CANDIDATE_ALERT_PREFIX . 'email_frequency',
			'type'              => 'select',
			'options'			=> $email_frequency
		);
		$metaboxes[ WP_JOB_BOARD_CANDIDATE_ALERT_PREFIX . 'general' ] = array(
			'id'                        => WP_JOB_BOARD_CANDIDATE_ALERT_PREFIX . 'general',
			'title'                     => __( 'General Options', 'wp-job-board' ),
			'object_types'              => array( 'candidate_alert' ),
			'context'                   => 'normal',
			'priority'                  => 'high',
			'show_names'                => true,
			'show_in_rest'				=> true,
			'fields'                    => $fields
		);
		return $metaboxes;
	}
	/**
	 * Custom admin columns for post type
	 *
	 * @access public
	 * @return array
	 */
	public static function custom_columns($columns) {
		if ( isset($columns['comments']) ) {
			unset($columns['comments']);
		}
		if ( isset($columns['date']) ) {
			unset($columns['date']);
		}
		$fields = array_merge($columns, array(
			'title' 			=> __( 'Title', 'wp-job-board' ),
			'email_frequency' 	=> __( 'Email Frequency', 'wp-job-board' ),
			'date' 				=> esc_html__( 'Date', 'wp-job-board' ),
			'auhtor' 			=> esc_html__( 'Auhtor', 'wp-job-board' ),
		));
		return $fields;
	}

	/**
	 * Custom admin columns implementation
	 *
	 * @access public
	 * @param string $column
	 * @return array
	 */
	public static function custom_columns_manage( $column ) {
		switch ( $column ) {
			case 'email_frequency':
					$email_frequency = get_post_meta( get_the_ID(), WP_JOB_BOARD_CANDIDATE_ALERT_PREFIX . 'email_frequency', true );
					echo wp_kses_post($email_frequency);
				break;
		}
	}

}
WP_Job_Board_Post_Type_Candidate_Alert::init();