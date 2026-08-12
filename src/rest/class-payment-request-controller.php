<?php
/**
 * Controlador REST administrativo para solicitudes de pago.
 *
 * @package Vicunav_Pagos
 */

namespace Vicu\Pagos\Rest;

use WP_Error;
use WP_REST_Posts_Controller;
use WP_REST_Request;

/**
 * Evita que la colección privada se exponga como lectura anónima.
 */
final class PaymentRequestController extends WP_REST_Posts_Controller {
	/**
	 * Restringe el listado a operadores con permiso de edición.
	 *
	 * @param WP_REST_Request $request Solicitud REST actual.
	 * @return true|WP_Error
	 */
	public function get_items_permissions_check( $request ) {
		if ( ! current_user_can( 'edit_vicu_payment_requests' ) ) {
			return new WP_Error(
				'rest_cannot_view',
				__( 'No tienes permisos para ver solicitudes de pago.', 'vicunav-pagos' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return parent::get_items_permissions_check( $request );
	}
}
