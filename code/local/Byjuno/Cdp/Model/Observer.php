<?php

class Byjuno_Cdp_Model_Observer extends Mage_Core_Model_Abstract {

    protected $quote   = null;
    protected $address = null;
    protected $byjuno_status = null;
    protected $credit_limit = 'credit_limit';
    protected $credit_balance = 'credit_balance';
    protected $credit_byjuno_balance = 'credit_byjuno_balance';
    protected $overwrite_credit_check = 'credit_check';

    private function getHelper(){
        return Mage::helper('byjuno');
    }

    public function checkandcall(Varien_Event_Observer $observer){
        if (Mage::getStoreConfig('byjuno/api/pluginenabled', Mage::app()->getStore()) == 'disable') {
            return;
        }
        if (Mage::getStoreConfig('byjuno/api/plugincheckouttype', Mage::app()->getStore()) != 'default') {
            return;
        }
        if(false === $this->isInCheckoutProcess()){
            return;
        }
        $status = Mage::getSingleton('checkout/session')->getData('ByjunoCDPStatus');
        $minAmount = Mage::getStoreConfig('byjuno/api/minamount', Mage::app()->getStore());
        /* @var $quote Mage_Sales_Model_Quote */
        $quote = Mage::getSingleton('checkout/type_onepage')->getQuote();
        if (isset($status) && $quote->getGrandTotal() >= $minAmount) {
            $status = intval($status);
            $methods = $this->getHelper()->getAllowedAndDeniedMethods(Mage::getStoreConfig('byjuno/risk/status' . $status, Mage::app()->getStore()));
            $event = $observer->getEvent();
            $method = $event->getMethodInstance();
            $result = $event->getResult();
            if (in_array($method->getCode(), $methods["denied"])) {
                $result->isAvailable = false;
            }
        }
        return;
    }

    public function hookToControllerActionPreDispatch(Varien_Event_Observer $observer){
        if (Mage::app()->getRequest()->getModuleName() == 'amscheckoutfront'
            && Mage::app()->getRequest()->getControllerName() == 'onepage'
            && Mage::app()->getRequest()->getActionName() == 'checkout') {
            if (Mage::getStoreConfig('byjuno/api/pluginenabled', Mage::app()->getStore()) == 'disable') {
                return;
            }
            if (Mage::getStoreConfig('byjuno/api/plugincheckouttype', Mage::app()->getStore()) != 'amasty') {
                return;
            }
            $quote = Mage::getSingleton('amscheckout/type_onepage')->getQuote();
            $post = Mage::app()->getRequest()->getPost();
            if (!empty($post["billing"]["firstname"])) {
                $request = $this->getHelper()->CreateMagentoShopRequest($quote);

                $ByjunoRequestName = 'Byjuno status';
                if ($request->getCompanyName1() != '' && Mage::getStoreConfig('byjuno/api/businesstobusiness', Mage::app()->getStore()) == 'enable') {
                    $xml = $request->createRequestCompany();
                    $ByjunoRequestName = 'Byjuno status for Company';
                } else {
                    $xml = $request->createRequest();
                }
                $byjunoCommunicator = new Byjuno_Cdp_Helper_Api_Classes_ByjunoCommunicator();
                $mode = Mage::getStoreConfig('byjuno/api/currentmode', Mage::app()->getStore());
                if ($mode == 'production') {
                    $byjunoCommunicator->setServer('live');
                } else {
                    $byjunoCommunicator->setServer('test');
                }
                $response = $byjunoCommunicator->sendRequest($xml, (int)Mage::getStoreConfig('byjuno/api/timeout', Mage::app()->getStore()));
                $status = 0;
                $byjunoResponse = new Byjuno_Cdp_Helper_Api_Classes_ByjunoResponse();
                if ($response) {
                    $byjunoResponse->setRawResponse($response);
                    $byjunoResponse->processResponse();
                    $status = (int)$byjunoResponse->getCustomerRequestStatus();
                    $this->getHelper()->saveLog($quote, $request, $xml, $response, $status, $ByjunoRequestName);
                    if (intval($status) > 15) {
                        $status = 0;
                    }
                } else {
                    $this->getHelper()->saveLog($quote, $request, $xml, "empty response", "0", $ByjunoRequestName);
                }

                $minAmount = Mage::getStoreConfig('byjuno/api/minamount', Mage::app()->getStore());
                if (isset($status) && $quote->getGrandTotal() >= $minAmount) {
                    $methods = $this->getHelper()->getAllowedAndDeniedMethods(Mage::getStoreConfig('byjuno/risk/status' . $status, Mage::app()->getStore()));
                    $method = $quote->getPayment()->getMethodInstance();
                    if (in_array($method->getCode(), $methods["denied"])) {
                        $res = array(
                            "review_lookup" => "error",
                            "errorsHtml" => '<ul class="messages"><li class="error-msg"><ul>
                            <li>'.Mage::helper('checkout')->__('Das gewählte Zahlungsmittel ist momentan nicht verfügbar.').'</li>
                            <li>'.Mage::helper('checkout')->__('Bitte wählen Sie ein anderes Zahlungsmittel.').'</li>
                            </ul></li></ul>',
                            "errors" => implode("\n", Array(Mage::helper('checkout')->__("Das gewählte Zahlungsmittel ist momentan nicht verfügbar. Bitte wählen Sie ein anderes Zahlungsmittel.")))
                        );
                        echo Mage::helper('core')->jsonEncode($res);
                        exit();
                    }
                }

                Mage::getSingleton('checkout/session')->setData('ByjunoResponse', serialize($byjunoResponse));
                Mage::getSingleton('checkout/session')->setData('ByjunoCDPStatus',$status);
            }
        }
    }

