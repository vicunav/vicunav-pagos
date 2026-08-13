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

## Estado de PAGOS-02

PAGOS-02 implementa el bootstrap, el CPT administrativo, la persistencia versionada,
las capabilities, la creación idempotente, la máquina de estados atómica, la
expiración repetible y los eventos públicos versionados. El proveedor manual, el
checkout y cualquier presentación permanecen fuera de esta fase.
