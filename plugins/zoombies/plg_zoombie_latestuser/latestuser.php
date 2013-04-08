<?php

/**
 * Zoombie DB Backup cron plugin
 * Embedd a DB backup on Joomla! 
 *
 * @author: Alikon
 * @version: 1.1.0
 * @release: 07/04/2013 21.50
 * @package: Alikonweb.zoombie 4 Joomla
 * @copyright: (C) 2007-2013 Alikonweb.it
 * @license: http://www.gnu.org/copyleft/gpl.html GNU/GPL
 *
 *
 *
 * */
// no direct access
defined('_JEXEC') or die('Restricted access');

class plgZoombieLatestUser extends JPlugin {

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
    var $runned = null;
    var $task_i_time=null;
    
    function plgZoombieLatestUser(&$subject, $params) {
        parent::__construct($subject, $params);


        ini_set('display_errors', 0);
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', 480);
        $this->cfg = JFactory::getConfig();
        $this->task_i_time = microtime(true);
    }

    function goAliveLatestUser($time) {
        $lang = JFactory::getLanguage();
        $lang->load('plg_zoombie_latestuser');
        // Include the JLog class.
        jimport('joomla.log.log');
        // Get the date so that we can roll the logs over a time interval.
        $this->date = JFactory::getDate()->format('Y-m-d');
        $config =  JFactory::getApplication();
        $this->name = 'sitename';  //$config->getCfg('db');        
        // Add a start message.
        $this->runned = (int) $this->params->get('runned', 0);
        $this->runned++;
        JLog::add('Start task #' . $this->runned . ' ZoombieLatestUser');
        $this->dbo = JFactory::getDBO();

        $this->_latestuser();
        $task_time = round(microtime(true) - $this->task_i_time, 3);
        JLog::add('End task: ZoombieLatestUser in ' . $task_time);
        return 4;
        //return 8;
    }

    function _latestuser() {
        $sendmail = $this->params->get('sendmail', false);
        $last_run = JFactory::getDate($this->params->get('last_run'));
        $last_run->setTimeZone(new DateTimeZone($this->cfg->getValue('config.offset')));
       // VAR_DUMP($last_run->toUNIX());
       // VAR_DUMP($last_run->toMysql());
        $query = $this->dbo->getQuery(true);
        $query->select('a.id, a.name, a.username, a.email, UNIX_TIMESTAMP(' . $this->dbo->quoteName('a.registerDate') . ') as utr ');
        $query->order('a.registerDate DESC');
        $query->from('#__users AS a');
        $user = JFactory::getUser();
        if (!$user->authorise('core.admin') && $this->params->get('filter_groups', 0) == 1) {
            $groups = $user->getAuthorisedGroups();
            if (empty($groups)) {
                return array();
            }
            $query->leftJoin('#__user_usergroup_map AS m ON m.user_id = a.id');
            $query->leftJoin('#__usergroups AS ug ON ug.id = m.group_id');
            $query->where('ug.id in (' . implode(',', $groups) . ')');
            $query->where('ug.id <> 1');
            //  $query->where('UNIX_TIMESTAMP(' . $this->dbo->quoteName('a.registerDate') . ')  > ' . $last_run->toUnix());
        }
        //var_dump($query);

        $this->dbo->setQuery($query, 0, $this->params->get('shownumber'));
        $items = $this->dbo->loadObjectList();
        // var_dump($items);
        $datas = array();
        foreach ($items as &$item) {
            $item->utr = JFactory::getDate($item->utr);
            $item->utr->setTimeZone(new DateTimeZone($this->cfg->getValue('config.offset')));
            /*
              echo('utr:'.$item->utr.'<br>');
              echo('utrtoU:'.$item->utr->toUnix().'<br>');
              echo('utrtom:'.$item->utr->toMysql().'<br>');
              echo('<br>las:'.$last_run.'<br>');
              echo('lasU:'.$last_run->toUnix().'<br>');
              echo('lasm:'.$last_run->toMysql().'<br>');
             */
            //  JLog::add('User :' . $item->id . ' ' . $item->username . ' registerd:' . $item->utr. '-' .date('d.m.Y, H:i:s', $item->utr).' lastrun '.$last_run->toMysql().'-'.date('d.m.Y, H:i:s', $last_run->toUnix()));
            if ($item->utr > $last_run->toMysql()) {
                $data = new stdClass();
                $data->id = $item->id;
                $data->name = $item->name;
                $data->username = $item->username;
                $data->email = $item->email;
                $data->utr = $item->utr;
                $datas[] = $data;
            }
        }
        //  jexit(var_dump($datas));
        if (($sendmail)&&(count($datas)>1)) {
            $this->sendNotice($datas);
        }
    }

    protected function sendNotice($items) {
        $last_run = JFactory::getDate($this->params->get('last_run'));
        $last_run->setTimeZone(new DateTimeZone($this->cfg->getValue('config.offset')));
        JLog::add('Found ' . count($items) . ' users');
        $task_time = round(microtime(true) - $this->task_i_time, 3);
        //jexit(var_dump($items));
        //$date = JFactory::getDate()->format('Y-m-d');
        $config =  JFactory::getConfig();
        $now = JFactory::getDate();
        $now = $now->toUnix();
        $fromemail = $config->getValue('config.mailfrom');
        $sendfile = $this->params->get('sendfile', false);
        $toemail = $fromemail;
        $runned = (int) $this->params->get('runned', 0);
        $interval = (int) ($this->params->get('interval', 5) * 60);
        $next = $interval + $now;
        $subject = 'LatestUser Zoombie task : ' .  $config->getValue('config.sitename');
        $body = "\n Task    #:" . ++$runned . "\n"
                . "Task name: Zoombie LatestUser" . "\n"
                . "Runned   : " . date('d.m.Y, H:i:s', $now) . "\n"
                . "Times    : " . $task_time . "\n"
                . "Scheduled: " . date('d.m.Y, H:i:s', $next) . "\n";
        //
        foreach ($items as &$item) {

            JLog::add('User ' . $item->username . ' reg date:' . $item->utr . ' lastrun ' . $last_run->toMysql() . ' ris' . ($item->utr > $last_run->toMysql()));
            //  if ($item->utr > $last_run->toUnix()) {
            $body .= "\n\nUserID  :" . $item->id;
            $body .= "\nName    :" . $item->name;
            $body .= "\nUsername:" . $item->username;
            $body .= "\nE-mail  :" . $item->email;
            $body .= "\nRegister:" . $item->utr;
            $body .= "\n\n";

            //  }
        }



        $body .= "\n\n Zoombie Task Scheduler Application 4 Joomla by  http://www.alikonweb.it \n";
        $mailer = JFactory::getMailer();
        $mailer->setSender(array($fromemail, 'Zoombie Task latestuser'));
        $mailer->addRecipient($toemail);
        $mailer->setSubject($subject);
        $mailer->setBody($body);
        $mailer->IsHTML(false);

        return $mailer->Send();
    }

}