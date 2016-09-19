<?php
/**
 * MagpieRSS: a simple RSS integration tool
 *
 * A compiled file for RSS syndication
 *
 * @author Kellan Elliott-McCrea <kellan@protest.net>
 * @version 0.51
 * @license GPL
 *
 * @package External
 * @subpackage MagpieRSS
 * @deprecated 3.0.0 Use SimplePie instead.
 */

/**
 * Deprecated. Use SimplePie (class-simplepie.php) instead.
 */
_deprecated_file( basename( __FILE__ ), '3.0.0', WPINC . '/class-simplepie.php' );

/**
 * Fires before MagpieRSS is loaded, to optionally replace it.
 *
 * @since 2.3.0
 * @deprecated 3.0.0
 */
do_action( 'load_feed_engine' );

/** RSS feed constant. */
define('RSS', 'RSS');
define('ATOM', 'Atom');
define('MAGPIE_USER_AGENT', 'WordPress/' . $GLOBALS['wp_version']);

if ( !function_exists('fetch_rss') ) :
/**
 * Build Magpie object based on RSS from URL.
 *
 * @since 1.5.0
 * @package External
 * @subpackage MagpieRSS
 *
 * @param string $url URL to retrieve feed
 * @return bool|MagpieRSS false on failure or MagpieRSS object on success.
 */
function fetch_rss ($url) {
	// initialize constants
	init();

	if ( !isset($url) ) {
		// error("fetch_rss called without a url");
		return false;
	}

	// if cache is disabled
	if ( !MAGPIE_CACHE_ON ) {
		// fetch file, and parse it
		$resp = _fetch_remote_file( $url );
		if ( is_success( $resp->status ) ) {
			return _response_to_rss( $resp );
		}
		else {
			// error("Failed to fetch $url and cache is off");
			return false;
		}
	}
	// else cache is ON
	else {
		// Flow
		// 1. check cache
		// 2. if there is a hit, make sure it's fresh
		// 3. if cached obj fails freshness check, fetch remote
		// 4. if remote fails, return stale object, or error

		$cache = new RSSCache( MAGPIE_CACHE_DIR, MAGPIE_CACHE_AGE );

		if (MAGPIE_DEBUG and $cache->ERROR) {
			debug($cache->ERROR, E_USER_WARNING);
		}

		$cache_status 	 = 0;		// response of check_cache
		$request_headers = array(); // HTTP headers to send with fetch
		$rss 			 = 0;		// parsed RSS object
		$errormsg		 = 0;		// errors, if any

		if (!$cache->ERROR) {
			// return cache HIT, MISS, or STALE
			$cache_status = $cache->check_cache( $url );
		}

		// if object cached, and cache is fresh, return cached obj
		if ( $cache_status == 'HIT' ) {
			$rss = $cache->get( $url );
			if ( isset($rss) and $rss ) {
				$rss->from_cache = 1;
				if ( MAGPIE_DEBUG > 1) {
				debug("MagpieRSS: Cache HIT", E_USER_NOTICE);
			}
				return $rss;
			}
		}

		// else attempt a conditional get

		// set up headers
		if ( $cache_status == 'STALE' ) {
			$rss = $cache->get( $url );
			if ( isset($rss->etag) and $rss->last_modified ) {
				$request_headers['If-None-Match'] = $rss->etag;
				$request_headers['If-Last-Modified'] = $rss->last_modified;
			}
		}

		$resp = _fetch_remote_file( $url, $request_headers );

		if (isset($resp) and $resp) {
			if ($resp->status == '304' ) {
				// we have the most current copy
				if ( MAGPIE_DEBUG > 1) {
					debug("Got 304 for $url");
				}
				// reset cache on 304 (at minutillo insistent prodding)
				$cache->set($url, $rss);
				return $rss;
			}
			elseif ( is_success( $resp->status ) ) {
				$rss = _response_to_rss( $resp );
				if ( $rss ) {
					if (MAGPIE_DEBUG > 1) {
						debug("Fetch successful");
					}
					// add object to cache
					$cache->set( $url, $rss );
					return $rss;
				}
			}
			else {
				$errormsg = "Failed to fetch $url. ";
				if ( $resp->error ) {
					# compensate for Snoopy's annoying habbit to tacking
					# on '\n'
					$http_error = substr($resp->error, 0, -2);
					$errormsg .= "(HTTP Error: $http_error)";
				}
				else {
					$errormsg .=  "(HTTP Response: " . $resp->response_code .')';
				}
			}
		}
		else {
			$errormsg = "Unable to retrieve RSS file for unknown reasons.";
		}

		// else fetch failed

		// attempt to return cached object
		if ($rss) {
			if ( MAGPIE_DEBUG ) {
				debug("Returning STALE object for $url");
			}
			return $rss;
		}

		// else we totally failed
		// error( $errormsg );

		return false;

	} // end if ( !MAGPIE_CACHE_ON ) {
} // end fetch_rss()
endif;

