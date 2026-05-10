<?php

namespace Adaptis\Payment\Block\Checkout;

/**
 * Class Form
 * @package Adaptis\Payment\Block\Checkout
 *
 * @method string getMerchantCode()
 * @method string getPaymentId()
 * @method string getRefNo()
 * @method string getAmount()
 * @method string getCurrency()
 * @method string getProdDesc()
 * @method string getUserName()
 * @method string getUserEmail()
 * @method string getUserContact()
 * @method string getStreet()
 * @method string getState()
 * @method string getCity()
 * @method string getPostalCode()
 * @method string getCountry()
 * @method string getRemark()
 * @method string getLang()
 * @method string getSignatureType()
 * @method string getSignature()
 * @method string getResponseUrl()
 * @method string getBackendUrl()
 * @method string getAppdeeplink()
 * @method string getXfield1()
 * @method string getAdaptisUrl()
 * 
 */
class Form extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $magentoCheckoutSession;

    /**
     * @var \Adaptis\Payment\Helper\Data
     */
    protected $adaptisPaymentDataHelper;

    /**
     * @var \Adaptis\Payment\Logger\Logger
     */
    protected $adaptisPaymentLogger;

     /**
     * @var \Adaptis\Payment\Gateway\Config\Config
     */
    protected $adaptisPaymentGatewayConfig;


    /**
     * Form constructor.
     *
     * @param  \Magento\Framework\View\Element\Template\Context  $context
     * @param  \Magento\Checkout\Model\Session  $magentoCheckoutSession
     * @param  \Adaptis\Payment\Helper\Data  $adaptisPaymentDataHelper
     * @param  \Adaptis\Payment\Logger\Logger  $adaptisPaymentLogger
     * @param  array  $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Checkout\Model\Session $magentoCheckoutSession,
        \Adaptis\Payment\Helper\Data $adaptisPaymentDataHelper,
        \Adaptis\Payment\Logger\Logger $adaptisPaymentLogger,
        \Adaptis\Payment\Gateway\Config\Config $adaptisPaymentGatewayConfig,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->magentoCheckoutSession  = $magentoCheckoutSession;
        $this->adaptisPaymentDataHelper = $adaptisPaymentDataHelper;
        $this->adaptisPaymentLogger     = $adaptisPaymentLogger;
        $this->adaptisPaymentGatewayConfig = $adaptisPaymentGatewayConfig;

    }

    /**
     * Set order
     *
     * @param  \Magento\Sales\Model\Order  $order
     */
    public function setOrder(
        \Magento\Sales\Model\Order $order
    ) {
        $this->setData('order', $order);

        $requestData = $this->adaptisPaymentDataHelper->generateRequestData($order);

        $this->setData($requestData);

        $this->adaptisPaymentLogger->info('[form] payment request', $requestData);
    }

    /**
     * Check if sandbox mode is enabled
     */
    public function isSandboxEnabled()
    {
        return (bool) $this->adaptisPaymentGatewayConfig->getSandbox();
    }
}