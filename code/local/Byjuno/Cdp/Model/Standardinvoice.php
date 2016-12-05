<?php

/**
 * Created by PhpStorm.
 * User: isgn
 * Date: 25.01.2016
 * Time: 18:35
 */
class Byjuno_Cdp_Model_Standardinvoice extends Mage_Payment_Model_Method_Abstract
{
    protected $_code = 'cdp_invoice';

    protected $_formBlockType = 'byjuno/form_byjunoinvoice';
    protected $_infoBlockType = 'byjuno/info_byjunoinvoice';
    protected $_isInitializeNeeded = true;
    protected $_canUseInternal = false;
    protected $_canUseForMultishipping = false;
    protected $_canRefund = true;
    protected $_canCapture = true;
    protected $_canCapturePartial = true;
    protected $_canVoid = true;
    protected $_canAuthorize = true;
    protected $_canRefundInvoicePartial = true;


    protected $_savedUser = Array(
        "FirstName" => "",
        "LastName" => "",
        "FirstLine" => "",
        "CountryCode" => "",
        "PostCode" => "",
        "Town" => "",
        "CompanyName1",
        "DateOfBirth",
        "Email",
        "Fax",
        "TelephonePrivate",
        "TelephoneOffice",
        "Gender",
        "DELIVERY_FIRSTNAME",
        "DELIVERY_LASTNAME",
        "DELIVERY_FIRSTLINE",
        "DELIVERY_HOUSENUMBER",
        "DELIVERY_COUNTRYCODE",
        "DELIVERY_POSTCODE",
        "DELIVERY_TOWN",
        "DELIVERY_COMPANYNAME"
    );

    public function validate()
    {
        parent::validate();
        /* @var $info Mage_Sales_Model_Quote_Payment */
        $info = $this->getInfoInstance();
        $paymentInfo = $this->getInfoInstance();
        if ($paymentInfo instanceof Mage_Sales_Model_Order_Payment) {
            $q = $paymentInfo->getOrder();
        } else {
            $q = $paymentInfo->getQuote();
        }
        if (Mage::getStoreConfig('payment/cdp/country_phone_validation', Mage::app()->getStore()) == '1') {
            $pattern = "/^[0-9]{4}$/";
            if (strtolower($q->getBillingAddress()->getCountry()) == 'ch' && !preg_match($pattern, $q->getBillingAddress()->getPostcode())) {
                Mage::throwException(Mage::getStoreConfig('payment/cdp/postal_code_wrong', Mage::app()->getStore()) . ": " . $q->getBillingAddress()->getPostcode());
            }
            if (!preg_match("/^[0-9\+\(\)\s]+$/", $q->getBillingAddress()->getTelephone())) {
                Mage::throwException(Mage::getStoreConfig('payment/cdp/telephone_code_wrong', Mage::app()->getStore()) . ": " . $q->getBillingAddress()->getTelephone());
            }
        }
        return $this;
    }

    public function processCreditmemo($creditmemo, $payment)
    {
        $creditmemo->setTransactionId(1);
        return $this;
    }

    public function capture(Varien_Object $payment, $amount)
    {
        $payment->setTransactionId($payment->getParentTransactionId().'-capture');
        //$transaction = $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH, null, true, "");
        //$transaction->setIsClosed(true);
        return $this;
    }

