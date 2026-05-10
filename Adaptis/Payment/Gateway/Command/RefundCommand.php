<?php

namespace Adaptis\Payment\Gateway\Command;

class RefundCommand implements \Magento\Payment\Gateway\CommandInterface
{
    /**
     * @var \Adaptis\Payment\Model\AdaptisApiClient
     */
    private $apiClient;

    /**
     * @var \Adaptis\Payment\Gateway\Config\Config
     */
    private $config;

    /**
     * @var \Adaptis\Payment\Model\OrderStatusApplier
     */
    private $orderStatusApplier;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var \Adaptis\Payment\Logger\Logger
     */
    private $logger;

    public function __construct(
        \Adaptis\Payment\Model\AdaptisApiClient $apiClient,
        \Adaptis\Payment\Gateway\Config\Config $config,
        \Adaptis\Payment\Model\OrderStatusApplier $orderStatusApplier,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Adaptis\Payment\Logger\Logger $logger
    ) {
        $this->apiClient = $apiClient;
        $this->config = $config;
        $this->orderStatusApplier = $orderStatusApplier;
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;
    }

    public function execute(array $commandSubject)
    {
        $paymentDataObject = \Magento\Payment\Gateway\Helper\SubjectReader::readPayment($commandSubject);
        $amount = (float) \Magento\Payment\Gateway\Helper\SubjectReader::readAmount($commandSubject);
        $payment = $paymentDataObject->getPayment();

        if (!$payment instanceof \Magento\Sales\Model\Order\Payment) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('ADAPTIS refund failed because the Magento order payment was not available.')
            );
        }

        $order = $payment->getOrder();
        $transactionId = (string) $payment->getLastTransId();

        if ($transactionId === '') {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('ADAPTIS refund failed because the original transaction ID is missing.')
            );
        }

        $refundRefNo = sprintf(
            '%s-RF-%s',
            $order->getIncrementId(),
            gmdate('YmdHis')
        );

        $response = $this->apiClient->refund(
            $order,
            $amount,
            $refundRefNo,
            sprintf('Magento refund for order %s', $order->getIncrementId())
        );

        $statusId = (string) ($response['RefundStatusId'] ?? '');
        $statusLabel = $this->config->getRefundStatusLabel($statusId);

        if (!$this->config->isSuccessfulRefundStatus($statusId)) {
            $message = (string) ($response['ErrorMessage'] ?? $response['RefundStatusDesc'] ?? $statusLabel);
            $this->logger->error('[refund] failed', [
                'order' => $order->getIncrementId(),
                'response' => $response,
            ]);

            throw new \Magento\Framework\Exception\LocalizedException(
                __('ADAPTIS refund failed: %1', $message)
            );
        }

        $this->orderStatusApplier->addRefundNote($order, $response, $statusLabel);
        $this->orderRepository->save($order);

        $payment->setTransactionId((string) ($response['RefundId'] ?? $refundRefNo));
        $payment->setIsTransactionClosed(false);

        return null;
    }
}
