<?php

class Byjuno_Cdp_Block_Catalog_Form_Renderer_Config_AllowedRange extends Mage_Adminhtml_Block_System_Config_Form_Field
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
        $allowedDefault = Array();
        $elementsJs = Array();
        foreach ($payments as $paymentCode=>$paymentModel) {

            $paymentTitle = Mage::getStoreConfig('payment/'.$paymentCode.'/title');
            $methodsAllowed[$paymentCode] = array(
                'label'   => $paymentTitle,
                'value' => $paymentCode."_allow",
            );
            array_push($allowedDefault, $paymentCode."_allow");
            $elementsJs[] = $element->getId().'_'.$paymentCode."";

        }

        foreach ($payments as $paymentCode=>$paymentModel) {

            $paymentTitle = Mage::getStoreConfig('payment/'.$paymentCode.'/title');
            $methodsDenied[$paymentCode] = array(
                'label'   => $paymentTitle,
                'value' => $paymentCode."_deny",
            );

        }
        if (empty($values)) {
            $values = $allowedDefault;
        }
        $from = $element->setValues($methodsAllowed)
            ->setValue($values)
            ->getElementHtml();
        $to = $element->setValues($methodsDenied)
            ->setValue($values)
            ->getElementHtml();

        $script = "";
        foreach($elementsJs as $elementj) {
            $script .= "
            document.getElementById('".$elementj."_allow').addEventListener('click', function() {
                if (document.getElementById('".$elementj."_deny').checked == true) {
                    document.getElementById('".$elementj."_deny').checked = false;
                } else {
                    document.getElementById('".$elementj."_deny').checked = true;
                }
            }, false);
            document.getElementById('".$elementj."_deny').addEventListener('click', function() {
                if (document.getElementById('".$elementj."_allow').checked == true) {
                    document.getElementById('".$elementj."_allow').checked = false;
                } else {
                    document.getElementById('".$elementj."_allow').checked = true;

                }
            }, false);
            if (document.getElementById('".$elementj."_deny').checked == false && document.getElementById('".$elementj."_allow').checked == false) {
                document.getElementById('".$elementj."_allow').checked = true;
            }
            ";
        }

        return '<div style="white-space: nowrap;"><div style="display:inline-block;padding: 0 5px 0 0; width:50%">'.$from
            . '</div> <div style="display:inline-block;padding: 0 5px 0 0; width:50%">'
            . $to.'</div></div><script>'.$script.'</script>';
    }
}
