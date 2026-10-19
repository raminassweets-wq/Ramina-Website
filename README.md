# Ramina’s Sweets — Final Release 2.0

A complete server-rendered PHP/MySQL e-commerce and owner-management website for Ramina’s Sweets. The final release combines the storefront, accounts, ordering, inventory, customer support, reviews, transactional email, payment-provider integration, reporting and owner tools into one deployment package.

## What is included

**Storefront:** responsive premium homepage, searchable/sortable/paginated catalogue, product details, stock states, cart, guest/account checkout, private receipts, order tracking, printable receipts, purchase history and verified-purchase reviews.

**Accounts:** registration, separate customer and owner logins, secure password hashing, login throttling, email verification, one-time expiring password-reset links, profile management and session invalidation after password changes.

**Owner portal:** dashboard, products/photos/stock/publishing, orders and payment management, collection confirmation, customers, support conversations, sales reports, CSV export, business/contact settings, email health/log/queue and payment health.

**Support:** rule-based instant chatbot replies, persistent customer conversations, owner/admin replies, signed-in conversation recovery and email notification attempts for owner replies.

**Email:** order confirmations, payment receipts, order/collection updates, verification links, password-reset links, support-reply notifications, delivery logging, protected retry queue and automatic retry. Delivery tries authorised SMTP first and the hosting server mail transport on a public host as a fallback.

**Payments:** Cash, bank transfer and M-PAiSA manual verification; localhost-only safe payment simulator; PayPal Orders v2 adapter with server-side capture, idempotency, amount/currency checks, verified webhooks, duplicate-event protection and provider refund flow.

**Security:** separate customer/admin session cookies, CSRF protection, prepared PDO queries, escaped output, SameSite/HttpOnly cookies, secure cookies on HTTPS, role enforcement, private guest access codes, transactional stock reservation, upload validation, CSP/security headers, protected private directories and audit records.

## Zero-touch local start

On a standard fresh XAMPP setup:

1. Extract to `C:\xampp\htdocs\ramina-sweets`.
2. Start Apache + MySQL.
3. Visit `http://localhost/ramina-sweets/`.

The application attempts to create the `ramina_sweets` database, install the schema, sample catalogue and owner account automatically. Existing final/v1.5 databases are preserved and receive additive final tables automatically.

Owner portal: `http://localhost/ramina-sweets/admin/login.php`

Customer portal: `http://localhost/ramina-sweets/login.php`

## Live hosting

Requirements: PHP 8.2+, PDO MySQL, MySQL 8/MariaDB 10.4+, OpenSSL, HTTPS, and writable `storage/` and `images/products/` directories. Configure database values with environment variables where the live host does not use the local defaults:

- `RAMINA_DB_HOST`
- `RAMINA_DB_PORT`
- `RAMINA_DB_NAME`
- `RAMINA_DB_USER`
- `RAMINA_DB_PASSWORD`
- optionally `RAMINA_APP_URL`

External email/payment providers still require authorisation from those providers. The code fails safely and keeps normal ordering usable when an external service is unavailable.

## Real email

The configured business Gmail address is the owner/Reply-To identity. `mail_transport=auto`:

1. uses authorised SMTP when usable credentials exist;
2. otherwise tries the public host's configured PHP mail service with a same-domain envelope sender and the business Gmail as Reply-To;
3. if neither accepts the message, stores it in the protected retry queue and a diagnostic local outbox.

Google does not accept an ordinary Gmail account password for basic SMTP clients. Gmail SMTP needs a Google App Password or OAuth-authorised mail transport. The normal Gmail password is intentionally **not stored in plaintext** in this release.

## Real online payments

A real payment gateway cannot legally/technically operate without merchant credentials issued to the business. The PayPal integration is complete but stays hidden on a public host until valid merchant configuration exists. Localhost exposes only the clearly labelled no-money test gateway.

Relevant environment/config values:

- `RAMINA_PAYPAL_ENABLED=1`
- `RAMINA_PAYPAL_MODE=sandbox` before live
- `RAMINA_PAYPAL_CLIENT_ID`
- `RAMINA_PAYPAL_CLIENT_SECRET`
- `paypal_webhook_id` in a private config override
- `RAMINA_PAYPAL_CURRENCY`
- `RAMINA_PAYPAL_FX_RATE`

The website never asks for or stores card numbers, CVV, payment PINs or one-time codes.

## Optional diagnostics

```powershell
C:\xampp\php\php.exe tools\system-check.php
```

Owner → **System & email** also exposes safe health diagnostics and lets the owner send a test message without exposing secrets.
