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
use WirecardEE\PaymentGateway\Mail\SupportMail;
use WirecardEE\PaymentGateway\Service\PaymentFactory;

/**
 * Admin controller to handle backend actions.
 *
 * @since 1.0.0
 */
class WirecardEE_PaymentGateway_Adminhtml_WirecardEEPaymentGatewayController extends Mage_Adminhtml_Controller_Action
{
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

    /**
     * Render general information admin page
     *
     * @since 1.0.0
     */
    public function infoAction()
    {
        $this->loadLayout();
        $this->_setActiveMenu('WirecardEE_PaymentGateway/info');
        $this->_title($this->__('General Information regarding Wirecard Shop Plugins'));

        /** @var Mage_Core_Block_Template $block */
        $block = $this->getLayout()->createBlock('adminhtml/template');
        $block->setTemplate('WirecardEE/info.phtml');
        $this->_addContent($block);
        $this->renderLayout();
    }

    /**
     * Render support mail form on support admin page
     *
     * @since 1.0.0
     */
    public function supportMailAction()
    {
        $this->loadLayout();
        $this->_setActiveMenu('WirecardEE_PaymentGateway/support');
        $this->_title($this->__('Wirecard Support'));

        $this->_addContent($this->getLayout()->createBlock('paymentgateway/adminhtml_supportMail'));
        $this->renderLayout();
    }

    /**
     * Send support mail
     *
     * @since 1.0.0
     */
    public function sendAction()
    {
        $data = $this->getRequest()->getPost();

        /** @var Mage_Core_Model_Session $session */
        $session = Mage::getSingleton('core/session');

        $mail = new SupportMail(new PaymentFactory());
        try {
            $mail->create($data['sender_address'], $data['content'], $data['reply_to'])
                 ->send();
            $session->addSuccess($this->__('E-mail sent successfully'));
            $this->_redirect('');
        } catch (Exception $e) {
            $session->addError($this->__('E-mail delivery error: ' . $e->getMessage()));
            $this->_redirect('');
        }
    }
}
