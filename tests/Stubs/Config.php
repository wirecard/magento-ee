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
    private $resourceModelClassInstances = [];

    public function rewriteModelInstance($modalClass, $instance)
    {
        $this->modelClassInstances[$modalClass] = $instance;
    }

    public function rewriteResourceModelInstance($modalClass, $instance)
    {
        $this->resourceModelClassInstances[$modalClass] = $instance;
    }

    public function restore()
    {
        $this->modelClassInstances         = [];
        $this->resourceModelClassInstances = [];
    }

    public function getModelInstance($modelClass = '', $constructArguments = [])
    {
        if (isset($this->modelClassInstances[$modelClass])) {
            return $this->modelClassInstances[$modelClass];
        }
        return parent::getModelInstance($modelClass, $constructArguments);
    }

    public function getResourceModelInstance($modelClass = '', $constructArguments = [])
    {
        if (isset($this->resourceModelClassInstances[$modelClass])) {
            return $this->resourceModelClassInstances[$modelClass];
        }
        return parent::getResourceModelInstance($modelClass, $constructArguments);
    }
}
