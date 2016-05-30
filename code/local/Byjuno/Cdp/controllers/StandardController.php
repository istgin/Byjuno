<?php

class Byjuno_Cdp_StandardController extends Mage_Core_Controller_Front_Action
{
    /**
     * Order instance
     */
    protected $_order;

    /**
     *  Get order
     *
     *  @return  Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        if ($this->_order == null) {
        }
        return $this->_order;
    }

    /**
     * Send expire header to ajax response
     *
     */
    protected function _expireAjax()
    {
        if (!Mage::getSingleton('checkout/session')->getQuote()->hasItems()) {
            $this->getResponse()->setHeader('HTTP/1.1','403 Session Expired');
            exit;
        }
    }

    /**
     * Get singleton with paypal strandard order transaction information
     *
     * @return Mage_Paypal_Model_Standard
     */
    public function getStandard()
    {
        return Mage::getSingleton('paypal/standard');
    }

    /**
     * When a customer chooses Paypal on Checkout/Payment page
     *
     */
    public function redirectAction()
    {
        $session = Mage::getSingleton('checkout/session');
        $session->setPaypalStandardQuoteId($session->getQuoteId());
        $this->getResponse()->setBody($this->getLayout()->createBlock('paypal/standard_redirect')->toHtml());
        $session->unsQuoteId();
        $session->unsRedirectUrl();
    }

    /**
     * When a customer cancel payment from paypal.
     */
    public function cancelAction()
    {
        $session = Mage::getSingleton('checkout/session');
        $session->setQuoteId($session->getByjunoStandardQuoteId(true));
        if ($session->getLastRealOrderId()) {
            $order = Mage::getModel('sales/order')->loadByIncrementId($session->getLastRealOrderId());
            if ($order->getId()) {
                $order->cancel()->save();
            }
            Mage::helper('byjuno/checkout')->restoreQuote();
        }
        $this->_redirect('checkout/cart');
    }

    /**
     * when paypal returns
     * The order information at this point is in POST
     * variables.  However, you don't want to "process" the order until you
     * get validation from the IPN.
     */
    public function resultAction()
    {
        $session = Mage::getSingleton('checkout/session');
        $session->setByjunoStandardQuoteId($session->getQuoteId());


        $status = $session->getData("intrum_status");
        if ($status == 2) {
            $this->_redirect('cdp/standard/success');
        } else {
            $session->addError(Mage::getStoreConfig('payment/cdp/byjuno_fail_message', Mage::app()->getStore()) . " (Internal error 90)");
            $this->_redirect('cdp/standard/cancel');
        }
    }

    public function  successAction()
    {
        $session = Mage::getSingleton('checkout/session');
        $session->setByjunoStandardQuoteId($session->getQuoteId());
        $statusRequest = $session->getData("intrum_status");
        $byjunoTransaction = $session->getData("byjuno_transaction");
        $orderId = $session->getData("intrum_order");
        if ($statusRequest != 2) {
            $session->addError(Mage::getStoreConfig('payment/cdp/byjuno_fail_message', Mage::app()->getStore()) . " (Internal error 102)");
            $this->_redirect('cdp/standard/cancel');
        }


        $quote = Mage::getSingleton('checkout/type_onepage')->getQuote();
        $session = Mage::getSingleton('checkout/session');
        $session->setQuoteId($session->getByjunoStandardQuoteId(true));
        $order = Mage::getModel('sales/order')->load($orderId);
        $helper = Mage::helper('byjuno');

        $payment = $order->getPayment();
        $paymentPlan = $payment->getAdditionalInformation("payment_plan");
        $paymentSend = $payment->getAdditionalInformation("payment_send");
        $request = $helper->CreateMagentoShopRequestPaid($order, $payment->getMethodInstance()->getCode(), $paymentPlan, $byjunoTransaction, $paymentSend);
        $ByjunoRequestName = "Order paid";
        if ($request->getCompanyName1() != '' && Mage::getStoreConfig('payment/cdp/businesstobusiness', Mage::app()->getStore()) == 'enable') {
            $ByjunoRequestName = "Order paid for Company";
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
            $helper->saveLog($quote, $request, $xml, $response, $status, $ByjunoRequestName);
            $statusToPayment = Mage::getSingleton('checkout/session')->getData('ByjunoCDPStatus');
            $ByjunoResponseSession = Mage::getSingleton('checkout/session')->getData('ByjunoResponse');
            if (!empty($statusToPayment) && !empty($ByjunoResponseSession)) {
                $helper->saveStatusToOrder($order, $statusToPayment, unserialize($ByjunoResponseSession));
            }
        } else {
            $helper->saveLog($quote, $request, $xml, "empty response", "0", $ByjunoRequestName);
        }
        if ($statusRequest == 2 && $status == 2) {
            try {
                $order->queueNewOrderEmail();
            } catch (Exception $e) {
                Mage::logException($e);
            }
            Mage::getSingleton('checkout/session')->getQuote()->setIsActive(false)->save();
            $this->_redirect('checkout/onepage/success', array('_secure' => true));
        } else {
            $session->addError(Mage::getStoreConfig('payment/cdp/byjuno_fail_message', Mage::app()->getStore()) . " (Internal error 153)");
            $this->_redirect('cdp/standard/cancel');
        }
    }
}
