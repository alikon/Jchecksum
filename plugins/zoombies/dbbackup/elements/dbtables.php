<?php
/**
 * JFormField dbtables
 * @version 1.0.0
 * @copyright Copyright (C) 2012 alikonweb.it All rights reserved.
 * @license GNU/GPL
 * @author alikon
 * @link http://www.alikonweb.it/
 *
 */

defined('JPATH_BASE') or die;
JFormHelper::loadFieldClass('list');

class JFormFielddbtables extends JFormFieldList {

    /**
     * Element name
     *
     * @access	protected
     * @var		string
     */
    protected $type = 'Dbtables';

    protected function getOptions() {

        // Initialize variables.
        $options = array();
        $config = & JFactory::getApplication();
        $dbname = $config->getCfg('db');
        // Get database tables 
        //$db = JFactory::getDbo(); 
        //$tables = $db->getTableList(); 
        // Get the database object and a new query object.
        $db = JFactory::getDBO();
        $query = $db->getQuery(true);

        // Build the query.
        $query->select('DISTINCT table_name AS value,table_name AS text');
        $query->from('information_schema.tables');
        $query->where('table_schema = ' . $db->Quote($dbname));

        // Set the query and load the options.
        $db->setQuery($query);
        $options = $db->loadObjectList();
        $object = new StdClass;
        $object->value = '*';
        $object->text = '- All Database -';

        $options = array_merge(array($object), $options);
        //jexit(var_dump($options));
        // Merge any additional options in the XML definition.
        $options = array_merge(parent::getOptions(), $options);

        return $options;


        /*

          SELECT table_name, table_type, engine FROM information_schema.tables WHERE table_schema = 'mydb'
          //$options[]	= JHtml::_('select.option', '*', '- None Selected -');

          foreach ($tables as $table) {
          $options[] = JHtml::_('select.option', $table, $table);

          }

          foreach ($options as $i=>$option) {
          $options[$i]->text = JText::_($option->text);
          }
          $options = array_merge(parent::getOptions(), $options);


          return  JHtml::_('select.genericlist', $options, 'dbtables', ' ', 'value', 'text', '*');

         */
    }

}
