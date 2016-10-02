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
		$CDPresponse = $this->CDPRequest($quote);
		if ($CDPresponse !== null) {
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
		if (is_array($data)) {
			if (isset($data["installment_payment_plan"])) {
				$info->setAdditionalInformation("payment_plan", $data["installment_payment_plan"]);
			}
			if (Mage::getStoreConfig('payment/cdp/gender_enable', Mage::app()->getStore()) == '1') {
				if (isset($data["installment_gender"])) {
					$info->setAdditionalInformation("gender_custom", $data["installment_gender"]);
				}
			}
			if (Mage::getStoreConfig('payment/cdp/birthday_enable', Mage::app()->getStore()) == '1') {
				if (isset($data["installment_dob"])) {
					$info->setAdditionalInformation("dob_custom", $data["installment_dob"]);
				}
				if (isset($data["installment_month"]) && isset($data["installment_day"]) && isset($data["installment_year"])) {
					$dob = intval($data["installment_day"]).'.'.intval($data["installment_month"]).'.'.intval($data["installment_year"]);
					$info->setAdditionalInformation("dob_custom", $dob);
				}
			}
			if (isset($data["installment_payment_send"])) {
				$send = $data["installment_payment_send"];
				$info->setAdditionalInformation("payment_send", $send);
				if ($send == 'postal') {
					$sentTo = (String)$info->getQuote()->getBillingAddress()->getStreetFull() . ', ' . (String)$info->getQuote()->getBillingAddress()->getCity() . ', ' . (String)$info->getQuote()->getBillingAddress()->getPostcode();
				} else {
					$sentTo = $info->getQuote()->getBillingAddress()->getEmail();
				}
				$info->setAdditionalInformation("payment_send_to", $sentTo);
			}
		}
		elseif ($data instanceof Varien_Object) {
			if ($data->getInstallmentPaymentPlan()) {
				$info->setAdditionalInformation("payment_plan", $data->getInstallmentPaymentPlan());
			}
			if (Mage::getStoreConfig('payment/cdp/gender_enable', Mage::app()->getStore()) == '1') {
				if ($data->getInstallmentGender()) {
					$info->setAdditionalInformation("gender_custom", $data->getInstallmentGender());
				}
			}
			if (Mage::getStoreConfig('payment/cdp/birthday_enable', Mage::app()->getStore()) == '1') {
				if ($data->getInstallmentMonth() && $data->getInstallmentDay() && $data->getInstallmentYear()) {
					$dob = intval($data->getInstallmentDay()).'.'.intval($data->getInstallmentMonth()).'.'.intval($data->getInstallmentYear());
					$info->setAdditionalInformation("dob_custom", $dob);
				}
			}
			if ($data->getInstallmentPaymentSend()) {
				$send = $data->getInstallmentPaymentSend();
				$info->setAdditionalInformation("payment_send", $send);
				if ($send == 'postal') {
					$sentTo = (String)$info->getQuote()->getBillingAddress()->getStreetFull() . ', ' . (String)$info->getQuote()->getBillingAddress()->getCity() . ', ' . (String)$info->getQuote()->getBillingAddress()->getPostcode();
				} else {
					$sentTo = $info->getQuote()->getBillingAddress()->getEmail();
				}
				$info->setAdditionalInformation("payment_send_to", $sentTo);
			}
		}
		$info->setAdditionalInformation("webshop_profile", Mage::app()->getStore()->getId());
		return $this;
	}

}

?>