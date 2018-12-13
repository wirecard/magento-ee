<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

namespace WirecardEE\PaymentGateway\Actions;

/**
 * @since 1.0.0
 */
class SuccessAction implements Action
{
    /**
     * @var string
     */
    protected $context;

    /**
     * @param array|null $context
     *
     * @since 1.0.0
     */
    public function __construct(array $context = null)
    {
        $this->context = $context;
    }

    /**
     * @return string
     *
     * @since 1.0.0
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @param string      $key
     * @param string|null $default
     *
     * @return mixed|null
     */
    public function getContextItem($key, $default = null)
    {
        if (empty($this->context[$key])) {
            return $default;
        }

        return $this->context[$key];
    }
}
