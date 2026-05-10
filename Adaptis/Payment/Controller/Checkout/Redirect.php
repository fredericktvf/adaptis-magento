<?php

namespace Adaptis\Payment\Controller\Checkout;

class Redirect extends \Magento\Framework\App\Action\Action implements \Magento\Framework\App\Action\HttpPostActionInterface,
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
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $magentoApiSearchCriteriaBuilder;

    /**
     * @var \Magento\Framework\DB\TransactionFactory
     */
    protected $magentoDbTransactionFactory;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $magentoCheckoutSession;

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
     * @var \Adaptis\Payment\Model\OrderStatusApplier
     */
    protected $adaptisPaymentOrderStatusApplier;

    /**
     * Redirect constructor.
     *
     * @param  \Magento\Framework\App\Action\Context  $context
     * @param  \Magento\Framework\App\CacheInterface  $magentoCache
     * @param  \Magento\Framework\Api\SearchCriteriaBuilder  $magentoApiSearchCriteriaBuilder
     * @param  \Magento\Framework\DB\TransactionFactory  $magentoDbTransactionFactory
     * @param  \Magento\Checkout\Model\Session  $magentoCheckoutSession
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
        \Magento\Checkout\Model\Session $magentoCheckoutSession,
        \Magento\Sales\Api\OrderRepositoryInterface $magentoSalesOrderRepository,
        \Magento\Sales\Api\OrderManagementInterface $magentoSalesOrderManagement,
        \Magento\Sales\Api\InvoiceOrderInterface $magentoSalesInvoiceOrder,
        \Magento\Sales\Model\Order\Config $magentoSalesOrderConfig,
        \Magento\Sales\Model\Order\InvoiceRepository $magentoSalesInvoiceRepository,
        \Magento\Sales\Model\Service\InvoiceService $magentoSalesInvoiceService,
        \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $magentoSalesInvoiceSender,
        \Adaptis\Payment\Helper\Data $adaptisPaymentDataHelper,
        \Adaptis\Payment\Logger\Logger $adaptisPaymentLogger,
        \Adaptis\Payment\Model\OrderStatusApplier $adaptisPaymentOrderStatusApplier
    ) {
        parent::__construct($context);

        $this->magentoCache                    = $magentoCache;
        $this->magentoRequest                  = $context->getRequest();
        $this->magentoResponse                 = $context->getResponse();
        $this->magentoResponseRedirect         = $context->getRedirect();
        $this->magentoMessageManager           = $context->getMessageManager();
        $this->magentoApiSearchCriteriaBuilder = $magentoApiSearchCriteriaBuilder;
        $this->magentoDbTransactionFactory     = $magentoDbTransactionFactory;
        $this->magentoCheckoutSession          = $magentoCheckoutSession;
        $this->magentoSalesOrderRepository     = $magentoSalesOrderRepository;
        $this->magentoSalesOrderManagement     = $magentoSalesOrderManagement;
        $this->magentoSalesInvoiceOrder        = $magentoSalesInvoiceOrder;
        $this->magentoSalesOrderConfig         = $magentoSalesOrderConfig;
        $this->magentoSalesInvoiceService      = $magentoSalesInvoiceService;
        $this->magentoSalesInvoiceRepository   = $magentoSalesInvoiceRepository;
        $this->magentoSalesInvoiceSender       = $magentoSalesInvoiceSender;
        $this->adaptisPaymentDataHelper         = $adaptisPaymentDataHelper;
        $this->adaptisPaymentLogger             = $adaptisPaymentLogger;
        $this->adaptisPaymentOrderStatusApplier = $adaptisPaymentOrderStatusApplier;
    }

    /**
     * @return void
     */
    public function execute(): void
    {
        $this->adaptisPaymentLogger->info('[redirect] params', $this->magentoRequest->getParams());

        $responseData = $this->adaptisPaymentDataHelper->normalizeResponseData($this->magentoRequest->getParams());

        $this->adaptisPaymentLogger->info('[redirect] data', $responseData);

        $salesOrder = $this->getOrderFromResponse($responseData);

        // check if order exist
        if ( ! $salesOrder) {
            $this->redirectToCheckoutCartPage(__("No order #{$responseData['ref_no']} for processing found."), $responseData);

            return;
        }

        // check if response are sent from adaptis
        // $isResponseRemoteAddressValid = $this->adaptisPaymentDataHelper->isResponseRemoteAddressValid();
        // if ( ! $isResponseRemoteAddressValid) {
        //     $this->redirectToCheckoutCartPage(__('Invalid remote address.'), $responseData);
        //
        //     return;
        // }

        // restore cart and cancel order if signature empty
        $isResponseSignatureExists = $this->adaptisPaymentDataHelper->isResponseSignatureExist($responseData);
        if ( ! $isResponseSignatureExists) {
            $this->handleEmptyResponseSignature($salesOrder, $responseData);

            return;
        }

        // ignore request if signature mismatches
        $isResponseSignatureMatched = $this->adaptisPaymentDataHelper->isResponseSignatureMatched($responseData);
        if ( ! $isResponseSignatureMatched) {
            $this->redirectToCheckoutCartPage(__("Returned signature `{$responseData['signature']}` not match."), $responseData);

            return;
        }

        if ($responseData['status'] === \Adaptis\Payment\Gateway\Config\Config::PAYMENT_STATUS_FAIL) {
            $this->redirectToCheckoutCartPage(__($responseData['err_desc']), $responseData, $salesOrder);

            return;
        }

        if ($responseData['status'] === \Adaptis\Payment\Gateway\Config\Config::PAYMENT_STATUS_PENDING) {
            $this->adaptisPaymentLogger->info('[redirect] pending', [
                'order'    => $salesOrder->getIncrementId(),
                'response' => $responseData,
            ]);

            $this->redirectToCheckoutSuccessPage(__('We have received and will process your order once payment is confirmed.'));

            return;
        }

        if ($responseData['status'] === \Adaptis\Payment\Gateway\Config\Config::PAYMENT_STATUS_SUCCESS) {
            $this->handleSuccessResponse($salesOrder, $responseData);

            return;
        }

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
        $this->redirectToCheckoutCartPage(__($response['err_desc']), $response, $salesOrder);

        $salesOrder->addStatusToHistory(
            false,
            __('Order cancelled due to response signature is empty. Please check order status in merchant portal for confirmation.')
        );

        $this->magentoSalesOrderRepository->save($salesOrder);
    }

    protected function handleSuccessResponse(
        \Magento\Sales\Model\Order $salesOrder,
        array $response
    ): void {
        if ($salesOrder->getPayment()->getLastTransId()) {
            $this->adaptisPaymentLogger->info('[redirect] transaction existed', [
                'order'    => $salesOrder->getIncrementId(),
                'response' => $response,
            ]);

            $this->redirectToCheckoutSuccessPage();

            return;
        }

        $isProcessing = (bool) $this->magentoCache->load("adaptis_payment_processing_{$salesOrder->getIncrementId()}");
        if ($isProcessing) {
            $this->adaptisPaymentLogger->info('[redirect] processing by callback', [
                'order'    => $salesOrder->getIncrementId(),
                'response' => $response,
            ]);

            sleep(1);

            $this->redirectToCheckoutSuccessPage();

            return;
        }

        $this->magentoCache->save(1, "adaptis_payment_processing_{$salesOrder->getIncrementId()}");

        $this->adaptisPaymentOrderStatusApplier->applySuccessfulPayment($salesOrder, $response, 'frontend redirect');

        $this->magentoCache->remove("adaptis_payment_processing_{$salesOrder->getIncrementId()}");

        $this->adaptisPaymentLogger->info('[redirect] success', [
            'order'    => $salesOrder->getIncrementId(),
            'response' => $response,
        ]);

        $this->redirectToCheckoutSuccessPage();
    }


    protected function handleNoHandler(
        \Magento\Sales\Model\Order $salesOrder,
        array $response
    ): void {
        $this->adaptisPaymentLogger->notice('[redirect] no handler', [
            'order'    => $salesOrder->getIncrementId(),
            'response' => $response,
        ]);

        $this->redirectToHomepage();
    }

    /**
     * @param  string  $errorMessage
     * @param  array  $responseData
     * @param  \Magento\Sales\Model\Order|null  $salesOrder  cancel order if set
     */
    protected function redirectToCheckoutCartPage(
        string $errorMessage,
        array $responseData,
        ?\Magento\Sales\Model\Order $salesOrder = null
    ): void {
        $isRestored = $this->magentoCheckoutSession->restoreQuote();

        $this->adaptisPaymentLogger->error("[redirect] is quote restored", [$isRestored ? 'yes' : 'no']);

        $this->magentoMessageManager->addErrorMessage($errorMessage);

        $errorContext['response'] = $responseData;

        if ($salesOrder) {
            $this->magentoSalesOrderManagement->cancel($salesOrder->getId());

            $errorContext['order'] = $salesOrder->getIncrementId();
        }

        $this->adaptisPaymentLogger->error("[redirect] {$errorMessage}", $errorContext);

        $this->magentoResponseRedirect->redirect($this->magentoResponse, 'checkout/cart');
    }

    /**
     * @param  null  $successMessage
     */
    protected function redirectToCheckoutSuccessPage(
        $successMessage = null
    ): void {
        //        $this->magentoCheckoutSession->getQuote()->setIsActive(false)->save();

        if ($successMessage) {
            $this->magentoMessageManager->addSuccessMessage($successMessage);
        }

        $this->magentoResponseRedirect->redirect($this->magentoResponse, 'checkout/onepage/success');
    }

    protected function redirectToHomepage(): void
    {
        $this->magentoResponseRedirect->redirect($this->magentoResponse, '/');
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
