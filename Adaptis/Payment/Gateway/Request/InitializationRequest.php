<?php

namespace Adaptis\Payment\Gateway\Request;

class InitializationRequest implements \Magento\Payment\Gateway\Request\BuilderInterface
{
    /**
     * @var \Adaptis\Payment\Gateway\Config\Config
     */
    protected $adaptisPaymentGatewayConfig;

    /**
     * AuthorizationRequest constructor.
     *
     * @param  \Adaptis\Payment\Gateway\Config\Config  $config
     */
    public function __construct(
        \Adaptis\Payment\Gateway\Config\Config $config
    ) {
        $this->adaptisPaymentGatewayConfig = $config;
    }

    /**
     * Builds ENV request
     *
     * @param  array  $buildSubject
     *
     * @return array
     */
    public function build(array $buildSubject)
    {
        /**
         * @var \Magento\Framework\DataObject $stateObject
         */
        $stateObject = $buildSubject['stateObject'];
        $stateObject->setData('state', \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);
        $stateObject->setData('status', \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);
        $stateObject->setData('is_notified', false);

        return [];
    }
}