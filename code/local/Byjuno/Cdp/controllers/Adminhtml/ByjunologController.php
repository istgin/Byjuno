<?php
class Byjuno_Cdp_Adminhtml_ByjunologController extends Mage_Adminhtml_Controller_Action
{
    public function logAction()
    {
        $this->loadLayout()->_addContent($this->getLayout()->createBlock('byjuno/admin_log'))->renderLayout();
    }

    public function logeditAction()
    {
        $this->loadLayout()->_addContent($this->getLayout()->createBlock('byjuno/admin_logedit'))->renderLayout();
    }
    
}
