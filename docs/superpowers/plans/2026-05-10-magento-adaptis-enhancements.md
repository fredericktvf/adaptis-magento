# Magento ADAPTIS Enhancements Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add Magento support for the same ADAPTIS payment gateway features recently added to WooCommerce.

**Architecture:** Keep the existing hosted-payment redirect flow and add a focused ADAPTIS API service for refund and requery JSON calls. Store sandbox and production credentials separately in Magento config, expose checkout sandbox metadata through the existing config provider, and use Magento’s native invoice credit memo refund command plus an admin order button/controller for manual requery.

**Tech Stack:** Magento 2 payment gateway module, PHP 7+/8-compatible code style, Knockout checkout renderer, Magento admin controllers/layout blocks, ADAPTIS JSON APIs.

---

## File Structure

- Modify `Adaptis/Payment/Gateway/Config/Config.php`: add separate credential keys, base URL helpers, backend URL helper, refund status constants, and active credential accessors.
- Modify `Adaptis/Payment/etc/adminhtml/system.xml`: replace the single credential fields with sandbox/live fields and configurable BackendUrl.
- Modify `Adaptis/Payment/etc/config.xml`: default feature support (`can_refund`, `can_refund_partial`) and default backend URL empty.
- Modify `Adaptis/Payment/Helper/Data.php`: centralize signatures, request URL building, response normalization, refund payloads, and requery payloads.
- Create `Adaptis/Payment/Gateway/Command/RefundCommand.php`: Magento payment gateway refund command called from invoice credit memo flow.
- Create `Adaptis/Payment/Model/AdaptisApiClient.php`: JSON POST client for refund and requery.
- Create `Adaptis/Payment/Model/OrderStatusApplier.php`: shared order status/note updates for callback, redirect, refund, and requery.
- Modify `Adaptis/Payment/etc/di.xml`: register refund command and dependencies.
- Modify `Adaptis/Payment/Controller/Checkout/Callback.php`: accept backend JSON, normalize `ErrorDescription`, and return exactly `RECEIVEOK` for backend success/duplicate responses.
- Modify `Adaptis/Payment/Controller/Checkout/Redirect.php`: use shared order status applier and `ErrorDescription`.
- Modify `Adaptis/Payment/Model/Ui/ConfigProvider.php`: pass sandbox warning and description data to checkout.
- Modify `Adaptis/Payment/view/frontend/web/js/view/payment/method-renderer/adaptis-payment.js`: expose sandbox warning and description helpers.
- Modify `Adaptis/Payment/view/frontend/web/template/payment/form.html`: render yellow sandbox bar in checkout.
- Modify `Adaptis/Payment/view/frontend/web/css/payment.css`: style sandbox warning.
- Modify `Adaptis/Payment/view/frontend/templates/checkout/form.phtml`: use configured hosted endpoint and BackendURL value.
- Create `Adaptis/Payment/Block/Adminhtml/Order/View/ManualSyncButton.php`: add admin order action.
- Create `Adaptis/Payment/Controller/Adminhtml/Order/Requery.php`: run requery and redirect back with status message.
- Create `Adaptis/Payment/view/adminhtml/layout/sales_order_view.xml`: register the manual sync button.
- Modify `README.md`: document Magento install, supported flow, refund/requery, logs, and configuration.

## Tasks

### Task 1: Branch, Baseline, and Compatibility Check

- [ ] Create branch `codex/magento-adaptis-enhancements` from `main`.
- [ ] Run `php -l` on current PHP files to capture baseline syntax state.
- [ ] Check `composer.json` and document current Magento module constraints in README.

### Task 2: Gateway Config and Admin Settings

- [ ] Update `Config.php` with sandbox/live credential accessors.
- [ ] Preserve old `merchant_code` and `merchant_key` as fallback values so existing merchants are not locked out before saving the new fields.
- [ ] Add `getBaseUrl()`, `getHostedPaymentUrl()`, `getRefundUrl()`, `getRequeryUrl()`, `getBackendUrl()`, and refund status labels.
- [ ] Update `system.xml` with fields:
  - `sandbox_merchant_code`
  - `sandbox_merchant_key`
  - `production_merchant_code`
  - `production_merchant_key`
  - `backend_url`
- [ ] Update `config.xml` with refund capability defaults.
- [ ] Run XML parsing and PHP syntax checks.

### Task 3: ADAPTIS API Client and Payload Builders

- [ ] Create `AdaptisApiClient` using Magento cURL client for JSON POST.
- [ ] Add helper methods for ADAPTIS-formatted amounts:
  - display/API amount with comma thousands and two decimals for hosted payment/refund.
  - signature amount with all separators stripped.
  - requery amount with two decimals and no thousands separator.
- [ ] Add refund request payload builder using fields from the refund API:
  - `MerchantCode`
  - `RequestType = 10`
  - `IpayId` from Magento payment last transaction id
  - unique `RefNo` based on order/invoice/credit memo
  - `RefundAmount`
  - `RefundCurrency`
  - `Remark`
  - nested `Verification`
- [ ] Add requery request payload builder:
  - `MerchantCode`
  - `RefNo`
  - `Amount`
  - nested `Verification`
- [ ] Add unit-like standalone PHP smoke checks where possible through syntax validation.

### Task 4: Native Invoice Refund Support

- [ ] Create `RefundCommand.php`.
- [ ] Read order/payment/amount from Magento command subject.
- [ ] Call `AdaptisApiClient::refund()`.
- [ ] Treat `3100`, `3200`, and `3300` as successful Magento refund outcomes.
- [ ] Add order comments:
  - `3100`: ADAPTIS refund pending.
  - `3200`: ADAPTIS refund processing.
  - `3300`: ADAPTIS refunded.
- [ ] Throw localized exceptions for other refund statuses and HTTP/API errors.
- [ ] Wire `refund` into `AdaptisPaymentCommandPool`.
- [ ] Ensure `can_refund` and `can_refund_partial` are enabled.

### Task 5: Manual Requery Order Action

- [ ] Create admin order button visible only for ADAPTIS orders.
- [ ] Label button `ADAPTIS: Manual sync payment status`.
- [ ] Create admin controller that loads the order, calls requery, applies status result, and redirects back.
- [ ] Add order history comments showing raw status, transaction id, and error description when present.
- [ ] Add success/error admin messages.

### Task 6: Shared Status Application and Backend Callback

- [ ] Create `OrderStatusApplier` for status `1300`, `1400`, pending `1100`, and no-handler statuses.
- [ ] Refactor callback and redirect controllers to use normalized `ErrorDescription`.
- [ ] Keep backend callback body exactly `RECEIVEOK` for successful or duplicate backend posts.
- [ ] Log backend JSON safely.
- [ ] Avoid outputting any extra whitespace or HTML on backend callback success.

### Task 7: Checkout Sandbox Warning and Backend URL Rendering

- [ ] Pass sandbox flag, warning text, and payment description through checkout config.
- [ ] Render yellow sandbox warning bar in the Knockout payment template when sandbox is enabled.
- [ ] Style it in `payment.css`.
- [ ] Use `Config::getHostedPaymentUrl()` in redirect form.
- [ ] Use configured BackendURL if filled; otherwise default to Magento callback route.

### Task 8: Documentation and Verification

- [ ] Update README with installation, supported Magento/Adobe Commerce constraints, logs, refund location, requery action, and config fields.
- [ ] Run `php -l` on all PHP files.
- [ ] Run XML parser checks for XML files.
- [ ] Run `git diff --check`.
- [ ] Commit and push the branch to GitHub.

