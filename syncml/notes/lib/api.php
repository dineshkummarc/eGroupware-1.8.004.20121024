<?php
/**
 * eGroupWare - SyncML
 *
 * SyncML Infolog eGroupWare Datastore API for Horde
 *
 * @link http://www.egroupware.org
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @package syncml
 * @subpackage infolog
 * @author Lars Kneschke <lkneschke@egroupware.org>
 * @author Ralf Becker <RalfBecker-AT-outdoor-training.de>
 * @author Joerg Lehrke <jlehrke@noc.de>
 * @version $Id: api.php 32196 2010-09-18 12:42:14Z ralfbecker $
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
 * Returns an array of GUIDs for all notes that the current user is
 * authorized to see.
 *
 * @param string  $filter     The filter expression the client provided.
 *
 * @return array  An array of GUIDs for all notes the user can access.
 */
function _egwnotessync_list($filter='')
{
	$guids = array();
	$boInfolog = new infolog_bo();

	if (is_array($GLOBALS['egw_info']['user']['preferences']['syncml']) &&
		array_key_exists('note_filter', $GLOBALS['egw_info']['user']['preferences']['syncml']))
	{
		$infolog_filter = $GLOBALS['egw_info']['user']['preferences']['syncml']['note_filter'];
		// Horde::logMessage('SymcML: egwtaskssync filter=' . $filter,
		// __FILE__, __LINE__, PEAR_LOG_DEBUG);
	}
	else
	{
		$infolog_filter = 'my';
	}

	$searchFilter = array(
		'order'		=> 'info_datemodified',
		'sort'		=> 'DESC',
		'filter'    => $infolog_filter,
		'col_filter'	=> array(
			'info_type'	=> 'note',
		),
		'col'		=> array('info_id'),
	);

	$notes =& $boInfolog->search($searchFilter);

	Horde::logMessage('SymcML: egwnotessync list found: ' . count($notes),
		__FILE__, __LINE__, PEAR_LOG_DEBUG);

	foreach ((array)$notes as $note)
	{
		$guids[] = 'infolog_note-'.$note['info_id'];
	}
	return $guids;
}

/**
 * Returns an array of GUIDs for notes that have had $action happen
 * since $timestamp.
 *
 * @param string  $action     The action to check for - add, modify, or delete.
 * @param integer $timestamp  The time to start the search.
 * @param string  $type       The type of the content.
 * @param string  $filter     The filter expression the client provided.
 *
 * @return array  An array of GUIDs matching the action and time criteria.
 */
function &_egwnotessync_listBy($action, $timestamp, $type, $filter)
{
	// Horde::logMessage("SymcML: egwtaskssync listBy action: $action timestamp: $timestamp filter: $filter",
	//	__FILE__, __LINE__, PEAR_LOG_DEBUG);
	$state	=& $_SESSION['SyncML.state'];

	$allReadAbleItems = (array)_egwnotessync_list($filter);
	#Horde::logMessage('SymcML: egwnotessync listBy $allReadAbleItems: '. count($allReadAbleItems), __FILE__, __LINE__, PEAR_LOG_DEBUG);
	$allClientItems = (array)$state->getClientItems($type);
	#Horde::logMessage('SymcML: egwnotessync listBy $allClientItems: '. count($allClientItems), __FILE__, __LINE__, PEAR_LOG_DEBUG);
	
	switch ($action) {
		case 'delete' :
			// filters may have changed, so we need to calculate which
			// items are to delete from client because they are not longer in the list.
			$allChangedItems = $state->getHistory('infolog_note', $action, $timestamp, $allClientItems);
			return array_unique($allChangedItems + array_diff($allClientItems, $allReadAbleItems));

		case 'add' :
			// - added items may not need to be added, cause they are filtered out.
			// - filters or entries may have changed, so that more entries
			//   pass the filter and need to be added on the client.
			$allChangedItems = $state->getHistory('infolog_note', $action, $timestamp, $allReadAbleItems);
			return array_unique($allChangedItems + array_diff($allReadAbleItems, $allClientItems));

		case 'modify' :
			// - modified entries, which not (longer) pass filters must not be send.
			// - modified entries which are not at the client must not be send, cause
			//   the 'add' run will send them!
			$allChangedItems = $state->getHistory('infolog_note', $action, $timestamp, $allClientItems);
			return $allChangedItems;

		default:
			return new PEAR_Error("$action is not defined!");
	}
}

