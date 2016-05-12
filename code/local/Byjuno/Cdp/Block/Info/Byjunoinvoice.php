<?php

class Byjuno_Cdp_Block_Info_Byjunoinvoice extends Mage_Payment_Block_Info
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('payment/info/byjuno.phtml');
    }

    public function getInstructions()
    {

        $methodsAllowed["invoice_byjuno_enable"] = 0;
        $methodsAllowed["invoice_single_enable"] = 1;

        $pl = explode(",", Mage::getStoreConfig('payment/cdp/byjuno_invoice_payments', Mage::app()->getStore()));

        $plId = $methodsAllowed[$this->getInfo()->getAdditionalInformation("payment_plan")];
        $paymentSend = $this->getInfo()->getAdditionalInformation("payment_send");
        $htmlAdd = '';
        if ($paymentSend == 'email')
        {
            $htmlAdd = '<br>'. Mage::getStoreConfig('payment/cdp/byjuno_invoice_email_text', Mage::app()->getStore());
        }
        else if ($paymentSend == 'postal')
        {
            $htmlAdd = '<br>'. Mage::getStoreConfig('payment/cdp/byjuno_invoice_postal_text', Mage::app()->getStore());
        }
        $i = 0;
        $stringValues = Array();
        foreach($pl as $val) {
            if (!strstr($val, "_enable")) {
                $stringValues[$i] = $val;
                $i++;
            }
        }
        return $stringValues[$plId] . ' - (<a href="'.$this->escapeHtml($stringValues[$plId + 2]).'" target="_blank">'.Mage::getStoreConfig('payment/cdp/byjuno_invoice_toc_string', Mage::app()->getStore()).'</a>)'.$htmlAdd;
    }
}
