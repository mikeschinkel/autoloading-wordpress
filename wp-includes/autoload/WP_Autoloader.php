<?php
/**
 * WordPress Class and Trait Autoloader
 */

class WP_Autoloader {

	/**
	 * @var array
	 */
	private static $_classmap;

	/**
	 * WP_Autoloader constructor.
	 */
	function __construct() {

		spl_autoload_register( array( $this, 'load' ), true, true );

		if ( is_file( ABSPATH . 'wp-classmap.php' ) ) {

			self::$_classmap = require( ABSPATH . 'wp-classmap.php' );

		}

	}

	/**
	 * @param string $class_name
	 */
	function load( $class_name ) {

		if ( isset( self::$_classmap[ $class_name ] ) ) {

			require ABSPATH . self::$_classmap[ $class_name ];

		}

	}

}