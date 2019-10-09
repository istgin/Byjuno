<?php

class Byjuno_Cdp_Block_Info_Byjunoinstallment extends Mage_Payment_Block_Info
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('payment/info/byjuno.phtml');
    }

    public function toPdf()
    {
        $paymentMehtodName = $this->getMethod()->getTitle();
        $info = $this->getInfo()->getAdditionalInformation("is_b2b");
        if ($info == "true") {
            $methodsAllowed["installment_3_enable"] = Array("byjuno_installment_payments_3b2b", "byjuno_installment_payments_3_urlb2b");
            $methodsAllowed["installment_10_enable"] = Array("byjuno_installment_payments_10b2b", "byjuno_installment_payments_10_urlb2b");
            $methodsAllowed["installment_12_enable"] = Array("byjuno_installment_payments_12b2b", "byjuno_installment_payments_12_urlb2b");
            $methodsAllowed["installment_24_enable"] = Array("byjuno_installment_payments_24b2b", "byjuno_installment_payments_24_urlb2b");
            $methodsAllowed["installment_4x12_enable"] = Array("byjuno_installment_payments_4x12b2b", "byjuno_installment_payments_4x12_urlb2b");
            $methodsAllowed["installment_4x10_enable"] = Array("byjuno_installment_payments_4x10b2b", "byjuno_installment_payments_4x10_urlb2b");
        } else {
            $methodsAllowed["installment_3_enable"] = Array("byjuno_installment_payments_3", "byjuno_installment_payments_3_url");
            $methodsAllowed["installment_10_enable"] = Array("byjuno_installment_payments_10", "byjuno_installment_payments_10_url");
            $methodsAllowed["installment_12_enable"] = Array("byjuno_installment_payments_12", "byjuno_installment_payments_12_url");
            $methodsAllowed["installment_24_enable"] = Array("byjuno_installment_payments_24", "byjuno_installment_payments_24_url");
            $methodsAllowed["installment_4x12_enable"] = Array("byjuno_installment_payments_4x12", "byjuno_installment_payments_4x12_url");
            $methodsAllowed["installment_4x10_enable"] = Array("byjuno_installment_payments_4x10", "byjuno_installment_payments_4x10_url");
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
        $out = '(B2C)';
        if ($info == 'true') {
            $out = '(B2B)';
        } else if ($info == "") {
            $out = '(-)';
        }
        return $paymentMehtodName."{{pdf_row_separator}}"
        .Mage::getStoreConfig('payment/cdp/'.$plId[0], Mage::app()->getStore()).' '.$out.'{{pdf_row_separator}}'
        .$htmlAdd;
    }

    public function getInstructions()
    {
        $info = $this->getInfo()->getAdditionalInformation("is_b2b");
        if ($info == "true") {
            $methodsAllowed["installment_3_enable"] = Array("byjuno_installment_payments_3b2b", "byjuno_installment_payments_3_urlb2b");
            $methodsAllowed["installment_10_enable"] = Array("byjuno_installment_payments_10b2b", "byjuno_installment_payments_10_urlb2b");
            $methodsAllowed["installment_12_enable"] = Array("byjuno_installment_payments_12b2b", "byjuno_installment_payments_12_urlb2b");
            $methodsAllowed["installment_24_enable"] = Array("byjuno_installment_payments_24b2b", "byjuno_installment_payments_24_urlb2b");
            $methodsAllowed["installment_4x12_enable"] = Array("byjuno_installment_payments_4x12b2b", "byjuno_installment_payments_4x12_urlb2b");
            $methodsAllowed["installment_4x10_enable"] = Array("byjuno_installment_payments_4x10b2b", "byjuno_installment_payments_4x10_urlb2b");
        } else {
            $methodsAllowed["installment_3_enable"] = Array("byjuno_installment_payments_3", "byjuno_installment_payments_3_url");
            $methodsAllowed["installment_10_enable"] = Array("byjuno_installment_payments_10", "byjuno_installment_payments_10_url");
            $methodsAllowed["installment_12_enable"] = Array("byjuno_installment_payments_12", "byjuno_installment_payments_12_url");
            $methodsAllowed["installment_24_enable"] = Array("byjuno_installment_payments_24", "byjuno_installment_payments_24_url");
            $methodsAllowed["installment_4x12_enable"] = Array("byjuno_installment_payments_4x12", "byjuno_installment_payments_4x12_url");
            $methodsAllowed["installment_4x10_enable"] = Array("byjuno_installment_payments_4x10", "byjuno_installment_payments_4x10_url");
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

        //return $stringValues[$plId] . ' '.$out.' - (<a href="'.$this->escapeHtml($stringValues[$plId + 6]).'" target="_blank">'.Mage::getStoreConfig('payment/cdp/byjuno_installment_toc_string', Mage::app()->getStore()).'</a>)'.$htmlAdd;
    }
}
