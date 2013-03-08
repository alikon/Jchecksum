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
			->where('p.folder = ' . $db->quote('zoombie').'OR (p.folder = ' . $db->quote('system').' AND p.element = '.$db->quote('zoombie').' )')
			->order('p.ordering');

    	// Push the query builder object into the database connector.
	    $db->setQuery($query);

	    // Get all the returned rows from the query as an array of objects.
	    $plugins = $db->loadObjectList();	
	
		$items=array();
	    foreach ($plugins as $plugin) {
		    $params = new JRegistry;
		    //$params->loadJSON($plugin->params);
			$params->loadString($plugin->params, 'JSON');
			$now = &JFactory::getDate();
			$now = $now->toUnix();
			$interval	= (int) ($params->get('interval', 5)*60);
			if($last = $params->get('last_run')) {
				$diff = $now - $last;
			} else {
				$diff = $interval+1;
			}
			//var_dump($interval);
			$data = new stdClass();
			$link = JRoute::_('index.php?option=com_plugins&task=plugin.edit&extension_id=' . $plugin->extension_id);
      $data->link='<a href="' . $link . '" title="Task settings" >' . $plugin->element . '</a>';
			$data->pid =$plugin->extension_id;
			$data->pname =$plugin->element;
			$data->last = $last;
			$data->next = $interval+$last;
			
			
			//
			switch ($interval/60)
      {
      case 1:
       $data->freq="1 min";
       break;
      case 2:
       $data->freq="2 min";
       break;
      case 3:
       $data->freq="3 min";
       break;
      case 4:
       $data->freq="4 min";
       break;
      case 5:
       $data->freq="5 min";
       break;
      case 10:
       $data->freq="10 min";
       break;
      case 15:
       $data->freq="15 min";
       break;
      case 20:
       $data->freq="20 min";
       break;       
      case 30:
       $data->freq="30 min";
       break;
      case 60:
       $data->freq="1 hour";
       break;
      case 120:
       $data->freq="2 hours";
       break;
      case 180:
       $data->freq="3 hours";
       break;
      case 240:
       $data->freq="4 hours";
       break;
      case 360:
       $data->freq="6 hours";
       break;
      case 720:
       $data->freq="12 hours";
       break;
      case 1440:
       $data->freq=" 24 hours";
       break;   
      case 2880:
       $data->freq=" 2 days";
       break;
      case 10080:
       $data->freq=" 1 week";
       break;
      case 20160:
       $data->freq=" 2 weeks";
       break;
      case 40320:
       $data->freq=" 1 month";
       break;
      case 80640:
       $data->freq="2 months";
       break;      
      case 120960:
       $data->freq=" 3 months";
       break;
      case 161280:
       $data->freq=" 4 months";
       break;
      case 241920:
       $data->freq="  6 months";
       break;
      case 483840:
       $data->freq=" 1 year";
       break;                           
      }
      //var_dump($data);
			$items[] =$data;
	    }
        
		return $items;
	}


}
