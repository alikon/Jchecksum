<?php
/**
* Zoombie pseudo cron plugin
* Embedd a zoombie on Joomla! 
*
* @author: Alikon
* @version: 1.0.0
* @release: 22/10/2012 21.50
* @package: Alikonweb.zoombie 4 Joomla
* @copyright: (C) 2007-2012 Alikonweb.it
* @license: http://www.gnu.org/copyleft/gpl.html GNU/GPL
*
*
*
* */

// Check to ensure this file is included in Joomla!
defined( '_JEXEC' ) or die( 'Restricted access' );



class plgSystemZoombie extends JPlugin
{
	protected $interval			= 300;

	function plgSystemZoombie( &$subject, $params )
	{
		parent::__construct( $subject, $params );

		$this->interval	= (int) ($this->params->get('interval', 5)*60);

		// correct value if value is under the minimum
		if ($this->interval < 100) { $this->interval = 100; }
	}

	//function onAfterRoute()
	function onAfterDispatch()
	{
		$app = &JFactory::getApplication();
		$db		= JFactory::getDbo();
		if ($app->isSite()) {
			// Prepare the logger.
			//
			// Include the JLog class.
			jimport('joomla.log.log');

			// Get the date so that we can roll the logs over a time interval.
			$date = JFactory::getDate()->format('Y-m-d');

			// Add the logger.
			 // Add the logger.
        JLog::addLogger(
                // Pass an array of configuration options.
                // Note that the default logger is 'formatted_text' - logging to a file.
                array(
                    // Set the name of the log file.
                    'text_file' => 'zoombie.' . $date . '.php',
                    // Set the path for log files.
                //    'text_file_path' => __DIR__ . '/logs'
                ),JLog::INFO
        );
			

			//$options['text_file'] = 'zoombie.php';
			//$options['text_file_path'] = __DIR__ . '/logs';
			//$log = JLog::addLogger($options,JLog::INFO);


			$now = &JFactory::getDate();
			$now = $now->toUnix();
			

			if($last = $this->params->get('last_run')) {
				$diff = $now - $last;
				} else {
					$diff = $this->interval+1;
				}

				if ($diff > $this->interval) {

					//JLog::add('asking for lock .');
					
					/*
					$query = $db->getQuery(true);
					$lock_query='SELECT * FROM #__zoombie WHERE task='.$db->Quote('zoombie');

					$db->setQuery($lock_query);
										if (!$db->query()) {
							jexit($db->getErrorMsg());
							return false;
				  }
					//JLog::add($lock_query);
					$locked = $db->loadResult();
					JLog::add('locked:'.$locked);
					if (!$locked){
					*/
					$fp = fopen(JPATH_SITE."/plugins/system/zoombie/logs/lock.txt", "r+");
			
					if (flock($fp, LOCK_EX | LOCK_NB)) { // do an exclusive lock
       
						//JLog::add('websatan last run:'.date('d.m.Y, H:i:s',$last));
					//	$insert='INSERT INTO #__zoombie VALUES('.$db->Quote('zoombie').',NULL)';
						//JLog::add($insert);
						//$db->setQuery($insert);
						//$db->query();
						//if (!$db->query()) {
						//	jexit($db->getErrorMsg());
						//	return false;
					//	}
						//JLog::add('Looking to run now:'.date('d.m.Y, H:i:s',$now).' diff:'.$diff.' interval:'.$this->interval);
						// do the work
						$this->doSatanJobs();

						//JLog::add('lastrun:'.$this->params->get('last_run'));
						jimport( 'joomla.registry.format' );
						
						$this->params->set('last_import',$now);

						$handler = &JRegistryFormat::getInstance('json');
						$params = new JObject();
						$params->set('interval',$this->params->get('interval',5));
						$params->set('last_run',$now);
						$params = $handler->objectToString($params,array());

						$query = 	'UPDATE #__extensions'.
						' SET params='.$db->Quote($params).
						' WHERE element = '.$db->Quote('zoombie').
						' AND folder = '.$db->Quote('system').
						' AND enabled >= 1'.
						' AND type ='.$db->Quote('plugin').
						' AND state >= 0';
						$db->setQuery($query);
						//$db->query();
						if (!$db->query()) {
							jexit($db->getErrorMsg());
							return false;
						}
						
						flock($fp, LOCK_UN); // release the lock
					/*
				     $db->setQuery('TRUNCATE TABLE #__zoombie')	;		
						if (!$db->query()) {
							jexit($db->getErrorMsg());
							return false;
						}
						*/
						//JLog::add('release the lock .');
						fclose($fp);
						//JLog::add('close file .');
			     
					}

					//} else {
						//JLog::add(' last websatan run :'.date('d.m.Y, H:i:s',$last).' next run after:'.($this->interval - $diff).' seconds');
					//	JLog::add(' next run scheduled: '.date('H:i:s - d.m.Y',($this->interval+$last)));
					}
          	 
					
				}
			}

