<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

namespace WirecardEE\Tests\Unit\Mapper;

use DateTime;
use Mage_Customer_Model_Customer;
use Mage_Customer_Model_Session;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use Wirecard\PaymentSdk\Constant\AuthMethod;
use Wirecard\PaymentSdk\Constant\ChallengeInd;
use WirecardEE\PaymentGateway\Mapper\AccountInfoMapper;

class AccountInfoMapperTest extends TestCase
{
    /**
     * @var AccountInfoMapper
     */
    protected $mapper;

    /**
     * @var Mage_Customer_Model_Session|PHPUnit_Framework_MockObject_MockObject
     */
    protected $session;

    /**
     * @var Mage_Customer_Model_Session|PHPUnit_Framework_MockObject_MockObject
     */
    protected $customer;

    protected function setUp()
    {
        // we need to explicitely specify the getUpdatedAt method, because it is implemented via __call
        $this->customer = $this->getMockBuilder(Mage_Customer_Model_Customer::class)
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->disableArgumentCloning()
            ->disallowMockingUnknownTypes()
            ->setMethods(['getUpdatedAt', 'getCreatedAtTimestamp'])
            ->getMock();

        $this->customer->method('getUpdatedAt')->willReturn('2019-08-21 12:12:12');

        $this->session = $this->createMock(Mage_Customer_Model_Session::class);
        $this->session->method('getCustomer')->willReturn($this->customer);
        $this->session->method('getCustomerId')->willReturn(1234);

        $dtShippingFirstUsed = new DateTime('2019-08-21 12:22:00');
        $dtCardCreationDate  = new DateTime('2018-08-21 12:22:00');
        $this->mapper        = new AccountInfoMapper($this->session,
            1231321323, ChallengeInd::NO_CHALLENGE, false, $dtShippingFirstUsed, $dtCardCreationDate, 6);
    }

    public function testMapper()
    {
        $this->assertSame($this->session->getCustomer(), $this->mapper->getCustomer());
        $this->assertSame(1234, $this->mapper->getCustomerId());
    }

    public function testGetAccountInfoForGuestWithoutToken()
    {
        $accountInfo = $this->mapper->getAccountInfo(null);

        $mapped = $accountInfo->mappedProperties();

        $this->assertEquals([
            'authentication-method'    => AuthMethod::GUEST_CHECKOUT,
            'authentication-timestamp' => 1231321323,
            'challenge-indicator'      => ChallengeInd::NO_CHALLENGE,
        ], $mapped);
    }

    public function testGetAccountInfoForGuestWithToken()
    {
        $accountInfo = $this->mapper->getAccountInfo('bla');

        $mapped = $accountInfo->mappedProperties();

        $this->assertEquals([
            'authentication-method'    => AuthMethod::GUEST_CHECKOUT,
            'authentication-timestamp' => 1231321323,
            'challenge-indicator'      => ChallengeInd::NO_CHALLENGE,
        ], $mapped);
    }

    public function testGetAccountInfoWithoutToken()
    {
        $this->session->method('isLoggedIn')->willReturn(true);
        $accountInfo = $this->mapper->getAccountInfo(null);

        $mapped   = $accountInfo->mappedProperties();
        $expected = [
            'authentication-method'      => AuthMethod::USER_CHECKOUT,
            'authentication-timestamp'   => 1231321323,
            'challenge-indicator'        => ChallengeInd::NO_CHALLENGE,
            'update-date'                => '2019-08-21',
            'shipping-address-first-use' => '2019-08-21',
            'card-creation-date'         => '2018-08-21',
            'purchases-last-six-months'  => 6,
        ];
        $this->assertEquals($expected, $mapped);

        $accountInfo = $this->mapper->getAccountInfo('wirecardee--new-card');
        $mapped      = $accountInfo->mappedProperties();
        $this->assertEquals($expected, $mapped);
    }

    public function testGetAccountInfoWithoutTokenWithCreationDate()
    {
        $this->session->method('isLoggedIn')->willReturn(true);
        $this->customer->method('getCreatedAtTimestamp')->willReturn('874368346');
        $accountInfo = $this->mapper->getAccountInfo(null);

        $mapped = $accountInfo->mappedProperties();

        $this->assertEquals([
            'authentication-method'      => AuthMethod::USER_CHECKOUT,
            'authentication-timestamp'   => 1231321323,
            'challenge-indicator'        => ChallengeInd::NO_CHALLENGE,
            'update-date'                => '2019-08-21',
            'shipping-address-first-use' => '2019-08-21',
            'card-creation-date'         => '2018-08-21',
            'purchases-last-six-months'  => 6,
            'creation-date'              => '1997-09-16'
        ], $mapped);
    }

    public function testGetAccountInfoWithExistingToken()
    {
        $this->session->method('isLoggedIn')->willReturn(true);
        $this->customer->method('getCreatedAtTimestamp')->willReturn('874368346');
        $accountInfo = $this->mapper->getAccountInfo('some-token');

        $mapped = $accountInfo->mappedProperties();

        $this->assertEquals([
            'authentication-method'      => AuthMethod::USER_CHECKOUT,
            'authentication-timestamp'   => 1231321323,
            'challenge-indicator'        => ChallengeInd::NO_CHALLENGE,
            'update-date'                => '2019-08-21',
            'shipping-address-first-use' => '2019-08-21',
            'card-creation-date'         => '2018-08-21',
            'purchases-last-six-months'  => 6,
            'creation-date'              => '1997-09-16'
        ], $mapped);
    }

    public function testGetAccountInfoWithNewToken()
    {
        $this->session->method('isLoggedIn')->willReturn(true);
        $this->customer->method('getCreatedAtTimestamp')->willReturn('874368346');
        $this->mapper->setIsNewToken(true);
        $accountInfo = $this->mapper->getAccountInfo('some-token');

        $mapped = $accountInfo->mappedProperties();

        $this->assertEquals([
            'authentication-method'      => AuthMethod::USER_CHECKOUT,
            'authentication-timestamp'   => 1231321323,
            'challenge-indicator'        => ChallengeInd::CHALLENGE_MANDATE,
            'update-date'                => '2019-08-21',
            'shipping-address-first-use' => '2019-08-21',
            'card-creation-date'         => '2018-08-21',
            'purchases-last-six-months'  => 6,
            'creation-date'              => '1997-09-16'
        ], $mapped);
    }

    public function testGetWithoutShippingAddressFirstUsedAndCardCreationDate()
    {
        $this->session->method('isLoggedIn')->willReturn(true);

        $mapper = new AccountInfoMapper($this->session, 1323, ChallengeInd::NO_CHALLENGE, false, null, null, 6);
        $accountInfo = $mapper->getAccountInfo(null);

        $mapped = $accountInfo->mappedProperties();

        $this->assertEquals([
            'authentication-method'    => AuthMethod::USER_CHECKOUT,
            'authentication-timestamp' => 1323,
            'challenge-indicator'      => ChallengeInd::NO_CHALLENGE,
            'update-date'                => '2019-08-21',
            'card-creation-date'         => date('Y-m-d'),
            'purchases-last-six-months'  => 6,
        ], $mapped);
    }

}
