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

        $info = $this->getInfo()->getAdditionalInformation("is_b2b");
        if ($info == "true") {
            $pl = explode(",", Mage::getStoreConfig('payment/cdp/byjuno_invoice_paymentsb2b', Mage::app()->getStore()));
        } else {
            $pl = explode(",", Mage::getStoreConfig('payment/cdp/byjuno_invoice_payments', Mage::app()->getStore()));
        }

        $plId = $methodsAllowed[$this->getInfo()->getAdditionalInformation("payment_plan")];
        $paymentSend = $this->getInfo()->getAdditionalInformation("payment_send");
        $htmlAdd = '';
        if ($paymentSend == 'email')
        {
            $htmlAdd = '<br>Rechnungsversand via E-Mail (ohne Gebühr).';
        }
        else if ($paymentSend == 'postal')
        {
            $htmlAdd = '<br>Rechnungsversand in Papierform via Post (gegen Gebühr von CHF 3.50 pro Rate).';
        }
        if ($this->getInfo()->getAdditionalInformation("gender_custom") != "") {
            $gendername = "";
            if ($this->getInfo()->getAdditionalInformation("gender_custom") == '1') {
                $gendername = $this->__("Male");
            } else if ($this->getInfo()->getAdditionalInformation("gender_custom") == '2') {
                $gendername = $this->__("Female");
            }
            $htmlAdd .= '<br>'.$this->__("Gender").": ".$gendername;
        }
        if ($this->getInfo()->getAdditionalInformation("dob_custom") != "") {
            $htmlAdd .= '<br>'.$this->__("Date of birth").": ".$this->getInfo()->getAdditionalInformation("dob_custom");
        }
        $i = 0;
        $stringValues = Array();
        foreach($pl as $val) {
            if (!strstr($val, "_enable")) {
                $stringValues[$i] = $val;
                $i++;
            }
        }
        $out = '(B2C)';
        if ($info == 'true') {
            $out = '(B2B)';
        } else if ($info == "") {
            $out = '(-)';
        }
        return $stringValues[$plId] . ' '.$out.' - (<a href="'.$this->escapeHtml($stringValues[$plId + 2]).'" target="_blank">'.Mage::getStoreConfig('payment/cdp/byjuno_invoice_toc_string', Mage::app()->getStore()).'</a>)'.$htmlAdd;
    }
}
