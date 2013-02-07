<?php
/**
 * eGroupWare - SyncML
 *
 * @link http://www.egroupware.org
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @package syncml
 * @subpackage preferences
 * @author Joerg Lehrke <jlehrke@noc.de>
 * @copyright (c) 2009 by Joerg Lehrke <jlehrke@noc.de>
 * @version $Id$
 */

class devices
{

	var $public_functions = array
		(
			'listDevices'		=> 'True',
			'consistencyCheck'	=> 'True',
			'deleteAccount'		=> 'True',
			'viewDeviceDetails'	=> 'True',
		);

	var $t;
	var $charset;
	var $db;
	var $user;
	var $sessionData;

	function devices()
	{
		$this->t = $GLOBALS['egw']->template;
		$this->charset = $GLOBALS['egw']->translation->charset();
		$this->db = $GLOBALS['egw']->db;
		$this->user = $GLOBALS['egw_info']['user']['userid'] . '@' . $GLOBALS['egw_info']['user']['domain'];
		$this->sessionData      = $GLOBALS['egw']->session->appsession('session_data', 'syncml');
	}


	// $_displayNavbar false == don't display navbar
	function display_app_header($_displayNavbar)
	{
		switch ($_GET['menuaction'])
		{
			case 'syncml.devices.listDevices':
			case 'syncml.devices.viewDeviceDetails':
				$GLOBALS['egw']->js->validate_file('tabs','tabs');
				$GLOBALS['egw']->js->validate_file('jscode','viewDeviceData','syncml');
				$GLOBALS['egw']->js->set_onload('javascript:initViewDeviceData();');
				$GLOBALS['egw']->js->set_onload('javascript:initTabs();');
				break;
		}

		$GLOBALS['egw_info']['flags']['include_xajax'] = True;

		$GLOBALS['egw']->common->egw_header();
		if($_displayNavbar == TRUE)	echo parse_navbar();
	}



	function getAllUserDevices()
	{
		$where[] = "egw_syncmldeviceowner.owner_locname = '$this->user'";
		$where[] = "egw_syncmldeviceowner.owner_devid = egw_syncmldevinfo.dev_id";
		$devices = array();
		foreach ($this->db->select('egw_syncmldeviceowner,egw_syncmldevinfo',
			'egw_syncmldevinfo.dev_id,' .
			'egw_syncmldeviceowner.owner_deviceid,' .
			'egw_syncmldevinfo.dev_devicetype,' .
			'egw_syncmldevinfo.dev_manufacturer,' .
			'egw_syncmldevinfo.dev_model,' .
			'egw_syncmldevinfo.dev_swversion', $where,
			__LINE__, __FILE__, 'syncml') as $row)
		{
			$devices[$row['owner_deviceid']] = $row;
		}
		return $devices;
	}
	
	function cleanupDevices()
	{
		$wherer1 = array();
		
		if (!isset($GLOBALS['egw_info']['user']['apps']['admin']))
		{
			$where1['owner_locname'] = $this->user;
		}
		
		foreach ($this->db->select('egw_syncmldeviceowner',
			'owner_devid', $where1,
			__LINE__, __FILE__, 'syncml') as $row)
		{
			$deviceID = $row['owner_devid'];
			$where2['dev_id'] = $deviceID;
			if (!$this->db->select('egw_syncmldevinfo', 'dev_id', $where2,
				__LINE__, __FILE__, 'syncml')->fetch())
			{
				$where1[0] = "owner_devid = '$deviceID'";
				$this->db->delete('egw_syncmldeviceowner', $where1, __LINE__, __FILE__, 'syncml');
				unset($where1[0]);
			}
		}	
	}
	
