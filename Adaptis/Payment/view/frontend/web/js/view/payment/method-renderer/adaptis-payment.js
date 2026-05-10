define([
  'Magento_Checkout/js/view/payment/default',
  'Magento_Checkout/js/action/redirect-on-success',
  'mage/url',
], function (
  MagentoCheckoutPaymentComponent,
  MagentoCheckoutRedirectOnSuccessAction,
  MagentoUrl
) {
  'use strict'

  return MagentoCheckoutPaymentComponent.extend({
    defaults: {
      template: 'Adaptis_Payment/payment/form'
    },

    initObservable: function () {
      this._super()
        .observe({
          selectedPaymentId: '',
        })

      return this
    },

    /**
     * Get payment method data
     */
    getData: function () {
      return {
        'method': this.item.method,
        'po_number': null,
        'additional_data': {
          'payment_id': this.selectedPaymentId(),
        }
      }
    },

    getInstructions () {
      return window.checkoutConfig.payment.instructions[this.item.method]
    },

    getConfig () {
      return window.checkoutConfig.payment.adaptis_payment
    },

    getShowAvailablePaymentTypes () {
      return !!this.getConfig().showAvailablePaymentTypes
    },

    getOnlineBankingMethods () {
      return this.getConfig().onlineBankingMethods || []
    },

    getCreditCardMethods () {
      return this.getConfig().creditCardMethods || []
    },

    getWalletMethods () {
      return this.getConfig().walletMethods || []
    },

    getBuyNowPayLaterMethods () {
      return this.getConfig().buyNowPayLaterMethods || []
    },

    getGroupPaymentMethodsByTypeOnCheckout () {
      return !!this.getConfig().groupPaymentMethodsByTypeOnCheckout
    },

    afterPlaceOrder () {
      MagentoCheckoutRedirectOnSuccessAction.redirectUrl = MagentoUrl.build('adaptis_payment/checkout/index')
    },
  })
})