<?php

class Byjuno_Cdp_Block_Catalog_Form_Renderer_Config_ByjunoInstallmentRange extends Mage_Adminhtml_Block_System_Config_Form_Field
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

        $methodsAllowed["installment_3"] = array(
            'value' => "installment_3_enable",
        );
        $methodsAllowed["installment_10"] = array(
            'value' => "installment_10_enable",
        );
        $methodsAllowed["installment_12"] = array(
            'value' => "installment_12_enable",
        );
        $methodsAllowed["installment_24"] = array(
            'value' => "installment_24_enable",
        );
        $methodsAllowed["installment_4x12"] = array(
            'value' => "installment_4x12_enable",
        );
        $methodsAllowed["installment_4x10"] = array(
            'value' => "installment_4x10_enable",
        );

        $methodsName["installment_3"] = array(
            'value' => "byjuno_installment_payments_3".$b2b,
            'url' => "byjuno_installment_payments_3_url".$b2b
        );
        $methodsName["installment_10"] = array(
            'value' => "byjuno_installment_payments_10".$b2b,
            'url' => "byjuno_installment_payments_10_url".$b2b
        );
        $methodsName["installment_12"] = array(
            'value' => "byjuno_installment_payments_12".$b2b,
            'url' => "byjuno_installment_payments_12_url".$b2b
        );
        $methodsName["installment_24"] = array(
            'value' => "byjuno_installment_payments_24".$b2b,
            'url' => "byjuno_installment_payments_24_url".$b2b
        );
        $methodsName["installment_4x12"] = array(
            'value' => "byjuno_installment_payments_4x12".$b2b,
            'url' => "byjuno_installment_payments_4x12_url".$b2b
        );
        $methodsName["installment_4x10"] = array(
            'value' => "byjuno_installment_payments_4x10".$b2b,
            'url' => "byjuno_installment_payments_4x10_url".$b2b
        );
        //var_dump($element->getValue());
        //installment_4x12_enable,3 monthly installments,10 monthly installments,12 monthly installments,24 monthly installments,4 installments in 12 months,4 installments in 10 months,http://www.byjuno.ch/&5,http://www.byjuno.ch/&6,http://www.byjuno.ch/&7,http://www.byjuno.ch/&8,http://www.byjuno.ch/&9,http://www.byjuno.ch/&10

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

            $byjuno_installment_plan = Mage::getStoreConfig('payment/cdp/'.$m["value"], Mage::getSingleton('adminhtml/config_data')->getStore());
            $byjuno_installment_plan_url = Mage::getStoreConfig('payment/cdp/'.$m["url"].'', Mage::getSingleton('adminhtml/config_data')->getStore());

            $to[] = '<input style="width: 200px" type="text" name="groups[cdp][fields]['.$m["value"].'][value]" value="'.htmlspecialchars($byjuno_installment_plan).'" '.$disabled.'>';
            $totoc[] = '<input style="width: 200px" type="text" name="groups[cdp][fields]['.$m["url"].'][value]" value="'.htmlspecialchars($byjuno_installment_plan_url).'" '.$disabled.'>';

            $i++;
        }
        return '<div style="white-space: nowrap;">
            <div style="display:inline-block;padding: 0 5px 0 0; width:15px; vertical-align: top"><ul class="checkboxes"><li>&nbsp;</li><li>'.implode("</li><li>", $from).'</li></ul></div>
            <div style="display:inline-block;padding: 0 5px 0 0; width:45%; vertical-align: top"><ul class="checkboxes"><li><b>Payment plan name</b></li><li>'.implode("</li><li>", $to).'</li></ul></div>
            <div style="display:inline-block;padding: 0 5px 0 0; width:45%; vertical-align: top"><ul class="checkboxes"><li><b>Payment plan T&C</b></li><li>'.implode("</li><li>", $totoc).'</li></ul></div>
        </div>
        <input type="checkbox" name="'.$elName.'[]" checked="checked" value="empty" style="display:none">
        ';

    }
}
