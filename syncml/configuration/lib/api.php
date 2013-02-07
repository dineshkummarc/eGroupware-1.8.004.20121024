<?php
/**
 * eGroupWare - SyncML
 *
 * SyncML Funambol Configuration Datastore API for EGroupware/Horde
 *
 * @link http://www.egroupware.org
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @package syncml
 * @subpackage funambol
 * @author Joerg Lehrke <jlehrke@noc.de>
 * @version $Id: api.php 31121 2010-06-26 16:28:37Z jlehrke $
 */

$_services['list'] = array(
    'args' => array('filter'),
    'type' => 'stringArray'
);

$_services['listBy'] = array(
    'args' => array('action', 'timestamp', 'type', 'filter'),
    'type' => 'stringArray'
);

$_services['import'] = array(
    'args' => array('content', 'contentType'),
    'type' => 'integer'
);

$_services['search'] = array(
    'args' => array('content', 'contentType', 'id' , 'type'),
    'type' => 'integer'
);

$_services['export'] = array(
    'args' => array('guid', 'contentType'),
    'type' => 'string'
);

$_services['delete'] = array(
    'args' => array('guid'),
    'type' => 'boolean'
);

$_services['replace'] = array(
    'args' => array('guid', 'content', 'contentType', 'type', 'merge'),
    'type' => 'boolean'
);


/**
 * Returns an array of GUIDs for all configurations that the current user is
 * authorized to see.
 *
 * @param string  $filter     The filter expression the client provided.
 *
 * @return array  An array of GUIDs for all notes the user can access.
 */
function _egwconfigurationsync_list($filter='')
{
	$state	=& $_SESSION['SyncML.state'];
	$allClientItems = (array)$state->getClientItems('configuration');
	return $allClientItems;
}

/**
 * Returns an array of GUIDs for configurations that have had $action happen
 * since $timestamp.
 *
 * @param string  $action     The action to check for - add, modify, or delete.
 * @param integer $timestamp  The time to start the search.
 * @param string  $type       The type of the content.
 * @param string  $filter     The filter expression the client provided.
 *
 * @return array  An array of GUIDs matching the action and time criteria.
 */
function &_egwconfigurationsync_listBy($action, $timestamp, $type, $filter)
{
	switch ($action) {
		case 'delete' :
		case 'add' :
		case 'modify' :
			return array();

		default:
			return new PEAR_Error("$action is not defined!");
	}
}

/**
 * Import a configurations represented in the specified contentType.
 *
 * @param string $content      The content of the configuration.
 * @param string $contentType  What format is the data in? Currently supports:
 *                             text/plain
 * @param string $guid         (optional) The guid of a collision entry.
 *
 * @return string  The new GUID, or false on failure.
 */
function _egwconfigurationsync_import($content, $contentType, $guid = null)
{
	static $added = 0;
	if (is_array($contentType)) {
		$contentType = $contentType['ContentType'];
	}
	
	Horde::logMessage("SymcML: egwconfigurationsync import content: $content contenttype: $contentType", __FILE__, __LINE__, PEAR_LOG_DEBUG);

	$state	=& $_SESSION['SyncML.state'];
	$items = count($state->getClientItems('configuration')) + $added;
	
	switch ($contentType)
	{
		case 'text/plain':
			if (empty($guid)) {
				$guid = "configuration-$items";
				$added++;
			}
			break;

		default:
			return PEAR::raiseError(_("Unsupported Content-Type."));
	}

	Horde::logMessage("SymcML: egwconfigurationsync imported: $guid",
			__FILE__, __LINE__, PEAR_LOG_DEBUG);
	return $guid;
}

/**
 * Search a configuration represented in the specified contentType.
 * used for SlowSync to check / rebuild content_map.
 *
 * @param string $content      The content of the configuration.
 * @param string $contentType  What format is the data in? Currently supports:
 *                             text/plain
 * @param string $contentid    the contentid read from contentmap we are expecting the content to be
 * @param string $type         The type of the content.
 *
 * @return string  The new GUID, or false on failure.
 */
function _egwconfigurationsync_search($content, $contentType, $contentid, $type=null)
{
	if (is_array($contentType)) {
		$contentType = $contentType['ContentType'];
	}

	Horde::logMessage("SymcML: egwconfigurationsync search content: $content contenttype: $contentType contentid: $contentid",
		__FILE__, __LINE__, PEAR_LOG_DEBUG);

	switch ($contentType)
	{
		case 'text/plain':
			break;
			
		default:
			return PEAR::raiseError(_("Unsupported Content-Type."));
	}
	
	$state	=& $_SESSION['SyncML.state'];
	$guid = 'configuration-' . $contentid;
	$allClientItems = (array)$state->getClientItems('configuration');
	if (!in_array($guid, $allClientItems)) $guid = false;
	
	return $guid;
}

/**
 * Export a configuration, identified by GUID, in the requested contentType.
 *
 * @param string $guid         Identify the configuration to export.
 * @param mixed  $contentType  What format should the data be in?
 *                             Either a string with one of:
 *                              'text/plain'
 *                             or an array with options:
 *                             'ContentType':  as above
 *                             'Properties': the client properties
 *
 * @return string  The requested data.
 */
function _egwconfigurationsync_export($guid, $contentType)
{
	if (is_array($contentType)) {
		$contentType = $contentType['ContentType'];
	}
	
	Horde::logMessage("SymcML: egwconfigurationsync export guid: $guid contenttype: ".$contentType, __FILE__, __LINE__, PEAR_LOG_DEBUG);

	switch ($contentType) {
		case 'text/plain':
			break;
			
		default:
			return PEAR::raiseError(_("Unsupported Content-Type."));
	}
	return '0';
}

/**
 * Delete a configuration identified by GUID.
 *
 * @param string | array $guid  Identify the configuration to delete, either a
 *                              single GUID or an array.
 *
 * @return boolean  Success or failure.
 */
function _egwconfigurationsync_delete($guid)
{
	return true;
}

/**
 * Replace the configuration identified by GUID with the content represented in
 * the specified contentType.
 *
 * @param string $guid         Idenfity the configuration to replace.
 * @param string $content      The content of the configuration.
 * @param string $contentType  What format is the data in? Currently supports:
 *                             text/plain
 * @param string  $type        The type of the content.
 * @param boolean $merge       merge data instead of replace
 *
 * @return boolean  Success or failure.
 */
function _egwconfigurationsync_replace($guid, $content, $contentType, $type, $merge=false)
{
	if (is_array($contentType)) {
		$contentType = $contentType['ContentType'];
	}
	
	Horde::logMessage("SymcML: egwconfigurationsync replace content: $content contenttype: $contentType", __FILE__, __LINE__, PEAR_LOG_DEBUG);

	switch ($contentType) {
		case 'text/plain':
			break;

		default:
			return PEAR::raiseError(_("Unsupported Content-Type."));
	}
	return true;
}
