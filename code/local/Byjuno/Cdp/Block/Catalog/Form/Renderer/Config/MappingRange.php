<?php

class Byjuno_Cdp_Block_Catalog_Form_Renderer_Config_MappingRange extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $element->setStyle('display:block')
            ->setName($element->getName() . '[]');


        if ($element->getValue()) {
            $values = explode(',', $element->getValue());
        } else {
            $values = array();
        }
        $payments = Mage::getSingleton('payment/config')->getActiveMethods();
        $methodsExists = Array();
        foreach ($payments as $paymentCode=>$paymentModel) {

            $paymentTitle = Mage::getStoreConfig('payment/'.$paymentCode.'/title');
            $methodsExists[] = $paymentTitle;

        }

        foreach ($payments as $paymentCode=>$paymentModel) {

            $paymentTitle = Mage::getStoreConfig('payment/'.$paymentCode.'/title');
            $methodsDenied[$paymentCode] = array(
                'label'   => $paymentTitle,
                'value' => $paymentCode."_deny",
            );

        }

        return '<div style="white-space: nowrap;"><div style="display:inline-block;padding: 0 5px 0 0; width:50%">'.implode("<br>", $methodsExists)
            . '</div> <div style="display:inline-block;padding: 0 5px 0 0; width:50%">x</div></div>';
    }
}
