<?php

$installer = $this;
$installer->startSetup();

$installer->getConnection()

    ->addColumn($installer->getTable('byjuno'), 'request_start', array(
        'type'      => Varien_Db_Ddl_Table::TYPE_TIMESTAMP,
        'nullable'  => true,
        'length'    => 255,
        'after'     => null, // column name to insert new column after
        'comment'   => 'Request start'
    ));
$installer->getConnection()
    ->addColumn($installer->getTable('byjuno'), 'request_end', array(
        'type'      => Varien_Db_Ddl_Table::TYPE_TIMESTAMP,
        'nullable'  => true,
        'length'    => 255,
        'after'     => null, // column name to insert new column after
        'comment'   => 'Request end'
    ));
$installer->endSetup();
