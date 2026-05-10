define([
  'uiComponent',
  'Magento_Checkout/js/model/payment/additional-validators',
  'Adaptis_Payment/js/model/payment-type-validator'
], function (
  MagentoComponent,
  MagentoCheckoutPaymentAdditionalValidators,
  AdaptisPaymentTypeValidator
) {
  MagentoCheckoutPaymentAdditionalValidators.registerValidator(AdaptisPaymentTypeValidator)

  return MagentoComponent.extend({})
})
