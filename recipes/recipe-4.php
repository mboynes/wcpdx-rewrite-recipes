<?php
/**
 * Recipe 4: Permanence
 */

if ( defined( 'WP_CLI' ) && WP_CLI )
    require_once __DIR__  . '/recipe-4-cli.php';


/**
 * Attempt to redirect 404s based on previous rewrite rules
 */
if ( !class_exists( 'WCPDX_Redirects' ) ) :

class WCPDX_Redirects {

	private static $instance;

	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new WCPDX_Redirects;
		}
		return self::$instance;
	}


	/**
	 * If the current request is a 404, attempt to find a match in old rewrite rules
	 *
	 * @return void
	 */
	public function redirect() {
		if ( is_404() ) {
			$redirects = $this->get_rewrite_rules();
			$matched_rule = false;
			$request = $this->get_request();
			foreach ( (array) $redirects as $match => $query ) {
				if ( preg_match("#^$match#", $request, $matches) ||
					preg_match("#^$match#", urldecode($request), $matches) ) {

					// Got a match.
					$matched_rule = true;
					break;
				}
			}
			if ( $matched_rule ) {
				$query = preg_replace("!^.+\?!", '', $query);
				$redirect = addslashes(WP_MatchesMapRegex::apply($query, $matches));
				if ( $redirect ) {
					wp_redirect( $this->clean_url( home_url( "?$redirect" ) ), 301 );
					exit;
				}
			}
		}
	}


	/**
	 * Get the request that we'll test against the old rewrite rules.
	 * This is pulled right out of core and accounts for many rare
	 * circumstances.
	 *
	 * @return string URL
	 */
	public function get_request() {
		global $wp_rewrite;

		if ( isset($_SERVER['PATH_INFO']) )
			$pathinfo = $_SERVER['PATH_INFO'];
		else
			$pathinfo = '';
		$pathinfo_array = explode('?', $pathinfo);
		$pathinfo = str_replace("%", "%25", $pathinfo_array[0]);
		$req_uri = $_SERVER['REQUEST_URI'];
		$req_uri_array = explode('?', $req_uri);
		$req_uri = $req_uri_array[0];
		$self = $_SERVER['PHP_SELF'];
		$home_path = parse_url(home_url());
		if ( isset($home_path['path']) )
			$home_path = $home_path['path'];
		else
			$home_path = '';
		$home_path = trim($home_path, '/');

		// Trim path info from the end and the leading home path from the
		// front. For path info requests, this leaves us with the requesting
		// filename, if any. For 404 requests, this leaves us with the
		// requested permalink.
		$req_uri = str_replace($pathinfo, '', $req_uri);
		$req_uri = trim($req_uri, '/');
		$req_uri = preg_replace("|^$home_path|i", '', $req_uri);
		$req_uri = trim($req_uri, '/');
		$pathinfo = trim($pathinfo, '/');
		$pathinfo = preg_replace("|^$home_path|i", '', $pathinfo);
		$pathinfo = trim($pathinfo, '/');


		// The requested permalink is in $pathinfo for path info requests and
		//  $req_uri for other requests.
		if ( ! empty($pathinfo) && !preg_match('|^.*' . $wp_rewrite->index . '$|', $pathinfo) ) {
			$request = $pathinfo;
		} else {
			// If the request uri is the index, blank it out so that we don't try to match it against a rule.
			if ( $req_uri == $wp_rewrite->index )
				$req_uri = '';
			$request = $req_uri;
		}

		return $request;
	}


	/**
	 * Old rewrite rules go here.
	 *
	 * @return array
	 */
	public function get_rewrite_rules() {
		return array(
			'archives/category/(.+?)/feed/(feed|rdf|rss|rss2|atom)/?$' => 'index.php?category_name=$matches[1]&feed=$matches[2]',
			'archives/category/(.+?)/(feed|rdf|rss|rss2|atom)/?$' => 'index.php?category_name=$matches[1]&feed=$matches[2]',
			'archives/category/(.+?)/page/?([0-9]{1,})/?$' => 'index.php?category_name=$matches[1]&paged=$matches[2]',
			'archives/category/(.+?)/?$' => 'index.php?category_name=$matches[1]',
			'archives/tag/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$' => 'index.php?tag=$matches[1]&feed=$matches[2]',
			'archives/tag/([^/]+)/(feed|rdf|rss|rss2|atom)/?$' => 'index.php?tag=$matches[1]&feed=$matches[2]',
			'archives/tag/([^/]+)/page/?([0-9]{1,})/?$' => 'index.php?tag=$matches[1]&paged=$matches[2]',
			'archives/tag/([^/]+)/?$' => 'index.php?tag=$matches[1]',
			'archives/type/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$' => 'index.php?post_format=$matches[1]&feed=$matches[2]',
			'archives/type/([^/]+)/(feed|rdf|rss|rss2|atom)/?$' => 'index.php?post_format=$matches[1]&feed=$matches[2]',
			'archives/type/([^/]+)/page/?([0-9]{1,})/?$' => 'index.php?post_format=$matches[1]&paged=$matches[2]',
			'archives/type/([^/]+)/?$' => 'index.php?post_format=$matches[1]',
			'archives/author/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$' => 'index.php?author_name=$matches[1]&feed=$matches[2]',
			'archives/author/([^/]+)/(feed|rdf|rss|rss2|atom)/?$' => 'index.php?author_name=$matches[1]&feed=$matches[2]',
			'archives/author/([^/]+)/page/?([0-9]{1,})/?$' => 'index.php?author_name=$matches[1]&paged=$matches[2]',
			'archives/author/([^/]+)/?$' => 'index.php?author_name=$matches[1]',
			'archives/date/([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/feed/(feed|rdf|rss|rss2|atom)/?$' => 'index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&feed=$matches[4]',
			'archives/date/([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/(feed|rdf|rss|rss2|atom)/?$' => 'index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&feed=$matches[4]',
			'archives/date/([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/page/?([0-9]{1,})/?$' => 'index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&paged=$matches[4]',
			'archives/date/([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/?$' => 'index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]',
			'archives/date/([0-9]{4})/([0-9]{1,2})/feed/(feed|rdf|rss|rss2|atom)/?$' => 'index.php?year=$matches[1]&monthnum=$matches[2]&feed=$matches[3]',
			'archives/date/([0-9]{4})/([0-9]{1,2})/(feed|rdf|rss|rss2|atom)/?$' => 'index.php?year=$matches[1]&monthnum=$matches[2]&feed=$matches[3]',
			'archives/date/([0-9]{4})/([0-9]{1,2})/page/?([0-9]{1,})/?$' => 'index.php?year=$matches[1]&monthnum=$matches[2]&paged=$matches[3]',
			'archives/date/([0-9]{4})/([0-9]{1,2})/?$' => 'index.php?year=$matches[1]&monthnum=$matches[2]',
			'archives/date/([0-9]{4})/feed/(feed|rdf|rss|rss2|atom)/?$' => 'index.php?year=$matches[1]&feed=$matches[2]',
			'archives/date/([0-9]{4})/(feed|rdf|rss|rss2|atom)/?$' => 'index.php?year=$matches[1]&feed=$matches[2]',
			'archives/date/([0-9]{4})/page/?([0-9]{1,})/?$' => 'index.php?year=$matches[1]&paged=$matches[2]',
			'archives/date/([0-9]{4})/?$' => 'index.php?year=$matches[1]',
			'archives/[0-9]+/attachment/([^/]+)/?$' => 'index.php?attachment=$matches[1]',
			'archives/[0-9]+/attachment/([^/]+)/trackback/?$' => 'index.php?attachment=$matches[1]&tb=1',
			'archives/[0-9]+/attachment/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$' => 'index.php?attachment=$matches[1]&feed=$matches[2]',
			'archives/[0-9]+/attachment/([^/]+)/(feed|rdf|rss|rss2|atom)/?$' => 'index.php?attachment=$matches[1]&feed=$matches[2]',
			'archives/[0-9]+/attachment/([^/]+)/comment-page-([0-9]{1,})/?$' => 'index.php?attachment=$matches[1]&cpage=$matches[2]',
			'archives/([0-9]+)/trackback/?$' => 'index.php?p=$matches[1]&tb=1',
			'archives/([0-9]+)/feed/(feed|rdf|rss|rss2|atom)/?$' => 'index.php?p=$matches[1]&feed=$matches[2]',
			'archives/([0-9]+)/(feed|rdf|rss|rss2|atom)/?$' => 'index.php?p=$matches[1]&feed=$matches[2]',
			'archives/([0-9]+)/page/?([0-9]{1,})/?$' => 'index.php?p=$matches[1]&paged=$matches[2]',
			'archives/([0-9]+)/comment-page-([0-9]{1,})/?$' => 'index.php?p=$matches[1]&cpage=$matches[2]',
			'archives/([0-9]+)(/[0-9]+)?/?$' => 'index.php?p=$matches[1]&page=$matches[2]',
			'archives/[0-9]+/([^/]+)/?$' => 'index.php?attachment=$matches[1]',
			'archives/[0-9]+/([^/]+)/trackback/?$' => 'index.php?attachment=$matches[1]&tb=1',
			'archives/[0-9]+/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$' => 'index.php?attachment=$matches[1]&feed=$matches[2]',
			'archives/[0-9]+/([^/]+)/(feed|rdf|rss|rss2|atom)/?$' => 'index.php?attachment=$matches[1]&feed=$matches[2]',
			'archives/[0-9]+/([^/]+)/comment-page-([0-9]{1,})/?$' => 'index.php?attachment=$matches[1]&cpage=$matches[2]'
		);
	}


	/**
	 * Remove empty URL args
	 *
	 * @param string $url
	 * @return string
	 */
	public function clean_url( $url ) {
		$parsed_url = parse_url( $url );
		wp_parse_str( $parsed_url['query'], $qs );
		$qs = array_filter( $qs );
		return add_query_arg( $qs, "{$parsed_url['scheme']}://{$parsed_url['host']}{$parsed_url['path']}" );
	}
}

function WCPDX_Redirects() {
	return WCPDX_Redirects::instance();
}
add_action( 'template_redirect', array( WCPDX_Redirects(), 'redirect' ), 5 );

endif;