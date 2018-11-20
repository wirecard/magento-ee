<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

namespace WirecardEE\PaymentGateway\Data;

use Wirecard\PaymentSdk\Entity\Device;
use WirecardEE\PaymentGateway\Payments\PaymentInterface;

/**
 * The `OrderSummary` is passed to the `PaymentHandler` which processes the actual payment, based on the information
 * stored in the summary (e.g. used payment method, consumer information, etc..).
 */
class OrderSummary
{
    /**
     * @var PaymentInterface
     */
    protected $payment;

    /**
     * @var \Mage_Sales_Model_Order
     */
    protected $order;

    /**
     * @var string
     */
    protected $deviceFingerprintId;

    /**
     * @var BasketMapper
     */
    protected $basketMapper;

    /**
     * @var UserMapper
     */
    protected $userMapper;

    /**
     * @param PaymentInterface        $payment
     * @param \Mage_Sales_Model_Order $order
     * @param BasketMapper            $basketMapper
     * @param UserMapper              $userMapper
     * @param                         $deviceFingerprintId
     */
    public function __construct(
        PaymentInterface $payment,
        \Mage_Sales_Model_Order $order,
        BasketMapper $basketMapper,
        UserMapper $userMapper,
        $deviceFingerprintId
    ) {
        $this->payment             = $payment;
        $this->order               = $order;
        $this->deviceFingerprintId = $deviceFingerprintId;
        $this->basketMapper        = $basketMapper;
        $this->userMapper          = $userMapper;
    }

    /**
     * @return \Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @return PaymentInterface
     */
    public function getPayment()
    {
        return $this->payment;
    }

    /**
     * @return string
     */
    public function getCurrency()
    {
        return $this->order->getBaseCurrencyCode();
    }

    /**
     * @return string
     */
    public function getAmount()
    {
        return number_format($this->order->getBaseGrandTotal(), 2);
    }

    /**
     * @return BasketMapper
     */
    public function getBasketMapper()
    {
        return $this->basketMapper;
    }

    /**
     * @return UserMapper
     */
    public function getUserMapper()
    {
        return $this->userMapper;
    }

    /**
     * @return string
     */
    public function getDeviceFingerprintId()
    {
        return $this->deviceFingerprintId;
    }

    /**
     * @return Device
     */
    public function getWirecardDevice()
    {
        $device = new Device();
        $device->setFingerprint($this->getDeviceFingerprintId());
        return $device;
    }
}
