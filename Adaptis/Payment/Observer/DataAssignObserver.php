<?php

namespace Adaptis\Payment\Observer;

use Magento\Quote\Api\Data\PaymentInterface;

class DataAssignObserver extends \Magento\Payment\Observer\AbstractDataAssignObserver
{
    /**
     * @var array
     */
    protected $additionalInformationList = [
        'payment_id',
    ];

    /**
     * @param  \Magento\Framework\Event\Observer  $observer
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $dataObject = $this->readDataArgument($observer);

        $additionalData = $dataObject->getData(PaymentInterface::KEY_ADDITIONAL_DATA);
        if ( ! is_array($additionalData)) {
            return;
        }

        $paymentModel = $this->readPaymentModelArgument($observer);

        foreach ($this->additionalInformationList as $additionalInformationKey) {
            if (isset($additionalData[$additionalInformationKey])) {
                $paymentModel->setAdditionalInformation(
                    $additionalInformationKey,
                    $additionalData[$additionalInformationKey]
                );
            }
        }
    }
}
