# Contrato público de `vicunav-pagos`

Estado: vigente para el contrato 0.3.0 y el plugin 0.3.0.

Este documento fija la superficie pública del motor de pagos. Los consumidores usan
los servicios y hooks descritos aquí; no leen tablas, post meta ni clases marcadas
como internas.

## Responsabilidad y dependencias

El plugin es propietario de las solicitudes y del ciclo de vida de pagos. No conoce
reservas, pedidos ni otros modelos verticales. La relación con la operación de origen
se expresa mediante una referencia externa polimórfica.

Requisitos:

- WordPress 6.6 o superior.
- PHP 8.1 o superior.
- `vicunav-plugin-core` 0.1.0 o superior dentro del contrato mayor 1.
- Namespace PHP raíz: `Vicu\Pagos`.
- Versión del plugin: constante `VICU_PAGOS_VERSION`.
- Versión del contrato: constante `VICU_PAGOS_CONTRACT_VERSION`.

El header `Requires Plugins` declara `vicunav-plugin-core`. El plugin no incluye ni
reimplementa sus clases.

## Orden de carga

El entry point `vicunav-pagos.php` registra el autoloader de `Vicu\Pagos`. El
bootstrap se ejecuta en `plugins_loaded` con prioridad 10, después de la prioridad 5
publicada por plugin core.

Si `Vicu\Core\PostType` no está disponible, pagos no registra comportamiento y
muestra un aviso administrativo a usuarios con `activate_plugins`.

El action siguiente indica que la superficie implementada está disponible:

```php
do_action(
	'vicu_pagos_loaded',
	VICU_PAGOS_VERSION,
	VICU_PAGOS_CONTRACT_VERSION
);
```

## Solicitudes de pago

### CPT administrativo

- Slug estable: `vicu_payment_req`.
- No es público ni consultable en frontend.
- Tiene interfaz administrativa bajo el menú Vicunav.
- Usa capabilities propias y `map_meta_cap`.
- Expone un controlador REST administrativo protegido en
  `/wp-json/wp/v2/vicu-payment-requests`.

El endpoint REST no es el contrato de integración entre plugins. Una llamada anónima
o de un rol sin las capabilities del CPT se rechaza. La creación REST delega en el
servicio idempotente y los metadatos contractuales no pueden modificarse mediante una
actualización genérica del post.

### Persistencia versionada

WordPress conserva una representación administrativa de cada solicitud como post del
CPT. La tabla interna `${prefix}vicu_payment_requests` es la fuente autoritativa de la
identidad, el monto, el proveedor, el estado, la revisión y el vencimiento. La tabla
interna `${prefix}vicu_payment_manual_submissions` conserva el historial idempotente
de referencias de comprobante del proveedor manual. El schema conjunto se identifica
con la opción `vicu_pagos_db_version`; la versión vigente es `2`.

El índice único de la referencia externa y las transacciones InnoDB protegen la
creación y las transiciones ante procesos concurrentes. Estos detalles son internos y
pueden evolucionar sin convertirse en una API para consumidores.

Las siguientes claves registradas reflejan la solicitud en el CPT:

| Clave | Tipo | Regla |
| --- | --- | --- |
| `vicu_external_type` | `string` | Slug ASCII en minúsculas, máximo 64 caracteres |
| `vicu_external_id` | `string` | Identificador opaco, máximo 191 caracteres |
| `vicu_amount_minor` | `integer` | Monto positivo en la unidad menor de la moneda |
| `vicu_currency` | `string` | Código ISO 4217 de tres letras mayúsculas |
| `vicu_payment_provider` | `string` | Código del proveedor actual o valor vacío |
| `vicu_payment_state` | `string` | Estado contractual actual |
| `vicu_payment_revision` | `integer` | Revisión monotónica, comienza en 1 |
| `vicu_expires_at` | `string` | Fecha UTC RFC 3339 o valor vacío |

No se almacenan montos en coma flotante. Un monto de USD 12,34 se representa como
`1234` y `USD`.

### Referencia externa e idempotencia

La identidad de origen es el par inmutable:

```text
(external_type, external_id)
```

`external_type` identifica el dominio propietario, no una clase o tabla interna.
`external_id` es opaco para pagos. No existe foreign key ni lectura inversa hacia el
vertical.

Repetir una creación con la misma referencia, monto, moneda y vencimiento devuelve la
solicitud existente sin emitir nuevamente `vicu_pagos_creado`. Reutilizar la
referencia con cualquiera de esos datos incompatibles devuelve un `WP_Error` con
código `vicu_pagos_reference_collision` y no persiste cambios parciales.

