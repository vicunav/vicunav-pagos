# Contrato público de `vicunav-pagos`

Estado: vigente para el contrato 0.1.0 y el plugin 0.1.0.

Este documento fija la superficie inicial del motor de pagos. Las capacidades
implementadas se distinguen de los nombres reservados para fases posteriores; ningún
consumidor debe llamar una API que este contrato marque como pendiente.

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

El entry point `vicunav-pagos.php` registra el autoloader de `Vicu\Pagos`. El bootstrap
se ejecuta en `plugins_loaded` con prioridad 10, después de la prioridad 5 publicada
por plugin core.

Si `Vicu\Core\PostType` no está disponible, pagos no registra comportamiento y muestra
un aviso administrativo a usuarios con `activate_plugins`.

El action siguiente indica que la superficie implementada está disponible:

```php
do_action(
	'vicu_pagos_loaded',
	VICU_PAGOS_VERSION,
	VICU_PAGOS_CONTRACT_VERSION
);
```

## Solicitudes de pago

### CPT

- Slug estable: `vicu_payment_req`.
- No es público ni consultable en frontend.
- Tiene interfaz administrativa bajo el menú Vicunav.
- Admite título y custom fields registrados.
- Usa capabilities propias y `map_meta_cap`.
- Expone un controlador REST administrativo en
  `/wp-json/wp/v2/vicu-payment-requests`.

El endpoint REST no es el contrato de integración entre plugins. Solo permite gestión
a usuarios con las capabilities del CPT. Una llamada anónima o de un rol sin permisos
se rechaza.

### Persistencia inicial

WordPress conserva cada solicitud como un post del CPT. PAGOS-01 registra estas claves:

| Clave | Tipo | Regla |
| --- | --- | --- |
| `vicu_external_type` | `string` | Slug ASCII en minúsculas, máximo 64 caracteres |
| `vicu_external_id` | `string` | Identificador opaco de la operación, máximo 191 caracteres |
| `vicu_amount_minor` | `integer` | Monto positivo en la unidad menor de la moneda |
| `vicu_currency` | `string` | Código ISO 4217 de tres letras mayúsculas |

No se almacenan montos en coma flotante. Un monto de USD 12,34 se representa como
`1234` y `USD`.

Estas claves son parte del schema REST administrativo, pero no autorizan a otro plugin
a leerlas directamente. La API de negocio posterior será la frontera entre paquetes.

### Referencia externa

La identidad de origen es el par inmutable:

```text
(vicu_external_type, vicu_external_id)
```

`vicu_external_type` identifica el dominio propietario, no una clase o tabla interna.
`vicu_external_id` es opaco para pagos. No existe foreign key ni lectura inversa hacia
el vertical.

La unicidad e idempotencia del par se implementarán junto con el servicio de creación.
PAGOS-01 no promete todavía creación idempotente.

## Capacidades

El CPT usa capacidades dedicadas con base singular `vicu_payment_request` y plural
`vicu_payment_requests`. Al activar el plugin se conceden al rol administrador las
capacidades primitivas necesarias para crear, editar, publicar, leer privados y
eliminar solicitudes.

No se conceden capacidades a editores, autores o suscriptores. Un sitio puede delegar
capacidades mediante las APIs de roles de WordPress bajo su propia política.

## Vocabulario reservado para PAGOS-02

Los estados contractuales serán:

- `pendiente`;
- `comprobante_subido`;
- `confirmado`;
- `rechazado`;
- `expirado`.

Transiciones permitidas:

| Origen | Destinos |
| --- | --- |
| `pendiente` | `comprobante_subido`, `expirado` |
| `comprobante_subido` | `confirmado`, `rechazado` |
| `rechazado` | `comprobante_subido`, `expirado` |
| `confirmado` | Ninguno |
| `expirado` | Ninguno |

PAGOS-01 no persiste ni modifica estos estados. PAGOS-02 debe implementar las
transiciones de forma atómica, validar concurrencia y emitir los eventos solo después
de persistir el nuevo estado.

## Eventos reservados

- `vicu_pagos_creado`;
- `vicu_pagos_confirmado`;
- `vicu_pagos_rechazado`;
- `vicu_pagos_expirado`.

PAGOS-01 no emite estos hooks. PAGOS-02 definirá y probará su payload versionado antes
de que un vertical los consuma.

## Idempotencia y proveedores

PAGOS-02 debe cerrar el contrato de clave idempotente, colisiones, expiración y
concurrencia antes de publicar su servicio de creación. PAGOS-03 implementará el
proveedor manual detrás de esa superficie.

La integración Mercantil no forma parte de la versión inicial y requiere otro contrato
e Issue.

## Gestión de cambios

Cambiar el slug, las claves persistidas, las capacidades, el REST base, los estados o
los hooks requiere identificar consumidores, actualizar este contrato y sus pruebas,
y coordinar cualquier ruptura en el hub.
