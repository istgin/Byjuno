<?php
class Byjuno_Cdp_Model_Mysql4_Byjuno_Collection extends Varien_Data_Collection_Db
{
    protected $_byjunoTable;

    public function getByjunoTable()
    {
        return $this->_byjunoTable;
    }
    public function __construct()
    {
        $resources = Mage::getSingleton('core/resource');
        parent::__construct($resources->getConnection('byjuno_read'));
        $this->_byjunoTable = $resources->getTableName('byjuno/byjuno');
        $this->_select->from(
                array('byjuno'=>$this->_byjunoTable),
                array('*'));
        $this->setItemObjectClass(Mage::getConfig()->getModelClassName('byjuno/byjuno'));
    }

}