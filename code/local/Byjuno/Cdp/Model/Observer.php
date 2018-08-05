<?php

class Byjuno_Cdp_Model_Observer extends Mage_Core_Model_Abstract {

    protected $quote   = null;
    protected $address = null;
    protected $byjuno_status = null;
    protected $credit_limit = 'credit_limit';
    protected $credit_balance = 'credit_balance';
    protected $credit_byjuno_balance = 'credit_byjuno_balance';
    protected $overwrite_credit_check = 'credit_check';

    private function getHelper()
    {
        return Mage::helper('byjuno');
    }

    public function orderStatusChange(Varien_Event_Observer $observer)
    {
        $order = $observer->getOrder();
        $payment = $order->getPayment();
        $methodInstance = $payment->getMethodInstance();
        if (!($methodInstance instanceof Byjuno_Cdp_Model_Standardinvoice) && !($methodInstance instanceof Byjuno_Cdp_Model_Standardinstallment)) {
            return;
        }
    }

    public function checkandcall(Varien_Event_Observer $observer){
        $methodInstance = $observer->getEvent()->getMethodInstance();
        if (!($methodInstance instanceof Byjuno_Cdp_Model_Standardinvoice) || !($methodInstance instanceof Byjuno_Cdp_Model_Standardinstallment)) {
            return;
        }
        return;
    }

    protected function getQuote(){

        if($this->quote){
            return $this->quote;
        }
        throw new Exception('quote not set');
    }

    public function byjunoProcessShipping(Varien_Event_Observer $observer)
    {
        /* @var $shipment Mage_Sales_Model_Order_Shipment */
        $shipment = $observer->getEvent()->getShipment();
        if ($shipment) {
            $order = $shipment->getOrder();
            /* @var $invoices Mage_Sales_Model_Resource_Order_Invoice_Collection */
            $invoices = $order->getInvoiceCollection();
            $coountInv = count($invoices);
            if ($coountInv > 0) {
                foreach ($invoices as $invoice) {
                    $request_start = date('Y-m-d G:i:s');
                    /* @var $invoice Mage_Sales_Model_Order_Invoice */
                    $payment = $order->getPayment();
                    $webshopProfileId = $payment->getAdditionalInformation("webshop_profile_id");
                    if (!isset($webshopProfileId) || $webshopProfileId == "") {
                        $webshopProfileId = $order->getStoreId();
                    }
                    if (Mage::getStoreConfig('payment/cdp/byjunos4transacton', $webshopProfileId) == '0') {
                        return;
                    }
                    if (Mage::getStoreConfig('payment/cdp/byjunos4transactonactivationplace', $webshopProfileId) == 'shipping') {
                        if ($payment->getAdditionalInformation("s3_ok") == null || $payment->getAdditionalInformation("s3_ok") == 'false') {
                            Mage::throwException(Mage::helper('payment')->__(Mage::getStoreConfig('payment/cdp/byjuno_s4_fail', $webshopProfileId)) . " (error code: S3_NOT_CREATED)");
                        }
                        $entityType = Mage::getModel('eav/entity_type')->loadByCode('invoice');

                        $webshopProfile = Mage::getModel('core/store')->load($webshopProfileId);
                        $invoiceId = $invoice->getIncrementId();
                        if ($invoiceId == null) {
                            $invoiceId = $entityType->fetchNewIncrementId($invoice->getStoreId());
                            $invoice->setIncrementId($invoiceId);
                        }

                        /* @var $request Byjuno_Cdp_Helper_Api_Classes_ByjunoS4Request */
                        $request = $this->getHelper()->CreateMagentoShopRequestS4Paid($order, $invoice, $webshopProfile);
                        $ByjunoRequestName = 'Byjuno S4';
                        $xml = $request->createRequest();
                        $byjunoCommunicator = new Byjuno_Cdp_Helper_Api_Classes_ByjunoCommunicator();
                        $mode = Mage::getStoreConfig('payment/cdp/currentmode', $webshopProfileId);
                        if ($mode == 'production') {
                            $byjunoCommunicator->setServer('live');
                        } else {
                            $byjunoCommunicator->setServer('test');
                        }
                        $response = $byjunoCommunicator->sendS4Request($xml, (int)Mage::getStoreConfig('payment/cdp/timeout', $webshopProfileId));
                        $byjunoResponse = new Byjuno_Cdp_Helper_Api_Classes_ByjunoS4Response();
                        if ($response) {
                            $byjunoResponse->setRawResponse($response);
                            $byjunoResponse->processResponse();
                            $status = $byjunoResponse->getProcessingInfoClassification();
                            $this->getHelper()->saveS4Log($order, $request, $xml, $response, $status, $ByjunoRequestName, $request_start, date('Y-m-d G:i:s'));
                        } else {
                            $status = "ERR";
                            $this->getHelper()->saveS4Log($order, $request, $xml, "empty response", $status, $ByjunoRequestName, $request_start, date('Y-m-d G:i:s'));
                        }
                        if ($status == 'INF') {
                            $this->getHelper()->sendEmailInvoice($invoice, $webshopProfileId);
                        }
                    }
                }
            }
        }
    }

