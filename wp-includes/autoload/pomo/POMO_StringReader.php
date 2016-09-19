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
 * Provides file-like methods for manipulating a string instead
 * of a physical file.
 */
class POMO_StringReader extends POMO_Reader {

	var $_str = '';

	/**
	 * PHP5 constructor.
	 */
	function __construct( $str = '' ) {
		parent::POMO_Reader();
		$this->_str = $str;
		$this->_pos = 0;
	}

	/**
	 * PHP4 constructor.
	 */
	public function POMO_StringReader( $str = '' ) {
		self::__construct( $str );
	}

	/**
	 * @param string $bytes
	 * @return string
	 */
	function read($bytes) {
		$data = $this->substr($this->_str, $this->_pos, $bytes);
		$this->_pos += $bytes;
		if ($this->strlen($this->_str) < $this->_pos) $this->_pos = $this->strlen($this->_str);
		return $data;
	}

	/**
	 * @param int $pos
	 * @return int
	 */
	function seekto($pos) {
		$this->_pos = $pos;
		if ($this->strlen($this->_str) < $this->_pos) $this->_pos = $this->strlen($this->_str);
		return $this->_pos;
	}

	/**
	 * @return int
	 */
	function length() {
		return $this->strlen($this->_str);
	}

	/**
	 * @return string
	 */
	function read_all() {
		return $this->substr($this->_str, $this->_pos, $this->strlen($this->_str));
	}

}

