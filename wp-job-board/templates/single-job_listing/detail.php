<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
global $post;

$salary = WP_Job_Board_Job_Listing::get_salary_html($post->ID);
$custom_fields = WP_Job_Board_Post_Type_Job_Custom_Fields::get_custom_fields('job_cfield');
?>
<div class="job-detail-detail">
    <ul class="list">
        <?php if ( $salary ) { ?>
            <li>
                <div class="icon">
                    <i class="flaticon-money"></i>
                </div>
                <div class="details">
                    <div class="text"><?php esc_html_e('Offered Salary', 'wp-job-board'); ?></div>
                    <div class="value"><?php echo wp_kses_post($salary); ?></div>
                </div>
            </li>
        <?php } ?>
        <?php if ( $custom_fields ) { ?>
            <?php foreach ($custom_fields as $cpost) {
                $meta_key = WP_Job_Board_Post_Type_Job_Custom_Fields::generate_key_id(WP_JOB_BOARD_JOB_LISTING_PREFIX, $cpost->post_name);
                $value = get_post_meta( $post->ID, $meta_key, true );
                $icon_class = get_post_meta( $cpost->ID, WP_JOB_BOARD_JOB_CUSTOM_FIELD_PREFIX .'icon_class', true );

                if ( !empty($value) ) {
                    ?>
                    <li>
                        <div class="icon">
                            <?php if ( !empty($icon_class) ) { ?>
                                <i class="<?php echo esc_attr($icon_class); ?>"></i>
                            <?php } ?>
                        </div>
                        <div class="details">
                            <div class="text"><?php echo wp_kses_post($cpost->post_title); ?></div>
                            <div class="value"><?php echo WP_Job_Board_Post_Type_Job_Custom_Fields::display_field($cpost, $value); ?></div>
                        </div>
                    </li>
                    <?php
                }
            ?>
            
            <?php } ?>
        <?php } ?>
    </ul>
</div>