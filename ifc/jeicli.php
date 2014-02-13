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
if (file_exists(dirname(__DIR__) . '/defines.php')) {
    require_once dirname(__DIR__) . '/defines.php';
}

if (!defined('_JDEFINES')) {
    define('JPATH_BASE', dirname(__DIR__));
    require_once JPATH_BASE . '/includes/defines.php';
}

// Get the framework.
require_once JPATH_LIBRARIES . '/import.legacy.php';

// Bootstrap the CMS libraries.
require_once JPATH_LIBRARIES . '/cms.php';

// Import the configuration.
require_once JPATH_CONFIGURATION . '/configuration.php';
require_once dirname(__DIR__) . '/cli/clipbar.php';

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
class InstallCli extends JApplicationCli {

    var $status = null;
    var $a = null;

    /**
     * Entry point for CLI script
     *
     * @return  void
     *
     * @since   3.0
     */
    public function doExecute() {
        $date = JFactory::getDate()->format('Y-m-d');

        JLog::addLogger(
                // Pass an array of configuration options.
                // Note that the default logger is 'formatted_text' - logging to a file.
                array(
            // Set the name of the log file.
            'text_file' => 'jeicli.' . $date . '.php',
                // Set the path for log files.
                //    'text_file_path' => __DIR__ . '/logs'
                ), JLog::INFO
        );
        $args = (array) $GLOBALS['argv'];
        //var_dump($args);
        if (count($args) < 2) {
            $this->out($this->help());
            exit(1);
        }
        
        $this->out(JText::_('INSTALL_CLI'));
        $this->out('============================');
        $this->out('This script is using:' . $this->getMemoryUsage());

        //var_dump($this->input->args[0]);
        //	$this->update($this->input->args[0]);
        if ($args[1] == '-f') {
            $this->updatefromfolder($args[2]);
        }

        if ($args[1] == '-u') {
            
            $this->updatefromurl($args[2]);

           
        }
        if ($args[1] == '-m') {
            $this->updatefromfile('oo');
        }
        $this->out("\n\n".JText::_('FINISHED_INSTALL_CLI'));
    }

    protected function updatefromfolder($p_file) {
        // Download the package at the URL given
        //$p_file = 'pkg_arp_30.zip';
        $this->out('\rFile:' . $p_file);
        // Cleanup the install files

        $config = JFactory::getConfig();
        $tmp_dest = $config->get('tmp_path');

        if (!is_file($tmp_dest . '/' . $p_file)) {
            $this->out('COM_INSTALLER_MSG_INSTALL_INVALID_FOLDER');
            return false;
        }



        // Unpack the downloaded package file
        $package = JInstallerHelper::unpack($tmp_dest . '/' . $p_file, true);
        //jexit(var_dump($package));
        // Get an installer instance
        $installer = JInstaller::getInstance();
        //var_dump($installer);
        // Install the package
        if (!$installer->update($package['dir'])) {
            // There was an error installing the package
            $this->out('COM_INSTALLER_INSTALL_ERROR_TYPE_' . strtoupper($package['type']) . ' ' . $p_file);
            $result = false;
        } else {
            // Package installed sucessfully
            $this->out('\rCOM_INSTALLER_INSTALL_SUCCESS_TYPE_' . strtoupper($package['type']) . ' ' . $p_file);
            $result = true;
        }
    }

    public function getMemoryUsage() {
        $size = memory_get_usage(true);
        $unit = array('b', 'kb', 'mb', 'gb', 'tb', 'pb');
        return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $unit[$i];
    }

    protected function help() {
        // Initialize variables.
        $help = array();
        // Build the help screen information.
        $help[] = 'Install Joomla! Extensions from CLI';
        $help[] = 'Usage: php installcli.php [options]';
        $help[] = '';
        $help[] = 'Options: -f [extensionfile]';
        $help[] = 'Example usage:php installcli.php -f plg_example.zip';
        $help[] = 'Install the example plugin from /tmp/plg_example.zip';
        $help[] = '';
        $help[] = 'Options: -u [extensionurl]';
        $help[] = 'Example usage:php installcli.php -u http://www.joomladdons.eu/update/mod_related_author_update.xml';
        $help[] = 'Install the extensions plugin from www.joomladdons.eu/update/mod_related_author_update.xml';
        $help[] = '';
        $help[] = 'Options: -m';
        $help[] = 'Example usage:php installcli.php -m';
        $help[] = 'Install the extensions listed on /cli/ijefcdfl.txt';
        // Print out the help information.
        $this->out(implode("\n", $help));
    }

    protected function updatefromurl($resource) {
        //$input = JFactory::getApplication()->input;
        jimport('joomla.updater.update');
        $urls = array();
        //$urls[]=$resource;
        // $data = JFile::read(JPATH_SITE . "/cli/installcli.txt");
        //$urls=explode(";", $data);
        //var_dump($data);
        // Get the URL of the package to install

        $row = 1;
        $handle = fopen("jfiles.csv", "r");
        while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
            $num = count($data);
            //   echo "$num campi sulla linea $row:\n";
            $row++;
            $urls['name'][] = $data[0];
            $urls['url'][] = $data[1];
            for ($c = 0; $c < $num; $c++) {
                //  echo $data[$c] . "\n";
            }
            //  echo"fine riga:".$row."\n\n";
        }
        fclose($handle);
        //progress bar;
        $bartask = ((count($urls['name'])-1)*3)+2;
        $this->a = new CliProgressBar();
        $this->a->initPBar($bartask, 13);
        $this->status = 1;
        $this->a->advancePBar($this->status, 'from list');

