<?php

class Byjuno_Cdp_Model_Standardinstallment
    extends Byjuno_Cdp_Model_Standardinvoice {

    public $_code = "cdp_installment";
	protected $_formBlockType = 'byjuno/form_byjunoinstallment';
    public function getTitle()
    {
        return Mage::getStoreConfig('payment/cdp/title_installment', Mage::app()->getStore());
    }

	public function isAvailable($quote = null)
	{
		if (Mage::getStoreConfig('payment/cdp/active', Mage::app()->getStore()) == "0") {
			return false;
		}
		$payments = Mage::getStoreConfig('payment/cdp/byjuno_installment_payments', Mage::app()->getStore());
		$active = false;
		$plns = explode(",", $payments);
		foreach($plns as $val) {
			if (strstr($val, "_enable")) {
				$active = true;
				break;
			}
		}
		if (!$active) {
			return false;
		}
		return true;
	}
	
	public function validate()
    {
        parent::validate(); 
        return $this;
    }
  
	public function assignData($data)
	{
		$info = $this->getInfoInstance();
		if ($data->getPaymentPlan())
		{
		    $info->setAdditionalInformation("payment_plan", $data->getPaymentPlan());
		}
		return $this;
	}

}

?>