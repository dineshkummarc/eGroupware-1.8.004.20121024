<?php
/**
 * eGroupWare - SyncML
 *
 * SyncML Addressbook eGroupWare Datastore API for Horde
 *
 * @link http://www.egroupware.org
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @package syncml
 * @subpackage addressbook
 * @author Lars Kneschke <lkneschke@egroupware.org>
 * @author Ralf Becker <RalfBecker-AT-outdoor-training.de>
 * @author Joerg Lehrke <jlehrke@noc.de>
 * @version $Id: api.php 39888 2012-07-22 11:08:54Z jlehrke $
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
function _egwcontactssync_list($filter='')
{
	$soAddressbook = new addressbook_so();

	#Horde::logMessage("SymcML: egwcontactssync list ", __FILE__, __LINE__, PEAR_LOG_DEBUG);

	// hardcode your search criteria here
	$criteria = array();
	$allOnList = array();

	$filter = array();
	if ($GLOBALS['egw_info']['user']['preferences']['addressbook']['hide_accounts'])
	{
		$filter['account_id'] = null;
	}

	$search_addressbook = true;

	if (is_array($GLOBALS['egw_info']['user']['preferences']['syncml']))
	{
		$preferences = $GLOBALS['egw_info']['user']['preferences']['syncml'];
		if (isset($preferences['filter_list']) && (int)$preferences['filter_list'])
		{
			$filter['list'] = (int)$preferences['filter_list'];
			$allOnList = $soAddressbook->search($criteria,true,'','','',false,'AND',false,$filter);
			//Horde::logMessage('SymcML: egwcontactssync list() list='. $filter['list'] . ', ' . array2string($allOnList), __FILE__, __LINE__, PEAR_LOG_DEBUG);
			unset($filter['list']);
		}

		if (isset($preferences['filter_addressbook']))
		{
			switch ($preferences['filter_addressbook'])
			{
				case 'G':
					$filter['owner'] = $GLOBALS['egw_info']['user']['account_primary_group'];
					break;
				case 'P':
					$filter['owner'] = $GLOBALS['egw_info']['user']['account_id'];
					break;
				case 'N':
					$search_addressbook = false;
					break;
				case '0':
					break;
				default:
					$filter['owner'] = (int)$preferences['filter_addressbook'];
			}
			//Horde::logMessage('SymcML: egwcontactssync list() owner='. $filter['owner'], __FILE__, __LINE__, PEAR_LOG_DEBUG);
		}
	}
	if ($search_addressbook)
	{
		$allContacts = $soAddressbook->search($criteria,true,'','','',false,'AND',false,$filter);
		//Horde::logMessage('SymcML: egwcontactssync list() owner='. $filter['owner'] . ', ' . array2string($allContacts), __FILE__, __LINE__, PEAR_LOG_DEBUG);
	}

	if (!is_array($allContacts)) $allContacts = array();
	if (!empty($allOnList))	$allContacts = array_merge($allContacts, $allOnList);

	$guids = array();
	foreach ($allContacts as $contact)
	{
		if (!empty($contact['contact_id']))
		{
			$guids[] = 'contacts-' . $contact['contact_id'];
		}
		elseif (!empty($contact['id']))
		{
			$guids[] = 'contacts-' . $contact['id'];
		}
	}

	$guids = array_unique($guids);

	Horde::logMessage('SymcML: egwcontactssync list found: '. count($guids),
	__FILE__, __LINE__, PEAR_LOG_DEBUG);

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
function &_egwcontactssync_listBy($action, $timestamp, $type, $filter='') {
	// Horde::logMessage("SymcML: egwcontactssync listBy action: $action timestamp: $timestamp filter: $filter",
	//	__FILE__, __LINE__, PEAR_LOG_DEBUG);
	$state =& $_SESSION['SyncML.state'];

	$allReadAbleItems = (array)_egwcontactssync_list($filter);
	#Horde::logMessage('SymcML: egwcontactssync listBy $allReadAbleItems: '. count($allReadAbleItems), __FILE__, __LINE__, PEAR_LOG_DEBUG);
	$allClientItems = (array)$state->getClientItems($type);
	#Horde::logMessage('SymcML: egwcontactssync listBy $allClientItems: '. count($allClientItems), __FILE__, __LINE__, PEAR_LOG_DEBUG);

	switch ($action) {
		case 'delete' :
			// filters may have changed, so we need to calculate which
			// items are to delete from client because they are not longer in the list.
			$allChangedItems = (array)$state->getHistory('contacts', $action, $timestamp, $allClientItems);
			return array_unique($allChangedItems + array_diff($allClientItems, $allReadAbleItems));

		case 'add' :
			// - added items may not need to be added, cause they are filtered out.
			// - filters or entries may have changed, so that more entries
			//   pass the filter and need to be added on the client.
			$allChangedItems = (array)$state->getHistory('contacts', $action, $timestamp, $allReadAbleItems);
			return array_unique($allChangedItems + array_diff($allReadAbleItems, $allClientItems));

		case 'modify' :
			// - modified entries, which not (longer) pass filters must not be send.
			// - modified entries which are not at the client must not be send, cause
			//   the 'add' run will send them!
			$allChangedItems = (array)$state->getHistory('contacts', $action, $timestamp, $allClientItems);
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
function _egwcontactssync_import($content, $contentType, $guid = null)
{
	//error_log("_egwcontactssync_import");
	#error_log("SymcML: egwcontactssync import content: ".base64_decode($ccontent)." contentType: $contentType");
	#Horde::logMessage("SymcML: egwcontactssync import content: $content contenttype:\n" . print_r($contentType,true), __FILE__, __LINE__, PEAR_LOG_DEBUG);

	if (is_array($contentType)) {
		$contentType = $contentType['ContentType'];
	}

	$state		= &$_SESSION['SyncML.state'];
	$deviceInfo = $state->getClientDeviceInfo();

	if (isset($deviceInfo['charset']) &&
	$deviceInfo['charset']) {
		$charset = $deviceInfo['charset'];
	} else {
		$charset = null;
	}

	$contactId = null; //default for new entry
	$boAddressbook = new addressbook_bo();

	if (isset($GLOBALS['egw_info']['user']['preferences']['syncml']['addressbook_conflict_category'])) {
		if (!$guid) {
			$guid = _egwcontactssync_search($content, $contentType, null, null);
		}
		if (preg_match('/contacts-(\d+)/', $guid, $matches)) {
			Horde::logMessage("SymcML: egwcontactssync import conflict found for " . $matches[1], __FILE__, __LINE__, PEAR_LOG_DEBUG);
			// We found a conflicting entry on the server, let's make it a duplicate
			if ($conflict = $boAddressbook->read($matches[1])) {
				$cat_ids = explode(",", $conflict['cat_id']);   //existing categories
				$conflict_cat = $GLOBALS['egw_info']['user']['preferences']['syncml']['addressbook_conflict_category'];
				if (!in_array($conflict_cat, $cat_ids)) {
					$cat_ids[] = $conflict_cat;
					$conflict['cat_id'] = implode(",", $cat_ids);
				}
				if (!empty($conflict['uid'])) {
					$conflict['uid'] = 'DUP-' . $conflict['uid'];
				}
				$boAddressbook->save($conflict);
			}
		}
	}

	switch ($contentType) {
		case 'text/x-vcard':
		case 'text/vcard':
			$vcaladdressbook = new addressbook_vcal();
			setSupportedFields($vcaladdressbook);
			$contactId = $vcaladdressbook->addVCard($content, $contactId, $charset);
			break;

		case 'text/x-s4j-sife':
		case 'text/x-s4j-sift':
		case 'text/x-s4j-sifn':
			error_log("[_egwcontactssync_import] Treating bad contact content-type '".$contentType."' as if is was 'text/x-s4j-sifc'");
		case 'text/x-s4j-sifc':
			$sifaddressbook	= new addressbook_sif();
			$contactId = $sifaddressbook->addSIF($content, $contactId);
			break;

		default:
			return PEAR::raiseError(_("Unsupported Content-Type."));
	}

	if (!empty($GLOBALS['egw_info']['user']['preferences']['syncml']['filter_list']))
	{
		$vcaladdressbook->add2list($contactId, $GLOBALS['egw_info']['user']['preferences']['syncml']['filter_list']);
	}

	if(!$contactId) {
		return false;
	}

	$guid = 'contacts-' .$contactId;
	Horde::logMessage("SymcML: egwcontactssync imported: $guid",
	__FILE__, __LINE__, PEAR_LOG_DEBUG);
	return $guid;
}

/**
 * Search a memo represented in the specified contentType,
 * used for SlowSync to check / rebuild content_map.
 *
 * @param string  $content      The content of the memo.
 * @param string  $contentType  What format is the data in? Currently supports:
 *                               text/plain
 *                               text/x-vnote
 * @param string  $contentid    the contentid read from contentmap we are expecting the content to be
 * @param string  $type         The type of the content.
 *
 *
 * @return string  The new GUID, or false on failure.
 */
