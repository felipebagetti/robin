<?php

require_once 'Zend/Db/Profiler.php';

/**
 * Basic DB Profiler saving SQL execution data in a CSV file
 */
class Db_Profiler extends Zend_Db_Profiler {
    
    /**
     * Log a query to a file
     * 
     * @param String $queryText
     * @param float $time
     */
    protected function _queryLog($queryText, $time = null){

        if(Util::isDev()){
            $queryText = date("H:i:s") . ";" . ($time != null ? $time . ";" : "") . preg_replace("(\n|\r)", "", $queryText) . "\n";
            $queryText = preg_replace("([\s]{2,})", " ", $queryText);
            
            $filename = dirname($_SERVER['SCRIPT_FILENAME'])."/log/sql-".date("Y-m-d").($time == null ? "-pre" : "").".sql";

            if(is_writable(dirname($filename))){
                $fp = fopen($filename, "a+");
                fwrite($fp, $queryText);
                fclose($fp);
            }
        }
        
    }
    
    /**
     * (non-PHPdoc)
     * @see Zend_Db_Profiler::queryStart()
     */
    public function queryStart($queryText, $queryType = null){
        $this->_queryLog($queryText);
        return parent::queryStart($queryText, $queryType);
    }
    
    /**
     * (non-PHPdoc)
     * @see Zend_Db_Profiler::queryEnd()
     */
    public function queryEnd($queryId){
        
        $ret = parent::queryEnd($queryId);
        
        $query = $this->getQueryProfile($queryId);
        
        $this->_queryLog($query->getQuery(), number_format($query->getElapsedSecs(), 4));
        
        return $ret;        
    }
    
}
