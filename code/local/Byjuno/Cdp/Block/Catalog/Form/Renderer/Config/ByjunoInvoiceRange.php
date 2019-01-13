<?php

class Byjuno_Cdp_Block_Catalog_Form_Renderer_Config_ByjunoInvoiceRange extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $elName = $element->getName();
        $b2b = '';
        if (strstr($elName, "b2b")) {
            $b2b = 'b2b';
        }
        $element->setStyle('display:block')
            ->setName($elName . '[]');


        $methodsAllowed["invoice_byjuno"] = array(
            'value' => "invoice_byjuno_enable",
        );
        $methodsAllowed["invoice_single"] = array(
            'value' => "invoice_single_enable",
        );

        $methodsName["invoice_byjuno"] = array(
            'value' => "byjuno_invoice_payments_invoice".$b2b,
            'url' => "byjuno_invoice_payments_invoice_url".$b2b
        );
        $methodsName["invoice_single"] = array(
            'value' => "byjuno_invoice_payments_single".$b2b,
            'url' => "byjuno_invoice_payments_single_url".$b2b
        );
        //invoice_byjuno_enable,invoice_single_enable,Byjuno invoice,Single invoice,http://www.byjuno.ch/&3,http://www.byjuno.ch/&4
        //var_dump($element->getValue());
        if ($element->getValue()) {
            $values = explode(',', $element->getValue());
        } else {
            $values = array();
        }
        $from = Array();
        $to = array();
        $totoc = array();

        $stringValues = Array();
        foreach($values as $val) {
            if (!strstr($val, "_enable")) {
                $stringValues[] = $val;
            }
        }

        $addInheritCheckbox = false;
        if ($element->getCanUseWebsiteValue()) {
            $addInheritCheckbox = true;
        }
        elseif ($element->getCanUseDefaultValue()) {
            $addInheritCheckbox = true;
        }
        $disabled = '';
        if ($addInheritCheckbox) {
            $inherit = $element->getInherit()==1 ? 'checked="checked"' : '';
            if ($inherit) {
                $disabled = ' disabled="disabled"';
            }
        }

        foreach($methodsAllowed as $m) {
            $checked = '';
            foreach($values as $val) {
                if ($val == $m['value']) {
                    $checked = 'checked="checked" ';
                }
            }
            $from[] = '<div style="margin: 3px 0 0 0; height: 19px"><input type="checkbox" name="'.$elName.'[]" '.$checked.'value="' . $m['value'] . '" '.$disabled.'></div>';
        }
        $i = 0;
        foreach($methodsName as $m) {

            $byjuno_invoice_plan = Mage::getStoreConfig('payment/cdp/'.$m["value"], Mage::getSingleton('adminhtml/config_data')->getStore());
            $byjuno_invoice_plan_url = Mage::getStoreConfig('payment/cdp/'.$m["url"].'', Mage::getSingleton('adminhtml/config_data')->getStore());

            $to[] = '<input style="width: 200px" type="text" name="groups[cdp][fields]['.$m["value"].'][value]" value="'.htmlspecialchars($byjuno_invoice_plan).'" '.$disabled.'>';
            $totoc[] = '<input style="width: 200px" type="text" name="groups[cdp][fields]['.$m["url"].'][value]" value="'.htmlspecialchars($byjuno_invoice_plan_url).'" '.$disabled.'>';
            $i++;
        }
        return '<div style="white-space: nowrap;">
            <div style="display:inline-block;padding: 0 5px 0 0; width:15px; vertical-align: top"><ul class="checkboxes"><li>&nbsp;</li><li>'.implode("</li><li>", $from).'</li></ul></div>
            <div style="display:inline-block;padding: 0 5px 0 0; width:45%; vertical-align: top"><ul class="checkboxes"><li><b>Payment plan name</b></li><li style="padding-top: 1px">'.implode("</li><li style=\"padding-top: 1px\">", $to).'</li></ul></div>
            <div style="display:inline-block;padding: 0 5px 0 0; width:45%; vertical-align: top"><ul class="checkboxes"><li><b>Payment plan T&C</b></li><li style="padding-top: 1px">'.implode("</li><li style=\"padding-top: 1px\">", $totoc).'</li></ul></div>
        </div>
        <input type="checkbox" name="'.$elName.'[]" checked="checked" value="empty" style="display:none">';
    }
}
