<?php

class Byjuno_Cdp_Model_Observer extends Mage_Core_Model_Abstract {

    protected $quote   = null;
    protected $address = null;
    protected $byjuno_status = null;
    protected $credit_limit = 'credit_limit';
    protected $credit_balance = 'credit_balance';
    protected $credit_byjuno_balance = 'credit_byjuno_balance';
    protected $overwrite_credit_check = 'credit_check';

    public function orderStatusChange(Varien_Event_Observer $observer)
    {
        $order = $observer->getOrder();
        $payment = $order->getPayment();
        $methodInstance = $payment->getMethodInstance();
        if (!($methodInstance instanceof Byjuno_Cdp_Model_Standardinvoice) && !($methodInstance instanceof Byjuno_Cdp_Model_Standardinstallment)) {
            return;
        }
    }

    public function checkandcall(Varien_Event_Observer $observer){
        $methodInstance = $observer->getEvent()->getMethodInstance();
        if (!($methodInstance instanceof Byjuno_Cdp_Model_Standardinvoice) || !($methodInstance instanceof Byjuno_Cdp_Model_Standardinstallment)) {
            return;
        }
        return;
    }

    protected function getQuote(){

        if($this->quote){
            return $this->quote;
        }
        throw new Exception('quote not set');
    }

}
