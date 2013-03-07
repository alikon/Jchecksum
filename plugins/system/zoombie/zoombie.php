<?php

/**
 * Zoombie pseudo cron plugin
 * Embedd a zoombie on Joomla! 
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
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

class plgSystemZoombie extends JPlugin {

    protected $interval = 300;

    function plgSystemZoombie(&$subject, $params) {
        parent::__construct($subject, $params);
        $this->lang = JFactory :: getLanguage();
        $this->lang->load('plg_system_zoombie');
        $this->interval = (int) ($this->params->get('interval', 5) * 60);

        // correct value if value is under the minimum
        if ($this->interval < 100) {
            $this->interval = 100;
        }
    }

    public function onAfterInitialise() {
        $app = &JFactory::getApplication();
        if ($app->isAdmin()) {
            $this->_removeFile();
            $this->_getFile();
        }
    }

    //function onAfterRoute()
    function onAfterDispatch() {
        $app = &JFactory::getApplication();
        if ($app->isAdmin()) {
            return;
        }
        //echo($this->params->get('cronmode',1));
        if ($this->params->get('cronmode', 1) != 1) {
            return;
        }

       

        $key = $this->params->get('key', "");
        if (($key == "") || (array_key_exists($key, JRequest::get('GET')))) {

            $db = JFactory::getDbo();
            // Prepare the logger.
            //
			      // Include the JLog class.
            jimport('joomla.log.log');

            // Get the date so that we can roll the logs over a time interval.
            $date = JFactory::getDate()->format('Y-m-d');

            // Add the logger.
            JLog::addLogger(
                    // Pass an array of configuration options.
                    // Note that the default logger is 'formatted_text' - logging to a file.
                    array(
                // Set the name of the log file.
                'text_file' => 'zoombie.' . $date . '.php',
                    // Set the path for log files.
                    //    'text_file_path' => __DIR__ . '/logs'
                    ), JLog::INFO
            );


            //$options['text_file'] = 'zoombie.php';
            //$options['text_file_path'] = __DIR__ . '/logs';
            //$log = JLog::addLogger($options,JLog::INFO);


            $now = &JFactory::getDate();
            $now = $now->toUnix();


            if ($last = $this->params->get('last_run')) {
                $diff = $now - $last;
            } else {
                $diff = $this->interval + 1;
            }

            if ($diff > $this->interval) {

                //JLog::add('asking for lock .');

                $fp = fopen(JPATH_SITE . "/plugins/system/zoombie/logs/lock.txt", "r+");

                if (flock($fp, LOCK_EX | LOCK_NB)) { // do an exclusive lock
                    //JLog::add('websatan last run:'.date('d.m.Y, H:i:s',$last));
                    //	$insert='INSERT INTO #__zoombie VALUES('.$db->Quote('zoombie').',NULL)';
                    //JLog::add($insert);
                    //$db->setQuery($insert);
                    //$db->query();
                    //if (!$db->query()) {
                    //	jexit($db->getErrorMsg());
                    //	return false;
                    //	}
                    //JLog::add('Looking to run now:'.date('d.m.Y, H:i:s',$now).' diff:'.$diff.' interval:'.$this->interval);
                    // do the work
                    //$app->enqueueMessage('Zoombie is running', 'error');
                    //
                    //$link_url = JURI::base() . 'plugins/system/zoombie/';
                    //JHTML::script('show_msg.js', $link_url, true);
                    $document = & JFactory::getDocument();

                    //$js = "window.addEvent('load', function(){ initialize() })";
                    //$document->addScriptDeclaration($js);
                    //	
                    $this->doSatanJobs();

                    //JLog::add('lastrun:'.$this->params->get('last_run'));
                    jimport('joomla.registry.format');

                    //	$this->params->set('last_import',$now);

                    $handler = &JRegistryFormat::getInstance('json');
                    $params = new JObject();
                    $params->set('cronmode', $this->params->get('cronmode', '1'));
                    $params->set('sendmail', $this->params->get('sendmail', '1'));
                    $params->set('key', $this->params->get('key', ''));
                    $params->set('interval', $this->params->get('interval', 5));
                    $params->set('last_run', $now);
                    $params->set('taskfile', 'Secret Key');
                    $params->set('taskremove', 'Secret Key');
                    $params = $handler->objectToString($params, array());

                    $query = 'UPDATE #__extensions' .
                            ' SET params=' . $db->Quote($params) .
                            ' WHERE element = ' . $db->Quote('zoombie') .
                            ' AND folder = ' . $db->Quote('system') .
                            ' AND enabled >= 1' .
                            ' AND type =' . $db->Quote('plugin') .
                            ' AND state >= 0';
                    $db->setQuery($query);
                    //$db->query();
                    if (!$db->query()) {
                        jexit($db->getErrorMsg());
                        return false;
                    }

                    flock($fp, LOCK_UN); // release the lock
                    /*
                      $db->setQuery('TRUNCATE TABLE #__zoombie')	;
                      if (!$db->query()) {
                      jexit($db->getErrorMsg());
                      return false;
                      }
                     */
                    //JLog::add('release the lock .');
                    fclose($fp);
                    //JLog::add('close file .');
                }

                //} else {
                //JLog::add(' last websatan run :'.date('d.m.Y, H:i:s',$last).' next run after:'.($this->interval - $diff).' seconds');
                //	JLog::add(' next run scheduled: '.date('H:i:s - d.m.Y',($this->interval+$last)));
            }
        }
    }

    function doSatanJobs() {
        $app = JFactory::getApplication();
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);

        // Get a list of the plugins from the database.
        $query->select('p.*')
                ->from('#__extensions AS p')
                ->where('p.enabled = 1')
                ->where('p.type = ' . $db->quote('plugin'))
                ->where('p.folder = ' . $db->quote('zoombie'))
                ->order('p.ordering');

        // Push the query builder object into the database connector.
        $db->setQuery($query);

        // Get all the returned rows from the query as an array of objects.
        $plugins = $db->loadObjectList();
        if (count($plugins) > 0) {
            JLog::add('Zoombie alive.');
        }

           $sendmail = $this->params->get('sendmail', false);
        // Log how many plugins were loaded from the database.
        //JLog::add(sprintf('.founded %d cronnable plugin(s).', count($plugins)));
        foreach ($plugins as $plugin) {
            //JLog::add($plugin->element);
            $params = new JRegistry;
            // loadJSON is @deprecated    12.1  Use loadString passing JSON as the format instead.
            // $params->loadString($this->item->params, 'JSON');
            // "item" should not be present.
            //(var_dump($plugin->params));
            $params->loadJSON($plugin->params);
            $now = &JFactory::getDate();
            $now = $now->toUnix();
            $interval = (int) ($params->get('interval', 5) * 60);
            $old_interval = $params->get('interval', 5);


            // correct value if value is under the minimum
            if ($interval < 300) {
                $interval = 300;
            }

            if ($last = $params->get('last_run')) {
                $diff = $now - $last;
            } else {
                $diff = $interval + 1;
            }

            if ($diff > $interval) {
                // is time to run 
                //JLog::add($plugin->element.' last run:'.date('d.m.Y, H:i:s',$last).' diff:'.$diff.' interval '.$interval);  
             

                // Trigger the alive event and let the Joomla zoombie plugins do their works.

                JPluginHelper::importPlugin('zoombie', $plugin->element);
                $dispatcher = & JDispatcher::getInstance();
                $results = $dispatcher->trigger('goAlive' . $plugin->element, array($last));

                if (is_array($results) && ($results[0] == 4)) {

                    $params->set('interval', $old_interval);
                    $params->set('last_run', $now);
                    $query = 'UPDATE #__extensions' .
                            ' SET params=' . $db->Quote($params) .
                            ' WHERE element = ' . $db->Quote($plugin->element) .
                            ' AND folder = ' . $db->Quote('zoombie') .
                            ' AND enabled >= 1' .
                            ' AND type =' . $db->Quote('plugin') .
                            ' AND state >= 0';
                    $db->setQuery($query);

                    if (!$db->query()) {
                        jexit($db->getErrorMsg());
                        return false;
                    }
                } else {
                    JLog::add('Zoombie ' . $plugin->element . ' no ACL to run');
                }
            }
            $plugin = null;
        }

        if (count($plugins) > 0) {
            JLog::add('Zoombie dead.');
        }
        if (count($plugins) == 0) {
            JLog::add('Zoombie live and dead.');
        }
        if ($sendmail) {
            $this->sendNotice('Zoombie Task Engine');
        }
    }

