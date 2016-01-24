<?php


class Byjuno_Cdp_Model_Source_Activate extends Mage_Core_Model_Config_Data
{

    public function toOptionArray() {

        $methods = Array();
        $methods[] = Array("label" => "Enable", "value" => "enable");
        $methods[] = Array("label" => "Disable", "value" => "disable");
        return $methods;
    }
}