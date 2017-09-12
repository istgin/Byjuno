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

    public function saveOrderAdmin(Varien_Event_Observer $observer)
    {
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
            $statusRequest = 0;
            $byjunoResponse = new Byjuno_Cdp_Helper_Api_Classes_ByjunoResponse();
            if ($response) {
                $byjunoResponse->setRawResponse($response);
                $byjunoResponse->processResponse();
                $statusRequest = (int)$byjunoResponse->getCustomerRequestStatus();
                $this->getHelper()->saveLogOrder($order, $request, $xml, $response, $statusRequest, $ByjunoRequestName);
                if (intval($statusRequest) > 15) {
                    $statusRequest = 0;
                }
                $trxId = $byjunoResponse->getResponseId();
            } else {
                $this->getHelper()->saveLogOrder($order, $request, $xml, "empty response", "0", $ByjunoRequestName);
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

                $request = $this->getHelper()->CreateMagentoShopRequestPaid($order, $payment->getMethodInstance()->getCode(), $paymentPlan, $byjunoResponse->getTransactionNumber(), $paymentSend, $gender_custom, $dob_custom, $riskOwner);
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
                    $this->getHelper()->saveLogOrder($order, $request, $xml, $response, $status, $ByjunoRequestName);
                } else {
                    $this->getHelper()->saveLogOrder($order, $request, $xml, "empty response", "0", $ByjunoRequestName);
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