/**
 * Import a memo represented in the specified contentType.
 *
 * @param string $content      The content of the memo.
 * @param string $contentType  What format is the data in? Currently supports:
 *                             text/plain
 *                             text/x-vnote
 * @param string $guid         (optional) The guid of a collision entry.
 *
 * @return string  The new GUID, or false on failure.
 */
function _egwnotessync_import($content, $contentType, $guid = null)
{
	Horde::logMessage("SymcML: egwnotessync import content: $content contenttype: $contentType", __FILE__, __LINE__, PEAR_LOG_DEBUG);

	if (is_array($contentType)) {
		$contentType = $contentType['ContentType'];
	}

	$boInfolog = new infolog_bo();
	$noteId = -1; // default for new entry

	if (isset($GLOBALS['egw_info']['user']['preferences']['syncml']['infolog_conflict_category'])) {
		if (!$guid) {
			$guid = _egwnotessync_search($content, $contentType, null, null);
		}
		if (preg_match('/infolog_note-(\d+)/', $guid, $matches)) {
			Horde::logMessage("SymcML: egwnotessync import conflict found for " . $matches[1], __FILE__, __LINE__, PEAR_LOG_DEBUG);
			// We found a conflicting entry on the server, let's make it a duplicate
			if (($conflict =& $boInfolog->read($matches[1]))) {
				$conflict['info_cat'] = $GLOBALS['egw_info']['user']['preferences']['syncml']['infolog_conflict_category'];
				if (!empty($conflict['info_uid'])) {
					$conflict['info_uid'] = 'DUP-' . $conflict['info_uid'];
				}
				$boInfolog->write($conflict);
			}
		}
	}

	$state =& $_SESSION['SyncML.state'];
	$deviceInfo = $state->getClientDeviceInfo();

	if (isset($deviceInfo['charset']) &&
			$deviceInfo['charset']) {
		$charset = $deviceInfo['charset'];
	} else {
		$charset = null;
	}

	switch ($contentType)
	{
		case 'text/plain':
		case 'text/x-vnote':
			$infolog_ical = new infolog_ical();
			$infolog_ical->setSupportedFields($deviceInfo['manufacturer'], $deviceInfo['model']);
			$noteId = $infolog_ical->importVNOTE($content, $contentType, $noteId, $charset);
			break;

		case 'text/x-s4j-sifc':
		case 'text/x-s4j-sife':
		case 'text/x-s4j-sift':
			Horde::logMessage("SyncML: egwnotessync import treating bad task content-type '$contentType' as if is was 'text/x-s4j-sifn'", __FILE__, __LINE__, PEAR_LOG_DEBUG);
		case 'text/x-s4j-sifn':
			$infolog_sif	= new infolog_sif();
			$infolog_sif->setSupportedFields($deviceInfo['model'], $deviceInfo['softwareVersion']);
			$noteId = $infolog_sif->addSIF($content, $noteId, 'note');
			break;

		default:
			return PEAR::raiseError(_("Unsupported Content-Type."));
	}

	if (is_a($noteId, 'PEAR_Error'))
	{
		return $noteId;
	}

	if(!$noteId || $noteId == -1) {
  		return false;
  	}

	$guid = 'infolog_note-' . $noteId;
	Horde::logMessage("SymcML: egwnotessync imported: $guid",
			__FILE__, __LINE__, PEAR_LOG_DEBUG);
	return $guid;
}

