<?php
/**
 * Publicación de eventos del dominio de pagos.
 *
 * @package Vicunav_Pagos
 */

namespace Vicu\Pagos;

/**
 * Construye payloads versionados después de persistir.
 *
 * @internal
 */
final class EventPublisher {
	public const PAYLOAD_VERSION = '1.0.0';

	/**
	 * Emite el evento de creación.
	 *
	 * @param array<string, mixed> $request Solicitud pública persistida.
	 * @return void
	 */
	public static function created( array $request ): void {
		self::publish( 'vicu_pagos_creado', 'creado', null, PaymentRequestState::PENDING, $request );
	}

	/**
	 * Emite el evento asociado al destino cuando existe.
	 *
	 * @param string               $from    Estado anterior.
	 * @param string               $to      Estado persistido.
	 * @param array<string, mixed> $request Solicitud pública persistida.
	 * @return void
	 */
	public static function transitioned( string $from, string $to, array $request ): void {
		$hooks = array(
			PaymentRequestState::CONFIRMED => 'vicu_pagos_confirmado',
			PaymentRequestState::REJECTED  => 'vicu_pagos_rechazado',
			PaymentRequestState::EXPIRED   => 'vicu_pagos_expirado',
		);

		if ( ! isset( $hooks[ $to ] ) ) {
			return;
		}

		self::publish( $hooks[ $to ], $to, $from, $to, $request );
	}

	/**
	 * Publica un único argumento versionado.
	 *
	 * @param string               $hook    Nombre del hook.
	 * @param string               $event   Nombre contractual del evento.
	 * @param string|null          $from    Estado anterior.
	 * @param string               $to      Estado persistido.
	 * @param array<string, mixed> $request Solicitud pública persistida.
	 * @return void
	 */
	private static function publish( string $hook, string $event, ?string $from, string $to, array $request ): void {
		$payload = array(
			'payload_version' => self::PAYLOAD_VERSION,
			'event'           => $event,
			'occurred_at'     => $request['updated_at'],
			'transition'      => array(
				'from' => $from,
				'to'   => $to,
			),
			'request'         => $request,
		);

		/**
		 * Publica un cambio persistido del ciclo de vida.
		 *
		 * @since 0.2.0
		 *
		 * @param array<string, mixed> $payload Payload contractual 1.0.0.
		 */
		do_action( $hook, $payload );
	}
}
