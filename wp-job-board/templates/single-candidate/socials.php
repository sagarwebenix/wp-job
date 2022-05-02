<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
global $post;


$socials = WP_Job_Board_Candidate::get_post_meta($post->ID, 'socials', true );

if ( !empty($socials) ) {
    ?>
    <div class="candidate-detail-socials">
        <div class="label">
            <?php esc_html_e('Social Profiles:', 'wp-job-board'); ?>
        </div>
        <ul class="list">
            <?php foreach ($socials as $social) {
                if ( !empty($social['network']) && !empty($social['url']) ) {
            ?>
                <li><a href="<?php echo esc_url($social['url']); ?>"><i class="fa fa-<?php echo esc_attr($social['network']); ?>"></a></li>
            <?php } ?>
            <?php } ?>
        </ul>
    </div>
    <?php
}
?>