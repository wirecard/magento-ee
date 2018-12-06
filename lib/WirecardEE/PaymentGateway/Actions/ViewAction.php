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
 * Returned by Handlers to extend the current view (or a specific template) with given variables.
 *
 * @package WirecardElasticEngine\Components\Actions
 *
 * @since   1.0.0
 */
class ViewAction implements Action
{
    /**
     * @var array
     */
    protected $assignments;

    /**
     * @var string
     */
    protected $blockName;

    /**
     * @param string $blockName   Template path
     * @param array  $assignments View variables which are assigned to the view.
     *
     * @since 1.0.0
     */
    public function __construct($blockName, array $assignments = [])
    {
        $this->blockName   = $blockName;
        $this->assignments = $assignments;
    }

    /**
     * @return array
     *
     * @since 1.0.0
     */
    public function getAssignments()
    {
        return $this->assignments;
    }

    /**
     * @return string|null
     *
     * @since 1.0.0
     */
    public function getBlockName()
    {
        return $this->blockName;
    }
}
