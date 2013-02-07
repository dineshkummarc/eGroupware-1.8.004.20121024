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
 * @version $Id: api.php 32945 2010-11-10 10:28:00Z ralfbecker $
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
 * Returns an array of GUIDs for all events that the current user is
 * authorized to see.
 *
 * @param string $_startDate='' only events after $_startDate or two year back, if empty
 * @param string $_endDate='' only events util $_endDate or two year ahead, if empty
 * @param string  $filter     The filter expression the client provided.
 *
 * @return array  An array of GUIDs for all events the user can access.
 */
function _egwcalendarsync_list($filter='')
{
	Horde::logMessage("SymcML: egwcalendarsync list filter: $filter",
		__FILE__, __LINE__, PEAR_LOG_DEBUG);

	$guids = array();

	$vcal = new Horde_iCalendar;
	$boCalendar = new calendar_bo();
	
	$syncCriteria = $GLOBALS['egw_info']['user']['preferences']['syncml']['calendar_filter'];
	if (empty($syncCriteria)) $syncCriteria = 'all';

	$calendarOwner = $GLOBALS['egw_info']['user']['account_id'];

	if (isset($GLOBALS['egw_info']['user']['preferences']['syncml']['calendar_owner']))
	{
		$owner = $GLOBALS['egw_info']['user']['preferences']['syncml']['calendar_owner'];
		switch ($owner)
		{
			case 'G':
			case -1:
				$calendarOwner = $GLOBALS['egw_info']['user']['account_primary_group'];
			break;
			case 'P':
				$calendarOwner = $GLOBALS['egw_info']['user']['account_id'];
			break;
			default:
				$calendarOwner = array();
				foreach ($boCalendar->list_cals() as $grant)
				{
					if (!$owner || $grant['grantor'] == $owner)
					{
						$calendarOwner[] = $grant['grantor'];
					}
				}
		}
	}

	Horde::logMessage("SymcML: egwcalendarsync calendar owner:" . array2string($calendarOwner),
		__FILE__, __LINE__, PEAR_LOG_DEBUG);

	$now = time();

	if (preg_match('/SINCE.*;([0-9TZ]*).*AND;BEFORE.*;([0-9TZ]*)/i', $filter, $matches)) {
		$cstartDate	= $vcal->_parseDateTime($matches[1]);
		$cendDate	= $vcal->_parseDateTime($matches[2]);
	} else {
		$cstartDate	= 0;
		$cendDate = 0;
	}
	if (isset($GLOBALS['egw_info']['user']['preferences']['syncml']['calendar_past'])) {
		$period = (int)$GLOBALS['egw_info']['user']['preferences']['syncml']['calendar_past'];
		$startDate	= $now - $period;
		$startDate = ($startDate > $cstartDate ? $startDate : $cstartDate);
	} else {
		$startDate	= ($cstartDate ? $cstartDate : ($now - 2678400)); // 31 days back
	}
	if (isset($GLOBALS['egw_info']['user']['preferences']['syncml']['calendar_future'])) {
		$period = (int)$GLOBALS['egw_info']['user']['preferences']['syncml']['calendar_future'];
		$endDate	= $now + $period;
		$endDate	= ($cendDate && $cendDate < $endDate ? $cendDate : $endDate);
	} else {
		$endDate	= ($cendDate ? $cendDate : ($now + 65000000)); // 2 years from now
	}

	Horde::logMessage('SymcML: egwcalendarsync list startDate: ' . date('r', $startDate) .
		', endDate: ' . date('r', $endDate), __FILE__, __LINE__, PEAR_LOG_DEBUG);

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

	$searchFilter = array
	(
		'start'		=> date('Ymd', $startDate),
		'end'		=> date('Ymd', $endDate),
		'filter'	=> $syncCriteria,
		'users'		=> $calendarOwner,
		'daywise'	=> false,
		'enum_recuring' => false,
		'enum_groups' => true,
		'cols'		=> array('egw_cal.cal_id', 'cal_start',	'recur_type'),
		'order'     => 'cal_id,cal_start ASC',
	);

	$events =& $boCalendar->search($searchFilter);

	$id = false;
	foreach ($events as $event)
	{
		if ($id == $event['cal_id']) continue;
		$id = $event['cal_id'];
		$guids[] = $guid = 'calendar-' . $event['cal_id'];
		if ($event['recur_type'] != MCAL_RECUR_NONE)
		{
			// Check if the stati for all participants are identical for all recurrences
			$event = $boCalendar->read($id, 0, true, 'server');
			$days = $boCalendar->so->get_recurrence_exceptions($event, $tzid, $startDate, $endDate, $syncCriteria);
			unset($event);
			foreach ($days as $recur_date)
			{
				if ($recur_date) $guids[] = $guid . ':' . $recur_date;
			}
		}
	}
	Horde::logMessage('SymcML: egwcalendarsync list found: ' . count($guids),
		__FILE__, __LINE__, PEAR_LOG_DEBUG);
	return $guids;
}

