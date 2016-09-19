<?php

class RSSCache {
	var $BASE_CACHE;	// where the cache files are stored
	var $MAX_AGE	= 43200;  		// when are files stale, default twelve hours
	var $ERROR 		= '';			// accumulate error messages

	/**
	 * PHP5 constructor.
	 */
	function __construct( $base = '', $age = '' ) {
		$this->BASE_CACHE = WP_CONTENT_DIR . '/cache';
		if ( $base ) {
			$this->BASE_CACHE = $base;
		}
		if ( $age ) {
			$this->MAX_AGE = $age;
		}

	}

	/**
	 * PHP4 constructor.
	 */
	public function RSSCache( $base = '', $age = '' ) {
		self::__construct( $base, $age );
	}

	/*=======================================================================*\
		Function:	set
		Purpose:	add an item to the cache, keyed on url
		Input:		url from wich the rss file was fetched
		Output:		true on success
	\*=======================================================================*/
	function set ($url, $rss) {
		$cache_option = 'rss_' . $this->file_name( $url );

		set_transient($cache_option, $rss, $this->MAX_AGE);

		return $cache_option;
	}

	/*=======================================================================*\
		Function:	get
		Purpose:	fetch an item from the cache
		Input:		url from wich the rss file was fetched
		Output:		cached object on HIT, false on MISS
	\*=======================================================================*/
	function get ($url) {
		$this->ERROR = "";
		$cache_option = 'rss_' . $this->file_name( $url );

		if ( ! $rss = get_transient( $cache_option ) ) {
			$this->debug(
				"Cache doesn't contain: $url (cache option: $cache_option)"
			);
			return 0;
		}

		return $rss;
	}

	/*=======================================================================*\
		Function:	check_cache
		Purpose:	check a url for membership in the cache
					and whether the object is older then MAX_AGE (ie. STALE)
		Input:		url from wich the rss file was fetched
		Output:		cached object on HIT, false on MISS
	\*=======================================================================*/
	function check_cache ( $url ) {
		$this->ERROR = "";
		$cache_option = 'rss_' . $this->file_name( $url );

		if ( get_transient($cache_option) ) {
			// object exists and is current
			return 'HIT';
		} else {
			// object does not exist
			return 'MISS';
		}
	}

	/*=======================================================================*\
		Function:	serialize
	\*=======================================================================*/
	function serialize ( $rss ) {
		return serialize( $rss );
	}

	/*=======================================================================*\
		Function:	unserialize
	\*=======================================================================*/
	function unserialize ( $data ) {
		return unserialize( $data );
	}

	/*=======================================================================*\
		Function:	file_name
		Purpose:	map url to location in cache
		Input:		url from wich the rss file was fetched
		Output:		a file name
	\*=======================================================================*/
	function file_name ($url) {
		return md5( $url );
	}

	/*=======================================================================*\
		Function:	error
		Purpose:	register error
	\*=======================================================================*/
	function error ($errormsg, $lvl=E_USER_WARNING) {
		// append PHP's error message if track_errors enabled
		if ( isset($php_errormsg) ) {
			$errormsg .= " ($php_errormsg)";
		}
		$this->ERROR = $errormsg;
		if ( MAGPIE_DEBUG ) {
			trigger_error( $errormsg, $lvl);
		}
		else {
			error_log( $errormsg, 0);
		}
	}
	function debug ($debugmsg, $lvl=E_USER_NOTICE) {
		if ( MAGPIE_DEBUG ) {
			$this->error("MagpieRSS [debug] $debugmsg", $lvl);
		}
	}
}