/**
 * Retrieve URL headers and content using WP HTTP Request API.
 *
 * @since 1.5.0
 * @package External
 * @subpackage MagpieRSS
 *
 * @param string $url URL to retrieve
 * @param array $headers Optional. Headers to send to the URL.
 * @return Snoopy style response
 */
function _fetch_remote_file($url, $headers = "" ) {
	$resp = wp_safe_remote_request( $url, array( 'headers' => $headers, 'timeout' => MAGPIE_FETCH_TIME_OUT ) );
	if ( is_wp_error($resp) ) {
		$error = array_shift($resp->errors);

		$resp = new stdClass;
		$resp->status = 500;
		$resp->response_code = 500;
		$resp->error = $error[0] . "\n"; //\n = Snoopy compatibility
		return $resp;
	}

	// Snoopy returns headers unprocessed.
	// Also note, WP_HTTP lowercases all keys, Snoopy did not.
	$return_headers = array();
	foreach ( wp_remote_retrieve_headers( $resp ) as $key => $value ) {
		if ( !is_array($value) ) {
			$return_headers[] = "$key: $value";
		} else {
			foreach ( $value as $v )
				$return_headers[] = "$key: $v";
		}
	}

	$response = new stdClass;
	$response->status = wp_remote_retrieve_response_code( $resp );
	$response->response_code = wp_remote_retrieve_response_code( $resp );
	$response->headers = $return_headers;
	$response->results = wp_remote_retrieve_body( $resp );

	return $response;
}

/**
 * Retrieve
 *
 * @since 1.5.0
 * @package External
 * @subpackage MagpieRSS
 *
 * @param array $resp
 * @return MagpieRSS|bool
 */
function _response_to_rss ($resp) {
	$rss = new MagpieRSS( $resp->results );

	// if RSS parsed successfully
	if ( $rss && (!isset($rss->ERROR) || !$rss->ERROR) ) {

		// find Etag, and Last-Modified
		foreach ( (array) $resp->headers as $h) {
			// 2003-03-02 - Nicola Asuni (www.tecnick.com) - fixed bug "Undefined offset: 1"
			if (strpos($h, ": ")) {
				list($field, $val) = explode(": ", $h, 2);
			}
			else {
				$field = $h;
				$val = "";
			}

			if ( $field == 'etag' ) {
				$rss->etag = $val;
			}

			if ( $field == 'last-modified' ) {
				$rss->last_modified = $val;
			}
		}

		return $rss;
	} // else construct error message
	else {
		$errormsg = "Failed to parse RSS file.";

		if ($rss) {
			$errormsg .= " (" . $rss->ERROR . ")";
		}
		// error($errormsg);

		return false;
	} // end if ($rss and !$rss->error)
}

/**
 * Set up constants with default values, unless user overrides.
 *
 * @since 1.5.0
 * @package External
 * @subpackage MagpieRSS
 */
function init () {
	if ( defined('MAGPIE_INITALIZED') ) {
		return;
	}
	else {
		define('MAGPIE_INITALIZED', 1);
	}

	if ( !defined('MAGPIE_CACHE_ON') ) {
		define('MAGPIE_CACHE_ON', 1);
	}

	if ( !defined('MAGPIE_CACHE_DIR') ) {
		define('MAGPIE_CACHE_DIR', './cache');
	}

	if ( !defined('MAGPIE_CACHE_AGE') ) {
		define('MAGPIE_CACHE_AGE', 60*60); // one hour
	}

	if ( !defined('MAGPIE_CACHE_FRESH_ONLY') ) {
		define('MAGPIE_CACHE_FRESH_ONLY', 0);
	}

		if ( !defined('MAGPIE_DEBUG') ) {
		define('MAGPIE_DEBUG', 0);
	}

	if ( !defined('MAGPIE_USER_AGENT') ) {
		$ua = 'WordPress/' . $GLOBALS['wp_version'];

		if ( MAGPIE_CACHE_ON ) {
			$ua = $ua . ')';
		}
		else {
			$ua = $ua . '; No cache)';
		}

		define('MAGPIE_USER_AGENT', $ua);
	}

	if ( !defined('MAGPIE_FETCH_TIME_OUT') ) {
		define('MAGPIE_FETCH_TIME_OUT', 2);	// 2 second timeout
	}

	// use gzip encoding to fetch rss files if supported?
	if ( !defined('MAGPIE_USE_GZIP') ) {
		define('MAGPIE_USE_GZIP', true);
	}
}