function _egwcontactssync_search($content, $contentType, $contentid, $type=null)
{
	#Horde::logMessage("SymcML: egwcontactssync search content: $content contentid: $contentid contenttype:\n" . print_r($contentType, true), __FILE__, __LINE__, PEAR_LOG_DEBUG);

	$state			= &$_SESSION['SyncML.state'];
	$deviceInfo = $state->getClientDeviceInfo();

	if (isset($deviceInfo['charset']) &&
	$deviceInfo['charset']) {
		$charset = $deviceInfo['charset'];
	} else {
		$charset = null;
	}
	$relax = !$type;

	if (is_array($contentType)) {
		$contentType = $contentType['ContentType'];
	}

	switch ($contentType) {
		case 'text/x-vcard':
		case 'text/vcard':
			$vcaladdressbook = new addressbook_vcal();
			$vcaladdressbook->setSupportedFields($deviceInfo['manufacturer'],$deviceInfo['model']);
			$foundEntries = $vcaladdressbook->search($content, $state->get_egwID($contentid), $relax, $charset);
			break;

		case 'text/x-s4j-sife':
		case 'text/x-s4j-sift':
		case 'text/x-s4j-sifn':
			#Horde::logMessage("SymcML: egwcontactssync search content: Treating bad contact content-type '$contentType' as if it was 'text/x-s4j-sifc'", __FILE__, __LINE__, PEAR_LOG_DEBUG);
		case 'text/x-s4j-sifc':
			$sifaddressbook	= new addressbook_sif();
			$foundEntries = $sifaddressbook->search($content, $state->get_egwID($contentid), $relax);
			break;

		default:
			Horde::logMessage("SymcML: egwcontactssync search: unsupported content: $content contentid: $contentid contenttype:\n"
			. print_r($contentType, true), __FILE__, __LINE__, PEAR_LOG_ERROR);
			return PEAR::raiseError(_("Unsupported Content-Type."));
	}

	foreach ($foundEntries as $contactId)
	{
		$contactId = 'contacts-' . $contactId;
		if ($contentid == $contactId) break;
		if (!$type) break; // we use the first match
		if (!$state->getLocID($type, $contactId)) break;
		$contactId = false;
	}

	if ($contactId)
	{
		Horde::logMessage('SymcML: egwcontactssync search found: ' .
		$contactId, __FILE__, __LINE__, PEAR_LOG_DEBUG);
	}
	return $contactId;
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
function _egwcontactssync_export($guid, $contentType)
{
	#Horde::logMessage("SymcML: egwcontactssync export guid: $guid contentType:\n" . print_r($contentType, true), __FILE__, __LINE__, PEAR_LOG_DEBUG);

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

	$state		= &$_SESSION['SyncML.state'];
	$deviceInfo = $state->getClientDeviceInfo();

	if (isset($deviceInfo['charset']) &&
	$deviceInfo['charset']) {
		$charset = $deviceInfo['charset'];
	} else {
		$charset = 'UTF-8';
	}

	$contactID	= $state->get_egwId($guid);

	switch ($contentType)
	{
		case 'text/x-vcard':
		case 'text/vcard':
			$vcaladdressbook = new addressbook_vcal('addressbook', $contentType, $clientProperties);
			setSupportedFields($vcaladdressbook);

			if (($vcard = $vcaladdressbook->getVCard($contactID, $charset))) return $vcard;

			return PEAR::raiseError(_("Access Denied"));

		case 'text/x-s4j-sift':
		case 'text/x-s4j-sife':
		case 'text/x-s4j-sifn':
			#Horde::logMessage("SyncML: egwcontactssync_export Treating bad contact content-type '$contentType' as if is was 'text/x-s4j-sifc'", __FILE__, __LINE__, PEAR_LOG_DEBUG);
			/* fall through */
		case 'text/x-s4j-sifc':
			$sifaddressbook	= new addressbook_sif();
			if (($sifcard = $sifaddressbook->getSIF($contactID))) return $sifcard;

			return PEAR::raiseError(_("Access Denied"));

		default:
			#Horde::logMessage("SymcML: export unsupported", __FILE__, __LINE__, PEAR_LOG_DEBUG);
			return PEAR::raiseError(_("Unsupported Content-Type: $contentType"));
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
function _egwcontactssync_delete($guid)
{
	$state = &$_SESSION['SyncML.state'];
	// Handle an arrray of GUIDs for convenience of deleting multiple
	// contacts at once.
	if (is_array($guid)) {
		foreach ($guid as $g) {
			$result = _egwcontactssync_delete($g);
			if (is_a($result, 'PEAR_Error')) {
				return $result;
			}
		}

		return true;
	}
	Horde::logMessage("SymcML: egwcontactssync delete guid: $guid egwid: ". $state->get_egwId($guid), __FILE__, __LINE__, PEAR_LOG_DEBUG);

	$boAddressbook = new addressbook_bo();

	#if (!array_key_exists($memo['memolist_id'], Mnemo::listNotepads(false, PERMS_DELETE))) {
	#	return PEAR::raiseError(_("Permission Denied"));
	#}

	return $boAddressbook->delete($state->get_egwId($guid));

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
function _egwcontactssync_replace($guid, $content, $contentType, $type, $merge=false)
{

	#Horde::logMessage("SymcML: egwcontactssync replace guid: $guid with content: $content", __FILE__, __LINE__, PEAR_LOG_DEBUG);
	#if (!array_key_exists($memo['memolist_id'], Mnemo::listNotepads(false, PERMS_EDIT))) {
	#	return PEAR::raiseError(_("Permission Denied"));
	#}

	$state		= &$_SESSION['SyncML.state'];

	if (isset($deviceInfo['charset']) &&
	$deviceInfo['charset']) {
		$charset = $deviceInfo['charset'];
	} else {
		$charset = null;
	}

	$contactID	= $state->get_egwId($guid);

	if (is_array($contentType)) {
		$contentType = $contentType['ContentType'];
	}

	switch ($contentType) {
		case 'text/x-vcard':
		case 'text/vcard':
			$vcaladdressbook = new addressbook_vcal();
			setSupportedFields($vcaladdressbook);
			$result = $vcaladdressbook->addVCard($content, $contactID, $merge, $charset);
			return $result;

		case 'text/x-s4j-sife':
		case 'text/x-s4j-sift':
		case 'text/x-s4j-sifn':
			#Horde::logMessage("SymcML: egwcontactssync replace treating bad contact content-type '$contentType' as if is was 'text/x-s4j-sifc'", __FILE__, __LINE__, PEAR_LOG_DEBUG);
		case 'text/x-s4j-sifc':
			#$tmpfname = tempnam('/tmp/sync/contents','sifcontact_');
			#$handle = fopen($tmpfname, "w");
			#fwrite($handle, base64_decode($content));
			#fclose($handle);

			$sifaddressbook		= new addressbook_sif();
			$result = $sifaddressbook->addSIF($content, $contactID, $merge);
			return $result;

		default:
			return PEAR::raiseError(_("Unsupported Content-Type."));
	}
}

/**
 * Adjust the supported fields of the device for a memo.
 *
 * @param object  $content    The content of the memo.
 *
 */
function setSupportedFields($content)
{
	$deviceInfo = $_SESSION['SyncML.state']->getClientDeviceInfo();

	if(!isset($deviceInfo) ||  !is_array($deviceInfo)) return;

	$productManufacturer = strtolower($deviceInfo['manufacturer']);
	$productName = strtolower($deviceInfo['model']);

	//Horde::logMessage('setSupportedFields(' . $productManufacturer . ', ' . $productName .')',
	//	__FILE__, __LINE__, PEAR_LOG_DEBUG);

	$defaultFields[0] = array(	// multisync
			'ADR' 		=> array('','','adr_one_street','adr_one_locality','adr_one_region',
									'adr_one_postalcode','adr_one_countryname'),
			'CATEGORIES' 	=> array('cat_id'),
			'CLASS'		=> array('private'),
			'EMAIL'		=> array('email'),
			'N'			=> array('n_family','n_given','','',''),
			'FN'		=> array('n_fn'),
			'NOTE'		=> array('note'),
			'ORG'		=> array('org_name',''),
			'TEL;CELL'	=> array('tel_cell'),
			'TEL;FAX'	=> array('tel_fax'),
			'TEL;HOME'	=> array('tel_home'),
			'TEL;WORK'	=> array('tel_work'),
			'TITLE'		=> array('title'),
			'UID'       => array('uid'),
	);

	$defaultFields[1] = array(	// all entries, nexthaus corporation, groupdav, ...
				'ADR;WORK'	=> array('','','adr_one_street','adr_one_locality','adr_one_region',
										'adr_one_postalcode','adr_one_countryname'),
				'ADR;HOME'	=> array('','','adr_two_street','adr_two_locality','adr_two_region',
										'adr_two_postalcode','adr_two_countryname'),
				'BDAY'		=> array('bday'),
				'CATEGORIES'	=> array('cat_id'),
				'EMAIL;INTERNET;WORK' => array('email'),
				'EMAIL;INTERNET;HOME' => array('email_home'),
				'N'		=> array('n_family','n_given','n_middle','n_prefix','n_suffix'),
				'FN'		=> array('n_fn'),
				'NOTE'		=> array('note'),
				'ORG'		=> array('org_name','org_unit'),
				'TEL;CELL;WORK'	=> array('tel_cell'),
				'TEL;CELL;HOME'	=> array('tel_cell_private'),
				'TEL;FAX;WORK'	=> array('tel_fax'),
				'TEL;FAX;HOME'	=> array('tel_fax_home'),
				'TEL;HOME'	=> array('tel_home'),
				'TEL;PAGER;WORK' => array('tel_pager'),
				'TEL;WORK'	=> array('tel_work'),
				'TITLE'		=> array('title'),
				'URL;WORK'	=> array('url'),
				'ROLE'		=> array('role'),
				'URL;HOME'	=> array('url_home'),
				'FBURL'		=> array('freebusy_uri'),
				'PHOTO'		=> array('jpegphoto'),
				'UID'       => array('uid'),
	);

	$defaultFields[2] = array(	// sony ericson
			'ADR;HOME' 		=> array('','','adr_one_street','adr_one_locality','adr_one_region',
										'adr_one_postalcode','adr_one_countryname'),
			'BDAY'		=> array('bday'),
			'CATEGORIES' 	=> array('cat_id'),
			'CLASS'		=> array('private'),
			'EMAIL'		=> array('email'),
			'N'		=> array('n_family','n_given','','',''),
			'FN'		=> array('n_fn'),
			'NOTE'		=> array('note'),
			'ORG'		=> array('org_name',''),
			'TEL;CELL;WORK'	=> array('tel_cell'),
			'TEL;FAX;WORK'	=> array('tel_fax'),
			'TEL;HOME'	=> array('tel_home'),
			'TEL;WORK'	=> array('tel_work'),
			'TITLE'		=> array('title'),
			'URL;WORK'	=> array('url'),
			'UID'       => array('uid'),
	);

	$defaultFields[3] = array(	// siemens
				'ADR;WORK'	=> array('','','adr_one_street','adr_one_locality','adr_one_region',
										'adr_one_postalcode','adr_one_countryname'),
				'ADR;HOME'	=> array('','','adr_two_street','adr_two_locality','adr_two_region',
										'adr_two_postalcode','adr_two_countryname'),
				'BDAY'		=> array('bday'),
				'EMAIL;INTERNET;WORK' => array('email'),
				'EMAIL;INTERNET;HOME' => array('email_home'),
				'N'		=> array('n_family','n_given','','',''),
				'FN'		=> array('n_fn'),
				'NOTE'		=> array('note'),
				'ORG'		=> array('org_name'), // only one company field is supported
				'TEL;CELL;WORK'	=> array('tel_cell'),
				'TEL;FAX;WORK'	=> array('tel_fax'),
				'TEL;HOME'	=> array('tel_home'),
				'TEL;PAGER;WORK' => array('tel_pager'),
				'TEL;WORK'	=> array('tel_work'),
				'TITLE'		=> array('title'),
				'URL;WORK'	=> array('url'),
				'UID'       => array('uid'),
	);

	$defaultFields[4] = array(	// nokia 6600
				'ADR;WORK'	=> array('','','adr_one_street','adr_one_locality','adr_one_region',
										'adr_one_postalcode','adr_one_countryname'),
				'ADR;HOME'	=> array('','','adr_two_street','adr_two_locality','adr_two_region',
										'adr_two_postalcode','adr_two_countryname'),
				'BDAY;TYPE=BASIC'		=> array('bday'),
				'EMAIL;INTERNET;WORK' => array('email'),
				'EMAIL;INTERNET;HOME' => array('email_home'),
				'N'		=> array('n_family','n_given','','',''),
				'FN'		=> array('n_fn'),
				'NOTE'		=> array('note'),
				'ORG'		=> array('org_name',''),
				'TEL;CELL;WORK'	=> array('tel_cell'),
				'TEL;CELL;HOME'	=> array('tel_cell_private'),
				'TEL;FAX;WORK'	=> array('tel_fax'),
				'TEL;FAX;HOME'	=> array('tel_fax_home'),
				'TEL;HOME'	=> array('tel_home'),
				'TEL;PAGER;WORK' => array('tel_pager'),
				'TEL;WORK'	=> array('tel_work'),
				'TITLE'		=> array('title'),
				'URL;WORK'	=> array('url'),
				'URL;HOME'	=> array('url_home'),
				'UID'       => array('uid'),
	);

	$defaultFields[5] = array(	// nokia e61
				'ADR;WORK'	=> array('','','adr_one_street','adr_one_locality','adr_one_region',
										'adr_one_postalcode','adr_one_countryname'),
				'ADR;HOME'	=> array('','','adr_two_street','adr_two_locality','adr_two_region',
										'adr_two_postalcode','adr_two_countryname'),
				'BDAY;TYPE=BASIC'		=> array('bday'),
				'EMAIL;INTERNET;WORK' => array('email'),
				'EMAIL;INTERNET;HOME' => array('email_home'),
				'N'		=> array('n_family','n_given','','n_prefix','n_suffix'),
				'FN'		=> array('n_fn'),
				'NOTE'		=> array('note'),
				'ORG'		=> array('org_name',''),
				'TEL;CELL;WORK'	=> array('tel_cell'),
				'TEL;CELL;HOME'	=> array('tel_cell_private'),
				'TEL;FAX;WORK'	=> array('tel_fax'),
				'TEL;FAX;HOME'	=> array('tel_fax_home'),
				'TEL;HOME'	=> array('tel_home'),
				'TEL;PAGER;WORK' => array('tel_pager'),
				'TEL;WORK'	=> array('tel_work'),
				'TITLE'		=> array('title'),
				'URL;WORK'	=> array('url'),
				'URL;HOME'	=> array('url_home'),
				'UID'       => array('uid'),
	);

	$defaultFields[6] = array(	// funambol: fmz-thunderbird-plugin
				'ADR;WORK'      => array('','adr_one_street2','adr_one_street','adr_one_locality','adr_one_region',
											'adr_one_postalcode','adr_one_countryname'),
				'ADR;HOME'      => array('','adr_two_street2','adr_two_street','adr_two_locality','adr_two_region',
											'adr_two_postalcode','adr_two_countryname'),
				'EMAIL'         => array('email'),
				'EMAIL;HOME'    => array('email_home'),
				'N'             => array('n_family','n_given','','',''),
				'FN'		=> array('n_fn'),
				'NOTE'          => array('note'),
				'ORG'           => array('org_name','org_unit'),
				'TEL;CELL'      => array('tel_cell'),
				'TEL;FAX;WORK'  => array('tel_fax'),
				'TEL;HOME;VOICE' => array('tel_home'),
				'TEL;PAGER'     => array('tel_pager'),
				'TEL;VOICE;WORK' => array('tel_work'),
				'TITLE'         => array('title'),
				'URL;WORK'      => array('url'),
				'URL;HOME'		=> array('url_home'),
				'BDAY'			=> array('bday'),
				'NICKNAME'		=> array('label'),
	);

	$defaultFields[7] = array(	// SyncEvolution
		'N'=>		array('n_family','n_given','n_middle','n_prefix','n_suffix'),
		'TITLE'		=> array('title'),
		'ROLE'		=> array('role'),
		'ORG'		=> array('org_name','org_unit','room'),
		'ADR;WORK'	=> array('','adr_one_street2','adr_one_street','adr_one_locality','adr_one_region', 'adr_one_postalcode','adr_one_countryname'),
		'ADR;HOME'	=> array('','adr_two_street2','adr_two_street','adr_two_locality','adr_two_region', 'adr_two_postalcode','adr_two_countryname'),
		'TEL;WORK;VOICE'	=> array('tel_work'),
		'TEL;HOME;VOICE'	=> array('tel_home'),
		'TEL;CELL;WORK'	=> array('tel_cell'),
		'TEL;FAX;WORK'	=> array('tel_fax'),
		'TEL;FAX;HOME'	=> array('tel_fax_home'),
		'TEL;PAGER;WORK' => array('tel_pager'),
		'TEL;CAR'	=> array('tel_car'),
		'TEL;VOICE'	=> array('tel_other'),
		'EMAIL;INTERNET;WORK'	=> array('email'),
		'EMAIL;INTERNET;HOME'	=> array('email_home'),
		'URL;WORK'		=> array('url'),
		'BDAY'		=> array('bday'),
		'CATEGORIES'	=> array('cat_id'),
		'NOTE'		=> array('note'),
		'X-EVOLUTION-ASSISTANT'		=> array('assistent'),
		'PHOTO'		=> array('jpegphoto'),
		'UID'       => array('uid'),
	);

	$defaultFields[8] = array_merge($defaultFields[1],array(	// KDE Addressbook, only changes from all=1
		'ORG' => array('org_name'),
		'X-KADDRESSBOOK-X-Department' => array('org_unit'),
	));

	$defaultFields[9] = array(	// nokia E-series
				'ADR;WORK'	=> array('','adr_one_street2','adr_one_street','adr_one_locality','adr_one_region',
										'adr_one_postalcode','adr_one_countryname'),
				'ADR;HOME'	=> array('','adr_two_street2','adr_two_street','adr_two_locality','adr_two_region',
										'adr_two_postalcode','adr_two_countryname'),
				'BDAY;BASIC'		=> array('bday'),
				'X-CLASS'	=> array('private'),
				'EMAIL;INTERNET;WORK' => array('email'),
				'EMAIL;INTERNET;HOME' => array('email_home'),
				'N'		=> array('n_family','n_given','n_middle','n_prefix','n_suffix'),
				'FN'		=> array('n_fn'),
				'NOTE'		=> array('note'),
				'ORG'		=> array('org_name','org_unit'),
				'TEL;CELL;WORK'	=> array('tel_cell'),
				'TEL;CELL;HOME'	=> array('tel_cell_private'),
				'TEL;FAX;WORK'	=> array('tel_fax'),
				'TEL;FAX;HOME'	=> array('tel_fax_home'),
				'TEL;CAR'	=> array('tel_car'),
				'TEL;VOICE;WORK' => array('tel_work'),
				'TEL;VOICE;HOME' => array('tel_home'),
				'TEL;VOICE'		=> array('tel_other'),
				'TEL;PAGER' => array('tel_pager'),
				'TITLE'		=> array('title'),
				'URL;WORK'	=> array('url'),
				'URL;HOME'	=> array('url_home'),
				'X-ASSISTANT'		=> array('assistent'),
				'X-ASSISTANT-TEL'	=> array('tel_assistent'),
				'PHOTO'		=> array('jpegphoto'),
				'UID'       => array('uid'),
	);

	$defaultFields[10] = array(	// nokia 9300
				'ADR;WORK'	=> array('','','adr_one_street','adr_one_locality','adr_one_region',
										'adr_one_postalcode','adr_one_countryname'),
				'ADR;HOME'	=> array('','','adr_two_street','adr_two_locality','adr_two_region',
										'adr_two_postalcode','adr_two_countryname'),
				'BDAY'		=> array('bday'),
				'EMAIL;INTERNET' => array('email'),
				'N'		=> array('n_family','n_given','n_middle','n_prefix','n_suffix'),
				'FN'		=> array('n_fn'),
				'NOTE'		=> array('note'),
				'ORG'		=> array('org_name','org_unit'),
				'TEL;CELL'	=> array('tel_cell'),
				'TEL;WORK;FAX'	=> array('tel_fax'),
				'TEL;FAX'	=> array('tel_fax_home'),
				'TEL;PAGER' => array('tel_pager'),
				'TEL;WORK;VOICE' => array('tel_work'),
				'TEL;HOME;VOICE' => array('tel_home'),
				'TITLE'		=> array('contact_role'),
				'URL'	=> array('url'),
				'UID'       => array('uid'),
	);

	$defaultFields[11] = array(	// funambol: wm pocket pc
				'ADR;WORK'      => array('','','adr_one_street','adr_one_locality','adr_one_region',
											'adr_one_postalcode','adr_one_countryname'),
				'ADR;HOME'      => array('','','adr_two_street','adr_two_locality','adr_two_region',
											'adr_two_postalcode','adr_two_countryname'),
				'BDAY'		=> array('bday'),
				'CATEGORIES'	=> array('cat_id'),
				'EMAIL;INTERNET'		=> array('email'),
				'EMAIL;INTERNET;HOME'	=> array('email_home'),
	// EMAIL;INTERNET;WORK is used by Funambol for the third email address
				'N'		=> array('n_family','n_given','n_middle','n_prefix','n_suffix'),
				'FN'		=> array('n_fn'),
				'NOTE'          => array('note'),
				'ORG'           => array('org_name','org_unit'),
				'TEL;CELL'      => array('tel_cell'),
				'TEL;FAX;HOME'  => array('tel_fax_home'),
				'TEL;FAX;WORK'  => array('tel_fax'),
				'TEL;VOICE;HOME' => array('tel_home'),
				'TEL;VOICE;WORK' => array('tel_work'),
				'TEL;PAGER'     => array('tel_pager'),
				'TEL;CAR'	=> array('tel_car'),
				'TITLE'         => array('title'),
				'URL;WORK'      => array('url'),
				'URL;HOME'	=> array('url_home'),
				'PHOTO'		=> array('jpegphoto'),
				'NICKNAME'		=> array('label'),
	);

	$defaultFields[12] = array(	// Synthesis 4 iPhone
				'ADR;WORK'	=> array('','adr_one_street2','adr_one_street','adr_one_locality','adr_one_region',
										'adr_one_postalcode','adr_one_countryname'),
				'ADR;HOME'	=> array('','adr_two_street2','adr_two_street','adr_two_locality','adr_two_region',
										'adr_two_postalcode','adr_two_countryname'),
				'BDAY'		=> array('bday'),
				'CATEGORIES'	=> array('cat_id'),
				'EMAIL;WORK;INTERNET' => array('email'),
				'EMAIL;HOME;INTERNET' => array('email_home'),
				'N'		=> array('n_family','n_given','n_middle','n_prefix','n_suffix'),
				'FN'		=> array('n_fn'),
				'NOTE'		=> array('note'),
				'ORG'		=> array('org_name','org_unit'),
				'TEL;VOICE;CELL'	=> array('tel_cell'),
				'TEL;X-CustomLabel-iPhone'	=> array('tel_cell_private'),
				'TEL;WORK;FAX'		=> array('tel_fax'),
				'TEL;HOME;FAX'		=> array('tel_fax_home'),
				'TEL;WORK;VOICE'	=> array('tel_work'),
				'TEL;HOME;VOICE'	=> array('tel_home'),
				'TEL;PAGER'		=> array('tel_pager'),
				'TEL;X-CustomLabel-car'	=> array('tel_car'),
				'TITLE'		=> array('title'),
				'URL;WORK'	=> array('url'),
				'ROLE'		=> array('role'),
				'URL;HOME'	=> array('url_home'),
				'PHOTO'		=> array('jpegphoto'),
	);

	$defaultFields[13] = array(	// sonyericsson
				'ADR;WORK'	=> array('','','adr_one_street','adr_one_locality','adr_one_region',
										'adr_one_postalcode','adr_one_countryname'),
				'ADR;HOME'	=> array('','','adr_two_street','adr_two_locality','adr_two_region',
										'adr_two_postalcode','adr_two_countryname'),
				'BDAY'		=> array('bday'),
				'EMAIL;WORK'	=> array('email'),
				'EMAIL;HOME'	=> array('email_home'),
				'N'		=> array('n_family','n_given','n_middle','n_prefix','n_suffix'),
				'NOTE'		=> array('note'),
				'ORG'		=> array('org_name',''),
				'TEL;CELL;WORK'	=> array('tel_cell'),
				'TEL;CELL;HOME'	=> array('tel_cell_private'),
				'TEL;FAX'	=> array('tel_fax'),
				'TEL;HOME'	=> array('tel_home'),
				'TEL;WORK'	=> array('tel_work'),
				'TITLE'		=> array('title'),
				'URL'		=> array('url'),
				'UID'       => array('uid'),
	//'PHOTO'		=> array('jpegphoto'),
	);

	$defaultFields[14] = array(	// Funambol Outlook Sync Client
				'ADR;WORK'      => array('','','adr_one_street','adr_one_locality','adr_one_region',
											'adr_one_postalcode','adr_one_countryname'),
				'ADR;HOME'      => array('','','adr_two_street','adr_two_locality','adr_two_region',
											'adr_two_postalcode','adr_two_countryname'),
				'BDAY'		=> array('bday'),
				'CATEGORIES'	=> array('cat_id'),
				'EMAIL;INTERNET'         => array('email'),
				'EMAIL;INTERNET;HOME'    => array('email_home'),
				'N'		=> array('n_family','n_given','n_middle','n_prefix','n_suffix'),
				'FN'			=> array('n_fn'),
				'NOTE'          => array('note'),
				'ORG'           => array('org_name','org_unit','room'),
				'ROLE'			=> array('role'),
				'CLASS'			=> array('private'),
				'NICKNAME'		=> array('label'),
				'TEL;CELL'      => array('tel_cell'),
				'TEL;HOME;FAX'  => array('tel_fax_home'),
				'TEL;WORK;FAX'  => array('tel_fax'),
				'TEL;VOICE;HOME' => array('tel_home'),
				'TEL;VOICE;WORK' => array('tel_work'),
				'TEL;PAGER'     => array('tel_pager'),
				'TEL;CAR;VOICE'	=> array('tel_car'),
				'TITLE'         => array('title'),
				'URL'      		=> array('url'),
				'URL;HOME'		=> array('url_home'),
				'PHOTO'			=> array('jpegphoto'),
	);

	$defaultFields[15] = array(     // motorola U9
				'ADR;WORK'      		=> array('','','adr_one_street','adr_one_locality','adr_one_region',
													'adr_one_postalcode','adr_one_countryname'),
				'ADR;HOME'      		=> array('','','adr_two_street','adr_two_locality','adr_two_region',
													'adr_two_postalcode','adr_two_countryname'),
				'BDAY;TYPE=BASIC'     	=> array('bday'),
				'EMAIL;INTERNET;WORK' 	=> array('email'),
				'EMAIL;INTERNET;HOME' 	=> array('email_home'),
				'N'             		=> array('n_family','n_given','','',''),
				'FN'            		=> array('n_fn'),
				'NOTE'          		=> array('note'),
				'ORG'           		=> array('org_name',''),
				'TEL;CELL;WORK' 		=> array('tel_cell'),
				'TEL;CELL;HOME' 		=> array('tel_cell_private'),
				'TEL;CELL' 				=> array('tel_car'),
				'TEL;FAX;WORK'  		=> array('tel_fax'),
				'TEL;FAX;HOME'  		=> array('tel_fax_home'),
				'TEL;HOME'      		=> array('tel_home'),
				'TEL;PAGER;WORK' 		=> array('tel_pager'),
				'TEL;WORK'      		=> array('tel_work'),
				'TITLE'         		=> array('title'),
				'URL;WORK'      		=> array('url'),
				'URL;HOME'      		=> array('url_home'),
				'UID'       			=> array('uid'),
	);

	$defaultFields[16] = array(	// funambol: iphone, blackberry, android
				'ADR;WORK'      => array('','','adr_one_street','adr_one_locality','adr_one_region',
											'adr_one_postalcode','adr_one_countryname'),
				'ADR;HOME'      => array('','','adr_two_street','adr_two_locality','adr_two_region',
											'adr_two_postalcode','adr_two_countryname'),
				'BDAY'		=> array('bday'),
				'CATEGORIES'	=> array('cat_id'),
				'EMAIL;INTERNET;WORK'	=> array('email'),
				'EMAIL;INTERNET;HOME'	=> array('email_home'),
				'N'		=> array('n_family','n_given','n_middle','n_prefix','n_suffix'),
				'FN'		=> array('n_fn'),
				'NOTE'          => array('note'),
				'ORG'           => array('org_name','org_unit'),
				'TEL;CELL'      => array('tel_cell'),
				'TEL;FAX;HOME'  => array('tel_fax_home'),
				'TEL;FAX;WORK'  => array('tel_fax'),
				'TEL;VOICE;HOME' => array('tel_home'),
				'TEL;VOICE;WORK' => array('tel_work'),
				'TEL;PAGER'     => array('tel_pager'),
				'TEL;CAR'	=> array('tel_car'),
				'TITLE'         => array('title'),
				'URL;WORK'      => array('url'),
				'URL;HOME'	=> array('url_home'),
				'PHOTO'		=> array('jpegphoto'),
				'NICKNAME'		=> array('label'),
	);

	$defaultFields[17] = array(  // funambol: windows sync client (10.x)
		        'ADR;WORK'      => array('','','adr_one_street','adr_one_locality','adr_one_region',
		        							'adr_one_postalcode','adr_one_countryname'),
		        'ADR;HOME'      => array('','','adr_two_street','adr_two_locality','adr_two_region',
		        							'adr_two_postalcode','adr_two_countryname'),
		        'BDAY'          => array('bday'),
		        'CATEGORIES'    => array('cat_id'),
		        'EMAIL'         => array('email'),
		        'EMAIL;HOME'    => array('email_home'),
		        'N'             => array('n_family','n_given','n_middle','n_prefix','n_suffix'),
		        'FN'            => array('n_fn'),
		        'NOTE'          => array('note'),
		        'ORG'           => array('org_name','org_unit','room'),
		        'ROLE'          => array('role'),
		        'CLASS'         => array('private'),
		        'NICKNAME'      => array('label'),
		        'TEL;CELL'      => array('tel_cell'),
		        'TEL;FAX;WORK'  => array('tel_fax'),
		        'TEL;HOME;FAX'  => array('tel_fax_home'),
		        'TEL;HOME;VOICE' => array('tel_home'),
		        'TEL;PAGER'     => array('tel_pager'),
		        'TEL;OTHER'     => array('tel_other'),
		        'TEL;VOICE;WORK' => array('tel_work'),
		        'TITLE'         => array('title'),
		        'URL'           => array('url'),
		        'URL;HOME'      => array('url_home'),
		        'PHOTO'         => array('jpegphoto'),
	);

	switch ($productManufacturer)
	{
		case 'funambol':
		case 'funambol inc.':
			switch ($productName)
			{
				case 'thunderbird':
				case 'mozilla plugin':
				case 'mozilla sync client':
					$supportedFields = $defaultFields[6];
					break;

				case 'pocket pc sync client':
				case 'pocket pc plug-in':
					$supportedFields = $defaultFields[11];
					break;
				case 'blackberry plug-in':
				case 'iphone plug-in':
				case 'iphone sync client':
				case 'android sync client':
					$supportedFields = $defaultFields[16];
					break;

				case 'outlook sync client v.':
					$supportedFields = $defaultFields[14];
					break;
						
	     		case 'windows sync client v.':
					$supportedFields = $defaultFields[17];
					break;

				default:
					error_log('Funambol product "' . $deviceInfo['model'] . '", assuming same as Thunderbird');
					$supportedFields = $defaultFields[6];
					break;
			}
			break;

		case 'nexthaus corporation':
		case 'nexthaus corp':
			switch ($productName)
			{
				case 'syncje outlook edition':
					$supportedFields = $defaultFields[1];
					break;
				default:
					error_log('Nexthaus product "'. $deviceInfo['model'] . '", assuming same as "syncje outlook"');
					$supportedFields = $defaultFields[1];
					break;
			}
			break;

		case 'nokia':
			switch ($productName)
			{
				case 'e61':
					$supportedFields = $defaultFields[5];
					break;
				case 'e51':
				case 'e66':
				case 'e90':
				case 'e71':
				case 'e72-1':
				case 'e75-1':
				case 'n95':
				case 'n97':
				case 'n97 mini':
				case '5800 xpressmusic':
					$supportedFields = $defaultFields[9];
					break;
				case '9300':
					$supportedFields = $defaultFields[10];
					break;
				case '6600':
					$supportedFields = $defaultFields[4];
					break;
				case 'nokia 6131':
					$supportedFields = $defaultFields[4];
					break;
				default:
					if ($productName[0] == 'e')
					{
						$model = 'E90';
						$supportedFields = $defaultFields[9];
					}
					else
					{
						$model = '6600';
						$supportedFields = $defaultFields[4];
					}
					error_log('Unknown Nokia phone "' . $deviceInfo['model'] . '", assuming same as "'. $model . '"');
						
					break;
			}
			break;


			// multisync does not provide anymore information then the manufacturer
			// we suppose multisync with evolution
		case 'the multisync project':
			switch ($productName)
			{
				default:
					$supportedFields = $defaultFields[0];
					break;
			}
			break;
				
		case 'samsung':
			switch ($productName)
			{
				case 'GT-S5830':
					$supportedFields = $defaultFields[16];
					break;
				default:
					error_log('Unknown Samsung device "'. $deviceInfo['model'] . '", assuming same as "GT-S5830"');
					$supportedFields = $defaultFields[16];
					break;
			}
			break;

		case 'siemens':
			switch ($productName)
			{
				case 'sx1':
					$supportedFields = $defaultFields[3];
					break;
				default:
					error_log('Unknown Siemens phone "'. $deviceInfo['model'] . '", assuming same as "SX1"');
					$supportedFields = $defaultFields[3];
					break;
			}
			break;

		case 'sonyericsson':
		case 'sony ericsson':
			switch ($productName)
			{
				case 'p910i':
				case 'd750i':
					$supportedFields = $defaultFields[2];
					break;
				case 'w760i':
				case 'w890i':
					$supportedFields = $defaultFields[13];
					break;
				default:
					if ($productName[0] == 'w')
					{
						error_log('unknown Sony Ericsson phone "' . $deviceInfo['model'] . '", assuming same as "W760i"');
						$supportedFields = $defaultFields[13];
					}
					else
					{
						error_log('unknown Sony Ericsson phone "' . $deviceInfo['model'] . '", assuming same as "D750i"');
						$supportedFields = $defaultFields[2];
					}
					break;
			}
			break;

		case 'synthesis ag':
			switch ($productName)
			{
				case 'sysync client pocketpc pro':
				case 'sysync client pocketpc std':
					$supportedFields = $defaultFields[1];
					$supportedFields['TEL;CELL;CAR;VOICE'] = array('tel_car');
					break;
				case 'sysync client iphone contacts':
				case 'sysync client iphone contacts+todoz':
					$supportedFields = $defaultFields[12];
					break;
				default:
					error_log('Synthesis connector "' . $deviceInfo['model'] . '", using default fields');
					$supportedFields = $defaultFields[0];
					break;
			}
			break;

		case 'patrick ohly':	// SyncEvolution
			$supportedFields = $defaultFields[7];
			break;

		case 'motorola':
			switch ($productName)
			{
				case 'u9':
					$supportedFields = $defaultFields[15];
					break;
				default:
					error_log('Unknown Motorola phone "' . $deviceInfo['model'] .'", assuming same as "U9"');
					$supportedFields = $defaultFields[15];
					break;
			}
			break;

			// the fallback for SyncML
		default:
			error_log(__FILE__ . __METHOD__ ."\nClient not found:'" . $deviceInfo['manufacturer'] . "' '" . $deviceInfo['model'] . "'");
			$supportedFields = $defaultFields[0];
			break;
	}
	$content->setSupportedFields($productManufacturer, $productName, $supportedFields);
}


