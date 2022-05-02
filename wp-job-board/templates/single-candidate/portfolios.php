<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
global $post;

$portfolio_photos = WP_Job_Board_Candidate::get_post_meta($post->ID, 'portfolio_photos', true );

if ( !empty($portfolio_photos) ) {
?>
    <div id="job-candidate-portfolio" class="candidate-detail-portfolio">
    	<h4 class="title"><?php esc_html_e('Portfolio', 'wp-job-board'); ?></h4>
        <?php foreach ($portfolio_photos as $attach_id => $img_url) { ?>
            <div class="education-item">
            	<a href="<?php echo esc_url($img_url); ?>" class="popup-image">
	                <img src="<?php echo esc_url($img_url); ?>" alt="">
	            </a>
            </div>
        <?php } ?>
    </div>
<?php }