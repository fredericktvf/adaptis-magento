<?php

namespace Adaptis\Payment\Logger\Handler;

class Base extends \Magento\Framework\Logger\Handler\Base
{
    public function __construct(
        \Magento\Framework\Filesystem\DriverInterface $filesystem
    )
    {
        $date = date('Y-m-d');

        $fileName = "/var/log/adaptis-payment-{$date}.log";

        parent::__construct($filesystem, null, $fileName);
    }
}