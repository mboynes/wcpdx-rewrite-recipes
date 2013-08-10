<?php
/**
 * Recipe 1: Adding custom taxonomies to custom post type rewrites
 */


/**
 * Register our data structures
 *
 * @return void
 */
function recipe_1() {
	register_taxonomy( 'make', 'model', array(
		'label' => 'Makes',
		'rewrite' => array(
			'slug' => 'inventory'
		)
	) );

	register_post_type( 'model', array(
		'public' => true,
		'label' => 'Models',
		'rewrite' => array(
			'slug' => 'inventory/%make%'
		)
	) );
}
add_action( 'init', 'recipe_1' );


/**
 * Filter post type links for models to replace %make% if present
 *
 * @param string $link
 * @param object $post
 * @return string
 */
function recipe_1_link( $link, $post ) {
	if ( 'model' == $post->post_type ) {
		if ( $makes = get_the_terms( $post->ID, 'make' ) ) {
			return str_replace( '%make%', array_pop( $makes )->slug, $link );
		}
	}
	return $link;
}
add_filter( 'post_type_link', 'recipe_1_link', 10, 2 );