<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
global $post;

$categories = get_the_terms( $post->ID, 'employer_category' );

$custom_fields = WP_Job_Board_Post_Type_Job_Custom_Fields::get_custom_fields('candidate_cfield');
?>
<div class="candidate-detail-detail">
    <ul class="list">
        <?php if ( $custom_fields ) { ?>
            <?php foreach ($custom_fields as $cpost) {
                $value = WP_Job_Board_Candidate::get_post_meta( $post->ID, 'custom_'. $cpost->post_name, true );
                $icon_class = get_post_meta( $cpost->ID, WP_JOB_BOARD_CANDIDATE_CUSTOM_FIELD_PREFIX .'icon_class', true );

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

        <?php if ( $categories ) { ?>
            <li>
                <div class="icon">
                    <i class="flaticon-2-squares"></i>
                </div>
                <div class="details">
                    <div class="text"><?php esc_html_e('Categories', 'wp-job-board'); ?></div>
                    <div class="value">
                        <?php foreach ($categories as $term) { ?>
                            <a href="<?php echo get_term_link($term); ?>"><?php echo esc_html($term->name); ?></a>
                        <?php } ?>
                    </div>
                </div>
            </li>
        <?php } ?>

    </ul>
</div>