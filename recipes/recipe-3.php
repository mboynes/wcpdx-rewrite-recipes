<?php

# To get the the templates working (as wordcamp.php, wordcamp-schedule.php, etc.),
# we can either add this to single-wordcamp.php:
# get_template_part( 'wordcamp', get_query_var( 'wc_section' ) );
# or we can alter the template hierarchy as follows:
function recipe_3_templates( $template = '' ) {
	$object = get_queried_object();

	if ( $object && 'wordcamp' == $object->post_type ) {
		$templates = array();
		if ( $section = get_query_var( 'wc_section' ) )
			$templates[] = "wordcamp-{$section}.php";
		$templates[] = 'wordcamp.php';

		if ( $new_template = locate_template( $templates ) )
			return $new_template;
	}

	return $template;
}
add_filter( 'single_template', 'recipe_3_templates' );


function recipe_3() {
	add_rewrite_tag( '%wc_section%', '(schedule|speakers|sponsors|tickets|location)' );
	add_rewrite_rule( 'wordcamp/([^/]+)/(schedule|speakers|sponsors|tickets|location)/?$', 'index.php?wordcamp=$matches[1]&wc_section=$matches[2]', 'top' );

	# Instead of using add_rewrite_rule, we could use add_permastruct to give us feeds and pagination
	# add_rewrite_tag( '%wc%', '([^/]+)', 'wordcamp=' );
	# add_permastruct( 'wc_section', 'wordcamp/%wc%/%wc_section%', array(
	# 	'with_front'  => true,
	# 	'ep_mask'     => EP_NONE,
	# 	'paged'       => true,
	# 	'feed'        => true,
	# 	'forcomments' => false,
	# 	'walk_dirs'   => false, # this is not the default
	# 	'endpoints'   => true
	# ) );
}
add_action( 'init', 'recipe_3' );


# If we wanted to use the %wordcamp% tag instead of %wc% in add_permastruct, we could use this filter
# to clean out the generated rewrite rules:
# add_filter( 'wc_section_rewrite_rules', 'function_that_pulls_out_attachment_rules' );


# Prerequisite post type and meta fields for Recipe 3: Creating Post "Sections"
function recipe_3_prereq() {
	register_post_type( 'wordcamp', array(
		'public' => true,
		'label' => 'WordCamps'
	) );

	# See http://fieldmanager.org
	if ( class_exists( 'Fieldmanager_Field' ) ) {

		$fm = new Fieldmanager_Group( array(
			'name' => 'Sections',
			'tabbed' => true,
			'children' => array(
				'schedule' => new Fieldmanager_Group( array(
					'label' => 'Schedule',
					'children' => array(
						'content' => new Fieldmanager_RichTextArea()
					)
				) ),
				'speakers' => new Fieldmanager_Group( array(
					'label' => 'Speakers',
					'children' => array(
						'speaker' => new Fieldmanager_Group( array(
							'limit'          => 0,
							'label'          => 'New Speaker',
							'label_macro'    => array( 'Speaker: %s', 'name' ),
							'add_more_label' => 'Add another speaker',
							'collapsible'    => true,
							'sortable'       => true,
							'children'       => array(
								'name'         => new Fieldmanager_TextField( 'Name' ),
								'image'        => new Fieldmanager_Media( 'Image' ),
								'email'        => new Fieldmanager_TextField( 'Email' ),
								'twitter'      => new FieldManager_TextField( 'Twitter Handle (omit the @)' ),
								'bio'          => new Fieldmanager_RichTextArea( 'Bio' ),
								'presentation' => new FieldManager_TextArea( 'Presentation Title', array( 'attributes' => array( 'style' => 'width:100%;height:50px' ) ) )
							)
						) )
					)
				) ),
				'sponsors' => new Fieldmanager_Group( array(
					'label' => 'Sponsors',
					'children' => array(
						'speaker' => new Fieldmanager_Group( array(
							'limit'          => 0,
							'label'          => 'New Sponsor',
							'label_macro'    => array( 'Sponsor: %s', 'name' ),
							'add_more_label' => 'Add another sponsor',
							'collapsible'    => true,
							'sortable'       => true,
							'children'       => array(
								'name'        => new Fieldmanager_TextField( 'Name' ),
								'image'       => new Fieldmanager_Media( 'Image' ),
								'twitter'     => new FieldManager_TextField( 'Twitter Handle (omit the @)' ),
								'website'     => new FieldManager_TextField( 'Website URL' ),
								'description' => new Fieldmanager_RichTextArea( 'Description' )
							)
						) )
					)
				) ),
				'tickets' => new Fieldmanager_Group( array(
					'label' => 'Tickets',
					'children' => array(
						'content' => new Fieldmanager_RichTextArea()
					)
				) ),
				'location' => new Fieldmanager_Group( array(
					'label' => 'Location',
					'children' => array(
						'address' => new FieldManager_TextField( 'Address or Lat/Long (for Google Map)' ),
						'content' => new Fieldmanager_RichTextArea( 'Content' )
					)
				) )
			)
		) );
		$fm->add_meta_box( __( 'Sections', 'wcpdx' ), 'wordcamp' );

	}
}
add_action( 'init', 'recipe_3_prereq' );
