<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/magento-ee/blob/master/LICENSE
 */

/* @var $installer Mage_Customer_Model_Entity_Setup */
$installer = $this;
$installer->startSetup();

//$installer->run("
//ALTER TABLE `{$installer->getTable('sales/quote_payment')}`
//ADD `custom_field` VARCHAR( 255 ) NOT NULL;
//");

$installer->endSetup();
