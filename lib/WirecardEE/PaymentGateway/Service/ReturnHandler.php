<?php

namespace WirecardEE\PaymentGateway\Service;

use Psr\Log\LoggerInterface;
use Wirecard\PaymentSdk\Response\FailureResponse;
use Wirecard\PaymentSdk\Response\Response;
use Wirecard\PaymentSdk\TransactionService;
use WirecardEE\PaymentGateway\Actions\ErrorAction;
use WirecardEE\PaymentGateway\Actions\RedirectAction;

class ReturnHandler
{
    /** @var LoggerInterface  */
    protected $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function handleRequest(
        \Mage_Core_Controller_Request_Http $request,
        TransactionService $transactionService
    ) {
        return $transactionService->handleResponse($request->getParams());
    }

    public function handleResponse(Response $response)
    {
        return $this->handleFailure($response);
    }

    public function handleSuccess(Response $repsonse, $url)
    {
        return new RedirectAction($url);
    }

    protected function handleFailure($response)
    {
        $message = 'Unexpected response';
        $context = [get_class($response)];

        if ($response instanceof FailureResponse) {
            $message = 'Failure response';
        }
        if ($response instanceof Response) {
            $context = $response->getData();
        }

        $this->logger->error('Return handling failed: ' . $message, $context);
        return new ErrorAction(0, $message);
    }
}
