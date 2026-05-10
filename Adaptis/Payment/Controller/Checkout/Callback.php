<?php

namespace Adaptis\Payment\Controller\Checkout;

class Callback extends \Magento\Framework\App\Action\Action implements \Magento\Framework\App\Action\HttpPostActionInterface,
                                                                       \Magento\Framework\App\CsrfAwareActionInterface
{
    /**
     * @var \Magento\Framework\App\CacheInterface
     */
    protected $magentoCache;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $magentoRequest;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $magentoApiSearchCriteriaBuilder;

    /**
     * @var \Magento\Framework\DB\TransactionFactory
     */
    protected $magentoDbTransactionFactory;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $magentoSalesOrderRepository;

    /**
     * @var \Magento\Sales\Api\OrderManagementInterface
     */
    protected $magentoSalesOrderManagement;

    /**
     * @var \Magento\Sales\Api\InvoiceOrderInterface
     */
    protected $magentoSalesInvoiceOrder;

    /**
     * @var \Magento\Sales\Model\Order\Config
     */
    protected $magentoSalesOrderConfig;

    /**
     * @var \Magento\Sales\Model\Service\InvoiceService
     */
    protected $magentoSalesInvoiceService;

    /**
     * @var \Magento\Sales\Model\Order\InvoiceRepository
     */
    protected $magentoSalesInvoiceRepository;

    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\InvoiceSender
     */
    protected $magentoSalesInvoiceSender;

    /**
     * @var \Adaptis\Payment\Helper\Data
     */
    protected $adaptisPaymentDataHelper;

    /**
     * @var \Adaptis\Payment\Logger\Logger
     */
    protected $adaptisPaymentLogger;

    /**
     * Callback constructor.
     *
     * @param  \Magento\Framework\App\Action\Context  $context
     * @param  \Magento\Framework\App\CacheInterface  $magentoCache
     * @param  \Magento\Framework\Api\SearchCriteriaBuilder  $magentoApiSearchCriteriaBuilder
     * @param  \Magento\Framework\DB\TransactionFactory  $magentoDbTransactionFactory
     * @param  \Magento\Sales\Api\OrderRepositoryInterface  $magentoSalesOrderRepository
     * @param  \Magento\Sales\Api\OrderManagementInterface  $magentoSalesOrderManagement
     * @param  \Magento\Sales\Api\InvoiceOrderInterface  $magentoSalesInvoiceOrder
     * @param  \Magento\Sales\Model\Order\Config  $magentoSalesOrderConfig
     * @param  \Magento\Sales\Model\Order\InvoiceRepository  $magentoSalesInvoiceRepository
     * @param  \Magento\Sales\Model\Service\InvoiceService  $magentoSalesInvoiceService
     * @param  \Magento\Sales\Model\Order\Email\Sender\InvoiceSender  $magentoSalesInvoiceSender
     * @param  \Adaptis\Payment\Helper\Data  $adaptisPaymentDataHelper
     * @param  \Adaptis\Payment\Logger\Logger  $adaptisPaymentLogger
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\CacheInterface $magentoCache,
        \Magento\Framework\Api\SearchCriteriaBuilder $magentoApiSearchCriteriaBuilder,
        \Magento\Framework\DB\TransactionFactory $magentoDbTransactionFactory,
        \Magento\Sales\Api\OrderRepositoryInterface $magentoSalesOrderRepository,
        \Magento\Sales\Api\OrderManagementInterface $magentoSalesOrderManagement,
        \Magento\Sales\Api\InvoiceOrderInterface $magentoSalesInvoiceOrder,
        \Magento\Sales\Model\Order\Config $magentoSalesOrderConfig,
        \Magento\Sales\Model\Order\InvoiceRepository $magentoSalesInvoiceRepository,
        \Magento\Sales\Model\Service\InvoiceService $magentoSalesInvoiceService,
        \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $magentoSalesInvoiceSender,
        \Adaptis\Payment\Helper\Data $adaptisPaymentDataHelper,
        \Adaptis\Payment\Logger\Logger $adaptisPaymentLogger
    ) {
        parent::__construct($context);

        $this->magentoCache                    = $magentoCache;
        $this->magentoRequest                  = $context->getRequest();
        $this->magentoApiSearchCriteriaBuilder = $magentoApiSearchCriteriaBuilder;
        $this->magentoDbTransactionFactory     = $magentoDbTransactionFactory;
        $this->magentoSalesOrderRepository     = $magentoSalesOrderRepository;
        $this->magentoSalesOrderManagement     = $magentoSalesOrderManagement;
        $this->magentoSalesInvoiceOrder        = $magentoSalesInvoiceOrder;
        $this->magentoSalesOrderConfig         = $magentoSalesOrderConfig;
        $this->magentoSalesInvoiceService      = $magentoSalesInvoiceService;
        $this->magentoSalesInvoiceRepository   = $magentoSalesInvoiceRepository;
        $this->magentoSalesInvoiceSender       = $magentoSalesInvoiceSender;
        $this->adaptisPaymentDataHelper         = $adaptisPaymentDataHelper;
        $this->adaptisPaymentLogger             = $adaptisPaymentLogger;
    }

    /**
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(): void
    {
        $raw_input = file_get_contents('php://input');
        $raw_input_decode = json_decode($raw_input, true);

        $this->adaptisPaymentLogger->info('[callback - modified] params', $raw_input_decode ?? $this->magentoRequest->getParams());
        
        $responseData = $this->adaptisPaymentDataHelper->normalizeCallbackData($raw_input_decode ?? $this->magentoRequest->getParams());

        $this->adaptisPaymentLogger->info('[callback - modified] data', $responseData);

        $salesOrder = $this->getOrderFromResponse($responseData);

        // check if order exist
        if ( ! $salesOrder) {
            $this->handleError(__("No order #{$responseData['ref_no']} for processing found."), $responseData);

            return;
        }

        // check if response are sent from adaptis
        // $isResponseRemoteAddressValid = $this->adaptisPaymentDataHelper->isResponseRemoteAddressValid();
        // if ( ! $isResponseRemoteAddressValid) {
        //     $this->handleError(__('Invalid remote address.'), $responseData);

        //     return;
        // }

        // restore cart and cancel order if signature empty
        $this->adaptisPaymentLogger->info('[callback - modified] signature empty check');
        $isResponseSignatureExists = $this->adaptisPaymentDataHelper->isResponseSignatureExist($responseData);
        if ( ! $isResponseSignatureExists) {
            $this->handleEmptyResponseSignature($salesOrder, $responseData);

            return;
        }

        // ignore request if signature mismatches
        $this->adaptisPaymentLogger->info('[callback - modified] signature miss match check');
        $isResponseSignatureMatched = $this->adaptisPaymentDataHelper->isResponseSignatureMatched($responseData);
        if ( ! $isResponseSignatureMatched) {
            $this->handleError(__("Returned signature `{$responseData['signature']}` not match."), $responseData);

            return;
        }

        $this->adaptisPaymentLogger->info('[callback - modified] status fail check');
        if ((string)$responseData['status'] === \Adaptis\Payment\Gateway\Config\Config::PAYMENT_STATUS_FAIL) {
            $this->handleError($responseData['err_desc'], $responseData, $salesOrder);

            return;
        }
        $this->adaptisPaymentLogger->info('[callback - modified] status success check');
        if ((string)$responseData['status'] === \Adaptis\Payment\Gateway\Config\Config::PAYMENT_STATUS_SUCCESS) {
            $this->handleSuccessResponse($salesOrder, $responseData);

            return;
        }

        $this->adaptisPaymentLogger->info('[callback - modified] no handler process check');
        $this->handleNoHandler($salesOrder, $responseData);
    }

    /**
     * @param  array  $response
     *
     * @return \Magento\Sales\Model\Order|false
     */
    protected function getOrderFromResponse(array $response)
    {
        $salesOrderSearchCriteria = $this->magentoApiSearchCriteriaBuilder->addFilter(
            \Magento\Sales\Api\Data\OrderInterface::INCREMENT_ID,
            $response['ref_no']
        )->create();

        $salesOrderCollection = $this->magentoSalesOrderRepository->getList($salesOrderSearchCriteria)->getItems();

        return reset($salesOrderCollection);
    }

    /**
     * @param  \Magento\Sales\Model\Order  $salesOrder
     * @param  array  $response
     */
    protected function handleEmptyResponseSignature(
        \Magento\Sales\Model\Order $salesOrder,
        array $response
    ): void {
        $this->handleError(__($response['err_desc']), $response, $salesOrder);

        $salesOrder->addStatusToHistory(
            false,
            __('Order cancelled due to response signature is empty. Please check order status in merchant portal for confirmation.')
        );

        $this->magentoSalesOrderRepository->save($salesOrder);
    }

    /**
     * @param  \Magento\Sales\Model\Order  $salesOrder
     * @param  array  $response
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function handleSuccessResponse(
        \Magento\Sales\Model\Order $salesOrder,
        array $response
    ): void {
        if ($salesOrder->getPayment()->getLastTransId()) {
            $this->adaptisPaymentLogger->info('[callback - success] transaction existed', [
                'order'    => $salesOrder->getIncrementId(),
                'response' => $response,
            ]);

            echo 'RECEIVEOK';
            exit;
        }

        $isProcessing = (bool) $this->magentoCache->load("adaptis_payment_processing_{$salesOrder->getIncrementId()}");
        if ($isProcessing) {
            $this->adaptisPaymentLogger->info('[callback - success] processing by request', [
                'order'    => $salesOrder->getIncrementId(),
                'response' => $response,
            ]);

            sleep(1);

            echo 'RECEIVEOK';
            exit;
        }

        $this->magentoCache->save(1, "adaptis_payment_processing_{$salesOrder->getIncrementId()}");

        $salesInvoice = $this->magentoSalesInvoiceService->prepareInvoice($salesOrder);
        $salesInvoice->setTransactionId($response['trans_id']);
        //            $salesInvoice->setRequestedCaptureCase();
        $salesInvoice->register();

        //            $salesOrder->setCustomerNoteNotify(! empty($data['send_email']));
        $salesOrder->setIsInProcess(true);
        $salesOrder->getPayment()->setLastTransId($response['trans_id']);
        $salesOrder->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
        $salesOrder->setStatus($this->magentoSalesOrderConfig->getStateDefaultStatus($salesOrder->getState()));
        $salesOrder->addStatusToHistory($salesOrder->getStatus(), "Adaptis transaction #{$salesInvoice->getTransactionId()} success.");

        $dbTransaction = $this->magentoDbTransactionFactory->create();
        $dbTransaction->addObject($salesOrder);
        $dbTransaction->addObject($salesInvoice);
        $dbTransaction->save();

        $this->magentoCache->remove("adaptis_payment_processing_{$salesOrder->getIncrementId()}");

        $this->magentoSalesInvoiceSender->send($salesInvoice);

        $this->adaptisPaymentLogger->info('[callback - success] success', [
            'order'    => $salesOrder->getIncrementId(),
            'invoice'  => $salesInvoice->getIncrementId(),
            'response' => $response,
        ]);

        echo 'RECEIVEOK';
        exit;
    }

    /**
     * @param  \Magento\Sales\Model\Order  $salesOrder
     * @param  array  $response
     */
    protected function handleNoHandler(
        \Magento\Sales\Model\Order $salesOrder,
        array $response
    ): void {
        $this->adaptisPaymentLogger->notice('[callback] no handler', [
            'order'    => $salesOrder->getIncrementId(),
            'response' => $response,
        ]);
    }

    /**
     * @param  string  $errorMessage
     * @param  array  $responseData
     * @param  \Magento\Sales\Model\Order|null  $salesOrder  cancel order if set
     */
    protected function handleError(
        string $errorMessage,
        array $responseData,
        \Magento\Sales\Model\Order $salesOrder = null
    ): void {
        if ($salesOrder) {
            $this->magentoSalesOrderManagement->cancel($salesOrder->getId());

            $errorContext['order'] = $salesOrder->getIncrementId();
        }

        $errorContext['response'] = $responseData;

        $this->adaptisPaymentLogger->error("[callback] {$errorMessage}", $errorContext);
    }

    /**
     * @param  \Magento\Framework\App\RequestInterface  $request
     *
     * @return \Magento\Framework\App\Request\InvalidRequestException|null
     */
    public function createCsrfValidationException(
        \Magento\Framework\App\RequestInterface $request
    ): ?\Magento\Framework\App\Request\InvalidRequestException {
        return null;
    }

    /**
     * @param  \Magento\Framework\App\RequestInterface  $request
     *
     * @return bool|null
     */
    public function validateForCsrf(
        \Magento\Framework\App\RequestInterface $request
    ): ?bool {
        return true;
    }
}