function is_info ($sc) {
	return $sc >= 100 && $sc < 200;
}

function is_success ($sc) {
	return $sc >= 200 && $sc < 300;
}

function is_redirect ($sc) {
	return $sc >= 300 && $sc < 400;
}

function is_error ($sc) {
	return $sc >= 400 && $sc < 600;
}

function is_client_error ($sc) {
	return $sc >= 400 && $sc < 500;
}

function is_server_error ($sc) {
	return $sc >= 500 && $sc < 600;
}

if ( !function_exists('parse_w3cdtf') ) :
function parse_w3cdtf ( $date_str ) {

	# regex to match wc3dtf
	$pat = "/(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2})(:(\d{2}))?(?:([-+])(\d{2}):?(\d{2})|(Z))?/";

	if ( preg_match( $pat, $date_str, $match ) ) {
		list( $year, $month, $day, $hours, $minutes, $seconds) =
			array( $match[1], $match[2], $match[3], $match[4], $match[5], $match[7]);

		# calc epoch for current date assuming GMT
		$epoch = gmmktime( $hours, $minutes, $seconds, $month, $day, $year);

		$offset = 0;
		if ( $match[11] == 'Z' ) {
			# zulu time, aka GMT
		}
		else {
			list( $tz_mod, $tz_hour, $tz_min ) =
				array( $match[8], $match[9], $match[10]);

			# zero out the variables
			if ( ! $tz_hour ) { $tz_hour = 0; }
			if ( ! $tz_min ) { $tz_min = 0; }

			$offset_secs = (($tz_hour*60)+$tz_min)*60;

			# is timezone ahead of GMT?  then subtract offset
			#
			if ( $tz_mod == '+' ) {
				$offset_secs = $offset_secs * -1;
			}

			$offset = $offset_secs;
		}
		$epoch = $epoch + $offset;
		return $epoch;
	}
	else {
		return -1;
	}
}
endif;

if ( !function_exists('wp_rss') ) :
/**
 * Display all RSS items in a HTML ordered list.
 *
 * @since 1.5.0
 * @package External
 * @subpackage MagpieRSS
 *
 * @param string $url URL of feed to display. Will not auto sense feed URL.
 * @param int $num_items Optional. Number of items to display, default is all.
 */
function wp_rss( $url, $num_items = -1 ) {
	if ( $rss = fetch_rss( $url ) ) {
		echo '<ul>';

		if ( $num_items !== -1 ) {
			$rss->items = array_slice( $rss->items, 0, $num_items );
		}

		foreach ( (array) $rss->items as $item ) {
			printf(
				'<li><a href="%1$s" title="%2$s">%3$s</a></li>',
				esc_url( $item['link'] ),
				esc_attr( strip_tags( $item['description'] ) ),
				esc_html( $item['title'] )
			);
		}

		echo '</ul>';
	} else {
		_e( 'An error has occurred, which probably means the feed is down. Try again later.' );
	}
}
endif;

if ( !function_exists('get_rss') ) :
/**
 * Display RSS items in HTML list items.
 *
 * You have to specify which HTML list you want, either ordered or unordered
 * before using the function. You also have to specify how many items you wish
 * to display. You can't display all of them like you can with wp_rss()
 * function.
 *
 * @since 1.5.0
 * @package External
 * @subpackage MagpieRSS
 *
 * @param string $url URL of feed to display. Will not auto sense feed URL.
 * @param int $num_items Optional. Number of items to display, default is all.
 * @return bool False on failure.
 */
function get_rss ($url, $num_items = 5) { // Like get posts, but for RSS
	$rss = fetch_rss($url);
	if ( $rss ) {
		$rss->items = array_slice($rss->items, 0, $num_items);
		foreach ( (array) $rss->items as $item ) {
			echo "<li>\n";
			echo "<a href='$item[link]' title='$item[description]'>";
			echo esc_html($item['title']);
			echo "</a><br />\n";
			echo "</li>\n";
		}
	} else {
		return false;
	}
}
endif;
