<?php


class Byjuno_Cdp_Model_Source_Modes extends Mage_Core_Model_Config_Data
{

    public function toOptionArray() {

        $methods = Array();
        $methods[] = Array("label" => "Test", "value" => "test");
        $methods[] = Array("label" => "Production", "value" => "production");
        return $methods;
    }
}