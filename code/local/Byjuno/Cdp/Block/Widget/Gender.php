<?php
class Byjuno_Cdp_Block_Widget_Gender extends Mage_Customer_Block_Widget_Gender
{
    /**
     * Initialize block
     */
    public function _construct()
    {
        parent::_construct();
        $this->setTemplate('customer/widget/gender.phtml');
    }

    /**
     * Check if gender attribute enabled in system
     *
     * @return bool
     */
    public function isEnabled()
    {
        return true;
    }

    /**
     * Check if gender attribute marked as required
     *
     * @return bool
     */
    public function isRequired()
    {
        return true;
    }

    /**
     * Get current customer from session
     *
     * @return Mage_Customer_Model_Customer
     */
    public function getCustomer()
    {
        return Mage::getSingleton('customer/session')->getCustomer();
    }
}
