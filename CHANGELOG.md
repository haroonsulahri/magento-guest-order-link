# Changelog

All notable changes to this project are documented in this file.

## [Unreleased]

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
