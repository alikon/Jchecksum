<?php

/**
 * JFormField Filelink
 * @author:  Alikon
 * @version:  1.1.1
 * @release:  11/04/2013 21.50
 * @package:  Alikonweb.zoombie 4 Joomla
 * @copyright: (C) 2007-2013 Alikonweb.it
 * @license:  http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link:     http://www.alikonweb.it
 *
 */
defined('JPATH_BASE') or die;

jimport('joomla.form.formfield');

class JFormFieldFileLink extends JFormField {

    /**
     * Element name
     *
     * @access	protected
     * @var		string
     */
    protected $type = 'filelink';

    protected function getInput() {
//		require_once dirname(dirname(__FILE__)).'/helper.php';

        $rows = (int) $this->element['rows'];
        $cols = (int) $this->element['cols'];
        $class = ( $this->element['class'] ? 'class="' . (string) $this->element['class'] . '"' : 'class="value"' );

        $links = '';
        $files = array();

        $values = $this->getBackupFiles();
        //jexit(var_dump($values));
        if (is_array($values) && count($values) > 0) {
            $db = JFactory::getDbo();
            $query = $db->getQuery(true);
            $query->select('params');
            $query->from('#__extensions');
            $query->where('type = ' . $db->Quote('plugin') . ' AND element = ' . $db->Quote('dbbackup') . ' AND folder = ' . $db->Quote('zoombie'));
            $db->setQuery($query);
            $params = new JRegistry($db->loadResult());

            $task = trim($params->get('taskfile'));
            $path = JPATH_SITE . DS . 'plugins' . DS . 'zoombie' . DS . 'dbbackup' . DS . 'backup' . DS;

            if (!empty($task)) {
                $token = $this->getToken('filebackup', 'taskfile', $params);
                $tipo='&backuptype=dbbackup';
                $base = '<span class="colleft"><a href="' . JURI::base() . 'index.php?' . $token . '=1'.$tipo.'&file=%s" title="%s" target="_blank">%s</a></span>';
                $token = JUtility::getToken();
                $removeToken = trim($params->get('taskremove'));
                $rebase = '';
                if (!empty($removeToken)) {
                    $cid = JRequest::getVar('extension_id', 0, 'request', 'int');
                    $querycid = '';
                    if ($cid) {
                        $querycid = '&extension_id=' . $cid;
                    }
                    $removeToken = $this->getToken('filebackupremove', 'taskremove', $params);
                    $confirm = JText::_('OK_REMOVE_S');
                    $rebase = '<span class="colright">' .
                            '<a class="btn" href="' . JURI::base() . 'index.php?' . $token . '=1'.$tipo.'&' . $removeToken . '=1' . $querycid . '&%s">' .
                            '%s</a></span>';
                    ;
                }

                foreach ($values as $v) {
                    if (file_exists($path . $v)) {
                        $links .= sprintf($base, $v, $v, $v);
                        if (!empty($rebase)) {
                            $links .= sprintf($rebase, '&file=' . $v, JText::_('REMOVE'));
                        }
                        $links .= '<hr class="clear"/>';
                        $files[] = $v;
                    }
                }
            } else {
                foreach ($values as $v) {
                    if (file_exists($path . $v)) {
                        $files[] = $v;
                    }
                }
                $links .= '<span class="value red">' . JText::_('SECRET_KEY_IS_NOT_REGISTERED') . '</span>';
            }
        } else {
            $links .= '<span class="value red">' . JText::_('THERE_IS_NO_BACKUP_FILE') . '</span>';
        }

        $style = 'span.colleft{float:left;width:200px;line-height:160%;}span.colright{float:right;line-height:160%;}' .
                'span.colright .btn{display:inline;width:90px;padding:2px 5px 2px 5px;border:1px solid #ccc}hr.clear{clear:both;border:none;}';
        $document =  JFactory::getDocument();
        $document->addStyleDeclaration($style);

        $value = '';
        if (count($files) > 0) {
            $value = implode("\n", $files);
        }

        $return = '<span ' . $class . ' id="' . $this->id . '">' . $links . '</span>';

        return $return;
    }

    public static function getToken($prefix, $name, $params = null) {
        if (is_null($params)) {
            $plugin = JPluginHelper::getPlugin('zoombie', 'dbbackup');
            $params = new JRegistry($plugin->params);
        }

        $token = trim($params->get($name));
        $config =  JFactory::getConfig();
        $email = $config->getValue('config.mailfrom');
        $db = $config->getValue('config.db');

        return JFilterOutput::stringURLSafe(md5($prefix . $email . $db . $token));
    }

    public static function getBackupFiles() {
        jimport('joomla.filesystem.folder');

        $path = JPATH_SITE . DS . 'plugins' . DS . 'zoombie' . DS . 'dbbackup' . DS . 'backup';



        $files = JFolder::files($path, '\.tar|\.bz2|\.gz|\.zip');

        if (!is_array($files) || count($files) < 1) {
            return false;
        }

        $temp = array();
        foreach ($files as $file) {
            $temp[$file] = intval(@filemtime($path . DS . $file));
        }

        arsort($temp);
        return array_flip($temp);
    }

}
