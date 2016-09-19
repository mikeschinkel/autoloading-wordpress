<?php
/**
 * Classes, which help reading streams of data from files.
 * Based on the classes from Danilo Segan <danilo@kvota.net>
 *
 * @version $Id: streams.php 1157 2015-11-20 04:30:11Z dd32 $
 * @package pomo
 * @subpackage streams
 */

/**
 * Reads the contents of the file in the beginning.
 */
class POMO_CachedIntFileReader extends POMO_CachedFileReader {
	/**
	 * PHP5 constructor.
	 */
	public function __construct( $filename ) {
		parent::POMO_CachedFileReader($filename);
	}

	/**
	 * PHP4 constructor.
	 */
	function POMO_CachedIntFileReader( $filename ) {
		self::__construct( $filename );
	}
}

