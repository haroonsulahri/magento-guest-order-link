# Validation Guide

## Verification contract

Target: an eligible guest order in a disposable Magento environment.

Expected outcome: the order is assigned to the matching customer account and appears in that customer's order collection.

Must remain unchanged: order state, status, totals, payment, items, billing and shipping records, quote data and global storefront-status configuration.

## Automated checks

Run from the Magento root:

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

Run the database-backed test separately:

```bash
php app/code/Haroone/LinkGuestOrderToCustomer/dev/check-integration-config.php \
  dev/tests/integration/etc/install-config-mysql.php.dist \
  app/etc/env.php

cd dev/tests/integration
../../../vendor/bin/phpunit -c phpunit.xml.dist \
  ../../../app/code/Haroone/LinkGuestOrderToCustomer/Test/Integration
```

Magento integration tests install and clean their own database. Verify the integration configuration before running them and never use a database containing business data.

When the Magento integration test framework is unavailable, run the transactional runtime smoke test from the Magento root. It creates generated records inside a database transaction, verifies persistence and rolls the transaction back:

```bash
php app/code/Haroone/LinkGuestOrderToCustomer/dev/runtime-smoke.php
```

The command must report that the runtime assignment passed and the rollback was verified.

## Functional matrix

| Case | Expected result |
| --- | --- |
| Guest order, exact email, global sharing | Link succeeds |
| Guest order, exact email, per-website sharing | Link succeeds only within the order website |
| Guest order, no matching customer | Button hidden; direct requests rejected without changing the order |
| Guest order, customer exists on another website | Rejected in per-website mode |
| Order already assigned to the same customer | Rejected as no longer eligible |
| Order assigned to a different customer | Rejected without reassignment |
| Guest order without customer email | Rejected without repository lookup |
| Concurrent link request for the same order | Second request is rejected by the lock |
| Admin role lacks module ACL | Button hidden and controller denied |
| Order status invisible on storefront | Confirmation warns; global status mapping remains unchanged |

## Persistence checks

After a successful disposable test assignment, verify:

- `sales_order.customer_id` matches the resolved customer;
- `sales_order.customer_is_guest` is false;
- the private history comment exists, is not visible on the storefront and does not notify the customer;
- the history comment contains no customer ID, customer email or Admin ID;
- the order is returned by the customer's order collection;
- `sales_order_grid` reflects the assigned customer.

Compare before and after values for:

- order state and status;
- grand total and base grand total;
- payment method and payment record;
- order items and quantities;
- billing and shipping address records;
- quote ownership and quote data;
- `sales_order_status_state.visible_on_front`.

Use only generated fixture records or an enclosing transaction that is rolled back. Never test with a real customer order.
