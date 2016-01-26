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
    public function getOrderPlaceRedirectUrl()
    {
        //exit(Mage::getUrl('paypal/standard/success'));
        return Mage::getUrl('cdp/standard/result');
        //return 'http://www.csv.lv';//Mage::getUrl('customcard/standard/redirect', array('_secure' => true));
    }

}