<?php

namespace Adaptis\Payment\Model;

class OrderStatusApplier
{
    /**
     * @var \Magento\Framework\DB\TransactionFactory
     */
    private $transactionFactory;

    /**
     * @var \Magento\Sales\Model\Order\Config
     */
    private $orderConfig;

    /**
     * @var \Magento\Sales\Model\Service\InvoiceService
     */
    private $invoiceService;

    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\InvoiceSender
     */
    private $invoiceSender;

    /**
     * @var \Magento\Sales\Api\OrderManagementInterface
     */
    private $orderManagement;

    /**
     * @var \Adaptis\Payment\Logger\Logger
     */
    private $logger;

    public function __construct(
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        \Magento\Sales\Model\Order\Config $orderConfig,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender,
        \Magento\Sales\Api\OrderManagementInterface $orderManagement,
        \Adaptis\Payment\Logger\Logger $logger
    ) {
        $this->transactionFactory = $transactionFactory;
        $this->orderConfig = $orderConfig;
        $this->invoiceService = $invoiceService;
        $this->invoiceSender = $invoiceSender;
        $this->orderManagement = $orderManagement;
        $this->logger = $logger;
    }

    public function applyPaymentResponse(\Magento\Sales\Model\Order $order, array $response, string $source): void
    {
        $status = (string) ($response['status'] ?? $response['TransactionStatusID'] ?? $response['TransactionStatusId'] ?? '');

        if ($status === \Adaptis\Payment\Gateway\Config\Config::PAYMENT_STATUS_SUCCESS) {
            $this->applySuccessfulPayment($order, $response, $source);

            return;
        }

        if ($status === \Adaptis\Payment\Gateway\Config\Config::PAYMENT_STATUS_PENDING) {
            $order->addStatusToHistory(
                false,
                __('ADAPTIS %1 status pending. Transaction status: %2.', $source, $status)
            );

            return;
        }

        if ($status === \Adaptis\Payment\Gateway\Config\Config::PAYMENT_STATUS_FAIL) {
            $message = $this->getErrorDescription($response) ?: __('Payment unsuccessful.');
            $this->orderManagement->cancel($order->getId());
            $order->addStatusToHistory(false, __('ADAPTIS %1 failed: %2', $source, $message));

            return;
        }

        $order->addStatusToHistory(
            false,
            __('ADAPTIS %1 returned unhandled status: %2.', $source, $status ?: 'empty')
        );
    }

    public function applySuccessfulPayment(\Magento\Sales\Model\Order $order, array $response, string $source): void
    {
        $transactionId = (string) ($response['trans_id'] ?? $response['TransID'] ?? $response['TransId'] ?? '');

        if ($order->getPayment()->getLastTransId()) {
            $order->addStatusToHistory(
                false,
                __('ADAPTIS %1 confirmed an already recorded transaction: %2.', $source, $order->getPayment()->getLastTransId())
            );

            return;
        }

        $invoice = $this->invoiceService->prepareInvoice($order);
        $invoice->setTransactionId($transactionId);
        $invoice->register();

        $order->setIsInProcess(true);
        $order->getPayment()->setLastTransId($transactionId);
        $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
        $order->setStatus($this->orderConfig->getStateDefaultStatus($order->getState()));
        $order->addStatusToHistory(
            $order->getStatus(),
            __('ADAPTIS %1 success. Transaction ID: %2.', $source, $transactionId)
        );

        $this->transactionFactory->create()
            ->addObject($order)
            ->addObject($invoice)
            ->save();

        $this->invoiceSender->send($invoice);

        $this->logger->info("[{$source}] success", [
            'order' => $order->getIncrementId(),
            'invoice' => $invoice->getIncrementId(),
            'response' => $response,
        ]);
    }

    public function addRefundNote(
        \Magento\Sales\Model\Order $order,
        array $response,
        string $statusLabel
    ): void {
        $order->addStatusToHistory(
            false,
            __(
                'ADAPTIS refund %1. Refund ID: %2. Status ID: %3. Amount: %4 %5.',
                $statusLabel,
                $response['RefundId'] ?? '',
                $response['RefundStatusId'] ?? '',
                $response['Amount'] ?? '',
                $response['Currency'] ?? ''
            )
        );
    }

    public function getErrorDescription(array $response): string
    {
        return (string) (
            $response['err_desc']
            ?? $response['ErrorDescription']
            ?? $response['ErrorInfo']['ErrorDescription']
            ?? $response['ErrorMessage']
            ?? ''
        );
    }
}
