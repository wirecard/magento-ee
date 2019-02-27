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
CREATE TABLE IF NOT EXISTS `{$installer->getTable('wirecard_elastic_engine_credit_card_vault')}` (
    `id` int unsigned NOT NULL auto_increment,
    `customer_id` int unsigned NOT NULL,
    `token` varchar(255) NOT NULL,
    `masked_account_number` varchar(255) NOT NULL,
    `last_used` datetime NOT NULL,
    `billing_address` longtext NOT NULL,
    `billing_address_hash` varchar(255) NOT NULL,
    `shipping_address` longtext NOT NULL,
    `shipping_address_hash` varchar(255) NOT NULL,
    `additional_data` longtext,
    `expiration_date` date,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");

$installer->endSetup();
