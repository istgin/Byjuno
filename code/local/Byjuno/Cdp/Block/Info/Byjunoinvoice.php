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


        $info = $this->getInfo()->getAdditionalInformation("is_b2b");
        if ($info == "true") {
            $methodsAllowed["invoice_byjuno_enable"] = Array("byjuno_invoice_payments_invoiceb2b", "byjuno_invoice_payments_invoice_urlb2b");
            $methodsAllowed["invoice_single_enable"] = Array("byjuno_invoice_payments_singleb2b", "byjuno_invoice_payments_single_urlb2b");
        } else {
            $methodsAllowed["invoice_byjuno_enable"] = Array("byjuno_invoice_payments_invoice", "byjuno_invoice_payments_invoice_url");
            $methodsAllowed["invoice_single_enable"] = Array("byjuno_invoice_payments_single", "byjuno_invoice_payments_single_url");
        }

        $plId = $methodsAllowed[$this->getInfo()->getAdditionalInformation("payment_plan")];
        $paymentSend = $this->getInfo()->getAdditionalInformation("payment_send");
        $htmlAdd = '';
        if ($paymentSend == 'email')
        {
            $htmlAdd = '<br>'.Mage::getStoreConfig('payment/cdp/byjuno_email_text_admin', Mage::app()->getStore());
        }
        else if ($paymentSend == 'postal')
        {
            $htmlAdd = '<br>'.Mage::getStoreConfig('payment/cdp/byjuno_postal_text_admin', Mage::app()->getStore());
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
        $out = '(B2C)';
        if ($info == 'true') {
            $out = '(B2B)';
        } else if ($info == "") {
            $out = '(-)';
        }
        return Mage::getStoreConfig('payment/cdp/'.$plId[0], Mage::app()->getStore()) .
        ' '.$out.' - (<a href="'.
        $this->escapeHtml(Mage::getStoreConfig('payment/cdp/'.$plId[1], Mage::app()->getStore())).
        '" target="_blank">'.
        Mage::getStoreConfig('payment/cdp/byjuno_invoice_toc_string', Mage::app()->getStore())
        .'</a>)'
        .$htmlAdd;
    }
}
