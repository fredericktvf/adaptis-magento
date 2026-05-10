# ADAPTIS Magento Installation and Configuration

This guide is for Magento Open Source / Adobe Commerce users installing the ADAPTIS payment module from GitHub.

## Composer Installation

The ADAPTIS Magento module is hosted in GitHub. Add the GitHub repository to the Magento project first, then require the package.

Run these commands from the Magento root folder, where `bin/magento` is located.

```bash
composer config repositories.adaptis-magento vcs https://github.com/fredericktvf/adaptis-magento.git
composer require adaptis/payment:dev-codex/magento-adaptis-enhancements
php bin/magento module:enable Adaptis_Payment --clear-static-content
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento cache:flush
```

After the feature branch is merged to `main` and tagged, use the stable tag instead of the development branch:

```bash
composer require adaptis/payment:^1.1
```

## Manual Installation

If Composer is not used, copy the module folder into Magento:

```text
app/code/Adaptis/Payment
```

Then run:

```bash
php bin/magento module:enable Adaptis_Payment --clear-static-content
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento cache:flush
```

## Supported Magento Versions

The module supports Magento module versions commonly used by Magento 2.3 and 2.4:

- `magento/module-checkout`: `100.3.*|100.4.*`
- `magento/module-payment`: `100.3.*|100.4.*`
- `magento/module-sales`: `102.0.*|103.0.*`

Composer validates these constraints during installation.

## Admin Configuration

Go to:

```text
Stores > Configuration > Sales > Payment Methods > ADAPTIS Payment
```

Configure these fields:

| Field | Description |
| --- | --- |
| Enabled | Set to `Yes` to enable ADAPTIS at checkout. |
| Title | Payment title shown to customers. Example: `ADAPTIS Payment`. |
| Sandbox Mode | Set `Yes` for testing, `No` for live production payments. |
| Sandbox Merchant Code | Merchant code for the sandbox environment. |
| Sandbox Merchant Key | Merchant key for the sandbox environment. |
| Production Merchant Code | Merchant code for the production environment. |
| Production Merchant Key | Merchant key for the production environment. |
| BackendUrl | Optional. Leave empty to use the default Magento callback URL. Merchants may override this if ADAPTIS must post to a custom public URL. |
| Show available payment types | Set `Yes` to show configured payment method choices in checkout. |
| Online Banking / Credit Card / Wallet / Buy Now Pay Later | Select available ADAPTIS payment methods. |
| Group payment methods by type on checkout | Groups payment methods under category headings. |
| Sort Order | Controls checkout display order. |

## Sandbox and Production URLs

Sandbox:

```text
https://pay.meglabox.com
```

Production:

```text
https://pay.adaptispay.com
```

When Sandbox Mode is enabled, checkout displays a yellow warning bar:

```text
You are in test mode. No actual payment is made in this mode.
```

## Refunds

Refunds are handled through Magento's native invoice credit memo flow:

```text
Sales > Orders > Open an Order > Invoices > Open Invoice > Credit Memo > Refund
```

The module supports partial and full ADAPTIS refunds.

ADAPTIS refund statuses treated as successful Magento refund submissions:

| RefundStatusId | Status |
| --- | --- |
| 3100 | Pending |
| 3200 | Processing |
| 3300 | Refunded |

The order history records the ADAPTIS refund status, refund ID, amount, and currency.

## Manual Payment Sync

For ADAPTIS orders, the Magento admin order page shows this button:

```text
ADAPTIS: Manual sync payment status
```

This calls the ADAPTIS Merchant Requery API and writes the returned status, transaction ID, and any `ErrorDescription` into the order history.

## Backend Callback

ADAPTIS backend posts are received as JSON.

For successful or duplicate backend posts, Magento responds with exactly:

```text
RECEIVEOK
```

## Logs

Logs are written in the Magento root folder:

```text
var/log/adaptis-payment-YYYY-MM-DD.log
```

Use this log for:

- Payment callback payloads
- Signature checks
- Refund API requests and responses
- Manual sync requests and responses
- API errors

