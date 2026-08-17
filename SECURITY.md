# Security Policy

## Reporting a vulnerability

Do not disclose a vulnerability in a public issue.

Use GitHub private vulnerability reporting from the repository's **Security** tab. Include the affected module version, Magento version, reproduction steps, impact and proposed mitigation. Do not include production credentials, customer data, order exports, tokens or database dumps.

Repository maintainers must enable and monitor private vulnerability reporting before publishing a stable release.

## Security model

Order-to-customer assignment is treated as a privileged operation:

- The Admin button and both controllers require `Haroone_LinkGuestOrderToCustomer::link`.
- The state-changing endpoint accepts POST requests only and uses Magento Admin form-key and secret-key validation.
- The server resolves the customer from the stored order email and never trusts a submitted customer ID.
- Magento's global or per-website customer-sharing scope is enforced.
- Orders already assigned to a customer are rejected.
- Automatic historical linking and frontend self-service linking are intentionally excluded.
- The private order-history comment records the trusted Admin display name but no customer ID, customer email or Admin ID.
- Structured application logs contain order, customer and Admin entity IDs only. They contain no exception objects, names, email addresses, credentials, payment details or submitted form values.
- The module sends no telemetry and makes no external network requests.

Grant the linking ACL only to trusted order-management roles and follow least-privilege access practices.
