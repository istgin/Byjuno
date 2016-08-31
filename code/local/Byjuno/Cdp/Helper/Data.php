<?php

class Byjuno_Cdp_Helper_Data extends Mage_Core_Helper_Abstract {


    function getByjunoErrorMessage($status, $paymentType = 'b2c') {
        $message = '';
        if ($status == 10 && $paymentType == 'b2b') {
            if (substr(Mage::getStoreConfig('general/locale/code'),0,2) == 'en') {
                $message = 'Company is not found in Register of Commerce';
            } else if (substr(Mage::getStoreConfig('general/locale/code'),0,2) == 'fr') {
                $message = 'La société n‘est pas inscrit au registre du commerce';
            } else if (substr(Mage::getStoreConfig('general/locale/code'),0,2) == 'it') {
                $message = 'L‘azienda non é registrata nel registro di commercio';
            } else {
                $message = 'Die Firma ist nicht im Handelsregister eingetragen';
            }
        } else {
            $message = Mage::getStoreConfig('payment/cdp/byjuno_fail_message', Mage::app()->getStore());
        }
        return $message;
    }

    function saveLog(Mage_Sales_Model_Quote $quote, Byjuno_Cdp_Helper_Api_Classes_ByjunoRequest $request, $xml_request, $xml_response, $status, $type) {
        $data = array( 'firstname'  => $request->getFirstName(),
            'lastname'   => $request->getLastName(),
            'postcode'   => $request->getPostCode(),
            'town'       => $request->getTown(),
            'country'    => $request->getCountryCode(),
            'street1'    => $request->getFirstLine(),
            'request_id' => $request->getRequestId(),
            'status'     => ($status != 0) ? $status : 'Error',
            'error'      => '',
            'request'    => $xml_request,
            'response'   => $xml_response,
            'type'       => $type,
            'ip'         => $_SERVER['REMOTE_ADDR']);

        $byjuno_model = Mage::getModel('byjuno/byjuno');
        $byjuno_model->setData($data);
        $byjuno_model->save();
    }

    function saveS4Log(Mage_Sales_Model_Order $order, Byjuno_Cdp_Helper_Api_Classes_ByjunoS4Request $request, $xml_request, $xml_response, $status, $type) {

        $data = array( 'firstname'  => $order->getCustomerFirstname(),
            'lastname'   => $order->getCustomerLastname(),
            'postcode'   => '-',
            'town'       => '-',
            'country'    => '-',
            'street1'    => '-',
            'request_id' => $request->getRequestId(),
            'status'     => $status,
            'error'      => '',
            'request'    => $xml_request,
            'response'   => $xml_response,
            'type'       => $type,
            'ip'         => $_SERVER['REMOTE_ADDR']);

        $byjuno_model = Mage::getModel('byjuno/byjuno');
        $byjuno_model->setData($data);
        $byjuno_model->save();
    }

    public function getClientIp() {
        $ipaddress = '';
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        } else if(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else if(!empty($_SERVER['HTTP_X_FORWARDED'])) {
            $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
        } else if(!empty($_SERVER['HTTP_FORWARDED_FOR'])) {
            $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
        } else if(!empty($_SERVER['HTTP_FORWARDED'])) {
            $ipaddress = $_SERVER['HTTP_FORWARDED'];
        } else if(!empty($_SERVER['REMOTE_ADDR'])) {
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        } else {
            $ipaddress = 'UNKNOWN';
        }
        return $ipaddress;
    }
    public function mapMethod($method) {
		if ($method == 'cdp_installment') {
			return "INSTALLMENT";
		} else {
			return "INVOICE";
		}
	}

    public function mapRepayment($type) {
        if ($type == 'installment_3_enable') {
            return "10";
        } else if ($type == 'installment_10_enable') {
            return "5";
        } else if ($type == 'installment_12_enable') {
            return "8";
        } else if ($type == 'installment_24_enable') {
            return "9";
        } else if ($type == 'installment_4x12_enable') {
            return "1";
        } else if ($type == 'installment_4x10_enable') {
            return "2";
        } else if ($type == 'invoice_single_enable') {
            return "3";
        } else {
            return "4";
        }
    }

