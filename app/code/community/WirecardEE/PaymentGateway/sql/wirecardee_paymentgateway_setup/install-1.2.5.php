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

$installer->run("
ALTER TABLE `{$installer->getTable('wirecard_elastic_engine_credit_card_vault')}` 
ADD COLUMN created_at timestamp NOT NULL default CURRENT_TIMESTAMP, 
ADD COLUMN updated_at timestamp NULL ON UPDATE CURRENT_TIMESTAMP
");

$installer->endSetup();
