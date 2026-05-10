define(
  [
    'uiComponent',
    'Magento_Checkout/js/model/payment/renderer-list'
  ],
  function (
    MagentoUiComponent,
    MagentoCheckoutPaymentRendererList
  ) {
    'use strict';
    MagentoCheckoutPaymentRendererList.push(
      {
        type: 'adaptis_payment',
        component: 'Adaptis_Payment/js/view/payment/method-renderer/adaptis-payment'
      }
    );

    return MagentoUiComponent.extend({});
  }
);