	/**
	 * Delete account hook
	 *
	 * @param array|int $old_user integer old user or array with keys 
	 'account_id' and 'new_owner' as the deleteaccount hook uses it
	 * @param int $new_user=null
	 */
	static function deleteAccount($old_user, $newuser=null)
	{
		if (is_array($old_user))
		{
			$new_user = $old_user['new_owner'];
			$old_user = $old_user['account_id'];
		}
		if (($user = $GLOBALS['egw']->accounts->id2name($old_user)))
		{
			$where = array();
			$domain = empty($GLOBALS['egw_info']['user']['domain']) ? 'default' : $GLOBALS['egw_info']['user']['domain'];
			$locname = $user . '@' . $domain;
			$where[0] = "owner_locname = '$locname'";
			$GLOBALS['egw']->db->delete('egw_syncmldeviceowner', $where, __LINE__, __FILE__, 'syncml');
			$map_id = $locname . '%';
			$where[0] = "map_id LIKE '$map_id'";
			$GLOBALS['egw']->db->delete('egw_contentmap', $where, __LINE__, __FILE__, 'syncml');
			$where[0] = "dev_id LIKE '$map_id'";
			$GLOBALS['egw']->db->delete('egw_syncmlsummary', $where, __LINE__, __FILE__, 'syncml');
		}	
	}

	function getSyncSummary($deviceID)
	{
		$where[] = "dev_id = '$this->user" . "$deviceID'";
		$dateStores = array();
		foreach ($this->db->select('egw_syncmlsummary', '*', $where,
					__LINE__, __FILE__, 'syncml') as $row)
		{
			$dataStores[$row['sync_path']] = $row;
		}
		return $dataStores;
	}


	function deleteDeviceHistory($deviceID)
	{
		$allDevices = $this->getAllUserDevices();
		if (isset($allDevices[$deviceID]))
		{
			$map_id = $this->user . $deviceID . '%';
			$where[0] = "map_id LIKE '$map_id'";
			$this->db->delete('egw_contentmap', $where, __LINE__, __FILE__, 'syncml');
			$where[0] = "dev_id = '$this->user" . "$deviceID'";
			$this->db->delete('egw_syncmlsummary', $where, __LINE__, __FILE__, 'syncml');
			$where[0] = "owner_deviceid = '$deviceID' AND owner_locname = '$this->user'";
			$this->db->delete('egw_syncmldeviceowner', $where, __LINE__, __FILE__, 'syncml');
		}
		$this->cleanupDevices();
	}


	function deleteDataStoreHistory($dataStore)
	{
		$deviceID = $this->sessionData['deviceID'];
		if (!empty($deviceID))
		{
			$map_id = $this->user . $deviceID . $dataStore;
			$where[0] = "map_id = '$map_id'";
			$this->db->delete('egw_contentmap', $where, __LINE__, __FILE__, 'syncml');
			$dev_id = $this->user . $deviceID;
			$where[0] = "dev_id  = '$dev_id'";
			$where[1] = "sync_path  = '$dataStore'";
			$this->db->delete('egw_syncmlsummary', $where, __LINE__, __FILE__, 'syncml');
		}
		$this->cleanupDevices();
	}


	function listDevices()
	{
		$this->display_app_header(TRUE);

		$this->t->set_file(array("body" => "preferences_list_devices.tpl"));
		$this->t->set_block('body','main');

		$this->translate();

		$this->t->set_var('url_image_delete',$GLOBALS['egw']->common->image('phpgwapi','delete'));

		$this->t->set_var('table', $this->createDeviceDataTable());

		$this->t->pparse("out","main");
	}


	function createDeviceDataTable()
	{
		$allDeviceData   = $this->getAllUserDevices();

		$linkData = array
			(
				'menuaction'    => 'syncml.devices.viewDeviceDetails'
			);
		$urlViewDeviceDetails = $GLOBALS['egw']->link('/index.php', $linkData);
		$tableRows[] = array(
			'1' => '',
			'2' => '',
			'3' => '<b>Device Type</b>',
			'4' => '<b>Device ID (IMEI)</b>',
		);

		if (is_array($allDeviceData) && !empty($allDeviceData))
		{
			foreach ($allDeviceData as $device)
			{
				$description = $device['dev_manufacturer'] . ' ' . $device['dev_model'] . ' ' . $device['dev_swversion'];
				$tableRows[] = array(
					'1'     => $device['dev_id'] != -1 ? html::checkbox('deviceID', false, $device['owner_deviceid']) : '',
							'.1'    => 'style="width:30px"',
							'2'     =>  '<a href="'. $urlViewDeviceDetails ."&deviceID=".$device['owner_deviceid'].'">'. @htmlspecialchars($description, ENT_QUOTES, $this->charset) .'</a>',
							'3'	=> @htmlspecialchars($device['dev_devicetype'], ENT_QUOTES, $this->charset),
							'4'	=> @htmlspecialchars($device['owner_deviceid'], ENT_QUOTES, $this->charset),
				);
			}

			return html::table($tableRows, 'style="width:70%;"');
		}

		return '';
	}


