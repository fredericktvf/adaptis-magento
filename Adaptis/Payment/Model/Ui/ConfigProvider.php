<?php

namespace Adaptis\Payment\Model\Ui;

class ConfigProvider implements \Magento\Checkout\Model\ConfigProviderInterface
{
    public const CODE = 'adaptis_payment';

    /**
     * @var \Adaptis\Payment\Gateway\Config\Config
     */
    protected $adaptisPaymentGatewayConfig;

    /**
     * ConfigProvider constructor.
     *
     * @param  \Adaptis\Payment\Gateway\Config\Config  $adaptisPaymentGatewayConfig
     */
    public function __construct(
        \Adaptis\Payment\Gateway\Config\Config $adaptisPaymentGatewayConfig
    ) {
        $this->adaptisPaymentGatewayConfig = $adaptisPaymentGatewayConfig;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        return [
            'payment' => [
                self::CODE => [
                    'showAvailablePaymentTypes'           => $this->adaptisPaymentGatewayConfig->getShowAvailablePaymentTypes(),
                    'onlineBankingMethods'                => $this->adaptisPaymentGatewayConfig->getOnlineBankingMethods(),
                    'creditCardMethods'                   => $this->adaptisPaymentGatewayConfig->getCreditCardMethods(),
                    'walletMethods'                       => $this->adaptisPaymentGatewayConfig->getWalletMethods(),
                    'buyNowPayLaterMethods'               => $this->adaptisPaymentGatewayConfig->getBuyNowPayLaterMethods(),
                    'groupPaymentMethodsByTypeOnCheckout' => $this->adaptisPaymentGatewayConfig->getGroupPaymentMethodsByTypeOnCheckout(),
                    'sandbox'                             => $this->adaptisPaymentGatewayConfig->getSandbox(),
                    'sandboxWarning'                      => (string) __('You are in test mode. No actual payment is made in this mode.'),
                ],
            ],
        ];
    }
}
