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
 * @since 1.0.0
 */
interface CustomFormTemplateInterface
{
    /**
     * Some payments (e.g. SEPA) require custom form templates.
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function getFormTemplateName();
}
