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
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'wp_api' );

/** MySQL database username */
define( 'DB_USER', 'root' );

/** MySQL database password */
define( 'DB_PASSWORD', '' );

/** MySQL hostname */
define( 'DB_HOST', 'localhost' );

/** Database Charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The Database Collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );


/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         'q9J:M/5DVw^|-JMecm>)4-Q=9P*wsKU%W{~zyZu6J06uViZ~3w*EAK@kNYJ(Qc8&');
define('SECURE_AUTH_KEY',  'CH7Pz,3mGywdACsO`D-u%%VPeO|OBUo}UD4+^KBVPTGIU.J(GE1*B1IM>M,Z{rLl');
define('LOGGED_IN_KEY',    'S>d.-/*$[!ZA+W/{eoZ? v.0NP-=|Kr5G-:AC8U0Ps`N;uSFf+j9F!]^ab+%|4v)');
define('NONCE_KEY',        '_Q7+^ZF5wh/mKWr`@@!~;,3WZ7%LQ:A!-}:hov2cD3Imcyd/(0$VtP+E)Ce~o+Qu');
define('AUTH_SALT',        '#$y<LIWOguEe#jJ<<GQ+s3}P,wK?=cu#w(c_Gq-#nVSFv!d.%hG@FrwdcR8$51.7');
define('SECURE_AUTH_SALT', 'IrJYA`9OgbIUKt3^q^g-WIGO-+1;.0th5^9yr0)j3+7ZX~5{E$-]n[1,g_2YBU$.');
define('LOGGED_IN_SALT',   'LJ2mkT;s^7wn$%tI53fY<)bJ>ek$D)0(M%ChJ~`3CyfDCqu%P08@+^$}5 bBQ|y-');
define('NONCE_SALT',       '}j!R*H`Wh7i,_,DeNE^c~p!DOw2gkg@p&7dE/hv7jB9(WHiH-|cZ jT<|sun+pb&');

define('JWT_AUTH_SECRET_KEY', 'V]f.E6+hS|7v`0`L~opo9^NHW.~A+;/O3;^$&0ja#Ue-x@Dyzs|Cu,3@}T~5O&y[');
/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', false );

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
require_once ABSPATH . 'wp-custom-api.php';

