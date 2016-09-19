<?php

/**
 * @package Text_Diff
 * @author  Geoffrey T. Dairiki <dairiki@dairiki.org>
 *
 * @access private
 */
class Text_Diff_Op_copy extends Text_Diff_Op {

	/**
	 * PHP5 constructor.
	 */
    function __construct( $orig, $final = false )
    {
        if (!is_array($final)) {
            $final = $orig;
        }
        $this->orig = $orig;
        $this->final = $final;
    }

	/**
	 * PHP4 constructor.
	 */
	public function Text_Diff_Op_copy( $orig, $final = false ) {
		self::__construct( $orig, $final );
	}

    function &reverse()
    {
        $reverse = new Text_Diff_Op_copy($this->final, $this->orig);
        return $reverse;
    }

}
