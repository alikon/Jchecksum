#!/usr/bin/php 
<?php 
/** 
* An Checksum command line application built on the Joomla Platform. 
* 
* To run this example, adjust the executable path above to suite your operating system, 
* make this file executable and run the file. 
* 
* @package    Joomla.Examples 
* @copyright  Copyright (C) 2005 - 2011 Alikonweb.it All rights reserved. 
* @license    GNU General Public License version 2 or later; see LICENSE 
*/ 

// We are a valid Joomla entry point. 
define('_JEXEC', 1); 

// Setup the base path related constant. 
define('JPATH_BASE', dirname(__FILE__)); 
define('JPATH_SITE', dirname(dirname(dirname(__FILE__)))); 
// Bootstrap the application. 
require dirname(dirname(dirname(__FILE__))).'/bootstrap.php'; 

// Import the JCli class from the platform. 
jimport('joomla.application.cli'); 

/** 
* An example command line application class. 
* 
* This application shows how to access command line arguments. 
* 
* @package  Joomla.Examples 
* @since    11.3 
*/ 
class Checksum extends JCli 
{   
	  const CLI_NAME = 'JChecksum 0.1 RC [FirstStep] - www.alikonweb.it';
	  public $files = null;
	  public $new = null;
	  public $updated = null;
	  public $failed = null;
	  public $firstexec = false;
    /** 
     * Execute the application. 
     * 
     * @return  void 
     * 
     * @since   11.3 
     */ 
    public function execute() 
    {    
        $this->out(); 
        $this->out(JPlatform::getLongVersion());
        $this->out(JPlatform::COPYRIGHT); 
        $this->out(Checksum::CLI_NAME ); 
        // You can look for named command line arguments in the form of: 
        // (a) -n value 
        // (b) --name=value 
        // 
        // Try running file like this: 
        // $ ./run.php -fa 
        // $ ./run.php -f foo 
        // $ ./run.php --set=match 
        // 
        // The values are accessed using the $this->input->get() method. 
        // $this->input is an instance of a JInputCli object. 

        // This is an example of an option using long args (--). 
        $value = $this->input->get('mode'); 
        $this->out('Mode:'.$value); 
        $this->out(); 
        // Include the JLog class. 
        jimport('joomla.log.log'); 

        // Get the date so that we can roll the logs over a time interval. 
        $date = JFactory::getDate()->format('Y-m-d'); 
        // You can also apply defaults to the command line options. 
         
        // Add the logger. 
        JLog::addLogger( 
            // Pass an array of configuration options. 
            // Note that the default logger is 'formatted_text' - logging to a file. 
            array( 
                // Set the name of the log file. 
                'text_file' => 'checksum.'.$date.'.php', 
                // Set the path for log files. 
                'text_file_path' => JPATH_SITE.'/cli/checksum/logs' 
            ) 
        );       
        JLog::add(Checksum::CLI_NAME);
        JLog::add('================================'); 
        JLog::add('Checksum task started.'); 
        
        // Print a blank line. 
        $init=gmdate('Y-m-d H:i:s');        
        $this->out('Started:'.$init); 
        $this->out('================================'); 
        
        //run the task
        $this->_checksum($value);     
      

        // Print a blank line at the end. 
        $this->out('================================'); 
        $fint=gmdate('Y-m-d H:i:s'); 
        $this->out('Finished:'.$fint); 
        JLog::add('Checksum task finished.'); 
         JLog::add('================================'); 
    } 
   protected function _checksum($para) { 
       #directory for checking integrity 
       //$dir = "./"; 
       $dir=JPATH_SITE ; 
       #file for storing fingerprints, should be writeable in case of fingerprints update 
       $file = JPATH_SITE."/cli/checksum/fingerprints"; 
       #set this value to false if you do not want to update fingerprints 
        JLog::add('Running mode: '.$para);  
       switch ($para) {
       	 case "first":
           $can_update = true;
           $force_update = false; 
           break;
          case "check":
           $can_update = false;
           $force_update = false; 
           break;  
          case "update":
           $can_update = true;
           $force_update = true; 
           break;   
          default: 
          $this->out( "Unknow mode!!"); 
          return;
       } 	
      
       #set this to value to true if you want to update fingerprints of modified files 
       #you should do this only if you had modified files yourself 
       //$force_update = false; 
       #the output parameters 
       $output["new"] = true; 
       $output["success"] = false; 
       $output["failed"] = true; 
       $this->files=0;
       $this->failed=0;
       $this->updated=0;
      // header("Content-Type: text/plain"); 
       $hashes = unserialize(@file_get_contents($file)); 
       if (!$hashes || !is_array($hashes))         { 
        $hashes = array(); 
        $this->out( 'First Time execution'); 
        $this->firstexec=true; 
       } 
       //var_dump($hashes); 
       if (!$this->lookDir($dir,$hashes,$output,$force_update))         { 
           $this->out( "Could not open the directory ".$dir."\n\n"); 
           JLog::add(sprintf('Could not open the directory `%s` folder.', $dir),JLog::ERROR); 
       } else { 
       	 $this->out( 'Checked '.$this->files.' files ');  
         JLog::add('Checked '.$this->files.' files');  
         if ($this->new>0){ 
             $this->out( 'New     '.$this->new.' files ');  
             JLog::add('New '.$this->new.' files');  
         }
         if ($this->failed>0){ 
             $this->out( 'Failed  '.$this->failed.' files ');  
             JLog::add('Failed '.$this->failed.' files');  
         }
         if ($this->updated>0){ 
             $this->out( 'Updated '.$this->updated.' files ');  
             JLog::add('Updated '.$this->updated.' files');  
         }    
         if ($can_update){ 
          // var_dump($hashes); 
           if (file_put_contents($file, serialize($hashes))) { 
             $this->out("Signature updated");      
             JLog::add("Signature updated");          
           } else { 
             $this->out("The file cannot be opened for writing! Signature  not updated"); 
             JLog::add("The file cannot be opened for writing! Signature  not updated"); 
           } 
         } else { 
           $this->out("Signature not updated"); 
           JLog::add("Signature not updated"); 
         } 
       } 
    }     
    // 
   protected function lookDir($path,&$hashes,$output,$force_update) {     
   $date=JFactory::getDate()->format('Y-m-d'); 	     
   $handle = @opendir($path);          
   if (!$handle)                 { 
      return false;          
    } 
    
   while ($item = readdir($handle)) {                  
         if ($item!="." && $item!="..") {                          
            if (is_dir($path."/".$item))                                 { 
               $this->lookDir($path."/".$item,$hashes,$output,$force_update);                          
            } else { 
            	
            	  if (($item!="fingerprints")&&($item!='checksum.'.$date.'.php')){      
                  $this->checkFile($path."/".$item,$hashes,$output,$force_update); 
                  $this->files++;                  
                } 
            } 
        }          
   }          
   closedir($handle);    
        
   return true; 
   } 
   // 
 protected function checkFile($file,&$hashes,$output,$force_update) {          
  
  if (is_readable($file)) { 
      // JLog::add('checking file:'.$file); 
      if (!isset($hashes[$file])) {                          
         $hashes[$file] =  md5_file($file);                                
        // $this->out( 'leggo:'.$file."\t\t\n"); 
         if ($output["new"]) { 
         	//  $this->out( 'Checking File...'); 
             if(!$this->firstexec){        	
              $this->out($file); 
         //   $this->out( "Hash:".$hashes[$file]);            
              $this->out( "Status:New"); 
            }      
            JLog::add('New checking file:'.$file, JLog::WARNING); 
            $this->new++;
         } 
           
      } else { 
       
        if ($hashes[$file] == md5_file($file)) {                          
            if ($output["success"]) {                  
                 $this->out( 'File:'.$file);      
        //        $this->out( "Hash:".$hashes[$file]);                  
                 $this->out( "Status:Success"); 
            } 
        } else {                          
              if ($output["failed"]) { 
                 if ($force_update) {    
                    $hashes[$file]=md5_file($file);                                                              
                    $this->out( 'File:'.$file);      
        //            $this->out( "Hash:".$hashes[$file]);                      
                    $this->out( "Status:Update forced"); 
                    JLog::add('Update forced checking file:'.$file, JLog::WARNING); 
                    $this->updated++;
                 } else { 
                    $this->out( 'File:'.$file);      
        //            $this->out( "Hash:".$hashes[$file]);                      
                    $this->out( "Status:Failed"); 
                    JLog::add('Failed checking file:'.$file, JLog::WARNING); 
                    $this->failed++;
                 } 
            }     
        } 
     } 

    } 
  } 

} 

// Instantiate the application object, passing the class name to JCli::getInstance 
// and use chaining to execute the application. 
JCli::getInstance('Checksum')->execute();