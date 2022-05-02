<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
global $post;

$socials = WP_Job_Board_Employer::get_post_meta($post->ID, 'socials', true );

if ( !empty($socials) ) {
    ob_start();
    ?>
    <?php foreach ($socials as $social) {
        if ( !empty($social['network']) && !empty($social['url']) ) {
    ?>
        <a href="<?php echo esc_url($social['url']); ?>"><i class="fa fa-<?php echo esc_attr($social['network']); ?>"></i></a>
        <?php } ?>
    <?php }
    $output = ob_get_clean();
    $output = trim($output);
    if ( !empty($output) ) {
    ?>

        <div class="social-job-detail">
            <span class="title">
                <?php esc_html_e('Social Profiles:', 'wp-job-board'); ?>
            </span>
            <?php echo wp_kses_post($output); ?>
        </div>
    <?php
    }
}
?>