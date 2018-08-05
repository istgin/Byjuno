<?php


class Byjuno_Cdp_Model_Source_Activationplace
{

    public function toOptionArray()
    {
        $options = array();
        $options[] = array(
            'value' => "invoice",
            'label' => "On invoice"
        );
        $options[] = array(
            'value' => "shipping",
            'label' => "On shipping (if available)"
        );
        return $options;
    }
}
