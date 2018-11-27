<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

namespace WirecardEE\Tests\Unit\Service;

use WirecardEE\PaymentGateway\Service\Logger;
use WirecardEE\Tests\Test\MagentoTestCase;

class CredentialsButtonTest extends MagentoTestCase
{
    public function testLogger()
    {
        $loggerMock = $this->getMockBuilder(Logger::class)
                           ->setMethods(['log'])
                           ->getMock();

        $loggerMock->expects($this->exactly(8))
                   ->method('log')
                   ->withConsecutive(
                       [$this->equalTo(\Zend_Log::EMERG), $this->equalTo('emergency')],
                       [$this->equalTo(\Zend_Log::ALERT), $this->equalTo('alert')],
                       [$this->equalTo(\Zend_Log::CRIT), $this->equalTo('critical')],
                       [$this->equalTo(\Zend_Log::ERR), $this->equalTo('error')],
                       [$this->equalTo(\Zend_Log::WARN), $this->equalTo('warning')],
                       [$this->equalTo(\Zend_Log::NOTICE), $this->equalTo('notice')],
                       [$this->equalTo(\Zend_Log::INFO), $this->equalTo('info')],
                       [$this->equalTo(\Zend_Log::DEBUG), $this->equalTo('debug')]
                   );

        /** @var $loggerMock Logger */
        $loggerMock->emergency('emergency', []);
        $loggerMock->alert('alert', []);
        $loggerMock->critical('critical', []);
        $loggerMock->error('error', []);
        $loggerMock->warning('warning', []);
        $loggerMock->notice('notice', []);
        $loggerMock->info('info', []);
        $loggerMock->debug('debug', []);
    }
}
