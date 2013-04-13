<?php

/**
 * Zoombie Extension LinkChecker plugin
 * Embedd Extension link checker on Joomla! 
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
require_once JPATH_SITE . '/components/com_content/helpers/route.php';
jimport('joomla.environment.uri');
JModelLegacy::addIncludePath(JPATH_SITE . '/components/com_content/models', 'ContentModel');

class plgZoombieLinkChecker extends JPlugin {

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
    
    function plgZoombieLinkChecker(&$subject, $params) {
        parent::__construct($subject, $params);


        ini_set('display_errors', 0);
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', 480);
    }

    function goAliveLinkChecker($time) {
        $this->task_i_time = microtime(true);
        $lang = JFactory::getLanguage();
        $lang->load('plg_zoombie_latestarticle', JPATH_ADMINISTRATOR);
        // Include the JLog class.
        jimport('joomla.log.log');
        // Get the date so that we can roll the logs over a time interval.
        $this->date = JFactory::getDate()->format('Y-m-d');
        $config = JFactory::getApplication();
        $this->name = 'sitename';  //$config->getCfg('db'); 
        $runned = (int) $this->params->get('runned', 0);
        $runned++;
        // Add a start message.
        JLog::add('Start task #' . $runned . ' ZoombieLinkChecker.');
        $this->dbo = JFactory::getDBO();

        $this->_LinkChecker();
        $task_time = round(microtime(true) - $this->task_i_time, 3);
        JLog::add('End task: ZoombieLinkChecker in ' . $task_time);
        return 4;
        //return 8;
    }

    private function _LinkChecker() {
           jimport('joomla.environment.uri');
        $sendmail = $this->params->get('sendmail', false);


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
            'mc_dsc' => 'CASE WHEN (a.modified = ' . $this->dbo->quote($this->dbo->getNullDate()) . ') THEN a.created ELSE a.modified END',
            'c_dsc' => 'a.created',
            'p_dsc' => 'a.publish_up',
        );
        $ordering = JArrayHelper::getValue($order_map, $this->params->get('ordering'), 'a.publish_up');
        $dir = 'DESC';

        $model->setState('list.ordering', $ordering);
        $model->setState('list.direction', $dir);

        $items = $model->getItems();
        //  var_dump($items);
        foreach ($items as &$item) {

            $status = $this->articleLinkCheck($item->introtext);
            if (count($status) > 0) {
                $item->status = $status;
                $c = count($item->status);
                JLOG::add('checking:' . $item->title . ' in ' . $item->category_title);
                for ($i = 0; $i < $c; $i++) {
                    JLOG::add('Http status:' . $item->status[$i]);
                }
            } else {
                $item->status = array('No link to check');
            }
            // JLog::add('job: ZoombieLinkChecker.'.$item->introtext);
            //       JLog::add('job: ZoombieLinkChecker.' . var_dump($item->status));
        }
        if ($sendmail) {
            $this->sendNotice($items);
        }
    }

    private function articleLinkCheck($article) {


        $timeout = intval($this->params->get('timeout'));

        $ret = array();

        // JLOG::add($article);	
        $matches = $this->verifyLink($article);

        if (count($matches) > 0) {
            $c = count($matches[0]);

            for ($i = 0; $i < $c; $i++) {
                $url = parse_url($matches[1][$i]);

                if (!isset($url['path']))
                    $url['path'] = '/';

                if (!isset($url['host']))
                    $url['host'] = $_SERVER['HTTP_HOST'];

                if ($url['host'] == $_SERVER['HTTP_HOST'] && $url['path'][0] != '/')
                    $url['path'] = '/' . $url['path'];

                // Make Sure We Have http added to the URL
                if ((stripos($url['host'], "http://") === false) || (stripos($url['host'], "https://") === false))
                    $url['host'] = "http://" . $url['host'];

                //$status = $this->getResponse($url['host'], $url['path'], $timeout);

                $status = $this->check_url($url['host'] . $url['path']);

                if ($status != '200') {
                    //JLOG::add($status . ':' . $url['host'] . $url['path']);
                    $ret[] = $status . ' on link ' . $url['host'] . $url['path'];
                }
            }
        }
        //var_dump($ret);
        return $ret;
    }

    private function getResponse($url, $page, $timeout = 1000) {

        $str = '';

        $fp = @fsockopen($url, 80, $errno, $errstr, $timeout);

        if ($fp) {
            $crlf = "\r\n";
            $out = "GET " . $page . " HTTP/1.1\r\n";
            $out .= "Host: " . $url . "\r\n";
            $out .= 'User-Agent: Mozilla/5.0 Firefox/3.6.12' . $crlf;
            $out .= 'Referer: http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . $crlf;
            $out .= "Connection: Close\r\n\r\n";

            fwrite($fp, $out);

            $str .= fgets($fp, 4096);

            fclose($fp);
        } else {
            return 'Aundefined';
        }

        $what = array('HTTP/1.0 ', 'HTTP/1.1 ');
        $to = array('', '');

        $status = str_replace($what, $to, $str);

        $status_code = explode(' ', $status);

        if (count($status_code) > 1) {
            return $status_code[0];
        }

        return 'undefined';
    }

    private function verifyLink($text) {
        // Original PHP code by Chirp Internet: www.chirp.com.au
        // Please acknowledge use of this code by including this header.
        $matches = array();

        $regexp = "<a[^>]*href=\"([^\"]*)\"[^>]*>(.*)<\/a>";
        if (preg_match_all("/$regexp/siU", $text, $matches)) {
            return $matches;
        }

        return array();
    }

    function check_url($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $data = curl_exec($ch);
        $headers = curl_getinfo($ch);
        curl_close($ch);
        return $headers['http_code'];
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

    protected function sendNotice($items) {
        $config = JFactory::getConfig();
        $now = JFactory::getDate();
        $now = $now->toUnix();
        $fromemail = $config->getValue('config.mailfrom');
        $sendfile = $this->params->get('sendfile', false);
        $runned = (int) $this->params->get('runned', 0);
        $interval = (int) ($this->params->get('interval', 5) * 60);
        $next = $interval + $now;
        $toemail = $fromemail;
        $task_time = round(microtime(true) - $this->task_i_time, 3);
        $subject = 'LinkChecker Zoombie task : ' . $config->getValue('config.sitename');
        $body = "\n Task    #:" . ++$runned . "\n"
                . "Task name: Zoombie LinkChecker" . "\n"
                . "Runned   : " . date('d.m.Y, H:i:s', $now) . "\n"
                . "Times    : " . $task_time . "\n"
                . "Scheduled: " . date('d.m.Y, H:i:s', $next) . "\n";

        $access = !JComponentHelper::getParams('com_content')->get('show_noauth');
        //var_dump( JURI::base( ));
        foreach ($items as &$item) {
          //  var_dump($item->status[0]);
            if ($item->status[0] != 'No link to check') {
//var_dump($item->status[0]);

                $item->slug = $item->id . ':' . $item->alias;
                $item->catslug = $item->catid . ':' . $item->category_alias;

                if ($access || in_array($item->access, $authorised)) {
                    // We know that user has the privilege to view the article
                    $item->link = JRoute::_(JURI::base() . ContentHelperRoute::getArticleRoute($item->slug, $item->catslug));
                } else {
                    $item->link = JRoute::_('index.php?option=com_users&view=login');
                }
                JLOG::add($item->link);
                $body .= "\n\nTitle : " . $item->title  ;
                $body .= "\nCategory: " . $item->category_title;
                $body .= "\nAuthor  : " . $item->modified_by_name . " " . $item->modified;
                $body .= "\nLink    : " . $item->link;

                $c = count($item->status);
                for ($i = 0; $i < $c; $i++) {
                    $body .= "\n\nstatus: " . $item->status[$i];
                }
            }
        }



        $body .= "\n\n Zoombie Application 4 Joomla  by  http://www.alikonweb.it \n";
        $mailer = JFactory::getMailer();
        $mailer->setSender(array($fromemail, 'Zoombie Task linkChecker'));
        $mailer->addRecipient($toemail);
        $mailer->setSubject($subject);
        $mailer->setBody($body);
        $mailer->IsHTML(false);
        //  var_dump($body);
        return $mailer->Send();
    }

}