    public function valueToStatus($val) {
        $status[0] = 'Fail to connect (status Error)';
        $status[1] = 'There are serious negative indicators (status 1)';
        $status[2] = 'All payment methods allowed (status 2)';
        $status[3] = 'Manual post-processing (currently not yet in use) (status 3)';
        $status[4] = 'Postal address is incorrect (status 4)';
        $status[5] = 'Enquiry exceeds the credit limit (the credit limit is specified in the cooperation agreement) (status 5)';
        $status[6] = 'Customer specifications not met (optional) (status 6)';
        $status[7] = 'Enquiry exceeds the net credit limit (enquiry amount plus open items exceeds credit limit) (status 7)';
        $status[8] = 'Person queried is not of creditworthy age (status 8)';
        $status[9] = 'Delivery address does not match invoice address (for payment guarantee only) (status 9)';
        $status[10] = 'Household cannot be identified at this address (status 10)';
        $status[11] = 'Country is not supported (status 11)';
        $status[12] = 'Party queried is not a natural person (status 12)';
        $status[13] = 'System is in maintenance mode (status 13)';
        $status[14] = 'Address with high fraud risk (status 14)';
        $status[15] = 'Allowance is too low (status 15)';
        if (isset($status[$val])) {
            return $status[$val];
        }
        return $status[0];
    }


    public function saveStatusToOrder($order, $byjuno_status, Byjuno_Cdp_Helper_Api_Classes_ByjunoResponse $ByjunoResponse) {
        $order->addStatusHistoryComment('<b>Byjuno status: '.$this->valueToStatus($byjuno_status).'</b><br/>Credit rating: '.$ByjunoResponse->getCustomerCreditRating().'<br/>Credit rating level: '.$ByjunoResponse->getCustomerCreditRatingLevel().'<br/>Status code: '. $byjuno_status.'</b>');
        $order->setByjunoStatus($byjuno_status);
        $order->setByjunoCreditRating($ByjunoResponse->getCustomerCreditRating());
        $order->setByjunoCreditLevel($ByjunoResponse->getCustomerCreditRatingLevel());
        $order->save();
    }

