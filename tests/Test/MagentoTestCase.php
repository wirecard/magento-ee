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
     */
    protected function loadFile($file, $path = 'app/code/community/WirecardEE/PaymentGateway/')
    {
        if (! file_exists($path . $file)) {
            throw new \RuntimeException("Unable to load file $file (in $path)");
        }

        /** @noinspection PhpIncludeInspection */
        require_once $path . $file;
    }
}
