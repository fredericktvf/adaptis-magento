define([
  'jquery',
  'mage/translate',
  'Magento_Ui/js/model/messageList',
  'Magento_Checkout/js/model/quote'
], function (
  $,
  $t,
  MagentoMessageList,
  MagentoCheckoutQuote
) {
  'use strict'
  return {
    validate () {
      if (MagentoCheckoutQuote.paymentMethod() && MagentoCheckoutQuote.paymentMethod().method !== 'adaptis_payment') {
        return true
      }

      const isShowAvailablePaymentTypes = window.checkoutConfig.payment.adaptis_payment.showAvailablePaymentTypes
      if (!isShowAvailablePaymentTypes) {
        return true
      }

      const hasSelectedPaymentType = !!jQuery(':radio[name="adaptis_payment_id"]:checked').length
      if (hasSelectedPaymentType) {
        return true
      }

      MagentoMessageList.addErrorMessage({
        message: $t('Please select your preferred ADAPTIS payment type before placing the order.')
      })

      return false
    }
  }
})