    /* @var $invoice Mage_Sales_Model_Order_Invoice */
    /* @var $payment Mage_Sales_Model_Order_Payment */
    public function processInvoice($invoice, $payment)
    {
        if (Mage::getStoreConfig('payment/cdp/byjunos4transacton', Mage::app()->getStore()) == '0') {
            return $this;
        }
        if ($payment->getAdditionalInformation("s3_ok") == null || $payment->getAdditionalInformation("s3_ok") == 'false') {
            Mage::throwException(Mage::helper('payment')->__(Mage::getStoreConfig('payment/cdp/byjuno_s4_fail', Mage::app()->getStore())). " (error code: S3_NOT_CREATED)");
        }
        $entityType = Mage::getModel('eav/entity_type')->loadByCode('invoice');
        $invoiceId = $entityType->fetchNewIncrementId($invoice->getStoreId());
        $order = $invoice->getOrder();
        $webshopProfileId = $order->getPayment()->getAdditionalInformation("webshop_profile_id");
        if (!isset($webshopProfileId) || $webshopProfileId == "") {
            $webshopProfileId = $order->getStoreId();
        }
        $webshopProfile = Mage::getModel('core/store')->load($webshopProfileId);
        $invoice->setIncrementId($invoiceId);
        /* @var $request Byjuno_Cdp_Helper_Api_Classes_ByjunoS4Request */
        $request = $this->getHelper()->CreateMagentoShopRequestS4Paid($order, $invoice, $webshopProfile);
        $ByjunoRequestName = 'Byjuno S4';
        $xml = $request->createRequest();
        $byjunoCommunicator = new Byjuno_Cdp_Helper_Api_Classes_ByjunoCommunicator();
        $mode = Mage::getStoreConfig('payment/cdp/currentmode', Mage::app()->getStore());
        if ($mode == 'production') {
            $byjunoCommunicator->setServer('live');
        } else {
            $byjunoCommunicator->setServer('test');
        }
        $response = $byjunoCommunicator->sendS4Request($xml, (int)Mage::getStoreConfig('payment/cdp/timeout', Mage::app()->getStore()));
        $byjunoResponse = new Byjuno_Cdp_Helper_Api_Classes_ByjunoS4Response();
        if ($response) {
            $byjunoResponse->setRawResponse($response);
            $byjunoResponse->processResponse();
            $status = $byjunoResponse->getProcessingInfoClassification();
            $this->getHelper()->saveS4Log($order, $request, $xml, $response, $status, $ByjunoRequestName);
        } else {
            $status = "ERR";
            $this->getHelper()->saveS4Log($order, $request, $xml, "empty response", $status, $ByjunoRequestName);
        }
        if ($status == 'ERR') {
            Mage::throwException(Mage::helper('payment')->__(Mage::getStoreConfig('payment/cdp/byjuno_s4_fail', Mage::app()->getStore())));
        } else {
            $this->getHelper()->sendEmailInvoice($invoice);
        }
        return $this;
    }

