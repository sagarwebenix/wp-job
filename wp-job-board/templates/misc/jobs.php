<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

echo WP_Job_Board_Template_Loader::get_template_part('loop/job/archive-inner', array('jobs' => $jobs));
