<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

namespace WirecardEE\Tests\Stubs;

class Config extends \Mage_Core_Model_Config
{
    private $modelClassInstances = [];

    public function rewriteModelInstance($modalClass, $instance)
    {
        $this->modelClassInstances[$modalClass] = $instance;
    }

    public function restore()
    {
        $this->modelClassInstances = [];
    }

    public function getModelInstance($modelClass = '', $constructArguments = [])
    {
        if (isset($this->modelClassInstances[$modelClass])) {
            return $this->modelClassInstances[$modelClass];
        }
        return parent::getModelInstance($modelClass, $constructArguments);
    }
}
