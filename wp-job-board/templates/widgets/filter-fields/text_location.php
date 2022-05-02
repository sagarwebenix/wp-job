<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$latitude = isset($_GET['filter-center-latitude']) ? $_GET['filter-center-latitude'] : '';
$longitude = isset($_GET['filter-center-longitude']) ? $_GET['filter-center-longitude'] : '';
?>
<div class="form-group form-group-<?php echo esc_attr($key); ?> <?php echo esc_attr(!empty($field['toggle']) ? 'toggle-field' : ''); ?> <?php echo esc_attr(!empty($field['hide_field_content']) ? 'hide-content' : ''); ?>">
	<?php if ( !isset($field['show_title']) || $field['show_title'] ) { ?>
    	<label for="<?php echo esc_attr( $args['widget_id'] ); ?>_<?php echo esc_attr($key); ?>" class="heading-label">
    		<?php echo wp_kses_post($field['label']); ?>
    		<?php if ( !empty($field['toggle']) ) { ?>
                <i class="fa fa-angle-down" aria-hidden="true"></i>
            <?php } ?>
    	</label>
    <?php } ?>
    <div class="form-group-inner inner">
	    <?php if ( !empty($field['icon']) ) { ?>
	    	<i class="<?php echo esc_attr( $field['icon'] ); ?>"></i>
	    <?php } ?>
	    <div class="action-location">
		    <input type="text" name="<?php echo esc_attr($name); ?>" class="form-control" value="<?php echo esc_attr($selected); ?>" id="<?php echo esc_attr( $args['widget_id'] ); ?>_<?php echo esc_attr($key); ?>" placeholder="<?php echo esc_attr(!empty($field['placeholder']) ? $field['placeholder'] : ''); ?>">
			<span class="find-me"></span>
			<span class="clear-location hidden"><i class="ti-close"></i></span>
		</div>
		<input type="hidden" name="filter-center-latitude" value="<?php echo esc_attr($latitude); ?>">
		<input type="hidden" name="filter-center-longitude" value="<?php echo esc_attr($longitude); ?>">
		
		<?php if ( !empty($field['show_distance']) ) { ?>
			<?php
				$distance_type = apply_filters( 'wp_job_board_filter_distance_type', 'miles' );
				$search_distance = isset( $_GET['filter-distance'] ) ? $_GET['filter-distance'] : apply_filters( 'wp_job_board_filter_distance_default', 50 );
			?>
			<div class="search_distance_wrapper clearfix">
				<div class="search-distance-label">
					<?php echo sprintf(wp_kses(__('Radius: <span class="text-distance">%s</span> %s', 'wp-job-board'), array('span' => array('class' => array()))), $search_distance, $distance_type); ?>
				</div>
				<div class="search-distance-wrapper">
					<input type="hidden" name="filter-distance" value="<?php echo esc_html($search_distance); ?>" />
					<div class="search-distance-slider"><div class="ui-slider-handle distance-custom-handle"></div></div>
				</div>
			</div>
		<?php } ?>
	</div>
</div><!-- /.form-group -->