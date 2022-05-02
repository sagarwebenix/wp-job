<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

header('Content-Type: ' . feed_content_type('rss-http') . '; charset=' . get_option('blog_charset'), true);

$query_args = array(
	'post_type' => 'job_listing',
    'post_status' => 'publish',
    'post_per_page' => -1,
    'fields' => 'ids'
);
$params = true;
if ( WP_Job_Board_Job_Filter::has_filter() ) {
	$params = $_GET;
}
$loop = WP_Job_Board_Query::get_posts($query_args, $params);

echo '<?xml version="1.0" encoding="' . get_option('blog_charset') . '"?' . '>';
?>
<rss version="2.0"
     xmlns:content="http://purl.org/rss/1.0/modules/content/"
     xmlns:wfw="http://wellformedweb.org/CommentAPI/"
     xmlns:dc="http://purl.org/dc/elements/1.1/"
     xmlns:atom="http://www.w3.org/2005/Atom"
     xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
     xmlns:slash="http://purl.org/rss/1.0/modules/slash/"
     <?php do_action('rss2_ns'); ?>>
    <channel>
        <title><?php bloginfo_rss('name'); ?> - Feed</title>
        <atom:link href="<?php self_link(); ?>" rel="self" type="application/rss+xml" />
        <link><?php bloginfo_rss('url') ?></link>
        <description><?php bloginfo_rss('description') ?></description>
        <lastBuildDate><?php echo mysql2date('D, d M Y H:i:s +0000', get_lastpostmodified('GMT'), false); ?></lastBuildDate>
        <language><?php echo get_option('rss_language'); ?></language>
        <sy:updatePeriod><?php echo apply_filters('rss_update_period', 'hourly'); ?></sy:updatePeriod>
        <sy:updateFrequency><?php echo apply_filters('rss_update_frequency', '1'); ?></sy:updateFrequency>
        <?php do_action('rss2_head'); ?>
        <?php
        if ( !empty($loop->posts) ) {
        	foreach ($loop->posts as $job_id) {
        		
                $expiry_date = get_post_meta($job_id, WP_JOB_BOARD_JOB_LISTING_PREFIX.'expiry_date', true);
                if ($expiry_date != '') {
                    $expiry_date = mysql2date('D, d M Y H:i:s +0000', $expiry_date, false);
                }
                $post_content = get_post_field('post_content', $job_id);
                $post_content = apply_filters('the_content', $post_content);
                $user_id = get_post_field ('post_author', $job_id);
                $employer_name = $post_thumbnail_src = '';
                if ( WP_Job_Board_User::is_employer($user_id) ) {
	                $employer_id = WP_Job_Board_User::get_employer_by_user_id($user_id);
	                if ( !empty($employer_id) ) {
	                    $employer_name = get_the_title($employer_id);
	                    $post_thumbnail_id = get_post_thumbnail_id($employer_id);
	                    $post_thumbnail_image = wp_get_attachment_image_src($post_thumbnail_id, 'thumbnail');
		                $post_thumbnail_src = isset($post_thumbnail_image[0]) && esc_url($post_thumbnail_image[0]) != '' ? $post_thumbnail_image[0] : '';
	                }
                } else {
                	$employer_name = get_the_author_meta('display_name');
                }
                
                
                $address = get_post_meta($job_id, WP_JOB_BOARD_JOB_LISTING_PREFIX.'address', true);
                
                
                $job_salary = WP_Job_Board_Job_Listing::get_salary_html($job_id, false);
                
                $categories = wp_get_post_terms($job_id, 'job_listing_category');
                $category = isset($categories[0]->name) ? $categories[0]->name : '';
                $job_types = wp_get_post_terms($job_id, 'job_listing_type');
                $job_type = isset($job_types[0]->name) ? $job_types[0]->name : '';
                ?>
                <item>
                    <title><?php the_title_rss(); ?></title>
                    <link><?php the_permalink_rss(); ?></link>
                    <pubDate><?php echo mysql2date('D, d M Y H:i:s +0000', get_post_time('Y-m-d H:i:s', true), false); ?></pubDate>
                        
                    <expiryDate><?php echo $expiry_date; ?></expiryDate>
                    <salary><![CDATA[<?php echo $job_salary; ?>]]></salary>
                        
                    <employer><![CDATA[<?php echo $employer_name; ?>]]></employer>
                    <employerImg><![CDATA[<?php echo $post_thumbnail_src; ?>]]></employerImg>
                    
                    <location><![CDATA[<?php echo $address; ?>]]></location>
                    <category><![CDATA[<?php echo $category; ?>]]></category>
                    <type><![CDATA[<?php echo $job_type; ?>]]></type>
                        
                    <excerpt><![CDATA[<?php the_excerpt_rss(); ?>]]></excerpt>
                    <description><![CDATA[<?php echo $post_content; ?>]]></description>
                    <?php rss_enclosure(); ?>
                    <?php do_action('rss2_item'); ?>
                </item>
                <?php
            }
        }
        ?>
    </channel>
</rss>