<?php


class Byjuno_Cdp_Model_Source_Checkouttype extends Mage_Core_Model_Config_Data
{

    public function toOptionArray() {

        $methods = Array();
        $methods[] = Array("label" => "Default", "value" => "default");
        $methods[] = Array("label" => "Amasty", "value" => "amasty");
        return $methods;
    }
}