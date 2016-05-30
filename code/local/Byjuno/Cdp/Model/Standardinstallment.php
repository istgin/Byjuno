<?php

class Byjuno_Cdp_Model_Standardinstallment
    extends Byjuno_Cdp_Model_Standardinvoice {

    public $_code = "cdp_installment";
	protected $_formBlockType = 'byjuno/form_byjunoinstallment';
	protected $_infoBlockType = 'byjuno/info_byjunoinstallment';

    public function getTitle()
    {
        return Mage::getStoreConfig('payment/cdp/title_installment', Mage::app()->getStore());
    }

	public function isAvailable($quote = null)
	{
		if (Mage::getStoreConfig('payment/cdp/active', Mage::app()->getStore()) == "0") {
			return false;
		}
		$quote = Mage::getSingleton('checkout/type_onepage')->getQuote();
		$minAmount = Mage::getStoreConfig('payment/cdp/minamount', Mage::app()->getStore());
		$maxAmount = Mage::getStoreConfig('payment/cdp/maxamount', Mage::app()->getStore());
		if ($quote->getGrandTotal() < $minAmount || $quote->getGrandTotal() > $maxAmount) {
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
		/* @var $info Mage_Sales_Model_Quote_Payment */
		$info = $this->getInfoInstance();
		if ($data->getInstallmentPaymentPlan())
		{
		    $info->setAdditionalInformation("payment_plan", $data->getInstallmentPaymentPlan());
		}
		if ($data->getInstallmentPaymentSend())
		{
			$send = $data->getInstallmentPaymentSend();
			$info->setAdditionalInformation("payment_send", $send);
			if ($send == 'postal') {
				$sentTo = (String)$info->getQuote()->getBillingAddress()->getStreetFull().', '.(String)$info->getQuote()->getBillingAddress()->getCity().', '.(String)$info->getQuote()->getBillingAddress()->getPostcode();
			} else {
				$sentTo = $info->getQuote()->getBillingAddress()->getEmail();
			}
			$info->setAdditionalInformation("payment_send_to", $sentTo);
		}
		return $this;
	}

}

?>