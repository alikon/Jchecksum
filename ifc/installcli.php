<?php
/**
 * @package    Joomla.Cli
 *
 * @copyright  Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

/**
 * Finder CLI Bootstrap
 *
 * Run the framework bootstrap with a couple of mods based on the script's needs
 */

// We are a valid entry point.
const _JEXEC = 1;

// Load system defines
if (file_exists(dirname(__DIR__) . '/defines.php'))
{
	require_once dirname(__DIR__) . '/defines.php';
}

if (!defined('_JDEFINES'))
{
	define('JPATH_BASE', dirname(__DIR__));
	require_once JPATH_BASE . '/includes/defines.php';
}

// Get the framework.
require_once JPATH_LIBRARIES . '/import.legacy.php';

// Bootstrap the CMS libraries.
require_once JPATH_LIBRARIES . '/cms.php';

// Import the configuration.
require_once JPATH_CONFIGURATION . '/configuration.php';

// System configuration.
$config = new JConfig;

// Configure error reporting to maximum for CLI output.
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load Library language
$lang = JFactory::getLanguage();

// Try the files_joomla file in the current language (without allowing the loading of the file in the default language)
$lang->load('files_joomla.sys', JPATH_SITE, null, false, false)
// Fallback to the files_joomla file in the default language
|| $lang->load('files_joomla.sys', JPATH_SITE, null, true);
jimport('joomla.filesystem.file');
jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.path');
/**
 * A command line cron job to attempt to install.
 *
 * @package  Joomla.Cli
 * @since    3.0
 */
class InstallCli extends JApplicationCli
{
	/**
	 * Entry point for CLI script
	 *
	 * @return  void
	 *
	 * @since   3.0
	 */
	public function doExecute()
	{
		$args = (array) $GLOBALS['argv'];
		//var_dump($args);
    if(count($args)<2){$this->out($this->help());exit(1);}
  	$this->out(JText::_('INSTALL_CLI'));
		$this->out('============================');
	//	$this->out('This script is using:'.$this->getMemoryUsage());
		
		//var_dump($this->input->args[0]);
		
		
		//	$this->update($this->input->args[0]);
		if($args[1]=='-f'){
		 $this->updatefromfolder($args[2]);
		}
		if($args[1]=='-u'){
		 $this->updatefromurl($args[2]);
		}
		if($args[1]=='-m'){
		 $this->updatefromfile('oo');
		}
    $this->out('============================');
    $this->out(JText::_('FINISHED_INSTALL_CLI'));
				
 }
 	protected function updatefromfolder($p_file)
	{			
		// Download the package at the URL given
		  //$p_file = 'pkg_arp_30.zip';
      $this->out('File:'.$p_file);		  
		  // Cleanup the install files

	    $config   = JFactory::getConfig();
		  $tmp_dest = $config->get('tmp_path');
		  
		  if (!is_file($tmp_dest . '/' . $p_file))
		  {
		  	$this->out( 'COM_INSTALLER_MSG_INSTALL_INVALID_FOLDER');
		  	return false;
		  }
      
	
      
		  // Unpack the downloaded package file
		  $package = JInstallerHelper::unpack($tmp_dest . '/' . $p_file, true);
      //jexit(var_dump($package));
		  // Get an installer instance
		  $installer = JInstaller::getInstance();
      //var_dump($installer);
		  // Install the package
		  if (!$installer->update($package['dir']))
		  {
		  	// There was an error installing the package
		  	$this->out('COM_INSTALLER_INSTALL_ERROR_TYPE_' . strtoupper($package['type']).' '.$p_file);
		  	$result = false;
		  }
		  else
		  {
		  	// Package installed sucessfully
		  	$this->out('COM_INSTALLER_INSTALL_SUCCESS_TYPE_' . strtoupper($package['type']).' '.$p_file);
		  	$result = true;
		  }
	}	
 public function getMemoryUsage()
        {
            $size = memory_get_usage(true);
            $unit=array('b','kb','mb','gb','tb','pb');
            return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
        }
protected function help()
	{
		// Initialize variables.
		$help = array();
		// Build the help screen information.
		$help[] = 'Install Joomla Extensions from CLI';
		$help[] = '';
		$help[] = 'Options: -f [extensionfile],-u [extensionurl],-m';
		$help[] = '';
		$help[] = 'Example usage:php installcli.php -f plg_example.zip';
		$help[] = 'Install the example plugin from /tmp/plg_example.zip';
		$help[] = '';
		$help[] = 'Example usage:php installcli.php -u http://www.joomladdons.eu/update/mod_related_author_update.xml';
		$help[] = 'Install the extensions plugin from www.joomladdons.eu/update/mod_related_author_update.xml';
		$help[] = '';
		$help[] = 'Example usage:php installcli.php -m';
		$help[] = 'Install the extensions listed on /cli/ijefcdfl.txt';
		// Print out the help information.
		$this->out(implode("\n", $help));
	}
	
