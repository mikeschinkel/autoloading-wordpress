<?php

/**
 * @package Text_Diff
 * @author  Geoffrey T. Dairiki <dairiki@dairiki.org>
 *
 * @access private
 */
class Text_Diff_Op_change extends Text_Diff_Op {

	/**
	 * PHP5 constructor.
	 */
    function __construct( $orig, $final )
    {
        $this->orig = $orig;
        $this->final = $final;
    }

	/**
	 * PHP4 constructor.
	 */
	public function Text_Diff_Op_change( $orig, $final ) {
		self::__construct( $orig, $final );
	}

    function &reverse()
    {
        $reverse = new Text_Diff_Op_change($this->final, $this->orig);
        return $reverse;
    }

}
