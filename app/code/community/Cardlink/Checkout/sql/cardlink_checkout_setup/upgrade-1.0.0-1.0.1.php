<?php

/**
 * Database migration script to create the required card token storage table and add support columns to order/quote payment tables.
 * 
 * @author Cardlink S.A.
 */

$installer = $this;
$installer->startSetup();

$installer->run("ALTER TABLE `{$installer->getTable('sales/quote_payment')}` ADD `cardlink_tokenize` SMALLINT( 1 ) NOT NULL;");
$installer->run("ALTER TABLE `{$installer->getTable('sales/quote_payment')}` ADD `cardlink_stored_token` INT( 10 ) NOT NULL;");
$installer->run("ALTER TABLE `{$installer->getTable('sales/quote_payment')}` ADD `cardlink_installments` SMALLINT( 5 ) NOT NULL;");

$installer->run("ALTER TABLE `{$installer->getTable('sales/order_payment')}` ADD `cardlink_tokenize` SMALLINT( 1 ) NOT NULL;");
$installer->run("ALTER TABLE `{$installer->getTable('sales/order_payment')}` ADD `cardlink_stored_token` INT( 10 ) NOT NULL;");
$installer->run("ALTER TABLE `{$installer->getTable('sales/order_payment')}` ADD `cardlink_installments` SMALLINT( 5 ) NOT NULL;");

$installer->run("ALTER TABLE `{$installer->getTable('sales/order_payment')}` ADD `cardlink_pay_method` VARCHAR( 20 );");
$installer->run("ALTER TABLE `{$installer->getTable('sales/order_payment')}` ADD `cardlink_pay_status` VARCHAR( 16 );");
$installer->run("ALTER TABLE `{$installer->getTable('sales/order_payment')}` ADD `cardlink_tx_id` VARCHAR( 20 );");
$installer->run("ALTER TABLE `{$installer->getTable('sales/order_payment')}` ADD `cardlink_pay_ref` VARCHAR( 64 );");

$installer->endSetup();
