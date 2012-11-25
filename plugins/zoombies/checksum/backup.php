<?php

/**
 * CronDetector cron plugin
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

class plgCronBackup extends JPlugin {
	private $_time = null;

	/**
	 * Start time for each batch
	 *
	 * @var    string
	 * @since  2.5
	 */
	  private $_qtime = null;
	  public $sname = null;
	  public $dbname = null;
	  public $pass = null;
	  public $user = null;
	  public $lhost = null;
	  public $lang = null;
	  public $dbo = null;

    /**
     * Constructor function
     *
     * @param object $subject
     * @param object $config
     * @return plgCronDetector4kunena
     */
	 function plgCronBackup( &$subject, $params )
	{
		     $config =& JFactory::getApplication(); 
         $this->host = $config->getCfg('host');
         $this->user = $config->getCfg('user');
         $this->pass = $config->getCfg('password');
         $this->dbname = $config->getCfg('db');
         $this->sname = $config->getCfg('db');
	     	parent::__construct( $subject, $params );

	
	}

    function doCronbackup($time) {
    	  $this->lang = JFactory :: getLanguage();
        //$this->lang->load('plg_cron_userdetector', JPATH_ADMINISTRATOR);
        $this->lang->load('plg_cron_backup');
     //   jexit($this->params->get('mode','2'));
        // Include the JLog class.
        jimport('joomla.log.log');
        // Get the date so that we can roll the logs over a time interval.
        $date = JFactory::getDate()->format('Y-m-d');
            // Add a start message.
             $jtime = microtime(true);
            JLog::add('Start job: CronBackup.');
            $this->dbo = JFactory::getDBO();
            $this->_backup();
            JLog::add (JText::sprintf('CHECKSUM_CRON_PROCESS', round(microtime(true) - $jtime, 3)));
            JLog::add('End job: CronBackup.');

    }
   //
   	// Connnect
	 private function _backup() {
	 	if(!defined('DS')){
	    define('DS',DIRECTORY_SEPARATOR);
    }
	 	// Change php.ini directives
    ini_set('display_errors', 0);
    ini_set('memory_limit', '512M');
    ini_set('max_execution_time', 480); // Pode ser '120'
    $portal2 = '../tmp/'.$name. '_'. $date . '.zip';
	 	this->remove_ext(JPATH_ROOT.DS.'tmp', 'sql');
    this->remove_ext(JPATH_ROOT.DS.'tmp', 'zip');
	 	$newImport = new backup($this->host,$this->dbname,$this->user,$this->pass,'*');
	 	$message=$newImport->backup();
    $sql = $newImport->file;
	 	$this->Zip("..".DS, $portal2);

 	 }
   // 
   function remove_ext($file, $ext){
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
	//Backup
	class backup{
		
    // Constructor
 	function __construct($dbhost,$database,$dbUser ,$dbPass ,$tables="*" ) {

		$config =& JFactory::getApplication(); 
		$name = $config->getCfg('db');

 	   //let me collact all data before we start	
		$date = date('d-m-Y_H-i');
		$this->host = $dbhost;
 		$this->database = $database;
 		$this->user = $dbUser;
 		$this->pass = $dbPass ;
 		$this->file = '../tmp/'.$name . '_'. $date . '.sql';
 		$this->tables =$tables;
	    $this->msg='';
 	}
			
	// Connnect
	private function Connect() {
 		 mysql_connect($this->host, $this->user, $this->pass) or die(mysql_error());
		 mysql_select_db($this->database) or die(mysql_error());
		mysql_query("SET NAMES 'utf8';");
 	}
	
	//Backup
	public function backup(){

		$this->Connect();    
		//get list of the tables
		if($this->tables == '*')  {
			$this->tables = array();
			$result = mysql_query('SHOW TABLES');
			while($row = mysql_fetch_row($result)){
				$this->tables[] = $row[0];
			}
		} else  {
			$this->tables = is_array($this->tables) ? $this->tables : explode(',',$this->tables);
		}

        //processs each
		$return="";
		foreach($this->tables as $table)  {
		$result = @mysql_query('SELECT * FROM '.$table);
		$num_fields = @mysql_num_fields($result);    
		$row2 = @mysql_fetch_row(@mysql_query('SHOW CREATE TABLE '.$table));		
	    $return .= "\n\n".$row2[1].";\n\n";
	   
    
			while($row = @mysql_fetch_row($result))	{
		    $return .= 'INSERT INTO '.$table.' VALUES(';
				for($j=0; $j<$num_fields; $j++){
				  $row[$j] = addslashes($row[$j]);
				  if (isset($row[$j])) { $return.= '"'.$row[$j].'"' ; } else { $return.= '""'; }
				  if ($j<($num_fields-1)) { $return.= ','; }		 
				}
				$return .= ");\n";
			}
 
		    $return.="\n\n\n";
		}

		//Lets Write A file
		if (file_exists($this->file)) unlink($this->file);
		$handle = fopen($this->file,'w+');
		fwrite($handle,$return);
		fclose($handle);
	}// function
}//class