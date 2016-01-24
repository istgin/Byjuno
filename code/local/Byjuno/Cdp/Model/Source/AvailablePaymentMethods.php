<?php


class Byjuno_Cdp_Model_Source_AvailablePaymentMethods
{
    public function toOptionArray() {

        $payments = Mage::getSingleton('payment/config')->getActiveMethods();

        $methods = Array();
        foreach ($payments as $paymentCode=>$paymentModel) {

            $paymentTitle = Mage::getStoreConfig('payment/'.$paymentCode.'/title');
            $methods[$paymentCode] = array(
                'label'   => $paymentTitle,
                'value' => $paymentCode,
            );

        }

        return $methods;
    }
}