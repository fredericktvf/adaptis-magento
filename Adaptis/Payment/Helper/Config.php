<?php

namespace Adaptis\Payment\Helper;

class Config extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_TESTING = 'adaptis_payment/testing';

    /**
     * Check is testing mode
     *
     * @return bool
     */
    public function isTestingMode()
    {
        return (bool)$this->scopeConfig->getValue(self::XML_PATH_TESTING);
    }
}