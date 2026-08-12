<?php
/**
 * Bootstrap de la suite de integración con WordPress.
 *
 * @package Vicunav_Pagos
 */

$vicu_pagos_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( false === $vicu_pagos_tests_dir || '' === $vicu_pagos_tests_dir ) {
	$vicu_pagos_tests_dir = dirname( __DIR__ ) . '/vendor/wp-phpunit/wp-phpunit';
}

require_once dirname( __DIR__ ) . '/vendor/autoload.php';

define( 'WP_TESTS_CONFIG_FILE_PATH', __DIR__ . '/wp-tests-config.php' );
define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', dirname( __DIR__ ) . '/vendor/yoast/phpunit-polyfills' );

require_once $vicu_pagos_tests_dir . '/includes/functions.php';

tests_add_filter(
	'muplugins_loaded',
	static function (): void {
		require dirname( __DIR__ ) . '/vendor/vicunav/vicunav-plugin-core/vicunav-plugin-core.php';
		require dirname( __DIR__ ) . '/vicunav-pagos.php';
	}
);

require $vicu_pagos_tests_dir . '/includes/bootstrap.php';
