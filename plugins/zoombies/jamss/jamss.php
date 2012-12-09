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

class plgCronJamss extends JPlugin {

    /**
     * Constructor function
     *
     * @param object $subject
     * @param object $config
     * @return plgCronDetector4kunena
     */
    var $cfg = null;
    var $mailfrom = null;
    var $fromname = null;
    var $app = null;
    var $dbo = null;
    var $lang = null;
    var $user = null;
    
    var $ext = null;
    var $patterns = null;
    var $ignoreDirs  = null;
    var $fileExt  = null;
    var $directory= null;
    var $jamssStrings= null;
    var $jamssDeepSearchStrings= null;
    var $jamssPatterns=null;
    
    function plgCronJamss( &$subject, $params )
   	{
		parent::__construct( $subject, $params );
	  }
	  
    function doCronJamss($time) {
    	  $this->fileExt = 'php|php3|php4|php5|phps|txt|html|htaccess|gif'; // file extensions
    	  $this->ignoreDirs = '.|..|.DS_Store|.svn|.git'; // dirnames to ignore
    	  $this->directory = '.'; // a directory to scan; default: current dir
    	  /* * * * *  Patterns Start * * * * */
        $this->jamssStrings = 'r0nin|m0rtix|upl0ad|r57shell|c99shell|shellbot|phpshell|void\.ru|phpremoteview|directmail|bash_history|multiviews|cwings|vandal|bitchx|eggdrop|guardservices|psybnc|dalnet|undernet|vulnscan|spymeta|raslan58|Webshell|str_rot13|FilesMan|Web Shell';

       // this patterns will be used if GET parameter ?deepscan=1 is set while calling jamss.php file
       $this->jamssDeepSearchStrings = 'eval|base64_decode|base64_encode|gzdecode|gzdeflate|gzuncompress|gzcompress|readgzfile|zlib_decode|zlib_encode|gzfile|gzget|gzpassthru|iframe';

       // the patterns to search for
       $this->jamssPatterns = array(
            array('preg_replace\s*\(\s*[\"\'].*e\s*[\"\'].*\)', // [0] = RegEx search pattern
                'PHP: preg_replace Eval', // [1] = Name / Title
                '1', // [2] =  number
                'We detected preg_replace function that evaluates (executes) mathed code. This means if PHP code is passed it will be executed.', // [3] = description
                'Part example code from http://sucuri.net/malware/backdoor-phppreg_replaceeval'), // [4] = More Information link
            array('c999sh_surl',
                'Backdoor: PHP:C99:045',
                '2',
                'Detected the "C99? backdoor that allows attackers to manage (and reinfect) your site remotely. It is often used as part of a compromise to maintain access to the hacked sites.',
                'http://sucuri.net/malware/backdoor-phpc99045'),
            array('preg_match\s*\(\s*\"\s*\/\s*bot\s*\/\s*\"',
                'Backdoor: PHP:R57:01',
                '3',
                'Detected the "R57? backdoor that allows attackers to access, modify and reinfect your site. It is often hidden in the filesystem and hard to find without access to the server or logs.',
                'http://sucuri.net/malware/backdoor-phpr5701'),
            array('eval\s*\(stripslashes\s*\(\s*\$_REQUEST\s*\[\s*\\\s*[\'\"]\s*asc\s*\\\s*[\'\"]',
                'Backdoor: PHP:GENERIC:07',
                '5',
                'Detected a generic backdoor that allows attackers to upload files, delete files, access, modify and/or reinfect your site. It is often hidden in the filesystem and hard to find without access to the server or logs. It also includes uploadify scripts and similars that offer upload options without security. ',
                'http://sucuri.net/malware/backdoor-phpgeneric07'),
            array('https?\S{1,63}\.ru',
                'russian URL',
                '6',
                'Detected a .RU domain link, as there are many attacks leading the innocent visitors to .RU pages. Maybe i\'s valid link, but we leave it to you to check this out.',
            ),
            array('preg_replace\s*\(\s*[\"\'\”]\s*\/\s*\.\s*\*\s*\/\s*e\s*[\"\'\”]\s*,\s*[\"\'\”]\s*\\x65\\x76\\x61\\x6c',
                'Backdoor: PHP:Filesman:02',
                '7',
                'We detected the “Filesman” backdoor that allows attackers to access, modify and reinfect your site. It is often hidden in the filesystem and hard to find without access to the server or logs.',
                'http://sucuri.net/malware/backdoor-phpfilesman02'),
            array('(include|require)(_once)*\s*[\"\']\s*php:\/\/input\s*[\"\']',
                'PHP:\input include',
                '8',
                'Detected the method of reading input through PHP protocol handler in include/require statements.',),
            array('data:;base64',
                'data:;base64 include',
                '9',
                'Detected the method of executing base64 data in include.',),
            array('RewriteCond\s*%\{HTTP_REFERER\}',
                '.HTAC RewriteCond-Referer',
                '10',
                'Your .htaccess file has a conditional redirection based on "HTTP Referer". This means it redirects according to site/url from where your visitors came to your site. Such technique has been used for unwanted redirections after coming from Google or other search engines, so check this directive carefully.',),
            array('brute\s*force',
                '"Brute Force" words',
                '11',
                'We detected the "Brute Force" words mentioned in code. <u>Sometimes it\'s a "false positive"</u> because several developers like to mention it in they code, but it\'s worth double-checking if this file is untouche (eg. compare it with one in original extension package).',),
            array('eval\s*\(\s*(gzuncompress|gzinflate|base64_decode|str_rot13)',
                'PHP: Eval+(GZINFLATE||GZUNCOMPRESS||B64||ROT13)',
                '12',
                'We detected one of base64 code that will get evaluated, and this might be a fingerprint of a malware.',),
            array('GIF89a.*[\r\n]*.*<\?php',
                'PHP file desguised as GIF image',
                '15',
                'We detected a PHP file that was most probably uploaded as an image via webform that loosely only checks file headers.',),
            array('\$ip\s*=\s*getenv\(["\']REMOTE_ADDR["\']\);\s*[\r\n]\$message',
                'Probably malicious PHP script that "calls home"',
                '16',
                'This pattern detects script variations used for informing attackers about found vulnerable website.',),
            array('(gzuncompress|gzinflate|base64_decode|str_rot13).*(gzuncompress|gzinflate|base64_decode|str_rot13)',
                'PHP: double GZINFLATE||GZUNCOMPRESS||B64||ROT13',
                '17',
                'Detected a highly encoded (and malicious) code hidden under a loop of gzinflate/gzuncompress/base64_decode calls. After decoded, it goes through an eval call to execute the code.',
                'Thanks to Dario Pintaric (dario.pintaric[et}orion-web.hr for this report!'),
        );
        
        /* * * * *  Patterns End * * * * */
        // check if DeepScan should be done
        ;
        if ($this->params->get('deepscan',1)) {
            $this->patterns = array_merge($this->jamssPatterns, explode('|', $this->jamssStrings), explode('|', $this->jamssDeepSearchStrings));
        } else {
            $this->patterns = array_merge($this->jamssPatterns, explode('|', $this->jamssStrings));
        }
        $this->ext = explode('|', $this->fileExt);
            	  
    	  $this->lang = JFactory :: getLanguage();
        //$this->lang->load('plg_cron_userdetector', JPATH_ADMINISTRATOR);
        $this->lang->load('plg_cron_userdetector');
        // Include the JLog class.
        jimport('joomla.log.log');
        // Get the date so that we can roll the logs over a time interval.
        $date = JFactory::getDate()->format('Y-m-d');


            // Add a start message.
            JLog::add('Start job: CronJamss.');
              if(php_sapi_name() == 'cli' && empty($_SERVER['REMOTE_ADDR'])) {
                JLog::add('job from CLI: CronJamss.');
            } else {
               JLog::add('job from WEB: CronJamss.');
            }    
             $this->dbo = JFactory::getDBO();
             $this->_scan($time);
            JLog::add('End job: CronJamss.');
            return 4;

    }
    private function _scan($lastrun) {
    	   $jtime = microtime(true);

        /* * * * * * * * * * * * * * *  SETTINGS  * * * * * * * * * * * * * * */
        ini_set('max_execution_time', '0'); // supress problems with timeouts
        ini_set('set_time_limit', '0'); // supress problems with timeouts
        ini_set('display_errors', '0'); // show/hide errors     
       
       
       $file_check_path = $this->params->get('file_manager_path',JPATH_ROOT);	
	     if ( ($file_check_path == "JPATH_ROOT") || ($file_check_path == JPATH_ROOT) ) {
		     $file_check_path = JPATH_ROOT;
	     } else {
		     $file_check_path = JPATH_ROOT .DS . $file_check_path;
	     }
       
       
       
    //  jexit(var_dump($file_check_path));
        $this->get_filelist($file_check_path);
       
         JLog::add (JText::sprintf('DETECTOR_CRON_PROCESS_USERCOMPLETE', round(microtime(true) - $jtime, 3)));
    }
    /**
 * Get the list of the files in rootdir and all subdirs<br>
 * 
 * @global string $ignoreDirs   directories to be ignored
 * @param string $dir   directory to scan for files
 * @return array    array with found files 
 */
function get_filelist($dir) {
    
    $ignoreArr = explode('|', $this->ignoreDirs);

    $path = '';
    $toResolve = array($dir);
    while ($toResolve) {
        $thisDir = array_pop($toResolve);
        if ($dirContent = scandir($thisDir)) {
            foreach ($dirContent As $content) {
                if (!in_array($content, $ignoreArr)) { // skipping ignored dirs
                    $thisFile = "$thisDir/$content";
                    if (is_file($thisFile)) {
                        $path[$thisFile] = md5_file($thisFile);
                        $this->scan_file($thisFile);
                    } else {
                        $toResolve[] = $thisFile;
                    }
                }
            }
        }
    }
    return $path;
}

/**
 * Scan given file for all malware patterns
 * 
 * @global string $fileExt  file extension list to be scanned
 * @global array $patterns array of patterns to search for
 * @param string $path  path of the scanned file
 */
function scan_file($path) {
   // global $ext, $patterns;
    $total_results = NULL;

    if (in_array(pathinfo($path, PATHINFO_EXTENSION), $this->ext) && filesize($path)/* skip empty ones */ && !stripos($path, 'jamss')/* skip patterns file */) {

        if (!($content = file_get_contents($path))) {
            $error = 'Could not check '.$path;
            JLog::add($error);
        } else { // do a search for fingerprints
            foreach ($this->patterns As $pattern) {
                if (is_array($pattern)) { // it's a pattern
                    // RegEx modifiers: i=case-insensitive; s=dot matches also newlines; S=optimization
                    preg_match_all('/' . $pattern[0] . '/isS', $content, $found, PREG_OFFSET_CAPTURE);
                } else { // it's a string
                    preg_match_all('/' . $pattern . '/isS', $content, $found, PREG_OFFSET_CAPTURE);
                }
                $all_results = $found[0]; // remove outer array from results
                $results_count = count($all_results); // count the number of results
                $total_results += $results_count; // total results of all fingerprints
                if (!empty($all_results)) {
                    if (is_array($pattern)) { // then it has some additional comments
                        JLog::add("In file ".$path );                      
                        JLog::add("we found ".$results_count." occurence(s) of Pattern #".$pattern[2]." - ".$pattern[1]);
                        JLog::add("Details:".$pattern[3]  );
                      
                      
                        foreach ($all_results as $match) {
                            // output the line of malware code, but sanitize it before
                            JLog::add("@Offset: ".$match[1]);
                            //JLog::add("... " . htmlentities(substr($content, $match[1], 200), ENT_QUOTES) . " ...");
                            
                        }
                    } else { // it's a string, no comments available
                        JLog::add("In file ".$path);
                        JLog::add("-> we found ".$results_count." occurence(s) of String '".$pattern."'");
                        
                        foreach ($all_results as $match) {
                            // output the line of malware code, but sanitize it before
                            JLog::add("@Offset: ".$match[1].":");
                            //JLog::add("... " . htmlentities(substr($content, $match[1], 200), ENT_QUOTES) . " ...");
                        }
                    }
                }
            }
            unset($content);
        }
    }
}
}