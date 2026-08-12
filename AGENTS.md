# vicunav-pagos

Propósito: Motor de pagos independiente para los plugins WordPress del ecosistema
Vicunav.

## Responsabilidades y límites

Este repositorio es propietario del namespace `Vicu\Pagos`, del CPT
`vicu_payment_req`, de los estados de pago y de los eventos públicos de su dominio.
Las reservas, los pedidos y cualquier otra operación externa se relacionan mediante
una referencia polimórfica, sin leer persistencia interna de otro plugin.

Depende de `vicunav-plugin-core` para la base de tipos de contenido, seguridad y REST.
No contiene presentación, checkout específico de un vertical, lógica de pedidos o
reservas ni integraciones bancarias no aprobadas.

El contrato vigente está en [`docs/contrato-publico.md`](docs/contrato-publico.md).
No ampliar firmas, estados, hooks o persistencia fuera de un Issue que actualice el
contrato y sus pruebas.

## Reglas aplicables

Las reglas transversales del repositorio están en
[`docs/standards/`](docs/standards/). Consúltalas antes de realizar cambios.

No repitas esas reglas aquí; este archivo solo contiene el contexto específico del
repositorio.

## Validación

```sh
composer check &&
git diff --check &&
git submodule status &&
! rg -n '\{\{|\}\}' --glob '!docs/standards/**' .
```

PHPUnit necesita una base MySQL aislada. La configuración y el flujo LocalWP están en
[`docs/pruebas.md`](docs/pruebas.md).

## Publicación

- No modificar manualmente `CHANGELOG.md` ni archivos autogenerados.
- No crear tags, releases o despliegues sin instrucción explícita.
- Todo cambio técnico usa un Issue, una rama, un PR y squash-merge.
- La documentación pública se escribe en inglés; la documentación interna y los
  comentarios de código se escriben en español.
