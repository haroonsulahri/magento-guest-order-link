# Contributing

Contributions are welcome when they preserve the module's security boundaries, privacy guarantees and Magento upgrade safety.

Participation is subject to the [Code of Conduct](CODE_OF_CONDUCT.md).

## Development requirements

- Use strict PHP types.
- Do not edit Magento core or use direct SQL for order assignment.
- Keep state-changing Admin actions POST-only and ACL-protected.
- Preserve Magento's configured customer-sharing scope.
- Do not add automatic historical linking without verified email ownership and a dedicated security review.
- Do not add telemetry, remote calls, production data, private URLs, local paths or personal contact details.
- Add or update tests for every behavior change.
- Keep the module independent of themes and unrelated modules.

## Validation

Run the following from a Magento root with development dependencies installed:

```bash
find app/code/Haroone/LinkGuestOrderToCustomer -name '*.php' -print0 \
  | xargs -0 -n1 php -l

vendor/bin/phpunit -c app/code/Haroone/LinkGuestOrderToCustomer/phpunit.xml.dist
vendor/bin/phpcs --standard=app/code/Haroone/LinkGuestOrderToCustomer/phpcs.xml.dist
vendor/bin/phpstan analyse -c app/code/Haroone/LinkGuestOrderToCustomer/phpstan.neon.dist
composer validate app/code/Haroone/LinkGuestOrderToCustomer/composer.json --strict --no-check-publish
php app/code/Haroone/LinkGuestOrderToCustomer/dev/check-release.php \
  app/code/Haroone/LinkGuestOrderToCustomer
bin/magento setup:di:compile
```

Run integration tests only with Magento's dedicated integration-test database:

```bash
php app/code/Haroone/LinkGuestOrderToCustomer/dev/check-integration-config.php \
  dev/tests/integration/etc/install-config-mysql.php.dist \
  app/etc/env.php

cd dev/tests/integration
../../../vendor/bin/phpunit -c phpunit.xml.dist \
  ../../../app/code/Haroone/LinkGuestOrderToCustomer/Test/Integration
```

Never point the Magento integration framework at a development, staging or production database. See [docs/validation.md](docs/validation.md) for the functional matrix.
