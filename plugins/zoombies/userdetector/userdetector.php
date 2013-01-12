<?php

/**
 * Zoombie Detector cron plugin
 * Embedd a spam report on Joomla! Kunena component
 * 
 * @author: Alikon
 * @version: 1.0.0
 * @release: 22/10/2012 21.50
 * @package: Alikonweb.detector 4 Joomla
 * @copyright: (C) 2007-2012 Alikonweb.it
 * @license: http://www.gnu.org/copyleft/gpl.html GNU/GPL
 *
 *
 *
 * */
// no direct access
defined('_JEXEC') or die('Restricted access');

class plgZoombieUserDetector extends JPlugin {

    /**
     * Constructor function
     *
     * @param object $subject
     * @param object $config
     * @return plgCronDetector4kunena
     */
    var $cfg = null;
    var $mailfrom = null;
    var $fromname = null;
    var $app = null;
    var $dbo = null;
    var $lang = null;
    var $user = null;
    var $testo = null;

    function plgZoombieUserDetector(&$subject, $params) {
        parent::__construct($subject, $params);
    }

    function goAliveuserdetector($time) {
        $this->lang = JFactory :: getLanguage();
        //$this->lang->load('plg_cron_userdetector', JPATH_ADMINISTRATOR);
        $this->lang->load('plg_zoombie_userdetector');
        // Include the JLog class.
        jimport('joomla.log.log');
        // Get the date so that we can roll the logs over a time interval.
        $date = JFactory::getDate()->format('Y-m-d');


        if (JPluginHelper::isEnabled('alikonweb', 'detector')) {
            // Add a start message.
            JLog::add('Start job: ZoombieUserDetector.');
            if (php_sapi_name() == 'cli' && empty($_SERVER['REMOTE_ADDR'])) {
                JLog::add('job from CLI: ZoombieUserDetector.');
            } else {
                JLog::add('job from WEB: ZoombieUserDetector.');
            }
            $this->dbo = JFactory::getDBO();
            $this->_detectu($time);
            JLog::add('End job: ZoombieUserDetector.');
            return 4;
        } else {
            return 4;
        }
    }
    private function _detectu($lastrun) {
        $jtime = microtime(true);

        // Import the detector plugin. 7200 1gg
        JPluginHelper::importPlugin('alikonweb');
        // Checking for enabled.
//JLog::add('last:'.$lastrun);
        //  $lastrun=$lastrun+7200;
        $query = $this->dbo->getQuery(true);

        $maxcheck = (int) $this->params->get('maxcheck', 5);
        $days = (int) $this->params->get('days', 1);
        // JLog::add('Start job:'.$maxcheck.' d:'.$days);
        $query = 'select * FROM #__userextras e , #__users u WHERE '
                . ' u.id=e.id AND e.hscore=0 and u.registerdate > ADDDATE(now(), INTERVAL -' . $days . ' DAY)  order by u.registerdate desc LIMIT 0 , ' . $maxcheck;
        // Push the query builder object into the database connector.
//JLog::add('query:'.$query);        
        $this->dbo->setQuery($query);

        // Get all the returned rows from the query as an array of objects.
        $rows = $this->dbo->loadObjectList();
        // Startup reporting.
//		echo '000';
        JLog::add(JText::sprintf('ZOOMBIE_USERDETECTOR_ITEMS', count($rows)));
        foreach ($rows as $user) {
            $i++;

            $info_detector = JDispatcher::getInstance()->trigger('onDetectUser', array($user->ip, $user->email, $user->username, ' ', ' '));

            $formattedip = sprintf("%16s", $user->ip);
            $formattedun = sprintf("%-15s", $user->username);
            $formattedid = sprintf("[%11s]", $user->id);
            if ($info_detector[0]['score'] >= 4) {
                $this->dbo->setQuery(
                        'UPDATE #__users SET block =   1 WHERE id=' . (int) $user->id
                );
                $this->dbo->query();
            }
            JLog::add($formattedid . ' username:' . $formattedun . ' ip:' . $formattedip . ' report:' . $info_detector[0]['text']);
            $this->dbo->setQuery(
                    'UPDATE #__userextras SET hscore =  ' . (int) $info_detector[0]['score'] . '  WHERE id=' . (int) $user->id
            );
            $this->dbo->query();
        }
        JLog::add(JText::sprintf('ZOOMBIE_PROCESS_USERDETECTOR_COMPLETE', round(microtime(true) - $jtime, 3)));
    }

}