# Pruebas y validación

## Dependencias

La instalación se reproduce desde `composer.lock`. Composer instala WordPress 6.9,
wp-phpunit, WordPress Coding Standards, PHPCompatibilityWP y la release 0.1.0 de
`vicunav-plugin-core` usada por la integración.

```sh
composer install
```

La compatibilidad mínima declarada es WordPress 6.6 y PHP 8.1. CI ejecuta PHP 8.4 y
PHPCompatibilityWP comprueba la sintaxis mínima de PHP 8.1.

## Base de datos aislada

PHPUnit necesita una base MySQL de desarrollo y un prefijo exclusivo:

```sh
export WP_TESTS_DB_NAME=wordpress_test
export WP_TESTS_DB_USER=root
export WP_TESTS_DB_PASSWORD=root
export WP_TESTS_DB_HOST=127.0.0.1
export WP_TESTS_TABLE_PREFIX=wptests_vicu_pagos_
```

En LocalWP, `WP_TESTS_DB_HOST` puede incluir el socket:

```text
localhost:/ruta/local/run/identificador/mysql/mysqld.sock
```

La suite crea y elimina únicamente las tablas con el prefijo indicado. Nunca se apunta
a producción ni se reutiliza el prefijo de un sitio real.

## Comandos

```sh
composer check
git diff --check
git submodule status
! rg -n '\{\{|\}\}' --glob '!docs/standards/**' .
```

La activación real se verifica en WordPress con `vicunav-plugin-core` activo y el
socket correcto. Un error HTML de conexión a base de datos cuenta como fallo aunque el
proceso PHP termine con código cero.
