<?php
/**
 * Recipe 2: Making one custom taxonomy a "parent" of another
 */



/**
 * Register our data structures
 *
 * @return void
 */
function recipe_2() {
	register_taxonomy( 'body_type', 'model', array(
		'label' => 'Body Types',
		'rewrite' => array( 'slug' => 'inventory' )
	) );

	add_permastruct( "all_makes", "inventory/all/%make%" );
	register_taxonomy( 'make', 'model', array(
		'label' => 'Makes',
		'rewrite' => array( 'slug' => 'inventory/%body_type%' )
	) );

	register_post_type( 'model', array(
		'public' => true,
		'label' => 'Models',
		'rewrite' => array( 'slug' => 'inventory/%body_type%/%make%' )
	) );
}
add_action( 'init', 'recipe_2' );


/**
 * Filter post type links for models to replace %body_type% & %make% if present
 *
 * @param string $link
 * @param object $post
 * @return string
 */
function recipe_2_car_links( $link, $post ) {
	if ( 'model' == $post->post_type ) {
		if ( $body_types = get_the_terms( $post->ID, 'body_type' ) ) {
			$link = str_replace( '%body_type%', array_pop( $body_types )->slug, $link );
		}
		if ( $makes = get_the_terms( $post->ID, 'make' ) ) {
			$link = str_replace( '%make%', array_pop( $makes )->slug, $link );
		}
	}
	return $link;
}
add_filter( 'post_type_link', 'recipe_2_car_links', 10, 2 );


/**
 * Filter term links for makes to replace %body_type% with "all"
 *
 * @param string $termlink
 * @param object $term
 * @param string $taxonomy
 * @return string
 */
function recipe_2_make_links( $termlink, $term, $taxonomy ) {
	if ( 'make' == $taxonomy ) {
		return str_replace( '%body_type%', 'all', $termlink );
	}
	return $termlink;
}
add_filter( 'term_link', 'recipe_2_make_links', 10, 3 );
