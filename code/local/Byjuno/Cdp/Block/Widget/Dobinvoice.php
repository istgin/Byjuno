<?php

class Byjuno_Cdp_Block_Widget_Dobinvoice extends Mage_Customer_Block_Widget_Dob
{
    /**
     * Date inputs
     *
     * @var array
     */
    protected $_dateInputs = array();

    public function _construct()
    {
        parent::_construct();

        // default template location
        $this->setTemplate('byjuno_frontend/dob_invoice.phtml');
    }

    public function isEnabled()
    {
        return true;
    }

    public function isRequired()
    {
        return true;
    }

    public function setDate($date)
    {
        $this->setTime($date ? strtotime($date) : false);
        $this->setData('date', $date);
        return $this;
    }

    public function getDay()
    {
        return $this->getTime() ? date('d', $this->getTime()) : '';
    }

    public function getMonth()
    {
        return $this->getTime() ? date('m', $this->getTime()) : '';
    }

    public function getYear()
    {
        return $this->getTime() ? date('Y', $this->getTime()) : '';
    }

    /**
     * Returns format which will be applied for DOB in javascript
     *
     * @return string
     */
    public function getDateFormat()
    {
        return Mage::app()->getLocale()->getDateStrFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT);
    }

    /**
     * Add date input html
     *
     * @param string $code
     * @param string $html
     */
    public function setDateInput($code, $html)
    {
        $this->_dateInputs[$code] = $html;
    }

    /**
     * Sort date inputs by dateformat order of current locale
     *
     * @return string
     */
    public function getSortedDateInputs()
    {
        $strtr = array(
            '%b' => '%1$s',
            '%B' => '%1$s',
            '%m' => '%1$s',
            '%d' => '%2$s',
            '%e' => '%2$s',
            '%Y' => '%3$s',
            '%y' => '%3$s'
        );

        $dateFormat = preg_replace('/[^\%\w]/', '\\1', $this->getDateFormat());

        return sprintf(strtr($dateFormat, $strtr),
            $this->_dateInputs['m'], $this->_dateInputs['d'], $this->_dateInputs['y']);
    }
}
