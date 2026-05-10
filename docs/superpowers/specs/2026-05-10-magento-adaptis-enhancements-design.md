# Magento ADAPTIS Enhancements Design

## Goal

Port the ADAPTIS payment gateway improvements already completed for WooCommerce into the Magento 2 module while following Magento's native payment, invoice, credit memo, and order admin patterns.

## Scope

The module will support separate sandbox and production credentials, a sandbox checkout warning, ADAPTIS refund API integration through Magento's invoice credit memo refund flow, manual order requery from the order admin page, clearer order history notes, backend callback handling, and unsuccessful payment messages sourced from `ErrorDescription`.

## Repository Baseline

The current Magento module is `Adaptis_Payment` under `Adaptis/Payment`. It already supports hosted payment redirect, frontend and backend callbacks, checkout payment method rendering, grouped payment method selection, logging, and sandbox/production hosted payment URLs. The baseline has been pushed to `fredericktvf/adaptis-magento` before feature work.

## Admin Configuration

The existing single `merchant_code` and `merchant_key` fields will be replaced or migrated into four fields:

- `sandbox_merchant_code`
- `sandbox_merchant_key`
- `production_merchant_code`
- `production_merchant_key`

The existing `sandbox` toggle will determine which credential pair is active. Existing saved values should be treated as production credentials when possible so merchants do not lose configuration during upgrade.

The module will also expose a Backend URL field. The field will default to Magento's backend callback route and remain editable for merchants who need a custom callback URL.

## Checkout Experience

When sandbox mode is enabled, checkout will render a yellow warning bar near the ADAPTIS payment method:

`You are in test mode. No actual payment is made in this mode.`

The warning will be passed from the Magento checkout config provider into the Knockout payment renderer so it is available in the standard checkout UI.

## Payment Callback Handling

Frontend responses will continue to validate the gateway signature. Backend JSON callbacks will be normalized separately, support ADAPTIS field casing, and return exactly `RECEIVEOK` on accepted processing.

Failure messages shown to customers and written to order history will use `ErrorDescription` when ADAPTIS provides it, including nested backend JSON error data.

Order history notes will clearly record payment success, failure, duplicate callbacks, validation failures, transaction IDs, and status IDs.

## Refunds

Refunds will use Magento's native invoice credit memo flow:

`Sales > Orders > View Order > Invoices > View Invoice > Credit Memo / Refund`

The module will add a Magento payment gateway `refund` command so online refunds call ADAPTIS Refund API. This keeps Magento responsible for refund amount validation, stock return, invoice and credit memo records, and admin UX.

The ADAPTIS refund request will use:

- `MerchantCode`
- `RequestType` as `10`
- `IpayId`
- unique refund `RefNo`
- `RefundAmount`
- `RefundCurrency`
- `Remark`
- nested `Verification.SignatureType = HMACSHA512`
- nested `Verification.Signature`

Refund statuses `3100`, `3200`, and `3300` will be treated as accepted by Magento. The order history will still note the gateway status label:

- `3100` = Pending
- `3200` = Processing
- `3300` = Refunded

Failures will return a Magento localized exception and avoid creating misleading success notes.

## Manual Sync / Merchant Requery

The order admin page will include an action button:

`ADAPTIS: Manual sync payment status`

The action will call ADAPTIS Merchant Requery API using the order increment ID and grand total, then update the order with clear history comments. A successful `1300` response will set the transaction ID and move the order to processing if it is not already paid. A failed `1400` response will record the gateway error description and fail/cancel according to Magento's existing order behavior.

## API Client and Signatures

Gateway HTTP calls, amount normalization, signature generation, and response parsing will be centralized in a service/helper layer rather than repeated in controllers. This keeps refund, requery, hosted payment, and callback logic consistent.

Amounts used in signatures will strip decimal and thousands separators to match ADAPTIS HMACSHA512 requirements.

## Logging

The existing `adaptis_payment` logger will be used for:

- hosted request payload summary
- callback raw and normalized data
- refund request and response
- requery request and response
- signature validation failures
- state transition decisions

Sensitive merchant keys will never be logged.

## Testing

Verification will include PHP syntax checks across the module. Where a full Magento runtime is unavailable locally, the implementation will be structured so API payload builders can be reviewed independently, and manual Magento admin test steps will be documented for:

- sandbox checkout warning
- sandbox/live credential switching
- successful frontend callback
- backend JSON callback returning `RECEIVEOK`
- unsuccessful callback using `ErrorDescription`
- full online refund from invoice credit memo
- partial online refund from invoice credit memo
- manual sync/requery from order view