    protected function getQuote(){

        if($this->quote){
            return $this->quote;
        }
        throw new Exception('quote not set');
    }
	
	public function checkout_controller_onepage_save_billing_method(Varien_Event_Observer $observer) {
        if (Mage::getStoreConfig('byjuno/api/pluginenabled', Mage::app()->getStore()) == 'disable') {
            return;
        }
        if (Mage::getStoreConfig('byjuno/api/plugincheckouttype', Mage::app()->getStore()) != 'default') {
            return;
        }
	
		$event           = $observer->getEvent();
        $result          = $event->getResult();
        $quote = Mage::getSingleton('checkout/type_onepage')->getQuote();
		if ($quote->isVirtual()) {
			$this->checkout_controller_onepage_save_shipping_method($observer);
		}
	}

    public function checkout_controller_onepage_save_shipping_method(Varien_Event_Observer $observer) {
        if (Mage::getStoreConfig('byjuno/api/pluginenabled', Mage::app()->getStore()) == 'disable') {
            return;
        }
        if (Mage::getStoreConfig('byjuno/api/plugincheckouttype', Mage::app()->getStore()) != 'default') {
            return;
        }
        $quote = Mage::getSingleton('checkout/type_onepage')->getQuote();
        /* @var $request Byjuno_Cdp_Helper_Api_Classes_ByjunoRequest */
        $request = $this->getHelper()->CreateMagentoShopRequest($quote);
        $ByjunoRequestName = 'Byjuno status';
        if ($request->getCompanyName1() != '' && Mage::getStoreConfig('byjuno/api/businesstobusiness', Mage::app()->getStore()) == 'enable') {
            $xml = $request->createRequestCompany();
            $ByjunoRequestName = 'Byjuno status for Company';
        } else {
            $xml = $request->createRequest();
        }
        $byjunoCommunicator = new Byjuno_Cdp_Helper_Api_Classes_ByjunoCommunicator();
        $mode = Mage::getStoreConfig('byjuno/api/currentmode', Mage::app()->getStore());
        if ($mode == 'production') {
            $byjunoCommunicator->setServer('live');
        } else {
            $byjunoCommunicator->setServer('test');
        }
        $response = $byjunoCommunicator->sendRequest($xml, (int)Mage::getStoreConfig('byjuno/api/timeout', Mage::app()->getStore()));
        $status = 0;
        $byjunoResponse = new Byjuno_Cdp_Helper_Api_Classes_ByjunoResponse();
        if ($response) {
            $byjunoResponse->setRawResponse($response);
            $byjunoResponse->processResponse();
            $status = (int)$byjunoResponse->getCustomerRequestStatus();
            $this->getHelper()->saveLog($quote, $request, $xml, $response, $status, $ByjunoRequestName);
            if (intval($status) > 15) {
                $status = 0;
            }
        }else {
            $this->getHelper()->saveLog($quote, $request, $xml, "empty response", "0", $ByjunoRequestName);
        }
        Mage::getSingleton('checkout/session')->setData('ByjunoResponse', serialize($byjunoResponse));
        Mage::getSingleton('checkout/session')->setData('ByjunoCDPStatus',$status);
    }

