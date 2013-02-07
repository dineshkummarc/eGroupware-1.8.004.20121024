<?php
/**
 * EGroupware - SyncML
 *
 * @link http://www.egroupware.org
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @package syncml
 * @subpackage preferences
 * @author Joerg Lehrke <jlehrke@noc.de>
 * @copyright (c) 2009 by Joerg Lehrke <jlehrke@noc.de>
 * @version $Id: class.syncml_hooks.inc.php 37262 2011-11-15 09:17:12Z ralfbecker $
 */

class syncml_hooks
{
	/**
	 * Settings hook
	 *
	 * @param array|string $hook_data
	 * @return array
	 */
	static function settings($hook_data)
	{
		$show_entries = array(
			0 => lang('Client Wins'),
			1 => lang('Server Wins'),
			2 => lang('Merge Data'),
			3 => lang('Resolv with Duplicates'),
			4 => lang('Ignore Client'),
			5 => lang('Enforce Server'),
		);

		$selectAllowed = array(
			-1	=> lang('Deny unkown devices'),
			0	=> lang('Disabled'),
			1	=> lang('Deny explicitly disabled devices'),
		);

		$select_CalendarFilter = array(
			'default'     => lang('Not rejected'),
			'accepted'    => lang('Accepted'),
			'owner'       => lang('Owner too'),
			'all'         => lang('All incl. rejected'),
		);

		$selectCharSet = array(
			null			=> lang('auto'),
			'utf-8'			=> 'UTF-8',
			'iso-8859-1'	=> 'ISO-8859-1',
		);

		$devices_Entries = array();
		if (!$hook_data['setup'])
		{
			$tzs = array(-1	=> 'Use Event TZ',
						 -2	=> 'Use my current TZ for import, UTC for export');
			$tzs += egw_time::getTimezones();

			require_once(EGW_INCLUDE_ROOT.'/syncml/inc/class.devices.inc.php');

			$user = $GLOBALS['egw_info']['user']['account_id'];

			// list the distribution lists of this user
			$addressbook_bo = new addressbook_bo();
			$perms = EGW_ACL_READ | EGW_ACL_ADD | EGW_ACL_EDIT | EGW_ACL_DELETE;
			$show_addr_lists = $addressbook_bo->get_lists($perms,array(0 => lang('None')));

			$show_addr_addr = $addressbook_bo->get_addressbooks(EGW_ACL_READ);
			unset($show_addr_addr[$user]); // skip personal addressbook
			unset($show_addr_addr[0]); // No Acounts
			$show_addr_addr[0] = lang('Accounts');
			$show_addr_addr = array('G'	=> lang('Primary Group'),
									'P'	=> lang('Personal'),
									'N' => lang('None'),
								 	 0	=> lang('All')) +  $show_addr_addr;
			// list the InfoLog filters
			$infolog_bo = new infolog_bo();
			$show_infolog_filters = array();
			translation::add_app('infolog');
			foreach ($infolog_bo->filters as $key => $val)
			{
				$show_infolog_filters[$key] = lang($val);
			}

			// list the calendars this user has access to
			$calendar_bo = new calendar_bo();
			$show_calendars = array('G'	=> lang('Primary Group'),
									'P'	=> lang('Personal'),
								 	 0	=> lang('All'));
			foreach((array)$calendar_bo->list_cals() as $grant)
			{
				if ($grant['grantor'] != $user) // skip personal calendar
				{
					$show_calendars[$grant['grantor']] = $grant['name'];
				}
			}

			// list the calendar categories of this user
			$categories = new categories($user, 'calendar');
			$calendar_categories = $categories->return_array('app', 0, false, '', 'ASC', 'cat_name', true);
			$show_cal_cats = array();
			foreach ((array)$calendar_categories as $cat)
			{
				$show_cal_cats[$cat['id']] = $cat['name'];
			}
			// list the addressbook categories of this user
			$categories = new categories($user, 'addressbook');
			$addressbook_categories = $categories->return_array('app', 0, false, '', 'ASC', 'cat_name', true);
			$show_addr = array();
			foreach ((array)$addressbook_categories as $cat)
			{
				$show_addr_cats[$cat['id']] = $cat['name'];
			}

			// list the infolog categories of this user
			$categories = new categories($user, 'infolog');
			$infolog_categories = $categories->return_array('app', 0, false, '', 'ASC', 'cat_name', true);
			$show_info_cats = array();
			foreach ((array)$infolog_categories as $cat)
			{
				$show_info_cats[$cat['id']] = $cat['name'];
			}

			// Device specific settings
			$devices =& CreateObject('syncml.devices');
			$user_devices = $devices->getAllUserDevices();
			foreach ((array)$user_devices as $device)
			{
				$label = '<b>'. lang('Settings for') . ' ' . $device['dev_manufacturer'] . ' ' . $device['dev_model'] . ' v' . $device['dev_swversion'] . '&nbsp;</b><br/>(' . $device['owner_deviceid'] . ')';
				$intro_name = 'deviceExtension-' . $device['dev_id'];
				$me_name = 'maxEntries-' . $device['dev_id'];
				$ue_name = 'uidExtension-' . $device['dev_id'];
				$nba_name = 'nonBlockingAllday-' . $device['dev_id'];
				$tz_name = 'tzid-' . $device['dev_id'];
				$charset_name = 'charset-' . $device['dev_id'];
				$allowed_name = 'allowed-' . $device['dev_id'];
				$device_Entry = array(
					$intro_name => array(
						'type'  => 'subsection',
						'title' =>  $label,
						'xmlrpc' => False,
						'admin'  => False,
						'run_lang' => false,	// do NOT translate title, as it's already translated
					),
					$me_name => array (
						'type'		=> 'input',
						'label'		=> 'Max Entries',
						'name'		=> $me_name,
						'size'		=> 3,
						'maxsize'	=> 10,
						'default'	=> 10,
						'xmlrpc'	=> True,
						'admin'		=> False,
					),
					$ue_name => array(
						'type'		=> 'check',
						'label'		=> 'UID Decription Extension',
						'name'		=> $ue_name,
						'default'	=> 0,
						'xmlrpc'	=> True,
						'admin'		=> False,
					),
					$nba_name => array(
						'type'		=> 'check',
						'label'		=> 'Non Blocking Allday Events',
						'name'		=> $nba_name,
						'default'	=> 0,
						'xmlrpc'	=> True,
						'admin'		=> False,
					),
					$tz_name => array(
						'type'   => 'select',
						'label'  => 'Time zone',
						'name'   => $tz_name,
						'values' => $tzs,
						'help'   => 'Please select the timezone of your device.',
						'xmlrpc' => True,
						'admin'  => False,
						'default'=> null,
					),
					$charset_name => array(
						'type'   => 'select',
						'label'  => 'Character Set',
						'name'   => $charset_name,
						'values' => $selectCharSet,
						'help'   => 'Please select the character set of your device.',
						'xmlrpc' => True,
						'admin'  => False,
						'default'=> null,
					),

					$allowed_name => array(
						'type'   => 'check',
						'label'  => 'Allowed Devices',
						'name'   => $allowed_name,
						'help'   => 'Is this device allowed to synchronize?',
						'default' => -1,
						'xmlrpc' => True,
						'admin'  => True,
					),
				);
				$devices_Entries += $device_Entry;
			}
		}
		/* Settings array for SyncML */
		return array(
			'prefssection' => array(
				'type'  => 'section',
				'title' => 'Preferences for the SyncML',
				'xmlrpc' => False,
				'admin'  => False
			),
			'denyunknown'	=> array(
				'type'		=> 'select',
				'label'		=> 'Deny unkown devices',
				'name'		=> 'deny_unknown_devices',
				'help'		=> 'If enabled, EGroupware will allow only devices which are allowed by the administrator.',
				'values'    => $selectAllowed,
				'default'	=> 0,
				'xmlrpc'	=> True,
				'admin'		=> True,
			),
			'slowsync'	=> array(
				'type'		=> 'check',
				'label'		=> 'SlowSync ignore map',
				'name'		=> 'slowsync_ignore_map',
				'help'		=> 'If enabled, EGroupware will ignore the mapping information of fromer sync-sessions during SlowSyncs.',
				'default'	=> 0,
				'xmlrpc'	=> True,
				'admin'		=> False,
			),
			'prefintro' => array(
				'type'  => 'subsection',
				'title' => '<h3>' . lang('Preferences for the SyncML Conflict Handling<br/>and Server R/O Options') . '</h3>',
				'xmlrpc' => False,
				'admin'  => False,
				'run_lang' => false,	// do NOT translate title, as it's already translated
			),
			'./calendar' => array(
				'type'   => 'select',
				'label'  => './calendar',
				'name'   => './calendar',
				'values' => $show_entries,
				'xmlrpc' => True,
				'admin'  => False,
				'default' => 1,
				'run_lang' => -1,	// do NOT translate label
			),
			'calendar' => array(
				'type'   => 'select',
				'label'  => 'calendar',
				'name'   => 'calendar',
				'values' => $show_entries,
				'xmlrpc' => True,
				'admin'  => False,
				'default' => 1,
				'run_lang' => -1,	// do NOT translate label
			),
			'./events' => array(
				'type'   => 'select',
				'label'  => './events',
				'name'   => './events',
				'values' => $show_entries,
				'xmlrpc' => True,
				'admin'  => False,
				'default' => 1,
				'run_lang' => -1,	// do NOT translate label
			),
			'events' => array(
				'type'   => 'select',
				'label'  => 'events',
				'name'   => 'events',
				'values' => $show_entries,
				'xmlrpc' => True,
				'admin'  => False,
				'default' => 1,
				'run_lang' => -1,	// do NOT translate label
			),
			'./contacts' => array(
				'type'   => 'select',
				'label'  => './contacts',
				'name'   => './contacts',
				'values' => $show_entries,
				'xmlrpc' => True,
				'admin'  => False,
				'default' => 1,
				'run_lang' => -1,	// do NOT translate label
			),
			'contacts' => array(
				'type'   => 'select',
				'label'  => 'contacts',
				'name'   => 'contacts',
				'values' => $show_entries,
				'xmlrpc' => True,
				'admin'  => False,
				'default' => 1,
				'run_lang' => -1,	// do NOT translate label
			),
			'./card' => array(
				'type'   => 'select',
				'label'  => './card',
				'name'   => './card',
				'values' => $show_entries,
				'xmlrpc' => True,
				'admin'  => False,
				'default' => 1,
				'run_lang' => -1,	// do NOT translate label
			),
			'card' => array(
				'type'   => 'select',
				'label'  => 'card',
				'name'   => 'card',
				'values' => $show_entries,
				'xmlrpc' => True,
				'admin'  => False,
				'default' => 1,
				'run_lang' => -1,	// do NOT translate label
			),
			'./tasks' => array(
				'type'   => 'select',
				'label'  => './tasks',
				'name'   => './tasks',
				'values' => $show_entries,
				'xmlrpc' => True,
				'admin'  => False,
				'default' => 1,
				'run_lang' => -1,	// do NOT translate label
			),
			'tasks' => array(
				'type'   => 'select',
				'label'  => 'tasks',
				'name'   => 'tasks',
				'values' => $show_entries,
				'xmlrpc' => True,
				'admin'  => False,
				'default' => 1,
				'run_lang' => -1,	// do NOT translate label
			),
			'./jobs' => array(
				'type'   => 'select',
				'label'  => './jobs',
				'name'   => './jobs',
				'values' => $show_entries,
				'xmlrpc' => True,
				'admin'  => False,
				'default' => 1,
				'run_lang' => -1,	// do NOT translate label
			),
			'jobs' => array(
				'type'   => 'select',
				'label'  => 'jobs',
				'name'   => 'jobs',
				'values' => $show_entries,
				'xmlrpc' => True,
				'admin'  => False,
				'default' => 1,
				'run_lang' => -1,	// do NOT translate label
			),
			'./caltasks' => array(
				'type'   => 'select',
				'label'  => './caltasks',
				'name'   => './caltasks',
				'values' => $show_entries,
				'xmlrpc' => True,
				'admin'  => False,
				'default' => 1,
				'run_lang' => -1,	// do NOT translate label
			),
			'caltasks' => array(
				'type'   => 'select',
				'label'  => 'caltasks',
				'name'   => 'caltasks',
				'values' => $show_entries,
				'xmlrpc' => True,
				'admin'  => False,
				'default' => 1,
				'run_lang' => -1,	// do NOT translate label
			),
			'./notes' => array(
				'type'   => 'select',
				'label'  => './notes',
				'name'   => './notes',
				'values' => $show_entries,
				'xmlrpc' => True,
				'admin'  => False,
				'default' => 1,
				'run_lang' => -1,	// do NOT translate label
			),
			'notes' => array(
				'type'   => 'select',
				'label'  => 'notes',
				'name'   => 'notes',
				'values' => $show_entries,
				'xmlrpc' => True,
				'admin'  => False,
				'default' => 1,
				'run_lang' => -1,	// do NOT translate label
			),
			'./sifcalendar' => array(
				'type'   => 'select',
				'label'  => './sifcalendar',
				'name'   => './sifcalendar',
				'values' => $show_entries,
				'xmlrpc' => True,
				'admin'  => False,
				'default' => 1,
				'run_lang' => -1,	// do NOT translate label
			),
			'sifcalendar' => array(
				'type'   => 'select',
				'label'  => 'sifcalendar',
				'name'   => 'sifcalendar',
				'values' => $show_entries,
				'xmlrpc' => True,
				'admin'  => False,
				'default' => 1,
				'run_lang' => -1,	// do NOT translate label
			),
			'./scal' => array(
				'type'   => 'select',
				'label'  => './scal',
				'name'   => './scal',
				'values' => $show_entries,
				'xmlrpc' => True,
				'admin'  => False,
				'default' => 1,
				'run_lang' => -1,	// do NOT translate label
			),
			'scal' => array(
				'type'   => 'select',
				'label'  => 'scal',
				'name'   => 'scal',
				'values' => $show_entries,
				'xmlrpc' => True,
				'admin'  => False,
				'default' => 1,
				'run_lang' => -1,	// do NOT translate label
			),
			'./sifcontacts' => array(
				'type'   => 'select',
				'label'  => './sifcontacts',
				'name'   => './sifcontacts',
				'values' => $show_entries,
				'xmlrpc' => True,
				'admin'  => False,
				'default' => 1,
				'run_lang' => -1,	// do NOT translate label
			),
			'sifcontacts' => array(
				'type'   => 'select',
				'label'  => 'sifcontacts',
				'name'   => 'sifcontacts',
				'values' => $show_entries,
				'xmlrpc' => True,
				'admin'  => False,
				'default' => 1,
				'run_lang' => -1,	// do NOT translate label
			),
			'./scard' => array(
				'type'   => 'select',
				'label'  => './scard',
				'name'   => './scard',
				'values' => $show_entries,
				'xmlrpc' => True,
				'admin'  => False,
				'default' => 1,
				'run_lang' => -1,	// do NOT translate label
			),
			'scard' => array(
				'type'   => 'select',
				'label'  => 'scard',
				'name'   => 'scard',
				'values' => $show_entries,
				'xmlrpc' => True,
				'admin'  => False,
				'default' => 1,
				'run_lang' => -1,	// do NOT translate label
			),
			'./siftasks' => array(
				'type'   => 'select',
				'label'  => './siftasks',
				'name'   => './siftasks',
				'values' => $show_entries,
				'xmlrpc' => True,
				'admin'  => False,
				'default' => 1,
				'run_lang' => -1,	// do NOT translate label
			),
			'siftasks' => array(
				'type'   => 'select',
				'label'  => 'siftasks',
				'name'   => 'siftasks',
				'values' => $show_entries,
				'xmlrpc' => True,
				'admin'  => False,
				'default' => 1,
				'run_lang' => -1,	// do NOT translate label
			),
			'./stask' => array(
				'type'   => 'select',
				'label'  => './stask',
				'name'   => './stask',
				'values' => $show_entries,
				'xmlrpc' => True,
				'admin'  => False,
				'default' => 1
			),
			'stask' => array(
				'type'   => 'select',
				'label'  => 'stask',
				'name'   => 'stask',
				'values' => $show_entries,
				'xmlrpc' => True,
				'admin'  => False,
				'default' => 1,
				'run_lang' => -1,	// do NOT translate label
			),
			'./sifnotes' => array(
				'type'   => 'select',
				'label'  => './sifnotes',
				'name'   => './sifnotes',
				'values' => $show_entries,
				'xmlrpc' => True,
				'admin'  => False,
				'default' => 1,
				'run_lang' => -1,	// do NOT translate label
			),
			'sifnotes' => array(
				'type'   => 'select',
				'label'  => 'sifnotes',
				'name'   => 'sifnotes',
				'values' => $show_entries,
				'xmlrpc' => True,
				'admin'  => False,
				'default' => 1,
				'run_lang' => -1,	// do NOT translate label
			),
			'./snote' => array(
				'type'   => 'select',
				'label'  => './snote',
				'name'   => './snote',
				'values' => $show_entries,
				'xmlrpc' => True,
				'admin'  => False,
				'default' => 1,
				'run_lang' => -1,	// do NOT translate label
			),
			'snote' => array(
				'type'   => 'select',
				'label'  => 'snote',
				'name'   => 'snote',
				'values' => $show_entries,
				'xmlrpc' => True,
				'admin'  => False,
				'default' => 1,
				'run_lang' => -1,	// do NOT translate label
			),
			'uidintro' => array(
				'type'  => 'subsection',
				'title' => '<h3>' . lang('Minimum Accepted UID Length') . '</h3>',
				'xmlrpc' => False,
				'admin'  => False,
				'run_lang' => false,	// do NOT translate title, as it's already translated
			),
			'minimum_uid_length' => array (
				'type'		=> 'input',
				'label'		=> 'Minimum UID Length',
				'name'		=> 'minimum_uid_length',
				'size'		=> 2,
				'maxsize'	=> 3,
				'default'	=> 8,
				'xmlrpc'	=> True,
				'admin'		=> False,
			),
			'preffilter' => array(
				'type'  => 'subsection',
				'title' => '<h3>' . lang('Addressbook Synchronization Options') . '</h3>',
				'xmlrpc' => False,
				'admin'  => False,
				'run_lang' => false,	// do NOT translate title, as it's already translated
			),
			'filter_list'	=> array(
				'type'		=> 'select',
				'label'		=> 'Synchronize this list',
				'name'		=> 'filter_list',
				'help'		=> 'This address list of contacts will be synchronized. ' .
								'If used together with the addressbook option, this list will appended.',
				'values'	=> $show_addr_lists,
				'xmlrpc'	=> True,
				'default'	=> 0,
				'admin'		=> False,
			),
			'filter_addressbook' => array(
				'type'   	=> 'select',
				'label'		=> 'Synchronize this addressbook',
				'name'		=> 'filter_addressbook',
				'help'		=> 'Only entries from this addressbook (and the above list) will be synchronized.',
				'values'	=> $show_addr_addr,
				'default'	=> 'P', // Personal
				'xmlrpc'	=> True,
				'admin'		=> False,
			),
			'calendarhistoryintro' => array(
				'type'  => 'subsection',
				'title' => '<h3>' . lang('Calendar Synchronization Options') . '</h3>',
				'xmlrpc' => False,
				'admin'  => False,
				'run_lang' => false,	// do NOT translate title, as it's already translated
			),
			'calendar_past' => array(
				'type'		=> 'input',
				'label'		=> 'Calendar History Period',
				'name'		=> 'calendar_past',
				'help'	    => 'Your calendar will be synchronized up to this number of seconds in the past (2678400 seconds = 31 days).',
				'size'		=> 8,
				'maxsize'	=> 9,
				'default'	=> 2678400,
				'xmlrpc'	=> True,
				'admin'		=> False,
			),
			'calendar_future' => array (
				'type'		=> 'input',
				'label'		=> 'Calendar Future Period',
				'name'		=> 'calendar_future',
				'help'	    => 'Only events up to this number of seconds in the future will be synchonized (65000000 seconds > 2 years).',
				'size'		=> 8,
				'maxsize'	=> 9,
				'default'	=> 65000000,
				'xmlrpc'	=> True,
				'admin'		=> False,
			),
			'calendar_filter' => array(
				'type'		=> 'select',
				'label' 	=> 'Calendar Filter',
				'name'		=> 'calendar_filter',
				'help'		=> 'Only Events matching this filter criteria will be synchronized.',
				'values'	=> $select_CalendarFilter,
				'default'	=> 'all',
				'xmlrpc'	 => True,
				'admin'  	=> False,
			),
			'calendar_owner' => array(
				'type'		=> 'select',
				'label' 	=> 'Syncronization Calendars',
				'name'		=> 'calendar_owner',
				'help'		=> 'Events from selected Calendars will be synchronized.',
				'values'	=> $show_calendars,
				'default'	=> 'P', // Personal
				'xmlrpc'	 => True,
				'admin'  	=> False,
			),
			'taskoptionintro' => array(
				'type'  => 'subsection',
				'title' => '<h3>' . lang('Task Synchronization Options') . '</h3>',
				'xmlrpc' => False,
				'admin'  => False,
				'run_lang' => false,	// do NOT translate title, as it's already translated
			),
			'task_filter' => array(
				'type'   => 'select',
				'label'  => 'Synchronize this selection',
				'name'   => 'task_filter',
				'help'   => 'Only Tasks matching this filter criteria will be synchronized.',
				'values' => $show_infolog_filters,
				'xmlrpc' => True,
				'admin'  => False,
			),
			'noteoptionintro' => array(
				'type'  => 'subsection',
				'title' => '<h3>' . lang('Note Synchronization Options') . '</h3>',
				'xmlrpc' => False,
				'admin'  => False,
				'run_lang' => false,	// do NOT translate title, as it's already translated
			),
			'note_filter' => array(
				'type'   => 'select',
				'label'  => 'Synchronize this selection',
				'name'   => 'note_filter',
				'help'   => 'Only Notes matching this filter criteria will be synchronized.',
				'values' => $show_infolog_filters,
				'xmlrpc' => True,
				'admin'  => False,
			),
			'catintro' => array(
				'type'  => 'subsection',
				'title' => '<h3>' . lang('Categories for Conflict Duplicates') . '</h3>',
				'xmlrpc' => False,
				'admin'  => False,
				'run_lang' => false,	// do NOT translate title, as it's already translated
			),
			'calendar_conflict_category' => array(
				'type'   => 'select',
				'label'  => 'Calendar Conflict Category',
				'name'   => 'calendar_conflict_category',
				'help'   => 'To this Calendar category a conflict duplicate will be added.',
				'values' => $show_cal_cats,
				'xmlrpc' => True,
				'admin'  => False,
			),
			'adddressbook_conflict_category' => array(
				'type'   => 'select',
				'label'  => 'Addressbook Conflict Category',
				'name'   => 'addressbook_conflict_category',
				'help'   => 'To this Addressbook category a conflict duplicate will be added.',
				'values' => $show_addr_cats,
				'xmlrpc' => True,
				'admin'  => False,
			),
			'infolog_conflict_category' => array(
				'type'   => 'select',
				'label'  => 'InfoLog Conflict Category',
				'name'   => 'infolog_conflict_category',
				'help'   => 'A duplicate infolog entry from a synchronization conflict will be assigned to this category.',
				'values' => $show_info_cats,
				'xmlrpc' => True,
				'admin'  => False,
			),
			'max_entries' => array(
				'type'  => 'subsection',
				'title' => '<h2>' . lang('Device Specific Settings')  . '</h2>' .
				lang('For <b>Max Entries</b> = 0 either <i>maxMsgSize</i> will be used or the default value 10.<br/>With <b>Non Blocking Allday Events</b> set allday events will be nonblocking when imported from this device.<br/>The <b>UID Extension</b> enables the preservation of vCalandar UIDs by appending them to <i>Description</i> field for this device.<br/>The selected <b>Time zone</b> is used for calendar event syncronization with the device. If not set, the timezones of the events are used.'),
				'xmlrpc' => False,
				'admin'  => False,
				'run_lang' => false,	// do NOT translate title, as it's already translated
			),
		) + $devices_Entries;
	}

	/**
	 * Preferences hook
	 *
	 * @param array|string $hook_data
	 */
	static function preferences($hook_data)
	{
		// Only Modify the $file and $title variables.....
		$title = $appname = 'syncml';
		$file = array(
			'Preferences' => $GLOBALS['egw']->link('/index.php', 'menuaction=preferences.uisettings.index&appname=' . $appname),
			'Device History' => $GLOBALS['egw']->link('/index.php', 'menuaction=syncml.devices.listDevices'),
			//'Consistency Check'	=> $GLOBALS['egw']->link('/index.php', 'menuaction=syncml.devices.consistencyCheck'),
			'Documentation' => $GLOBALS['egw']->link('/'. $appname . '/index.php')
		);
		// Don't modify below this line
		display_section($appname,$title,$file);
	}
}
