# SAML Core

Shared SAML 2.0 library for Craft CMS.

[![CI](https://github.com/flipboxfactory/saml-core/actions/workflows/ci.yml/badge.svg)](https://github.com/flipboxfactory/saml-core/actions/workflows/ci.yml)
[![Latest Version](https://img.shields.io/packagist/v/flipboxfactory/saml-core.svg?style=flat-square)](https://packagist.org/packages/flipboxfactory/saml-core)
[![Total Downloads](https://img.shields.io/packagist/dt/flipboxfactory/saml-core.svg?style=flat-square)](https://packagist.org/packages/flipboxfactory/saml-core)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)

SAML Core holds the code shared by the [SAML SP](https://github.com/flipboxfactory/saml-sp) and [SAML IdP](https://github.com/flipboxfactory/saml-idp) Craft plugins: provider records and metadata, HTTP-POST/Redirect bindings, logout, and signing and encryption keys (via [KeyChain](https://github.com/flipboxfactory/keychain)). It is built on [simplesamlphp/saml2](https://github.com/simplesamlphp/saml2).

It is not a plugin. You normally don't install it directly; it comes in as a dependency of SAML SP or SAML IdP.

## Requirements

- saml-core 6.x: Craft CMS 6 and PHP 8.5. It's a native Craft 6 library: no Yii2 adapter.
- saml-core 5.x: Craft CMS 5 and PHP 8.2+.

## Installation

Install [SAML SP](https://github.com/flipboxfactory/saml-sp) or [SAML IdP](https://github.com/flipboxfactory/saml-idp). To require it directly:

```bash
composer require flipboxfactory/saml-core
```

## For plugin developers (6.x)

A SAML plugin extends `AbstractPlugin` and provides its records, services and controllers:

- `getProviderRecordClass()` / `getProviderIdentityRecordClass()`: Eloquent models extending `records\AbstractProvider` / `records\AbstractProviderIdentity`, with a `TABLE_ALIAS`.
- `services()`: adds at least `provider` and `providerIdentity` (subclasses of `services\AbstractProviderService` / `AbstractProviderIdentityService`). Each plugin has its own service instances.
- `controllers()`: classes for the routes saml-core registers. The SAML endpoints (`{endpointPrefix}/login`, `/logout` and their `/request` URLs) keep their 5.x URLs and skip CSRF; the control panel screens and form actions require an admin.
- `database/migrations/Install.php`: a migration extending `migrations\AbstractInstall`.

Incoming messages are only parsed by `services\bindings\Factory::receive()`; verify them with `helpers\SignatureVerifier`, which fails closed (no signing keys, unsigned, or no key verifies).

## Development

Tests use Pest with Orchestra Testbench and a fixture SAML plugin (`tests/fixtures/plugin`). They run on SQLite by default; set `DB_CONNECTION`, `DB_HOST`, `DB_DATABASE`, `DB_USERNAME` and `DB_PASSWORD` for MySQL or PostgreSQL. Style is checked with Pint, static analysis with Larastan.

```bash
composer check      # check-cs + phpstan + test
composer fix-cs     # auto-fix code style
```

CI runs the same checks on SQLite, MySQL and PostgreSQL for every push and pull request.

## Contributing

See [CONTRIBUTING](CONTRIBUTING.md).

## Credits

- [Flipbox Digital](https://github.com/flipbox)
- [garycrane](https://github.com/garycrane/)

## License

MIT. See [LICENSE](LICENSE.md).
