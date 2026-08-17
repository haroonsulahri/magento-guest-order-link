# Changelog

All notable changes to this project are documented in this file.

## [Unreleased]

## [1.0.2] - 2026-08-17

### Added

- Automated GitHub release creation and verified ZIP upload for release tags.

### Changed

- Shortened the eligible Admin order action label to **Link to Customer Account**.

## [1.0.1] - 2026-08-13

### Changed

- Renamed the product to **Link Guest Order to Customer for Magento 2**.
- Renamed the Composer package to `haroone/module-link-guest-order-to-customer`.
- Renamed the Magento module to `Haroone_LinkGuestOrderToCustomer`.
- Renamed the PHP namespace to `Haroone\LinkGuestOrderToCustomer`.
- Renamed the Admin route, ACL resource, button ID, source directory and release archive consistently.
- Changed the Admin action label to **Link Guest Order to Customer**.
- Added a Composer conflict and a documented migration path from the 1.x package.
- Replaced the repository banner and renamed the extension artwork files.

### Breaking

- The legacy Composer package and Magento module must be disabled and removed before the renamed package is installed.
- Integrations referencing legacy PHP classes, ACL resources or Admin routes must use the new technical identifiers.

### Preserved

- Order eligibility, exact-email matching, website scope, confirmation, locking, assignment, audit history and sales-grid refresh behavior are unchanged.

## [1.0.0] - 2026-08-12

### Added

- ACL-protected Admin action for assigning an eligible guest order.
- Scope-aware exact-email customer resolution and confirmation page.
- Magento-native customer assignment with a per-order lock.
- Targeted sales-grid refresh and private order-history audit comment.
- Unit and Magento integration tests.
- Linux-safe release builder and package-quality CI.
- Public validation, privacy, security, contribution and release documentation.
- Canonical repository banner, extension icon and Haroone brand assets.
- Structured bug and feature request forms.

### Security

- Customer identity is resolved from server-side order data.
- Existing customer assignments cannot be replaced.
- Private history comments contain no customer ID, customer email or Admin ID.
- Application logs contain entity IDs only, with no exception objects, names or email addresses.
- Public source and release artifacts are checked for local paths, non-example email addresses and common secret formats.
