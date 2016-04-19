<?php

class Byjuno_Cdp_Model_Standardinstallment
    extends Byjuno_Cdp_Model_Standardinvoice {

    public $_code = "cdp_installment";
    public function getTitle()
    {
        return  Mage::getStoreConfig('payment/cdp/title_installment', Mage::app()->getStore());
    }

}

?>