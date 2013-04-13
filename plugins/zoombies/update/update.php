<?php

/**
 * Zoombie Extension update cron plugin
 * Embedd Extension update on Joomla! 
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
    var $task_i_time=null;

    function plgZoombieUpdate(&$subject, $params) {
        $this->task_i_time = microtime(true);
        parent::__construct($subject, $params);

        if (!defined('DS')) {
            define('DS', DIRECTORY_SEPARATOR);
        }
        ini_set('display_errors', 0);
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', 480);
    }

    function goAliveUpdate($time) {
        $this->runned = (int) $this->params->get('runned', 0);
        $this->runned++;
        $this->lang = JFactory :: getLanguage();
        $this->lang->load('plg_zoombie_update');
        $sendmail = (int) $this->params->get('sendmail', '0');
        // Include the JLog class.
        jimport('joomla.log.log');
        // Get the date so that we can roll the logs over a time interval.
        $this->date = JFactory::getDate()->format('Y-m-d');
        $config =  JFactory::getApplication();
        $this->name = 'sitename';  //$config->getCfg('db');        
        // Add a start message.  
        JLog::add('Start task #' . $this->runned . ' ZoombieUpdate');
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
        //echo var_dump($results);
        if ($sendmail) {
           $this->sendNotice($results);
        }

        $task_time = round(microtime(true) - $this->task_i_time, 3);
        JLog::add('End task: ZoombieUpdate. in ' . $task_time);
        return 4;
        //return 8;
    }

    protected function sendNotice($items) {
        $config =  JFactory::getConfig();
        $now = JFactory::getDate();
        $now = $now->toUnix();
        $fromemail = $config->getValue('config.mailfrom');
        $sendfile = $this->params->get('sendfile', false);
        $runned = (int) $this->params->get('runned', 0);
        $interval = (int) ($this->params->get('interval', 5) * 60);
        $next = $interval + $now;
        $toemail = $fromemail;
        $task_time = round(microtime(true) - $this->task_i_time, 3);
        $subject = 'Update Zoombie task : ' . $config->getValue('config.sitename');
        $body = "\n Task    #:" . ++$runned . "\n"
                . "Task name: Zoombie Update" . "\n"
                . "Runned   : " . date('d.m.Y, H:i:s', $now) . "\n"
                . "Times    : " . $task_time . "\n"
                . "Scheduled: " . date('d.m.Y, H:i:s', $next) . "\n";







        $body .= "\n\n Zoombie Application 4 Joomla  by  http://www.alikonweb.it \n";
        $mailer = JFactory::getMailer();
        $mailer->setSender(array($fromemail, 'Zoombie Task Update'));
        $mailer->addRecipient($toemail);
        $mailer->setSubject($subject);
        $mailer->setBody($body);
        $mailer->IsHTML(false);
        //  var_dump($body);
        return $mailer->Send();
    }

}