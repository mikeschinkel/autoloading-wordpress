<?php

/**
 * WordPress AtomPub API implementation.
 *
 * Originally stored in wp-app.php, and later wp-includes/class-wp-atom-server.php.
 * It is kept here in case a plugin directly referred to the class.
 *
 * @since 2.2.0
 * @deprecated 3.5.0
 *
 * @link https://wordpress.org/plugins/atom-publishing-protocol/
 */
class wp_atom_server {
	public function __call( $name, $arguments ) {
		_deprecated_function( __CLASS__ . '::' . $name, '3.5.0', 'the Atom Publishing Protocol plugin' );
	}

	public static function __callStatic( $name, $arguments ) {
		_deprecated_function( __CLASS__ . '::' . $name, '3.5.0', 'the Atom Publishing Protocol plugin' );
	}
}
