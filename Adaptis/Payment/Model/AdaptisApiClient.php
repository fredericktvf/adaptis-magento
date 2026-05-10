<?php

namespace Adaptis\Payment\Model;

class AdaptisApiClient
{
    /**
     * @var \Magento\Framework\HTTP\Client\Curl
     */
    private $curl;

    /**
     * @var \Adaptis\Payment\Gateway\Config\Config
     */
    private $config;

    /**
     * @var \Adaptis\Payment\Helper\Data
     */
    private $dataHelper;

    /**
     * @var \Adaptis\Payment\Logger\Logger
     */
    private $logger;

    public function __construct(
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Adaptis\Payment\Gateway\Config\Config $config,
        \Adaptis\Payment\Helper\Data $dataHelper,
        \Adaptis\Payment\Logger\Logger $logger
    ) {
        $this->curl = $curl;
        $this->config = $config;
        $this->dataHelper = $dataHelper;
        $this->logger = $logger;
    }

    public function refund(
        \Magento\Sales\Model\Order $order,
        float $amount,
        string $refundRefNo,
        string $remark = ''
    ): array {
        $payload = $this->dataHelper->buildRefundRequestData($order, $amount, $refundRefNo, $remark);

        return $this->postJson($this->config->getRefundUrl(), $payload, 'refund');
    }

    public function requery(\Magento\Sales\Model\Order $order): array
    {
        $payload = $this->dataHelper->buildRequeryRequestData($order);

        return $this->postJson($this->config->getRequeryUrl(), $payload, 'requery');
    }

    private function postJson(string $url, array $payload, string $context): array
    {
        $this->logger->info("[api {$context}] request", [
            'url' => $url,
            'payload' => $payload,
        ]);

        $this->curl->setHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ]);
        $this->curl->post($url, json_encode($payload));

        $status = (int) $this->curl->getStatus();
        $body = (string) $this->curl->getBody();
        $decoded = json_decode($body, true);

        $this->logger->info("[api {$context}] response", [
            'status' => $status,
            'body' => $decoded ?: $body,
        ]);

        if ($status < 200 || $status >= 300) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __("ADAPTIS API returned HTTP %1.", $status)
            );
        }

        if (!is_array($decoded)) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('ADAPTIS API returned an invalid JSON response.')
            );
        }

        return $decoded;
    }
}
