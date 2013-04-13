<?php
/**
 * @author:  Alikon
 * @version:  1.1.1
 * @release:  11/04/2013 21.50
 * @package:  Alikonweb.zoombie 4 Joomla
 * @copyright: (C) 2007-2013 Alikonweb.it
 * @license:  http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link:     http://www.alikonweb.it
 */

// No direct access.
defined('_JEXEC') or die;

// Include the mod_zoombie functions only once.
require_once dirname(__FILE__).'/helper.php';

// Get module data.
$list = modZoombieHelper::getList($params);

// Render the module
require JModuleHelper::getLayoutPath('mod_zoombie', $params->get('layout', 'default'));
