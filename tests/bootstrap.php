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

/**
 * Limpia únicamente la tabla interna entre pruebas que ejercen el servicio.
 *
 * @return void
 */
function vicu_pagos_reset_requests(): void {
	global $wpdb;

	$post_ids = get_posts(
		array(
			'post_type'      => 'vicu_payment_req',
			'post_status'    => 'any',
			'fields'         => 'ids',
			'posts_per_page' => -1,
		)
	);

	foreach ( $post_ids as $post_id ) {
		wp_delete_post( $post_id, true );
	}

	$table = \Vicu\Pagos\PaymentRequestRepository::table_name();

	// La tabla pertenece a la base aislada y su nombre proviene del prefijo de pruebas.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$wpdb->query( "TRUNCATE TABLE {$table}" );
}
