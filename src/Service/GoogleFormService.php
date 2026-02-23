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
        private string $tailorFormUrl = '',
    ) {
        $this->orderFormUrl = $_ENV['GOOGLE_FORM_ORDER_URL'] ?? '';
        $this->tailorFormUrl = $_ENV['GOOGLE_FORM_TAILOR_URL'] ?? '';
    }

    /**
     * Submit order data to Google Form
     */
    public function submitOrder(
        string $fullName,
        string $phone,
        string $productName,
        string $size,
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
                    'entry.201442480' => $size,
                    'entry.985822519' => (string) $quantity,
                ],
            ]);
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to submit order to Google Form: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Submit tailor booking data to Google Form
     */
    public function submitTailorBooking(
        string $fullName,
        string $phone,
        string $tailorName
    ): bool {
        if (empty($this->tailorFormUrl)) {
            $this->logger->warning('Google Form tailor URL not configured');
            return true;
        }

        try {
            $this->httpClient->request('POST', $this->tailorFormUrl, [
                'body' => [
                    'entry.2018268978' => $fullName,
                    'entry.1583412665' => $phone,
                    'entry.817770932' => $tailorName,
                ],
            ]);
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to submit tailor booking to Google Form: ' . $e->getMessage());
            return false;
        }
    }
}
