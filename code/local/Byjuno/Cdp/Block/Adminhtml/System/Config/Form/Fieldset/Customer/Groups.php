<?php
class Byjuno_Cdp_Block_Adminhtml_System_Config_Form_Fieldset_Customer_Groups extends Mage_Adminhtml_Block_System_Config_Form_Fieldset{
    protected $_dummyElement;
    protected $_fieldRenderer;
    protected $_values;

    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        $html = $this->_getHeaderHtml($element);

        $groups = Mage::getSingleton('payment/config')->getActiveMethods();
        foreach ($groups as $paymentCode => $paymentModel) {
            $html.= $this->_getFieldHtml($element, $paymentModel, $paymentCode);
        }
        $html .= $this->_getFooterHtml($element);

        return $html;
    }

    protected function _getDummyElement()
    {
        if (empty($this->_dummyElement)) {
            $this->_dummyElement = new Varien_Object(array('show_in_default'=>1, 'show_in_website'=>1));
        }
        return $this->_dummyElement;
    }

    protected function _getFieldRenderer()
    {
        if (empty($this->_fieldRenderer)) {
            $this->_fieldRenderer = Mage::getBlockSingleton('adminhtml/system_config_form_field');
        }
        return $this->_fieldRenderer;
    }

    protected function _getValues()
    {
        if (empty($this->_values)) {
            $this->_values = array(
                array('label'=>Mage::helper('adminhtml')->__('INVOICE'), 'value'=>'INVOICE'),
                array('label'=>Mage::helper('adminhtml')->__('DIRECT-DEBIT'), 'value'=>'DIRECT-DEBIT'),
                array('label'=>Mage::helper('adminhtml')->__('CREDIT-CARD'), 'value'=>'CREDIT-CARD'),
                array('label'=>Mage::helper('adminhtml')->__('PRE-PAY'), 'value'=>'PRE-PAY'),
                array('label'=>Mage::helper('adminhtml')->__('CASH-ON-DELIVERY'), 'value'=>'CASH-ON-DELIVERY'),
                array('label'=>Mage::helper('adminhtml')->__('E-PAYMENT'), 'value'=>'E-PAYMENT'),
                array('label'=>Mage::helper('adminhtml')->__('PAYMENT'), 'value'=>'PAYMENT')
            );
        }
        return $this->_values;
    }

    protected function _getFieldHtml($fieldset, $group, $paymentCode)
    {
        $configData = $this->getConfigData();
        $path = 'byjuno/mappings/group_'.$paymentCode;
        if (isset($configData[$path])) {
            $data = $configData[$path];
            $inherit = false;
        } else {
            $data = 'INVOICE';
            $inherit = true;
        }

        $e = $this->_getDummyElement();

        $field = $fieldset->addField($group->getId(), 'select',
            array(
                'name'          => 'groups[mappings][fields][group_'.$paymentCode.'][value]',
                'label'         => $paymentTitle = Mage::getStoreConfig('payment/'.$paymentCode.'/title'),
                'value'         => $data,
                'values'        => $this->_getValues(),
                'inherit'       => $inherit,
                'can_use_default_value' => $this->getForm()->canUseDefaultValue($e),
                'can_use_website_value' => $this->getForm()->canUseWebsiteValue($e),
            ))->setRenderer($this->_getFieldRenderer());

        return $field->toHtml();
    }
}