<?php

/**
 * Zoombie DB Backup cron plugin
 * Embedd a DB backup on Joomla! 
 *
 * @author:  Alikon
 * @version:  1.1.1
 * @release:  11/04/2013 21.50
 * @package:  Alikonweb.zoombie 4 Joomla
 * @copyright: (C) 2007-2013 Alikonweb.it
 * @license:  http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link:     http://www.alikonweb.it
 *
 *
 *
 * */
// no direct access
defined('_JEXEC') or die('Restricted access');

class plgZoombieDBOptimize extends JPlugin {

    /**
     * Constructor function
     *
     * @param object $subject
     * @param object $config
     * @return plgZoombieFileBackup
     */
    var $cfg = null;
    var $mailfrom = null;
    var $fromname = null;
    var $name = null;
    var $dbo = null;
    var $lang = null;
    var $date = null;
    var $file = null;
    var $sqlzip = null;
    var $task_i_time=null;

    function plgZoombieDBOptimize(&$subject, $params) {
        $this->task_i_time = microtime(true);
        parent::__construct($subject, $params);
        jimport('joomla.filesystem.archive');
        jimport('joomla.filesystem.file');
        jimport('joomla.filesystem.folder');
        if (!defined('DS')) {
            define('DS', DIRECTORY_SEPARATOR);
        }
        ini_set('display_errors', 0);
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', 480);
    }

    function goAliveDBOptimize($time) {
        $this->runned = (int) $this->params->get('runned', 0);
        $this->runned++;
        JLog::add('Start task #' . $this->runned . ' ZoombieDBOptimize');    
        $this->lang = JFactory :: getLanguage();
        $this->lang->load('plg_zoombie_dboptimize');
        // Include the JLog class.
        jimport('joomla.log.log');
        // Get the date so that we can roll the logs over a time interval.
        $this->date = JFactory::getDate()->format('Y-m-d');
        $config = JFactory::getApplication();
        $this->name = 'sitename';  //$config->getCfg('db');
        $this->file = JPATH_ROOT . '/tmp/' . $this->name . '_' . $this->date . '.sql';
        $this->sqlzip = JPATH_ROOT . '/tmp/SQL' . $this->name . '_' . $this->date . '.zip';
        $this->host = $config->getCfg('host');
        $this->user = $config->getCfg('user');
        $this->pass = $config->getCfg('password');
        $this->database = $config->getCfg('db');
        $this->tables = '*';
        // Add a start message.
    
        $this->dbo = JFactory::getDBO();
        $this->_DBOptimize($time);
        $task_time = round(microtime(true) - $this->task_i_time, 3);
        JLog::add('End task: ZoombieDBOptimizee in ' . $task_time);
 
        return 4;
        //return 8;
    }

    private function _DBOptimize($lastrun) {
        $jtime = microtime(true);
        $interval = (int) $this->params->get('interval', 5);
        $this->tables = $this->params->get('tables', array('*'));
        //JLog::add('Start job:' . $interval . ' t:' . $this->tables);
        //jexit(var_dump($this->tables));
        $this->Connect();
        //get list of the tables
        if ((count($this->tables) == 1) && ($this->tables[0] == '*')) {
            $this->tables = array();
            $result = mysql_query('SHOW TABLES');
            while ($row = mysql_fetch_row($result)) {
                $this->tables[] = $row[0];
            }
        } else {
            $this->tables = is_array($this->tables) ? $this->tables : explode(',', $this->tables);
        }

        //processs each
        $return = "";
        foreach ($this->tables as $table) {
            JLog::add(' ZoombieDBOptimize:' . $table);
            $result = @mysql_query('OPTIMIZE TABLE ' . $table);
            $num_fields = @mysql_num_fields($result);
        }

        JLog::add(JText::sprintf('ZOOMBIE_PROCESS_DBOPTIMIZE_COMPLETE', round(microtime(true) - $jtime, 3)));
    }

    private function Connect() {
        mysql_connect($this->host, $this->user, $this->pass) or die(mysql_error());
        mysql_select_db($this->database) or die(mysql_error());
        mysql_query("SET NAMES 'utf8';");
    }

}