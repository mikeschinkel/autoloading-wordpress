<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'wordpress');

/** MySQL database username */
define('DB_USER', 'wordpress');

/** MySQL database password */
define('DB_PASSWORD', 'wordpress');

/** MySQL hostname */
define('DB_HOST', 'localhost');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8mb4');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

define( 'WP_HOME', 'http://wptrunk.dev' );
define( 'WP_SITEURL', 'http://wptrunk.dev' );

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         'JL>_N*U>6Ik:v}P?~|RHS=s~`NZ_qfe.S*DzoB4#O~0|cg>>-rfP&oF4RS^r]+%%');
define('SECURE_AUTH_KEY',  ' V9eW2r</*lLH~M,m&&T~NDT$]({qp3!E4WA25-UM./pf4OSnjm8>~/yE!,!~(Ep');
define('LOGGED_IN_KEY',    'LaH(>Hhk&}PLltJ<_3O;.Qpgd}ZB8*yO0*umsE7PWuLDG,Bj!KmXh+dCOF|8=.yg');
define('NONCE_KEY',        'a1+@bSJYa3jC!&y]{-A@CGc2?{5GSW ax>0Sr?Fn(;jWbW$I6UhRT]Mf0?)#zQi#');
define('AUTH_SALT',        '}rMB{W6E#j-V8v2rArY4wxTe/$S<KS2^C?z$)V6l|3r+I?t6LV*e4DYc_q|G=jE.');
define('SECURE_AUTH_SALT', 'o3~ c;Dz4hL0[RDa.Z=E15&d~aAu4qV0:nrHJ(?BRZ1|9>2sejszpyA4@G,_*quI');
define('LOGGED_IN_SALT',   'uW-y2uYJwbQ}MJ>b?eTV<]IKU|CNqwH<%Vc2JOmjri6=HBQp+[xb0vy9#-NKNhkd');
define('NONCE_SALT',       'q3D{;7/Q(_${IHJfSC9D?^}9)6B^_5,KAkG12$sm=o~FV5`we6StZL=>R!@>veQ~');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the Codex.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define('WP_DEBUG', true);

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

error_reporting(E_ALL);

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
