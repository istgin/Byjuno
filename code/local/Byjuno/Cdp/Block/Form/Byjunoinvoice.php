<?php

class Byjuno_Cdp_Block_Form_Byjunoinvoice extends Mage_Payment_Block_Form
{
  protected function _construct()
  {
    parent::_construct();
    if (Mage::app()->getStore()->isAdmin())
    {
      $this->setTemplate('byjuno/byjunoinvoice.phtml');
    } else {
      $this->setTemplate('byjuno_frontend/byjunoinvoice.phtml');
    }
  }
}