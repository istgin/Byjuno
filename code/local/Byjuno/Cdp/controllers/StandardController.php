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


    public function cancelAction()
    {
        $session = Mage::getSingleton('checkout/session');
        $session->setQuoteId($session->getByjunoStandardQuoteId(true));
        if ($session->getLastRealOrderId()) {
            $order = Mage::getModel('sales/order')->loadByIncrementId($session->getLastRealOrderId());
            if ($order->getId()) {
                $order->cancel()->save();
            }
            Mage::helper('byjuno/checkout')->restoreCart($order);
        }
        Mage::getSingleton('checkout/session')->setData("byjuno_session_id", "");
        $this->_redirect('checkout/cart');
    }

    public function resultAction()
    {
        $helper = Mage::helper('byjuno');
        $session = Mage::getSingleton('checkout/session');
        $session->setByjunoStandardQuoteId($session->getQuoteId());


        $status = $session->getData("intrum_status");
        $statusRequestType = $session->getData("intrum_request_type");
        if ($status == 2) {
            $this->_redirect('cdp/standard/success');
        } else {
            $session->addError($helper->getByjunoErrorMessage($status, $statusRequestType) . " (S1 Redirect)");
            $this->_redirect('cdp/standard/cancel');
        }
    }

    public function  successAction()
    {
        $helper = Mage::helper('byjuno');
        $session = Mage::getSingleton('checkout/session');
        $session->setByjunoStandardQuoteId($session->getQuoteId());
        $statusRequest = $session->getData("intrum_status");
        $statusRequestType = $session->getData("intrum_request_type");
        $byjunoTransaction = $session->getData("byjuno_transaction");
        $orderId = $session->getData("intrum_order");
        if ($statusRequest != 2) {
            $session->addError($helper->getByjunoErrorMessage($statusRequest, $statusRequestType) . " (S1 Redirect-2)");
            $this->_redirect('cdp/standard/cancel');
        }


        $quote = Mage::getSingleton('checkout/type_onepage')->getQuote();
        $session = Mage::getSingleton('checkout/session');
        $session->setQuoteId($session->getByjunoStandardQuoteId(true));
        /* @var $order Mage_Sales_Model_Order */
        $order = Mage::getModel('sales/order')->load($orderId);

        $payment = $order->getPayment();
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

        $request = $helper->CreateMagentoShopRequestPaid($order, $payment->getMethodInstance()->getCode(), $paymentPlan, $byjunoTransaction, $paymentSend, $gender_custom, $dob_custom);
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
            $order->save();
            try {
                if (Mage::getStoreConfig('payment/cdp/forsesendendcustomer', Mage::app()->getStore()) == '1') {

                    $order->queueNewOrderEmail(true);
                    $order->getResource()->saveAttribute($order, 'email_sent');
                    $order->setEmailSent(true);
                    $order->save();
                }
                $helper->queueNewOrderEmail($order);
            } catch (Exception $e) {
                Mage::logException($e);
            }
            Mage::getSingleton('checkout/session')->setData("byjuno_session_id", "");
            Mage::getSingleton('checkout/session')->getQuote()->setIsActive(false)->save();
            $this->_redirect('checkout/onepage/success', array('_secure' => true));
        } else {
            $session->addError($helper->getByjunoErrorMessage($status, $requestType) . " (S3)");
            $this->_redirect('cdp/standard/cancel');
        }
    }
}
