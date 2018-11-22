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
 * Returned by Handlers if a redirect is required.
 *
 * @since 1.0.0
 */
class RedirectAction implements Action
{
    /**
     * @var string
     */
    protected $url;

    /**
     * @param string $url
     *
     * @since 1.0.0
     */
    public function __construct($url)
    {
        $this->url = $url;
    }

    /**
     * @return string
     *
     * @since 1.0.0
     */
    public function getUrl()
    {
        return $this->url;
    }
}
