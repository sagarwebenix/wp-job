<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
global $post;

$award = WP_Job_Board_Candidate::get_post_meta($post->ID, 'award', true );

if ( !empty($award) ) {
?>
    <div id="job-candidate-award" class="my_resume_eduarea">
        <h4 class="title"><?php esc_html_e('Awards', 'wp-job-board'); ?></h4>
        <?php foreach ($award as $item) { ?>

            <div class="content">
                <div class="circle"></div>
                <?php if ( !empty($item['year']) ) { ?>
                    <div class="edu_center"><span class="year"><?php echo $item['year']; ?></span></div>
                <?php } ?>
                <?php if ( !empty($item['title']) ) { ?>
                    <h4 class="edu_stats"><?php echo $item['title']; ?></h4>
                <?php } ?>
                <?php if ( !empty($item['description']) ) { ?>
                    <div class="mb0"><?php echo $item['description']; ?></div>
                <?php } ?>
            </div>

        <?php } ?>
    </div>
<?php }