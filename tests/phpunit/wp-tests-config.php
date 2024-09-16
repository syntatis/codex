<?php // phpcs:ignore

/* Path to the WordPress codebase you'd like to test. Add a forward slash in the end. */
define('ABSPATH', dirname(__DIR__, 2) . '/wordpress/');

/*
 * Path to the theme to test with.
 *
 * The 'default' theme is symlinked from test/phpunit/data/themedir1/default into
 * the themes directory of the WordPress installation defined above.
 */
define('WP_DEFAULT_THEME', 'default');

/*
 * Test with multisite enabled.
 * Alternatively, use the tests/phpunit/multisite.xml configuration file.
 */
// define( 'WP_TESTS_MULTISITE', true );

/*
 * Force known bugs to be run.
 * Tests with an associated Trac ticket that is still open are normally skipped.
 */
// define( 'WP_TESTS_FORCE_KNOWN_BUGS', true );

// Test with WordPress debug mode (default).
define('WP_DEBUG', true);

// ** MySQL settings ** //

/*
 * This configuration file will be used by the copy of WordPress being tested.
 * wordpress/wp-config.php will be ignored.
 *
 * WARNING WARNING WARNING!
 * These tests will DROP ALL TABLES in the database with the prefix named below.
 * DO NOT use a production database or one that is shared with something else.
 */

define('DB_NAME', getenv('WP_DB_TEST_NAME') ? getenv('WP_DB_TEST_NAME') : 'wp_test');
define('DB_USER', getenv('WP_DB_TEST_USER') ? getenv('WP_DB_TEST_USER') : 'wp_test');
define('DB_PASSWORD', getenv('WP_DB_TEST_PASS') ? getenv('WP_DB_TEST_PASS') : 'wp_test');
define('DB_HOST', getenv('WP_DB_TEST_HOST') ? getenv('WP_DB_TEST_HOST') : 'localhost');
define('DB_CHARSET', 'utf8');
define('DB_COLLATE', '');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 */
define('AUTH_KEY', 'Q={k(HD!Y&+S>uuBdIXRv174/(sGF&=BF9:tMBk[!]z-*?B*(8#9V?V1V2}+Gcpj');
define('SECURE_AUTH_KEY', '`=YUm{^-T%Is9k<$:|e7J(WHP|h^&IVOH@#w:TPTd/6gtmig&U%/]Jw#5?Q]NASo');
define('LOGGED_IN_KEY', '6AfMA$H:hedb2S-Tu:ajKP%:AktM2vb$||a%v|, @/mg_k C|R<Jo-fj|,Eg3-*8');
define('NONCE_KEY', 'Iz}|z^E28,K023J2|%G~4b)4zy`Y}%%;+CuIdDy%PtPP>y=e%cq(Cq|d{ 6<+<(F');
define('AUTH_SALT', 'p)h]fPQ A~:@v|._||@d41lF0](~?SsVO&CI_d9lk.F|IlXJh&[fSo77L`l-EPl2');
define('SECURE_AUTH_SALT', '%jp2o XB?`]*y;JI5G+aHGBHRo.+~)o>*<%Z 0B`Pf%]^7li5f15L`z+[>;}h7<=');
define('LOGGED_IN_SALT', '5?g?iSxc^C>YuYadqk]& F5nu%&%Wc7,b&+V.+ .Y^w,oR{@lbr%-shOjWQAc<Z%');
define('NONCE_SALT', '<$C17H<(hs0ed/ )|wTKv}<rP%R7E=!8r|C;S7|av#nvNMYQ{():mmuyMCuC8U-G');

define('WP_TESTS_DOMAIN', 'example.org');
define('WP_TESTS_EMAIL', 'admin@example.org');
define('WP_TESTS_TITLE', 'Test Site');
define('WP_PHP_BINARY', 'php');
define('WPLANG', '');
