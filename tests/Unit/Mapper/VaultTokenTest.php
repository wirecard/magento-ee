<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

namespace WirecardEE\Tests\Unit\Mapper;

use PHPUnit\Framework\TestCase;
use Wirecard\PaymentSdk\Entity\AccountHolder;
use WirecardEE\PaymentGateway\Mapper\UserMapper;
use WirecardEE\PaymentGateway\Mapper\VaultToken;

class VaultTokenTest extends TestCase
{
    public function testVaultToken()
    {
        $token = [
            'id'                    => 1,
            'customer_id'           => 137,
            'token'                 => '12345',
            'masked_account_number' => '123***321',
            'last_used'             => '2019-01-01 10:00:00',
            'billing_address'       => serialize(['address' => 'billing']),
            'shipping_address'      => serialize(['address' => 'shipping']),
            'additional_data'       => serialize(['data' => 'test']),
        ];

        $vaultToken = new VaultToken($token);

        $this->assertSame($vaultToken->getId(), 1);
        $this->assertSame($vaultToken->getCustomerId(), 137);
        $this->assertSame($vaultToken->getToken(), '12345');
        $this->assertSame($vaultToken->getMaskedAccountNumber(), '123***321');
        $this->assertSame($vaultToken->getBillingAddress(), ['address' => 'billing']);
        $this->assertSame($vaultToken->getBillingAddressHash(), md5(serialize(['address' => 'billing'])));
        $this->assertSame($vaultToken->getShippingAddress(), ['address' => 'shipping']);
        $this->assertSame($vaultToken->getShippingAddressHash(), md5(serialize(['address' => 'shipping'])));
        $this->assertSame($vaultToken->getFromAdditionalData('data'), 'test');
        $this->assertInstanceOf(\DateTime::class, $vaultToken->getLastUsed());
    }
}
