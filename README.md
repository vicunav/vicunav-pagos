# Vicunav Pagos

Independent payment engine for the Vicunav WordPress ecosystem.

## Status

Version 0.3.0 provides an installable payment lifecycle plus a minimal manual payment
provider. Manual proof references are persisted atomically with idempotency,
concurrency protection, explicit collision errors, and a versioned post-commit event.

Banking integrations, checkout, file handling, proof-of-payment UI, and restaurant or
reservation logic remain intentionally out of scope.

## Responsibilities

`vicunav-pagos` owns payment requests and their lifecycle without knowing the internal
model of a restaurant order, hotel reservation, or future vertical. Each request
references its source through a stable type and opaque identifier pair.

The repository owns the `Vicu\Pagos` PHP namespace. Its public services, persistence
rules, errors, capabilities, and events are defined in
[`docs/contrato-publico.md`](docs/contrato-publico.md).

## Requirements

- WordPress 6.6 or later.
- PHP 8.1 or later.
- [`vicunav-plugin-core`](https://github.com/vicunav/vicunav-plugin-core) 0.1.0 or
  later within contract major 1.

Install both plugins and activate **Vicunav Plugin Core** before **Vicunav Pagos**.

## Public lifecycle

- `Vicu\Pagos\PaymentRequests::create()` creates or returns a request by external
  reference.
- `Vicu\Pagos\PaymentRequests::get()` returns a stable public array without exposing
  post meta or internal tables.
- `Vicu\Pagos\PaymentRequests::transition()` applies allowed state changes with an
  optional expected revision.
- `Vicu\Pagos\PaymentRequests::expire()` and `expire_due()` provide safe, repeatable
  expiration.
- `Vicu\Pagos\ManualPaymentProvider::configure()` enables or disables the manual
  provider through its complete v1 configuration.
- `Vicu\Pagos\ManualPaymentProvider::submit_proof()` stores an opaque proof reference
  and transitions the request atomically. Exact retries return the original result.
- `Vicu\Pagos\ManualPaymentProvider::get_submission()` returns a stable public result
  without exposing the idempotency key or internal history table.
- Lifecycle and manual proof hooks publish payload schema `1.0.0` only after
  persistence commits.

The private `vicu_payment_req` post type and
`/wp-json/wp/v2/vicu-payment-requests` collection remain protected administrative
interfaces. Business plugins use the public PHP service and events instead.

## Boundaries

- It does not contain presentation, templates, patterns, or theme styling.
- It does not contain order, reservation, room, menu, or inventory logic.
- It does not read another plugin's database or post metadata.
- It does not require ACF or implement banking APIs, account configuration, proof
  files, or provider-specific presentation.

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

The integration suite requires an isolated MySQL database. The E2E script additionally
loads an active LocalWP site and exercises the real plugin lifecycle. See
[`docs/pruebas.md`](docs/pruebas.md).

Contributions follow an atomic issue, branch, pull request, and squash-merge workflow.
See [`CONTRIBUTING.md`](CONTRIBUTING.md).

## License

This project is licensed under the [GPL-2.0-or-later](LICENSE) license.
