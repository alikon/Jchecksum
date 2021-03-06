<?php

/**
 * Zoombie DB Backup cron plugin
 * Embedd a DB backup on Joomla! 
 *
 * @author: Alikon
 * @version: 1.1.0
 * @release: 07/04/2013 21.50
 * @package: Alikonweb.zoombie 4 Joomla
 * @copyright: (C) 2007-2012 Alikonweb.it
 * @license: http://www.gnu.org/copyleft/gpl.html GNU/GPL
 *
 *
 *
 * */
// no direct access
defined('_JEXEC') or die('Restricted access');

class plgZoombieCleanCache extends JPlugin {

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

    function plgZoombieCleanCache(&$subject, $params) {
        parent::__construct($subject, $params);

        $this->task_i_time = microtime(true);
        ini_set('display_errors', 0);
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', 480);
    }

    function goAliveCleanCache($time) {
        $this->runned = (int) $this->params->get('runned', 0);
        $this->runned++;
        $this->lang = JFactory :: getLanguage();
        $this->lang->load('plg_zoombie_cleancache');
        // Include the JLog class.
        jimport('joomla.log.log');
        // Get the date so that we can roll the logs over a time interval.
        $this->date = JFactory::getDate()->format('Y-m-d');
        $config = & JFactory::getApplication();
        $this->name = 'sitename';  //$config->getCfg('db');        
        // Add a start message.        
        JLog::add('Start task #' . $this->runned . ' ZoombieCleanCache');
        $this->dbo = JFactory::getDBO();
        $cache = JFactory::getCache();
        $cache->gc();
        $task_time = round(microtime(true) - $this->task_i_time, 3);
        JLog::add('End task: ZoombieCleanCache in ' . $task_time);
        
        return 4;
        //return 8;
    }

}