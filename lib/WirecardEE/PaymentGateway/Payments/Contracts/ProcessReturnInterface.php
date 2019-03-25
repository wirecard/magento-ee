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
     * @param TransactionService $transactionService
     * @param \Mage_Core_Controller_Request_Http $request
     * @param \Mage_Sales_Model_Order $order
     *
     * @return Response|null
     *
     * @since 1.0.0
     */
    public function processReturn(
        TransactionService $transactionService,
        \Mage_Core_Controller_Request_Http $request,
        \Mage_Sales_Model_Order $order
    );
}