        $filesn = count($urls['name']);
        
        for ($e = 1; $e < $filesn; $e++) {
            // Did you give us a URL?	
            if (!$urls['url'][$e]) {
                echo 'COM_INSTALLER_MSG_INSTALL_ENTER_A_URL';
                JLog::add($urls['url'][$e].' is not valid url.');
                return false;
            }
           
            // Handle updater XML file case:
            if (preg_match('/\.xml\s*$/', $urls['url'][$e])) {
             
                $update = new JUpdate;
                $update->loadFromXML($urls['url'][$e]);
                $package_url = trim($update->get('downloadurl', false)->_data);
               
                if ($package_url) {
                    $urls['url'][$e] = $package_url;
                }
                unset($update);
            }
            $this->status++;
            $this->a->advancePBar($this->status, 'download:' . $urls['name'][$e]);           
            JLog::add($urls['url'][$e].' downloading.');
            // Download the package at the URL given
            $p_file = JInstallerHelper::downloadPackage($urls['url'][$e]);
         //  echo "\n\n";
         //         var_dump($p_file);
            // Was the package downloaded?
            if (!$p_file) {
                JLog::add($urls['url'][$e].' is not valid download.');
                $this->a->finishPBar();
            } else {
                $config = JFactory::getConfig();
                $tmp_dest = $config->get('tmp_path');
                $this->status++;                
                $this->a->advancePBar($this->status, 'unpack:' . $urls['name'][$e]);
                JLog::add($urls['name'][$e].' unpack.');
                // Unpack the downloaded package file
                $package = JInstallerHelper::unpack($tmp_dest . '/' . $p_file, true);
                //var_dump($package);
                // Get an installer instance
                $installer = JInstaller::getInstance();
                //var_dump($installer);
                // Install the package      
                ob_start();       
                try
		            {
		            	// Process the batches.
		            	if (!@$installer->update($package['dir'])){
		            	  
                    throw new Exception('InstallKO.');
                   
                    JLog::add($urls['name'][$e].' is not valid install.');
                  } else {
                    // Package installed sucessfully                    
                    $this->status++;                    
                    $this->a->advancePBar($this->status, 'installed:' . $installer->manifest->name);
                    JLog::add($urls['name'][$e].' installed.'.$installer->manifest->name);            
                    // JInstallerHelper::cleanupInstall($package['packagefile'], $package['extractdir']);  
		            	}
		            }
		            catch (Exception $ex)
		            {
		            	ob_end_clean();
		            	// Display the error		            
		            	JLog::add($urls['name'][$e].$ex->getMessage().$installer->manifest->name);    
		              $this->a->advancePBar($this->status, ' not installed:' . $installer->manifest->name);   		            
		            }
            }
            unset($update);
            unset($installer);
        }
        $this->a->finishPBar();
        return;
    }

    protected function updatefromfile($l_file) {
        //  installfromlist
        $data = JFile::read(JPATH_SITE . "/cli/files.txt");
        $files = explode(";", $data);
        var_dump($files);
        $this->out('Installing from extension list:installcli.jel');
        // Cleanup the install files
         //progress bar;
        $bartask = ((count($files)-1)*3)+2;
        $this->a = new CliProgressBar();
        $this->a->initPBar($bartask, 13);
        $this->status = 1;
        $this->a->advancePBar($this->status, 'from list');
        $config = JFactory::getConfig();
        $tmp_dest = $config->get('tmp_path');
        foreach ($files as $p_file) {
             $file=$config->get('tmp_path') . '/' . $p_file;
            // echo "\n\n".$file;
             //exit();
            //$this->out('Extension:' . $p_file);
            $this->status++;                
            $this->a->advancePBar($this->status, 'unpack:' . $p_file);
            //JLog::add($p_file.' unpack.');
            if (!is_file($file)) {
                //$this->out('COM_INSTALLER_MSG_INSTALL_INVALID_LIST_ELEMNET');
                $this->status++;                
                $this->a->advancePBar($this->status, 'pack not found:' . $p_file);
                JLog::add($p_file.' pack not found.');
            } else {
                // Unpack the downloaded package file
                $package = JInstallerHelper::unpack($config->get('tmp_path') . '/' . $p_file, true);
                //jexit(var_dump($package));
                // Get an installer instance
                $installer = JInstaller::getInstance();
                //var_dump($installer);
                // Install the package
                if (!@$installer->update($package['dir'])) {
                    // There was an error installing the package
                   // $this->out('COM_INSTALLER_INSTALL_ERROR_TYPE_' . strtoupper($package['type']) . ' ' . $p_file);
                    $result = false;
                    $this->status++;                
                    $this->a->advancePBar($this->status, 'install failed:' . $p_file);
                   JLog::add($p_file.' install failed.');
                } else {
                	  $this->status++;                
                    $this->a->advancePBar($this->status, 'install ok' . $p_file);
                    JLog::add($p_file.' install failed.');
                    JInstallerHelper::cleanupInstall($package['packagefile'], $package['extractdir']);
                    unset($installer);
                }
            }
            unset($file);
        }
          $this->a->finishPBar();
    }


}

// Instantiate the application object, passing the class name to JCli::getInstance
// and use chaining to execute the application.
JApplicationCli::getInstance('InstallCli')->execute();