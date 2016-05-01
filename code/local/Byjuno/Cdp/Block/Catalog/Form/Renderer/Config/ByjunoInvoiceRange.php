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
        );
        $methodsName["invoice_byjuno"] = array(
            'label'   => 'Byjuno Invoice',
            'value' => "invoice_byjuno",
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
            $from[] = '<div style="margin: 3px 0 0 0"><input type="checkbox" name="groups[cdp][fields][byjuno_invoice_payments][value][]" '.$checked.'value="' . $m['value'] . '"></div>';
        }
        $i = 0;
        foreach($methodsName as $m) {
            //<input id="payment_cdp_byjuno_installment_payments_installment_3" type="checkbox" name="groups[cdp][fields][byjuno_installment_payments][value][]" value="installment_3">
            $val = $m["label"];
            if (!empty($stringValues[$i])) {
                $val = $stringValues[$i];
            }
            $to[] = '<input type="text" name="groups[cdp][fields][byjuno_invoice_payments][value][]" value="'.htmlspecialchars($val).'">';
            $i++;
        }
        return '<div style="white-space: nowrap;">
            <div style="display:inline-block;padding: 0 5px 0 0; width:15px; vertical-align: top"><ul class="checkboxes"><li>'.implode("</li><li>", $from).'</li></ul></div>
            <div style="display:inline-block;padding: 0 5px 0 0; width:90%; vertical-align: top"><ul class="checkboxes"><li>'.implode("</li><li>", $to).'</li></ul></div>
        </div>';
    }
}
