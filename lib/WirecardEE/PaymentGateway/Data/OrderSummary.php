<?php

namespace WirecardEE\PaymentGateway\Data;

use WirecardEE\PaymentGateway\Payments\PaymentInterface;

class OrderSummary
{
    protected $payment;

    protected $order;

    protected $deviceFingerprintId;

    protected $basketMapper;

    public function __construct(
        PaymentInterface $payment,
        \Mage_Sales_Model_Order $order,
        BasketMapper $basketMapper,
        $deviceFingerprintId
    ) {
        $this->payment = $payment;
        $this->order = $order;
        $this->deviceFingerprintId = $deviceFingerprintId;
        $this->basketMapper = $basketMapper;
    }

    public function getOrder()
    {
        return $this->order;
    }

    public function getPayment()
    {
        return $this->payment;
    }

    public function getCurrency()
    {
        return $this->order->getBaseCurrencyCode();
    }

    public function getAmount()
    {
        return number_format($this->order->getBaseGrandTotal(), 2);
    }

    public function getBasketMapper()
    {
        return $this->basketMapper;
    }
}
