<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


ob_start();
if ( !empty($options) ) {
    $i = 1;
    foreach ($options as $option) {
        if ( $option['value'] ) {
            if ( isset($option['count']) && $option['count'] <= 0 ) {
                continue;
            }
            ?>
            <option value="<?php echo esc_attr($option['value']); ?>" <?php selected($selected, $option['value']); ?>>
                <?php echo esc_attr($option['text']); ?>
                <?php if ( isset($option['count']) ) { ?>
                    <?php echo sprintf(__('(%d)', 'wp-job-board'), $option['count']); ?>
                <?php } ?>
            </option>
            <?php
            $i++;
        }
    }
}
$output = ob_get_clean();

if ( !empty($output) ) {
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
            <select name="<?php echo esc_attr($name); ?>" class="form-control" id="<?php echo esc_attr( $args['widget_id'] ); ?>_<?php echo esc_attr($key); ?>" <?php if ( !empty($field['placeholder']) ) { ?>
                    data-placeholder="<?php echo esc_attr($field['placeholder']); ?>"
                    <?php } ?>>
                <option value=""><?php echo sprintf(__('Filter by %s', 'wp-job-board'), $field['label']); ?></option>
                <?php echo $output; ?>
            </select>
        </div>
    </div><!-- /.form-group -->
<?php }