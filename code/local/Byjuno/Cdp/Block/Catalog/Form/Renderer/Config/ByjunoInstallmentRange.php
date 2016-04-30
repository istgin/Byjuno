<?php

class Byjuno_Cdp_Block_Catalog_Form_Renderer_Config_ByjunoInstallmentRange extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $element->setStyle('display:block')
            ->setName($element->getName() . '[]');

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
            'label'   => '3 monthly installments',
            'value' => "installment_3",
        );
        $methodsName["installment_10"] = array(
            'label'   => '10 monthly installments',
            'value' => "installment_10",
        );
        $methodsName["installment_12"] = array(
            'label'   => '12 monthly installments',
            'value' => "installment_12",
        );
        $methodsName["installment_24"] = array(
            'label'   => '24 monthly installments',
            'value' => "installment_24",
        );
        $methodsName["installment_4x12"] = array(
            'label'   => '4 installments in 12 months',
            'value' => "installment_4x12",
        );
        $methodsName["installment_4x10"] = array(
            'label'   => '4 installments in 10 months',
            'value' => "installment_4x10",
        );
        if ($element->getValue()) {
            $values = explode(',', $element->getValue());
        } else {
            $values = array();
        }
        $from = Array();
        $to = array();

        $stringValues = Array();
        foreach($values as $val) {
            if (!strstr($val, "_enable")) {
                $stringValues[] = $val;
            }
        }

        foreach($methodsAllowed as $m) {
            $checked = '';
            foreach($values as $val) {
                if ($val == $m['value']) {
                    $checked = 'checked="checked" ';
                }
            }
            $from[] = '<div style="margin: 3px 0 0 0"><input type="checkbox" name="groups[cdp][fields][byjuno_installment_payments][value][]" '.$checked.'value="' . $m['value'] . '"></div>';
        }
        $i = 0;
        foreach($methodsName as $m) {
            //<input id="payment_cdp_byjuno_installment_payments_installment_3" type="checkbox" name="groups[cdp][fields][byjuno_installment_payments][value][]" value="installment_3">
            $val = $m["label"];
            if (!empty($stringValues[$i])) {
                $val = $stringValues[$i];
            }
            $to[] = '<input type="text" name="groups[cdp][fields][byjuno_installment_payments][value][]" value="'.htmlspecialchars($val).'">';
            $i++;
        }
        return '<div style="white-space: nowrap;">
            <div style="display:inline-block;padding: 0 5px 0 0; width:15px; vertical-align: top"><ul class="checkboxes"><li>'.implode("</li><li>", $from).'</li></ul></div>
            <div style="display:inline-block;padding: 0 5px 0 0; width:90%; vertical-align: top"><ul class="checkboxes"><li>'.implode("</li><li>", $to).'</li></ul></div>
        </div>';

    }
}
