![Haroone Guest Order Link for Magento](docs/images/guest-order-link-banner.png)

# Haroone Guest Order Link

[![Package quality](https://github.com/haroonsulahri/magento-guest-order-link/actions/workflows/quality.yml/badge.svg)](https://github.com/haroonsulahri/magento-guest-order-link/actions/workflows/quality.yml)
[![Packagist](https://img.shields.io/badge/Packagist-haroone%2Fmodule--guest--order--link-f28d1a.svg)](https://packagist.org/packages/haroone/module-guest-order-link)
[![License: MIT](https://img.shields.io/badge/License-MIT-2563eb.svg)](LICENSE.txt)

Connect an eligible guest order to the matching customer account from Magento Admin.

> **Release status:** The package is available on [Packagist](https://packagist.org/packages/haroone/module-guest-order-link) as `dev-main`. A stable version has not been tagged yet, so production projects should wait for `v1.0.0`.

## Installation

### Composer installation

The package currently provides the `dev-main` development version:

```bash
composer require haroone/module-guest-order-link:dev-main

bin/magento module:enable Haroone_GuestOrderLink
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:clean
```

After the stable `v1.0.0` release is published, the recommended production command will be:

```bash
composer require haroone/module-guest-order-link:^1.0
```

### Manual installation

Download or clone the repository into:

```text
app/code/Haroone/GuestOrderLink
```

Then run from the Magento root:

```bash
bin/magento module:enable Haroone_GuestOrderLink
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:clean
```

No database tables are added. Reindexing and static-content deployment are not required for this module.

## Why this module exists

A shopper can place an order as a guest and create an account later with the same email address. Magento does not automatically make every earlier guest order belong to that new account. Support teams are then left with a customer who is signed in but cannot find the order under **My Account > My Orders**.

This module gives an authorized Admin user a controlled way to link one guest order to the exact matching account. It does not bulk-claim order history or let an Admin choose an arbitrary customer.

## What it does

- Adds **Link to Customer Account** immediately before **Edit** on an eligible Admin order.
- Shows the action only when an exact account-email match exists in the applicable customer-sharing scope.
- Displays a confirmation page before anything is changed.
- Revalidates the order and customer immediately before saving.
- Uses Magento's native customer-assignment service instead of direct SQL.
- Records a private audit comment with the trusted Admin display name.
- Refreshes the affected sales grid after assignment.
- Uses a per-order lock to reduce concurrent assignment attempts.
- Protects the UI and controllers with a dedicated ACL permission.

## Eligibility and safety

The action is available only when the order is still an unassigned guest order, contains an email address, and has one exact customer match in Magento's configured global or per-website account-sharing scope. The Admin role must also have the module permission.

The same checks run when the confirmation page opens and again when the POST request is submitted. A stale page therefore cannot bypass the rules.

The module deliberately does not:

- reassign an order that already belongs to a customer;
- match customers by name or a submitted customer ID;
- create customer accounts or addresses;
- bulk-link historical orders;
- change order status, totals, payment, addresses, items or quote data;
- add a storefront endpoint, telemetry or external service call.

After linking, storefront visibility still follows Magento's normal order-status configuration. The confirmation page warns the Admin when the current status is not visible on the storefront, but the module does not change that global setting.

## Requirements

- Magento Open Source or Adobe Commerce components compatible with the constraints in [`composer.json`](composer.json)
- PHP 8.1 or newer, provided that the PHP version is also supported by the installed Magento release
- An Admin role with `Haroone_GuestOrderLink::link`

The current package constraints begin with Magento 2.4.4-era component versions. A full public Magento compatibility matrix has not yet been completed. Test the module against the exact Magento and PHP combination you run before using it in production.

The module has been exercised on a local Magento 2.4.6-p12 installation. That local installation runs PHP 8.3 even though Magento 2.4.6-p12 declares PHP 8.1 or 8.2, so it is runtime evidence only and is not presented as an officially supported compatibility combination.

## Admin usage

1. Open **Sales > Orders**.
2. Open an unassigned guest order.
3. Click **Link to Customer Account** next to **Edit**.
4. Check the order and matched account on the confirmation page.
5. Click **Link Order to Customer**.
6. Confirm the order is assigned to the customer. If the order status is storefront-visible, confirm it also appears under **My Account > My Orders**.

Grant access under:

```text
Sales > Operations > Orders > Actions > Link Guest Orders to Customer Accounts
```

## Development and verification

Run the unit suite from a Magento root with development dependencies:

```bash
vendor/bin/phpunit -c app/code/Haroone/GuestOrderLink/phpunit.xml.dist
```

The repository's package-quality workflow validates Composer metadata, PHP syntax, XML, privacy checks and the Linux release archive. The separate Magento workflow installs a clean Magento instance and runs unit tests, coding standards, PHPStan, DI compilation and integration tests when `COMPOSER_AUTH` is configured.

See:

- [Validation guide](docs/validation.md)
- [Privacy and data handling](docs/privacy.md)
- [Release process](docs/releasing.md)
- [Brand assets](docs/branding.md)
- [Contributing](CONTRIBUTING.md)
- [Security policy](SECURITY.md)

## Privacy and security

The module sends no data outside Magento. Its private order-history comment contains only the trusted Admin display name. Structured application logs contain entity IDs only and never include customer names, email addresses, Admin names, submitted form values or exception objects.

Report security issues privately as described in [SECURITY.md](SECURITY.md). Do not put customer data, credentials, order exports or vulnerabilities in a public issue.

## License

Released under the [MIT License](LICENSE.txt).
