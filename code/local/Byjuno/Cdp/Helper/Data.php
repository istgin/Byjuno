<?php

class Byjuno_Cdp_Helper_Data extends Mage_Core_Helper_Abstract {

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
/*
    function CreateMagentoShopRequest(Mage_Sales_Model_Quote $quote) {

        $request = new Byjuno_Cdp_Helper_Api_Classes_ByjunoRequest();
        $request->setClientId(Mage::getStoreConfig('byjuno/api/clientid',Mage::app()->getStore()));
        $request->setUserID(Mage::getStoreConfig('byjuno/api/userid',Mage::app()->getStore()));
        $request->setPassword(Mage::getStoreConfig('byjuno/api/password',Mage::app()->getStore()));
        $request->setVersion("1.00");
        try {
            $request->setRequestEmail(Mage::getStoreConfig('byjuno/api/mail',Mage::app()->getStore()));
        } catch (Exception $e) {

        }
        $b = $quote->getCustomerDob();
        if (!empty($b)) {
            $request->setDateOfBirth(Mage::getModel('core/date')->date('Y-m-d', strtotime($b)));
        }
        Mage::getSingleton('checkout/session')->setData('dob_amasty', '');
        if (Mage::getStoreConfig('byjuno/api/plugincheckouttype', Mage::app()->getStore()) == 'amasty' && !empty($_POST["billing"]["dob"])) {
            $request->setDateOfBirth(Mage::getModel('core/date')->date('Y-m-d', strtotime($_POST["billing"]["dob"])));
            Mage::getSingleton('checkout/session')->setData('dob_amasty', $_POST["billing"]["dob"]);
        }

        $g = $quote->getCustomerGender();
        if (!empty($g)) {
            if ($g == '1') {
                $request->setGender('1');
            } else if ($g == '2') {
                $request->setGender('2');
            }
        }

        $request->setRequestId(uniqid((String)$quote->getBillingAddress()->getId()."_"));
        $reference = $quote->getCustomer()->getId();
        if (empty($reference)) {
            $request->setCustomerReference("guest_".$quote->getBillingAddress()->getId());
        } else {
            $request->setCustomerReference($quote->getCustomer()->getId());
        }
        if (Mage::getStoreConfig('byjuno/api/plugincheckouttype', Mage::app()->getStore()) == 'amasty' && !empty($_POST["method"]) && $_POST["method"] == 'guest') {
            $request->setCustomerReference(uniqid("guest_"));
        }

        $request->setFirstName((String)$quote->getBillingAddress()->getFirstname());
        if (Mage::getStoreConfig('byjuno/api/plugincheckouttype', Mage::app()->getStore()) == 'amasty' && !empty($_POST["billing"]["firstname"])) {
            $request->setFirstName((String)$_POST["billing"]["firstname"]);
        }

        $request->setLastName((String)$quote->getBillingAddress()->getLastname());
        if (Mage::getStoreConfig('byjuno/api/plugincheckouttype', Mage::app()->getStore()) == 'amasty' && !empty($_POST["billing"]["lastname"])) {
            $request->setLastName((String)$_POST["billing"]["lastname"]);
        }

        $request->setFirstLine(trim((String)$quote->getBillingAddress()->getStreetFull()));
        if (Mage::getStoreConfig('byjuno/api/plugincheckouttype', Mage::app()->getStore()) == 'amasty' && !empty($_POST["billing"]["street"])) {
            $street = '';
            if (!empty($_POST["billing"]["street"][0])) {
                $street .= $_POST["billing"]["street"][0];
            }
            if (!empty($_POST["billing"]["street"][1])) {
                $street .= $_POST["billing"]["street"][1];
            }
            $request->setFirstLine((String)trim($street));
        }

        $request->setCountryCode(strtoupper((String)$quote->getBillingAddress()->getCountry()));
        if (Mage::getStoreConfig('byjuno/api/plugincheckouttype', Mage::app()->getStore()) == 'amasty' && !empty($_POST["billing"]["country_id"])) {
            $request->setCountryCode((String)$_POST["billing"]["country_id"]);
        }

        $request->setPostCode((String)$quote->getBillingAddress()->getPostcode());
        if (Mage::getStoreConfig('byjuno/api/plugincheckouttype', Mage::app()->getStore()) == 'amasty' && !empty($_POST["billing"]["postcode"])) {
            $request->setPostCode($_POST["billing"]["postcode"]);
        }

        $request->setTown((String)$quote->getBillingAddress()->getCity());
        if (Mage::getStoreConfig('byjuno/api/plugincheckouttype', Mage::app()->getStore()) == 'amasty' && !empty($_POST["billing"]["city"])) {
            $request->setTown($_POST["billing"]["city"]);
        }

        $request->setFax((String)trim($quote->getBillingAddress()->getFax(), '-'));
        if (Mage::getStoreConfig('byjuno/api/plugincheckouttype', Mage::app()->getStore()) == 'amasty' && !empty($_POST["billing"]["fax"])) {
            $request->setFax(trim($_POST["billing"]["fax"], '-'));
        }

        $request->setLanguage((String)substr(Mage::app()->getLocale()->getLocaleCode(), 0, 2));
        if ($quote->getBillingAddress()->getCompany()) {
            $request->setCompanyName1($quote->getBillingAddress()->getCompany());
        }
        if (Mage::getStoreConfig('byjuno/api/plugincheckouttype', Mage::app()->getStore()) == 'amasty' && !empty($_POST["billing"]["company"])) {
            $request->setCompanyName1(trim($_POST["billing"]["company"], '-'));
        }


        $request->setTelephonePrivate((String)trim($quote->getBillingAddress()->getTelephone(), '-'));
        if (Mage::getStoreConfig('byjuno/api/plugincheckouttype', Mage::app()->getStore()) == 'amasty' && !empty($_POST["billing"]["telephone"])) {
            $request->setTelephonePrivate(trim($_POST["billing"]["telephone"], '-'));
        }

        $request->setEmail((String)$quote->getBillingAddress()->getEmail());
        if (Mage::getStoreConfig('byjuno/api/plugincheckouttype', Mage::app()->getStore()) == 'amasty' && !empty($_POST["billing"]["email"])) {
            $request->setEmail((String)$_POST["billing"]["email"]);
        }

        Mage::getSingleton('checkout/session')->setData('gender_amasty', '');
        if (Mage::getStoreConfig('byjuno/api/plugincheckouttype', Mage::app()->getStore()) == 'amasty') {
            $g = isset($_POST["billing"]["gender"]) ? $_POST["billing"]["gender"] : '';
            $request->setGender($g);
            Mage::getSingleton('checkout/session')->setData('gender_amasty', $g);
        }
        if (Mage::getStoreConfig('byjuno/api/plugincheckouttype', Mage::app()->getStore()) == 'amasty' && !empty($_POST["billing"]["prefix"])) {
            if (strtolower($_POST["billing"]["prefix"]) == 'herr') {
                $request->setGender('1');
                Mage::getSingleton('checkout/session')->setData('gender_amasty', '1');
            } else if (strtolower($_POST["billing"]["prefix"]) == 'frau') {
                $request->setGender('2');
                Mage::getSingleton('checkout/session')->setData('gender_amasty', '2');
            }
        }

        $extraInfo["Name"] = 'ORDERCLOSED';
        $extraInfo["Value"] = 'NO';
        $request->setExtraInfo($extraInfo);

        $extraInfo["Name"] = 'ORDERAMOUNT';
        $extraInfo["Value"] = number_format($quote->getGrandTotal(), 2, '.', '');
        $request->setExtraInfo($extraInfo);

        $extraInfo["Name"] = 'ORDERCURRENCY';
        $extraInfo["Value"] = $quote->getBaseCurrencyCode();
        $request->setExtraInfo($extraInfo);

        $extraInfo["Name"] = 'IP';
        $extraInfo["Value"] = $this->getClientIp();
        $request->setExtraInfo($extraInfo);

        $sesId = Mage::getSingleton('checkout/session')->getData("byjuno_session_id");
        if (Mage::getStoreConfig('byjuno/api/tmxenabled', Mage::app()->getStore()) == 'enable' && !empty($sesId)) {
            $extraInfo["Name"] = 'DEVICE_FINGERPRINT_ID';
            $extraInfo["Value"] = Mage::getSingleton('checkout/session')->getData("byjuno_session_id");
            $request->setExtraInfo($extraInfo);
        }


        if (!$quote->isVirtual()) {
            $extraInfo["Name"] = 'DELIVERY_FIRSTNAME';
            $extraInfo["Value"] = $quote->getShippingAddress()->getFirstname();
            if (Mage::getStoreConfig('byjuno/api/plugincheckouttype', Mage::app()->getStore()) == 'amasty' && empty($_POST["shipping"]["same_as_billing"])) {
                if (!empty($_POST["shipping"]["firstname"])) {
                    $extraInfo["Value"] = $_POST["shipping"]["firstname"];
                }
            }
            $request->setExtraInfo($extraInfo);

            $extraInfo["Name"] = 'DELIVERY_LASTNAME';
            $extraInfo["Value"] = $quote->getShippingAddress()->getLastname();
            if (Mage::getStoreConfig('byjuno/api/plugincheckouttype', Mage::app()->getStore()) == 'amasty' && empty($_POST["shipping"]["same_as_billing"])) {
                if (!empty($_POST["shipping"]["lastname"])) {
                    $extraInfo["Value"] = $_POST["shipping"]["lastname"];
                }
            }
            $request->setExtraInfo($extraInfo);

            $extraInfo["Name"] = 'DELIVERY_FIRSTLINE';
            $extraInfo["Value"] = trim($quote->getShippingAddress()->getStreetFull());
            if (Mage::getStoreConfig('byjuno/api/plugincheckouttype', Mage::app()->getStore()) == 'amasty' && empty($_POST["shipping"]["same_as_billing"])) {
                $extraInfo["Value"] = '';
                if (!empty($_POST["shipping"]["street"][0])) {
                    $extraInfo["Value"] = $_POST["shipping"]["street"][0];
                }
                if (!empty($_POST["shipping"]["street"][1])) {
                    $extraInfo["Value"] .= ' '.$_POST["shipping"]["street"][1];
                }
                $extraInfo["Value"] = trim($extraInfo["Value"]);
            }
            $request->setExtraInfo($extraInfo);

            $extraInfo["Name"] = 'DELIVERY_HOUSENUMBER';
            $extraInfo["Value"] = '';
            $request->setExtraInfo($extraInfo);

            $extraInfo["Name"] = 'DELIVERY_COUNTRYCODE';
            $extraInfo["Value"] = strtoupper($quote->getShippingAddress()->getCountry());
            if (Mage::getStoreConfig('byjuno/api/plugincheckouttype', Mage::app()->getStore()) == 'amasty' && empty($_POST["shipping"]["same_as_billing"])) {
                if (!empty($_POST["shipping"]["country_id"])) {
                    $extraInfo["Value"] = $_POST["shipping"]["country_id"];
                }
            }
            $request->setExtraInfo($extraInfo);
            $extraInfo["Name"] = 'DELIVERY_POSTCODE';
            $extraInfo["Value"] = $quote->getShippingAddress()->getPostcode();
            if (Mage::getStoreConfig('byjuno/api/plugincheckouttype', Mage::app()->getStore()) == 'amasty' && empty($_POST["shipping"]["same_as_billing"])) {
                if (!empty($_POST["shipping"]["postcode"])) {
                    $extraInfo["Value"] = $_POST["shipping"]["postcode"];
                }
            }
            $request->setExtraInfo($extraInfo);

            $extraInfo["Name"] = 'DELIVERY_TOWN';
            $extraInfo["Value"] = $quote->getShippingAddress()->getCity();
            if (Mage::getStoreConfig('byjuno/api/plugincheckouttype', Mage::app()->getStore()) == 'amasty') {
                if (!empty($_POST["shipping"]["same_as_billing"]) && $_POST["shipping"]["same_as_billing"] == '1' && !empty($_POST["billing"]["city"])) {
                    $extraInfo["Value"] = $_POST["billing"]["city"];
                } else if (!empty($_POST["shipping"]["city"])) {
                    $extraInfo["Value"] = $_POST["shipping"]["city"];
                }
            }
            $request->setExtraInfo($extraInfo);

            if ($quote->getShippingAddress()->getCompany() != '' && Mage::getStoreConfig('byjuno/api/businesstobusiness', Mage::app()->getStore()) == 'enable') {
                $extraInfo["Name"] = 'DELIVERY_COMPANYNAME';
                $extraInfo["Value"] = $quote->getShippingAddress()->getCompany();
                $request->setExtraInfo($extraInfo);
            }
        }

		$extraInfo["Name"] = 'CONNECTIVTY_MODULE';
		$extraInfo["Value"] = 'Byjuno Magento module 1.0.0';
		$request->setExtraInfo($extraInfo);
        return $request;

    }

    function CreateMagentoShopRequestPaid(Mage_Sales_Model_Order $order, $paymentmethod) {

        $request = new Byjuno_Cdp_Helper_Api_Classes_ByjunoRequest();
        $request->setClientId(Mage::getStoreConfig('byjuno/api/clientid',Mage::app()->getStore()));
        $request->setUserID(Mage::getStoreConfig('byjuno/api/userid',Mage::app()->getStore()));
        $request->setPassword(Mage::getStoreConfig('byjuno/api/password',Mage::app()->getStore()));
        $request->setVersion("1.00");
        try {
            $request->setRequestEmail(Mage::getStoreConfig('byjuno/api/mail',Mage::app()->getStore()));
        } catch (Exception $e) {

        }
        $b = $order->getCustomerDob();
        if (!empty($b)) {
            $request->setDateOfBirth(Mage::getModel('core/date')->date('Y-m-d', strtotime($b)));
        }

        if (Mage::getStoreConfig('byjuno/api/plugincheckouttype', Mage::app()->getStore()) == 'amasty' && empty($b)) {
            $dob = Mage::getSingleton('checkout/session')->getData('dob_amasty');
            if (!empty($dob)) {
                $request->setDateOfBirth(Mage::getModel('core/date')->date('Y-m-d', strtotime($dob)));
            }
            Mage::getSingleton('checkout/session')->setData('dob_amasty', '');
        }

        $g = $order->getCustomerGender();
        if (!empty($g)) {
            if ($g == '1') {
                $request->setGender('1');
            } else if ($g == '2') {
                $request->setGender('2');
            }
        }

        if (Mage::getStoreConfig('byjuno/api/plugincheckouttype', Mage::app()->getStore()) == 'amasty' && empty($g)) {
            $gender = Mage::getSingleton('checkout/session')->getData('gender_amasty');
            if (!empty($gender)) {
                $request->setGender($gender);
            }
            Mage::getSingleton('checkout/session')->setData('gender_amasty', '');
        }

        $request->setRequestId(uniqid((String)$order->getBillingAddress()->getId()."_"));
        $reference = $order->getCustomerId();
        if (empty($reference)) {
            $request->setCustomerReference("guest_".$order->getBillingAddress()->getId());
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
        $extraInfo["Value"] = 'YES';
        $request->setExtraInfo($extraInfo);

        $extraInfo["Name"] = 'ORDERAMOUNT';
        $extraInfo["Value"] = number_format($order->getGrandTotal(), 2, '.', '');
        $request->setExtraInfo($extraInfo);

        $extraInfo["Name"] = 'ORDERCURRENCY';
        $extraInfo["Value"] = $order->getBaseCurrencyCode();
        $request->setExtraInfo($extraInfo);

        $sesId = Mage::getSingleton('checkout/session')->getData("byjuno_session_id");
        if (Mage::getStoreConfig('byjuno/api/tmxenabled', Mage::app()->getStore()) == 'enable' && !empty($sesId)) {
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

            if ($order->getShippingAddress()->getCompany() != '' && Mage::getStoreConfig('byjuno/api/businesstobusiness', Mage::app()->getStore()) == 'enable') {
                $extraInfo["Name"] = 'DELIVERY_COMPANYNAME';
                $extraInfo["Value"] = $order->getShippingAddress()->getCompany();
                $request->setExtraInfo($extraInfo);
            }
        }

        $extraInfo["Name"] = 'ORDERID';
        $extraInfo["Value"] = $order->getIncrementId();
        $request->setExtraInfo($extraInfo);

        $extraInfo["Name"] = 'PAYMENTMETHOD';
        $extraInfo["Value"] = $this->mapPaymentMethodToSpecs($paymentmethod);
        $request->setExtraInfo($extraInfo);

		$extraInfo["Name"] = 'CONNECTIVTY_MODULE';
		$extraInfo["Value"] = 'Byjuno Magento module 4.0.0';
		$request->setExtraInfo($extraInfo);	

        return $request;

    }
*/

    function CreateMagentoShopRequestOrder(Mage_Sales_Model_Order $order, $paymentmethod) {

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
            $request->setDateOfBirth(Mage::getModel('core/date')->date('Y-m-d', strtotime($b)));
        }

        $g = $order->getCustomerGender();
        if (!empty($g)) {
            if ($g == '1') {
                $request->setGender('1');
            } else if ($g == '2') {
                $request->setGender('2');
            }
        }

        $requestId = uniqid((String)$order->getBillingAddress()->getId()."_");
        $request->setRequestId($requestId);
        $reference = $order->getCustomerId();
        if (empty($reference)) {
            $request->setCustomerReference("guest_".$order->getBillingAddress()->getId());
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
        $extraInfo["Value"] = 'BYJUNO-INVOICE';
        $request->setExtraInfo($extraInfo);

		$extraInfo["Name"] = 'CONNECTIVTY_MODULE';
		$extraInfo["Value"] = 'Byjuno Magento module 1.0.0';
		$request->setExtraInfo($extraInfo);
        return $request;
    }


}