    public function refund(Varien_Object $payment, $requestedAmount)
    {
        if (Mage::getStoreConfig('payment/cdp/byjunos5transacton', Mage::app()->getStore()) == '0') {
            return $this;
        }
        /* @var $payment Mage_Sales_Model_Order_Payment */
        $order = $payment->getOrder();
        $webshopProfileId = $payment->getAdditionalInformation("webshop_profile_id");
        if (!isset($webshopProfileId) || $webshopProfileId == "") {
            $webshopProfileId = $order->getStoreId();
        }
        $webshopProfile = Mage::getModel('core/store')->load($webshopProfileId);
        /* @var $memo Mage_Sales_Model_Order_Creditmemo */
        $memo = $payment->getCreditmemo();
        $incoiceId = $memo->getInvoice()->getIncrementId();
        /* @var $request Byjuno_Cdp_Helper_Api_Classes_ByjunoS4Request */
        $request = $this->getHelper()->CreateMagentoShopRequestS5Paid($order, $requestedAmount, "REFUND", $webshopProfile, $incoiceId);
        $ByjunoRequestName = 'Byjuno S5';
        $xml = $request->createRequest();
        $byjunoCommunicator = new Byjuno_Cdp_Helper_Api_Classes_ByjunoCommunicator();
        $mode = Mage::getStoreConfig('payment/cdp/currentmode', Mage::app()->getStore());
        if ($mode == 'production') {
            $byjunoCommunicator->setServer('live');
        } else {
            $byjunoCommunicator->setServer('test');
        }
        $response = $byjunoCommunicator->sendS4Request($xml, (int)Mage::getStoreConfig('payment/cdp/timeout', Mage::app()->getStore()));
        $byjunoResponse = new Byjuno_Cdp_Helper_Api_Classes_ByjunoS4Response();
        if ($response) {
            $byjunoResponse->setRawResponse($response);
            $byjunoResponse->processResponse();
            $status = $byjunoResponse->getProcessingInfoClassification();
            $this->getHelper()->saveS4Log($order, $request, $xml, $response, $status, $ByjunoRequestName);
        } else {
            $status = 'ERR';
            $this->getHelper()->saveS4Log($order, $request, $xml, "empty response", $status, $ByjunoRequestName);
        }
        if ($status == 'ERR') {
            Mage::throwException(Mage::helper('payment')->__(Mage::getStoreConfig('payment/cdp/byjuno_s5_fail', Mage::app()->getStore())));
        } else {
            $this->getHelper()->sendEmailCreditMemo($memo);
        }
        /* @var $payent Mage_Sales_Model_Order_Payment */
        $payment->setTransactionId($payment->getParentTransactionId().'-refund');
        $transaction = $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_VOID, null, true, "Transaction refunced");
        $transaction->setIsClosed(true);
        $transaction->save();
        return $this;
    }

    /* @var $payment Mage_Sales_Model_Order_Payment */
    public function cancel(Varien_Object $payment)
    {
        if (Mage::getStoreConfig('payment/cdp/byjunos5transacton', Mage::app()->getStore()) == '0') {
            return $this;
        }
        /* @var $order Mage_Sales_Model_Order */
        $order = $payment->getOrder();
        $webshopProfileId = $payment->getAdditionalInformation("webshop_profile_id");
        if (!isset($webshopProfileId) || $webshopProfileId == "") {
            $webshopProfileId = $order->getStoreId();
        }
        $webshopProfile = Mage::getModel('core/store')->load($webshopProfileId);
        /* @var $request Byjuno_Cdp_Helper_Api_Classes_ByjunoS4Request */
        $request = $this->getHelper()->CreateMagentoShopRequestS5Paid($order, $order->getTotalDue(), "EXPIRED", $webshopProfile);
        $ByjunoRequestName = 'Byjuno S5';
        $xml = $request->createRequest();
        $byjunoCommunicator = new Byjuno_Cdp_Helper_Api_Classes_ByjunoCommunicator();
        $mode = Mage::getStoreConfig('payment/cdp/currentmode', Mage::app()->getStore());
        if ($mode == 'production') {
            $byjunoCommunicator->setServer('live');
        } else {
            $byjunoCommunicator->setServer('test');
        }
        $response = $byjunoCommunicator->sendS4Request($xml, (int)Mage::getStoreConfig('payment/cdp/timeout', Mage::app()->getStore()));
        $byjunoResponse = new Byjuno_Cdp_Helper_Api_Classes_ByjunoS4Response();
        if ($response) {
            $byjunoResponse->setRawResponse($response);
            $byjunoResponse->processResponse();
            $status = $byjunoResponse->getProcessingInfoClassification();
            $this->getHelper()->saveS4Log($order, $request, $xml, $response, $status, $ByjunoRequestName);
        } else {
            $status = 'ERR';
            $this->getHelper()->saveS4Log($order, $request, $xml, "empty response", $status, $ByjunoRequestName);
        }
        if ($status == 'ERR') {
            Mage::throwException(Mage::helper('payment')->__(Mage::getStoreConfig('payment/cdp/byjuno_s5_fail', Mage::app()->getStore())));
        }
        $transaction = $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_VOID, null, true, "Transaction canceled");
        $transaction->setIsClosed(true);
        $transaction->save();
        return $this;
    }

    public function isInCheckoutProcess() {
        $places = Mage::getStoreConfig('payment/cdp/cdpplaces', Mage::app()->getStore());
        $pl = explode("\n", $places);
        foreach ($pl as $place) {
            $segments = explode(',', trim($place));
            if (count($segments) >= 2) {
                list($moduleName, $controllerName, $actionName) = $segments;
				if ($actionName == null) {
					$actionName = 'saveShippingMethod';
				}
                if (Mage::app()->getRequest()->getModuleName() == trim($moduleName) &&
                    Mage::app()->getRequest()->getControllerName() == trim($controllerName) &&
                    Mage::app()->getRequest()->getActionName() == trim($actionName)
                ) {
                    return true;
                }
            }

        }
        return false;
    }

    public function isTheSame(Byjuno_Cdp_Helper_Api_Classes_ByjunoRequest $request) {

        if ($request->getFirstName() != $this->_savedUser["FirstName"]
            || $request->getLastName() != $this->_savedUser["LastName"]
            || $request->getFirstLine() != $this->_savedUser["FirstLine"]
            || $request->getCountryCode() != $this->_savedUser["CountryCode"]
            || $request->getPostCode() != $this->_savedUser["PostCode"]
            || $request->getTown() != $this->_savedUser["Town"]
            || $request->getCompanyName1() != $this->_savedUser["CompanyName1"]
            || $request->getDateOfBirth() != $this->_savedUser["DateOfBirth"]
            || $request->getEmail() != $this->_savedUser["Email"]
            || $request->getFax() != $this->_savedUser["Fax"]
            || $request->getTelephonePrivate() != $this->_savedUser["TelephonePrivate"]
            || $request->getTelephoneOffice() != $this->_savedUser["TelephoneOffice"]
            || $request->getGender() != $this->_savedUser["Gender"]
            || $request->getExtraInfoByKey("DELIVERY_FIRSTNAME") != $this->_savedUser["DELIVERY_FIRSTNAME"]
            || $request->getExtraInfoByKey("DELIVERY_LASTNAME") != $this->_savedUser["DELIVERY_LASTNAME"]
            || $request->getExtraInfoByKey("DELIVERY_FIRSTLINE") != $this->_savedUser["DELIVERY_FIRSTLINE"]
            || $request->getExtraInfoByKey("DELIVERY_HOUSENUMBER") != $this->_savedUser["DELIVERY_HOUSENUMBER"]
            || $request->getExtraInfoByKey("DELIVERY_COUNTRYCODE") != $this->_savedUser["DELIVERY_COUNTRYCODE"]
            || $request->getExtraInfoByKey("DELIVERY_POSTCODE") != $this->_savedUser["DELIVERY_POSTCODE"]
            || $request->getExtraInfoByKey("DELIVERY_TOWN") != $this->_savedUser["DELIVERY_TOWN"]
            || $request->getExtraInfoByKey("DELIVERY_COMPANYNAME") != $this->_savedUser["DELIVERY_COMPANYNAME"]
        ) {
            return false;
        }
        return true;
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
        $payments = Mage::getStoreConfig('payment/cdp/byjuno_invoice_payments', Mage::app()->getStore());
        $active = false;
        $plns = explode(",", $payments);
        foreach ($plns as $val) {
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

    public function CDPRequest($quote) {
        if (Mage::getStoreConfig('payment/cdp/cdpbeforeshow', Mage::app()->getStore()) == '1' && $this->isInCheckoutProcess() && $quote->getShippingAddress()->getFirstname() != null) {
            $session = Mage::getSingleton('checkout/session');
            $theSame = $session->getData("isTheSame");
            $CDPStatus = $session->getData("CDPStatus");
            if ($theSame != null) {
                $this->_savedUser = $theSame;
            }
            try {
                $request = $this->getHelper()->CreateMagentoShopRequestCreditCheck($quote);
                if ($CDPStatus != null && intval($CDPStatus) != 2 && $this->isTheSame($request))
                {
                    return false;
                }
                if (!$this->isTheSame($request) || $CDPStatus == null) {
                    $ByjunoRequestName = "Credit check request";
                    if ($request->getCompanyName1() != '' && Mage::getStoreConfig('payment/cdp/businesstobusiness', Mage::app()->getStore()) == 'enable') {
                        $ByjunoRequestName = "Credit check request for Company";
                        $xml = $request->createRequestCompany();
                    } else {
                        $xml = $request->createRequest();
                    }
                    $byjunoCommunicator = new Byjuno_Cdp_Helper_Api_Classes_ByjunoCommunicator();
                    $mode = Mage::getStoreConfig('payment/cdp/currentmode', Mage::app()->getStore());
                    if ($mode == 'production') {
                        $byjunoCommunicator->setServer('live');
                    } else {
                        $byjunoCommunicator->setServer('test');
                    }
                    $response = $byjunoCommunicator->sendRequest($xml, (int)Mage::getStoreConfig('payment/cdp/timeout', Mage::app()->getStore()));
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
                    $this->_savedUser = Array(
                        "FirstName" => $request->getFirstName(),
                        "LastName" => $request->getLastName(),
                        "FirstLine" => $request->getFirstLine(),
                        "CountryCode" => $request->getCountryCode(),
                        "PostCode" => $request->getPostCode(),
                        "Town" => $request->getTown(),
                        "CompanyName1" => $request->getCompanyName1(),
                        "DateOfBirth" => $request->getDateOfBirth(),
                        "Email" => $request->getEmail(),
                        "Fax" => $request->getFax(),
                        "TelephonePrivate" => $request->getTelephonePrivate(),
                        "TelephoneOffice" => $request->getTelephoneOffice(),
                        "Gender" => $request->getGender(),
                        "DELIVERY_FIRSTNAME" => $request->getExtraInfoByKey("DELIVERY_FIRSTNAME"),
                        "DELIVERY_LASTNAME" => $request->getExtraInfoByKey("DELIVERY_LASTNAME"),
                        "DELIVERY_FIRSTLINE" => $request->getExtraInfoByKey("DELIVERY_FIRSTLINE"),
                        "DELIVERY_HOUSENUMBER" => $request->getExtraInfoByKey("DELIVERY_HOUSENUMBER"),
                        "DELIVERY_COUNTRYCODE" => $request->getExtraInfoByKey("DELIVERY_COUNTRYCODE"),
                        "DELIVERY_POSTCODE" => $request->getExtraInfoByKey("DELIVERY_POSTCODE"),
                        "DELIVERY_TOWN" => $request->getExtraInfoByKey("DELIVERY_TOWN"),
                        "DELIVERY_COMPANYNAME" => $request->getExtraInfoByKey("DELIVERY_COMPANYNAME")
                    );
                    $session->setData("isTheSame", $this->_savedUser);
                    $session->setData("CDPStatus", $status);
                    if ($status != 2) {
                        return false;
                    }
                }
            } catch (Exception $e) {
            }
        }
        return null;
    }

    public function assignData($data)
    {
        /* @var $info Mage_Sales_Model_Quote_Payment */
        $info = $this->getInfoInstance();
        if (is_array($data)) {
            if (isset($data["invoice_payment_plan"])) {
                $info->setAdditionalInformation("payment_plan", $data["invoice_payment_plan"]);
            }
            if (Mage::getStoreConfig('payment/cdp/gender_enable', Mage::app()->getStore()) == '1') {
                if (isset($data["invoice_gender"])) {
                    $info->setAdditionalInformation("gender_custom", $data["invoice_gender"]);
                }
            }
            if (Mage::getStoreConfig('payment/cdp/birthday_enable', Mage::app()->getStore()) == '1') {
                if (isset($data["invoice_month"]) && isset($data["invoice_day"]) && isset($data["invoice_year"])) {
                    $dob = intval($data["invoice_day"]).'.'.intval($data["invoice_month"]).'.'.intval($data["invoice_year"]);
                    $info->setAdditionalInformation("dob_custom", $dob);
                }
            }
            if (isset($data["invoice_payment_send"])) {
                $send = $data["invoice_payment_send"];
                $info->setAdditionalInformation("payment_send", $send);
                if ($send == 'postal') {
                    $sentTo = (String)$info->getQuote()->getBillingAddress()->getStreetFull().', '.(String)$info->getQuote()->getBillingAddress()->getCity().', '.(String)$info->getQuote()->getBillingAddress()->getPostcode();
                } else {
                    $sentTo = $info->getQuote()->getBillingAddress()->getEmail();
                }
                $info->setAdditionalInformation("payment_send_to", $sentTo);
            }
        }
        elseif ($data instanceof Varien_Object) {
            if ($data->getInvoicePaymentPlan()) {
                $info->setAdditionalInformation("payment_plan", $data->getInvoicePaymentPlan());
            }
            if (Mage::getStoreConfig('payment/cdp/gender_enable', Mage::app()->getStore()) == '1') {
                if ($data->getInvoiceGender()) {
                    $info->setAdditionalInformation("gender_custom", $data->getInvoiceGender());
                }
            }
            if (Mage::getStoreConfig('payment/cdp/birthday_enable', Mage::app()->getStore()) == '1') {
                if ($data->getInvoiceMonth() && $data->getInvoiceDay() && $data->getInvoiceYear()) {
                    $dob = intval($data->getInvoiceDay()).'.'.intval($data->getInvoiceMonth()).'.'.intval($data->getInvoiceYear());
                    $info->setAdditionalInformation("dob_custom", $dob);
                }
            }
            if ($data->getInvoicePaymentSend()) {
                $send = $data->getInvoicePaymentSend();
                $info->setAdditionalInformation("payment_send", $send);
                if ($send == 'postal') {
                    $sentTo = (String)$info->getQuote()->getBillingAddress()->getStreetFull().', '.(String)$info->getQuote()->getBillingAddress()->getCity().', '.(String)$info->getQuote()->getBillingAddress()->getPostcode();
                } else {
                    $sentTo = $info->getQuote()->getBillingAddress()->getEmail();
                }
                $info->setAdditionalInformation("payment_send_to", $sentTo);
            }
        }
        $info->setAdditionalInformation("s3_ok", 'false');
        $info->setAdditionalInformation("webshop_profile_id", Mage::app()->getStore()->getId());
        return $this;
    }

    public function getTitle()
    {
        return Mage::getStoreConfig('payment/cdp/title_invoice', Mage::app()->getStore());
    }

    public function getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * @return Byjuno_Cdp_Helper_Data
     */
    private function getHelper()
    {
        return Mage::helper('byjuno');
    }

    public function getOrderPlaceRedirectUrl()
    {
        $session = Mage::getSingleton('checkout/session');
        /* @var $quote Mage_Sales_Model_Quote */
        $quote = Mage::getSingleton('checkout/cart')->getQuote();
        /* @var $order Mage_Sales_Model_Order */
        /* @var $ordSess Mage_Sales_Model_Order */
        $ordSess = Mage::getModel('sales/order');

        $order = $ordSess->loadByIncrementId($quote->getReservedOrderId());
        $payment = $order->getPayment();
        $paymentMethod = $payment->getMethod();
        $paymentPlan = $payment->getAdditionalInformation("payment_plan");
        $paymentSend = $payment->getAdditionalInformation("payment_send");

        $gender_custom = '';
        if (Mage::getStoreConfig('payment/cdp/gender_enable', Mage::app()->getStore()) == '1') {
            $gender_custom = $payment->getAdditionalInformation("gender_custom");
        }
        $dob_custom = '';
        if (Mage::getStoreConfig('payment/cdp/birthday_enable', Mage::app()->getStore()) == '1') {
            $dob_custom = $payment->getAdditionalInformation("dob_custom");
        }

        $request = $this->getHelper()->CreateMagentoShopRequestOrder($order, $paymentMethod, $paymentPlan, $paymentSend, $gender_custom, $dob_custom);

        $ByjunoRequestName = "Order request";
        $requestType = 'b2c';
        if ($request->getCompanyName1() != '' && Mage::getStoreConfig('payment/cdp/businesstobusiness', Mage::app()->getStore()) == 'enable') {
            $ByjunoRequestName = "Order request for Company";
            $requestType = 'b2b';
            $xml = $request->createRequestCompany();
        } else {
            $xml = $request->createRequest();
        }
        $byjunoCommunicator = new Byjuno_Cdp_Helper_Api_Classes_ByjunoCommunicator();
        $mode = Mage::getStoreConfig('payment/cdp/currentmode', Mage::app()->getStore());
        if ($mode == 'production') {
            $byjunoCommunicator->setServer('live');
        } else {
            $byjunoCommunicator->setServer('test');
        }
        $response = $byjunoCommunicator->sendRequest($xml, (int)Mage::getStoreConfig('payment/cdp/timeout', Mage::app()->getStore()));
        $status = 0;
        $byjunoResponse = new Byjuno_Cdp_Helper_Api_Classes_ByjunoResponse();
        if ($response) {
            $byjunoResponse->setRawResponse($response);
            $byjunoResponse->processResponse();
            $status = (int)$byjunoResponse->getCustomerRequestStatus();
            $session->setData("byjuno_transaction", $byjunoResponse->getTransactionNumber());
            $this->getHelper()->saveLog($quote, $request, $xml, $response, $status, $ByjunoRequestName);
            if (intval($status) > 15) {
                $status = 0;
            }
            $trxId = $byjunoResponse->getResponseId();
        } else {
            $this->getHelper()->saveLog($quote, $request, $xml, "empty response", "0", $ByjunoRequestName);
            $trxId = "empty";
        }
        $payment->setTransactionId($trxId);
        $payment->setParentTransactionId($payment->getTransactionId());
        $transaction = $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH, null, true, "");
        if ($status == 2) {
            $transaction->setIsClosed(false);
        } else {
            $transaction->setIsClosed(true);
        }
        $transaction->save();
        $payment->save();
        $session->setData("intrum_status", $status);
        $session->setData("intrum_request_type", $requestType);
        $session->setData("intrum_order", $order->getId());
        if ($status == 2) {
            return Mage::getUrl('cdp/standard/result');
        } else if ($status == 0) {
            $order->cancel()->save();
            $session->addError($this->getHelper()->getByjunoErrorMessage($status, $requestType));
            return Mage::getUrl('cdp/standard/cancel');
        } else {
            $order->cancel()->save();
            $session->addError($this->getHelper()->getByjunoErrorMessage($status, $requestType));
            return Mage::getUrl('cdp/standard/cancel');
        }
    }

}