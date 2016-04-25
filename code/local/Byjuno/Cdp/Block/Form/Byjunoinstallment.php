<?php

class Byjuno_Cdp_Block_Form_Byjunoinstallment extends Mage_Payment_Block_Form
{
  protected function _construct()
  {
    parent::_construct();
    $this->setTemplate('byjuno_frontend/byjunoinstallment.phtml');
  }
}