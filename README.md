# Vicunav Pagos

Independent payment engine for the Vicunav WordPress ecosystem.

## Status

The initial 0.1.0 foundation provides an installable plugin, the
`vicu_payment_req` post type, protected administrative REST access, and a versioned
contract for external references and payment request metadata.

State transitions, lifecycle events, expiration, idempotent creation, and the manual
payment provider are intentionally reserved for later atomic changes.

## Responsibilities

`vicunav-pagos` owns payment requests and the payment lifecycle without knowing the
internal model of a restaurant order, hotel reservation, or future vertical. Each
request references its source through a stable type and identifier pair.

The repository owns the `Vicu\Pagos` PHP namespace. Its public boundary, persistence
rules, capabilities, and REST behavior are defined in
[`docs/contrato-publico.md`](docs/contrato-publico.md).

## Requirements

- WordPress 6.6 or later.
- PHP 8.1 or later.
- [`vicunav-plugin-core`](https://github.com/vicunav/vicunav-plugin-core) 0.1.0 or
  later within contract major 1.

Install both plugins and activate **Vicunav Plugin Core** before **Vicunav Pagos**.

## Initial capabilities

- Private `vicu_payment_req` post type with dedicated capabilities.
- Administrative REST collection at `/wp-json/wp/v2/vicu-payment-requests`.
- External source type and identifier metadata.
- Integer minor-unit amount and uppercase ISO 4217 currency metadata.
- Capabilities granted to administrators on activation.

The WordPress REST endpoint is an administrative interface, not the integration API
for business verticals. Consumers must use the public services and events introduced
by later contract versions.

## Boundaries

- It does not contain presentation, templates, patterns, or theme styling.
- It does not contain order, reservation, room, menu, or inventory logic.
- It does not read another plugin's database or post metadata.
- It does not require ACF and does not implement banking provider APIs in this phase.

## Development

Initialize shared standards and install dependencies:

```bash
git submodule update --init --recursive
composer install
```

Run the complete validation suite:

```bash
composer check
```

The integration suite requires an isolated MySQL database. See
[`docs/pruebas.md`](docs/pruebas.md).

Contributions follow an atomic issue, branch, pull request, and squash-merge workflow.
See [`CONTRIBUTING.md`](CONTRIBUTING.md).

## License

This project is licensed under the [GPL-2.0-or-later](LICENSE) license.