		protected function updatefromurl($resource)
	{
		//$input = JFactory::getApplication()->input;
			jimport('joomla.updater.update');
	  $urls=array();
	  //$urls[]=$resource;
		// $data = JFile::read(JPATH_SITE . "/cli/installcli.txt");
		//$urls=explode(";", $data);
    //var_dump($data);
		// Get the URL of the package to install
	
		$urls[] = 'http://www.joomladdons.eu/update/mod_related_author_update.xml';
		$urls[] = 'http://www.joomladdons.eu/update/pkg_zoombie_3.0.zip';
    ///$urls[] = 'http://www.joomladdons.eu/update/pkg_arp_30.zip';
    $urls[] = 'http://www.joomladdons.eu/update/mod_responsive_gads_110.zip';
    foreach ($urls as $url)
    {
		  // Did you give us a URL?
		  if (!$url)
		  {
		  	echo 'COM_INSTALLER_MSG_INSTALL_ENTER_A_URL';
		  	return false;
		  }
      $this->out('Installing from:'.$url);
		  // Handle updater XML file case:
		  if (preg_match('/\.xml\s*$/', $url))
		  {
		  
		  	$update = new JUpdate;
		  	$update->loadFromXML($url);
		  	$package_url = trim($update->get('downloadurl', false)->_data);
		  	if ($package_url)
		  	{
		  		$url = $package_url;
		  	}
		  	unset($update);
		  }
      
		  // Download the package at the URL given
		  $p_file = JInstallerHelper::downloadPackage($url);
      $this->out('Downloaded:'.$p_file);
		  // Was the package downloaded?
		  if (!$p_file)
		  {
		  	$this->out( 'COM_INSTALLER_MSG_INSTALL_INVALID_URL');
		  	//return false;
		  }
      
		  $config   = JFactory::getConfig();
		  $tmp_dest = $config->get('tmp_path');
      
		  // Unpack the downloaded package file
		  $package = JInstallerHelper::unpack($tmp_dest . '/' . $p_file, true);
      // var_dump($package);
		  // Get an installer instance
		  $installer = JInstaller::getInstance();
      //var_dump($installer);
		  // Install the package
		  if (!$installer->update($package['dir']))
		  {
		  	// There was an error installing the package
		  	$this->out('COM_INSTALLER_INSTALL_ERROR_TYPE_' . strtoupper($package['type']).' '.$p_file);
		  	$result = false;
		  }
		  else
		  {
		  	// Package installed sucessfully
		  	$this->out('COM_INSTALLER_INSTALL_SUCCESS_TYPE_' . strtoupper($package['type']).' '.$p_file);
		  	$result = true;
		  }
		  	unset($update);
		  	unset($installer);
		}
		return;
	}
	
	protected function updatefromfile($l_file)
	{			
		  //  installfromlist
		  $data = JFile::read(JPATH_SITE . "/cli/installcli.jel");
	  	$files=explode(";", $data);
	  	//var_dump($files);
      $this->out('Installing from extension list:installcli.jel');		  
		  // Cleanup the install files

	    $config   = JFactory::getConfig();
	  	$tmp_dest = $config->get('tmp_path');
		  foreach ($files as $p_file)
		  { 
		  	 //var_dump($p_file);
		  	 $this->out('Extension:'.$p_file);
		  //	$this->out('Extension:'.$tmp_dest . '/' . $p_file);
		    if (!is_file($tmp_dest . '/' . $p_file))
		    {
		  	   $this->out( 'COM_INSTALLER_MSG_INSTALL_INVALID_LIST_ELEMNET');
		  	//   $this->out($tmp_dest . '/' . $p_file);
		   // 	return false;
		    }else {
		    	// Unpack the downloaded package file
		      $package = JInstallerHelper::unpack($tmp_dest . '/' . $p_file, true);
          //jexit(var_dump($package));
		      // Get an installer instance
		      $installer = JInstaller::getInstance();
          //var_dump($installer);
		      // Install the package
		      if (!$installer->update($package['dir']))
		      {
		      	// There was an error installing the package
		      	$this->out('COM_INSTALLER_INSTALL_ERROR_TYPE_' . strtoupper($package['type']).' '.$p_file);
		      	$result = false;
		      }
		      else
		      {
		      	// Package installed sucessfully
		      	$this->out('COM_INSTALLER_INSTALL_SUCCESS_TYPE_' . strtoupper($package['type']).' '.$p_file);
		      	$result = true;
		      	 JInstallerHelper::cleanupInstall($package['packagefile'], $package['extractdir']);
		     
		  	    unset($installer);
		      }
		    }			 
      } 			  
	}	
}
// Instantiate the application object, passing the class name to JCli::getInstance
// and use chaining to execute the application.
JApplicationCli::getInstance('InstallCli')->execute();