	function viewDeviceDetails()
	{
		if (isset($_GET['deviceID']))
		{
			$deviceID = $_GET['deviceID'];

			$this->sessionData['deviceID'] = $deviceID;
			$GLOBALS['egw']->session->appsession('session_data', 'syncml', $this->sessionData);

			$this->display_app_header(TRUE);

			$allDeviceData   = $this->getAllUserDevices();

			$title = $allDeviceData[$deviceID]['dev_manufacturer'] . ' '
				. $allDeviceData[$deviceID]['dev_model'] . ' '
				. $allDeviceData[$deviceID]['dev_swversion'];



			$this->t->set_file(array("body" => "preferences_device_details.tpl"));
			$this->t->set_block('body', 'main');

			$this->translate();

			$this->t->set_var('device_title', $title);
			$this->t->set_var('url_image_delete', $GLOBALS['egw']->common->image('phpgwapi','delete'));

			$this->t->set_var('table', $this->createDeviceDetailView());

			$this->t->pparse("out", "main");
		}
	}

	function consistencyCheck()
	{
		$this->cleanupDevices();
		$this->display_app_header(TRUE);
		echo('<center>');
		echo('<h1>'.lang('SyncML Table Consistency').'</h1>');
		echo(lang('The consistency of the internal data structures is re-established.'));
		echo('</center>');
	}

	function createDeviceDetailView()
	{
		$deviceHistory   = $this->getSyncSummary($this->sessionData['deviceID']);

		$tableRows[] = array(
			'1' => '',
			'2' => '<b>DataStore Path</b>',
			'3' => '<b>Last Synchronization</b>',
			'4' => '<b>Last Device Achor</b>',
		);

		if (is_array($deviceHistory) && !empty($deviceHistory))
		{
			foreach ($deviceHistory as $dataStore)
			{
				$tableRows[] = array(
					'1'     => $dataStore['sync_path'] != '' ? html::checkbox('dataStore', false, $dataStore['sync_path']) : '',
							'.1'    => 'style="width:30px"',
							'2'     => @htmlspecialchars($dataStore['sync_path'], ENT_QUOTES, $this->charset),
							'3'	=> @htmlspecialchars(date('Y-m-d H:i:s', $dataStore['sync_serverts']), ENT_QUOTES, $this->charset),
							'4'	=> @htmlspecialchars($dataStore['sync_clientts'], ENT_QUOTES, $this->charset),
				);
			}

			return html::table($tableRows, 'style="width:70%;"');
		}

		return '';
	}


	function translate()
	{
		$this->t->set_var("lang_really_delete_devices_history",lang("Do you really want to delete the synchonization history of the selected devices?"));
		$this->t->set_var("lang_really_delete_datastore_history",lang("Do you really want to delete the synchonization history of the selected datastores?"));
		$this->t->set_var("lang_delete",lang('delete'));
		$this->t->set_var('lang_apply',lang('apply'));
		$this->t->set_var("th_bg",$GLOBALS['egw_info']["theme"]["th_bg"]);
		$this->t->set_var("bg01",$GLOBALS['egw_info']["theme"]["bg01"]);
		$this->t->set_var("bg02",$GLOBALS['egw_info']["theme"]["bg02"]);
		$this->t->set_var("bg03",$GLOBALS['egw_info']["theme"]["bg03"]);
	}
}