			function doSatanJobs(){
				$app = JFactory::getApplication();
				$db		= JFactory::getDbo();
				$query = $db->getQuery(true);

				// Get a list of the plugins from the database.
				$query->select('p.*')
				->from('#__extensions AS p')
				->where('p.enabled = 1')
				->where('p.type = ' . $db->quote('plugin'))
				->where('p.folder = ' . $db->quote('cron'))
				->order('p.ordering');

				// Push the query builder object into the database connector.
				$db->setQuery($query);

				// Get all the returned rows from the query as an array of objects.
				$plugins = $db->loadObjectList();
				if (count($plugins) > 0) {JLog::add('Zoombie alive.'); }
              
			       
				// Log how many plugins were loaded from the database.
				//JLog::add(sprintf('.founded %d cronnable plugin(s).', count($plugins)));
				foreach ($plugins as $plugin) {
                    //JLog::add($plugin->element);
					$params = new JRegistry;
					// loadJSON is @deprecated    12.1  Use loadString passing JSON as the format instead.
					// $params->loadString($this->item->params, 'JSON');
					// "item" should not be present.
					//(var_dump($plugin->params));
					$params->loadJSON($plugin->params);
					$now = &JFactory::getDate();
					$now = $now->toUnix();
					$interval	= (int) ($params->get('interval', 5)*60);
					$old_interval=$params->get('interval', 5);

					// correct value if value is under the minimum
					if ($interval < 300) { $interval = 300; }
					if($last = $params->get('last_run')) {
						$diff = $now - $last;
					} else {
							$diff = $interval+1;
					}
					if ($diff > $interval) {

							//JLog::add($plugin->element.' last run:'.date('d.m.Y, H:i:s',$last).' diff:'.$diff.' interval '.$interval);  
									
							    // Trigger the event and let the Joomla plugins do all the work.
                                    
							    JPluginHelper::importPlugin('cron',$plugin->element);
							    $dispatcher = & JDispatcher::getInstance();
							    $results = $dispatcher->trigger('doCron'.$plugin->element, array($last));
							    if (is_array($results)&&($results[0]==4)){
							    //			    jexit(var_dump($results[0]));
							    // Log the results.
                  //$handler = &JRegistryFormat::getInstance('json');
							    //$uparams = new JObject();
							    
							    $params->set('interval',$old_interval);
							    $params->set('last_run',$now);
							   // $params = $handler->objectToString($params,array());
                  //(var_dump($params));
							    $query = 	'UPDATE #__extensions'.
							    ' SET params='.$db->Quote($params).
							    ' WHERE element = '.$db->Quote($plugin->element).
							    ' AND folder = '.$db->Quote('cron').
							    ' AND enabled >= 1'.
							    ' AND type ='.$db->Quote('plugin').
							    ' AND state >= 0';
							    $db->setQuery($query);
							    //$db->query();
							    if (!$db->query()) {
							    	jexit($db->getErrorMsg());
							    	return false;
							    }
							  } else {
							  	JLog::add($plugin->element.' no ACL to run');
							  }	  
						    //  	JLog::add($plugin->element.' Updated interval:'.$old_interval.' last '.date('d.m.Y, H:i:s',$now));
						
						  //	} else {
								//JLog::add($plugin->element.' will run after:'.($interval - $diff).' seconds'.' last run was:'.date('d.m.Y, H:i:s',$last));
								//JLog::add($plugin->element.' will run next:'.date('d.m.Y, H:i:s',($interval+$last)).' last run was:'.date('d.m.Y, H:i:s',$last));
							//	JLog::add('.zoombie '.$plugin->element.' alive scheduled: '.date('H:i:s - d.m.Y',($interval+$last)));
							}
							$plugin=null;
							
						}

           if (count($plugins) > 0) { JLog::add('Zoombie dead.'); }


				}
			}