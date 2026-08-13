# Alcance de vicunav-pagos

## Propósito

`vicunav-pagos` concentra el ciclo de vida de las solicitudes de pago sin conocer la
estructura interna del pedido, reserva u operación que origina el cobro. Su objetivo
es ofrecer una frontera reutilizable para los verticales del ecosistema.

## Responsabilidades

- CPT `vicu_payment_req` y su persistencia.
- Referencias externas polimórficas.
- Estados y transiciones del dominio de pagos.
- Idempotencia, concurrencia y expiración del ciclo de vida.
- Proveedores de pago detrás de contratos explícitos.
- Hooks públicos para que los verticales reaccionen a resultados.
- Capacidades y REST propios del dominio.

## Límites

Este repositorio no es propietario de:

- presentación, templates, patterns o estilos;
- pedidos, reservas, inventario o disponibilidad;
- datos internos de un vertical;
- checkout editorial específico de un demo;
- ACF ni campos pertenecientes a otro paquete.

Los verticales entregan una referencia externa y reaccionan a eventos públicos. Nunca
leen post meta, tablas o clases internas de pagos.

## Estado de PAGOS-03

PAGOS-03 añade el proveedor manual v1 detrás de la superficie pública del motor. Su
configuración solo habilita o deshabilita el proveedor. Una entrega conserva una
referencia opaca de comprobante, una identidad idempotente interna y la revisión de
la solicitud, y transiciona atómicamente a `comprobante_subido`.

Las integraciones bancarias, cuentas e instrucciones de pago, checkout, archivos,
comprobantes visuales y cualquier presentación permanecen fuera de esta fase.
