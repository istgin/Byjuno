<?php

$this->startSetup();

$setup = new Mage_Sales_Model_Mysql4_Setup('sales_setup');

$setup->addAttribute('order', 'byjuno_status', array('type' => Varien_Db_Ddl_Table::TYPE_TEXT));

$this->run("DROP TABLE IF EXISTS {$this->getTable('byjuno')};");
$this->run("CREATE TABLE {$this->getTable('byjuno')} (
  `byjuno_id` int(10) unsigned NOT NULL auto_increment,
  `firstname` varchar(250) default NULL,
  `lastname` varchar(250) default NULL,
  `town` varchar(250) default NULL,
  `postcode` varchar(250) default NULL,
  `street1` varchar(250) default NULL,
  `country` varchar(250) default NULL,
  `ip` varchar(250) default NULL,
  `status` varchar(250) default NULL,
  `request_id` varchar(250) default NULL,
  `type` varchar(250) default NULL,
  `error` text default NULL,
  `response` text default NULL,
  `request` text default NULL,
  `creation_date` TIMESTAMP NULL DEFAULT now() ,
  PRIMARY KEY  (`byjuno_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;");


$this->endSetup();


