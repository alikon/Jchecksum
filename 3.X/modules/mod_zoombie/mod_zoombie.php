<?php
/**
 * @package		Joomla.Administrator
 * @copyright	Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

// Include the mod_popular functions only once.
require_once dirname(__FILE__).'/helper.php';

// Get module data.
$list = modZoombieHelper::getList($params);

// Render the module
require JModuleHelper::getLayoutPath('mod_zoombie', $params->get('layout', 'default'));
