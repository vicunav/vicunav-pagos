<?php
/**
 * Pruebas del bootstrap y contrato mínimo.
 *
 * @package Vicunav_Pagos
 */

use Vicu\Pagos\PostTypes\PaymentRequest;

/**
 * Verifica carga, constantes y dependencia declarada.
 */
final class BootstrapTest extends WP_UnitTestCase {
	/**
	 * Verifica las versiones publicadas por el entry point.
	 *
	 * @return void
	 */
	public function test_versions_match_current_contract(): void {
		$this->assertSame( '0.2.0', VICU_PAGOS_VERSION );
		$this->assertSame( '0.2.0', VICU_PAGOS_CONTRACT_VERSION );
		$this->assertSame( '1', VICU_PAGOS_DB_VERSION );
		$this->assertSame( 1, did_action( 'vicu_pagos_loaded' ) );
	}

	/**
	 * Verifica que el autoloader resuelva la clase del CPT.
	 *
	 * @return void
	 */
	public function test_public_namespace_is_available(): void {
		$this->assertTrue( class_exists( PaymentRequest::class ) );
	}

	/**
	 * Verifica la dependencia declarativa para WordPress.
	 *
	 * @return void
	 */
	public function test_plugin_header_requires_core(): void {
		$data = get_file_data(
			VICU_PAGOS_PLUGIN_FILE,
			array( 'requires_plugins' => 'Requires Plugins' )
		);

		$this->assertSame( 'vicunav-plugin-core', $data['requires_plugins'] );
	}
}
