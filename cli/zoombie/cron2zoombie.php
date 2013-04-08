#!/usr/bin/php
<?php
/**
 * An example command line application built on the Joomla Platform.
 *
 * To run this example, adjust the executable path above to suite your operating system,
 * make this file executable and run the file.
 *
 * Alternatively, run the file using:
 *
 * php -f cli2zoombie.php
 *
 * Note, this application requires configuration.php and the connection details
 * for the database may need to be changed to suit your local setup.
 *
 * @package    Joomla.Examples
 * @copyright  Copyright (C) 2005 - 2011 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE

  JAccess::clearStatics();
  $session	 =	JFactory::getSession();
  $session->set( 'user', new JUser( (int) $myUserIdid ) );

 */
// We are a valid Joomla entry point.
define('_JEXEC', 1);
defined('_JEXEC') or die('Restricted access');

define('DS', DIRECTORY_SEPARATOR);

// Load system defines
if (file_exists(dirname(dirname(__FILE__)) . '/defines.php')) {
    require_once dirname(dirname(__FILE__)) . '/defines.php';
}

if (!defined('_JDEFINES')) {
    define('JPATH_BASE', dirname(dirname(__FILE__)));
    require_once JPATH_BASE . '/includes/defines.php';
}

// Get the framework.
require_once JPATH_LIBRARIES . '/import.php';

// Bootstrap the CMS libraries.
require_once JPATH_LIBRARIES . '/cms.php';
jimport('joomla.database.database');
jimport('joomla.application.input');
jimport('joomla.event.dispatcher');
jimport('joomla.application.input');
jimport('joomla.event.dispatcher');
jimport('joomla.environment.response');
jimport('joomla.log.log');


$run = new Cron2Zoombie();
$fp = fopen(JPATH_SITE . "/plugins/system/zoombie/logs/lock.txt", "r+");
//echo JPATH_SITE.'/plugins/system/zoombie/logs/lock.txt';
if (flock($fp, LOCK_EX | LOCK_NB)) { // do an exclusive lock
    $output = $run->doExecute();
} else {
    $output = 'Locked';
}
echo $output;

/**
 * An example command line application class.
 *
 * This application shows how to build an application that could serve as a cron manager
 * that makes use of Joomla plugins.
 *
 * @package  Joomla.Examples
 * @since    11.3
 */
class Cron2Zoombie {

    /**
     * A database object for the application to use.
     *
     * @var    JDatabase
     * @since  11.3
     */
    protected $dbo = null;
    protected $app = null;
    protected $sup = null;
    var $sendfile = null;
    var $runned = null;
    var $interval = null;
    var $task_i_time=null;
    /**
     * Class constructor.
     *
     * This constructor invokes the parent JApplicationCli class constructor,
     * and then creates a connector to the database so that it is
     * always available to the application when needed.
     *
     * @since   11.3
     * @throws  JDatabaseException
     */
    public function __construct() {
     
        // Call the parent __construct method so it bootstraps the application class.
        //   parent::__construct();
        $this->app = JFactory::getApplication('site');
        //
        // Prepare the logger.
        //
         $this->task_i_time = microtime(true);

        // Include the JLog class.
        jimport('joomla.log.log');

        // Get the date so that we can roll the logs over a time interval.
        $date = JFactory::getDate()->format('Y-m-d');

        JLog::addLogger(
                // Pass an array of configuration options.
                // Note that the default logger is 'formatted_text' - logging to a file.
                array(
            // Set the name of the log file.
            'text_file' => 'cli2zoombie.' . $date . '.php',
			
                // Set the path for log files.
                //    'text_file_path' => __DIR__ . '/logs'
                ), JLog::INFO
        );

        //
        // Prepare the database connection.
        //

	jimport('joomla.database.database');
        $config = JFactory::getConfig();
        // Note, this will throw an exception if there is an error
        // creating the database connection.
        /*
          $this->dbo = JDatabase::getInstance(
          array(
          'driver' => $this->get('dbDriver'),
          'host' => $this->get('dbHost'),
          'user' => $this->get('dbUser'),
          'password' => $this->get('dbPass'),
          'database' => $this->get('dbName'),
          'prefix' => $this->get('dbPrefix'),
          )
          );
         */
        $this->dbo = JFactory::getDBO();
        // Get the quey builder class from the database.
        $query = $this->dbo->getQuery(true);

        // Get a list of the admins from the database.
        $query->select('u.id')
                ->from('#__user_usergroup_map AS m, #__usergroups as  g, #__users as u')
                ->where('g.title = ' . $this->dbo->quote('Super Users'))
                ->where('g.id = m.group_id')
                ->where('u.id = m.user_id');


        // Push the query builder object into the database connector.
        $this->dbo->setQuery($query);
        $this->sup = $this->dbo->loadObjectList();
        //echo(var_dump($this->sup[0]->id));
    }

