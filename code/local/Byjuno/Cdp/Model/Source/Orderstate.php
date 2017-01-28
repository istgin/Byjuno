<?php


class Byjuno_Cdp_Model_Source_Orderstate
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
        $statuses = Mage::getSingleton('sales/order_config')->getStateStatuses($this->_stateStatuses);
        $options = array();
        foreach ($statuses as $code=>$label) {
            $options[] = array(
                'value' => $code,
                'label' => $label
            );
        }
        return $options;
    }
}
