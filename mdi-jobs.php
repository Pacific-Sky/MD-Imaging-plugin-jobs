<?php /*
Plugin Name: MDI Jobs Functionality
Plugin URI: http://mdimaging.net
Description: Enables Job Listing and application functions on mdimaging.no-repeat
Version: 0.1
Author: Tyler Shuster
Author URI: https://tyler.shuster.house
*/

/**
* Copyright (c) 2016 Tyler Shuster. All rights reserved.
*
* Released under the GPL license
* http://www.opensource.org/licenses/gpl-license.php
*
* This is an add-on for WordPress
* http://wordpress.org/
*
* **********************************************************************
* This program is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2 of the License, or
* (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
* **********************************************************************
*/





// Register Custom Post Type
function mdi_register_post_type_job() {

	$labels = array(
		'name'                  => 'Job Postings',
		'singular_name'         => 'Job Posting',
		'menu_name'             => 'Job Postings',
		'name_admin_bar'        => 'Job Posting',
		'archives'              => 'Item Archives',
		'parent_item_colon'     => 'Parent Item:',
		'all_items'             => 'All Items',
		'add_new_item'          => 'Add New Job Posting',
		'add_new'               => 'Add New',
		'new_item'              => 'New Job Posting',
		'edit_item'             => 'Edit Job Posting',
		'update_item'           => 'Update Job Posting',
		'view_item'             => 'View Job Posting',
		'search_items'          => 'Search Job Posting',
		'not_found'             => 'Not found',
		'not_found_in_trash'    => 'Not found in Trash',
		'featured_image'        => 'Featured Image',
		'set_featured_image'    => 'Set featured image',
		'remove_featured_image' => 'Remove featured image',
		'use_featured_image'    => 'Use as featured image',
		'insert_into_item'      => 'Insert into job posting',
		'uploaded_to_this_item' => 'Uploaded to this job posting',
		'items_list'            => 'Job posting list',
		'items_list_navigation' => 'Job posting list navigation',
		'filter_items_list'     => 'Filter job posting list',
	);
	$args = array(
		'label'                 => 'Job Postings',
		'description'           => 'Job listings',
		'labels'                => $labels,
		'supports'              => array( 'title', 'editor', 'thumbnail', ),
		'hierarchical'          => false,
		'public'                => true,
		'show_ui'               => true,
		'show_in_menu'          => true,
		'menu_position'         => 5,
		'show_in_admin_bar'     => true,
		'show_in_nav_menus'     => true,
		'can_export'            => true,
		'has_archive'           => true,
		'exclude_from_search'   => false,
		'publicly_queryable'    => true,
		'capability_type'       => 'page',
		'menu_icon' => 'dashicons-megaphone'
	);
	register_post_type( 'job', $args );

}
add_action( 'init', 'mdi_register_post_type_job', 0 );

function mdi_shortcode_job_postings ($atts, $content) {
  $atts = extract(shortcode_atts(array(
		'department' => false
	), $atts));
  $args = array(
    'post_type' => 'job'
  );
  if($department) {
    $args['tax_query'] = array(
      array(
        'taxonomy' => 'department',
        'field' => 'slug',
        'terms' => $department
      )
    );
  }
	$jobs = new WP_Query($args);
	ob_start();
	if($jobs->have_posts()):
		while ($jobs->have_posts()): $jobs->the_post(); ?>
			<h3><?php the_title(); ?></h3>
			<a href="<?php the_permalink(); ?>">View this posting</a>
		<?php endwhile;
	else: ?>
		<h3>No positions currently open</h3>
	<?php endif;
	return ob_get_clean();
}
add_shortcode('mdi_job_postings', 'mdi_shortcode_job_postings');


function mdi_shortcode_job_link($atts, $content) {
	$atts = extract(shortcode_atts(array(
		'text' => 'Apply online'
	), $atts));
	$args = array(
    'post_type' => 'job'
  );
	$jobs = new WP_Query($args);
	ob_start();
	if($jobs->have_posts()):
		?>
		<a href="<?php echo get_bloginfo('url'); ?>/apply-for-job/"><?php echo $text; ?></a>
		<?php
	endif;
	return ob_get_clean();
}
add_shortcode('mdi_job_link', 'mdi_shortcode_job_link');

function mdi_job_rewrite() {
	add_rewrite_rule('^apply-for-job/([0-9]+)/?', 'index.php?jobid=$matches[1]', 'top');
	add_rewrite_rule('^apply-for-job/?', 'index.php?jobid=any', 'top');
}
add_action('init', 'mdi_job_rewrite');

// Register `jobid` as a query value that can be passed as seen in `mdi_job_rewrite`
function mdi_job_query_vars ( $vars ) {
	 $vars[] = 'jobid';
	 return $vars;
}
add_filter('query_vars', 'mdi_job_query_vars');

// Add filter to gravity forms that picks up the job id being queried and adds it to the hidden form field
function gform_field_value_jobid () {
	global $wp;
	if(array_key_exists('jobid', $wp->query_vars) && $wp->query_vars['jobid'] !== 'any') {
		return $wp->query_vars['jobid'];
	}
}
add_filter('gform_field_value_jobid', 'gform_field_value_jobid');

// Add filter to gravity forms that populates one of the fields with all current jobs
function gform_populate_jobs($form) {
	foreach($form['fields'] as &$field){

		if($field['type'] != 'select' || strpos($field['cssClass'], 'job--list') === false)
				continue;

		$posts = get_posts('numberposts=-1&post_status=publish&post_type=job');
		$choices = array();
		if(count($posts) > 0) {
			$choices[] = array('text' => 'Select a Job', 'value' => ' ');
			foreach($posts as $post){
				$choices[] = array('text' => $post->post_title, 'value' => $post->ID);
			}
		} else {
			$choices[] = array('text' => 'no job openings', 'disabled' => 'disabled');
		}

		$field['choices'] = $choices;

	}

	return $form;
}
add_filter('gform_pre_render_1', 'gform_populate_jobs');


function mdi_job_parse_request($query) {
	if(array_key_exists('jobid', $query->query_vars)) {
		add_filter( 'template_include', create_function( '$a', 'return locate_template( array( "single-job.php" ) );' ) );
	}
	return $query;
}
add_action('parse_request', 'mdi_job_parse_request');

require 'vendor/plugin-update-checker/plugin-update-checker.php';
	Puc_v4_Factory::buildUpdateChecker(
		'https://pacificsky.co/plugins/mdi-jobs.json',
		__FILE__,
		'mdi-jobs'
	);
