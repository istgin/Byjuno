<?php

class Byjuno_Cdp_Block_Catalog_Form_Renderer_Config_ByjunoSettingsCheck extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        //Mage_Sales_Model_Order::XML_PATH_EMAIL_COPY_TO
        $paymentsEmailOrder = Mage::getStoreConfig(Mage_Sales_Model_Order::XML_PATH_EMAIL_COPY_TO, Mage::app()->getStore());
        $copyEmailOrder = '<span style="color: #FF0000">Not found</span>';
        $ok = 0;
        if (stristr($paymentsEmailOrder, "info@byjuno.ch")) {
            $copyEmailOrder = '<span style="color: #009600">OK</span>';
            $ok++;
        }
        //Mage_Sales_Model_Order_Invoice::XML_PATH_EMAIL_COPY_TO
        $paymentsEmailOrder = Mage::getStoreConfig(Mage_Sales_Model_Order_Invoice::XML_PATH_EMAIL_COPY_TO, Mage::app()->getStore());
        $copyEmailInvoice = '<span style="color: #FF0000">Not found</span>';
        if (stristr($paymentsEmailOrder, "info@byjuno.ch")) {
            $copyEmailInvoice = '<span style="color: #009600">OK</span>';
            $ok++;
        }
        //Mage_Sales_Model_Order_Shipment::XML_PATH_EMAIL_COPY_TO
        $paymentsEmailOrder = Mage::getStoreConfig(Mage_Sales_Model_Order_Shipment::XML_PATH_EMAIL_COPY_TO, Mage::app()->getStore());
        $copyEmailShipping = '<span style="color: #FF0000">Not found</span>';
        if (stristr($paymentsEmailOrder, "info@byjuno.ch")) {
            $copyEmailShipping = '<span style="color: #009600">OK</span>';
            $ok++;
        }
        $color = 'ddffdf';
        if ($ok != 3) {
            $color = 'FFE5E6';
        }
        return '<div style="white-space: nowrap;">
            <fieldset style="background-color: #'.$color.'">
                Copy new order email to Byjuno: ' .$copyEmailOrder.'<br>
                Copy invoice email to Byjuno: '.$copyEmailInvoice.'<br>
                Copy shipping email to Byjuno: '.$copyEmailShipping.'<br>
            </fieldset>
        </div>';

    }
}
