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
            'toc' => "http://www.byjuno.ch"
        );
        $methodsName["installment_10"] = array(
            'label'   => '10 monthly installments',
            'value' => "installment_10",
            'toc' => "http://www.byjuno.ch"
        );
        $methodsName["installment_12"] = array(
            'label'   => '12 monthly installments',
            'value' => "installment_12",
            'toc' => "http://www.byjuno.ch"
        );
        $methodsName["installment_24"] = array(
            'label'   => '24 monthly installments',
            'value' => "installment_24",
            'toc' => "http://www.byjuno.ch"
        );
        $methodsName["installment_4x12"] = array(
            'label'   => '4 installments in 12 months',
            'value' => "installment_4x12",
            'toc' => "http://www.byjuno.ch"
        );
        $methodsName["installment_4x10"] = array(
            'label'   => '4 installments in 10 months',
            'value' => "installment_4x10",
            'toc' => "http://www.byjuno.ch"
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
            $from[] = '<div style="margin: 3px 0 0 0; height: 19px"><input type="checkbox" name="groups[cdp][fields][byjuno_installment_payments][value][]" '.$checked.'value="' . $m['value'] . '" '.$disabled.'></div>';
        }
        $i = 0;
        foreach($methodsName as $m) {
            //<input id="payment_cdp_byjuno_installment_payments_installment_3" type="checkbox" name="groups[cdp][fields][byjuno_installment_payments][value][]" value="installment_3">
            $val = $m["label"];
            if (!empty($stringValues[$i])) {
                $val = $stringValues[$i];
            }
            $toc = $m["toc"];
            if (!empty($stringValues[$i + 6])) {
                $toc = $stringValues[$i + 6];
            }
            $to[] = '<input style="width: 200px" type="text" name="groups[cdp][fields][byjuno_installment_payments][value][]" value="'.htmlspecialchars($val).'" '.$disabled.'>';
            $totoc[] = '<input style="width: 200px" style="width: 200px" type="text" name="groups[cdp][fields][byjuno_installment_payments][value][]" value="'.htmlspecialchars($toc).'" '.$disabled.'>';
            $i++;
        }
        return '<div style="white-space: nowrap;">
            <div style="display:inline-block;padding: 0 5px 0 0; width:15px; vertical-align: top"><ul class="checkboxes"><li>&nbsp;</li><li>'.implode("</li><li>", $from).'</li></ul></div>
            <div style="display:inline-block;padding: 0 5px 0 0; width:45%; vertical-align: top"><ul class="checkboxes"><li><b>Payment plan name</b></li><li>'.implode("</li><li>", $to).'</li></ul></div>
            <div style="display:inline-block;padding: 0 5px 0 0; width:45%; vertical-align: top"><ul class="checkboxes"><li><b>Payment plan T&C</b></li><li>'.implode("</li><li>", $totoc).'</li></ul></div>
        </div>';

    }
}
