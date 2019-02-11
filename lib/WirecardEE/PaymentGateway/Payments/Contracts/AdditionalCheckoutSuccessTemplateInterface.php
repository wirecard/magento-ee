<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

namespace WirecardEE\PaymentGateway\Payments\Contracts;

/**
 * @since 1.2.0
 */
interface AdditionalCheckoutSuccessTemplateInterface
{
    /**
     * Some payments will show additional information on the success page.
     *
     * @return string
     *
     * @since 1.2.0
     */
    public function getCheckoutSuccessTemplate();
}
