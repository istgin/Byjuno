<?php
/**
 * Created by PhpStorm.
 * User: isgn
 * Date: 25.01.2016
 * Time: 18:35
 */
class Byjuno_Cdp_Model_Standard extends Mage_Payment_Model_Method_Abstract
{
    protected $_code = 'cdp';

    protected $_isInitializeNeeded = true;
    protected $_canUseInternal = false;
    protected $_canUseForMultishipping = false;

    /**
     * Return Order place redirect url
     *
     * @return string
     */

    public function getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    public function getOrderPlaceRedirectUrl()
    {

        return Mage::getUrl('cdp/standard/result');

        $order_id = Mage::getSingleton('checkout/session')->getQuoteId();
        return 'http://www.csv.lv/' . $order_id;

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
        //exit(Mage::getUrl('paypal/standard/success'));
        return Mage::getUrl('cdp/standard/result');
        //return 'http://www.csv.lv';//Mage::getUrl('customcard/standard/redirect', array('_secure' => true));
    }

}