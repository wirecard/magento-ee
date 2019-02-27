<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

namespace WirecardEE\Tests\Unit\Model;

use PHPUnit\Framework\TestCase;

class CreditCardVaultTokenTest extends TestCase
{
    public function testCreditCardVaultToken()
    {
        $model = new \WirecardEE_PaymentGateway_Model_CreditCardVaultToken();
        $model->setCustomerId(42);
        $model->setMaskedAccountNumber('123***321');
        $model->setToken('ABC123');
        $model->setExpirationDate(2019, 2);
        $model->setLastUsed(new \DateTime());

        $this->assertEquals(42, $model->getCustomerId());
        $this->assertEquals('123***321', $model->getMaskedAccountNumber());
        $this->assertEquals('ABC123', $model->getToken());
        $this->assertStringStartsWith('2019-02-01', $model->getExpirationDate());
    }

    public function testAdditionalData()
    {
        $model = new \WirecardEE_PaymentGateway_Model_CreditCardVaultToken();
        $this->assertTrue(is_array($model->getAdditionalData()));
        $this->assertEmpty($model->getAdditionalData());
        $model->setAdditionalData([
            'firstName' => 'John',
            'lastName'  => 'Doe',
        ]);
        $this->assertEquals('John', $model->getFirstName());
        $this->assertEquals('Doe', $model->getLastName());
    }

    public function testCreateAddress()
    {
        $billingAddress = $this->createMock(\Mage_Sales_Model_Order_Address::class);
        $billingAddress->method('__call')->willReturnMap([
            ['getCountryId', [], 'AT'],
            ['getCity', [], 'Graz'],
            ['getPostcode', [], '8020'],
        ]);
        $billingAddress->method('getStreet')->willReturn('Grieskai');
        $billingAddress->method('getRegionCode')->willReturn('billing');

        $shippingAddress = $this->createMock(\Mage_Sales_Model_Order_Address::class);
        $shippingAddress->method('__call')->willReturnMap([
            ['getCountryId', [], 'DE'],
            ['getCity', [], 'München'],
            ['getPostcode', [], '80337'],
        ]);
        $shippingAddress->method('getStreet')->willReturn('Camerloherstraße');
        $shippingAddress->method('getRegionCode')->willReturn('shipping');

        $model = new \WirecardEE_PaymentGateway_Model_CreditCardVaultToken();
        $model->setBillingAddress($billingAddress);
        $model->setShippingAddress($shippingAddress);

        $expectedBillingAddress = serialize([
            'country' => 'AT',
            'city'    => 'Graz',
            'street'  => 'Grieskai',
            'zip'     => '8020',
            'region'  => 'billing',
        ]);
        $billingAddressHash = md5($expectedBillingAddress);
        $this->assertEquals($expectedBillingAddress, $model->getSerializedBillingAddress());
        $this->assertEquals($billingAddressHash, $model->getBillingAddressHash());

        $expectedShippingAddress = serialize([
            'country' => 'DE',
            'city'    => 'München',
            'street'  => 'Camerloherstraße',
            'zip'     => '80337',
            'region'  => 'shipping',
        ]);
        $shippingAddressHash = md5($expectedShippingAddress);
        $this->assertEquals($expectedShippingAddress, $model->getSerializedShippingAddress());
        $this->assertEquals($shippingAddressHash, $model->getShippingAddressHash());
    }
}
