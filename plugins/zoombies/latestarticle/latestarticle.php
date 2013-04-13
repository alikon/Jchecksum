<?php

/**
 * Zoombie LatestArticle task plugin
 * Report Latest Article Joomla! 
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
 * */
// no direct access
defined('_JEXEC') or die('Restricted access');

class plgZoombieLatestArticle extends JPlugin {

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
    var $sup = null;
    var $task_i_time = null;

    function plgZoombieLatestArticle(&$subject, $params) {
        $this->task_i_time = microtime(true);
        parent::__construct($subject, $params);

        if (!defined('DS')) {
            define('DS', DIRECTORY_SEPARATOR);
        }
        ini_set('display_errors', 0);
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', 480);
    }

    function goAliveLatestArticle($time) {
        $this->runned = (int) $this->params->get('runned', 0);
        $this->runned++;
        $lang = JFactory::getLanguage();
        $lang->load('plg_zoombie_latestarticle', JPATH_ADMINISTRATOR);
        // Include the JLog class.
        jimport('joomla.log.log');
        // Get the date so that we can roll the logs over a time interval.
        $this->date = JFactory::getDate()->format('Y-m-d');
        $config = JFactory::getApplication();
        $this->name = 'sitename';  //$config->getCfg('db');        
        // Add a start message.

        JLog::add('Start task #' . $this->runned . ' ZoombieLatestArticle');
        $this->dbo = JFactory::getDBO();

        $this->_latestarticle();

        $task_time = round(microtime(true) - $this->task_i_time, 3);
        JLog::add('End task: ZoombieLatestArticle. in ' . $task_time);
        return 4;
        //return 8;
    }

    function _latestarticle() {
        jimport('joomla.environment.uri');
        require_once JPATH_SITE . '/components/com_content/helpers/route.php';
        jimport('joomla.environment.uri');
        JModelLegacy::addIncludePath(JPATH_SITE . '/components/com_content/models', 'ContentModel');
        // Get the dbo
        $db = JFactory::getDbo();

        // Get an instance of the generic articles model
        $model = JModelLegacy::getInstance('Articles', 'ContentModel', array('ignore_request' => true));

        /* Set application parameters in model
         * 
         */
        $app = JFactory::getApplication();
        $appParams = $app->getParams();


        $model->setState('params', $appParams);

        // Set the filters based on the module params
        $model->setState('list.start', 0);
        $model->setState('list.limit', (int) $this->params->get('count', 5));
        $model->setState('filter.published', 1);
        // User filter
        $userId = $this->getSuper();
        //$
        // Access filter
        $access = !JComponentHelper::getParams('com_content')->get('show_noauth');

        $authorised = JAccess::getAuthorisedViewLevels($userId);
        $model->setState('filter.access', $access);

        // Category filter
        $model->setState('filter.category_id', $this->params->get('catid', array()));



        switch ($this->params->get('user_id')) {
            case 'by_me':
                $model->setState('filter.author_id', (int) $userId);
                break;
            case 'not_me':
                $model->setState('filter.author_id', $userId);
                $model->setState('filter.author_id.include', false);
                break;

            case '0':
                break;

            default:
                $model->setState('filter.author_id', (int) $this->params->get('user_id'));
                break;
        }

        // Filter by language
        $model->setState('filter.language', $app->getLanguageFilter());

        //  Featured switch
        switch ($this->params->get('show_featured')) {
            case '1':
                $model->setState('filter.featured', 'only');
                break;
            case '0':
                $model->setState('filter.featured', 'hide');
                break;
            default:
                $model->setState('filter.featured', 'show');
                break;
        }

        // Set ordering
        $order_map = array(
            'm_dsc' => 'a.modified DESC, a.created',
            'mc_dsc' => 'CASE WHEN (a.modified = ' . $db->quote($db->getNullDate()) . ') THEN a.created ELSE a.modified END',
            'c_dsc' => 'a.created',
            'p_dsc' => 'a.publish_up',
        );
        $ordering = JArrayHelper::getValue($order_map, $this->params->get('ordering'), 'a.publish_up');
        $dir = 'DESC';

        $model->setState('list.ordering', $ordering);
        $model->setState('list.direction', $dir);

        $items = $model->getItems();
        //$date = JFactory::getDate()->format('Y-m-d');
        $config = JFactory::getConfig();
        $now = JFactory::getDate();
        $now = $now->toUnix();
        $fromemail = $config->getValue('config.mailfrom');
        //$sendfile = $this->params->get('sendfile', false);
        $toemail = $fromemail;
        $subject = 'Zoombie Task LatestArticle : ' . $config->getValue('config.sitename');
        $body = "\n\n * Zoombie LatestArticle runned at " . date('d.m.Y, H:i:s', $now) . "\n";
        //var_dump( JURI::base( ));
        foreach ($items as &$item) {
            $item->slug = $item->id . ':' . $item->alias;
            $item->catslug = $item->catid . ':' . $item->category_alias;

            if ($access || in_array($item->access, $authorised)) {
                // We know that user has the privilege to view the article
                $item->link = JRoute::_(JURI::base() . ContentHelperRoute::getArticleRoute($item->slug, $item->catslug));
            } else {
                $item->link = JRoute::_('index.php?option=com_users&view=login');
            }
            $body .= "\n\nTitle: " . $item->title;
            $body .= "\n\nAuthor: " . $item->modified_by_name . " " . $item->modified;
            $body .= "\n\nlink: " . $item->link;
        }



        $body .= "\n\n Zoombie Application 4 Joomla  by  http://www.alikonweb.it \n";
        $mailer = JFactory::getMailer();
        $mailer->setSender(array($fromemail, 'Zoombie Task LatestArticle'));
        $mailer->addRecipient($toemail);
        $mailer->setSubject($subject);
        $mailer->setBody($body);
        $mailer->IsHTML(false);

        return $mailer->Send();
    }

    function getSuper() {

        // Get the quey builder class from the database.
        $query = $this->dbo->getQuery(true);

        // Get a list of the superadmin from the database.
        $query->select('u.id')
                ->from('#__user_usergroup_map AS m, #__usergroups as  g, #__users as u')
                ->where('g.title = ' . $this->dbo->quote('Super Users'))
                ->where('g.id = m.group_id')
                ->where('u.id = m.user_id');


        // Push the query builder object into the database connector.
        $this->dbo->setQuery($query);
        $this->sup = $this->dbo->loadObjectList();
        //echo(var_dump($this->sup[0]->id));
        return $this->sup[0]->id;
    }

}