<?php

$this->startSetup();

$this->run("ALTER TABLE {$this->getTable('byjuno')} (
  ADD COLUMN `orderid` varchar(250) AFTER `creation_date`;");
  
$this->endSetup();