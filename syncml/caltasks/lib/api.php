<?php
/**
 * eGroupWare - SyncML
 *
 * SyncML Calendar eGroupWare Datastore API for Horde
 *
 * @link http://www.egroupware.org
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @package syncml
 * @subpackage calendar
 * @author Lars Kneschke <lkneschke@egroupware.org>
 * @author Ralf Becker <RalfBecker-AT-outdoor-training.de>
 * @author Joerg Lehrke <jlehrke@noc.de>
 * @version $Id$
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
    'args' => array('content', 'contentType', 'id', 'type'),
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
 * Returns an array of GUIDs for all events and tasks that the current user is
 * authorized to see.
 *
 * @param string  $filter     The filter expression the client provided.
 *
 * @return array  An array of GUIDs for all events and tasks the user can access.
 */
function _egwcaltaskssync_list($filter='')
{
	$calendar_items = _egwcalendarsync_list($filter);
	$task_items = _egwtaskssync_list($filter);

	return array_merge($calendar_items, $task_items);
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
function &_egwcaltaskssync_listBy($action, $timestamp, $type, $filter='')
{
	// Horde::logMessage("SymcML: egwcaltaskssync listBy action: $action timestamp: $timestamp filter: $filter",
	//	__FILE__, __LINE__, PEAR_LOG_DEBUG);
	$state	=& $_SESSION['SyncML.state'];
	$tzid = null;
	$deviceInfo = $state->getClientDeviceInfo();
	if (isset($deviceInfo['tzid']) &&
			$deviceInfo['tzid']) {
		switch ($deviceInfo['tzid'])
		{
			case -1:
			case -2:
				$tzid = null;
				break;
			default:
				$tzid = $deviceInfo['tzid'];
		}
	}

	$allReadableCalendarItems = (array)_egwcalendarsync_list($filter);
	$allReadableTaskItems = (array)_egwtaskssync_list($filter);
	$allReadAbleItems = array_merge($allReadableCalendarItems, $allReadableTaskItems);
	$allClientItems = $state->getClientItems($type);
	
	switch ($action) {
		case 'delete' :
			// filters may have changed, so we need to calculate which
			// items are to delete from client because they are not longer in the list.
			$allChangedCalendarItems = $state->getHistory('calendar', $action, $timestamp, $allClientItems);
			$allChangedPseudoExceptions =& _egwcalendarsync_listPseudoExceptions($allChangedCalendarItems, $tzid);
			$allChangedTaskItems = $state->getHistory('infolog_task', $action, $timestamp, $allClientItems);
			$allChangedItems = array_merge($allChangedCalendarItems, $allChangedPseudoExceptions, $allChangedTaskItems);
			$guids = array_unique($allChangedItems + array_diff($allClientItems, $allReadAbleItems));
			break;

		case 'add' :
			// - added items may not need to be added, cause they are filtered out.
			// - filters or entries may have changed, so that more entries
			//   pass the filter and need to be added on the client.
			$allChangedCalendarItems = $state->getHistory('calendar', $action, $timestamp, $allReadableCalendarItems);
			$allChangedPseudoExceptions =& _egwcalendarsync_listPseudoExceptions($allChangedCalendarItems, $tzid);
			$allChangedTaskItems = $state->getHistory('infolog_task', $action, $timestamp, $allReadableTaskItems);
			$allChangedItems = array_merge($allChangedCalendarItems, $allChangedPseudoExceptions, $allChangedTaskItems);
			$guids = array_unique($allChangedItems + array_diff($allReadAbleItems, $allClientItems));
			break;

		case 'modify' :
			// - modified entries, which not (longer) pass filters must not be send.
			// - modified entries which are not at the client must not be send, cause
			//   the 'add' run will send them!
			$allChangedCalendarItems = $state->getHistory('calendar', $action, $timestamp, $allClientItems);
			$allChangedPseudoExceptions =& _egwcalendarsync_listPseudoExceptions($allChangedCalendarItems, $tzid);
			$allChangedTaskItems = $state->getHistory('infolog_task', $action, $timestamp, $allClientItems);
			$allChangedItems = array_merge($allChangedCalendarItems, $allChangedPseudoExceptions, $allChangedTaskItems);	
			$guids = $allChangedItems;
			break;

		default:
			return new PEAR_Error("$action is not defined!");
	}
	usort($guids,"_caltasksync_guid_sort");
	return $guids;
}
/**
 * Sort GUIDs be calendar-id and recurrence
 *
 * @param string $a calendar-123[:456], 123=calendar-id, 456=recurrence
 * @param string $b
 * @return int see usort
 */
function _caltasksync_guid_sort($a,$b)
{
	// remove calendar- prefix;
	list(,$a) = explode('-',$a);
	list(,$b) = explode('-',$b);
	list($a_id,$a_recurrence) = explode(':',$a);
	list($b_id,$b_recurrence) = explode(':',$b);

	if ($a_id == $b_id)
	{
		return $a_recurrence - $b_recurrence;
	}
	return $a_id - $b_id;
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
function _egwcaltaskssync_import($content, $contentType, $guid = null)
{
	Horde::logMessage("SymcML: egwcaltaskssync import content: $content contenttype: $contentType", __FILE__, __LINE__, PEAR_LOG_DEBUG);

	if (is_array($contentType)) {
		$contentType = $contentType['ContentType'];
	}

	$boCalendar = new calendar_boupdate();
	$boInfolog = new infolog_bo();


	$taskID = -1; // default for new entry

	if (isset($GLOBALS['egw_info']['user']['preferences']['syncml']['infolog_conflict_category'])) {
		if (!$guid) {
			$guid = _egwcaltaskssync_search($content, $contentType, null, null);
		}
		if (preg_match('/infolog_task-(\d+)/', $guid, $matches)) {
			Horde::logMessage("SymcML: egwcaltaskssync import conflict found for " . $matches[1],
				__FILE__, __LINE__, PEAR_LOG_DEBUG);
			// We found a conflicting entry on the server, let's make it a duplicate
			if (($conflict =& $boInfolog->read($matches[1]))) {
				$conflict['info_cat'] = $GLOBALS['egw_info']['user']['preferences']['syncml']['infolog_conflict_category'];
				if (!empty($conflict['info_uid'])) {
					$conflict['info_uid'] = 'DUP-' . $conflict['info_uid'];
				}
				// the EGW's item gets a new id, to keep the subtasks attached to the client's entry
				$boInfolog->write($conflict);
			}
		} else if (preg_match('/calendar-(\d+)(:(\d+))?/', $guid, $matches)) {
			Horde::logMessage("SymcML: egwcaltaskssync import conflict found for " . $matches[1],
				__FILE__, __LINE__, PEAR_LOG_DEBUG);
			// We found a matching entry. Are we allowed to change it?
			if ($boCalendar->check_perms(EGW_ACL_EDIT, $matches[1]))
			{
				// We found a conflicting entry on the server, let's make it a duplicate
				if (($conflict =& $boCalendar->read($matches[1])))
				{
					$cat_ids = explode(",", $conflict['category']);   //existing categories
					$conflict_cat = $GLOBALS['egw_info']['user']['preferences']['syncml']['infolog_conflict_category'];
					if (!in_array($conflict_cat, $cat_ids))
					{
						$cat_ids[] = $conflict_cat;
						$conflict['category'] = implode(",", $cat_ids);
					}
					/*
					// Changing the UID would destroy the relationship to exceptions
					if ($conflict['recur_type'] == MCAL_RECUR_NONE &&
						empty($conflict['recurrence']) &&
						!empty($conflict['uid'])) {
						$conflict['uid'] = 'DUP-' . $conflict['uid'];
					}
					*/
					$boCalendar->save($conflict);
				}
			}
			else
			{
				// If the user is not allowed to change this event,
				// he still may update his participaction status
				$calendarId = $matches[1];
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

	switch ($contentType) {
		case 'text/x-vcalendar':
		case 'text/vcalendar':
		case 'text/calendar':
			if(strrpos($content, 'BEGIN:VTODO')) {
				$infolog_ical = new infolog_ical();
				$infolog_ical->setSupportedFields($deviceInfo['manufacturer'],$deviceInfo['model']);
				$taskID = $infolog_ical->importVTODO($content, $taskID, null, $charset);
				$type = 'infolog_task';
			} else {
				$boical	= new calendar_ical();
				$boical->setSupportedFields($deviceInfo['manufacturer'],$deviceInfo['model']);
				$taskID = $boical->importVCal($content, $taskID, null, false, 0, '', null, $charset);
				$type = 'calendar';
			}
			break;

		default:
			return PEAR::raiseError(_("Unsupported Content-Type."));
	}

	if (is_a($taskID, 'PEAR_Error')) return $taskID;

	if(!$taskID || $taskID == -1) return false;

	$guid = $type .'-' . $taskID;
	Horde::logMessage("SymcML: egwcaltaskssync imported: $guid",
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
function _egwcaltaskssync_search($content, $contentType, $contentid, $type=null)
{
	Horde::logMessage("SymcML: egwcaltaskssync search content: $content contenttype: $contentType contentid: $contentid", __FILE__, __LINE__, PEAR_LOG_DEBUG);

	$state =& $_SESSION['SyncML.state'];
	$deviceInfo = $state->getClientDeviceInfo();

	if (isset($deviceInfo['charset']) &&
			$deviceInfo['charset']) {
		$charset = $deviceInfo['charset'];
	} else {
		$charset = null;
	}

	$taskId = false;
	$relax = !$type;

	switch ($contentType) {
		case 'text/x-vcalendar':
		case 'text/vcalendar':
		case 'text/calendar':
			if (strrpos($content, 'BEGIN:VTODO')) {
				$infolog_ical = new infolog_ical();
				$infolog_ical->setSupportedFields($deviceInfo['manufacturer'],$deviceInfo['model']);
				$foundEntries =  $infolog_ical->searchVTODO($content, $state->get_egwID($contentid), $relax, $charset);
				$prefix =  'infolog_task';
			} else {
				$boical	= new calendar_ical();
				$boical->setSupportedFields($deviceInfo['manufacturer'], $deviceInfo['model']);
				$foundEntries	=  $boical->search($content, $state->get_egwID($contentid), $relax, $charset);
				$prefix =  'calendar';
			}
			Horde::logMessage('SymcML: egwcaltaskssync search searched for type: '. $prefix, __FILE__, __LINE__, PEAR_LOG_DEBUG);
			break;

		default:
			return PEAR::raiseError(_("Unsupported Content-Type."));
	}

	foreach ($foundEntries as $taskId)
	{
		$taskId = $prefix . '-' . $taskId;
		if ($contentid == $taskId) break;
		if (!$type) break; // we use the first match
		if (!$state->getLocID($type, $taskId)) break;
		$taskId = false;
	}

	if ($taskId)
	{
		Horde::logMessage('SymcML: egwcaltaskssync search found: ' .
			$taskId, __FILE__, __LINE__, PEAR_LOG_DEBUG);
	}
	return $taskId;
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
function _egwcaltaskssync_export($guid, $contentType)
{
	$state =& $_SESSION['SyncML.state'];
  	$deviceInfo = $state->getClientDeviceInfo();

  	if (isset($deviceInfo['charset']) &&
			$deviceInfo['charset']) {
		$charset = $deviceInfo['charset'];
	} else {
		$charset = 'UTF-8';
	}

  	$_id = $state->get_egwId($guid);
  	$parts = preg_split('/:/', $_id);
  	$taskID = $parts[0];
  	$recur_date = (isset($parts[1]) ? $parts[1] : 0);

	if (is_array($contentType)) {
		if (is_array($contentType['Properties'])) {
			$clientProperties = &$contentType['Properties'];
		} else {
			$clientProperties = array();
		}
		$contentType = $contentType['ContentType'];
	} else {
		$clientProperties = array();
	}


	Horde::logMessage("SymcML: egwcaltaskssync export guid: $guid contenttype: ".$contentType,
		__FILE__, __LINE__, PEAR_LOG_DEBUG);

	if (strrpos($guid, 'infolog_task') !== false) {
		Horde::logMessage("SymcML: egwcaltaskssync export exporting tasks",
			__FILE__, __LINE__, PEAR_LOG_DEBUG);
        $infolog_ical = new infolog_ical($clientProperties);
		$infolog_ical->setSupportedFields($deviceInfo['manufacturer'],$deviceInfo['model']);

		switch ($contentType) {
			case 'text/x-vcalendar':
				return $infolog_ical->exportVTODO($taskID, '1.0', 'PUBLISH', $charset);

			case 'text/vcalendar':
			case 'text/calendar':
				return $infolog_ical->exportVTODO($taskID, '2.0', 'PUBLISH', $charset);

			default:
				return PEAR::raiseError(_("Unsupported Content-Type."));
		}
	} else {
		Horde::logMessage("SymcML: egwcaltaskssync export exporting event",
			__FILE__, __LINE__, PEAR_LOG_DEBUG);
		$boical	= new calendar_ical($clientProperties);
		$boical->setSupportedFields($deviceInfo['manufacturer'],$deviceInfo['model']);

		switch ($contentType) {
			case 'text/x-vcalendar':
				return $boical->exportVCal($taskID,'1.0', 'PUBLISH', $recur_date, '', null, $charset);

			case 'text/vcalendar':
			case 'text/calendar':
				return $boical->exportVCal($taskID,'2.0', 'PUBLISH', $recur_date, '', null, $charset);

			default:
				return PEAR::raiseError(_("Unsupported Content-Type."));
		}
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
function _egwcaltaskssync_delete($guid)
{
	$state = &$_SESSION['SyncML.state'];
	// Handle an arrray of GUIDs for convenience of deleting multiple
	// contacts at once.
	if (is_array($guid)) {
		foreach ($guid as $g) {
			$result = _egwcaltaskssync_delete($g);
			if (is_a($result, 'PEAR_Error')) return $result;
		}
		return true;
	}

	#if (!array_key_exists($memo['memolist_id'], Mnemo::listNotepads(false, PERMS_DELETE))) {
	#	return PEAR::raiseError(_("Permission Denied"));
	#}
	Horde::logMessage("SymcML: egwcaltaskssync delete id: ".$state->get_egwId($guid),
		__FILE__, __LINE__, PEAR_LOG_DEBUG);

	if (strrpos($guid, 'infolog_task') !== false) {
		Horde::logMessage("SymcML: egwcaltaskssync delete deleting task",
			__FILE__, __LINE__, PEAR_LOG_DEBUG);

		$boInfolog = new infolog_bo();
		return $boInfolog->delete($state->get_egwId($guid));
	} else {
		Horde::logMessage("SymcML: egwcaltaskssync delete deleting event",
			__FILE__, __LINE__, PEAR_LOG_DEBUG);

		$boCalendar = new calendar_boupdate();
		$_id = $state->get_egwId($guid);
		$parts = preg_split('/:/', $_id);
		$eventId = $parts[0];
		$recur_date = (isset($parts[1]) ? $parts[1] : 0);
		$user = $GLOBALS['egw_info']['user']['account_id'];

		// Check if the user has at least read access to the event
		if (!($event =& $boCalendar->read($eventId))) return false;

		if (!$boCalendar->check_perms(EGW_ACL_EDIT, $eventId)
				&& isset($event['participants'][$user]))
		{
			if ($recur_date && $event['recur_type'] != MCAL_RECUR_NONE) {
				$boCalendar->set_status($event, $user, $event['participants'][$user], $recur_date);
			} else {
				// user rejects the event by deleting it from his device
				$boCalendar->set_status($eventId, $user, 'R', $recur_date);
			}
			return true;
		}

		if ($recur_date && $event['recur_type'] != MCAL_RECUR_NONE)
		{
			// Delete a "status only" exception of a recurring event
			$participants = $boCalendar->so->get_participants($event['id'], 0);
			foreach ($participants as &$participant)
			{
				if (isset($event['participants'][$participant['uid']]))
				{
					$participant['status'] = $event['participants'][$participant['uid']][0];
				}
				else
				{
					// Will be deleted from this recurrence
					$participant['status'] = 'G';
				}
			}
			foreach ($participants as $attendee)
			{
				// Set participant status back
				$boCalendar->set_status($event, $attendee['uid'], $attendee['status'], $recur_date);
			}
			return true;
		}

		return $boCalendar->delete($eventId);
	}
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
function _egwcaltaskssync_replace($guid, $content, $contentType, $type, $merge=false)
{
	Horde::logMessage("SymcML: egwcaltaskssync replace guid: $guid content: $content contenttype: $contentType", __FILE__, __LINE__, PEAR_LOG_DEBUG);

	if (is_array($contentType))
	{
		$contentType = $contentType['ContentType'];
	}

	$state =& $_SESSION['SyncML.state'];
	$tzid = null;
	$deviceInfo = $state->getClientDeviceInfo();
	if (isset($deviceInfo['tzid']) &&
			$deviceInfo['tzid']) {
		switch ($deviceInfo['tzid'])
		{
			case -1:
			case -2:
				$tzid = null;
				break;
			default:
				$tzid = $deviceInfo['tzid'];
		}
	}
	if (isset($deviceInfo['charset']) &&
			$deviceInfo['charset']) {
		$charset = $deviceInfo['charset'];
	} else {
		$charset = null;
	}

	$_id = $state->get_egwId($guid);
  	$parts = preg_split('/:/', $_id);
  	$taskID = $parts[0];
  	$recur_date = (isset($parts[1]) ? $parts[1] : 0);

	switch ($contentType) {
		case 'text/x-vcalendar':
		case 'text/vcalendar':
		case 'text/calendar':
			if(strrpos($guid, 'infolog_task') !== false) {
				Horde::logMessage("SymcML: egwcaltaskssync replace replacing task",
					__FILE__, __LINE__, PEAR_LOG_DEBUG);
				$infolog_ical = new infolog_ical();
				$infolog_ical->setSupportedFields($deviceInfo['manufacturer'],$deviceInfo['model']);

				return $infolog_ical->importVTODO($content, $taskID, $merge, null, $charset);
			} else {
				Horde::logMessage("SymcML: egwcaltaskssync replace replacing event",
					__FILE__, __LINE__, PEAR_LOG_DEBUG);
				if ($taskID) {
					// Make sure that we can at least read this entry
					$boCalendar = new calendar_bo();
					if (!$boCalendar->check_perms(EGW_ACL_READ, $taskID))
						return PEAR::raiseError(_("Entry does not exist."));;
				}
				$boical	= new calendar_ical();
				$boical->setSupportedFields($deviceInfo['manufacturer'],$deviceInfo['model']);
				$calendarId = $boical->importVCal($content, $taskID, null, $merge, $recur_date, '', null, $charset);
				$pseudoExceptions =& _egwcalendarsync_listPseudoExceptions(array($guid), $tzid);
				$state->mergeChangedItems($type, $pseudoExceptions);

				foreach ($pseudoExceptions as $pseudoGUID)
				{
					//Horde::logMessage("SymcML: egwcaltaskssync touch $guid related GUID $pseudoGUID",
					//	__FILE__, __LINE__, PEAR_LOG_DEBUG);
					$pseudoID = $state->get_egwId($pseudoGUID);
					// all pseudo exceptions are affected, to
					//$ts = $state->getSyncTSforAction($guid, 'modify');
					if (!$state->getSyncTSforAction($guid, 'modify'))
					{
						// Touch all new pseudo entries
						$ts = $state->getServerAnchorLast($type) + 1;
						$GLOBALS['egw']->contenthistory->updateTimeStamp('calendar', $pseudoID, 'modify', $ts);
					}
					//$ts = $state->getSyncTSforAction($guid, 'modify');
					//$state->setUID($type, $state->getLocID($type, $pseudoGUID), $pseudoGUID, $ts);
				}
				if ($recur_date && $_id != $calendarId) {
					Horde::logMessage("SymcML: egwcaltaskssync replace propagated guid: $guid to calendar-$calendarId",
						__FILE__, __LINE__, PEAR_LOG_DEBUG);
					// The pseudo exception was propagated to a real exception;
					// we mirror the changes back to the client within the same session this way
					$locid = $state->getLocID($type, $guid);
					$state->addConflictItem($type, $locid);
					$ts = $state->getServerAnchorLast($type) + 1;
					$GLOBALS['egw']->contenthistory->updateTimeStamp('calendar', $taskID, 'modify', $ts);
					$GLOBALS['egw']->contenthistory->updateTimeStamp('calendar', $_id, 'delete', $ts);
					$GLOBALS['egw']->contenthistory->updateTimeStamp('calendar', $calendarId, 'modify', $ts);
				}
				if (strstr($calendarId, ':')) {
					// We have modified a pseudo exception; touch timestamp
					$GLOBALS['egw']->contenthistory->updateTimeStamp('calendar', $calendarId, 'modify', time());
				}
				return $calendarId;
			}

		default:
			return PEAR::raiseError(_("Unsupported Content-Type."));
	}

}
