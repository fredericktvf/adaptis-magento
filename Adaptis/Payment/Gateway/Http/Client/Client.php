<?php

namespace Adaptis\Payment\Gateway\Http\Client;

class Client implements \Magento\Payment\Gateway\Http\ClientInterface
{
    /**
     * @param  \Magento\Payment\Gateway\Http\TransferInterface  $transferObject
     *
     * @return array
     */
    public function placeRequest(
        \Magento\Payment\Gateway\Http\TransferInterface $transferObject
    ) {
        return [];
    }
}
