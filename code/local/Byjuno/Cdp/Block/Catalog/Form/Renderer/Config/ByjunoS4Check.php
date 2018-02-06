<?php

class Byjuno_Cdp_Block_Catalog_Form_Renderer_Config_ByjunoS4Check extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $byjuno_s4_explain = Mage::getStoreConfig('payment/cdp/byjunos4transacton', Mage::getSingleton('adminhtml/config_data')->getStore());
        $message = 'S4 Transaction (Settlement/Invoice) must be delivered to Byjuno manually or from ERP system';
        $color = 'FFE5E6';
        if ($byjuno_s4_explain == 1) {
            $message = 'S4 Transaction (Settlement/Invoice) will be sent to Byjuno when new Invoice is created on the order';
            $color = 'ddffdf';
        }
        return '<div style="white-space: nowrap;">
            <fieldset style="background-color: #'.$color.'">'.$message.'
            </fieldset>
        </div>';

    }
}