/**
 * Search a memo represented in the specified contentType.
 * used for SlowSync to check / rebuild content_map.
 *
 * @param string $content      The content of the memo.
 * @param string $contentType  What format is the data in? Currently supports:
 *                             text/plain
 *                             text/x-vnote
 * @param string $contentid    the contentid read from contentmap we are expecting the content to be
 * @param string $type         The type of the content.
 *
 * @return string  The new GUID, or false on failure.
 */
function _egwnotessync_search($content, $contentType, $contentid, $type=null)
{
	if (is_array($contentType)) {
		$contentType = $contentType['ContentType'];
	}

	Horde::logMessage("SymcML: egwnotessync search content: $content contenttype: $contentType contentid: $contentid",
		__FILE__, __LINE__, PEAR_LOG_DEBUG);

	$state =& $_SESSION['SyncML.state'];
	$deviceInfo = $state->getClientDeviceInfo();

	if (isset($deviceInfo['charset']) &&
			$deviceInfo['charset']) {
		$charset = $deviceInfo['charset'];
	} else {
		$charset = null;
	}

	$relax = !$type;
	$noteId = false;

	switch ($contentType)
	{
		case 'text/x-vnote':
		case 'text/plain':
			$infolog_ical = new infolog_ical();
			$infolog_ical->setSupportedFields($deviceInfo['manufacturer'], $deviceInfo['model']);
			$foundEntries	= $infolog_ical->searchVNOTE($content, $contentType, $state->get_egwID($contentid), $relax, $charset);
			break;

		case 'text/x-s4j-sifc':
		case 'text/x-s4j-sife':
		case 'text/x-s4j-sift':
			Horde::logMessage("SyncML: egwnotessync search treating bad task content-type '$contentType' as if is was 'text/x-s4j-sifn'",
				__FILE__, __LINE__, PEAR_LOG_DEBUG);
		case 'text/x-s4j-sifn':
			$infolog_sif	= new infolog_sif();
			$infolog_sif->setSupportedFields($deviceInfo['model'], $deviceInfo['softwareVersion']);
			$foundEntries = $infolog_sif->searchSIF($content,'note', $state->get_egwID($contentid), $relax);
			break;

		default:
			return PEAR::raiseError(_("Unsupported Content-Type."));
	}

	foreach ($foundEntries as $noteId)
	{
		$noteId = 'infolog_note-' . $noteId;
		if ($contentid == $noteId) break;
		if (!$type) break; // we use the first match
		if (!$state->getLocID($type, $noteId)) break;
		$noteId = false;
	}

	if ($noteId)
	{
		Horde::logMessage('SymcML: egwnotessync search found: ' .
			$noteId, __FILE__, __LINE__, PEAR_LOG_DEBUG);
	}
	return $noteId;
}

/**
 * Export a memo, identified by GUID, in the requested contentType.
 *
 * @param string $guid         Identify the memo to export.
 * @param mixed  $contentType  What format should the data be in?
 *                             Either a string with one of:
 *                              'text/plain'
 *                              'text/x-vnote'
 *                             or an array with options:
 *                             'ContentType':  as above
 *                             'Properties': the client properties
 *
 * @return string  The requested data.
 */
