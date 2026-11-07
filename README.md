# SAML Core
# A dependency library for SAML SP and IDP
[![Latest Version](https://img.shields.io/github/release/flipboxfactory/saml-core.svg?style=flat-square)](https://github.com/flipboxfactory/saml-core/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)

## Installation

To install, use composer:

```
composer require flipboxfactory/saml-core
```

## Testing

Checks follow Craft's [coding guidelines](https://craftcms.com/docs/5.x/extend/coding-guidelines.html): [craftcms/ecs](https://github.com/craftcms/ecs) and [craftcms/phpstan](https://github.com/craftcms/phpstan). Tests use Codeception with Craft's test module, which needs a MySQL database:

```bash
docker run -d --rm --name saml-core-test-mysql -e MYSQL_ROOT_PASSWORD=password \
  -e MYSQL_DATABASE=saml_core_test -p 127.0.0.1:3306:3306 mysql:8.0
cp tests/.env.example tests/.env   # adjust CRAFT_DB_* if needed

composer check      # check-cs + phpstan + test
composer test       # tests only
composer fix-cs     # auto-fix coding standards
```

CI runs the same checks on every push and pull request (`.github/workflows/ci.yml`).

## Contributing

Please see [CONTRIBUTING](https://github.com/flipboxfactory/saml-core/blob/master/CONTRIBUTING.md) for details.


## Credits

- [Flipbox Digital](https://github.com/flipbox)
- [garycrane](https://github.com/garycrane/)

## License

The MIT License (MIT). Please see [License File](https://github.com/flipboxfactory/saml-core/blob/master/LICENSE) for more information.
