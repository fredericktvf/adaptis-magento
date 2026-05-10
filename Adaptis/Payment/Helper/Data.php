<?php

namespace Adaptis\Payment\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $magentoUrlBuilder;

    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    protected $magentoEncryptor;

    /**
     * @var \Adaptis\Payment\Gateway\Config\Config
     */
    protected $adaptisPaymentGatewayConfig;

    /**
     * @var \Adaptis\Payment\Helper\Config
     */
    protected $adaptisPaymentConfigHelper;

    /**
     * @var \Adaptis\Payment\Logger\Logger
     */
    protected $adaptisPaymentLogger;

    /**
     * @var \Magento\Directory\Model\CountryFactory
     */
    protected $countryFactory;

    /**
     * @var array
     */
    protected $responseIpAddress = [
        '111.67.33.142',
        '111.67.33.90',
        '111.67.33.126',
    ];

    /**
     * Data constructor.
     *
     * @param  \Magento\Framework\App\Helper\Context  $context
     * @param  \Magento\Framework\Encryption\EncryptorInterface  $magentoEncryptor
     * @param  \Adaptis\Payment\Gateway\Config\Config  $adaptisPaymentGatewayConfig
     * @param  \Adaptis\Payment\Helper\Config  $adaptisPaymentConfigHelper
     * @param  \Adaptis\Payment\Logger\Logger  $adaptisPaymentLogger
     * @param  \Adaptis\Payment\Logger\Logger  $adaptisPaymentLogger
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Encryption\EncryptorInterface $magentoEncryptor,
        \Adaptis\Payment\Gateway\Config\Config $adaptisPaymentGatewayConfig,
        \Adaptis\Payment\Helper\Config $adaptisPaymentConfigHelper,
        \Adaptis\Payment\Logger\Logger $adaptisPaymentLogger,
        \Magento\Directory\Model\CountryFactory $countryFactory
        
    ) {
        parent::__construct($context);

        $this->magentoUrlBuilder          = $context->getUrlBuilder();
        $this->magentoEncryptor           = $magentoEncryptor;
        $this->adaptisPaymentGatewayConfig = $adaptisPaymentGatewayConfig;
        $this->adaptisPaymentConfigHelper  = $adaptisPaymentConfigHelper;
        $this->adaptisPaymentLogger        = $adaptisPaymentLogger;
        $this->countryFactory             = $countryFactory;
    }

    /**
     * @param  \Magento\Sales\Model\Order  $order
     *
     * @return array
     */
    public function generateRequestData(
        \Magento\Sales\Model\Order $order
    ): array {
        $amount = $this->adaptisPaymentConfigHelper->isTestingMode() ? 1 : $order->getGrandTotal();

        $productDescriptions = array_map(function (\Magento\Sales\Model\Order\Item $item) {
            return $item->getName();
        }, $order->getAllItems());

        //  ['RefNo'] . $this->hash_amount . $params['Currency'] . $params['TransId'] . $params['TransactionStatusId'] . $params['PaymentID'];

        $signature = $this->generateSignature([
            $order->getIncrementId(), //ref no
            number_format($amount,  2, '', ''), //amount
            $order->getOrderCurrencyCode(), //currency 
            '', // xfield1
        ]);
    
        // $this->magentoEncryptor->decrypt($this->adaptisPaymentGatewayConfig->getMerchantKey()), // merchant key
        // $this->adaptisPaymentGatewayConfig->getMerchantCode(), // merchant code
    

        $paymentId = $order->getPayment()->getAdditionalInformation()['payment_id'] ?? '';
        $billingAddress = $order->getBillingAddress();
        $street = $billingAddress->getStreet();
        $countryCode = $billingAddress->getCountryId();
        $countryName = $this->countryFactory->create()->loadByCode($countryCode)->getName();
       
        return [
            'merchant_code'  => $this->adaptisPaymentGatewayConfig->getMerchantCode(),
            'payment_id'     => $paymentId,
            'ref_no'         => $order->getIncrementId(),
            'amount'         => number_format($amount, 2),
            'currency'       => $order->getOrderCurrencyCode(),
            'prod_desc'      => substr(implode(', ', $productDescriptions), 0, 90),
            'user_name'      => "{$order->getBillingAddress()->getFirstname()} {$order->getBillingAddress()->getLastname()}",
            'user_email'     => $order->getBillingAddress()->getEmail() ?: $order->getCustomerEmail(),
            'user_contact'   => $order->getBillingAddress()->getTelephone(),
            'street'         => is_array($street) ? implode(', ', $street) : $street,
            'state'          => $billingAddress->getRegion() ?: $billingAddress->getCity(),
            'city'           => $billingAddress->getCity(),
            'postal_code'    => $billingAddress->getPostcode(),
            'country'        => $countryName,
            'signature_type' => 'HMACSHA512',
            'signature'      => $signature,
            'response_url'   => $this->magentoUrlBuilder->getUrl('adaptis_payment/checkout/redirect'),
            'backend_url'    => $this->adaptisPaymentGatewayConfig->getBackendUrl()
                ?: $this->magentoUrlBuilder->getUrl('adaptis_payment/checkout/callback'),
            'hosted_payment_url' => $this->adaptisPaymentGatewayConfig->getHostedPaymentUrl(),
        ];
    }

    public function normalizeResponseData(array $params): array
    {
        return [
            'merchant_code' => $params['MerchantCode'] ?? '',
            'ref_no'        => $params['RefNo'] ?? '',
            'amount'        => $params['Amount'] ?? '',
            'currency'      => $params['Currency'] ?? '',
            'trans_id'      => $params['TransId'] ?? $params['TransID'] ?? '',
            'remark'        => $params['Remark'] ?? '',
            'status'        => $params['TransactionStatusId'] ?? $params['TransactionStatusID'] ?? '',
            'err_desc'      => $params['ErrorDescription'] ?? $params['ErrorInfo']['ErrorDescription'] ?? '',
            'payment_id'    => $params['PaymentID'] ?? $params['PaymentId'] ?? '',
            'auth_code'     => $params['AuthCode'] ?? '',
            'cc_name'       => $params['PayerName'] ?? '',
            'cc_no'         => $params['CardNumber'] ?? '',
            's_bankname'    => $params['CardBankName'] ?? '',
            's_country'     => $params['CardCountry'] ?? '',
            'tran_date'     => $params['TransactionDate'] ?? '',
            'signature'     => $params['Signature'] ?? '',
        ];
    }

    public function normalizeCallbackData(array $params): array
    {
        return [
            'merchant_code' => $params['MerchantCode'] ?? '',
            'ref_no'        => $params['RefNo'] ?? '',
            'amount'        => $params['Amount'] ?? '',
            'currency'      => $params['Currency'] ?? '',
            'trans_id'      => $params['TransId'] ?? $params['TransID'] ?? '',
            'remark'        => $params['Remark'] ?? '',
            'status'        => $params['TransactionStatusId'] ?? $params['TransactionStatusID'] ?? '',
            'err_desc'      => $params['ErrorDescription'] ?? $params['ErrorInfo']['ErrorDescription'] ?? '',
            'payment_id'    => $params['PaymentInfo']['PaymentID'] ?? $params['PaymentInfo']['PaymentId'] ?? '',
            'auth_code'     => $params['PaymentInfo']['AuthCode'] ?? '',
            'cc_name'       => $params['PaymentInfo']['PayerName'] ?? '',
            'cc_no'         => $params['PaymentInfo']['CardNumber'] ?? '',
            's_bankname'    => $params['PaymentInfo']['CardBankName'] ?? '',
            's_country'     => $params['PaymentInfo']['CardCountry'] ?? '',
            'tran_date'     => $params['PaymentInfo']['TransactionDate'] ?? '',
            'signature'     => $params['Verification']['Signature'] ?? $params['Signature'] ?? '',

        ];
    }

    public function buildRefundRequestData(
        \Magento\Sales\Model\Order $order,
        float $amount,
        string $refundRefNo,
        string $remark = ''
    ): array {
        $currency = $order->getOrderCurrencyCode();
        $ipayId = (string) $order->getPayment()->getLastTransId();
        $refundAmount = $this->formatAmountWithThousands($amount);

        return [
            'MerchantCode' => $this->adaptisPaymentGatewayConfig->getMerchantCode(),
            'RequestType' => '10',
            'IpayId' => $ipayId,
            'RefNo' => $refundRefNo,
            'RefundAmount' => $refundAmount,
            'RefundCurrency' => $currency,
            'Remark' => $remark !== '' ? $remark : 'Magento refund',
            'Verification' => [
                'SignatureType' => 'HMACSHA512',
                'Signature' => $this->generateSignature([
                    $ipayId,
                    $refundRefNo,
                    $this->formatAmountForSignature($amount),
                    $currency,
                ]),
            ],
        ];
    }

    public function buildRequeryRequestData(\Magento\Sales\Model\Order $order): array
    {
        $amount = (float) $order->getGrandTotal();
        $refNo = $order->getIncrementId();

        return [
            'MerchantCode' => $this->adaptisPaymentGatewayConfig->getMerchantCode(),
            'RefNo' => $refNo,
            'Amount' => $this->formatAmountNoThousands($amount),
            'Verification' => [
                'SignatureType' => 'HMACSHA512',
                'Signature' => $this->generateSignature([
                    $refNo,
                    $this->formatAmountForSignature($amount),
                ]),
            ],
        ];
    }

    public function formatAmountWithThousands($amount): string
    {
        return number_format((float) $amount, 2, '.', ',');
    }

    public function formatAmountNoThousands($amount): string
    {
        return number_format((float) $amount, 2, '.', '');
    }

    public function formatAmountForSignature($amount): string
    {
        return str_replace(['.', ','], '', $this->formatAmountWithThousands($amount));
    }

    /**
     * @param  array  $response
     *
     * @return bool
     */
    public function isResponseSignatureExist(array $response): bool
    {
        return isset($response['signature']) && $response['signature'] !== '';
    }

    /**
     * @param  array  $response
     *
     * @return bool
     */
    public function isResponseSignatureMatched(array $response): bool
    {
        //. $query['RefNo'] . $hash_amount . $query['Currency'] . $query['TransId'] . $query['TransactionStatusId'] . $query['PaymentID'];
        $signature = $this->generateSignature([
            $response['ref_no'], //ref no
            $this->formatAmountForSignature($response['amount']), //amount
            $response['currency'], //currency
            $response['trans_id'], //status
            $response['status'], //status
            $response['payment_id'], //payment id
        ]);

        return $signature === $response['signature'];
    }

    /**
     * @return bool
     */
    public function isResponseRemoteAddressValid(): bool
    {
        return in_array($this->_remoteAddress->getRemoteAddress(), $this->responseIpAddress, true);
    }

    /**
     * Generature
     *
     * @param  array  $source
     *
     * @return false|string
     */
    public function generateSignature(array $source)
    {
        $merchantKey = $this->magentoEncryptor->decrypt($this->adaptisPaymentGatewayConfig->getMerchantKey());
        $data = $merchantKey . $this->adaptisPaymentGatewayConfig->getMerchantCode() . implode('', $source);
        $hashed =  hash_hmac('sha512', $data, $merchantKey);

        $this->adaptisPaymentLogger->info('[signature]', [
            'source'    => $source,
            'signature' => $hashed,
        ]);

        return $hashed;
    }
}
