<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
global $post;

$profile_photos = WP_Job_Board_Employer::get_post_meta($post->ID, 'profile_photos', true );

if ( !empty($profile_photos) ) {
?>
    <div id="job-employer-portfolio" class="employer-detail-portfolio">
    	<h4 class="title"><?php esc_html_e('Office Photos', 'wp-job-board'); ?></h4>
        <?php foreach ($profile_photos as $attach_id => $img_url) { ?>
            <div class="photo-item">
            	<a href="<?php echo esc_url($img_url); ?>" class="popup-image">
                	<img src="<?php echo esc_url($img_url); ?>" alt="">
                </a>
            </div>
        <?php } ?>
    </div>
<?php }