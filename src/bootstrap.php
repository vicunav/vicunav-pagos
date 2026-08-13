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
	Installer::install();
	Capabilities::grant_to_administrator();
	ExpirationScheduler::schedule();
}

/**
 * Retira la recurrencia sin borrar solicitudes al desactivar.
 *
 * @internal
 *
 * @return void
 */
function deactivate(): void {
	ExpirationScheduler::unschedule();
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

	Installer::maybe_upgrade();

	$payment_request = new PaymentRequest();
	$payment_request->register_hooks();
	add_action( 'init', array( PaymentRequest::class, 'register_meta' ), 20 );
	add_action( 'before_delete_post', __NAMESPACE__ . '\delete_payment_request_storage', 10, 2 );
	ExpirationScheduler::register();

	/**
	 * Se ejecuta cuando la superficie pública de Vicunav Pagos está disponible.
	 *
	 * @since 0.1.0
	 *
	 * @param string $plugin_version   Versión del plugin.
	 * @param string $contract_version Versión del contrato público.
	 */
	do_action( 'vicu_pagos_loaded', VICU_PAGOS_VERSION, VICU_PAGOS_CONTRACT_VERSION );
}

/**
 * Mantiene sincronizada la persistencia al eliminar el post administrativo.
 *
 * @internal
 *
 * @param int      $post_id ID del post eliminado.
 * @param \WP_Post $post    Post que WordPress eliminará.
 * @return void
 */
function delete_payment_request_storage( int $post_id, \WP_Post $post ): void {
	if ( PaymentRequest::SLUG === $post->post_type ) {
		PaymentRequestRepository::delete_by_post_id( $post_id );
	}
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
