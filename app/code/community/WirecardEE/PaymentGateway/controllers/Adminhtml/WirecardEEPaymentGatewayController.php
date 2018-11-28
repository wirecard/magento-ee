<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

use Wirecard\PaymentSdk\Config\Config;
use Wirecard\PaymentSdk\TransactionService;

/**
 * @since 1.0.0
 * @codingStandardsIgnoreStart
 */
class WirecardEE_PaymentGateway_Adminhtml_WirecardEEPaymentGatewayController extends Mage_Adminhtml_Controller_Action
{
    // @codingStandardsIgnoreEnd

    /**
     * Check Credentials against Wirecard server.
     *
     * @return Zend_Controller_Response_Abstract
     * @throws \Http\Client\Exception
     *
     * @since 1.0.0
     */
    public function testCredentialsAction()
    {
        $params = $this->getRequest()->getParams();
        $prefix = 'wirecardElasticEngine';

        if (empty($params[$prefix . 'Server'])
            || empty($params[$prefix . 'HttpUser'])
            || empty($params[$prefix . 'HttpPassword'])
        ) {
            throw new RuntimeException(
                'Missing credentials. Please check Server, HttpUser and HttpPassword.'
            );
        }

        /** @var \Mage_Core_Helper_Data $coreHelper */
        $coreHelper = Mage::helper('core');

        try {
            $testConfig = new Config(
                $params[$prefix . 'Server'],
                $params[$prefix . 'HttpUser'],
                $params[$prefix . 'HttpPassword']
            );

            $transactionService = new TransactionService($testConfig);

            $success = $transactionService->checkCredentials();
        } catch (\Exception $e) {
            return $this->getResponse()->setBody(
                $coreHelper->jsonEncode([
                    'status' => 'failed',
                    'msg'    => $e->getMessage(),
                ])
            );
        }

        return $this->getResponse()->setBody(
            $coreHelper->jsonEncode([
                'status' => $success ? 'success' : 'failed',
            ])
        );
    }
}