/**
 * Returns an array of related pseudo exception GUIDs for given events
 * since $timestamp.
 *
 * @param array of GUIDs
 *
 * @return array	An array of related pseudo exception GUIDs
 */
function &_egwcalendarsync_listPseudoExceptions($eventGUIDs, $tzid=null)
{
	$pseudoGUIDs = array();

	$boCalendar = new calendar_bo();

	// We may have changed timezone pseudo exceptions
	foreach ($eventGUIDs as $guid) {
		if (preg_match('/calendar-(\d+)$/', $guid, $matches) &&
				($event = $boCalendar->read($matches[1], 0, true, 'server')) &&
				$event['recur_type'] != MCAL_RECUR_NONE)  {
			// we check only series masters
			$pseudoExceptions = $boCalendar->so->get_recurrence_exceptions($event, $tzid);
			unset($event);
			foreach ($pseudoExceptions as $recur_date)
			{
				if ($recur_date) $pseudoGUIDs[] = $guid . ':' . $recur_date;
			}
		}
	}
	return $pseudoGUIDs;
}
/**
 * Returns an array of GUIDs for events that have had $action happen
 * since $timestamp.
 *
 * @param string  $action     The action to check for - add, modify, or delete.
 * @param integer $timestamp  The time to start the search.
 * @param string  $type       The type of the content.
 * @param string  $filter     The filter expression the client provided.
 *
 * @return array  An array of GUIDs matching the action and time criteria.
 */
function &_egwcalendarsync_listBy($action, $timestamp, $type, $filter='')
{
	// Horde::logMessage("SymcML: egwcalendarsync listBy action: $action timestamp: $timestamp filter: $filter",
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

	$allReadAbleItems = (array)_egwcalendarsync_list($filter);
	#Horde::logMessage('SymcML: egwcalendarsync listBy $allReadAbleItems: '. count($allReadAbleItems), __FILE__, __LINE__, PEAR_LOG_DEBUG);
	$allClientItems = $state->getClientItems($type);
	#Horde::logMessage('SymcML: egwcalendarsync listBy $allClientItems: '. count($allClientItems), __FILE__, __LINE__, PEAR_LOG_DEBUG);
	
	switch ($action) {
		case 'delete' :
			// filters may have changed, so we need to calculate which
			// items are to delete from client because they are not longer in the list.
			$allChangedItems = $state->getHistory('calendar', $action, $timestamp, $allClientItems);
			$allChangedPseudoExceptions =& _egwcalendarsync_listPseudoExceptions($allChangedItems, $tzid);
			$allChangedItems = array_merge($allChangedItems, $allChangedPseudoExceptions);
			#Horde::logMessage('SymcML: egwcalendarsync listBy $allChangedItems: '. count($allChangedItems), __FILE__, __LINE__, PEAR_LOG_DEBUG);
			$guids = array_unique($allChangedItems + array_diff($allClientItems, $allReadAbleItems));
			break;

		case 'add' :
			// - added items may not need to be added, cause they are filtered out.
			// - filters or entries may have changed, so that more entries
			//   pass the filter and need to be added on the client.
			$allChangedItems = $state->getHistory('calendar', $action, $timestamp, $allReadAbleItems);
			$allChangedPseudoExceptions =& _egwcalendarsync_listPseudoExceptions($allChangedItems, $tzid);
			$allChangedItems = array_merge($allChangedItems, $allChangedPseudoExceptions);
			#Horde::logMessage('SymcML: egwcalendarsync listBy $allChangedItems: '. count($allChangedItems), __FILE__, __LINE__, PEAR_LOG_DEBUG);
			$guids = array_unique($allChangedItems + array_diff($allReadAbleItems, $allClientItems));
			break;

		case 'modify' :
			// - modified entries, which not (longer) pass filters must not be send.
			// - modified entries which are not at the client must not be send, cause
			//   the 'add' run will send them!
			$allChangedItems = $state->getHistory('calendar', $action, $timestamp, $allClientItems);
			$allChangedPseudoExceptions =& _egwcalendarsync_listPseudoExceptions($allChangedItems, $tzid);
			$allChangedItems = array_merge($allChangedItems, $allChangedPseudoExceptions);
			#Horde::logMessage('SymcML: egwcalendarsync listBy $allChangedItems: '. count($allChangedItems), __FILE__, __LINE__, PEAR_LOG_DEBUG);
			$guids = $allChangedItems;
			break;

		default:
			return new PEAR_Error("$action is not defined!");
	}
	usort($guids,"_calendarsync_guid_sort");
	return $guids;
}

