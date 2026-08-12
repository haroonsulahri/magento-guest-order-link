# Upgrading from Guest Order Link 1.x

Version 2.0.0 is a breaking technical rename. The order-linking behavior and stored Magento sales data are unchanged, but the Composer package, Magento module, PHP namespace, Admin route, ACL resource and installation directory have new names.

## Identifier map

| 1.x | 2.0.0 |
| --- | --- |
| `haroone/module-guest-order-link` | `haroone/module-link-guest-order-to-customer` |
| `Haroone_GuestOrderLink` | `Haroone_LinkGuestOrderToCustomer` |
| `Haroone\GuestOrderLink` | `Haroone\LinkGuestOrderToCustomer` |
| `app/code/Haroone/GuestOrderLink` | `app/code/Haroone/LinkGuestOrderToCustomer` |
| `haroone_guestorderlink` | `haroone_linkguestordertocustomer` |
| `Haroone_GuestOrderLink::link` | `Haroone_LinkGuestOrderToCustomer::link` |
| `haroone-guest-order-link` | `haroone-link-guest-order-to-customer` |

## Composer installation upgrade

Back up `composer.json`, `composer.lock` and `app/etc/config.php`, then run from the Magento root:

```bash
bin/magento module:disable Haroone_GuestOrderLink

composer remove haroone/module-guest-order-link --no-update
composer require haroone/module-link-guest-order-to-customer:^2.0 --no-update
composer update haroone/module-guest-order-link haroone/module-link-guest-order-to-customer --with-all-dependencies

bin/magento module:enable Haroone_LinkGuestOrderToCustomer
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:clean
```

The new package conflicts with `haroone/module-guest-order-link`, so Composer cannot keep both packages installed. Do not bypass that conflict with `replace`, a path repository or a manually copied second module.

## Manual installation upgrade

Disable the old module before moving its source out of `app/code`:

```bash
bin/magento module:disable Haroone_GuestOrderLink
```

Remove or archive only this verified old directory:

```text
app/code/Haroone/GuestOrderLink
```

Install 2.0.0 into:

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

## After upgrading

- Re-grant **Sales > Operations > Orders > Actions > Link Guest Order to Customer** to restricted Admin roles. The ACL resource ID changed, so a role that explicitly held the 1.x permission does not inherit the 2.0.0 permission automatically.
- Update custom integrations, tests or preferences that reference the 1.x PHP namespace, route or ACL resource.
- Confirm only `Haroone_LinkGuestOrderToCustomer` is enabled.
- Open an eligible guest order and confirm the **Link Guest Order to Customer** action appears immediately before **Edit**.
- Confirm ineligible and already-assigned orders do not show the action.

No schema or data migration is required. The extension has no custom database tables, and existing order-to-customer assignments remain Magento sales data. Magento may retain the disabled 1.x module entry in `setup_module`; it does not need to be deleted.

## Rollback to 1.x

If rollback is necessary, disable 2.0.0, remove its package, restore the 1.x package and enable the old module:

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

For a manual rollback, remove only `app/code/Haroone/LinkGuestOrderToCustomer`, restore the archived `app/code/Haroone/GuestOrderLink` directory, then run the same Magento enable and deployment commands for the 1.x module.
