<?php


class Byjuno_Cdp_Model_Source_Timeoutorderstate
{

    public function toOptionArray()
    {
        $options = array();
        $options[] = array(
            'value' => 'cancel',
            'label' => 'Cancel order and return to cart'
        );
        $options[] = array(
            'value' => 'keeporder',
            'label' => 'Keep order in Byjuno pending state and return to cart'
        );
        $options[] = array(
            'value' => 'successorder',
            'label' => 'Success order with state Byjuno timeout'
        );
        return $options;
    }
}
