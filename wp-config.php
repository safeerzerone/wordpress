<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'wordpress4' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', 'NewPassword123' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'iJ&RLW>AwX.a^cTaTX5_) 0^-~KoVdKMcRC}iD/UaOr{h40aN1#v.TDcc8#kk7=W' );
define( 'SECURE_AUTH_KEY',  'n#ecB,}OL`ay!meYpgYNSb~dALSFL,)zf_#Tp7=INg3 |-@q[jm7~N_c^o39Es6_' );
define( 'LOGGED_IN_KEY',    'D.x#>Qn4(!wsxm&iENX,5nVSvoD<<qIg*0GXQ1LQ@(<vCP*)YINznOsju6=4OM,-' );
define( 'NONCE_KEY',        'ip1,%yoOFo&XTL7@[vtb&&n1[k%a !f`lh,BS9;P~<+[gX.-CM X#3Z}5ycNENN2' );
define( 'AUTH_SALT',        'a10T7]-rQ<9!H.5>5A*^>^_8+qF$=dR_HO7#![2]LE.=9>9 j4ur5Gr~9(o N  E' );
define( 'SECURE_AUTH_SALT', '&C*M%F*tE09:/kfBc1O6GM|r509(I57J29L$zb?S|d`QYya)_wqPkNh4N;wfPg2K' );
define( 'LOGGED_IN_SALT',   'u+0(TRJ5?wdON*~+KS@581=3]JJyRg49PB0A!qTUk.Q*E6v{Q2SoihNjny}R/>#=' );
define( 'NONCE_SALT',       'xnLzWY^O=uXt9 ltGEjxh4:60x:Q4iB{.@^nFGZaMWM{qP^D7zd [8]f[gl7ex$n' );

define( 'ARSENAL_STRIPE_SECRET_KEY', 'sk_test_51TCvdTGvz5b3dvSRUAmcAPlFwbq2Af3nZ25bH5Sa6MdnX8GzZuQZatRw479WcvGTOHDmNH7fFudvgQ8J23pSHp2T00TofNbZzL' );
/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
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
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
