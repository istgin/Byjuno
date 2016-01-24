<?php

$this->startSetup();

$setup = new Mage_Sales_Model_Mysql4_Setup('sales_setup');

$setup->addAttribute('order', 'byjuno_credit_rating', array('type' => Varien_Db_Ddl_Table::TYPE_TEXT));
$setup->addAttribute('order', 'byjuno_credit_level', array('type' => Varien_Db_Ddl_Table::TYPE_TEXT));

$this->endSetup();
