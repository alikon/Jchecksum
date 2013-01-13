<?php
/**
 * @copyright	Copyright (C) 2005 - 2013 Alikonweb.it, Inc. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * @package		Joomla.Administrator
 * @subpackage	mod_zoombie
 * @since		2.5
 */
abstract class modZoombieHelper
{
	/**
	 * Get a list of the zoombie
	 *
	 * @param	JObject		The module parameters.
	 *
	 * @return	array
	 */
	public static function getList($params)
	{
 
		// Initialise variables
        $db		= &JFactory::getDbo();
            // Get the quey builder class from the database.
     	$query = $db->getQuery(true);
         
	    // Get a list of the plugins from the database.
	    $query->select('p.*')
			->from('#__extensions AS p')
			->where('p.enabled = 1')
			->where('p.type = ' . $db->quote('plugin'))
			->where('p.folder = ' . $db->quote('zoombie'))
			->order('p.ordering');

    	// Push the query builder object into the database connector.
	    $db->setQuery($query);

	    // Get all the returned rows from the query as an array of objects.
	    $plugins = $db->loadObjectList();	
	
		$items=array();
	    foreach ($plugins as $plugin) {
		    $params = new JRegistry;
		    $params->loadJSON($plugin->params);
			$now = &JFactory::getDate();
			$now = $now->toUnix();
			$interval	= (int) ($params->get('interval', 5)*60);
			if($last = $params->get('last_run')) {
				$diff = $now - $last;
			} else {
				$diff = $interval+1;
			}
			$data = new stdClass();
			$data->pname =$plugin->element;
			$data->last = $last;
			$data->next = $interval+$last;
			$items[] =$data;
	    }
        
		return $items;
	}


}
