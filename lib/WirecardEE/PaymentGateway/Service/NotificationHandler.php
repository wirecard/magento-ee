<?php

namespace WirecardEE\PaymentGateway\Service;

use Psr\Log\LoggerInterface;
use Wirecard\PaymentSdk\Response\Response;
use Wirecard\PaymentSdk\Response\SuccessResponse;

class NotificationHandler
{
    protected $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function handleResponse(Response $response)
    {
        if ($response instanceof SuccessResponse) {
            $this->handleSuccess($response);
        }
    }

    protected function handleSuccess(SuccessResponse $response)
    {
        $this->logger->info('Incoming success notification', $response->getData());
    }
}