/**
 * Sort GUIDs be calendar-id and recurrence
 *
 * @param string $a calendar-123[:456], 123=calendar-id, 456=recurrence
 * @param string $b
 * @return int see usort
 */
function _calendarsync_guid_sort($a,$b)
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
 * Import an event represented in the specified contentType.
 *
 * @param string $content      The content of the event.
 * @param string $contentType  What format is the data in? Currently supports:
 *                             text/calendar
 *                             text/x-vcalendar
 *                             text/x-s4j-sife
 * @param string $guid         (optional) The guid of a collision entry.
 *
 * @return string  The new GUID, or false on failure.
 */
function _egwcalendarsync_import($content, $contentType, $guid = null)
{
	Horde::logMessage("SymcML: egwcalendarsync import content: $content contenttype: $contentType", __FILE__, __LINE__, PEAR_LOG_DEBUG);

	#$syncProfile	= _egwcalendarsync_getSyncProfile();

	if (is_array($contentType)) {
		$contentType = $contentType['ContentType'];
	}

	$calendarId = -1; // default for new entry

	if (isset($GLOBALS['egw_info']['user']['preferences']['syncml']['calendar_conflict_category'])) {
		if (!$guid) {
			$guid = _egwcalendarsync_search($content, $contentType, null, null);
		}
		if (preg_match('/calendar-(\d+)(:(\d+))?/', $guid, $matches))
		{
			$boCalendar = new calendar_boupdate();
			// We found a matching entry. Are we allowed to change it?
			if ($boCalendar->check_perms(EGW_ACL_EDIT, $matches[1]))
			{
				// We found a conflicting entry on the server, let's make it a duplicate
				Horde::logMessage("SymcML: egwcalendarsync import conflict found for " . $matches[1], __FILE__, __LINE__, PEAR_LOG_DEBUG);
				if (($conflict =& $boCalendar->read($matches[1])))
				{
					$cat_ids = explode(",", $conflict['category']);   //existing categories
					$conflict_cat = $GLOBALS['egw_info']['user']['preferences']['syncml']['calendar_conflict_category'];
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

	switch ($contentType)
	{
		case 'text/x-vcalendar':
		case 'text/vcalendar':
		case 'text/calendar':
			$boical	= new calendar_ical();
			$boical->setSupportedFields($deviceInfo['manufacturer'],$deviceInfo['model']);
			$calendarId = $boical->importVCal($content, $calendarId, null, false, 0, '', null, $charset);
			break;

		case 'text/x-s4j-sifc':
		case 'text/x-s4j-sift':
		case 'text/x-s4j-sifn':
			Horde::logMessage("SyncML: egwcalendarsync import treating bad calendar content-type '$contentType' as if is was 'text/x-s4j-sife'", __FILE__, __LINE__, PEAR_LOG_DEBUG);
		case 'text/x-s4j-sife':
			$sifcalendar = new calendar_sif();
			$sifcalendar->setSupportedFields($deviceInfo['model'],$deviceInfo['softwareVersion']);
			$calendarId = $sifcalendar->addSIF($content, $calendarId);
			break;

		default:
			return PEAR::raiseError(_("Unsupported Content-Type."));
	}

	if (preg_match('/(\d+):(\d+)/', $calendarId, $matches)) {
		// We have created a pseudo exception; date it back to this session
		$guid = 'calendar-' . $matches[1];
		$ts = $state->getSyncTSforAction($guid, 'modify');
		if (!$ts)
		{
			$ts = $state->getSyncTSforAction($guid, 'add');
		}
		$GLOBALS['egw']->contenthistory->updateTimeStamp('calendar', $calendarId, 'modify', $ts);
	}

	if(!$calendarId) {
  		return false;
  	}

	$guid = 'calendar-' . $calendarId;
	Horde::logMessage("SymcML: egwcalendarsync imported: $guid",
		__FILE__, __LINE__, PEAR_LOG_DEBUG);
	return $guid;
}

/**
 * Search an event represented in the specified contentType.
 * used for SlowSync to check / rebuild content_map.
 *
 * @param string $content      The content of the event.
 * @param string $contentType  What format is the data in? Currently supports:
 *                             text/calendar
 *                             text/x-vcalendar
 *                             text/x-s4j-sife
 * @param string $contentid    the contentid read from contentmap we are expecting the content to be
 * @param string $type         The type of the content.
 *
 * @return string  The new GUID, or false on failure.
 */
function _egwcalendarsync_search($content, $contentType, $contentid, $type=null)
{
	Horde::logMessage("SymcML: egwcalendarsync search content: $content contenttype: $contentType contentid: $contentid", __FILE__, __LINE__, PEAR_LOG_DEBUG);

	$state =& $_SESSION['SyncML.state'];
	$deviceInfo = $state->getClientDeviceInfo();

	if (isset($deviceInfo['charset']) &&
			$deviceInfo['charset']) {
		$charset = $deviceInfo['charset'];
	} else {
		$charset = null;
	}

	$eventId = false;
	$relax = !$type;

	if (is_array($contentType))
	{
		$contentType = $contentType['ContentType'];
	}

	// We want to break the slow-sync duplicates generation. If you have two identical entries
	// on your device before a slow-sync, we match them one after the other.
	switch ($contentType)
	{
		case 'text/x-vcalendar':
		case 'text/vcalendar':
		case 'text/calendar':
			$boical	= new calendar_ical();
			$boical->setSupportedFields($deviceInfo['manufacturer'],$deviceInfo['model']);
			$foundEntries = $boical->search($content, $state->get_egwID($contentid), $relax, $charset);
			break;

		case 'text/x-s4j-sifc':
		case 'text/x-s4j-sift':
		case 'text/x-s4j-sifn':
			Horde::logMessage("SyncML: egwcalendarsync treating bad calendar content-type '$contentType' as if is was 'text/x-s4j-sife'", __FILE__, __LINE__, PEAR_LOG_DEBUG);
		case 'text/x-s4j-sife':
			$sifcalendar = new calendar_sif();
			$sifcalendar->setSupportedFields($deviceInfo['model'],$deviceInfo['softwareVersion']);
			$foundEntries = $sifcalendar->search($content, $state->get_egwID($contentid), $relax);
			break;

		default:
			return PEAR::raiseError(_("Unsupported Content-Type."));
	}

	foreach ($foundEntries as $eventId)
	{
		$eventId = 'calendar-' . $eventId;
		if ($contentid == $eventId) break;
		if (!$type) break; // we use the first match
		if (!$state->getLocID($type, $eventId)) break;
		$eventId = false;
	}

	if ($eventId)
	{
		Horde::logMessage('SymcML: egwcalendarsync search found: ' .
			$eventId, __FILE__, __LINE__, PEAR_LOG_DEBUG);
	}
	return $eventId;
}

/**
 * Export an event, identified by GUID, in the requested contentType.
 *
 * @param string $guid         Identify the memo to export.
 * @param mixed  $contentType  What format should the data be in?
 *                             Either a string with one of:
 *                              'text/calendar'
 *                              'text/x-vcalendar'
 *                              'text/x-s4j-sife'
 *                             or an array with options:
 *                             'ContentType':  as above
 *                             'Properties': the client properties
 *
 * @return string  The requested data.
 */
function _egwcalendarsync_export($guid, $contentType)
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
  	$eventID = $parts[0];
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

	Horde::logMessage("SymcML: egwcalendarsync export guid: $eventID ($recur_date) contenttype:\n"
		. print_r($contentType, true), __FILE__, __LINE__, PEAR_LOG_DEBUG);

	switch ($contentType)
	{
		case 'text/x-vcalendar':
		case 'text/vcalendar':
		case 'text/calendar':
			$boical	= new calendar_ical($clientProperties);
			$boical->setSupportedFields($deviceInfo['manufacturer'],$deviceInfo['model']);
			$vcal_version = ($contentType == 'text/x-vcalendar') ? '1.0' : '2.0';
			$retval = $boical->exportVCal($eventID, $vcal_version, 'PUBLISH', $recur_date, '', $charset);
			break;

		case 'text/x-s4j-sifc':
		case 'text/x-s4j-sift':
		case 'text/x-s4j-sifn':
			Horde::logMessage("SyncML: egwcalendarsync export treating bad calendar content-type '$contentType' as if is was 'text/x-s4j-sife'", __FILE__, __LINE__, PEAR_LOG_DEBUG);
		case 'text/x-s4j-sife':
			$sifcalendar = new calendar_sif();
			$sifcalendar->setSupportedFields($deviceInfo['model'],$deviceInfo['softwareVersion']);
			$retval = $sifcalendar->getSIF($eventID, $recur_date);
			break;

		default:
			$retval = PEAR::raiseError(_("Unsupported Content-Type."));
	}
	if ($retval === -1) $retval = PEAR::raiseError(_("Access denied!"));
	return $retval;
}

/**
 * Delete an event identified by GUID.
 *
 * @param string | array $guid  Identify the event to delete, either a
 *                              single GUID or an array.
 *
 * @return boolean  Success or failure.
 */
function _egwcalendarsync_delete($guid)
{
	// Handle an arrray of GUIDs for convenience of deleting multiple
	// events at once.
	$state =& $_SESSION['SyncML.state'];
	if (is_array($guid))
	{
		foreach ($guid as $g) {
			$result = _egwcalendarsync_delete($g);
			if (is_a($result, 'PEAR_Error')) return $result;
		}
		return true;
	}


	Horde::logMessage("SymcML: egwcalendarsync delete id: ".$state->get_egwId($guid),
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
		// Delete the pseudo exception by propagation
		$event['recur_exception'] = array_unique(array_merge($event['recur_exception'], array($recur_date)));
		$boCalendar->update($event, true);
		return true;

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

/**
 * Replace the event identified by GUID with the content represented in
 * the specified contentType.
 *
 * @param string $guid         Idenfity the memo to replace.
 * @param string $content      The content of the memo.
 * @param string $contentType  What format is the data in? Currently supports:
 *                             text/calendar
 *                             text/x-vcalendar
 *                             text/x-s4j-sife
 * @param string  $type        The type of the content.
 * @param boolean $merge       merge data instead of replace
 *
 * @return boolean  Success or failure.
 */
function _egwcalendarsync_replace($guid, $content, $contentType, $type, $merge=false)
{
	Horde::logMessage("SymcML: egwcalendarsync replace guid: $guid content: $content contenttype: $contentType",
		__FILE__, __LINE__, PEAR_LOG_DEBUG);

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
  	$eventID = $parts[0];
  	$recur_date = (isset($parts[1]) ? $parts[1] : 0);

  	if ($eventID) {
  		// Make sure that we can at least read this entry
  		$boCalendar = new calendar_bo();
  		if (!$boCalendar->check_perms(EGW_ACL_READ, $eventID))
  			return PEAR::raiseError(_("Entry does not exist."));;
  	}

	switch ($contentType)
	{
		case 'text/x-vcalendar':
		case 'text/vcalendar':
		case 'text/calendar':
			$boical	= new calendar_ical();
			$boical->setSupportedFields($deviceInfo['manufacturer'],$deviceInfo['model']);
			$calendarId = $boical->importVCal($content, $eventID, null, $merge, $recur_date, '', null, $charset);
			break;

		case 'text/x-s4j-sifc':
		case 'text/x-s4j-sift':
		case 'text/x-s4j-sifn':
			error_log("[_egwsifcalendarsync_replace] Treating bad calendar content-type '".$contentType."' as if is was 'text/x-s4j-sife'");
		case 'text/x-s4j-sife':
			$sifcalendar = new calendar_sif();
			$sifcalendar->setSupportedFields($deviceInfo['model'],$deviceInfo['softwareVersion']);
			$calendarId = $sifcalendar->addSIF($content, $eventID, $merge, $recur_date);
			break;

		default:
			return PEAR::raiseError(_("Unsupported Content-Type."));
	}

	$pseudoExceptions =& _egwcalendarsync_listPseudoExceptions(array($guid), $tzid);
	$state->mergeChangedItems($type, $pseudoExceptions);

	foreach ($pseudoExceptions as $pseudoGUID)
	{
		//Horde::logMessage("SymcML: egwcalendarsync touch $guid related GUID $pseudoGUID",
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
		Horde::logMessage("SymcML: egwcalendarsync replace propagated guid: $guid to calendar-$calendarId",
			__FILE__, __LINE__, PEAR_LOG_DEBUG);
		// The pseudo exception was propagated to a real exception;
		// we mirror the changes back to the client within the same session this way
		$locid = $state->getLocID($type, $guid);
		$state->addConflictItem($type, $locid);
		$ts = $state->getServerAnchorLast($type) + 1;
		$GLOBALS['egw']->contenthistory->updateTimeStamp('calendar', $eventID, 'modify', $ts);
		$GLOBALS['egw']->contenthistory->updateTimeStamp('calendar', $_id, 'delete', $ts);
		$GLOBALS['egw']->contenthistory->updateTimeStamp('calendar', $calendarId, 'modify', $ts);
	}
	if (strstr($calendarId, ':')) {
		// We have modified a pseudo exception; touch timestamp
		$GLOBALS['egw']->contenthistory->updateTimeStamp('calendar', $calendarId, 'modify', time());
	}
	return $calendarId;

}