    /**
     * Custom doExecute method.
     *
     * This method loads a list of the published plugins from the 'cron' group,
     * then loads the plugins and registers them against the 'doCron' event.
     * The event is then triggered and results logged.
     *
     * Any configuration for the cron plugins is done via the Joomla CMS
     * administrator interface and plugin parameters.
     *
     * @return  void
     *
     * @since   11.3
     */
    public function doExecute() {
        $this->task_i_time = microtime(true);
        // Check if we have some critical information.
        // and get Zoombie system plugin parameters
        // $this->getZoombieSystem();
        //  if (!$this->engine_check()) {
        if (!$this->getZoombieSystem()) {
            JLog::add('Starter engine is not cron.');
            echo('Starter engine is not cron.');
            return;
        }
        //

        if (!defined('JPATH_PLUGINS') || !is_dir(JPATH_PLUGINS)) {
            throw new Exception('JPATH_PLUGINS not defined');
        }

        // Add a start message.
        JLog::add('Starting cli2zoombie run.');
        echo('Starting cli2zoombie run ..');
        //
        // Prepare the plugins
        //

		// Get the quey builder class from the database.
        $query = $this->dbo->getQuery(true);

        // Get a list of the plugins from the database.
        $query->select('p.*')
                ->from('#__extensions AS p')
                ->where('p.enabled = 1')
                ->where('p.type = ' . $this->dbo->quote('plugin'))
                ->where('p.folder = ' . $this->dbo->quote('zoombie'))
                ->order('p.ordering');

        // Push the query builder object into the database connector.
        $this->dbo->setQuery($query);

        // Get all the returned rows from the query as an array of objects.
        $plugins = $this->dbo->loadObjectList();
        // Force reload from database
        //$user = JFactory::getUser();
        //$session = JFactory::getSession();
        //$session->set('user', new JUser(42));
        // Log how many plugins were loaded from the database.
        JLog::add(sprintf('.loaded %d plugin(s).', count($plugins)));
        echo (sprintf(' founded %d zoombie plugin(s).', count($plugins))) . "\n\n";
        // Loop through each of the results from the database query.

        if (count($plugins) > 0) {
            JLog::add('Zoombie alive.');
        }
        $tasks = array();
        //
        // Run the cron plugins.
        //
	    	// Each plugin should have been installed in the Joomla CMS site
        // and must include a 'doCron' method. Configuration of the plugin
        // is done via plugin parameters.
        foreach ($plugins as $plugin) {
            JLog::add('Zoombie:' . $plugin->element);
            echo('Zoombie:' . $plugin->element) . "\n";
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
            $runned = (int) $params->get('runned', 0);
            $runned++;
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

                //echo($plugin->element.' old run:'.date('d.m.Y, H:i:s',$last).' diff:'.$diff.' interval '.$interval) . "\n";  

                /*
                  //force credentials
                  //The minimum group
                  $options=array();
                  $options['group'] = 'Public Backend';
                  // Set the access control action to check.
                  $options['action'] = 'core.login.admin';
                  $credentials = array();
                  $credentials['username'] = 'admin';
                  $credentials['password'] = 'admin';
                  if (true === $this->app->login($credentials,$options )) {
                  echo $plugin->element. 'user logged ';
                  } else {
                  echo $plugin->element. 'cli not granted ';
                  }

                  $session =& JFactory::getSession();
                  $session->set( 'admin', 'admin' );
                  $user = JFactory::getUser(42);
                  $options=array();
                  $options['group'] = 'Public Backend';
                  // Set the access control action to check.
                  $options['action'] = 'core.login.admin';
                  $credentials = array();
                  $credentials['username'] = 'admin';
                  $credentials['password'] = 'admin';
                  if (true === $this->app->login($credentials,$options )) {
                  echo $plugin->element. 'user logged ';
                  } else {
                  echo $plugin->element. 'cli not granted ';
                  }



                 */
                //get one superadministrator

                $admin = JFactory::getUser((int) $this->sup[0]->id);
                // var_dump($admin->getAuthorisedViewLevels());
                // Register the needed session variables
                $session = JFactory::getSession();
                $session->set('user', $admin);
                $task_i_time = microtime(true);
                // Trigger the event and let the Joomla plugins do all the work.                                    
                JPluginHelper::importPlugin('zoombie', $plugin->element);
                $dispatcher = & JDispatcher::getInstance();
                $results = $dispatcher->trigger('GOalive' . $plugin->element, array($last));
                // jexit(var_dump($results));
                if (is_array($results) && ($results[0] == 4)) {
                    // Log the results.
                    //jexit(var_dump($results));

                    $params->set('interval', $old_interval);
                    $params->set('last_run', $now);
                    $durata = round(microtime(true) - $task_i_time, 3);
                    $params->set('runned', $runned);
                    $params->set('durata', $durata);
                    //(var_dump($params));
                    $query = 'UPDATE #__extensions' .
                            ' SET params=' . $this->dbo->Quote($params) .
                            ' WHERE element = ' . $this->dbo->Quote($plugin->element) .
                            ' AND folder = ' . $this->dbo->Quote('zoombie') .
                            ' AND enabled >= 1' .
                            ' AND type =' . $this->dbo->Quote('plugin') .
                            ' AND state >= 0';
                    $this->dbo->setQuery($query);
                    //$db->query();
                    if (!$this->dbo->query()) {
                        jexit($this->dbo->getErrorMsg());
                        return false;
                    }
                    $task = new stdClass();

                    $task->id = $runned;

                    $task->name = $plugin->element;

                    $task->durata = $durata;

                    $task->next = $interval + $now;

                    $tasks[] = $task;
                    echo $plugin->element . ' just runned in ' . $durata . "\n";
                } else {
                    JLog::add($plugin->element . ' no ACL to run');
                    echo($plugin->element . ' no ACL to run');
                }
                $session->destroy();
            } else {
                //  echo($plugin->element.' will run after:'.($interval - $diff).' seconds'.' last run was:'.date('d.m.Y, H:i:s',$last)) . "\n";
                echo($plugin->element . ' will run next:' . date('H:i:s, d.m.Y', ($interval + $last)) . ' old run was:' . date('H:i:s, d.m.Y', $last)) . "\n";
                //  echo('.zoombie '.$plugin->element.' alive scheduled: '.date('H:i:s - d.m.Y',($interval+$last))) . "\n";
            }
            $plugin = null;
            echo "\n";
        }





