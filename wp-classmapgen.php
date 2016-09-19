<?php
/**
 * Generates a classmap for classes in /wp-includes and /wp-admin
 * It should be saved to the www root and when run it saves to wp-classmap.php
 * wp-classmap.php could then be loaded by wp-settings.php
 *
 * THIS IS A STRAWMAN PROPOSAL - https://en.wikipedia.org/wiki/Straw_man_proposal
 */
global $classmap;
$classmap = array();

/**_custom_header_background_just_in_time
 * @param SplFileInfo $file
 */
function handle_file( $file ) {
	global $classmap;

	do {

		if ( $file->isDir() ) {
			continue;
		}

		if ( 'php' !== strtolower( $file->getExtension() ) ) {
			continue;
		}

		$php_code = file_get_contents( $file->getRealPath() );

		/**
		 * @see http://stackoverflow.com/a/12011255/102699
		 */
		$token = '([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)';

		if ( ! preg_match_all( "/\n\s*(final|abstract)?\s*(class|interface|trait)\s+{$token}/", $php_code, $matches ) ) {
			continue;
		}

		$dir = preg_quote( __DIR__ );
		$file_path = preg_replace( "#^{$dir}/(.+)$#", '$1', $file->getRealPath() );


		foreach( $matches[ 3 ] as $token ) {
			$classmap[ $token ] = $file_path;
		}

	} while ( false );

}

$files = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( __DIR__ . '/wp-includes' ) );
foreach( $files as $file ) {
	handle_file( $file );
}
$files = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( __DIR__ . '/wp-admin' ) );
foreach( $files as $file ) {
	handle_file( $file );
}
ob_start();
var_export( $classmap );
$classmap = ob_get_clean();
$classmap = '<?' . <<<PHP
php
// WordPress Core Classmap
return {$classmap};
PHP;

header( 'Content-type:text/plain');
echo $classmap;
file_put_contents( __DIR__ . '/wp-classmap.php', $classmap );
