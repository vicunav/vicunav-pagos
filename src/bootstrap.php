<?php
/**
 * Carga técnica del plugin.
 *
 * @package Vicunav_Pagos
 */

namespace Vicu\Pagos;

use Vicu\Core\PostType;
use Vicu\Pagos\PostTypes\PaymentRequest;

/**
 * Carga una clase del namespace Vicu\Pagos desde src/.
 *
 * @internal
 *
 * @param string $requested_class Nombre completo de la clase solicitada.
 * @return void
 */
function autoload( string $requested_class ): void {
	$prefix = __NAMESPACE__ . '\\';

	if ( 0 !== strpos( $requested_class, $prefix ) ) {
		return;
	}

	$relative_class = substr( $requested_class, strlen( $prefix ) );
	$parts          = explode( '\\', $relative_class );
	$short_name     = array_pop( $parts );
	$directories    = array_map( __NAMESPACE__ . '\\to_kebab_case', $parts );
	$file_name      = 'class-' . to_kebab_case( $short_name ) . '.php';
	$file           = VICU_PAGOS_PATH . 'src/';

	if ( array() !== $directories ) {
		$file .= implode( '/', $directories ) . '/';
	}

	$file .= $file_name;

	if ( is_readable( $file ) ) {
		require_once $file;
	}
}

/**
 * Convierte un segmento PascalCase a kebab-case.
 *
 * @internal
 *
 * @param string $value Segmento que se convertirá.
 * @return string
 */
function to_kebab_case( string $value ): string {
	$converted = preg_replace( '/(?<!^)[A-Z]/', '-$0', $value );

	return strtolower( (string) $converted );
}

/**
 * Registra las capacidades iniciales durante la activación.
 *
 * @internal
 *
 * @return void
 */
function activate(): void {
	Capabilities::grant_to_administrator();
}

/**
 * Carga el dominio inicial cuando plugin core está disponible.
 *
 * @internal
 *
 * @return void
 */
function bootstrap(): void {
	if ( ! class_exists( PostType::class ) ) {
		add_action( 'admin_notices', __NAMESPACE__ . '\\render_missing_dependency_notice' );
		return;
	}

	$payment_request = new PaymentRequest();
	$payment_request->register_hooks();
	add_action( 'init', array( PaymentRequest::class, 'register_meta' ), 20 );

	/**
	 * Se ejecuta cuando la superficie inicial de Vicunav Pagos está disponible.
	 *
	 * @since 0.1.0
	 *
	 * @param string $plugin_version   Versión del plugin.
	 * @param string $contract_version Versión del contrato público.
	 */
	do_action( 'vicu_pagos_loaded', VICU_PAGOS_VERSION, VICU_PAGOS_CONTRACT_VERSION );
}

/**
 * Informa una dependencia ausente sin exponer detalles internos.
 *
 * @internal
 *
 * @return void
 */
function render_missing_dependency_notice(): void {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	printf(
		'<div class="notice notice-error"><p>%s</p></div>',
		esc_html__( 'Vicunav Pagos requiere Vicunav Plugin Core activo.', 'vicunav-pagos' )
	);
}

spl_autoload_register( __NAMESPACE__ . '\\autoload' );
add_action( 'plugins_loaded', __NAMESPACE__ . '\\bootstrap', 10 );
