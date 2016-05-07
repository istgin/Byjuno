<?php

class Byjuno_Cdp_Model_Observer extends Mage_Core_Model_Abstract {

    protected $quote   = null;
    protected $address = null;
    protected $byjuno_status = null;
    protected $credit_limit = 'credit_limit';
    protected $credit_balance = 'credit_balance';
    protected $credit_byjuno_balance = 'credit_byjuno_balance';
    protected $overwrite_credit_check = 'credit_check';


    function CreateMagentoShopRequest(Mage_Sales_Model_Quote $quote) {

        $request = new Byjuno_Cdp_Helper_Api_Classes_ByjunoRequest();
        $request->setClientId(Mage::getStoreConfig('byjuno/api/clientid',Mage::app()->getStore()));
        $request->setUserID(Mage::getStoreConfig('byjuno/api/userid',Mage::app()->getStore()));
        $request->setPassword(Mage::getStoreConfig('byjuno/api/password',Mage::app()->getStore()));
        $request->setVersion("1.00");
        try {
            $request->setRequestEmail(Mage::getStoreConfig('byjuno/api/mail',Mage::app()->getStore()));
        } catch (Exception $e) {

        }
        $b = $quote->getCustomerDob();
        if (!empty($b)) {
            $request->setDateOfBirth(Mage::getModel('core/date')->date('Y-m-d', strtotime($b)));
        }
        Mage::getSingleton('checkout/session')->setData('dob_amasty', '');
        if (Mage::getStoreConfig('byjuno/api/plugincheckouttype', Mage::app()->getStore()) == 'amasty' && !empty($_POST["billing"]["dob"])) {
            $request->setDateOfBirth(Mage::getModel('core/date')->date('Y-m-d', strtotime($_POST["billing"]["dob"])));
            Mage::getSingleton('checkout/session')->setData('dob_amasty', $_POST["billing"]["dob"]);
        }

        $g = $quote->getCustomerGender();
        if (!empty($g)) {
            if ($g == '1') {
                $request->setGender('1');
            } else if ($g == '2') {
                $request->setGender('2');
            }
        }

        $request->setRequestId(uniqid((String)$quote->getBillingAddress()->getId()."_"));
        $reference = $quote->getCustomer()->getId();
        if (empty($reference)) {
            $request->setCustomerReference("guest_".$quote->getBillingAddress()->getId());
        } else {
            $request->setCustomerReference($quote->getCustomer()->getId());
        }
        if (Mage::getStoreConfig('byjuno/api/plugincheckouttype', Mage::app()->getStore()) == 'amasty' && !empty($_POST["method"]) && $_POST["method"] == 'guest') {
            $request->setCustomerReference(uniqid("guest_"));
        }

        $request->setFirstName((String)$quote->getBillingAddress()->getFirstname());
        if (Mage::getStoreConfig('byjuno/api/plugincheckouttype', Mage::app()->getStore()) == 'amasty' && !empty($_POST["billing"]["firstname"])) {
            $request->setFirstName((String)$_POST["billing"]["firstname"]);
        }

        $request->setLastName((String)$quote->getBillingAddress()->getLastname());
        if (Mage::getStoreConfig('byjuno/api/plugincheckouttype', Mage::app()->getStore()) == 'amasty' && !empty($_POST["billing"]["lastname"])) {
            $request->setLastName((String)$_POST["billing"]["lastname"]);
        }

        $request->setFirstLine(trim((String)$quote->getBillingAddress()->getStreetFull()));
        if (Mage::getStoreConfig('byjuno/api/plugincheckouttype', Mage::app()->getStore()) == 'amasty' && !empty($_POST["billing"]["street"])) {
            $street = '';
            if (!empty($_POST["billing"]["street"][0])) {
                $street .= $_POST["billing"]["street"][0];
            }
            if (!empty($_POST["billing"]["street"][1])) {
                $street .= $_POST["billing"]["street"][1];
            }
            $request->setFirstLine((String)trim($street));
        }

        $request->setCountryCode(strtoupper((String)$quote->getBillingAddress()->getCountry()));
        if (Mage::getStoreConfig('byjuno/api/plugincheckouttype', Mage::app()->getStore()) == 'amasty' && !empty($_POST["billing"]["country_id"])) {
            $request->setCountryCode((String)$_POST["billing"]["country_id"]);
        }

        $request->setPostCode((String)$quote->getBillingAddress()->getPostcode());
        if (Mage::getStoreConfig('byjuno/api/plugincheckouttype', Mage::app()->getStore()) == 'amasty' && !empty($_POST["billing"]["postcode"])) {
            $request->setPostCode($_POST["billing"]["postcode"]);
        }

        $request->setTown((String)$quote->getBillingAddress()->getCity());
        if (Mage::getStoreConfig('byjuno/api/plugincheckouttype', Mage::app()->getStore()) == 'amasty' && !empty($_POST["billing"]["city"])) {
            $request->setTown($_POST["billing"]["city"]);
        }

        $request->setFax((String)trim($quote->getBillingAddress()->getFax(), '-'));
        if (Mage::getStoreConfig('byjuno/api/plugincheckouttype', Mage::app()->getStore()) == 'amasty' && !empty($_POST["billing"]["fax"])) {
            $request->setFax(trim($_POST["billing"]["fax"], '-'));
        }

        $request->setLanguage((String)substr(Mage::app()->getLocale()->getLocaleCode(), 0, 2));
        if ($quote->getBillingAddress()->getCompany()) {
            $request->setCompanyName1($quote->getBillingAddress()->getCompany());
        }
        if (Mage::getStoreConfig('byjuno/api/plugincheckouttype', Mage::app()->getStore()) == 'amasty' && !empty($_POST["billing"]["company"])) {
            $request->setCompanyName1(trim($_POST["billing"]["company"], '-'));
        }


        $request->setTelephonePrivate((String)trim($quote->getBillingAddress()->getTelephone(), '-'));
        if (Mage::getStoreConfig('byjuno/api/plugincheckouttype', Mage::app()->getStore()) == 'amasty' && !empty($_POST["billing"]["telephone"])) {
            $request->setTelephonePrivate(trim($_POST["billing"]["telephone"], '-'));
        }

        $request->setEmail((String)$quote->getBillingAddress()->getEmail());
        if (Mage::getStoreConfig('byjuno/api/plugincheckouttype', Mage::app()->getStore()) == 'amasty' && !empty($_POST["billing"]["email"])) {
            $request->setEmail((String)$_POST["billing"]["email"]);
        }

        Mage::getSingleton('checkout/session')->setData('gender_amasty', '');
        if (Mage::getStoreConfig('byjuno/api/plugincheckouttype', Mage::app()->getStore()) == 'amasty') {
            $g = isset($_POST["billing"]["gender"]) ? $_POST["billing"]["gender"] : '';
            $request->setGender($g);
            Mage::getSingleton('checkout/session')->setData('gender_amasty', $g);
        }
        if (Mage::getStoreConfig('byjuno/api/plugincheckouttype', Mage::app()->getStore()) == 'amasty' && !empty($_POST["billing"]["prefix"])) {
            if (strtolower($_POST["billing"]["prefix"]) == 'herr') {
                $request->setGender('1');
                Mage::getSingleton('checkout/session')->setData('gender_amasty', '1');
            } else if (strtolower($_POST["billing"]["prefix"]) == 'frau') {
                $request->setGender('2');
                Mage::getSingleton('checkout/session')->setData('gender_amasty', '2');
            }
        }

        $extraInfo["Name"] = 'ORDERCLOSED';
        $extraInfo["Value"] = 'NO';
        $request->setExtraInfo($extraInfo);

        $extraInfo["Name"] = 'ORDERAMOUNT';
        $extraInfo["Value"] = number_format($quote->getGrandTotal(), 2, '.', '');
        $request->setExtraInfo($extraInfo);

        $extraInfo["Name"] = 'ORDERCURRENCY';
        $extraInfo["Value"] = $quote->getBaseCurrencyCode();
        $request->setExtraInfo($extraInfo);

        $extraInfo["Name"] = 'IP';
        $extraInfo["Value"] = $this->getClientIp();
        $request->setExtraInfo($extraInfo);

        $sesId = Mage::getSingleton('checkout/session')->getData("byjuno_session_id");
        if (Mage::getStoreConfig('byjuno/api/tmxenabled', Mage::app()->getStore()) == 'enable' && !empty($sesId)) {
            $extraInfo["Name"] = 'DEVICE_FINGERPRINT_ID';
            $extraInfo["Value"] = Mage::getSingleton('checkout/session')->getData("byjuno_session_id");
            $request->setExtraInfo($extraInfo);
        }

        /* shipping information */
        if (!$quote->isVirtual()) {
            $extraInfo["Name"] = 'DELIVERY_FIRSTNAME';
            $extraInfo["Value"] = $quote->getShippingAddress()->getFirstname();
            if (Mage::getStoreConfig('byjuno/api/plugincheckouttype', Mage::app()->getStore()) == 'amasty' && empty($_POST["shipping"]["same_as_billing"])) {
                if (!empty($_POST["shipping"]["firstname"])) {
                    $extraInfo["Value"] = $_POST["shipping"]["firstname"];
                }
            }
            $request->setExtraInfo($extraInfo);

            $extraInfo["Name"] = 'DELIVERY_LASTNAME';
            $extraInfo["Value"] = $quote->getShippingAddress()->getLastname();
            if (Mage::getStoreConfig('byjuno/api/plugincheckouttype', Mage::app()->getStore()) == 'amasty' && empty($_POST["shipping"]["same_as_billing"])) {
                if (!empty($_POST["shipping"]["lastname"])) {
                    $extraInfo["Value"] = $_POST["shipping"]["lastname"];
                }
            }
            $request->setExtraInfo($extraInfo);

            $extraInfo["Name"] = 'DELIVERY_FIRSTLINE';
            $extraInfo["Value"] = trim($quote->getShippingAddress()->getStreetFull());
            if (Mage::getStoreConfig('byjuno/api/plugincheckouttype', Mage::app()->getStore()) == 'amasty' && empty($_POST["shipping"]["same_as_billing"])) {
                $extraInfo["Value"] = '';
                if (!empty($_POST["shipping"]["street"][0])) {
                    $extraInfo["Value"] = $_POST["shipping"]["street"][0];
                }
                if (!empty($_POST["shipping"]["street"][1])) {
                    $extraInfo["Value"] .= ' '.$_POST["shipping"]["street"][1];
                }
                $extraInfo["Value"] = trim($extraInfo["Value"]);
            }
            $request->setExtraInfo($extraInfo);

            $extraInfo["Name"] = 'DELIVERY_HOUSENUMBER';
            $extraInfo["Value"] = '';
            $request->setExtraInfo($extraInfo);

            $extraInfo["Name"] = 'DELIVERY_COUNTRYCODE';
            $extraInfo["Value"] = strtoupper($quote->getShippingAddress()->getCountry());
            if (Mage::getStoreConfig('byjuno/api/plugincheckouttype', Mage::app()->getStore()) == 'amasty' && empty($_POST["shipping"]["same_as_billing"])) {
                if (!empty($_POST["shipping"]["country_id"])) {
                    $extraInfo["Value"] = $_POST["shipping"]["country_id"];
                }
            }
            $request->setExtraInfo($extraInfo);
            $extraInfo["Name"] = 'DELIVERY_POSTCODE';
            $extraInfo["Value"] = $quote->getShippingAddress()->getPostcode();
            if (Mage::getStoreConfig('byjuno/api/plugincheckouttype', Mage::app()->getStore()) == 'amasty' && empty($_POST["shipping"]["same_as_billing"])) {
                if (!empty($_POST["shipping"]["postcode"])) {
                    $extraInfo["Value"] = $_POST["shipping"]["postcode"];
                }
            }
            $request->setExtraInfo($extraInfo);

            $extraInfo["Name"] = 'DELIVERY_TOWN';
            $extraInfo["Value"] = $quote->getShippingAddress()->getCity();
            if (Mage::getStoreConfig('byjuno/api/plugincheckouttype', Mage::app()->getStore()) == 'amasty') {
                if (!empty($_POST["shipping"]["same_as_billing"]) && $_POST["shipping"]["same_as_billing"] == '1' && !empty($_POST["billing"]["city"])) {
                    $extraInfo["Value"] = $_POST["billing"]["city"];
                } else if (!empty($_POST["shipping"]["city"])) {
                    $extraInfo["Value"] = $_POST["shipping"]["city"];
                }
            }
            $request->setExtraInfo($extraInfo);

            if ($quote->getShippingAddress()->getCompany() != '' && Mage::getStoreConfig('byjuno/api/businesstobusiness', Mage::app()->getStore()) == 'enable') {
                $extraInfo["Name"] = 'DELIVERY_COMPANYNAME';
                $extraInfo["Value"] = $quote->getShippingAddress()->getCompany();
                $request->setExtraInfo($extraInfo);
            }
        }

		$extraInfo["Name"] = 'CONNECTIVTY_MODULE';
		$extraInfo["Value"] = 'Byjuno Magento module 4.0.0';
		$request->setExtraInfo($extraInfo);
        return $request;

    }

