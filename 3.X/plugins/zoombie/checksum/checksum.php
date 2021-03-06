<?php

/**
 * zoombieChecksum cron plugin
 * Embedd a spam report on Joomla! Kunena component
 * 
 * @author: Alikon
 * @version: 1.0.0
 * @release: 22/10/2012 21.50
 * @package: Alikonweb.detector 4 Kunena
 * @copyright: (C) 2007-2012 Alikonweb.it
 * @license: http://www.gnu.org/copyleft/gpl.html GNU/GPL
 *
 *
 *
 * */
// no direct access
defined('_JEXEC') or die('Restricted access');

class plgZoombieChecksum extends JPlugin {
	private $_time = null;

	/**
	 * Start time for each batch
	 *
	 * @var    string
	 * @since  2.5
	 */
	  private $_qtime = null;
	  public $files = null;
	  public $new = null;
	  public $updated = null;
	  public $failed = null;
	  public $firstexec = false;
	  public $lastid = null;
	  public $lang = null;
	  public $dbo = null;

    /**
     * Constructor function
     *
     * @param object $subject
     * @param object $config
     * @return plgZoombieDetector4kunena
     */
	 function plgZoombieChecksum( &$subject, $params )
	{
		parent::__construct( $subject, $params );

	
	}

	 



    function goAlivechecksum($time) {
    	
        
       
    	  /*
    	 	$user = JFactory::getUser();
    	 //	var_dump($user->get('guest'));
	    	if (!$user->authorise('core.manage', 'com_user')) {
	    		  JLog::add('Stopped job: CronChecksum for lack of grant.'.$user->get('guest'));
			      return ;
	    	}
	    	*/
    	  $this->lang = JFactory :: getLanguage();
        //$this->lang->load('plg_cron_userdetector', JPATH_ADMINISTRATOR);
        $this->lang->load('plg_zoombie_checksum');
     //   jexit($this->params->get('mode','2'));
        // Include the JLog class.
        jimport('joomla.log.log');
        // Get the date so that we can roll the logs over a time interval.
        $date = JFactory::getDate()->format('Y-m-d');
            // Add a start message.
        
            JLog::add('Start job: zoombieChecksum.');
          
            
             $this->dbo = JFactory::getDBO();
            $this->_checksum($time);            
           //  $this->ins_db_run(); 
            JLog::add('End job: ZoombieChecksum.');
            return 4;

    }
    //  
	private function _checksum($lastrun) {
    	 $jtime = microtime(true);
    	 		$files= $this->params->get('file_manager_path',JPATH_ROOT);
          //$dir=JPATH_SITE; 
          //$dir=$this->params->get('file_manager_path',JPATH_ROOT); 
          
          
       $file_check_path = $this->params->get('file_manager_path',JPATH_ROOT);	
	     if ( ($file_check_path == "JPATH_ROOT") || ($file_check_path == JPATH_ROOT) ) {
		     $file_check_path = JPATH_ROOT;
	     } else {
		     $file_check_path = JPATH_ROOT .DS . $file_check_path;
	     }
       $dir=$file_check_path;    	
		  $para=$this->params->get('mode','2');
       #file for storing fingerprints, should be writeable in case of fingerprints update 
       $file = JPATH_SITE."/plugins/zoombie/checksum/fingerprints"; 
       #set this value to false if you do not want to update fingerprints 
       
       switch ($this->params->get('mode','2')) {
       	 //case "first":
       	   case "1":
           $can_update = true;
           $force_update = false; 
            $para="first";
           break;
          //case "check":
         case "2": 
           $can_update = false;
           $force_update = false; 
           $para= "check";
           break;  
         // case "update":
         case "3": 
           $can_update = true;
           $force_update = true; 
           $para= "update";
           break;   
          default: 
          JLog::add( "Unknow mode!!"); 
          return;
       } 	
        JLog::add('Running mode: '.$para);  
      
       #set this to value to true if you want to update fingerprints of modified files 
       #you should do this only if you had modified files yourself 
       //$force_update = false; 
       #the output parameters 
       $output["new"] = true; 
       $output["success"] = false; 
       $output["failed"] = true; 
       $this->new=0;
       
       $this->files=0;

       $this->failed=0;

       $this->updated=0;
       $hashes = unserialize(@file_get_contents($file)); 
       if (!$hashes || !is_array($hashes))         { 
        $hashes = array(); 
        JLog::add( 'First Time execution'); 
        $this->firstexec=true; 
       } 
	     set_time_limit(0);  
        //var_dump($hashes); 
        if (!$this->lookDir($dir,$hashes,$output,$force_update))         { 
       //if (true)   {
           JLog::add(sprintf('Could not open the directory `%s` folder.', $dir),JLog::ERROR); 
       } else { 
       	 
         JLog::add('Checked '.$this->files.' files');  
         if ($this->new>0){ 
             
             JLog::add('New '.$this->new.' files');  
         }
         if ($this->failed>0){ 
             
             JLog::add('Failed '.$this->failed.' files');  
         }
         if ($this->updated>0){ 
             
             JLog::add('Updated '.$this->updated.' files');  
         }    
         if ($can_update){ 
          // var_dump($hashes); 
           if (file_put_contents($file, serialize($hashes))) { 
          
             JLog::add("Signature updated");          
           } else { 
          
             JLog::add("The file cannot be opened for writing! Signature  not updated"); 
           } 
         } else { 
          
           JLog::add("Signature not updated"); 
         } 
       } 
	   JLog::add (JText::sprintf('ZOOMBIE_PROCESS_CHECKSUM_COMPLETE', round(microtime(true) - $jtime, 3)));
    }  
    //     
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
            	  //exclude some files
				  //   JLog::add('item:'.$path."/".$item);
            	  if (($item!="fingerprints")&&($item!='jchecksum.php')){      
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
              JLog::add($file); 
         //   $this->out( "Hash:".$hashes[$file]);            
              JLog::add( "Status:New"); 
			//  $this->ins_db_log($file,'new',$hashes[$file]);
            }      
            JLog::add('New checking file:'.$file, JLog::WARNING); 
            $this->new++;
         } 
           
      } else { 
       
        if ($hashes[$file] == md5_file($file)) {                          
            if ($output["success"]) {                  
                 JLog::add( 'File:'.$file);      
        //        $this->out( "Hash:".$hashes[$file]);                  
                 JLog::add( "Status:Success"); 
            } 
        } else {                          
              if ($output["failed"]) { 
                 if ($force_update) {    
                    $hashes[$file]=md5_file($file);                                                              
                    JLog::add( 'File:'.$file);      
        //            $this->out( "Hash:".$hashes[$file]);                      
                    JLog::add( "Status:Update forced"); 
                    JLog::add('Update forced checking file:'.$file, JLog::WARNING); 
					// $this->ins_db_log($file,'updated',$hashes[$file]);
                    $this->updated++;
                 } else { 
                    JLog::add( 'File:'.$file);      
        //            $this->out( "Hash:".$hashes[$file]);                      
                    JLog::add( "Status:Failed"); 
                    JLog::add('Failed checking file:'.$file, JLog::ERROR); 
				//	 $this->ins_db_log($file,'failed',$hashes[$file]);
                    $this->failed++;
                 } 
            }     
        } 
     } 

    } 
  } 
  
  function ins_db_run(){
  
	$query = $this->dbo->getQuery(true);
  $query = 
	    'INSERT INTO #__checksum_run VALUES ( NULL, CURRENT_TIMESTAMP, '.$this->files.', '.$this->new.', '.$this->updated.', '.$this->failed.')';				 
	$this->dbo->setQuery($query);
	$this->dbo->query();
	$lastid=$this->dbo->insertid();
	//$this->out( "Sql:".$query); 
 // Check for a database error.
		if ($this->dbo->getErrorNum())
		{
			// Throw database error exception.
			throw new Exception($this->dbo->getErrorMsg(), 500);
		}
    return $lastid;
 }
 function upd_db_run(){
  
  $query = $this->dbo->getQuery(true);  
  $query = 
	    'UPDATE #__checksum_run SET checked = '.$this->files.', new = '.$this->new.', updated = '.$this->updated.', failed = '.$this->failed.
        ' WHERE id = '.$this->lastid;  						 
	$this->dbo->setQuery($query);
	$this->dbo->query();
	// Check for a database error.
	if ($this->dbo->getErrorNum())
	{
		// Throw database error exception.
		throw new Exception($this->dbo->getErrorMsg(), 500);
	}
	
}
function ins_db_log($nomefile,$status,$hash){
  
  $query = $this->dbo->getQuery(true);  
  $query = 
	    'INSERT INTO #__checksum_log VALUES ( '.$this->lastid.', '.$this->dbo->Quote($nomefile).','.$this->dbo->Quote($status).', '.$this->dbo->Quote($hash).' )';				
	$this->dbo->setQuery($query);
	$this->dbo->query();
	// Check for a database error.
	if ($this->dbo->getErrorNum())
	{
		// Throw database error exception.
		throw new Exception($this->dbo->getErrorMsg(), 500);
	}
	
 }

 
}