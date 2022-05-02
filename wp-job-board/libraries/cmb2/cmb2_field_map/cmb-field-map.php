<?php

/**
 * Class WP_Job_Board_CMB2_Field_Maps
 */
class WP_Job_Board_CMB2_Field_Maps {

	/**
	 * Initialize the plugin by hooking into CMB2
	 */
	public function __construct() {
		add_filter( 'cmb2_render_pw_map', array( $this, 'render_pw_map' ), 10, 5 );
		add_filter( 'cmb2_sanitize_pw_map', array( $this, 'sanitize_pw_map' ), 10, 4 );
	}

	/**
	 * Render field
	 */
	public function render_pw_map( $field, $field_escaped_value, $field_object_id, $field_object_type, $field_type_object ) {
		$this->setup_admin_scripts();

		echo '<div class="pw-map-search-wrapper">';
		
		echo $field_type_object->input( array(
			'type'       => 'text',
			'name'       => $field->args( '_name' ) . '[address]',
			'value'      => isset( $field_escaped_value['address'] ) ? $field_escaped_value['address'] : '',
			'class'      => 'large-text pw-map-search',
			'desc'       => '',
			'placeholder' => __('Enter search e.g. Lincoln Park', 'wp-job-board'),
		) );

		echo '<span class="find-me-location"></span>';
		echo '</div>';

		echo '<div id="' . $field->args( 'id' ) . '-map" class="pw-map"></div>';
		
		$field_type_object->_desc( true, true );

		echo $field_type_object->input( array(
			'type'       => 'text',
			'name'       => $field->args( '_name' ) . '[latitude]',
			'value'      => isset( $field_escaped_value['latitude'] ) ? $field_escaped_value['latitude'] : '',
			'class'      => 'pw-map-latitude',
			'desc'       => '',
			'placeholder' => __('Latitude', 'wp-job-board'),
			
		) );
		echo $field_type_object->input( array(
			'type'       => 'text',
			'name'       => $field->args( '_name' ) . '[longitude]',
			'value'      => isset( $field_escaped_value['longitude'] ) ? $field_escaped_value['longitude'] : '',
			'class'      => 'pw-map-longitude',
			'desc'       => '',
			'placeholder' => __('Longitude', 'wp-job-board'),
		) );

		echo '<div class="hidden pw-map-properties" data-name="'.$field->args( '_name' ) . '_properties">';
			$properties = get_post_meta( $field_object_id, $field->args( '_name' ) . '_properties', true );
			if ( !empty($properties) && is_array($properties) ) {
				foreach ($properties as $key => $value) {
					echo '<input name="'.$field->args( '_name' ) . '_properties['.$key.']" value="'.$value.'">';
				}
			}
		echo '</div>';
		// echo $field_type_object->input( array(
		// 	'type'       => 'hidden',
		// 	'name'       => $field->args( '_name' ) . '[properties]',
		// 	'value'      => isset( $field_escaped_value['properties'] ) ? $field_escaped_value['properties'] : '',
		// 	'class'      => 'pw-map-properties',
		// 	'desc'       => '',
		// 	'placeholder' => '',
		// ) );
	}

	/**
	 * Optionally save the latitude/longitude values into two custom fields
	 */
	public function sanitize_pw_map( $override_value, $value, $object_id, $field_args ) {
		if ( isset( $field_args['split_values'] ) && $field_args['split_values'] ) {
			if ( ! empty( $value['address'] ) ) {
				update_post_meta( $object_id, $field_args['id'] . '_address', $value['address'] );
			}

			if ( ! empty( $value['latitude'] ) ) {
				update_post_meta( $object_id, $field_args['id'] . '_latitude', $value['latitude'] );
			}

			if ( ! empty( $value['longitude'] ) ) {
				update_post_meta( $object_id, $field_args['id'] . '_longitude', $value['longitude'] );
			}

			if ( ! empty( $_POST[$field_args['id'] .'_properties'] ) ) {
				update_post_meta( $object_id, $field_args['id'] . '_properties', $_POST[$field_args['id'] .'_properties'] );
			} else {
				delete_post_meta( $object_id, $field_args['id'] . '_properties' );
			}
		}

		return $value;
	}

	/**
	 * Enqueue scripts and styles
	 */
	public function setup_admin_scripts() {
		wp_enqueue_style( 'leaflet' );
		wp_enqueue_script( 'jquery-highlight' );
	    wp_enqueue_script( 'leaflet' );
	    wp_enqueue_script( 'leaflet-GoogleMutant' );
	    wp_enqueue_script( 'control-geocoder' );
	    wp_enqueue_script( 'esri-leaflet' );
	    wp_enqueue_script( 'esri-leaflet-geocoder' );
	    wp_enqueue_script( 'leaflet-markercluster' );
	    wp_enqueue_script( 'leaflet-HtmlIcon' );

		wp_enqueue_script( 'pw-script', plugins_url( 'js/script.js', __FILE__ ), array(), '1.0' );
		wp_enqueue_style( 'pw-maps-style', plugins_url( 'css/style.css', __FILE__ ), array(), '1.0' );

		$mapbox_token = '';
		$mapbox_style = '';
		$custom_style = '';
		if ( wp_job_board_get_option('map_service', '') == 'mapbox' ) {
			$mapbox_token = wp_job_board_get_option('mapbox_token', '');
			$mapbox_style = wp_job_board_get_option('mapbox_style', 'mapbox.streets-basic');
		} else {
			$custom_style = wp_job_board_get_option('google_map_style', '');
		}
		wp_localize_script( 'pw-script', 'wp_job_board_maps_opts', array(
			'mapbox_token' => $mapbox_token,
			'mapbox_style' => $mapbox_style,
			'custom_style' => $custom_style,
			'geocoder_country' => wp_job_board_get_option('geocoder_country', ''),
			'default_latitude' => wp_job_board_get_option('default_maps_location_latitude', '43.6568'),
			'default_longitude' => wp_job_board_get_option('default_maps_location_longitude', '-79.4512'),
		));

	}
}
$cmb2_field_maps = new WP_Job_Board_CMB2_Field_Maps();
