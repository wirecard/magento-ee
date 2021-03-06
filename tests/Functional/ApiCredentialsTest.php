<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

namespace WirecardEE\Tests\Functional;

use PHPUnit\Framework\TestCase;
use Wirecard\PaymentSdk\Config\Config;
use Wirecard\PaymentSdk\TransactionService;
use WirecardEE\PaymentGateway\Service\Logger;

class ApiCredentialsTest extends TestCase
{
    public function testCredentials()
    {
        $testConfig         = new Config(
            getenv('API_TEST_URL'),
            getenv('API_HTTP_USER'),
            getenv('API_HTTP_PASSWORD')
        );
        $transactionService = new TransactionService($testConfig, new Logger());

        $this->assertTrue($transactionService->checkCredentials());
    }
}
