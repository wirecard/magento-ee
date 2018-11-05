<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

use PHPUnit\Framework\TestCase;

class CredentialsButtonTest extends TestCase
{
    public function setUp()
    {
        require_once 'app/code/community/Wirecard/ElasticEngine/Block/Adminhtml/System/Config/CredentialsButton.php';
    }

    public function testTemplate()
    {
        /** @var Mage_Core_Model_Layout $layout */
        $layout = $this->createMock(Mage_Core_Model_Layout::class);

        $button = new Wirecard_ElasticEngine_Block_Adminhtml_System_Config_CredentialsButton();
        $button->setLayout($layout);

        $this->assertEquals('wirecard/system/config/credentials_button.phtml', $button->getTemplate());
    }
}
