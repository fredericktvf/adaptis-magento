<?php

namespace Adaptis\Payment\Observer\Adminhtml;

class AddManualSyncButton implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Magento\Backend\Block\Widget\Context
     */
    private $context;

    public function __construct(
        \Magento\Backend\Block\Widget\Context $context
    ) {
        $this->context = $context;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $block = $observer->getEvent()->getBlock();

        if (!$block instanceof \Magento\Sales\Block\Adminhtml\Order\View) {
            return;
        }

        $order = $block->getOrder();
        if (!$order || $order->getPayment()->getMethod() !== \Adaptis\Payment\Model\Ui\ConfigProvider::CODE) {
            return;
        }

        $block->addButton(
            'adaptis_manual_sync',
            [
                'label' => __('ADAPTIS: Manual sync payment status'),
                'class' => 'adaptis-manual-sync',
                'onclick' => sprintf(
                    "location.href = '%s';",
                    $this->context->getUrlBuilder()->getUrl(
                        'adaptis_payment/order/requery',
                        ['order_id' => $order->getId()]
                    )
                ),
            ],
            0,
            60
        );
    }
}
