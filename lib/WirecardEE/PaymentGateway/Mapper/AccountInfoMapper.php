<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

namespace WirecardEE\PaymentGateway\Mapper;

use DateTime;
use Exception;
use Mage_Customer_Model_Customer;
use Mage_Customer_Model_Session;
use Wirecard\PaymentSdk\Constant\AuthMethod;
use Wirecard\PaymentSdk\Constant\ChallengeInd;
use Wirecard\PaymentSdk\Entity\AccountInfo;

/**
 * @since 1.2.5
 */
class AccountInfoMapper
{
    /**
     * @var int|null
     */
    protected $customerId;

    /**
     * unix timestamp of last auth, must be enabled under Adanced/System/Log
     *
     * @var int|null
     */
    protected $lastAuth;

    /**
     * the configured challenge indicator
     *
     * @var string
     */
    protected $challengeIndicator;

    /**
     * @var Mage_Customer_Model_Session
     */
    protected $customerSession;

    /**
     * @var DateTime|null
     */
    protected $shippingFirstUsed;

    /**
     * @var DateTime|null
     */
    protected $cardCreationDate;

    /**
     * @var int
     */
    protected $numPurchasesSixMonth;

    /**
     * @var bool
     */
    protected $isNewToken;

    public function __construct(
        Mage_Customer_Model_Session $customerSession,
        $lastAuth,
        $challengeIndicator,
        $isNewToken,
        $shippingFirstUsed,
        $cardCreationDate,
        $numPurchasesSixMonth
    ) {
        $this->customerSession      = $customerSession;
        $this->lastAuth             = $lastAuth;
        $this->challengeIndicator   = $challengeIndicator;
        $this->isNewToken           = $isNewToken;
        $this->shippingFirstUsed    = $shippingFirstUsed;
        $this->cardCreationDate     = $cardCreationDate;
        $this->numPurchasesSixMonth = $numPurchasesSixMonth;
    }

    public function getCustomerId()
    {
        return $this->customerSession->getCustomerId();
    }

    /**
     * @return Mage_Customer_Model_Customer
     */
    public function getCustomer()
    {
        return $this->customerSession->getCustomer();
    }

    /**
     * needed for testing only
     *
     * @param $isNew
     */
    public function setIsNewToken($isNew)
    {
        $this->isNewToken = $isNew;
    }

    /**
     * @param $tokenId
     *
     * @return AccountInfo
     * @throws Exception
     */
    public function getAccountInfo($tokenId)
    {
        $accountInfo = new AccountInfo();
        $authMethod  = $this->customerSession->isLoggedIn() ? AuthMethod::USER_CHECKOUT : AuthMethod::GUEST_CHECKOUT;
        $accountInfo->setAuthMethod($authMethod);
        $accountInfo->setAuthTimestamp($this->lastAuth);
        $accountInfo->setChallengeInd($this->getChallengeIndicator($tokenId));
        $this->addAuthenticatedUserData($accountInfo);

        return $accountInfo;
    }


    /**
     * Get challenge indicator depending on existing token
     * - return config setting: for non one-click-checkout, guest checkout, existing token
     * - return 04/CHALLENGE_MANDATE: for new one-click-checkout token
     * the tokenId is set to 'wirecardee--new-card-save' for one-click-checkout using a new card this is checked in
     * the threeds helper isNewToken.
     * the tokenId is set to 'wirecardee--new-card' for a non-one-click-checkout
     *
     * @see WirecardEE_PaymentGateway_Helper_Threeds::isNewToken()
     * @param $tokenId
     *
     * @return string
     */
    protected function getChallengeIndicator($tokenId)
    {
        // non one-click-checkout
        if ($tokenId === null || $tokenId === 'wirecardee--new-card') {
            return $this->challengeIndicator;
        }

        // guest
        if (!$this->customerSession->isLoggedIn()) {
            return $this->challengeIndicator;
        }

        // new token
        if ($this->isNewToken) {
            return ChallengeInd::CHALLENGE_MANDATE;
        }

        // existing token
        return $this->challengeIndicator;
    }

    /**
     * @param AccountInfo $accountInfo
     *
     * @return $this
     * @throws Exception
     */
    protected function addAuthenticatedUserData(AccountInfo $accountInfo)
    {

        if ($this->customerSession->isLoggedIn()) {
            if ($this->getCustomer()->getCreatedAtTimestamp() !== null) {
                $dt = new DateTime('@' . $this->getCustomer()->getCreatedAtTimestamp());
                $accountInfo->setCreationDate($dt);
            }

            $dt = new DateTime($this->getCustomer()->getUpdatedAt());
            $accountInfo->setUpdateDate($dt);
            $accountInfo->setShippingAddressFirstUse($this->shippingFirstUsed);
            $accountInfo->setCardCreationDate($this->cardCreationDate === null ?
                new DateTime() : $this->cardCreationDate);
            $accountInfo->setAmountPurchasesLastSixMonths($this->numPurchasesSixMonth);
        }

        return $this;
    }
}
