<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

namespace WirecardEE\Tests\Unit\Controller\Adminhtml;

use WirecardEE\Tests\Test\MagentoTestCase;

class WirecardEEPaymentGatewayAdminhtmlControllerTest extends MagentoTestCase
{
    public function setUp()
    {
        $this->requireFile('controllers/Adminhtml/WirecardEEPaymentGatewayController.php');
    }

    public function testCredentialsAction()
    {
        /** @var \Zend_Controller_Request_Abstract $request */
        $request = $this->getMockForAbstractClass(\Zend_Controller_Request_Abstract::class);
        /** @var \Zend_Controller_Response_Abstract $response */
        $response = $this->getMockForAbstractClass(\Zend_Controller_Response_Abstract::class);

        $request->setParams([
            'wirecardElasticEngineServer'       => getenv('API_TEST_URL'),
            'wirecardElasticEngineHttpUser'     => getenv('API_HTTP_USER'),
            'wirecardElasticEngineHttpPassword' => getenv('API_HTTP_PASSWORD'),
        ]);

        $controller = new \WirecardEE_PaymentGateway_Adminhtml_WirecardEEPaymentGatewayController(
            $request,
            $response
        );

        $response = $controller->testCredentialsAction();

        $this->assertEquals(200, $response->getHttpResponseCode());
        $body = $response->getBody('default');
        $data = json_decode($body, true);
        $this->assertEquals([
            'status' => 'success',
        ], $data);
    }
}
