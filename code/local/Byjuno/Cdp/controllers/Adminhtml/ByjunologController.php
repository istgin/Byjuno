<?php
class Byjuno_Cdp_Adminhtml_ByjunologController extends Mage_Adminhtml_Controller_Action
{
    public function logAction()
    {
        $this->loadLayout()->_addContent($this->getLayout()->createBlock('byjuno/admin_log'))->renderLayout();
    }

    public function logeditAction()
    {
        $this->loadLayout()->_addContent($this->getLayout()->createBlock('byjuno/admin_logedit'))->renderLayout();
    }

    public function logexportAction()
    {
        @ini_set('max_execution_time', 0);
        @set_time_limit(0);
        $model = Mage::getModel('byjuno/byjuno');
        $model->getTableName('byjuno');
        /* @var $collection Byjuno_Cdp_Model_Mysql4_Byjuno_Collection */
        $collection = $model->getCollection();
        $table = $collection->getByjunoTable();
        $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $sql        = "Select * from `".$table."` order by byjuno_id DESC";
        $rows       = $connection->fetchAll($sql);
        $array = Array();
        foreach($rows as $row) {
            $array[] = Array(
                $row["byjuno_id"],
                $row["firstname"],
                $row["lastname"],
                $row["town"],
                $row["postcode"],
                $row["street1"],
                $row["country"],
                $row["ip"],
                $row["status"],
                $row["request_id"],
                $row["type"],
                $row["error"],
                $row["request"],
                $row["response"],
                $row["creation_date"],
                $row["request_start"],
                $row["request_end"]
            );
        }
        $this->array_to_csv_download(
            $array,
            "byjunoexport.csv"
        );
        exit();
    }

    private function array_to_csv_download($array, $filename = "byjunoexport.csv", $delimiter=";") {
        header('Content-Type: application/csv');
        header('Content-Disposition: attachment; filename="'.$filename.'";');
        $f = fopen('php://output', 'w');
        foreach ($array as $line) {
            fputcsv($f, $line, $delimiter);
        }
    }
    
}
