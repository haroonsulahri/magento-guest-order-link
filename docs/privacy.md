# Privacy and Data Handling

## Public source and release artifacts

The repository and release archive must contain no production customer records, order identifiers, Admin identities, private store names, private domains, local filesystem paths, credentials or tokens.

The dependency-free release check rejects non-example email addresses, local URLs, common absolute user paths and common credential formats. A maintainer must also inspect the final archive before publishing it.

## Runtime data

The module processes data already stored in Magento and does not transmit it outside the Magento installation.

| Surface | Data used or recorded |
| --- | --- |
| Admin order and confirmation pages | Existing order and matched customer details, visible only to an authorized Admin user |
| POST request | Order ID only; no customer ID is accepted |
| Private order history | Trusted Admin display name only; no customer ID, customer email or Admin ID |
| Magento application log | Order, customer and Admin entity IDs only |
| External services | None |
| Telemetry or analytics | None |

Magento administrators remain responsible for protecting Admin access, application logs, database backups and order data according to their own retention and privacy policies.
