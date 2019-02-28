<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

namespace WirecardEE\Tests\Test;

use PHPUnit\Framework\TestCase;
use WirecardEE\Tests\Test\Stubs\Config;

abstract class MagentoTestCase extends TestCase
{
    private $registry = [];

    /**
     * Restore Mage state after each test
     */
    protected function tearDown()
    {
        $this->restoreMage();
    }

    /**
     * Responsible for loading a file from Magento.
     *
     * @param string $file
     * @param string $path
     *
     * @return mixed
     */
    protected function requireFile($file, $path = 'app/code/community/WirecardEE/PaymentGateway/')
    {
        $filename = BP . DS . $path . $file;
        if (! file_exists($filename)) {
            throw new \RuntimeException("Unable to load file $filename");
        }

        /** @noinspection PhpIncludeInspection */
        return require_once $filename;
    }

    protected function replaceMageModel($modelClass, $className)
    {
        /** @var Config $config */
        $config = \Mage::getConfig();
        $config->rewriteModelInstance($modelClass, $className);
    }

    protected function replaceMageResourceModel($modelClass, $className)
    {
        /** @var Config $config */
        $config = \Mage::getConfig();
        $config->rewriteResourceModelInstance($modelClass, $className);
    }

    /**
     * @param string $modelClass
     * @param mixed  $instance
     *
     * @return mixed
     * @throws \Mage_Core_Exception
     */
    protected function replaceMageSingleton($modelClass, $instance)
    {
        return $this->replaceMageRegistry('_singleton/' . $modelClass, $instance);
    }

    /**
     * @param string                     $name
     * @param \Mage_Core_Helper_Abstract $instance
     *
     * @return \Mage_Core_Helper_Abstract
     * @throws \Mage_Core_Exception
     */
    protected function replaceMageHelper($name, $instance)
    {
        return $this->replaceMageRegistry('_helper/' . $name, $instance);
    }

    /**
     * @param string $key
     * @param mixed  $value
     *
     * @return mixed
     * @throws \Mage_Core_Exception
     */
    protected function replaceMageRegistry($key, $value)
    {
        $this->registry[$key] = \Mage::registry($key);
        \Mage::unregister($key);
        \Mage::register($key, $value);
        return $this->registry[$key];
    }

    /**
     * Restore replaced Mage registry and config model mappings
     */
    protected function restoreMage()
    {
        foreach ($this->registry as $key => $value) {
            \Mage::unregister($key);
            \Mage::register($key, $value);
        }
        $this->registry = [];

        /** @var Config $config */
        $config = \Mage::getConfig();
        $config->restore();
    }

    /**
     * @param string $path
     * @param string $value
     */
    protected function setMageConfig($path, $value)
    {
        \Mage::getConfig()->saveConfig($path, $value);
        \Mage::getConfig()->cleanCache();
    }
}
