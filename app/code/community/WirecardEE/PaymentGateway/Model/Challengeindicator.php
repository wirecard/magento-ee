<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

use Wirecard\PaymentSdk\Constant\ChallengeInd;

/**
 * @since 1.2.5
 */
class WirecardEE_PaymentGateway_Model_Challengeindicator
{
    /**
     * Return available challenge indicators
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => ChallengeInd::NO_PREFERENCE,
                'label' => Mage::helper('catalog')->__('config_challenge_indicator_no_preference'),
            ],
            [
                'value' => ChallengeInd::NO_CHALLENGE,
                'label' => Mage::helper('catalog')->__('config_challenge_indicator_no_challenge'),
            ],
            [
                'value' => ChallengeInd::CHALLENGE_THREED,
                'label' => Mage::helper('catalog')->__('config_challenge_indicator_threed'),
            ],
        ];
    }

}
