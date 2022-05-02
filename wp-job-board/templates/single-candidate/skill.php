<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
global $post;

$skill = WP_Job_Board_Candidate::get_post_meta($post->ID, 'skill', true );

if ( !empty($skill) ) {
?>
    <div id="job-candidate-skill" class="candidate-detail-skill candidate_resume_skill">
        <h4 class="title"><?php esc_html_e('Skills', 'wp-job-board'); ?></h4>
        <div class="progress-levels">
            <?php $i=1; foreach ($skill as $item) {
                $delay = $i*100;
            ?>
                <div class="progress-box wow animated" data-wow-delay="<?php echo esc_attr($delay); ?>ms" data-wow-duration="1500ms">

                    <?php if ( !empty($item['title']) ) { ?>
                        <h5 class="box-title"><?php echo $item['title']; ?></h5>
                    <?php } ?>
                    
                    <?php if ( !empty($item['percentage']) ) { ?>
                        <div class="inner">
                            <div class="bar">
                                <div class="bar-innner"><div class="bar-fill ulockd-bgthm" data-percent="<?php echo esc_attr($item['percentage']); ?>" style="width: <?php echo trim($item['percentage']); ?>%;"><div class="percent"><?php echo esc_html($item['percentage']); ?>%</div></div></div>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            <?php $i++; } ?>
        </div>
    </div>
<?php }