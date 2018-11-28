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

abstract class MagentoTestCase extends TestCase
{
    /**
     * Responsible for loading a file from Magento.
     *
     * @param string $file
     * @param string $path
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
}
