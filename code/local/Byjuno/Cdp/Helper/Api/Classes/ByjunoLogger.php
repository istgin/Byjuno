<?php
/**
 * Created by Byjuno.
 * User: i.sutugins
 * Date: 14.2.9
 * Time: 10:28
 */
class Byjuno_Cdp_Helper_Api_Classes_ByjunoLogger
{
    private static $instance = NULL;
    private $logs;

    private function __construct() {
        $this->logs = array();
    }

    public static function getInstance() {
        if(self::$instance === NULL) {
            self::$instance = new Byjuno_Cdp_Helper_Api_Classes_ByjunoLogger();
        }
        return self::$instance;
    }
};