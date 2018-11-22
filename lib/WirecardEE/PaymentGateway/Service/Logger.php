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
 * PSR compatible Logger.
 *
 * @since 1.0.0
 */
class Logger implements LoggerInterface
{
    const LOG_FILE = 'wirecard_elastic_engine.log';

    /**
     * System is unusable.
     *
     * @param string $message
     * @param array  $context
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function emergency($message, array $context = [])
    {
        $this->log(\Zend_Log::EMERG, $message, $context);
    }

    /**
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     *
     * @param string $message
     * @param array  $context
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function alert($message, array $context = [])
    {
        $this->log(\Zend_Log::ALERT, $message, $context);
    }

    /**
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     *
     * @param string $message
     * @param array  $context
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function critical($message, array $context = [])
    {
        $this->log(\Zend_Log::CRIT, $message, $context);
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param string $message
     * @param array  $context
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function error($message, array $context = [])
    {
        $this->log(\Zend_Log::ERR, $message, $context);
    }

    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     *
     * @param string $message
     * @param array  $context
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function warning($message, array $context = [])
    {
        $this->log(\Zend_Log::WARN, $message, $context);
    }

    /**
     * Normal but significant events.
     *
     * @param string $message
     * @param array  $context
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function notice($message, array $context = [])
    {
        $this->log(\Zend_Log::NOTICE, $message, $context);
    }

    /**
     * Interesting events.
     *
     * Example: User logs in, SQL logs.
     *
     * @param string $message
     * @param array  $context
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function info($message, array $context = [])
    {
        $this->log(\Zend_Log::INFO, $message, $context);
    }

    /**
     * Detailed debug information.
     *
     * @param string $message
     * @param array  $context
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function debug($message, array $context = [])
    {
        $this->log(\Zend_Log::DEBUG, $message, $context);
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed  $level
     * @param string $message
     * @param array  $context
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function log($level, $message, array $context = [])
    {
        \Mage::log(
            $message . (count($context) > 0 ? ' ' . print_r($context, true) : ''),
            (int)$level,
            self::LOG_FILE
        );
    }
}
