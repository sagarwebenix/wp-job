<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$count = WP_Job_Board_Abstract_Filter::filter_count($name, '1', $field);
if ( $count > 0 ) {
?>
	<div class="form-group form-group-<?php echo esc_attr($key); ?>">
		<div class="circle-check">
			<div class="list-item">
				<input type="checkbox" name="<?php echo esc_attr($name); ?>" class="form-control"
			           value="1"
			           id="<?php echo esc_attr( $args['widget_id'] ); ?>_<?php echo esc_attr($key); ?>" <?php echo trim($selected ? ' checked="checked"' : ''); ?>>
			    <label for="<?php echo esc_attr( $args['widget_id'] ); ?>_<?php echo esc_attr($key); ?>"><?php echo wp_kses_post($field['label']); ?></label>
			    
	            <span class="count"><?php echo sprintf(__('(%d)', 'wp-job-board'), $count); ?></span>
	            
		    </div>
	    </div>
	</div><!-- /.form-group -->
<?php }