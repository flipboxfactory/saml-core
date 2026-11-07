# SAML Core

Shared SAML 2.0 library for Craft CMS.

[![CI](https://github.com/flipboxfactory/saml-core/actions/workflows/ci.yml/badge.svg)](https://github.com/flipboxfactory/saml-core/actions/workflows/ci.yml)
[![Latest Version](https://img.shields.io/packagist/v/flipboxfactory/saml-core.svg?style=flat-square)](https://packagist.org/packages/flipboxfactory/saml-core)
[![Total Downloads](https://img.shields.io/packagist/dt/flipboxfactory/saml-core.svg?style=flat-square)](https://packagist.org/packages/flipboxfactory/saml-core)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)

SAML Core holds the code shared by the [SAML SP](https://github.com/flipboxfactory/saml-sp) and [SAML IdP](https://github.com/flipboxfactory/saml-idp) Craft plugins: provider records and metadata, HTTP-POST/Redirect bindings, logout, and signing and encryption keys (via [KeyChain](https://github.com/flipboxfactory/keychain)). It is built on [simplesamlphp/saml2](https://github.com/simplesamlphp/saml2).

It is not a plugin. You normally don't install it directly; it comes in as a dependency of SAML SP or SAML IdP.

## Requirements

- Craft CMS 5 (saml-core 5.x)
- PHP 8.2+

## Installation

Install [SAML SP](https://github.com/flipboxfactory/saml-sp) or [SAML IdP](https://github.com/flipboxfactory/saml-idp). To require it directly:

```bash
composer require flipboxfactory/saml-core
```

## Development

Checks follow Craft's [coding guidelines](https://craftcms.com/docs/5.x/extend/coding-guidelines.html): [craftcms/ecs](https://github.com/craftcms/ecs) and [craftcms/phpstan](https://github.com/craftcms/phpstan). Tests use Codeception with Craft's test module and need a MySQL database:

```bash
docker run -d --rm --name saml-core-test-mysql -e MYSQL_ROOT_PASSWORD=password \
  -e MYSQL_DATABASE=saml_core_test -p 127.0.0.1:3306:3306 mysql:8.0
cp tests/.env.example tests/.env   # adjust CRAFT_DB_* if needed

composer check      # check-cs + phpstan + test
composer fix-cs     # auto-fix coding standards
```

CI runs the same checks on every push and pull request.

## Contributing

See [CONTRIBUTING](CONTRIBUTING.md).

## Credits

- [Flipbox Digital](https://github.com/flipbox)
- [garycrane](https://github.com/garycrane/)

## License

MIT. See [LICENSE](LICENSE.md).
