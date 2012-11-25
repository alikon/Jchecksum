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

class plgCronFileBackup extends JPlugin {

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

	function plgCronFileBackup( &$subject, $params )
	{
		parent::__construct( $subject, $params );
		jimport('joomla.filesystem.archive');
		jimport('joomla.filesystem.file');
		jimport('joomla.filesystem.folder');
		if(!defined('DS')){
			define('DS',DIRECTORY_SEPARATOR);
		}
		ini_set('display_errors', 0);
		ini_set('memory_limit', '512M');
		ini_set('max_execution_time', 480);

	}
	function doCronFileBackup($time) {
		$this->lang = JFactory :: getLanguage();
		//$this->lang->load('plg_cron_userdetector', JPATH_ADMINISTRATOR);
		$this->lang->load('plg_cron_userdetector');
		// Include the JLog class.
		jimport('joomla.log.log');
		// Get the date so that we can roll the logs over a time interval.
		$this->date = JFactory::getDate()->format('Y-m-d');
		$config =& JFactory::getApplication();
		$this->name = $config->getCfg('db');
		// Add a start message.
		JLog::add('Start job: CronFileBackup.');
		$this->_FileBackup($time);
		JLog::add('End job: CronFileBackup.');
		return 4;
		//return 8;
	}
	private function _FileBackup($lastrun) {
		$jtime = microtime(true);


		$interval= (int) $this->params->get('interval',5);
		$files= $this->params->get('file_manager_path',JPATH_ROOT);
		JLog::add('Start job:'.$interval.' t:'.$files);
		//jexit(var_dump($files));
		//$this->removezip(JPATH_ROOT.DS.'tmp', 'zip');
	  $this->createzip(JPATH_ROOT.'/'.$files,JPATH_ROOT.'/tmp/'.$this->name. '_'. $this->date . '.zip');
		JLog::add (JText::sprintf('DETECTOR_CRON_PROCESS_USERCOMPLETE', round(microtime(true) - $jtime, 3)));
	}

	function createzip($source,$destination)
	{
		//$source=JPATH_ROOT;
		if (!extension_loaded('zip') || !file_exists($source)) {
			return false;
		}

		$zip = new ZipArchive();
		if (!$zip->open($destination, ZIPARCHIVE::CREATE)) {
			return false;
		}

		$source = str_replace('\\', '/', realpath($source));

		if (is_dir($source) === true)
		{
			$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);

			foreach ($files as $file)
			{
				$file = str_replace('\\', '/', realpath($file));

				if (is_dir($file) === true)
				{
					$zip->addEmptyDir(str_replace($source . '/', '', $file . '/'));
				}
				else if (is_file($file) === true)
				{
					$zip->addFromString(str_replace($source . '/', '', $file), file_get_contents($file));
					JLog::add('CronBackup adding file:'.$file);
				}
			}
		}
		else if (is_file($source) === true)
		{
			$zip->addFromString(basename($source), file_get_contents($source));
			JLog::add('CronBackup write file:'.$source);
		}
		JLog::add('CronBackup write zip file:'.$destination);
		return $zip->close();
		// Original: http://stackoverflow.com/questions/1334613/how-to-recursively-zip-a-directory-in-php
	}

	//
	function removezip($file, $ext){
		$handle = opendir($file);

		/* This is the correct way to loop over the directory. */
		while (false !== ($file2 = readdir($handle))) {
			$ext2 =  JFile::getExt($file2);

			if($ext2 == $ext && $file2 != '.' && $file2 != '..') {
				unlink($file.DS.$file2);
			}
		}
		closedir($handle);
	}
	//
}