    private function getHelper(){
        return Mage::helper('byjuno');
    }

    public function orderStatusChange(Varien_Event_Observer $observer)
    {
        $order = $observer->getOrder();
        $payment = $order->getPayment();
        $methodInstance = $payment->getMethodInstance();
        if (!($methodInstance instanceof Byjuno_Cdp_Model_Standardinvoice) || !($methodInstance instanceof Byjuno_Cdp_Model_Standardinstallment)) {
            return;
        }
        $stateProcessing = $order::STATE_PROCESSING;
        $stateComplete = $order::STATE_COMPLETE;
        // Only trigger when an order enters processing state.
        if ($order->getState() == $stateProcessing && $order->getOrigData('state') != $stateProcessing) {
            //
          //  var_dump($order->getState(), $order->getOrigData('state'));
        }
        //var_dump(get_class($methodInstance), $order->getState(), $order->getOrigData('state'));
        //exit();
    }

    public function checkandcall(Varien_Event_Observer $observer){
        $methodInstance = $observer->getEvent()->getMethodInstance();
        if (!($methodInstance instanceof Byjuno_Cdp_Model_Standardinvoice) || !($methodInstance instanceof Byjuno_Cdp_Model_Standardinstallment)) {
            return;
        }
        return;/*
        if (Mage::getStoreConfig('payment/cdp/active', Mage::app()->getStore()) == "0") {
            $observer->getEvent()->getResult()->isAvailable = false;
            return;
        }

        if ($methodInstance instanceof Byjuno_Cdp_Model_Standardinvoice) {
            if (Mage::getStoreConfig('payment/cdp/active', Mage::app()->getStore()) == "0") {
                $observer->getEvent()->getResult()->isAvailable = false;
                return;
            }
        }

        if ($methodInstance instanceof Byjuno_Cdp_Model_Standardinstallment) {
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
                $observer->getEvent()->getResult()->isAvailable = false;
                return;
            }
        }

        return;
        */
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