    function CreateMagentoShopRequestPaid(Mage_Sales_Model_Order $order, $paymentmethod, $repayment, $transaction, $invoiceDelivery) {

        $request = new Byjuno_Cdp_Helper_Api_Classes_ByjunoRequest();
        $request->setClientId(Mage::getStoreConfig('payment/cdp/clientid',Mage::app()->getStore()));
        $request->setUserID(Mage::getStoreConfig('payment/cdp/userid',Mage::app()->getStore()));
        $request->setPassword(Mage::getStoreConfig('payment/cdp/password',Mage::app()->getStore()));
        $request->setVersion("1.00");
        try {
            $request->setRequestEmail(Mage::getStoreConfig('payment/cdp/mail',Mage::app()->getStore()));
        } catch (Exception $e) {

        }
        $b = $order->getCustomerDob();
        if (!empty($b)) {
            try {
                $dobObject = new DateTime($b);
                if ($dobObject != null) {
                    $request->setDateOfBirth($dobObject->format('Y-m-d'));
                }
            } catch (Exception $e) {

            }
        }

        $g = $order->getCustomerGender();
        if (!empty($g)) {
            if ($g == '1') {
                $request->setGender('1');
            } else if ($g == '2') {
                $request->setGender('2');
            } else {			
                $request->setGender('0');
			}
        } else {
            if (strtolower($order->getCustomerPrefix()) == 'herr') {
                $request->setGender('1');
            } else if (strtolower($order->getCustomerPrefix()) == 'frau') {
                $request->setGender('2');
            } else {			
                $request->setGender('0');
			}
        }

        $request->setRequestId(uniqid((String)$order->getBillingAddress()->getId()."_"));
        $reference = $order->getCustomerId();
        if (empty($reference)) {
            $request->setCustomerReference("guest_".$order->getId());
        } else {
            $request->setCustomerReference($order->getCustomerId());
        }
        $request->setFirstName((String)$order->getBillingAddress()->getFirstname());
        $request->setLastName((String)$order->getBillingAddress()->getLastname());
        $request->setFirstLine(trim((String)$order->getBillingAddress()->getStreetFull()));
        $request->setCountryCode(strtoupper((String)$order->getBillingAddress()->getCountry()));
        $request->setPostCode((String)$order->getBillingAddress()->getPostcode());
        $request->setTown((String)$order->getBillingAddress()->getCity());
        $request->setFax((String)trim($order->getBillingAddress()->getFax(), '-'));
        $request->setLanguage((String)substr(Mage::app()->getLocale()->getLocaleCode(), 0, 2));

        if ($order->getBillingAddress()->getCompany()) {
            $request->setCompanyName1($order->getBillingAddress()->getCompany());
        }

        $request->setTelephonePrivate((String)trim($order->getBillingAddress()->getTelephone(), '-'));
        $request->setEmail((String)$order->getBillingAddress()->getEmail());

        $extraInfo["Name"] = 'TRANSACTIONNUMBER';
        $extraInfo["Value"] = $transaction;
        $request->setExtraInfo($extraInfo);

        $extraInfo["Name"] = 'ORDERCLOSED';
        $extraInfo["Value"] = 'YES';
        $request->setExtraInfo($extraInfo);

        $extraInfo["Name"] = 'ORDERAMOUNT';
        $extraInfo["Value"] = number_format($order->getGrandTotal(), 2, '.', '');
        $request->setExtraInfo($extraInfo);

        $extraInfo["Name"] = 'ORDERCURRENCY';
        $extraInfo["Value"] = $order->getBaseCurrencyCode();
        $request->setExtraInfo($extraInfo);

        if ($invoiceDelivery == 'postal') {
            $extraInfo["Name"] = 'PAPER_INVOICE';
            $extraInfo["Value"] = 'YES';
            $request->setExtraInfo($extraInfo);
        }

        $extraInfo["Name"] = 'IP';
        $extraInfo["Value"] = $this->getClientIp();
        $request->setExtraInfo($extraInfo);

        $sesId = Mage::getSingleton('checkout/session')->getData("byjuno_session_id");
        if (Mage::getStoreConfig('payment/cdp/tmxenabled', Mage::app()->getStore()) == '1' && !empty($sesId)) {
            $extraInfo["Name"] = 'DEVICE_FINGERPRINT_ID';
            $extraInfo["Value"] = Mage::getSingleton('checkout/session')->getData("byjuno_session_id");
            $request->setExtraInfo($extraInfo);
        }

        if ($order->canShip()) {
            $extraInfo["Name"] = 'DELIVERY_FIRSTNAME';
            $extraInfo["Value"] = $order->getShippingAddress()->getFirstname();
            $request->setExtraInfo($extraInfo);

            $extraInfo["Name"] = 'DELIVERY_LASTNAME';
            $extraInfo["Value"] = $order->getShippingAddress()->getLastname();
            $request->setExtraInfo($extraInfo);

            $extraInfo["Name"] = 'DELIVERY_FIRSTLINE';
            $extraInfo["Value"] = trim($order->getShippingAddress()->getStreetFull());
            $request->setExtraInfo($extraInfo);

            $extraInfo["Name"] = 'DELIVERY_HOUSENUMBER';
            $extraInfo["Value"] = '';
            $request->setExtraInfo($extraInfo);

            $extraInfo["Name"] = 'DELIVERY_COUNTRYCODE';
            $extraInfo["Value"] = strtoupper($order->getShippingAddress()->getCountry());
            $request->setExtraInfo($extraInfo);

            $extraInfo["Name"] = 'DELIVERY_POSTCODE';
            $extraInfo["Value"] = $order->getShippingAddress()->getPostcode();
            $request->setExtraInfo($extraInfo);

            $extraInfo["Name"] = 'DELIVERY_TOWN';
            $extraInfo["Value"] = $order->getShippingAddress()->getCity();
            $request->setExtraInfo($extraInfo);

            if ($order->getShippingAddress()->getCompany() != '' && Mage::getStoreConfig('payment/cdp/businesstobusiness', Mage::app()->getStore()) == 'enable') {
                $extraInfo["Name"] = 'DELIVERY_COMPANYNAME';
                $extraInfo["Value"] = $order->getShippingAddress()->getCompany();
                $request->setExtraInfo($extraInfo);
            }
        }

        $extraInfo["Name"] = 'ORDERID';
        $extraInfo["Value"] = $order->getIncrementId();
        $request->setExtraInfo($extraInfo);

        $extraInfo["Name"] = 'PAYMENTMETHOD';
        $extraInfo["Value"] = $this->mapMethod($paymentmethod);
        $request->setExtraInfo($extraInfo);

        $extraInfo["Name"] = 'REPAYMENTTYPE';
        $extraInfo["Value"] = $this->mapRepayment($repayment);
        $request->setExtraInfo($extraInfo);

        $extraInfo["Name"] = 'RISKOWNER';
        $extraInfo["Value"] = 'IJ';
        $request->setExtraInfo($extraInfo);

		$extraInfo["Name"] = 'CONNECTIVTY_MODULE';
		$extraInfo["Value"] = 'Byjuno magento payment module 1.1.0';
		$request->setExtraInfo($extraInfo);	

        return $request;

    }

