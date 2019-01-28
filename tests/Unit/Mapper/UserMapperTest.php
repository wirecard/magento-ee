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

class UserMapperTest extends TestCase
{
    public function testMapper()
    {
        $ua = 'User-Agent: Mozilla/5.0 (X11; U; Linux i686; xx; rv:1.9.1.9) ' .
            'Gecko/20100330 Fedora/3.5.9-2.fc12 Firefox/3.5.9';
        $order  = $this->createMock(\Mage_Sales_Model_Order::class);
        $mapper = new UserMapper($order, '127.0.0.1', $ua, 'de');
        $this->assertSame($order, $mapper->getOrder());
        $this->assertSame('127.0.0.1', $mapper->getClientIp());
        $this->assertSame('de', $mapper->getLocale());
        $this->assertSame($ua, $mapper->getUserAgent());
    }

    public function testShippingAccountHolder()
    {
        $order   = $this->createMock(\Mage_Sales_Model_Order::class);
        $address = $this->createMock(\Mage_Sales_Model_Order_Address::class);
        $address->method('__call')->willReturnMap([
            ['getFirstname', [], 'First Name'],
            ['getLastname', [], 'Last Name'],
            ['getEmail', [], 'test@example.com'],
            ['getTelephone', [], '+43123456789'],
            ['getCountryId', [], 'DE'],
            ['getCity', [], 'Footown'],
            ['getPostcode', [], '1337'],
        ]);
        $address->method('getStreet1')->willReturn('Barstreet 1');
        $address->method('getStreet2')->willReturn('Barstreet 2');
        $address->method('getRegion')->willReturn('Bazien');
        $order->method('getShippingAddress')->willReturn($address);
        $order->method('__call')->willReturnMap([
            ['getCustomerDob', [], '1.2.1990'],
            ['getCustomerGender', [], '1'],
        ]);

        $mapper  = new UserMapper($order, '127.0.0.1', '', 'de');
        $account = $mapper->getShippingAccountHolder();
        $this->assertInstanceOf(AccountHolder::class, $account);
        $this->assertEquals([
            'first-name' => 'First Name',
            'last-name'  => 'Last Name',
            'address'    => [
                'street1'     => 'Barstreet 1',
                'street2'     => 'Barstreet 2',
                'city'        => 'Footown',
                'country'     => 'DE',
                'postal-code' => '1337',
                'state'       => 'Bazien',
            ],
        ], $account->mappedProperties());
    }

    public function testBillingAccountHolder()
    {
        $order   = $this->createMock(\Mage_Sales_Model_Order::class);
        $address = $this->createMock(\Mage_Sales_Model_Order_Address::class);
        $address->method('__call')->willReturnMap([
            ['getFirstname', [], 'First Name'],
            ['getLastname', [], 'Last Name'],
            ['getEmail', [], 'test@example.com'],
            ['getTelephone', [], '+43123456789'],
            ['getCountryId', [], 'DE'],
            ['getCity', [], 'Footown'],
            ['getPostcode', [], '1337'],
        ]);
        $address->method('getStreet1')->willReturn('Barstreet 1');
        $address->method('getStreet2')->willReturn('Barstreet 2');
        $address->method('getRegion')->willReturn('Bazien');
        $order->method('getBillingAddress')->willReturn($address);
        $order->method('__call')->willReturnMap([
            ['getCustomerDob', [], '1.2.1990'],
            ['getCustomerGender', [], '1'],
        ]);

        $mapper  = new UserMapper($order, '127.0.0.1', '', 'de');
        $account = $mapper->getBillingAccountHolder();
        $this->assertInstanceOf(AccountHolder::class, $account);
        $this->assertEquals([
            'first-name'    => 'First Name',
            'last-name'     => 'Last Name',
            'email'         => 'test@example.com',
            'date-of-birth' => (new \DateTime('1990-02-01'))->format('d-m-Y'),
            'phone'         => '+43123456789',
            'gender'        => 'm',
            'address'       => [
                'street1'     => 'Barstreet 1',
                'street2'     => 'Barstreet 2',
                'city'        => 'Footown',
                'country'     => 'DE',
                'postal-code' => '1337',
                'state'       => 'Bazien',
            ],
        ], $account->mappedProperties());
    }

    public function testBillingAccountHolderAlternative()
    {
        $order   = $this->createMock(\Mage_Sales_Model_Order::class);
        $address = $this->createMock(\Mage_Sales_Model_Order_Address::class);
        $address->method('__call')->willReturnMap([
            ['getCountryId', [], 'AT'],
            ['getCity', [], ''],
        ]);
        $address->method('getStreet1')->willReturn('');
        $order->method('getBillingAddress')->willReturn($address);
        $order->method('__call')->willReturnMap([
            ['getCustomerDob', [], ''],
            ['getCustomerGender', [], '2'],
        ]);

        $mapper  = new UserMapper($order, '127.0.0.1', '', 'de');
        $account = $mapper->getBillingAccountHolder();
        $this->assertInstanceOf(AccountHolder::class, $account);
        $this->assertEquals([
            'gender'  => 'f',
            'address' => [
                'street1' => '',
                'city'    => '',
                'country' => 'AT',
            ],
        ], $account->mappedProperties());
    }
}
