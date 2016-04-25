<?php

class Byjuno_Cdp_Model_Standardinstallment
    extends Byjuno_Cdp_Model_Standardinvoice {

    public $_code = "cdp_installment";
	protected $_formBlockType = 'byjuno/form_byjunoinstallment';
    public function getTitle()
    {
        return  Mage::getStoreConfig('payment/cdp/title_installment', Mage::app()->getStore());
    }
	
	public function validate()
    {
        parent::validate(); 
        return $this;
    }
  
	public function assignData($data)
	{
		$info = $this->getInfoInstance();
		 /*
		if ($data->getCustomFieldOne())
		{
		  $info->setCustomFieldOne($data->getCustomFieldOne());
		}
		 
		if ($data->getCustomFieldTwo())
		{
		  $info->setCustomFieldTwo($data->getCustomFieldTwo());
		}
		*/
	 
		return $this;
	}

}

?>