## Servicio público

La clase pública `Vicu\Pagos\PaymentRequests` ofrece métodos estáticos. Los resultados
de solicitud son arrays estables y los fallos son instancias de `WP_Error`.

### Crear

```php
$request = Vicu\Pagos\PaymentRequests::create(
	array(
		'external_type' => 'vicu_order',
		'external_id'   => 'ORD-42',
		'amount_minor'  => 1234,
		'currency'      => 'USD',
		'expires_at'    => '2026-08-14T18:00:00Z', // Opcional.
	)
);
```

### Consultar y transicionar

```php
$request = Vicu\Pagos\PaymentRequests::get( $request_id );

$updated = Vicu\Pagos\PaymentRequests::transition(
	$request_id,
	Vicu\Pagos\PaymentRequestState::CONFIRMED,
	$request['revision']
);
```

`expected_revision` es opcional, pero se recomienda transmitir la revisión leída por
el consumidor. Si ya cambió, el servicio devuelve
`vicu_pagos_concurrent_transition`. Aun cuando se omita, la escritura usa la revisión
vigente como compare-and-swap y nunca sobrescribe una transición concurrente.

Los resultados tienen esta forma:

```php
array(
	'id'                 => 123,
	'external_reference' => array(
		'type' => 'vicu_order',
		'id'   => 'ORD-42',
	),
	'amount_minor'       => 1234,
	'currency'           => 'USD',
	'provider'           => null,
	'state'              => 'pendiente',
	'revision'           => 1,
	'expires_at'         => '2026-08-14T18:00:00+00:00',
	'created_at'         => '2026-08-13T18:00:00+00:00',
	'updated_at'         => '2026-08-13T18:00:00+00:00',
);
```

Errores contractuales:

| Código | Significado |
| --- | --- |
| `vicu_pagos_invalid_request` | Datos de creación o transición inválidos |
| `vicu_pagos_reference_collision` | La referencia existe con datos incompatibles |
| `vicu_pagos_request_not_found` | La solicitud no existe |
| `vicu_pagos_invalid_transition` | La transición no pertenece a la máquina de estados |
| `vicu_pagos_concurrent_transition` | La revisión esperada quedó obsoleta |
| `vicu_pagos_storage_error` | La persistencia atómica no pudo completarse |

## Máquina de estados

Los valores se publican como constantes de `Vicu\Pagos\PaymentRequestState`:

- `PENDING`: `pendiente`;
- `PROOF_UPLOADED`: `comprobante_subido`;
- `CONFIRMED`: `confirmado`;
- `REJECTED`: `rechazado`;
- `EXPIRED`: `expirado`.

Transiciones permitidas:

| Origen | Destinos |
| --- | --- |
| `pendiente` | `comprobante_subido`, `expirado` |
| `comprobante_subido` | `confirmado`, `rechazado` |
| `rechazado` | `comprobante_subido`, `expirado` |
| `confirmado` | Ninguno |
| `expirado` | Ninguno |

Cada transición válida aumenta `revision` exactamente una vez. Una transición
inválida, terminal o concurrente no modifica persistencia ni emite hooks.

## Expiración

`PaymentRequests::expire( $request_id, $expected_revision )` aplica la transición a
`expirado`. Repetirla sobre una solicitud ya expirada devuelve el estado persistido
sin aumentar la revisión ni volver a emitir el hook.

`PaymentRequests::expire_due( $now, $limit )` procesa solicitudes vencidas en lotes.
El cron `vicu_pagos_expire_requests` lo ejecuta cada hora. La activación agenda un solo
evento y la desactivación lo retira. Los reintentos y ejecuciones solapadas son
seguros por la misma revisión atómica.

## Eventos públicos

Los hooks reciben un único argumento array con `payload_version` igual a `1.0.0`:

- `vicu_pagos_creado` después de crear y confirmar la persistencia;
- `vicu_pagos_confirmado` después de transicionar a `confirmado`;
- `vicu_pagos_rechazado` después de transicionar a `rechazado`;
- `vicu_pagos_expirado` después de transicionar a `expirado`.
- `vicu_pagos_comprobante_recibido` después de que el proveedor manual persiste una
  nueva referencia de comprobante y transiciona a `comprobante_subido`.

Los eventos generales tienen esta forma:

```php
array(
	'payload_version' => '1.0.0',
	'event'           => 'confirmado',
	'occurred_at'     => '2026-08-13T18:05:00+00:00',
	'transition'      => array(
		'from' => 'comprobante_subido',
		'to'   => 'confirmado',
	),
	'request'         => array(), // Resultado público completo de la solicitud.
);
```

