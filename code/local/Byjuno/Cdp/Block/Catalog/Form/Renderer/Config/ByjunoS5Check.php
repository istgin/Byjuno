<?php

class Byjuno_Cdp_Block_Catalog_Form_Renderer_Config_ByjunoS5Check extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $byjuno_s5_explain = Mage::getStoreConfig('payment/cdp/byjunos5transacton', Mage::getSingleton('adminhtml/config_data')->getStore());
        $message = 'S5 Transactions (Cancel and/or Refund) must be delivered to Byjuno manually or from ERP System';
        $color = 'FFE5E6';
        if ($byjuno_s5_explain == 1) {
            $message = 'S5 Transactions will be sent to Byjuno:<br/>
Cancel - for not invoiced amount<br/>
Refund - per Credit Memo';
            $color = 'ddffdf';
        }
        return '<div style="white-space: nowrap;">
            <fieldset style="background-color: #'.$color.'">'.$message.'
            </fieldset>
        </div>';

    }
}
