<?php
/**
 * Flujo E2E ejecutado dentro de un sitio WordPress real.
 *
 * @package Vicunav_Pagos
 */

// El script de consola escribe resultados en stdout y errores en stderr.
// phpcs:disable WordPress.WP.AlternativeFunctions.file_system_operations_fwrite

$vicu_pagos_wp_load = getenv( 'VICU_PAGOS_E2E_WP_LOAD' );

if ( false === $vicu_pagos_wp_load || ! is_readable( $vicu_pagos_wp_load ) ) {
	fwrite( STDERR, "VICU_PAGOS_E2E_WP_LOAD debe apuntar a un wp-load.php legible.\n" );
	exit( 1 );
}

require $vicu_pagos_wp_load;
require_once ABSPATH . 'wp-admin/includes/plugin.php';

use Vicu\Pagos\ExpirationScheduler;
use Vicu\Pagos\PaymentRequestRepository;
use Vicu\Pagos\PaymentRequests;
use Vicu\Pagos\PaymentRequestState;

$vicu_pagos_plugin = 'vicunav-pagos/vicunav-pagos.php';
$vicu_core_plugin  = 'vicunav-plugin-core/vicunav-plugin-core.php';

if ( ! is_plugin_active( $vicu_core_plugin ) || ! is_plugin_active( $vicu_pagos_plugin ) ) {
	fwrite( STDERR, "Vicunav Plugin Core y Vicunav Pagos deben estar activos antes del E2E.\n" );
	exit( 1 );
}

$vicu_pagos_events = array();
$vicu_pagos_record = static function ( array $payload ) use ( &$vicu_pagos_events ): void {
	$vicu_pagos_events[] = $payload;
};

add_action( 'vicu_pagos_creado', $vicu_pagos_record );
add_action( 'vicu_pagos_confirmado', $vicu_pagos_record );

$vicu_pagos_external_id = 'E2E-' . gmdate( 'YmdHis' ) . '-' . wp_rand( 1000, 9999 );
$vicu_pagos_request     = PaymentRequests::create(
	array(
		'external_type' => 'vicu_e2e',
		'external_id'   => $vicu_pagos_external_id,
		'amount_minor'  => 4321,
		'currency'      => 'USD',
		'expires_at'    => gmdate( DATE_RFC3339, time() + HOUR_IN_SECONDS ),
	)
);

if ( is_wp_error( $vicu_pagos_request ) ) {
	fwrite( STDERR, $vicu_pagos_request->get_error_code() . "\n" );
	exit( 1 );
}

$vicu_pagos_id      = $vicu_pagos_request['id'];
$vicu_pagos_request = PaymentRequests::create(
	array(
		'external_type' => 'vicu_e2e',
		'external_id'   => $vicu_pagos_external_id,
		'amount_minor'  => 4321,
		'currency'      => 'USD',
		'expires_at'    => $vicu_pagos_request['expires_at'],
	)
);

if ( is_wp_error( $vicu_pagos_request ) || $vicu_pagos_id !== $vicu_pagos_request['id'] ) {
	fwrite( STDERR, "La creación E2E no fue idempotente.\n" );
	exit( 1 );
}

$vicu_pagos_request = PaymentRequests::transition(
	$vicu_pagos_id,
	PaymentRequestState::PROOF_UPLOADED,
	1
);
$vicu_pagos_request = is_wp_error( $vicu_pagos_request )
	? $vicu_pagos_request
	: PaymentRequests::transition( $vicu_pagos_id, PaymentRequestState::CONFIRMED, 2 );

if (
	is_wp_error( $vicu_pagos_request ) ||
	PaymentRequestState::CONFIRMED !== $vicu_pagos_request['state'] ||
	3 !== $vicu_pagos_request['revision'] ||
	2 !== count( $vicu_pagos_events ) ||
	'1.0.0' !== $vicu_pagos_events[0]['payload_version'] ||
	'1.0.0' !== $vicu_pagos_events[1]['payload_version']
) {
	fwrite( STDERR, "El flujo o los eventos E2E no coinciden con el contrato.\n" );
	exit( 1 );
}

$vicu_pagos_was_active = is_plugin_active( $vicu_pagos_plugin );
deactivate_plugins( $vicu_pagos_plugin, true );
$vicu_pagos_activation_error = activate_plugin( $vicu_pagos_plugin, '', false, true );

if (
	is_wp_error( $vicu_pagos_activation_error ) ||
	false === wp_next_scheduled( ExpirationScheduler::HOOK )
) {
	fwrite( STDERR, "La desactivación o reactivación E2E produjo un error.\n" );
	exit( 1 );
}

if ( ! $vicu_pagos_was_active ) {
	deactivate_plugins( $vicu_pagos_plugin, true );
}

global $wpdb;

// El E2E elimina solo la fila y el post efímeros que creó.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
$wpdb->delete( PaymentRequestRepository::table_name(), array( 'post_id' => $vicu_pagos_id ), array( '%d' ) );
wp_delete_post( $vicu_pagos_id, true );

fwrite(
	STDOUT,
	wp_json_encode(
		array(
			'request_id' => $vicu_pagos_id,
			'state'      => PaymentRequestState::CONFIRMED,
			'revision'   => 3,
			'events'     => array( 'creado', 'confirmado' ),
			'activation' => 'ok',
		),
		JSON_PRETTY_PRINT
	) . "\n"
);

// phpcs:enable WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
