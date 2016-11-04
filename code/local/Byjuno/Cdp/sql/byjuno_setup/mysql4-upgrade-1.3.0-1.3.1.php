<?php
$this->startSetup();

$statusTable        = $this->getTable('sales/order_status');
$statusStateTable   = $this->getTable('sales/order_status_state');
$statusLabelTable   = $this->getTable('sales/order_status_label');

$data = array(
    array('status' => 'byjuno_pending', 'label' => 'Byjuno pending')
);
$this->getConnection()->insertArray($statusTable, array('status', 'label'), $data);

$data = array(
    array('status' => 'byjuno_pending', 'state' => 'byjuno_pending', 'is_default' => 1)
);
$this->getConnection()->insertArray($statusStateTable, array('status', 'state', 'is_default'), $data);
$this->endSetup();