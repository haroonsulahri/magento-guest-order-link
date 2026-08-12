# Haroone Guest Order Link for Magento 2

Safely link an existing guest order to the matching registered customer account from Magento Admin.

Magento can associate an order when a guest creates an account through its post-checkout registration flow, but it does not provide a general Admin action for orders that remain unassigned after the customer registers later. This module adds that operational workflow without modifying Magento core files.

## Features

- Adds **Link to Customer Account** immediately before **Edit** on eligible Admin order pages.
- Shows the action only when an exact customer-email match exists in Magento's applicable customer-sharing scope.
- Presents a confirmation page before changing the order.
- Revalidates eligibility immediately before saving.
- Uses Magento's native customer-assignment service instead of direct SQL.
- Adds a private audit comment containing the trusted Admin display name, without customer IDs, customer emails, or Admin IDs.
- Refreshes the affected sales grid after a successful assignment.
- Uses a per-order lock to reduce concurrent assignment risk.
- Protects the button and controllers with a dedicated ACL resource.
- Adds no database tables, storefront assets, telemetry, or external service calls.

## Safety boundaries

The module does not:

- automatically link historical orders by email;
- reassign an order that already belongs to a customer;
- accept a customer ID from the request;
- link across websites when customer accounts are shared per website;
- modify order state, status, totals, payment, addresses, quote, or items;
- change global order-status storefront visibility;
- create or update customer addresses;
- expose a customer-facing linking endpoint.

## Compatibility

The Composer constraints target Magento Open Source and Adobe Commerce 2.4.4 or newer, with a PHP version supported by the installed Magento release.

| Magento | PHP | Verification status |
| --- | --- | --- |
| 2.4.6-p12 | 8.3 | Unit, static, compilation, package and local runtime verification completed |
| Other releases allowed by `composer.json` | Magento-supported version | Not yet verified in the public CI matrix |

Compatibility outside the verified row should be tested in a disposable Magento environment before production deployment.

## Installation with Composer

After the package is published to a Composer repository:

```bash
composer require haroone/module-guest-order-link
bin/magento module:enable Haroone_GuestOrderLink
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:clean
```

## Manual installation

Copy the module to:

```text
app/code/Haroone/GuestOrderLink
```

Then run:

```bash
bin/magento module:enable Haroone_GuestOrderLink
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:clean
```

The module has no declarative schema and does not require reindexing or static-content deployment.

## Admin usage

1. Open **Sales > Orders**.
2. Open an unassigned guest order.
3. Click **Link to Customer Account**.
4. Review the order and resolved customer account.
5. Click **Link Order to Customer**.
6. Confirm the order appears in the customer's **My Orders** collection.

The action is hidden when no exact match exists, the order is already assigned, the order is not a guest order, or the Admin role lacks permission.

## Permission

Grant the role permission under:

```text
Sales > Operations > Orders > Actions > Link Guest Orders to Customer Accounts
```

The ACL resource is `Haroone_GuestOrderLink::link`.

## Testing

Run the unit suite from a Magento root containing development dependencies:

```bash
vendor/bin/phpunit -c app/code/Haroone/GuestOrderLink/phpunit.xml.dist
```

Run the Magento integration test in a dedicated integration-test database:

```bash
cd dev/tests/integration
../../../vendor/bin/phpunit -c phpunit.xml.dist \
  ../../../app/code/Haroone/GuestOrderLink/Test/Integration
```

See [docs/validation.md](docs/validation.md) for the complete test matrix and [docs/privacy.md](docs/privacy.md) for data-handling details.

The dependency-free package workflow runs on every push and pull request. Maintainers can run the Magento verification workflow after configuring the repository's `COMPOSER_AUTH` secret for `repo.magento.com`; it installs a clean Magento instance and runs unit, coding-standard, PHPStan, DI compilation and integration checks.

## Support and security

Use the repository's Issues tab for non-sensitive defects and feature requests. Follow [SECURITY.md](SECURITY.md) for private vulnerability reporting.

## License

MIT. See [LICENSE.txt](LICENSE.txt).
