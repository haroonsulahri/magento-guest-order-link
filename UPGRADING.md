# Migrating from Guest Order Link

The renamed extension was first published as version 1.0.1. This is not an in-place Composer update because the Composer package, Magento module, PHP namespace, Admin route, ACL resource and installation directory have new names. The order-linking behavior and stored Magento sales data are unchanged.

## Identifier map

| Legacy package | Current package (v1.0.1 and later) |
| --- | --- |
| `haroone/module-guest-order-link` | `haroone/module-link-guest-order-to-customer` |
| `Haroone_GuestOrderLink` | `Haroone_LinkGuestOrderToCustomer` |
| `Haroone\GuestOrderLink` | `Haroone\LinkGuestOrderToCustomer` |
| `app/code/Haroone/GuestOrderLink` | `app/code/Haroone/LinkGuestOrderToCustomer` |
| `haroone_guestorderlink` | `haroone_linkguestordertocustomer` |
| `Haroone_GuestOrderLink::link` | `Haroone_LinkGuestOrderToCustomer::link` |
| `haroone-guest-order-link` | `haroone-link-guest-order-to-customer` |

## Composer migration

Back up `composer.json`, `composer.lock` and `app/etc/config.php`, then run from the Magento root:

```bash
bin/magento module:disable Haroone_GuestOrderLink

composer remove haroone/module-guest-order-link --no-update
composer require haroone/module-link-guest-order-to-customer:^1.0.2 --no-update
composer update haroone/module-guest-order-link haroone/module-link-guest-order-to-customer --with-all-dependencies

bin/magento module:enable Haroone_LinkGuestOrderToCustomer
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:clean
```

The current package conflicts with `haroone/module-guest-order-link`, so Composer cannot keep both packages installed. Do not bypass that conflict with `replace`, a path repository or a manually copied second module.

## Manual migration

Disable the old module before moving its source out of `app/code`:

```bash
bin/magento module:disable Haroone_GuestOrderLink
```

Remove or archive only the following verified legacy directory:

```text
app/code/Haroone/GuestOrderLink
```

Install the current release into:

```text
app/code/Haroone/LinkGuestOrderToCustomer
```

Then run:

```bash
bin/magento module:enable Haroone_LinkGuestOrderToCustomer
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:clean
```

## After migrating

- Re-grant **Sales > Operations > Orders > Actions > Link Guest Order to Customer** to restricted Admin roles. The ACL resource ID changed, so a role that explicitly held the legacy permission does not inherit the new permission automatically.
- Update custom integrations, tests or preferences that reference the legacy PHP namespace, route or ACL resource.
- Confirm only `Haroone_LinkGuestOrderToCustomer` is enabled.
- Open an eligible guest order and confirm the **Link to Customer Account** action appears immediately before **Edit**.
- Confirm ineligible and already-assigned orders do not show the action.

No schema or data migration is required. The extension has no custom database tables, and existing order-to-customer assignments remain Magento sales data. Magento may retain the disabled legacy module entry in `setup_module`; it does not need to be deleted.

## Rollback to the legacy package

If rollback is necessary, disable the current module, remove its package, restore the legacy package and enable the old module:

```bash
bin/magento module:disable Haroone_LinkGuestOrderToCustomer

composer remove haroone/module-link-guest-order-to-customer --no-update
composer require haroone/module-guest-order-link:^1.0 --no-update
composer update haroone/module-link-guest-order-to-customer haroone/module-guest-order-link --with-all-dependencies

bin/magento module:enable Haroone_GuestOrderLink
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:clean
```

For a manual rollback, remove only `app/code/Haroone/LinkGuestOrderToCustomer`, restore the archived `app/code/Haroone/GuestOrderLink` directory, then run the same Magento enable and deployment commands for the legacy module.