    function CreateMagentoShopRequestS4Paid(Mage_Sales_Model_Order $order, Mage_Sales_Model_Order_Invoice $invoice, $webshopProfile) {

        $request = new Byjuno_Cdp_Helper_Api_Classes_ByjunoS4Request();
        $request->setClientId(Mage::getStoreConfig('payment/cdp/clientid',$webshopProfile));
        $request->setUserID(Mage::getStoreConfig('payment/cdp/userid',$webshopProfile));
        $request->setPassword(Mage::getStoreConfig('payment/cdp/password',$webshopProfile));
        $request->setVersion("1.3");
        try {
            $request->setRequestEmail(Mage::getStoreConfig('payment/cdp/mail',$webshopProfile));
        } catch (Exception $e) {

        }
        $request->setRequestId(uniqid((String)$order->getCustomerId()."_"));

        $request->setOrderId($order->getIncrementId());
        $reference = $order->getCustomerId();
        if (empty($reference)) {
            $request->setClientRef("guest_".$order->getId());
        } else {
            $request->setClientRef($order->getCustomerId());
        }
        $request->setTransactionDate($order->getCreatedAtStoreDate()->toString(Varien_Date::DATE_INTERNAL_FORMAT));
        $request->setTransactionAmount(number_format($invoice->getGrandTotal(), 2, '.', ''));
        $request->setTransactionCurrency($order->getBaseCurrencyCode());
        $request->setAdditional1("INVOICE");
        $request->setAdditional2($invoice->getIncrementId());
        $request->setOpenBalance(number_format($invoice->getGrandTotal(), 2, '.', ''));

        return $request;

    }

    function CreateMagentoShopRequestS5Paid(Mage_Sales_Model_Order $order, $amount, $transactionType, $webshopProfile, $invoiceId = '') {

        $request = new Byjuno_Cdp_Helper_Api_Classes_ByjunoS5Request();
        $request->setClientId(Mage::getStoreConfig('payment/cdp/clientid',$webshopProfile));
        $request->setUserID(Mage::getStoreConfig('payment/cdp/userid',$webshopProfile));
        $request->setPassword(Mage::getStoreConfig('payment/cdp/password',$webshopProfile));
        $request->setVersion("1.3");
        try {
            $request->setRequestEmail(Mage::getStoreConfig('payment/cdp/mail',$webshopProfile));
        } catch (Exception $e) {

        }
        $request->setRequestId(uniqid((String)$order->getCustomerId()."_"));

        $request->setOrderId($order->getIncrementId());
        $reference = $order->getCustomerId();
        if (empty($reference)) {
            $request->setClientRef("guest_".$order->getId());
        } else {
            $request->setClientRef($order->getCustomerId());
        }
        $request->setTransactionDate($order->getCreatedAtStoreDate()->toString(Varien_Date::DATE_INTERNAL_FORMAT));
        $request->setTransactionAmount(number_format($amount, 2, '.', ''));
        $request->setTransactionCurrency($order->getBaseCurrencyCode());
        $request->setTransactionType($transactionType);
        $request->setAdditional2($invoiceId);
        if ($transactionType == "EXPIRED") {
            $request->setOpenBalance("0");
        }

        return $request;

    }