function _egwnotessync_export($guid, $contentType)
{
	Horde::logMessage("SymcML: egwnotessync export guid: $guid contenttype: ".$contentType, __FILE__, __LINE__, PEAR_LOG_DEBUG);

	$state =& $_SESSION['SyncML.state'];
	$deviceInfo = $state->getClientDeviceInfo();

	if (isset($deviceInfo['charset']) &&
			$deviceInfo['charset']) {
		$charset = $deviceInfo['charset'];
	} else {
		$charset = 'UTF-8';
	}

	if (is_array($contentType)) {
		if (is_array($contentType['Properties'])) {
			$clientProperties =& $contentType['Properties'];
		} else {
			$clientProperties = array();
		}
		$contentType = $contentType['ContentType'];
	} else {
		$clientProperties = array();
	}

	$noteId = $state->get_egwId($guid);

	switch ($contentType) {
		case 'text/x-vnote':
		case 'text/plain':
			$infolog_ical = new infolog_ical($clientProperties);
			$infolog_ical->setSupportedFields($deviceInfo['manufacturer'], $deviceInfo['model']);
			return $infolog_ical->exportVNOTE($noteId, $contentType, $charset);

		case 'text/x-s4j-sifc':
		case 'text/x-s4j-sife':
		case 'text/x-s4j-sift':
			Horde::logMessage("SyncML: egwnotessync export treating bad task content-type '$contentType' as if is was 'text/x-s4j-sifn'", __FILE__, __LINE__, PEAR_LOG_DEBUG);
		case 'text/x-s4j-sifn':
			$infolog_sif = new infolog_sif();
			$infolog_sif->setSupportedFields($deviceInfo['model'], $deviceInfo['softwareVersion']);
			if($note = $infolog_sif->getSIF($noteId, 'note')) return $note;

			return PEAR::raiseError(_("Access Denied"));

		default:
			return PEAR::raiseError(_("Unsupported Content-Type."));
	}
}

/**
 * Delete a memo identified by GUID.
 *
 * @param string | array $guid  Identify the note to delete, either a
 *                              single GUID or an array.
 *
 * @return boolean  Success or failure.
 */
function _egwnotessync_delete($guid)
{
	$state =& $_SESSION['SyncML.state'];
	// Handle an arrray of GUIDs for convenience of deleting multiple
	// notes at once.
	if (is_array($guid)) {
		foreach ($guid as $g) {
			$result = _egwnotessync_delete($g);
			if (is_a($result, 'PEAR_Error')) {
				return $result;
			}
		}
		return true;
	}

	$boInfolog = new infolog_bo();

	return $boInfolog->delete($state->get_egwId($guid));
}

/**
 * Replace the memo identified by GUID with the content represented in
 * the specified contentType.
 *
 * @param string $guid         Idenfity the memo to replace.
 * @param string $content      The content of the memo.
 * @param string $contentType  What format is the data in? Currently supports:
 *                             text/plain
 *                             text/x-vnote
 * @param string  $type        The type of the content.
 * @param boolean $merge       merge data instead of replace
 *
 * @return boolean  Success or failure.
 */
function _egwnotessync_replace($guid, $content, $contentType, $type, $merge=false)
{
	Horde::logMessage("SymcML: egwtaskssync replace content: $content contenttype: $contentType", __FILE__, __LINE__, PEAR_LOG_DEBUG);

	$state =& $_SESSION['SyncML.state'];

	if (is_array($contentType)) {
		$contentType = $contentType['ContentType'];
	}

	$noteId = $state->get_egwId($guid);
	$deviceInfo = $state->getClientDeviceInfo();

	if (isset($deviceInfo['charset']) &&
			$deviceInfo['charset']) {
		$charset = $deviceInfo['charset'];
	} else {
		$charset = null;
	}

	switch ($contentType) {
		case 'text/plain':
		case 'text/x-vnote':
			$infolog_ical = new infolog_ical();
			$infolog_ical->setSupportedFields($deviceInfo['manufacturer'], $deviceInfo['model']);
			return $infolog_ical->importVNOTE($content, $contentType, $noteId, $merge, $charset);

		case 'text/x-s4j-sifc':
		case 'text/x-s4j-sife':
		case 'text/x-s4j-sift':
			Horde::logMessage("SyncML: egwnotessync replace treating bad task content-type '$contentType' as if is was 'text/x-s4j-sifn'", __FILE__, __LINE__, PEAR_LOG_DEBUG);
		case 'text/x-s4j-sifn':
			$infolog_sif = new infolog_sif();
			$infolog_sif->setSupportedFields($deviceInfo['model'], $deviceInfo['softwareVersion']);
			return $infolog_sif->addSIF($content, $noteId, 'note', $merge);

		default:
			return PEAR::raiseError(_("Unsupported Content-Type."));
	}
}