El hook se dispara únicamente después de confirmar la transacción. Un callback puede
consultar inmediatamente `PaymentRequests::get()` y observar el mismo estado y
revisión incluidos en el payload.

## Proveedor manual v1

La clase pública `Vicu\Pagos\ManualPaymentProvider` implementa el proveedor con código
estable `manual`. No representa un banco, una cuenta, una interfaz ni un método de
entrega. Está deshabilitado por defecto y solo acepta esta configuración:

```php
$configuration = Vicu\Pagos\ManualPaymentProvider::configure(
	array( 'enabled' => true )
);

$configuration = Vicu\Pagos\ManualPaymentProvider::get_configuration();
```

`enabled` debe ser booleano y no se admiten claves adicionales. Una configuración
inválida devuelve `vicu_pagos_manual_invalid_configuration` sin cambiar la opción
persistida.

Un consumidor confiable entrega una referencia opaca de comprobante mediante:

```php
$result = Vicu\Pagos\ManualPaymentProvider::submit_proof(
	$request_id,
	array(
		'proof_reference' => 'evidence:ORD-42:1',
		'idempotency_key' => 'order-42-attempt-1',
	),
	$request['revision']
);
```

Ambos valores son strings no vacíos de máximo 191 caracteres después de normalizar
texto. No se admiten claves adicionales. Pagos no descarga, interpreta ni presenta
el recurso referido y nunca devuelve ni publica la clave de idempotencia.

La operación bloquea la solicitud, persiste una entrada histórica y aplica
atómicamente `pendiente|rechazado -> comprobante_subido`, asignando `provider` a
`manual` y aumentando la revisión una sola vez. El resultado contiene:

```php
array(
	'request'    => array(), // Resultado público completo de la solicitud.
	'submission' => array(
		'id'               => 456,
		'provider'         => 'manual',
		'proof_reference'  => 'evidence:ORD-42:1',
		'request_revision' => 2,
		'submitted_at'     => '2026-08-13T18:02:00+00:00',
	),
);
```

Repetir la misma clave para la misma solicitud y la misma referencia devuelve el
resultado persistido incluso si `expected_revision` quedó obsoleta, sin aumentar la
revisión ni repetir el evento. La misma clave con una referencia distinta devuelve
`vicu_pagos_manual_submission_collision`. Una clave nueva sí valida la revisión y la
máquina de estados vigentes. Ningún error deja una entrada histórica, cambia estado o
emite eventos.

`ManualPaymentProvider::get_submission( $request_id, $submission_id )` devuelve la
forma pública de una entrada o `vicu_pagos_manual_submission_not_found`. Confirmar o
rechazar una solicitud continúa usando `PaymentRequests::transition()`.

Errores propios:

| Código | Significado |
| --- | --- |
| `vicu_pagos_manual_invalid_configuration` | Configuración desconocida o de tipo inválido |
| `vicu_pagos_manual_provider_disabled` | El proveedor manual está deshabilitado |
| `vicu_pagos_manual_invalid_submission` | Payload de comprobante inválido |
| `vicu_pagos_manual_submission_collision` | La clave idempotente ya identifica otra referencia |
| `vicu_pagos_manual_submission_not_found` | La entrada no existe para la solicitud indicada |

El evento `vicu_pagos_comprobante_recibido` usa `payload_version` `1.0.0`, `event`
`comprobante_recibido`, la transición persistida, `provider` `manual`, el objeto
`submission` anterior y la solicitud completa. Se emite una sola vez y únicamente
después de confirmar la transacción.

## Capacidades

El CPT usa capacidades dedicadas con base singular `vicu_payment_request` y plural
`vicu_payment_requests`. Al activar el plugin se conceden al rol administrador las
capacidades primitivas necesarias para crear, editar, publicar, leer privados y
eliminar solicitudes.

No se conceden capacidades a editores, autores o suscriptores. Un sitio puede delegar
capacidades mediante las APIs de roles de WordPress bajo su propia política.

## Proveedores y límites

El proveedor manual v1 no implementa integración Mercantil, cuentas o instrucciones
bancarias, checkout, archivos, comprobantes visuales, presentación ni lógica de
pedidos o reservas. La referencia opaca no convierte a pagos en propietario del
recurso externo.

## Gestión de cambios

Cambiar el slug, la persistencia, la forma pública de resultados, los errores, los
estados o los hooks requiere identificar consumidores, actualizar este contrato y sus
pruebas, y coordinar cualquier ruptura en el hub.