    function CreateMagentoShopRequestOrder(Mage_Sales_Model_Order $order, $paymentmethod, $repayment, $invoiceDelivery) {

        $request = new Byjuno_Cdp_Helper_Api_Classes_ByjunoRequest();
        $request->setClientId(Mage::getStoreConfig('payment/cdp/clientid',Mage::app()->getStore()));
        $request->setUserID(Mage::getStoreConfig('payment/cdp/userid',Mage::app()->getStore()));
        $request->setPassword(Mage::getStoreConfig('payment/cdp/password',Mage::app()->getStore()));
        $request->setVersion("1.00");
        try {
            $request->setRequestEmail(Mage::getStoreConfig('payment/cdp/mail',Mage::app()->getStore()));
        } catch (Exception $e) {

        }
        $b = $order->getCustomerDob();
        if (!empty($b)) {
            try {
                $dobObject = new DateTime($b);
                if ($dobObject != null) {
                    $request->setDateOfBirth($dobObject->format('Y-m-d'));
                }
            } catch (Exception $e) {

            }
        }


        $g = $order->getCustomerGender();
        if (!empty($g)) {
            if ($g == '1') {
                $request->setGender('1');
            } else if ($g == '2') {
                $request->setGender('2');
            } else {			
                $request->setGender('0');
			}
        } else {
            if (strtolower($order->getCustomerPrefix()) == 'herr') {
                $request->setGender('1');
            } else if (strtolower($order->getCustomerPrefix()) == 'frau') {
                $request->setGender('2');
            } else {			
                $request->setGender('0');
			}
        }

        $requestId = uniqid((String)$order->getBillingAddress()->getId()."_");
        $request->setRequestId($requestId);
        $reference = $order->getCustomerId();
        if (empty($reference)) {
            $request->setCustomerReference("guest_".$order->getId());
        } else {
            $request->setCustomerReference($order->getCustomerId());
        }
        $request->setFirstName((String)$order->getBillingAddress()->getFirstname());
        $request->setLastName((String)$order->getBillingAddress()->getLastname());
        $request->setFirstLine(trim((String)$order->getBillingAddress()->getStreetFull()));
        $request->setCountryCode(strtoupper((String)$order->getBillingAddress()->getCountry()));
        $request->setPostCode((String)$order->getBillingAddress()->getPostcode());
        $request->setTown((String)$order->getBillingAddress()->getCity());
        $request->setFax((String)trim($order->getBillingAddress()->getFax(), '-'));
        $request->setLanguage((String)substr(Mage::app()->getLocale()->getLocaleCode(), 0, 2));

        if ($order->getBillingAddress()->getCompany()) {
            $request->setCompanyName1($order->getBillingAddress()->getCompany());
        }

        $request->setTelephonePrivate((String)trim($order->getBillingAddress()->getTelephone(), '-'));
        $request->setEmail((String)$order->getBillingAddress()->getEmail());

        $extraInfo["Name"] = 'ORDERCLOSED';
        $extraInfo["Value"] = 'NO';
        $request->setExtraInfo($extraInfo);

        $extraInfo["Name"] = 'ORDERAMOUNT';
        $extraInfo["Value"] = number_format($order->getGrandTotal(), 2, '.', '');
        $request->setExtraInfo($extraInfo);

        $extraInfo["Name"] = 'ORDERCURRENCY';
        $extraInfo["Value"] = $order->getBaseCurrencyCode();
        $request->setExtraInfo($extraInfo);

        $extraInfo["Name"] = 'IP';
        $extraInfo["Value"] = $this->getClientIp();
        $request->setExtraInfo($extraInfo);

        $sesId = Mage::getSingleton('checkout/session')->getData("byjuno_session_id");
        if (Mage::getStoreConfig('payment/cdp/tmxenabled', Mage::app()->getStore()) == '1' && !empty($sesId)) {
            $extraInfo["Name"] = 'DEVICE_FINGERPRINT_ID';
            $extraInfo["Value"] = Mage::getSingleton('checkout/session')->getData("byjuno_session_id");
            $request->setExtraInfo($extraInfo);
        }

        if ($invoiceDelivery == 'postal') {
            $extraInfo["Name"] = 'PAPER_INVOICE';
            $extraInfo["Value"] = 'YES';
            $request->setExtraInfo($extraInfo);
        }

        /* shipping information */
        if ($order->canShip()) {
            $extraInfo["Name"] = 'DELIVERY_FIRSTNAME';
            $extraInfo["Value"] = $order->getShippingAddress()->getFirstname();
            $request->setExtraInfo($extraInfo);

            $extraInfo["Name"] = 'DELIVERY_LASTNAME';
            $extraInfo["Value"] = $order->getShippingAddress()->getLastname();
            $request->setExtraInfo($extraInfo);

            $extraInfo["Name"] = 'DELIVERY_FIRSTLINE';
            $extraInfo["Value"] = trim($order->getShippingAddress()->getStreetFull());
            $request->setExtraInfo($extraInfo);

            $extraInfo["Name"] = 'DELIVERY_HOUSENUMBER';
            $extraInfo["Value"] = '';
            $request->setExtraInfo($extraInfo);

            $extraInfo["Name"] = 'DELIVERY_COUNTRYCODE';
            $extraInfo["Value"] = strtoupper($order->getShippingAddress()->getCountry());
            $request->setExtraInfo($extraInfo);

            $extraInfo["Name"] = 'DELIVERY_POSTCODE';
            $extraInfo["Value"] = $order->getShippingAddress()->getPostcode();
            $request->setExtraInfo($extraInfo);

            $extraInfo["Name"] = 'DELIVERY_TOWN';
            $extraInfo["Value"] = $order->getShippingAddress()->getCity();
            $request->setExtraInfo($extraInfo);

            if ($order->getShippingAddress()->getCompany() != '' && Mage::getStoreConfig('payment/api/businesstobusiness', Mage::app()->getStore()) == 'enable') {
                $extraInfo["Name"] = 'DELIVERY_COMPANYNAME';
                $extraInfo["Value"] = $order->getShippingAddress()->getCompany();
                $request->setExtraInfo($extraInfo);
            }
        }

        $extraInfo["Name"] = 'PP_TRANSACTION_NUMBER';
        $extraInfo["Value"] = $requestId;
        $request->setExtraInfo($extraInfo);

        $extraInfo["Name"] = 'ORDERID';
        $extraInfo["Value"] = $order->getIncrementId();
        $request->setExtraInfo($extraInfo);

        $extraInfo["Name"] = 'PAYMENTMETHOD';
        $extraInfo["Value"] = $this->mapMethod($paymentmethod);
        $request->setExtraInfo($extraInfo);

        $extraInfo["Name"] = 'REPAYMENTTYPE';
        $extraInfo["Value"] = $this->mapRepayment($repayment);
        $request->setExtraInfo($extraInfo);

        $extraInfo["Name"] = 'RISKOWNER';
        $extraInfo["Value"] = 'IJ';
        $request->setExtraInfo($extraInfo);

		$extraInfo["Name"] = 'CONNECTIVTY_MODULE';
		$extraInfo["Value"] = 'Byjuno Magento module 1.1.0';
		$request->setExtraInfo($extraInfo);
        return $request;
    }


}