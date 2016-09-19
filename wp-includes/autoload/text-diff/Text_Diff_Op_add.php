<?php

/**
 * @package Text_Diff
 * @author  Geoffrey T. Dairiki <dairiki@dairiki.org>
 *
 * @access private
 */
class Text_Diff_Op_add extends Text_Diff_Op {

	/**
	 * PHP5 constructor.
	 */
    function __construct( $lines )
    {
        $this->final = $lines;
        $this->orig = false;
    }

	/**
	 * PHP4 constructor.
	 */
	public function Text_Diff_Op_add( $lines ) {
		self::__construct( $lines );
	}

    function &reverse()
    {
        $reverse = new Text_Diff_Op_delete($this->final);
        return $reverse;
    }

}
