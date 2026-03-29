<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class GoogleFormService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        private string $orderFormUrl = '',
    ) {
        $this->orderFormUrl = $_ENV['GOOGLE_FORM_ORDER_URL'] ?? '';
    }

    /**
     * Submit order data to Google Form
     */
    public function submitOrder(
        string $fullName,
        string $phone,
        string $productName,
        int $quantity
    ): bool {
        if (empty($this->orderFormUrl)) {
            $this->logger->warning('Google Form order URL not configured');
            return true; // Return true to not block the flow
        }

        try {
            $this->httpClient->request('POST', $this->orderFormUrl, [
                'body' => [
                    'entry.2018268978' => $fullName,
                    'entry.1583412665' => $phone,
                    'entry.817770932' => $productName,
                    'entry.985822519' => (string) $quantity,
                ],
            ]);
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to submit order to Google Form: ' . $e->getMessage());
            return false;
        }
    }

}
