<?php

/**
 * CronDetector cron plugin
 * Embedd a spam report on Joomla! Kunena component
 * 
 * @author: Alikon
 * @version: 1.0.0
 * @release: 22/10/2012 21.50
 * @package: Alikonweb.detector 4 Kunena
 * @copyright: (C) 2007-2012 Alikonweb.it
 * @license: http://www.gnu.org/copyleft/gpl.html GNU/GPL
 *
 *
 *
 * */
// no direct access
defined('_JEXEC') or die('Restricted access');

class plgZoombieSlicommentsDetector extends JPlugin {

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
    
         function plgZoombieSlicommentsDetector( &$subject, $params )
	{
		parent::__construct( $subject, $params );

	
	}

    function goAliveslicommentsdetector() {
    	  $this->lang = JFactory :: getLanguage();
        //$this->lang->load('plg_cron_userdetector', JPATH_ADMINISTRATOR);
        $this->lang->load('plg_zoombie_slicommentsdetector');
        // Include the JLog class.
        jimport('joomla.log.log');
        // Get the date so that we can roll the logs over a time interval.
        $date = JFactory::getDate()->format('Y-m-d');


        if (JPluginHelper::isEnabled('alikonweb', 'detector')) {
            // Add a start message.
            JLog::add('Start job: ZoombieSlicommnetsDetector.');
             $this->dbo = JFactory::getDBO();
             $this->_detectc();
            JLog::add('End job: ZoombieSlicommentsDetector.');
            return 4;
        } else {
            return 8;
        }
    }
    private function _detectc() {
    	 $jtime =null;
    	 $jtime = microtime(true);
        // Import the detector plugin.
        JPluginHelper::importPlugin('alikonweb');
        // Checking for enabled.

        $query = $this->dbo->getQuery(true);
        $query = 'select id, name AS username, raw AS title, text AS description,' .
                ' IFNULL(email,' . $this->dbo->quote('guest@guest.com') . ') AS email ' .
                'FROM #__slicomments WHERE status=0 ORDER by id desc LIMIT 0 , 5';

        // Push the query builder object into the database connector.
        $this->dbo->setQuery($query);

        // Get all the returned rows from the query as an array of objects.
        $rows = $this->dbo->loadObjectList();
        // Startup reporting.
        JLog::add (JText::sprintf('ZOOMBIE_SLICOMMENTSDETECTOR_ITEMS', count($rows)));
        foreach ($rows as $user) {
            $i++;           
            $info_detector = JDispatcher::getInstance()->trigger('onDetectText', array(null, $user->email, $user->username, $user->description, ''));
            // echo ($info_detector[0]['text']);
            $formattedip = sprintf("%36s", $user->email);
            $formattedun = sprintf("%-15s", $user->username);
            $formattedid = sprintf("[%11s]", $user->id);
            if ($info_detector[0]['score'] >= 4) {

                $this->dbo->setQuery(
                        'UPDATE #__slicomments SET status = -1 WHERE id=' . (int) $user->id
                );
                $this->dbo->query();
            }
            JLog::add ($formattedid . ' name:' . $formattedun . ' mail:' . $formattedip . ' report:' . $info_detector[0]['text']);
           
        }
         JLog::add (JText::sprintf('ZOOMBIE_PROCESS_SLICOMMENTSDETECTOR_COMPLETE', round(microtime(true) - $jtime, 3)));
    }
 
}