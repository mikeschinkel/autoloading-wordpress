<?php

if (class_exists('PEAR_Error')) {

    class Services_JSON_Error extends PEAR_Error
    {
        function __construct($message = 'unknown error', $code = null,
                                     $mode = null, $options = null, $userinfo = null)
        {
            parent::PEAR_Error($message, $code, $mode, $options, $userinfo);
        }

    }

} else {

    /**
     * @todo Ultimately, this class shall be descended from PEAR_Error
     */
    class Services_JSON_Error
    {
	    /**
	     * PHP5 constructor.
	     */
        function __construct( $message = 'unknown error', $code = null,
                                     $mode = null, $options = null, $userinfo = null )
        {

        }

    }
    
}