<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

use PHPUnit\Framework\TestCase;

class WirecardElasticEngineControllerTest extends TestCase
{
    public function setUp()
    {
        require_once 'app/code/community/Wirecard/ElasticEngine/controllers/Adminhtml/WirecardElasticEngineController.php';
    }

    public function testCredentialsAction()
    {
        /** @var Zend_Controller_Request_Abstract $request */
        $request = $this->getMockForAbstractClass(Zend_Controller_Request_Abstract::class);
        /** @var Zend_Controller_Response_Abstract $response */
        $response = $this->getMockForAbstractClass(Zend_Controller_Response_Abstract::class);

        $request->setParams([
            'wirecardElasticEngineServer' => 'https://api-test.wirecard.com',
            'wirecardElasticEngineHttpUser' => '70000-APITEST-AP',
            'wirecardElasticEngineHttpPassword' => 'qD2wzQ_hrc!8',
        ]);

        $controller = new Wirecard_ElasticEngine_Adminhtml_WirecardElasticEngineController(
            $request,
            $response
        );

        $response = $controller->testCredentialsAction();

        $this->assertEquals(200, $response->getHttpResponseCode());
        $body = $response->getBody('default');
        $data = json_decode($body, true);
        $this->assertEquals([
            'status' => 'success'
        ], $data);
    }
}
