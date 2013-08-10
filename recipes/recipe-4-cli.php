<?php
/*
	Plugin Name: Permalinks WP-CLI Commands
	Plugin URI: http://github.com/mboynes/permalinks-wp-cli
	Description: WP-CLI Commands for working with permalinks
	Version: 0.1
	Author: Matthew Boynes
	Author URI: http://boyn.es/
*/
/*  This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/



WP_CLI::add_command( 'permalinks', 'Permalinks_CLI_Command' );

class Permalinks_CLI_Command extends WP_CLI_Command {

	/**
	 * Prevent memory leaks from growing out of control
	 */
	private function contain_memory_leaks() {
		global $wpdb, $wp_object_cache;
		$wpdb->queries = array();
		if ( !is_object( $wp_object_cache ) )
			return;
		$wp_object_cache->group_ops = array();
		$wp_object_cache->stats = array();
		$wp_object_cache->memcache_debug = array();
		$wp_object_cache->cache = array();
		if ( method_exists( $wp_object_cache, '__remoteset' ) )
			$wp_object_cache->__remoteset();
	}


	/**
	 * Find and update old links in content based on http status.
	 * You should backup your posts table before running this as it modifies post_content.
	 *
	 * @subcommand update-old-permalinks
	 * @synopsis [--per-page=<batch size>]
	 */
	public function update_old_permalinks( $args, $assoc_args ) {
		# Keep tabs on where we are and what we've done
		$per_page = 100;
		if ( isset( $assoc_args['per-page'] ) )
			$per_page = intval( $assoc_args['per-page'] );


		$processed = $updated = $page = 0;
		do {
			$posts = get_posts( array(
				'post_type'      => 'any',
				'post_status'    => 'any',
				'posts_per_page' => $per_page,
				'offset'         => $per_page * $page++
			) );
			if ( !$posts || is_wp_error( $posts ) )
				break;

			foreach ( $posts as $post ) {
				if ( preg_match_all( '!href\s*=\s*([\'"])(?:' . preg_quote( home_url() ) . ')?(/.*)\1!i', $post->post_content, $matches, PREG_SET_ORDER ) ) {
					$modified = false;

					foreach ( $matches as $match ) {
						// WP_CLI::line( "URI found: {$match[2]}" );
						$url = home_url( $match[2] );
						$resulting_url = $this->process_url( $url );
						if ( $resulting_url && $url != $resulting_url ) {
							$post->post_content = preg_replace( '!(?:' . preg_quote( home_url() ) . ")?{$match[2]}!i", $resulting_url, $post->post_content );
							$modified = true;
						}
					}

					if ( $modified ) {
						wp_update_post( $post );
						$updated++;
						WP_CLI::line( "Post {$post->ID} Updated" );
					}
				}

				$processed++;
			}

			$this->contain_memory_leaks();
		} while ( $per_page == count( $posts ) );

		WP_CLI::success( "Process complete! Updated {$updated}/{$processed} posts" );
	}


	/**
	 * Fetch a URL and respond as appropriate. If the response is 200, return the url. If it's a 301, fetch the location sent.
	 * Otherwise, leave it be.
	 *
	 * @param string $url
	 * @return mixed a URL on success and false on failure
	 */
	private function process_url( $url ) {
		$response = wp_remote_head( $url );
		// WP_CLI::line( $url . ': ' . print_r( $response, 1 ) );
		if ( ! is_wp_error( $response ) && isset( $response['response']['code'] ) ) {
			if ( 301 == $response['response']['code'] && isset( $response['headers']['location'] ) ) {
				return $this->process_url( $response['headers']['location'] );
			} elseif ( 200 == $response['response']['code'] ) {
				return $url;
			} elseif ( $response['response']['code'] > 301 && $response['response']['code'] < 400 ) {
				WP_CLI::line( "Redirect found for {$url}, but not permanent: {$response['response']['code']} {$response['response']['message']}" );
			} else {
				WP_CLI::line( "Error fetching {$url}: {$response['response']['code']} {$response['response']['message']}" );
			}
		} else {
			WP_CLI::line( "Error fetching {$url}: General error" );
		}
		return false;
	}

}