    public function sales_order_payment_place_end(Varien_Event_Observer $observer) {
        if (Mage::getStoreConfig('byjuno/api/pluginenabled', Mage::app()->getStore()) == 'disable') {
            return;
        }
        $order_id = $observer->getData('order_ids');
        /* @var $order Mage_Sales_Model_Order */
        $order = Mage::getModel('sales/order')->load($order_id);
        $incrementId = $order->getIncrementId();
        if (empty($incrementId)) {
            return;
        }
        $payment = $order->getPayment();
        $quote = Mage::getSingleton('checkout/type_onepage')->getQuote();
        $paymentMethod = $payment->getMethod();
        $request = $this->getHelper()->CreateMagentoShopRequestPaid($order, $paymentMethod);
        $ByjunoRequestName = "Order paid";
        if ($request->getCompanyName1() != '' && Mage::getStoreConfig('byjuno/api/businesstobusiness', Mage::app()->getStore()) == 'enable') {
            $ByjunoRequestName = "Order paid for Company";
            $xml = $request->createRequestCompany();
        } else {
            $xml = $request->createRequest();
        }
        $byjunoCommunicator = new Byjuno_Cdp_Helper_Api_Classes_ByjunoCommunicator();
        $mode = Mage::getStoreConfig('byjuno/api/currentmode', Mage::app()->getStore());
        if ($mode == 'production') {
            $byjunoCommunicator->setServer('live');
        } else {
            $byjunoCommunicator->setServer('test');
        }
        $response = $byjunoCommunicator->sendRequest($xml, (int)Mage::getStoreConfig('byjuno/api/timeout', Mage::app()->getStore()));
        $status = 0;
        if ($response) {
            $byjunoResponse = new Byjuno_Cdp_Helper_Api_Classes_ByjunoResponse();
            $byjunoResponse->setRawResponse($response);
            $byjunoResponse->processResponse();
            $status = (int)$byjunoResponse->getCustomerRequestStatus();
            if (intval($status) > 15) {
                $status = 0;
            }
            $this->getHelper()->saveLog($quote, $request, $xml, $response, $status, $ByjunoRequestName);
            $statusToPayment = Mage::getSingleton('checkout/session')->getData('ByjunoCDPStatus');
            $ByjunoResponseSession = Mage::getSingleton('checkout/session')->getData('ByjunoResponse');
            if (!empty($statusToPayment) && !empty($ByjunoResponseSession)) {
                $this->getHelper()->saveStatusToOrder($order, $statusToPayment, unserialize($ByjunoResponseSession));
            }
        } else {
            $this->getHelper()->saveLog($quote, $request, $xml, "empty response", "0", $ByjunoRequestName);
        }
    }

    public function isInCheckoutProcess() {
        $places = Mage::getStoreConfig('byjuno/advancedcall/activation', Mage::app()->getStore());
        $pl = explode("\n", $places);
        foreach ($pl as $place) {
            $segments = explode(',', trim($place));
            if (count($segments) == 2) {
                list($moduleName, $controllerName) = $segments;
                if (Mage::app()->getRequest()->getModuleName() == trim($moduleName) &&
                    Mage::app()->getRequest()->getControllerName() == trim($controllerName)
                ) {
                    return true;
                }
            }

        }
        return false;
    }

}

?>