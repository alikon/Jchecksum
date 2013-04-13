<?php

/**
 * Zoombie Extension update cron plugin
 * Embedd Extension update on Joomla! 
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

class plgZoombieIndexer extends JPlugin {

    /**
     * Constructor function
     *
     * @param object $subject
     * @param object $config
     * @return plgZoombieFileBackup
     */
    var $cfg = null;
    var $mailfrom = null;
    var $fromname = null;
    var $name = null;
    var $dbo = null;
    var $lang = null;
    var $date = null;
    var $file = null;
    var $sqlzip = null;
    private $_time = null;
    var $task_i_time=null;

    /**
     * Start time for each batch
     *
     * @var    string
     * @since  2.5
     */
    private $_qtime = null;

    function plgZoombieIndexer(&$subject, $params) {
        parent::__construct($subject, $params);
        $this->task_i_time = microtime(true);
        if (!defined('DS')) {
            define('DS', DIRECTORY_SEPARATOR);
        }
        ini_set('display_errors', 0);
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', 480);
    }

    function goAliveIndexer($time) {
        $this->runned = (int) $this->params->get('runned', 0);
        $this->runned++;
        $this->lang = JFactory :: getLanguage();
        $this->lang->load('plg_zoombie_update');
        // Include the JLog class.
        jimport('joomla.log.log');
        // Get the date so that we can roll the logs over a time interval.
        $this->date = JFactory::getDate()->format('Y-m-d');
        $config = & JFactory::getApplication();
        $this->name = 'sitename';  //$config->getCfg('db');        
        // Add a start message.
        JLog::add('Start task #' . $this->runned . ' ZoombieIndexer');
        $this->dbo = JFactory::getDBO();
        // Get the update cache time
        $this->finder_indexer();
        $task_time = round(microtime(true) - $this->task_i_time, 3);
        JLog::add('End task: ZoombieIndexer. in ' . $task_time);
        return 4;
        //return 8;
    }

    function finder_indexer() {
        // initialize the time value
        $this->_time = microtime(true);
        jimport('joomla.log.log');
        // import library dependencies
        require_once JPATH_ADMINISTRATOR . '/components/com_finder/helpers/indexer/indexer.php';
        jimport('joomla.application.component.helper');
        jimport('joomla.environment.uri');
        // fool the system into thinking we are running as JSite with Finder as the active component
        JFactory::getApplication('site');
        $_SERVER['HTTP_HOST'] = 'domain.com';
        define('JPATH_COMPONENT_ADMINISTRATOR', JPATH_ADMINISTRATOR . '/components/com_finder');

        // Disable caching.
        $config = JFactory::getConfig();
        $config->set('caching', 0);
        $config->set('cache_handler', 'file');

        // Reset the indexer state.
        FinderIndexer::resetState();

        // Import the finder plugins.
        JPluginHelper::importPlugin('finder');

        // Starting Indexer.
        JLog::add(JText::_('FINDER_CLI_STARTING_INDEXER'));

        // Trigger the onStartIndex event.
        JDispatcher::getInstance()->trigger('onStartIndex');

        // Remove the script time limit.
        @set_time_limit(0);

        // Get the indexer state.
        $state = FinderIndexer::getState();

        // Setting up plugins.
        JLog::add(JText::_('FINDER_CLI_SETTING_UP_PLUGINS'));

        // Trigger the onBeforeIndex event.
        JDispatcher::getInstance()->trigger('onBeforeIndex');

        // Startup reporting.
        JLog::add(JText::sprintf('FINDER_CLI_SETUP_ITEMS', $state->totalItems, round(microtime(true) - $this->_time, 3)));

        // Get the number of batches.
        $t = (int) $state->totalItems;
        $c = (int) ceil($t / $state->batchSize);
        $c = $c === 0 ? 1 : $c;

        try {
            // Process the batches.
            for ($i = 0; $i < $c; $i++) {
                // Set the batch start time.
                $this->_qtime = microtime(true);

                // Reset the batch offset.
                $state->batchOffset = 0;

                // Trigger the onBuildIndex event.
                JDispatcher::getInstance()->trigger('onBuildIndex');

                // Batch reporting.
                JLog::add(JText::sprintf('FINDER_CLI_BATCH_COMPLETE', ($i + 1), round(microtime(true) - $this->_qtime, 3)));
            }
        } catch (Exception $e) {
            // Display the error
            JLog::add($e->getMessage());

            // Reset the indexer state.
            FinderIndexer::resetState();

            // Close the app
            $this->close($e->getCode());
        }

        // Total reporting.
        JLog::add(JText::sprintf('FINDER_CLI_PROCESS_COMPLETE', round(microtime(true) - $this->_time, 3)));

        // Reset the indexer state.
        FinderIndexer::resetState();
    }

}