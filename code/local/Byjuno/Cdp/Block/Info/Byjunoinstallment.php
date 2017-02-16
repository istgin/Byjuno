<?php

class Byjuno_Cdp_Block_Info_Byjunoinstallment extends Mage_Payment_Block_Info
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('payment/info/byjuno.phtml');
    }

    public function getInstructions()
    {
        $methodsAllowed["installment_3_enable"] = 0;
        $methodsAllowed["installment_10_enable"] = 1;
        $methodsAllowed["installment_12_enable"] = 2;
        $methodsAllowed["installment_24_enable"] = 3;
        $methodsAllowed["installment_4x12_enable"] = 4;
        $methodsAllowed["installment_4x10_enable"] = 5;

        $pl = explode(",", Mage::getStoreConfig('payment/cdp/byjuno_installment_payments', Mage::app()->getStore()));

        $plId = $methodsAllowed[$this->getInfo()->getAdditionalInformation("payment_plan")];
        $paymentSend = $this->getInfo()->getAdditionalInformation("payment_send");
        $paymentRiskOwner = $this->getInfo()->getAdditionalInformation("payment_riskowner");
        $htmlAdd = '';
        if ($paymentSend == 'email')
        {
            $htmlAdd = '<br>'. Mage::getStoreConfig('payment/cdp/byjuno_installment_email_text', Mage::app()->getStore()).': '.$this->getInfo()->getAdditionalInformation("payment_send_to");
        }
        else if ($paymentSend == 'postal')
        {
            $htmlAdd = '<br>'. Mage::getStoreConfig('payment/cdp/byjuno_installment_postal_text', Mage::app()->getStore()).': '.$this->getInfo()->getAdditionalInformation("payment_send_to");
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
        if ($paymentRiskOwner == null || $paymentRiskOwner == "") {
            $paymentRiskOwner = "Check actual transaction RISKOWNER tag";
        }
        $htmlAdd .= '<br>'.$this->__("Risk owner").": ".$paymentRiskOwner;

        $i = 0;
        $stringValues = Array();
        foreach($pl as $val) {
            if (!strstr($val, "_enable")) {
                $stringValues[$i] = $val;
                $i++;
            }
        }
        return $stringValues[$plId] . ' - (<a href="'.$this->escapeHtml($stringValues[$plId + 6]).'" target="_blank">'.Mage::getStoreConfig('payment/cdp/byjuno_installment_toc_string', Mage::app()->getStore()).'</a>)'.$htmlAdd;
    }
}
