<?php

/**
 * Created by PhpStorm.
 * User: Igor
 * Date: 15.02.2017
 * Time: 15:54
 */
class Byjuno_Cdp_Model_Source_Riskstate
{
    // set null to enable all possible
    protected $_stateStatuses = array(
        Mage_Sales_Model_Order::STATE_NEW,
        Mage_Sales_Model_Order::STATE_PENDING_PAYMENT,
        Mage_Sales_Model_Order::STATE_PROCESSING,
        Mage_Sales_Model_Order::STATE_COMPLETE,
        Mage_Sales_Model_Order::STATE_CLOSED,
        Mage_Sales_Model_Order::STATE_CANCELED,
        Mage_Sales_Model_Order::STATE_HOLDED
    );

    public function toOptionArray()
    {
        $options = array();
        $options[] = array(
            'value' => 'standard',
            'label' => 'Standard'
        );
        $options[] = array(
            'value' => 'custom',
            'label' => 'Custom'
        );
        return $options;
    }
}