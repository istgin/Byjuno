<?php

class Byjuno_Cdp_Block_Catalog_Form_Renderer_Config_ByjunoInvoiceRange extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $element->setStyle('display:block')
            ->setName($element->getName() . '[]');

        $methodsAllowed["invoice_single"] = array(
            'value' => "invoice_single_enable",
        );
        $methodsAllowed["invoice_byjuno"] = array(
            'value' => "invoice_byjuno_enable",
        );

        $methodsName["invoice_single"] = array(
            'label'   => 'Single',
            'value' => "invoice_single",
            'toc' => "http://www.byjuno.ch",
        );
        $methodsName["invoice_byjuno"] = array(
            'label'   => 'Byjuno Invoice',
            'value' => "invoice_byjuno",
            'toc' => "http://www.byjuno.ch",
        );

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
            $from[] = '<div style="margin: 3px 0 0 0; height: 19px"><input type="checkbox" name="groups[cdp][fields][byjuno_invoice_payments][value][]" '.$checked.'value="' . $m['value'] . '" '.$disabled.'></div>';
        }
        $i = 0;
        foreach($methodsName as $m) {
            //<input id="payment_cdp_byjuno_installment_payments_installment_3" type="checkbox" name="groups[cdp][fields][byjuno_installment_payments][value][]" value="installment_3">
            $val = $m["label"];
            if (!empty($stringValues[$i])) {
                $val = $stringValues[$i];
            }

            $toc = $m["toc"];
            if (!empty($stringValues[$i + 2])) {
                $toc = $stringValues[$i + 2];
            }
            $to[] = '<input style="width: 200px" type="text" name="groups[cdp][fields][byjuno_invoice_payments][value][]" value="'.htmlspecialchars($val).'" '.$disabled.'>';
            $totoc[] = '<input style="width: 200px" type="text" name="groups[cdp][fields][byjuno_invoice_payments][value][]" value="'.htmlspecialchars($toc).'" '.$disabled.'>';
            $i++;
        }
        return '<div style="white-space: nowrap;">
            <div style="display:inline-block;padding: 0 5px 0 0; width:15px; vertical-align: top"><ul class="checkboxes"><li>&nbsp;</li><li>'.implode("</li><li>", $from).'</li></ul></div>
            <div style="display:inline-block;padding: 0 5px 0 0; width:45%; vertical-align: top"><ul class="checkboxes"><li><b>Payment plan name</b></li><li>'.implode("</li><li>", $to).'</li></ul></div>
            <div style="display:inline-block;padding: 0 5px 0 0; width:45%; vertical-align: top"><ul class="checkboxes"><li><b>Payment plan T&C</b></li><li>'.implode("</li><li>", $totoc).'</li></ul></div>
        </div>';
    }
}
