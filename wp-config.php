<?php
/**
 * The base configurations of the WordPress.
 *
 * This file has the following configurations: MySQL settings, Table Prefix,
 * Secret Keys, and ABSPATH. You can find more information by visiting
 * {@link https://codex.wordpress.org/Editing_wp-config.php Editing wp-config.php}
 * Codex page. You can get the MySQL settings from your web host.
 *
 * This file is used by the wp-config.php creation script during the
 * installation. You don't have to use the web site, you can just copy this file
 * to "wp-config.php" and fill in the values.
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'restrepo_newco2');

/** MySQL database username */
define('DB_USER', 'restrepo_newco2');

/** MySQL database password */
define('DB_PASSWORD', 'restrepo_newco2');

/** MySQL hostname */
define('DB_HOST', 'localhost');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8mb4');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         'EnOw;GtLtiShLe9uPZjI$<@R^^&a],*lOcwB@yv&MrvQ!oepw>_Jg|^yCAs;[7k7');
define('SECURE_AUTH_KEY',  'aUI7EuGe3/a.)pe85ZkgO#o+69!F|`(WSphcdjUtX2%h_,ICxV,MX#)nSy,x6T/9');
define('LOGGED_IN_KEY',    'L[ER MTds9AGe6u,[W=QG1J2J|]x3Bqfu+r$?H_Z>U[}3x$DwKo$a&;2U;,2|wP/');
define('NONCE_KEY',        ',UQxL+pZ&N0H4ms!!)fbw!-p^puk1{R@OWo6vTnV*rcV]f.6CM+9nsW8I$n+^tH,');
define('AUTH_SALT',        'D>Du@>uW*7dbOEV<P_UG4*<[x.|Rf#2r||( O4A1-5nTf|P s&T: &{em,6`-cGQ');
define('SECURE_AUTH_SALT', '1ITC63( +8#6@!yRl[1);d]~;)-j%e^NYf|xr~f+y|byxf<7kE}T2*U?(W4;0Rcx');
define('LOGGED_IN_SALT',   ')w.{~c|Y)qQCX]oRe}+P6Rhwc4-3S!@(Q3>7[|5,~pPfwLBvGA{7zotJ`OGcwNk0');
define('NONCE_SALT',       '-7G99&;-;Owu3S=NC&SU=z?W{w`Kwm-#Al86$:0dteLpW+qyjljgm)f!2=HEVApj');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each a unique
 * prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 */
#define('WP_DEBUG', false);

ini_set('log_errors','On');
ini_set('display_errors','Off');
ini_set('error_reporting', E_ALL );
define('WP_DEBUG', false);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);

# DISABLE WORDPRESS UPDATES
define( 'AUTOMATIC_UPDATER_DISABLED', true );

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');