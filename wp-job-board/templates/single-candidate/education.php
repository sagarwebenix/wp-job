<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
global $post;

$education = WP_Job_Board_Candidate::get_post_meta($post->ID, 'education', true );

if ( !empty($education) ) {
?>
    <div id="job-candidate-education" class="candidate-detail-education my_resume_eduarea">
        <h4 class="title"><?php esc_html_e('Education', 'wp-job-board'); ?></h4>
        <?php foreach ($education as $item) { ?>

            <div class="content">
                <div class="circle bgc-thm"></div>
                <p class="edu_center">
                    <?php if ( !empty($item['academy']) ) { ?>
                        <span class="university"><?php echo $item['academy']; ?></span>
                    <?php } ?>
                    <?php if ( !empty($item['year']) ) { ?>
                        <small class="year"><?php echo $item['year']; ?></small>
                    <?php } ?>
                </p>
                <?php if ( !empty($item['title']) ) { ?>
                    <h4 class="edu_stats"><?php echo $item['title']; ?></h4>
                <?php } ?>
                <?php if ( !empty($item['description']) ) { ?>
                    <p class="mb0"><?php echo $item['description']; ?></p>
                <?php } ?>
            </div>
            
        <?php } ?>
    </div>
<?php }