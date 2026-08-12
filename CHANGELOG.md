# Changelog

All notable changes to this project are documented in this file.

## [Unreleased]

## [1.0.0] - 2026-08-12

### Added

- ACL-protected Admin action for assigning an eligible guest order.
- Scope-aware, exact-email customer resolution.
- Confirmation page with order and account details.
- Magento-native customer assignment with a per-order lock.
- Targeted sales-grid refresh.
- Private order-history auditing using the trusted Admin display name.
- Unit and Magento integration tests.
- Linux-safe release builder and dependency-free package CI.
- Canonical repository banner, extension icon and Haroone brand assets.
- Public documentation for compatibility, validation, privacy, security, contribution and release handling.
- Relative and absolute release output paths with explicit exclusion of local design drafts and temporary files.

### Security

- Customer identity is always resolved from server-side order data.
- Existing customer assignments cannot be replaced.
- Private history comments contain no customer ID, customer email or Admin ID.
- Application logs contain entity IDs only and contain no customer email, customer name or Admin name.
- Public source and release artifacts are checked for local paths, non-example email addresses and common secret formats.
