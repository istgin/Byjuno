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

    public function validate()
    {
        parent::validate();
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
        $entityType = Mage::getModel('eav/entity_type')->loadByCode('invoice');
        $invoiceId = $entityType->fetchNewIncrementId($invoice->getStoreId());
        $order = $invoice->getOrder();
        $invoice->setIncrementId($invoiceId);
        /* @var $request Byjuno_Cdp_Helper_Api_Classes_ByjunoS4Request */
        $request = $this->getHelper()->CreateMagentoShopRequestS4Paid($order, $invoice);
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
        /* @var $memo Mage_Sales_Model_Order_Creditmemo */
        $memo = $payment->getCreditmemo();
        $incoiceId = $memo->getInvoice()->getIncrementId();
        /* @var $request Byjuno_Cdp_Helper_Api_Classes_ByjunoS4Request */
        $request = $this->getHelper()->CreateMagentoShopRequestS5Paid($order, $requestedAmount, "REFUND", $incoiceId);
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
        /* @var $payent Mage_Sales_Model_Order_Payment */
        $payment->setTransactionId($payment->getParentTransactionId().'-refund');
        $transaction = $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_VOID, null, true, "Transaction refunced");
        $transaction->setIsClosed(true);
        $transaction->save();
        return $this;
    }

    public function cancel(Varien_Object $payment)
    {
        if (Mage::getStoreConfig('payment/cdp/byjunos5transacton', Mage::app()->getStore()) == '0') {
            return $this;
        }
        /* @var $order Mage_Sales_Model_Order */
        $order = $payment->getOrder();
        /* @var $request Byjuno_Cdp_Helper_Api_Classes_ByjunoS4Request */
        $request = $this->getHelper()->CreateMagentoShopRequestS5Paid($order, $order->getTotalDue(), "EXPIRED");
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
        return true;
    }

    public function assignData($data)
    {
        /* @var $info Mage_Sales_Model_Quote_Payment */
        $info = $this->getInfoInstance();
        if ($data->getInvoicePaymentPlan()) {
            $info->setAdditionalInformation("payment_plan", $data->getInvoicePaymentPlan());
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
        $request = $this->getHelper()->CreateMagentoShopRequestOrder($order, $paymentMethod, $paymentPlan, $paymentSend);

        $ByjunoRequestName = "Order request";
        if ($request->getCompanyName1() != '' && Mage::getStoreConfig('payment/cdp/businesstobusiness', Mage::app()->getStore()) == 'enable') {
            $ByjunoRequestName = "Order request for Company";
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
        $session->setData("intrum_order", $order->getId());
        if ($status == 2) {
            try {
                $order->queueNewOrderEmail();
            } catch (Exception $e) {
                Mage::logException($e);
            }
            return Mage::getUrl('cdp/standard/result');
        } else if ($status == 0) {
            $session->addError(Mage::getStoreConfig('payment/cdp/byjuno_fail_message', Mage::app()->getStore()));
            return Mage::getUrl('cdp/standard/cancel');
        } else {
            $session->addError(Mage::getStoreConfig('payment/cdp/byjuno_fail_message', Mage::app()->getStore()));
            return Mage::getUrl('cdp/standard/cancel');
        }
    }

}