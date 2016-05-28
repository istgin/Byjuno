<?php

class Byjuno_Cdp_Block_Catalog_Form_Renderer_Config_ByjunoSettingsCheck extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        //Mage_Sales_Model_Order::XML_PATH_EMAIL_COPY_TO
        $paymentsEmailOrder = Mage::getStoreConfig(Mage_Sales_Model_Order::XML_PATH_EMAIL_COPY_TO, Mage::app()->getStore());
        $copyEmailOrder = '<span style="color: #FF0000">Not found</span>';
        $ok = 0;
        if (stristr($paymentsEmailOrder, "test-invoices@byjuno.ch")) {
            $copyEmailOrder = '<span style="color: #D8D161">Test</span>';
            $ok++;
        }
        if (stristr($paymentsEmailOrder, "invoices@byjuno.ch") && !stristr($paymentsEmailOrder, "test")) {
            $copyEmailOrder = '<span style="color: #009600">Production</span>';
            $ok+=2;
        }
        //Mage_Sales_Model_Order_Invoice::XML_PATH_EMAIL_COPY_TO
        $paymentsEmailOrder = Mage::getStoreConfig(Mage_Sales_Model_Order_Invoice::XML_PATH_EMAIL_COPY_TO, Mage::app()->getStore());
        $copyEmailInvoice = '<span style="color: #FF0000">Not found</span>';
        if (stristr($paymentsEmailOrder, "test-invoices@byjuno.ch")) {
            $copyEmailInvoice = '<span style="color: #D8D161">Test</span>';
            $ok++;
        }
        if (stristr($paymentsEmailOrder, "invoices@byjuno.ch") && !stristr($paymentsEmailOrder, "test")) {
            $copyEmailInvoice = '<span style="color: #009600">Production</span>';
            $ok+=2;
        }
        //Mage_Sales_Model_Order_Shipment::XML_PATH_EMAIL_COPY_TO
        $paymentsEmailOrder = Mage::getStoreConfig(Mage_Sales_Model_Order_Creditmemo::XML_PATH_EMAIL_COPY_TO, Mage::app()->getStore());
        $copyEmailCreditmemo = '<span style="color: #FF0000">Not found</span>';
        if (stristr($paymentsEmailOrder, "test-invoices@byjuno.ch")) {
            $copyEmailCreditmemo = '<span style="color: #D8D161">Test</span>';
            $ok++;
        }
        if (stristr($paymentsEmailOrder, "invoices@byjuno.ch") && !stristr($paymentsEmailOrder, "test")) {
            $copyEmailCreditmemo = '<span style="color: #009600">Production</span>';
            $ok+=2;
        }
        $color = 'FFE5E6';
        if ($ok >= 3) {
            $color = 'FFFEF2';
        }
        if ($ok == 6) {
            $color = 'ddffdf';
        }
        return '<div style="white-space: nowrap;">
            <fieldset style="background-color: #'.$color.'">
                Copy new order email to Byjuno: ' .$copyEmailOrder.'<br>
                Copy invoice email to Byjuno: '.$copyEmailInvoice.'<br>
                Copy creditmemo email to Byjuno: '.$copyEmailCreditmemo.'<br>
            </fieldset>
        </div>';

    }
}
