<?php
/**
 * Classes, which help reading streams of data from files.
 * Based on the classes from Danilo Segan <danilo@kvota.net>
 *
 * @version $Id: streams.php 1157 2015-11-20 04:30:11Z dd32 $
 * @package pomo
 * @subpackage streams
 */

class POMO_FileReader extends POMO_Reader {

	/**
	 * @param string $filename
	 */
	function __construct( $filename ) {
		parent::POMO_Reader();
		$this->_f = fopen($filename, 'rb');
	}

	/**
	 * PHP4 constructor.
	 */
	public function POMO_FileReader( $filename ) {
		self::__construct( $filename );
	}

	/**
	 * @param int $bytes
	 */
	function read($bytes) {
		return fread($this->_f, $bytes);
	}

	/**
	 * @param int $pos
	 * @return boolean
	 */
	function seekto($pos) {
		if ( -1 == fseek($this->_f, $pos, SEEK_SET)) {
			return false;
		}
		$this->_pos = $pos;
		return true;
	}

	/**
	 * @return bool
	 */
	function is_resource() {
		return is_resource($this->_f);
	}

	/**
	 * @return bool
	 */
	function feof() {
		return feof($this->_f);
	}

	/**
	 * @return bool
	 */
	function close() {
		return fclose($this->_f);
	}

	/**
	 * @return string
	 */
	function read_all() {
		$all = '';
		while ( !$this->feof() )
			$all .= $this->read(4096);
		return $all;
	}
}
