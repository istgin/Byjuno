<?php

$installer = $this;
$installer->startSetup();

$installer->getConnection()

    ->addColumn($installer->getTable('byjuno'), 'orderid', array(
        'type'      => Varien_Db_Ddl_Table::TYPE_VARCHAR,
        'nullable'  => true,
        'length'    => 255,
        'after'     => null, // column name to insert new column after
        'comment'   => 'Order ID'
    ));
$installer->endSetup();
