<?php


class Byjuno_Cdp_Model_Source_Timeoutorderstate
{

    public function toOptionArray()
    {
        $options = array();
        $options[] = array(
            'value' => 'cancel',
            'label' => 'Cancel order'
        );
        $options[] = array(
            'value' => 'keeporder',
            'label' => 'Keep cart'
        );
        $options[] = array(
            'value' => 'successorder',
            'label' => 'Success order with state Byjuno timeout'
        );
        return $options;
    }
}