    public function saveOrderAdmin(Varien_Event_Observer $observer)
    {
        $request_start = date('Y-m-d G:i:s');
        /* @var $order Mage_Sales_Model_Order */
        $order = $observer->getEvent()->getData('order');
        $methodInstance = $order->getPayment()->getMethodInstance();
        if (!($methodInstance instanceof Byjuno_Cdp_Model_Standardinvoice) && !($methodInstance instanceof Byjuno_Cdp_Model_Standardinstallment))
        {
            return;
        }
        if (Mage::app()->getStore()->isAdmin())
        {
            $payment = $order->getPayment();
            $email = $order->getBillingAddress()->getEmail();
            if (empty($email)) {
                $email = $order->getCustomerEmail();
            }
            if (!preg_match('/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/i', $email)) {
                $order->cancel()->save();
                throw new Exception("Wrong email address. Order canceled. ". $email);
            }
            $paymentMethod = $payment->getMethod();
            $paymentPlan = $payment->getAdditionalInformation("payment_plan");
            $paymentSend = $payment->getAdditionalInformation("payment_send");
            $preffered_language = $payment->getAdditionalInformation("preffered_language");

            $gender_custom = '';
            if (Mage::getStoreConfig('payment/cdp/gender_enable', Mage::app()->getStore()) == '1') {
                $gender_custom = $payment->getAdditionalInformation("gender_custom");
            }
            $dob_custom = '';
            if (Mage::getStoreConfig('payment/cdp/birthday_enable', Mage::app()->getStore()) == '1') {
                $dob_custom = $payment->getAdditionalInformation("dob_custom");
            }
            $request = $this->getHelper()->CreateMagentoShopRequestOrder($order, $paymentMethod, $paymentPlan, $paymentSend, $gender_custom, $dob_custom, $email, $preffered_language);

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
            $statusRequest = 0;
            $byjunoResponse = new Byjuno_Cdp_Helper_Api_Classes_ByjunoResponse();
            if ($response) {
                $byjunoResponse->setRawResponse($response);
                $byjunoResponse->processResponse();
                $statusRequest = (int)$byjunoResponse->getCustomerRequestStatus();
                $this->getHelper()->saveLogOrder($order, $request, $xml, $response, $statusRequest, $ByjunoRequestName, $request_start, date('Y-m-d G:i:s'));
                if (intval($statusRequest) > 15) {
                    $statusRequest = 0;
                }
                $trxId = $byjunoResponse->getResponseId();
            } else {
                $this->getHelper()->saveLogOrder($order, $request, $xml, "empty response", "0", $ByjunoRequestName, $request_start, date('Y-m-d G:i:s'));
                $trxId = "empty";
            }
            $payment->setTransactionId($trxId);
            $payment->setParentTransactionId($payment->getTransactionId());
            $transaction = $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH, null, true, "");
            if ($this->getHelper()->isStatusOk($statusRequest)) {
                $transaction->setIsClosed(false);
            } else {
                $transaction->setIsClosed(true);
            }
            $transaction->save();
            $payment->save();

            if ($this->getHelper()->isStatusOk($statusRequest)) {
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

                $riskOwner = $this->getHelper()->getStatusRisk($statusRequest);
                $riskOwnerVisual = $this->getHelper()->getStatusRiskVisual($riskOwner);

                $request = $this->getHelper()->CreateMagentoShopRequestPaid($order, $payment->getMethodInstance()->getCode(), $paymentPlan, $byjunoResponse->getTransactionNumber(), $paymentSend, $gender_custom, $dob_custom, $riskOwner, $email, $preffered_language);
                $ByjunoRequestName = "Order paid";
                $requestType = 'b2c';
                if ($request->getCompanyName1() != '' && Mage::getStoreConfig('payment/cdp/businesstobusiness', Mage::app()->getStore()) == 'enable') {
                    $ByjunoRequestName = "Order paid for Company";
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
                if ($response) {
                    $byjunoResponse = new Byjuno_Cdp_Helper_Api_Classes_ByjunoResponse();
                    $byjunoResponse->setRawResponse($response);
                    $byjunoResponse->processResponse();
                    $status = (int)$byjunoResponse->getCustomerRequestStatus();
                    if (intval($status) > 15) {
                        $status = 0;
                    }
                    $this->getHelper()->saveLogOrder($order, $request, $xml, $response, $status, $ByjunoRequestName, $request_start, date('Y-m-d G:i:s'));
                } else {
                    $this->getHelper()->saveLogOrder($order, $request, $xml, "empty response", "0", $ByjunoRequestName, $request_start, date('Y-m-d G:i:s'));
                }
                if ($this->getHelper()->isStatusOk($statusRequest) && $status == 2) {

                    $payment->setAdditionalInformation("s3_ok", 'true')->save();
                    $status = Mage::getStoreConfig('payment/cdp/success_order_status', Mage::app()->getStore());
                    /* @var $config Mage_Sales_Model_Order_Config */
                    $config = $order->getConfig();
                    $states = $config->getStatusStates($status);
                    if (!empty($states[0]) && $states[0] instanceof Mage_Sales_Model_Order_Status) {
                        /* @var $state Mage_Sales_Model_Order_Status */
                        $state = $states[0];
                        $st = $state->getData();
                        if (!empty($st["status"]) && !empty($st["state"])) {
                            $order->setState($st["state"], true, '', null);
                            $order->setStatus($st["status"]);
                        } else {
                            $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true, '', null);
                        }
                    } else {
                        $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true, '', null);

                    }
                    $order->getPayment()->setAdditionalInformation("payment_riskowner", $riskOwnerVisual);
                    $order->save();
                    try {
                        $this->getHelper()->queueNewOrderEmail($order);
                    } catch (Exception $e) {
                        Mage::logException($e);
                    }
                    $paymentRiskOwner = $order->getPayment()->getAdditionalInformation("payment_riskowner");

                    if ($paymentRiskOwner == null || $paymentRiskOwner == "") {
                        $paymentRiskOwner = "Check actual transaction RISKOWNER tag";
                    }
                    $htmlAdd = "Risk owner: ".$paymentRiskOwner;

                    $historyItem = $order->addStatusHistoryComment($htmlAdd, $status);
                    $historyItem->setIsVisibleOnFront(false)->save();

                } else {
                    $order->cancel()->save();
                    throw new Exception($this->getHelper()->getByjunoErrorMessage($status, $requestType) . " (S3)");
                }
            } else if ($statusRequest == 0) {
                $order->cancel()->save();
                throw new Exception($this->getHelper()->getByjunoErrorMessage($statusRequest, $requestType));
            } else {
                $order->cancel()->save();
                throw new Exception($this->getHelper()->getByjunoErrorMessage($statusRequest, $requestType));
            }

        }
    }

}
