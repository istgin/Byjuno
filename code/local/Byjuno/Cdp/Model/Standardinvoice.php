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
    protected $_isInitializeNeeded = true;
    protected $_canUseInternal = false;
    protected $_canUseForMultishipping = false;

	public function validate()
    {
        parent::validate(); 
        return $this;
    }

    public function isAvailable($quote = null)
    {
        if (Mage::getStoreConfig('payment/cdp/active', Mage::app()->getStore()) == "0") {
            return false;
        }
        return true;
    }
  
	public function assignData($data)
	{
		$info = $this->getInfoInstance();
		 /*
		if ($data->getCustomFieldOne())
		{
		  $info->setCustomFieldOne($data->getCustomFieldOne());
		}
		 
		if ($data->getCustomFieldTwo())
		{
		  $info->setCustomFieldTwo($data->getCustomFieldTwo());
		}
		*/
	 
		return $this;
	}
    /**
     * Return Order place redirect url
     *
     * @return string
     */


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
        $request = $this->getHelper()->CreateMagentoShopRequestOrder($order, $paymentMethod);

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
            $session->addError("Gateway timeout. Please try again later");
            return Mage::getUrl('cdp/standard/cancel');
        } else {
            $session->addError("You are not allowed to pay with this payment method");
            return Mage::getUrl('cdp/standard/cancel');
        }
    }

}