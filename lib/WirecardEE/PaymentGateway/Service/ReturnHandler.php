<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

namespace WirecardEE\PaymentGateway\Service;

use Psr\Log\LoggerInterface;
use Wirecard\PaymentSdk\Response\FailureResponse;
use Wirecard\PaymentSdk\Response\InteractionResponse;
use Wirecard\PaymentSdk\Response\Response;
use Wirecard\PaymentSdk\Response\SuccessResponse;
use Wirecard\PaymentSdk\TransactionService;
use WirecardEE\PaymentGateway\Actions\ErrorAction;
use WirecardEE\PaymentGateway\Actions\RedirectAction;

class ReturnHandler
{
    /** @var LoggerInterface  */
    protected $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param \Mage_Core_Controller_Request_Http $request
     * @param TransactionService                 $transactionService
     *
     * @return FailureResponse|InteractionResponse|Response|SuccessResponse
     */
    public function handleRequest(
        \Mage_Core_Controller_Request_Http $request,
        TransactionService $transactionService
    ) {
        return $transactionService->handleResponse($request->getParams());
    }

    /**
     * @param Response $response
     *
     * @return ErrorAction
     */
    public function handleResponse(Response $response)
    {
        return $this->handleFailure($response);
    }

    /**
     * @param Response $repsonse
     * @param          $url
     *
     * @return RedirectAction
     */
    public function handleSuccess(Response $repsonse, $url)
    {
        return new RedirectAction($url);
    }

    /**
     * @param $response
     *
     * @return ErrorAction
     */
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
        return new ErrorAction(ErrorAction::FAILURE_RESPONSE, $message);
    }
}
