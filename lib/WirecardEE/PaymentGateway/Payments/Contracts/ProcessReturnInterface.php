<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

namespace WirecardEE\PaymentGateway\Payments\Contracts;

use Wirecard\PaymentSdk\Response\Response;
use Wirecard\PaymentSdk\TransactionService;

/**
 * @since 1.0.0
 */
interface ProcessReturnInterface
{
    /**
     * Payment specific return processing, called by the `ReturnHandler`. This method either returns a `Response` (which
     * is directly returned to the controller) or `null`. Returning `null` leads to the `TransactionService` taking
     * care of handling the response (via `handleResponse`) which is then returned to the controller.
     *
     * @param TransactionService                  $transactionService
     *
     * @return Response|null
     *
     * @since 1.1.0 Added $sessionManager
     * @since 1.0.0
     */
    public function processReturn(TransactionService $transactionService);
}
