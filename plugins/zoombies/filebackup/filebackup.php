<?php

/**
 * Zoombie File Backup cron plugin
 * Embedd a spam report on Joomla! Kunena component
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

class plgZoombieFileBackup extends JPlugin {

    /**
     * Constructor function
     *
     * @param object $subject
     * @param object $config
     * @return plgCronFileBackup
     */
    var $cfg = null;
    var $mailfrom = null;
    var $fromname = null;
    var $app = null;
    var $dbo = null;
    var $lang = null;
    var $name = null;
    var $date = null;

    function plgZoombieFileBackup(&$subject, $params) {

        parent::__construct($subject, $params);

        jimport('joomla.filesystem.archive');
        jimport('joomla.filesystem.file');
        jimport('joomla.filesystem.folder');
        if (!defined('DS')) {
            define('DS', DIRECTORY_SEPARATOR);
        }
        ini_set('display_errors', 0);
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', 480);
    }

    function goAliveFileBackup($time) {
         $this->task_i_time = microtime(true);     
        $this->lang = JFactory :: getLanguage();
        //$this->lang->load('plg_zoombie_userdetector', JPATH_ADMINISTRATOR);
        $this->lang->load('plg_zoombie_filebackup');
        // Include the JLog class.
        jimport('joomla.log.log');
        // Get the date so that we can roll the logs over a time interval.
        $this->date = JFactory::getDate()->format('Y-m-d');
        $config = JFactory::getApplication();
        $this->name = 'filebackup.' . $config->getCfg('sitename');
        // Add a start message.
         $runned = (int) $this->params->get('runned', 0);
        $runned++;
         JLog::add('Start task# ' . $runned . ' ZoombieFileBackup.');
        $this->_FileBackup($time);
         $task_time = round(microtime(true) - $this->task_i_time, 3);
        JLog::add('End task: ZoombieFileBackup.');
        return 4;
        //return 8;
    }

    private function _FileBackup($lastrun) {
        $jtime = microtime(true);

        $sendmail = (int) $this->params->get('sendmail', '0');
        $interval = (int) $this->params->get('interval', 5);
        $files = $this->params->get('file_manager_path', '');
        if ($files == 'JPATH_ROOT') {
            $files = '';
        }

        //JLog::add('Start job:' . $interval . ' t:' . $files);
        //jexit(var_dump($files));
        //$this->removezip(JPATH_ROOT.DS.'tmp', 'zip');
        $path = JPATH_SITE . DS . 'plugins' . DS . 'zoombie' . DS . 'filebackup' . DS . 'backup' . DS;
        //$this->createzip(JPATH_ROOT . '/' . $files, JPATH_ROOT . '/tmp/' . $this->name . '_' . $this->date . '.zip');
        $this->createzip(JPATH_ROOT . '/' . $files, $path . $this->name . '_' . $this->date . '.zip');
        JLog::add(JText::sprintf('ZOOMBIE_PROCESS_FILEBACKUP_COMPLETE', round(microtime(true) - $jtime, 3)));
        if ($sendmail) {
            $this->sendFile();
        }
    }

    function createzip($source, $destination) {
        //$source=JPATH_ROOT;
        if (!extension_loaded('zip') || !file_exists($source)) {
            return false;
        }

        $zip = new ZipArchive();
        if (!$zip->open($destination, ZIPARCHIVE::CREATE)) {
            return false;
        }

        $source = str_replace('\\', '/', realpath($source));

        if (is_dir($source) === true) {
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);

            foreach ($files as $file) {
                $file = str_replace('\\', '/', realpath($file));

                if (is_dir($file) === true) {
                    $zip->addEmptyDir(str_replace($source . '/', '', $file . '/'));
                } else if (is_file($file) === true) {
                    $zip->addFromString(str_replace($source . '/', '', $file), file_get_contents($file));
                 //   JLog::add('ZoombieFileBackup adding file:' . $file);
                }
            }
        } else if (is_file($source) === true) {
            $zip->addFromString(basename($source), file_get_contents($source));
            JLog::add('ZoombieFileBackup write file:' . $source);
        }
        JLog::add('ZoombieFileBackup write zip file:' . $destination);
        return $zip->close();
        // Original: http://stackoverflow.com/questions/1334613/how-to-recursively-zip-a-directory-in-php
    }

    protected function sendFile() {
        $config = JFactory::getConfig();
        $now = JFactory::getDate();
        $now = $now->toUnix();
        $fromemail = $config->getValue('config.mailfrom');
        $sendfile = $this->params->get('sendfile', false);
        $toemail = $fromemail;
        $subject =  'FileBackup Zoombie Task : ' . $config->getValue('config.sitename');
        
        $runned = (int) $this->params->get('runned', 0);
        $interval = (int) ($this->params->get('interval', 5) * 60);
        $next = $interval + $now;
        $task_time = round(microtime(true) - $this->task_i_time, 3);
        
        $body = "\n Task    #:" . ++$runned . "\n"
                . "Task name: Zoombie FileBackup" . "\n"
                . "Runned   : " . date('d.m.Y, H:i:s', $now) . "\n"
                . "Times    : " . $task_time . "\n"
                . "Scheduled: " . date('d.m.Y, H:i:s', $next) . "\n";

        
        $body .= "\n\n Zoombie Application 4 Joomla  by  http://www.alikonweb.it \n";
        
        $mailer = JFactory::getMailer();
        $mailer->setSender(array($fromemail, 'Zoombie Task FileBackup'));
        $mailer->addRecipient($toemail);
        $mailer->setSubject($subject);
        $mailer->setBody($body);
        $date = JFactory::getDate()->format('Y-m-d');

        $attachment = JPATH_SITE . '/plugins/zoombie/filebackup/backup/filebackup.' . $config->getValue('config.sitename') . '_' . $date . '.zip';
        
        //jexit(var_dump($attachment));
        if ((!empty($attachment))&&($sendfile)) {
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