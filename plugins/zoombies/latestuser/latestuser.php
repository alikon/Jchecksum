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
    var $sqlzip = null;

    function plgZoombieLatestUser(&$subject, $params) {
        parent::__construct($subject, $params);

        if (!defined('DS')) {
            define('DS', DIRECTORY_SEPARATOR);
        }
        ini_set('display_errors', 0);
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', 480);
    }

    function goAliveLatestUser($time) {
        $lang = JFactory::getLanguage();
		$lang->load('plg_zoombie_latestuser');
        // Include the JLog class.
        jimport('joomla.log.log');
        // Get the date so that we can roll the logs over a time interval.
        $this->date = JFactory::getDate()->format('Y-m-d');
        $config = & JFactory::getApplication();
        $this->name = 'sitename';  //$config->getCfg('db');        
        // Add a start message.
        JLog::add('Start job: Zoombielatestuser.');
        $this->dbo = JFactory::getDBO();
        
        $this->_latestuser();
        JLog::add('End job: Zoombielatestuser.');
        return 4;
        //return 8;
    }

    function _latestuser() {
       $query	= $this->dbo->getQuery(true);
		$query->select('a.id, a.name, a.username, a.registerDate');
		$query->order('a.registerDate DESC');
		$query->from('#__users AS a');
		$user = JFactory::getUser();
		if (!$user->authorise('core.admin') && $this->params->get('filter_groups', 0) == 1)
		{
			$groups = $user->getAuthorisedGroups();
			if (empty($groups))
			{
				return array();
			}
			$query->leftJoin('#__user_usergroup_map AS m ON m.user_id = a.id');
			$query->leftJoin('#__usergroups AS ug ON ug.id = m.group_id');
			$query->where('ug.id in (' . implode(',', $groups) . ')');
			$query->where('ug.id <> 1');
		}
		$this->dbo->setQuery($query, 0, $this->params->get('shownumber'));
		$items = $this->dbo->loadObjectList();
       jexit(var_dump($items));
        //$date = JFactory::getDate()->format('Y-m-d');
        $config = & JFactory::getConfig();
        $now = &JFactory::getDate();
        $now = $now->toUnix();
        $fromemail = $config->getValue('config.mailfrom');
        $sendfile = $this->params->get('sendfile', false);
        $toemail = $fromemail;
        $subject = $zoombie . ' : ' . $config->getValue('config.sitename');
        $body = "\n\n * Zoombie latestuser runned at " . date('d.m.Y, H:i:s', $now) . "\n";
        //
        foreach ($items as &$item) {
            $item->slug = $item->id . ':' . $item->alias;
            $item->catslug = $item->catid . ':' . $item->category_alias;

            if ($access || in_array($item->access, $authorised)) {
                // We know that user has the privilege to view the article
                $item->link = JRoute::_(ContentHelperRoute::getArticleRoute($item->slug, $item->catslug));
            } else {
                $item->link = JRoute::_('index.php?option=com_users&view=login');
            }
            $body .= "\n\nTitle:" . $item->title;
            $body .= "\n\nAuthor:" . $item->modified_by_name;
            $body .= "\n\nlink:" . JPATH_ROOT.$item->link;
        }



        $body .= "\n\n Zoombie Application 4 Joomla  by  http://www.alikonweb.it \n";
        $mailer = & JFactory::getMailer();
        $mailer->setSender(array($fromemail, 'Zoombie Task latestuser'));
        $mailer->addRecipient($toemail);
        $mailer->setSubject($subject);
        $mailer->setBody($body);
        $mailer->IsHTML(false);

        return $mailer->Send();
    }

}