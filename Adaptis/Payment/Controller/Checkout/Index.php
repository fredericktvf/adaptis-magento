<?php

namespace Adaptis\Payment\Controller\Checkout;

class Index extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\App\ResponseInterface
     */
    protected $magentoResponse;

    /**
     * @var \Magento\Framework\App\Response\RedirectInterface
     */
    protected $magentoResponseRedirect;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $magentoMessageManager;

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $magentoViewPageResultFactory;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $magentoCheckoutSession;

    /**
     * @var \Adaptis\Payment\Logger\Logger
     */
    protected $adaptisPaymentLogger;

    /**
     * Index constructor.
     *
     * @param  \Magento\Checkout\Model\Session  $magentoCheckoutSession
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $magentoCheckoutSession,
        \Adaptis\Payment\Logger\Logger $adaptisPaymentLogger
    ) {
        parent::__construct($context);

        $this->magentoResponse         = $context->getResponse();
        $this->magentoResponseRedirect = $context->getRedirect();
        $this->magentoMessageManager   = $context->getMessageManager();
        $this->magentoCheckoutSession  = $magentoCheckoutSession;
    }

    public function execute()
    {
        $order = $this->magentoCheckoutSession->getLastRealOrder();
        if ( ! $order) {
            $this->redirectToCheckoutCartPage();
            return;
        }

        if ($order->getState() !== \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT) {
            $this->adaptisPaymentLogger->info('[form] Unexpected order state', [
                'order'    => $order->getIncrementId(),
                'state' => $order->getState(),
            ]);

            //            $this->magentoMessageManager->addErrorMessage($message);

            $this->redirectToCheckoutCartPage();
            return;
        }

        $page = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_PAGE);

        /**
         * @var \Adaptis\Payment\Block\Checkout\Form $formBlock
         */
        $formBlock = $page->getLayout()->getChildBlock('content', 'adaptis_payment_checkout_index_form');
        $formBlock->setOrder($order);

        return $page;
    }

    protected function redirectToCheckoutCartPage()
    {
        $this->magentoResponseRedirect->redirect($this->magentoResponse, 'checkout/cart');
    }
}