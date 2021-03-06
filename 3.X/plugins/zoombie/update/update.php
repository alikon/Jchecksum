<?php

/**
 * Zoombie Extension update cron plugin
 * Embedd Extension update on Joomla! 
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

class plgZoombieUpdate extends JPlugin {

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

    function plgZoombieUpdate(&$subject, $params) {
        parent::__construct($subject, $params);

        if (!defined('DS')) {
            define('DS', DIRECTORY_SEPARATOR);
        }
        ini_set('display_errors', 0);
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', 480);
    }

    function goAliveUpdate($time) {
        $this->lang = JFactory :: getLanguage();
        $this->lang->load('plg_zoombie_update');
        // Include the JLog class.
        jimport('joomla.log.log');
        // Get the date so that we can roll the logs over a time interval.
        $this->date = JFactory::getDate()->format('Y-m-d');
        $config = & JFactory::getApplication();
        $this->name = 'sitename';  //$config->getCfg('db');        
        // Add a start message.
        JLog::add('Start job: ZoombieUpdate.');
        $this->dbo = JFactory::getDBO();
        // Get the update cache time
		jimport('joomla.application.component.helper');
		$component = JComponentHelper::getComponent('com_installer');

		$params = $component->params;
		$cache_timeout = $params->get('cachetimeout', 6, 'int');
		$cache_timeout = 3600 * $cache_timeout;

		// Find all updates
		//$this->out('Fetching updates...');
		$updater = JUpdater::getInstance();
		$results = $updater->findUpdates(0, $cache_timeout);
		//$this->out('Finished fetching updates');
     //		echo var_dump($results);
        JLog::add('End job: ZoombieUpdate.');
        return 4;
        //return 8;
    }





}