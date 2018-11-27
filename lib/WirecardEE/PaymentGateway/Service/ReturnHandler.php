<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

namespace WirecardEE\PaymentGateway\Service;

use Wirecard\PaymentSdk\Response\FailureResponse;
use Wirecard\PaymentSdk\Response\FormInteractionResponse;
use Wirecard\PaymentSdk\Response\InteractionResponse;
use Wirecard\PaymentSdk\Response\Response;
use Wirecard\PaymentSdk\Response\SuccessResponse;
use Wirecard\PaymentSdk\TransactionService;
use WirecardEE\PaymentGateway\Actions\Action;
use WirecardEE\PaymentGateway\Actions\ErrorAction;
use WirecardEE\PaymentGateway\Actions\RedirectAction;
use WirecardEE\PaymentGateway\Actions\ViewAction;
use WirecardEE\PaymentGateway\Payments\Contracts\ProcessReturnInterface;
use WirecardEE\PaymentGateway\Payments\PaymentInterface;

/**
 * Responsible for handling return actions. Payments may implement their own way of handling returns by implementing
 * the `ProcessReturnInterface` interface.
 *
 * @since 1.0.0
 */
class ReturnHandler extends Handler
{
    /**
     * @param PaymentInterface                   $payment
     * @param \Mage_Core_Controller_Request_Http $request
     * @param TransactionService                 $transactionService
     *
     * @return FailureResponse|InteractionResponse|Response|SuccessResponse
     *
     * @since 1.0.0
     */
    public function handleRequest(
        PaymentInterface $payment,
        \Mage_Core_Controller_Request_Http $request,
        TransactionService $transactionService
    ) {
        if ($payment instanceof ProcessReturnInterface) {
            $response = $payment->processReturn($transactionService);
            if ($response) {
                return $response;
            }
        }

        return $transactionService->handleResponse($request->getParams());
    }

    /**
     * @param Response $response
     *
     * @return Action
     *
     * @since 1.0.0
     */
    public function handleResponse(Response $response)
    {
        if ($response instanceof FormInteractionResponse) {
            return new ViewAction('seamless_form.tpl', [
                'method'     => $response->getMethod(),
                'formFields' => $response->getFormFields(),
                'url'        => $response->getUrl(),
            ]);
        }
        if ($response instanceof InteractionResponse) {
            return new RedirectAction($response->getRedirectUrl());
        }
        return $this->handleFailure($response);
    }

    /**
     * @param $response
     *
     * @return ErrorAction
     *
     * @since 1.0.0
     */
    protected function handleFailure($response)
    {
        // todo: set order status to cancel
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
