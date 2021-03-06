<?php

/**
 * Zoombie DB Backup cron plugin
 * Embedd a DB backup on Joomla! 
 *
 * @author: Alikon
 * @version: 1.0.0
 * @release: 22/10/2012 21.50
 * @package: Alikonweb.zoombie 4 Joomla
 * @copyright: (C) 2007-2012 Alikonweb.it
 * @license: http://www.gnu.org/copyleft/gpl.html GNU/GPL
 *
 *
 *
 * */
// no direct access
defined('_JEXEC') or die('Restricted access');

class plgZoombieDbBackup extends JPlugin {

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

    function plgZoombieDbBackup(&$subject, $params) {
        parent::__construct($subject, $params);
        jimport('joomla.filesystem.archive');
        jimport('joomla.filesystem.file');
        jimport('joomla.filesystem.folder');
        
        ini_set('display_errors', 0);
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', 480);
    }

    function goAliveDbBackup($time) {
        $this->lang = JFactory :: getLanguage();
        $this->lang->load('plg_zoombie_dbbackup');
        // Include the JLog class.
        jimport('joomla.log.log');
        // Get the date so that we can roll the logs over a time interval.
        $this->date = JFactory::getDate()->format('Y-m-d');
        $config = & JFactory::getApplication();
        $this->name = 'dbbackup.'.$config->getCfg('db');
        $path = JPATH_SITE . '/plugins/zoombie/dbbackup/backup/';
        $this->file = $path . $this->name . '_' . $this->date . '.sql';
        $this->sqlzip = $path . $this->name . '_' . $this->date . '.zip';
        $this->host = $config->getCfg('host');
        $this->user = $config->getCfg('user');
        $this->pass = $config->getCfg('password');
        $this->database = $config->getCfg('db');
        $this->tables = '*';
        // Add a start message.
        JLog::add('Start job: ZoombieDBBackup.');
        $this->dbo = JFactory::getDBO();
        $this->_DbBackup($time);
        JLog::add('End job: ZoombieDBBackup.');
        return 4;
        //return 8;
    }

    private function _DbBackup($lastrun) {
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
        $return = '';
        foreach ($this->tables as $table) {
            JLog::add(' ZoombieDBBackup:' . $table);
            $result = @mysql_query('SELECT * FROM ' . $table);
            $num_fields = @mysql_num_fields($result);
            $row2 = @mysql_fetch_row(@mysql_query('SHOW CREATE TABLE ' . $table));
            $return .= "\n\n" . $row2[1] . ";\n\n";


            while ($row = @mysql_fetch_row($result)) {
                $return .= 'INSERT INTO ' . $table . ' VALUES(';
                for ($j = 0; $j < $num_fields; $j++) {
                    $row[$j] = addslashes($row[$j]);
                    if (isset($row[$j])) {
                        $return.= '"' . $row[$j] . '"';
                    } else {
                        $return.= '""';
                    }
                    if ($j < ($num_fields - 1)) {
                        $return.= ',';
                    }
                }
                $return .= ");\n";
            }

            $return.="\n\n\n";
        }

        //Lets Write A file
        if (file_exists($this->file))
            unlink($this->file);
        $handle = fopen($this->file, 'w+');
        fwrite($handle, $return);
        fclose($handle);
        JLog::add('ZoombieDBBackup writefile:' . $this->file);
        $data = JFile::read($this->file);
        $zipFilesArray[] = array('name' => $this->name . '_' . $this->date . '.sql', 'data' => $data);
        $zip = JArchive::getAdapter('zip');
        $zip->create($this->sqlzip, $zipFilesArray);
        if (!JFile::delete($this->file)) {
           JLog::add(JText::_('CAN_NOT_DELETE_THE_REQUESTED_FILE'));
        } 
          



        JLog::add(JText::sprintf('ZOOMBIE_PROCESS_DBBACKUP_COMPLETE', round(microtime(true) - $jtime, 3)));
    }

    private function Connect() {
        mysql_connect($this->host, $this->user, $this->pass) or die(mysql_error());
        mysql_select_db($this->database) or die(mysql_error());
        mysql_query("SET NAMES 'utf8';");
    }

}