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
    protected $_canRefund               = true;
    protected $_canCapture               = true;

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
        $payment->setTransactionId(1);
        $payment->setParentTransactionId($payment->getTransactionId());
        $transaction = $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH, null, true, "");
        $transaction->setIsClosed(true);
        return $this;
    }

    public function refund(Varien_Object $payment, $requestedAmount)
    {
        $order = $payment->getOrder();
        if ($order->hasInvoices()) {
            $invIncrementIDs = array();
            foreach ($order->getInvoiceCollection() as $inv) {
                $invIncrementIDs[] = $inv->getIncrementId();
            }
        }
        /* @var $request Byjuno_Cdp_Helper_Api_Classes_ByjunoS4Request */
        $request = $this->getHelper()->CreateMagentoShopRequestS5Paid($order, $requestedAmount);
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
            $this->getHelper()->saveS4Log($order, $request, $xml, "empty response", "0", $ByjunoRequestName);
        }
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
        foreach($plns as $val) {
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
        $info = $this->getInfoInstance();
        if ($data->getPaymentPlan())
        {
            $info->setAdditionalInformation("payment_plan", $data->getPaymentPlan());
        }
        return $this;
    }


    public function getTitle()
    {
        return  Mage::getStoreConfig('payment/cdp/title_invoice', Mage::app()->getStore());
    }

    public function getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    private function getHelper(){
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
        $request = $this->getHelper()->CreateMagentoShopRequestOrder($order, $paymentMethod, $paymentPlan);

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
        } else {
            $this->getHelper()->saveLog($quote, $request, $xml, "empty response", "0", $ByjunoRequestName);
        }
        $session->setData("intrum_status", $status);
        $session->setData("intrum_order", $order->getId());
        if ($status == 2) {
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