<?php

class Byjuno_Cdp_Block_Admin_Logedit extends Mage_Core_Block_Abstract
{
     public function __construct()
    {
        $this->_headerText = Mage::helper('byjuno')->__('Log view');
    }

    protected function _toHtml()
    {
        $logview = Mage::getModel('byjuno/byjuno')->load($this->getRequest()->getParam('id'));
        /* @var $logview Byjuno_Cdp_Model_Byjuno */
        $domInput = new DOMDocument();
        $domInput->preserveWhiteSpace = FALSE;
        $domInput->loadXML($logview->getData("request"));
        $elem = $domInput->getElementsByTagName('Request');
        $elem->item(0)->removeAttribute("UserID");
        $elem->item(0)->removeAttribute("Password");

        $domInput->formatOutput = TRUE;
        libxml_use_internal_errors(true);
        $testXml = simplexml_load_string($logview->getData("response"));
        $domOutput = new DOMDocument();
        $domOutput->preserveWhiteSpace = FALSE;
        if ($testXml) {
            $domOutput->loadXML($logview->getData("response"));
            $domOutput->formatOutput = TRUE;
            echo '
            <a href="javascript:history.go(-1)">Back to log</a>
            <h1>Input & output XML</h1>
            <table width="50%">
                <tr>
                    <td>Input (Attributes Login & password removed)</td>
                    <td>Response</td>
                </tr>
                <tr>
                    <td width="50%" style="border: 1px solid #CCCCCC; padding: 5px;"><code style="width: 100%; word-wrap: break-word; white-space: pre-wrap;">'.htmlspecialchars($domInput->saveXml()).'</code></td>
                    <td width="50%" style="border: 1px solid #CCCCCC; padding: 5px;"><code style="width: 100%; word-wrap: break-word; white-space: pre-wrap;">'.htmlspecialchars($domOutput->saveXml()).'</code></td>
                </tr>
            </table>';
        } else {
            echo '
            <a href="javascript:history.go(-1)">Back to log</a>
            <h1>Input & output XML</h1>
            <table width="50%">
                <tr>
                    <td>Input (Attributes Login & password removed)</td>
                    <td>Response</td>
                </tr>
                <tr>
                    <td width="50%" style="border: 1px solid #CCCCCC; padding: 5px;"><code style="width: 100%; word-wrap: break-word; white-space: pre-wrap;">'.htmlspecialchars($domInput->saveXml()).'</code></td>
                    <td width="50%" style="border: 1px solid #CCCCCC; padding: 5px;"><code style="width: 100%; word-wrap: break-word; white-space: pre-wrap;">Raw data: '.$logview->getData("response").'</code></td>
                </tr>
            </table>';
        }
    }

}