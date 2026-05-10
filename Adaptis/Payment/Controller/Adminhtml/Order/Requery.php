<?php

namespace Adaptis\Payment\Controller\Adminhtml\Order;

class Requery extends \Magento\Backend\App\Action
{
    public const ADMIN_RESOURCE = 'Magento_Sales::actions_view';

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var \Adaptis\Payment\Model\AdaptisApiClient
     */
    private $apiClient;

    /**
     * @var \Adaptis\Payment\Helper\Data
     */
    private $dataHelper;

    /**
     * @var \Adaptis\Payment\Model\OrderStatusApplier
     */
    private $orderStatusApplier;

    /**
     * @var \Adaptis\Payment\Logger\Logger
     */
    private $logger;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Adaptis\Payment\Model\AdaptisApiClient $apiClient,
        \Adaptis\Payment\Helper\Data $dataHelper,
        \Adaptis\Payment\Model\OrderStatusApplier $orderStatusApplier,
        \Adaptis\Payment\Logger\Logger $logger
    ) {
        parent::__construct($context);
        $this->orderRepository = $orderRepository;
        $this->apiClient = $apiClient;
        $this->dataHelper = $dataHelper;
        $this->orderStatusApplier = $orderStatusApplier;
        $this->logger = $logger;
    }

    public function execute()
    {
        $orderId = (int) $this->getRequest()->getParam('order_id');
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('sales/order/view', ['order_id' => $orderId]);

        try {
            $order = $this->orderRepository->get($orderId);

            if ($order->getPayment()->getMethod() !== \Adaptis\Payment\Model\Ui\ConfigProvider::CODE) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('This order was not paid using ADAPTIS.')
                );
            }

            $response = $this->apiClient->requery($order);
            $responseData = $this->dataHelper->normalizeCallbackData($response);
            $this->orderStatusApplier->applyPaymentResponse($order, $responseData, 'manual requery');

            $errorDescription = $this->orderStatusApplier->getErrorDescription($responseData);
            $order->addStatusToHistory(
                false,
                __(
                    'ADAPTIS manual sync completed. Status ID: %1. Transaction ID: %2.%3',
                    $responseData['status'] ?: 'empty',
                    $responseData['trans_id'] ?: 'empty',
                    $errorDescription ? ' ErrorDescription: ' . $errorDescription : ''
                )
            );
            $this->orderRepository->save($order);
            $this->messageManager->addSuccessMessage(__('ADAPTIS manual sync completed.'));
        } catch (\Magento\Framework\Exception\LocalizedException $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
        } catch (\Throwable $exception) {
            $this->logger->error('[manual requery] error', [
                'order_id' => $orderId,
                'exception' => $exception->getMessage(),
            ]);
            $this->messageManager->addErrorMessage(__('ADAPTIS manual sync failed. Please check var/log/adaptis-payment-*.log.'));
        }

        return $resultRedirect;
    }
}
