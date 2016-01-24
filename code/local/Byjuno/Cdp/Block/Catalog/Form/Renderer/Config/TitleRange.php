<?php

class Byjuno_Cdp_Block_Catalog_Form_Renderer_Config_TitleRange extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        return '<div style="white-space: nowrap;"><div style="display:inline-block;padding: 0 5px 0 0; width:50%"><b style="font-size: 14px">Allowed methods</b></div> <div style="display:inline-block;padding: 0 5px 0 0; width:50%"><b style="font-size: 14px">Denied Methods<b></div></div>';
    }
}
