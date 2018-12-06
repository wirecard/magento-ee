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

/**
 * Base class for handler implementations. Handlers are used to perform specific tasks, e.g. payment processing,
 * handling return actions, etc..
 *
 * @since   1.0.0
 */
abstract class Handler
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var TransactionManager
     */
    protected $transactionManager;

    /**
     * @param TransactionManager $transactionManager
     * @param LoggerInterface    $logger
     *
     * @since 1.0.0
     */
    public function __construct(TransactionManager $transactionManager, LoggerInterface $logger)
    {
        $this->transactionManager = $transactionManager;
        $this->logger = $logger;
    }
}