//
    protected function _removeFile() {
        // jexit(	var_dump('btype:'.JRequest::getVar('backuptype', null, 'request', 'string')));	
        $secret = trim($this->params->get('taskremove'));
        if (empty($secret)) {
            //jexit(	var_dump('secer'.$secret));		
            return true;
        }
        //	jexit(	var_dump('secer'.$secret));		
        $token = $this->getToken('filebackupremove', 'taskremove', $this->params);

        if (JRequest::getVar($token, null, 'request', 'int')) {
            $file = JRequest::getVar('file', null, 'request', 'string');
            $file = JPath::clean($file);
            $file = str_replace(array('..' . DS, '.' . DS, '..'), '', $file);
            //jexit(	var_dump('secer'.$file));		
            if (!empty($file) && in_array(JFile::getExt($file), array('gz', 'zip'))) {
                $files = $this->getBackupFiles();
                if (!is_array($files) || count($files) < 1) {
                    return true;
                }

                JRequest::checkToken('request') or jexit('Invalid Token');


                $zoombie = JRequest::getVar('backuptype', null, 'request', 'string');

                $prefix = JPATH_SITE . DS . 'plugins' . DS . 'zoombie' . DS . $zoombie . DS . 'backup' . DS;
                if (in_array($file, $files)) {
                    if (!JFile::delete($prefix . $file)) {
                        $this->_redirect(JText::_('CAN_NOT_DELETE_THE_REQUESTED_FILE'));
                    } else {
                        $cid = JRequest::getVar('extension_id', 0, 'request', 'int');
                        $msg = JText::sprintf('SUCCESS_REMOVE_FILE_SPRINTF', $file);
                        if ($cid) {
                            $redirect = JURI::base() . 'index.php?option=com_plugins&view=plugin&layout=edit&extension_id=' . $cid;
                            $this->_redirect($msg, $redirect, 'message');
                        } else {
                            $this->_redirect($msg, 'index.php', 'message');
                        }
                    }
                } else {
                    $this->_redirect(JText::_('FILE_NOT_FOUND') . $prefix . $file);
                }
            }
        }

        return true;
    }

    protected function _redirect($msg, $redirect = 'index.php', $type = 'error') {
        $mainframe = & JFactory::getApplication();
        $mainframe->enqueueMessage($msg, $type);
        $mainframe->redirect($redirect);
        exit;
    }

    public static function getToken($prefix, $name, $params = null) {
        if (is_null($params)) {
            $plugin = JPluginHelper::getPlugin('zoombie', 'filebackup');
            $params = new JRegistry($plugin->params);
        }

        $token = trim($params->get($name));
        $config = & JFactory::getConfig();
        $email = $config->getValue('config.mailfrom');
        $db = $config->getValue('config.db');
        // jexit(	var_dump('secer1'.$token));		
        return JFilterOutput::stringURLSafe(md5($prefix . $email . $db . $token));
    }

    public static function getBackupFiles() {
        jimport('joomla.filesystem.folder');

        $zoombie = JRequest::getVar('backuptype', null, 'request', 'string');
        //jexit('qui:'.$zoombie);
        $path = JPATH_SITE . DS . 'plugins' . DS . 'zoombie' . DS . $zoombie . DS . 'backup';
        $files = JFolder::files($path, '\.tar|\.bz2|\.gz|\.zip');

        if (!is_array($files) || count($files) < 1) {
            return false;
        }

        $temp = array();
        foreach ($files as $file) {
            $temp[$file] = intval(@filemtime($path . DS . $file));
        }

        arsort($temp);
        return array_flip($temp);
    }

    protected function _getFile() {
        $task = trim($this->params->get('taskfile'));
        if (empty($task)) {
            return true;
        }

        $token = $this->getToken('filebackup', 'taskfile', $this->params);

        if (JRequest::getVar($token, null, 'request', 'int')) {
            $file = JRequest::getVar('file', null, 'request', 'string');

            if (empty($file)) {
                $file = $this->getLatestFile();
                if ($file) {
                    $this->_outputFile($file);
                }
            } else {
                $files = $this->getBackupFiles();
                if (in_array($file, $files)) {
                    $this->_outputFile($file);
                }
            }

            $this->_redirect(JText::_('FILE_NOT_FOUND'));
        }

        return true;
    }

    protected function _outputFile($name) {
        $name = str_replace(array('..' . DS, '.' . DS, '..'), '', $name);
        if (!in_array(JFile::getExt($name), array('gz', 'zip'))) {
            return false;
        }
        $zoombie = JRequest::getVar('backuptype', null, 'request', 'string');
        //$zoombie = 'filebackup';
        $prefix = JPATH_SITE . DS . 'plugins' . DS . 'zoombie' . DS . $zoombie . DS . 'backup' . DS;
        $filename = $prefix . $name;
        if (!file_exists($filename)) {
            return false;
        }

        if (!headers_sent()) {
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $name . '"');
            header('Content-Length: ' . filesize($filename));
            readfile($filename);
        }
        exit;
    }

    public static function getLatestFile() {
        $files = $this->getBackupFiles();
        if (!is_array($files) || count($files) < 1) {
            return false;
        }

        return array_shift($files);
    }

    protected function sendNotice($zoombie) {
        $config = & JFactory::getConfig();
        $now = &JFactory::getDate();
        $now = $now->toUnix();
        $fromemail = $config->getValue('config.mailfrom');
        $sendfile = $this->params->get('sendfile', false);
        $toemail = $fromemail;
        $subject = $zoombie . ' : ' . $config->getValue('config.sitename');
        $body = "\n\n * " . $zoombie . " runned at " . date('d.m.Y, H:i:s', $now) . "\n";
        $body .= "\n\n  Zoombie Application 4 Joomla by  http://www.alikonweb.it \n";
        $mailer = & JFactory::getMailer();
        $mailer->setSender(array($fromemail, 'Zoombie Task event log'));
        $mailer->addRecipient($toemail);
        $mailer->setSubject($subject);
        $mailer->setBody($body);
        $date = JFactory::getDate()->format('Y-m-d');

        $attachment = JPATH_SITE . DS . 'logs' . DS . 'zoombie.' . $date . '.php';
        if ((!empty($attachment)) && ($sendfile)) {
            if (!file_exists($attachment) || !(is_file($attachment) || is_link($attachment))) {
                JLog::add("The file " . $attachment . " does not exist, or it's not a file; no email sent");
            } else {
                JLog::add("-- Attaching File");
                $mailer->addAttachment($attachment);
            }
        }

        $mailer->IsHTML(false);

        return $mailer->Send();
    }

}