        //


        $this->sendNotice($tasks);
        $this->setZoombieSystem();
        $task_time = round(microtime(true) - $this->task_i_time, 3);
        JLog::add('Zoombie task dead in ' . $task_time);
        echo ('Finished cli2zoombie run in ').$task_time . "\n";
        //JLog::add('Finished cli2zoombie run.');
    }

    protected function sendNotice($tasks) {
        $config = & JFactory::getConfig();
        $task_time = round(microtime(true) - $this->task_i_time, 3);
        $now = &JFactory::getDate();
        $now = $now->toUnix();
        $fromemail = $config->getValue('config.mailfrom');
        
        $sendfile = $this->sendfile;
        $runned = (int) $this->runned;
        $interval = (int) ($this->interval * 60);

        $next = $interval + $now;
        $toemail = $fromemail;

        $subject = 'Zoombie Daemon : ' . $config->getValue('config.sitename');
        $body = 'Runned task' . "\n\n";

        $body .= "Task    #:" . ++$runned . "\n"
                . "Task name:  Zoombie Daemon Task\n"
                . "Runned   : " . date('d.m.Y, H:i:s', $now) . "\n"
                . "Times    : " . $task_time . "\n"
                . "Scheduled: " . date('d.m.Y, H:i:s', $next) . "\n";

        $body.='-------------------' . "\n";

        foreach ($tasks as $task) {

            $body.= "Task    #: " . $task->id . "\n"
                    . "Task name: " . $task->name . "\n"
                    . "Runned   : " . date('d.m.Y, H:i:s', $now) . "\n"
                    . "Times    : " . $task->durata . "\n"
                    . "Scheduled: " . date('d.m.Y, H:i:s', $task->next) . "\n";
            $body.='-------------------' . "\n";
        }



        $body.="\n" . 'Scheduled task' . "\n";

        $next = $this->nextTask();
        //var_dump($next);
        foreach ($next as $task) {

            //$body .= "\n\nTask: " . sprintf("[%15s]", $task->pname) . ' next: ' . date('d.m.Y, H:i:s', $task->next);
            $body .= "\n\n Task #: " . sprintf("[%5s]", ++$task->runned) . ' ' . sprintf("%-15s", $task->pname) . ' ' . date('d.m.Y, H:i:s', $task->next);
        }

        $footer = "\n\n  Zoombie Task Scheduler Application 4 Joomla by  http://www.alikonweb.it \n";
        $body = $body . $footer;

        //Jexit($body);

        $mailer = & JFactory::getMailer();
        $mailer->setSender(array($fromemail, 'Zoombie Task event log'));
        $mailer->addRecipient($toemail);
        $mailer->setSubject($subject);
        $mailer->setBody($body);
        $date = JFactory::getDate()->format('Y-m-d');

        $attachment = JPATH_SITE . DS . 'logs' . DS . 'cli2zoombie.' . $date . '.php';
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

   
    public function getZoombieSystem() {
        $query = $this->dbo->getQuery(true);
        // Get params of the zoombie plugin from the database.
        $query->select('params')
                ->from('#__extensions')
                ->where('type = ' . $this->dbo->quote('plugin'))
                ->where('folder = ' . $this->dbo->quote('system'))
                ->where('element = ' . $this->dbo->quote('zoombie'))
                ->where('enabled = 1');
        $now = &JFactory::getDate();
        $now = $now->toUnix();
        // Push the query builder object into the database connector.
        $this->dbo->setQuery($query);
        $this->par = $this->dbo->loadObjectList();
        //echo(var_dump($this->par[0]->params));
        $params = new JRegistry;
        $params->loadJSON($this->par[0]->params);
        $this->sendfile = $params->get('sendfile', '1');
        $this->runned = $params->get('runned', 0);
        $this->interval = (int) ($params->get('interval', 5) );
        $this->key = $params->get('key', '');
        $this->cronmode = (int) $params->get('cronmode', '1');
        $this->sendmail = $params->get('sendmail', '1');
        $this->last_run = $now;
        $this->taskfile = 'Secret Key';
        $this->taskremove = 'Secret Key';
        $this->durata = 0;

        if ((int) $params->get('cronmode', 1) != 1) {
            return true;
        } else {
            return false;
        }
    }

    public function setZoombieSystem() {
        jimport('joomla.registry.format');
        $now = &JFactory::getDate();
        $now = $now->toUnix();
        $handler = &JRegistryFormat::getInstance('json');
        $params = new JObject();
        $params->set('cronmode', $this->cronmode);
        $params->set('sendmail', $this->sendmail);
        $params->set('sendfile', $this->sendfile);
        $params->set('key', $this->key);
        $params->set('interval', $this->interval);
        $params->set('last_run', $now);
        $params->set('taskfile', 'Secret Key');
        $params->set('taskremove', 'Secret Key');
        $durata = round(microtime(true) - $this->task_i_time, 3);
        $params->set('durata', $durata);
        $runned = (int) $this->runned;
        $runned++;
        $params->set('runned', $runned);
        $params = $handler->objectToString($params, array());

        $query = 'UPDATE #__extensions' .
                ' SET params=' . $this->dbo->Quote($params) .
                ' WHERE element = ' . $this->dbo->Quote('zoombie') .
                ' AND folder = ' . $this->dbo->Quote('system') .
                ' AND enabled >= 1' .
                ' AND type =' . $this->dbo->Quote('plugin') .
                ' AND state >= 0';
        $this->dbo->setQuery($query);

        //$db->query();

        if (!$this->dbo->query()) {
            jexit($this->dbo->getErrorMsg());
            return false;
        }
    }

    private function nextTask() {
        $this->dbo = JFactory::getDBO();
// Get the quey builder class from the database.
        $query = $this->dbo->getQuery(true);

        // Get a list of the zoombie plugins from the database.
        $query->select('p.*')
                ->from('#__extensions AS p')
                ->where('p.enabled = 1')
                ->where('p.type = ' . $this->dbo->quote('plugin'))
                ->where('p.folder = ' . $this->dbo->quote('zoombie'))
                ->order('p.ordering');

        // Push the query builder object into the database connector.
        $this->dbo->setQuery($query);

        // Get all the returned rows from the query as an array of objects.
        $plugins = $this->dbo->loadObjectList();

        $items = array();
        foreach ($plugins as $plugin) {
            $params = new JRegistry;
            $params->loadJSON($plugin->params);
            $now = &JFactory::getDate();
            $now = $now->toUnix();
            $interval = (int) ($params->get('interval', 5) * 60);
            $runned = (int) $params->get('runned', 0);

            $data = new stdClass();
            $data->pid = $plugin->extension_id;
            $data->pname = $plugin->element;
            $data->last = $params->get('last_run');
            $data->next = $interval + $params->get('last_run');
            $data->runned = $runned;
            $data->durata = $params->get('durata');

            //var_dump($data);
            $items[] = $data;
        }
        usort($items, array("Cron2Zoombie", "mysort"));
        // var_dump($items);
        return $items;
    }

    function mysort($a, $b) {
        if ($a->next == $b->next) {
            return 0;
        }
        return ($a->next < $b->next) ? -1 : 1;
    }

}

/*
// Wrap the execution in a try statement to catch any exceptions thrown anywhere in the script.
try {
    // Instantiate the application object, passing the class name to JApplicationCli::getInstance
    // and use chaining to execute the application.
    JApplicationCli::getInstance('cli2zoombie')->execute();
} catch (Exception $e) {
    // An exception has been caught, echo the message.
    fwrite(STDOUT, $e->getMessage() . "\n");
    exit($e->getCode());
}
*/