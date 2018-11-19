<?php

class WirecardEE_PaymentGateway_Block_Checkout_Success extends Mage_Checkout_Block_Onepage_Success
{
    public function getPaymentStatus()
    {
        $order = $this->getCheckoutSession()->getLastRealOrder();
        return $order->getStatusLabel();
    }

    protected function getCheckoutSession()
    {
        return Mage::getSingleton('checkout/session');
    }
}
