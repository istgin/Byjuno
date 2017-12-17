<?php

class Byjuno_Cdp_Block_Admin_Log extends Mage_Adminhtml_Block_Widget_Grid
{
     public function __construct()
    {
        $this->_headerText = Mage::helper('byjuno')->__('Log');

         parent::__construct();
        
        $this->setId('byjunoGrid');
        $this->_controller = 'byjuno';
    }

    protected function _prepareCollection()
    {
        $model = Mage::getModel('byjuno/byjuno');
        $collection = $model->getCollection();
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    public function getMainButtonsHtml() {
        $html = parent::getMainButtonsHtml();
        $url = $this->getUrl('*/*/logexport', array('export' => 'true'));
        $add_artwork_button = $this->getLayout()->createBlock('adminhtml/widget_button')
            ->setData(array(
                'label'     => Mage::helper('byjuno')->__('Export logs'),
                'onclick'   => 'window.open(\'' . $url . '\', \'_blank\')',
            ));
        $html .= $add_artwork_button->toHtml();
        return $html;
    }

    public function getRowUrl($row)
    {
        // This is where our row data will link to
        return $this->getUrl('*/*/logedit', array('id' => $row->getId()));
    }

    protected function _prepareColumns()
    {

        $this->addColumn('byjuno_id', array(
            'header'        => Mage::helper('byjuno')->__('ID'),
            'align'         => 'right',
            'width'         => '50px',
            'filter_index'  => 'byjuno_id',
            'index'         => 'byjuno_id',
        ));
        $this->setDefaultSort('byjuno_id');
        $this->setDefaultDir('desc');

        $this->addColumn('request_id', array(
            'header'        => Mage::helper('byjuno')->__('Request ID'),
            'align'         => 'left',
            'width'         => '150px',
            'filter_index'  => 'request_id',
            'index'         => 'request_id',
            'type'          => 'text',
            'truncate'      => 50,
            'escape'        => true,
        ));

        $this->addColumn('type', array(
            'header'        => Mage::helper('byjuno')->__('Request type'),
            'align'         => 'left',
            'width'         => '150px',
            'filter_index'  => 'type',
            'index'         => 'type',
            'type'          => 'text',
            'truncate'      => 50,
            'escape'        => true,
        ));


        $this->addColumn('firstname', array(
            'header'        => Mage::helper('byjuno')->__('Firstname'),
            'align'         => 'left',
            'width'         => '150px',
            'filter_index'  => 'firstname',
            'index'         => 'firstname',
            'type'          => 'text',
            'truncate'      => 50,
            'escape'        => true,
        ));


        $this->addColumn('lastname', array(
            'header'        => Mage::helper('byjuno')->__('Lastname'),
            'align'         => 'left',
            'width'         => '150px',
            'filter_index'  => 'lastname',
            'index'         => 'lastname',
            'type'          => 'text',
            'truncate'      => 50,
            'escape'        => true,
        ));

        $this->addColumn('ip', array(
            'header'        => Mage::helper('byjuno')->__('IP'),
            'align'         => 'left',
            'width'         => '150px',
            'filter_index'  => 'ip',
            'index'         => 'ip',
            'type'          => 'text',
            'truncate'      => 50,
            'escape'        => true,
        ));

        $this->addColumn('status', array(
            'header'        => Mage::helper('byjuno')->__('Status'),
            'align'         => 'left',
            'width'         => '150px',
            'filter_index'  => 'status',
            'index'         => 'status',
            'type'          => 'text',
            'truncate'      => 50,
            'escape'        => true,
        ));


        $this->addColumn('creation_date', array(
            'header'        => Mage::helper('byjuno')->__('Date'),
            'align'         => 'left',
            'width'         => '150px',
            'filter_index'  => 'creation_date',
            'index'         => 'creation_date',
            'type'          => 'datetime',
            'truncate'      => 50,
            'escape'        => true,
        ));


        $this->addColumn('request_start', array(
            'header'        => Mage::helper('byjuno')->__('Request start time'),
            'align'         => 'left',
            'width'         => '150px',
            'filter_index'  => 'request_start',
            'index'         => 'request_start',
            'type'          => 'datetime',
            'truncate'      => 50,
            'escape'        => true,
        ));


        $this->addColumn('request_end', array(
            'header'        => Mage::helper('byjuno')->__('Request end time'),
            'align'         => 'left',
            'width'         => '150px',
            'filter_index'  => 'request_end',
            'index'         => 'request_end',
            'type'          => 'datetime',
            'truncate'      => 50,
            'escape'        => true,
        ));

        return parent::_prepareColumns();
    }

}