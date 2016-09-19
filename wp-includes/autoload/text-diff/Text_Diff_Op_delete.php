<?php

/**
 * @package Text_Diff
 * @author  Geoffrey T. Dairiki <dairiki@dairiki.org>
 *
 * @access private
 */
class Text_Diff_Op_delete extends Text_Diff_Op {

	/**
	 * PHP5 constructor.
	 */
	function __construct( $lines )
    {
        $this->orig = $lines;
        $this->final = false;
    }

	/**
	 * PHP4 constructor.
	 */
	public function Text_Diff_Op_delete( $lines ) {
		self::__construct( $lines );
	}

    function &reverse()
    {
        $reverse = new Text_Diff_Op_add($this->orig);
        return $reverse;
    }

}
