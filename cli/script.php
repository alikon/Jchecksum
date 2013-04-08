<?php
/**
 * Zoombie Cli for Joomla!
 *
 * @package    Zoombie Cli
 *
 * @copyright  Copyright (C) 2011-2013 Alikon. All rights reserved.
 * @license    GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 *
 * Zoombie is based upon the ideas found in Joomla Platform example cron plugin
 * 
 */

/**
 * Installation class to perform additional changes during install/uninstall/update
 *
 * @package  Zoombie Cli
 * @since    2.0
 */
class File_ZoombieInstallerScript
{
	/**
	 * An array of supported database types
	 *
	 * @var    array
	 * @since  2.0
	 */
	protected $dbSupport = array('mysql', 'mysqli', 'postgresql', 'sqlsrv');
  	function install($parent) 
	{ 
			
		$parent->getParent()->setRedirectURL('index.php?option=com_aa4j');
	}
	/**
	 * Function to act prior to installation process begins
	 *
	 * @param   string             $type    The action being performed
	 * @param   JInstallerPackage  $parent  The class calling this method
	 *
	 * @return  boolean  True on success
	 *
	 * @since   2.0
	 */
	public function preflight($type, $parent)
	{
		
		return true;
	}

	/**
	 * Function to act after the installation process runs
	 *
	 * @param   string             $type     The action being performed
	 * @param   JInstallerPackage  $parent   The class calling this method
	 * @param   array              $results  The results of each installer action
	 *
	 * @return	void
	 *
	 * @since	2.0
	 */
	public function postflight($type, $parent, $results)
	{
		return true;
	}
}