<?php
/**
 * Tracker - Universal tracker (bugs, feature requests, ...) with voting and bounties
 *
 * @link http://www.egroupware.org
 * @author Ralf Becker <RalfBecker-AT-outdoor-training.de>
 * @package tracker
 * @copyright (c) 2006-10 by Ralf Becker <RalfBecker-AT-outdoor-training.de>
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @version $Id: class.tracker_bo.inc.php 40093 2012-08-09 09:53:34Z ralfbecker $
 */

/**
 * Some constants for the check_rights function
 */
define('TRACKER_ADMIN',1);
define('TRACKER_TECHNICIAN',2);
define('TRACKER_USER',4);		// non-anonymous user with tracker-rights
define('TRACKER_EVERYBODY',8);	// everyone incl. anonymous user
define('TRACKER_ITEM_CREATOR',16);
define('TRACKER_ITEM_ASSIGNEE',32);
define('TRACKER_ITEM_NEW',64);
define('TRACKER_ITEM_GROUP',128);

/**
 * Business Object of the tracker
 */
class tracker_bo extends tracker_so
{
	/**
	 * Timestamps which need to be converted to user-time and back
	 *
	 * @var array
	 */
	var $timestamps = array('tr_created','tr_modified','tr_closed','reply_created');
	/**
	 * Current user
	 *
	 * @var int;
	 */
	var $user;

	/**
	 * Existing trackers (stored as app-global cats with cat_data='tracker')
	 *
	 * @var array
	 */
	var $trackers;
	/**
	 * Existing priorities
	 *
	 * @var array
	 */
	static $stock_priorities = array(
		1 => '1 - lowest',
		2 => '2',
		3 => '3',
		4 => '4',
		5 => '5 - medium',
		6 => '6',
		7 => '7',
		8 => '8',
		9 => '9 - highest',
	);
	/**
	 * Priorities by tracker or key=0 for all trackers
	 *
	 * Not set trackers use the key=0 or if that's not set the stock priorities
	 *
	 * @var array
	 */
	protected $priorities;
	/**
	 * Stati used by all trackers
	 *
	 * @var array
	 */
	static $stati = array(
		self::STATUS_OPEN    => 'Open',
		self::STATUS_CLOSED  => 'Closed',
		self::STATUS_DELETED => 'Deleted',
		self::STATUS_PENDING => 'Pending',
	);
	/**
	 * Resolutions used by all trackers
	 *
	 * @var array
	 */
	static $resolutions = array(
		'n' => 'None',
		'a' => 'Accepted',
		'd' => 'Duplicate',
		'f' => 'Fixed',
		'i' => 'Invalid',
		'I' => 'Info only',
		'l' => 'Later',
		'o' => 'Out of date',
		'p' => 'Postponed',
		'O' => 'Outsourced',
		'r' => 'Rejected',
		'R' => 'Remind',
		'w' => 'Wont fix',
		'W' => 'Works for me',
	);
	/**
	 * Technicians by tracker or key=0 for all trackers
	 *
	 * @var array
	 */
	var $technicians;
	/**
	 * Admins by tracker or key=0 for all trackers
	 *
	 * @var array
	 */
	var $admins;
	/**
	 * Users by tracker or key=0 for all trackers
	 *
	 * @var array
	 */
	var $users;
	/**
	 * ACL for the fields of the tracker
	 *
	 * field-name is the key with values or'ed together from the TRACKER_ constants
	 *
	 * @var array
	 */
	var $field_acl;
	/**
	 * Restricions settings (tracker specific, keys: group, creator)
	 *
	 * @var array
	 */
	var $restrictions;
	/**
	 * Enabled the acl queue access?
	 *
	 * @var boolean
	 */
	var $enabled_queue_acl_access = false;
	/**
	 * Mailhandler settings (tracker unspecific)
	 *  Keys:
	 *   interval
	 *   address
	 *   server
	 *   servertype
	 *   serverport
	 *   folder
	 *   username
	 *   password
	 *   delete_from_server (true/false)
	 *   default_tracker (<empty>=reject new tickets|TrackerID)
	 *   unrecognized_mails (ignore/delete/forward/default)
	 *   unrec_reply (0=Creator/1=Nobody)
	 *   unrec_mail (<empty>=ignore|UID)
	 *   forward_to
	 *   auto_reply (0=Never/1=New/2=Always)
	 *   reply_unknown (1=Yes/0=No)
	 *   reply_text (text message)
	 *
	 * @var array
	 */
	var $mailhandling = array();
	/**
	 * Supported server types for mail handling as an array of arrays with spec => descr
	 *
	 * @var array
	 */
	var $mailservertypes = array(
		0 => array('imap/notls', 'Standard IMAP'),
		1 => array('imap/tls', 'IMAP, TLS secured'),
		2 => array('imap/ssl', 'IMAP, SSL secured'),
		3 => array('pop3', 'POP3'),
	);

	/**
	 * Translates field / acl-names to labels
	 *
	 * @var array
	 */
	var $field2label = array(
		'tr_summary'     => 'Summary',
		'tr_tracker'     => 'Tracker',
		'cat_id'         => 'Category',
		'tr_version'     => 'Version',
		'tr_status'      => 'Status',
		'tr_description' => 'Description',
		'tr_assigned'    => 'Assigned to',
		'tr_private'     => 'Private',
//		'tr_budget'      => 'Budget',
		'tr_resolution'  => 'Resolution',
		'tr_completion'  => 'Completed',
		'tr_priority'    => 'Priority',
		'tr_closed'      => 'Closed',
		'tr_creator'     => 'Created by',
		'tr_created'     => 'Created on',
		'tr_group'		 => 'Owned by group',
		// pseudo fields used in edit
		'link_to'        => 'Attachments & Links',
		'canned_response' => 'Canned response',
		'reply_message'  => 'Add comment',
		'add'            => 'Add',
		'vote'           => 'Vote for it!',
		'no_notifications'	=> 'No notifications',
		'bounty'         => 'Set bounty',
		'tr_cc'			 => 'CC',
		'num_replies'    => 'Number of replies',
		'customfields'   => 'Custom fields',
	);
	/**
	 * Translate field-name to 2-char history status
	 *
	 * @var array
	 */
	var $field2history = array(
		'tr_summary'     => 'Su',
		'tr_tracker'     => 'Tr',
		'cat_id'         => 'Ca',
		'tr_version'     => 'Ve',
		'tr_status'      => 'St',
		'tr_description' => 'De',
		'tr_assigned'    => 'As',
		'tr_private'     => 'pr',
//		'tr_budget'      => 'Bu',
		'tr_completion'  => 'Co',
		'tr_priority'    => 'Pr',
		'tr_closed'      => 'Cl',
		'tr_resolution'  => 'Re',
		'tr_cc'			 => 'Cc',
		'tr_group'		 => 'Gr',
		'num_replies'    => 'Nr',
/* the following bounty-stati are only for reference
		'bounty-set'     => 'bo',
		'bounty-deleted' => 'xb',
		'bounty-confirmed'=> 'Bo',
*/
		// all custom fields together
		'customfields'	=> '#c',
	);
	/**
	 * Allow to assign tracker items to groups:  0=no; 1=yes, display groups+users; 2=yes, display users+groups
	 *
	 * @var int
	 */
	var $allow_assign_groups=1;
	/**
	 * Allow to vote on tracker items
	 *
	 * @var boolean
	 */
	var $allow_voting=true;
	/**
	 * How many days to mark a not responded item overdue
	 *
	 * @var int
	 */
	var $overdue_days=14;
	/**
	 * How many days to mark a pending item closed
	 *
	 * @var int
	 */
	var $pending_close_days=7;
	/**
	 * Permit html editing on details and comments
	 */
	var $htmledit = false;
	var $all_cats;
	var $historylog;
	/**
	 * Instance of the tracker_tracking object
	 *
	 * @var tracker_tracking
	 */
	var $tracking;
	/**
	 * Names of all config vars
	 *
	 * @var array
	 */
	var $config_names = array(
		'technicians','admins','users','notification','projects','priorities','restrictions',	// tracker specific
		'field_acl','allow_assign_groups','allow_voting','overdue_days','pending_close_days','htmledit', 'mailhandling',	// tracker unspecific
		'allow_bounties','currency','enabled_queue_acl_access',
	);
	/**
	 * Notification settings (tracker specific, keys: sender, link, copy, lang)
	 *
	 * @var array
	 */
	var $notification;
	/**
	 * Allow bounties to be set on tracker items
	 *
	 * @var string
	 */
	var $allow_bounties = true;
	/**
	 * Currency used by the bounties
	 *
	 * @var string
	 */
	var $currency = 'Euro';
	/**
	 * Filters to manage advanced logical statis
	 */
	var $filters = array(
		'not-closed'						=> '&#9830; Not closed',
		'own-not-closed'					=> '&#9830; Own not closed',
		'without-reply-not-closed' 			=> '&#9830; Without reply not closed',
		'own-without-reply-not-closed' 		=> '&#9830; Own without reply not closed',
		'without-30-days-reply-not-closed'	=> '&#9830; Without 30 days reply not closed',
	);

	/**
	 * Constructor
	 *
	 * @return tracker_bo
	 */
	function __construct()
	{
		parent::__construct();

		$this->user = $GLOBALS['egw_info']['user']['account_id'];

		$this->trackers = $this->get_tracker_labels();

		// read the tracker-configuration
		$this->load_config();
	}

	/**
	 * Changes the data from the db-format to your work-format
	 *
	 * @param array $data if given works on that array and returns result, else works on internal data-array
	 * @return array with changed data
	 */
	function db2data($data=null)
	{
		if (($intern = !is_array($data)))
 		{
 			$data =& $this->data;
 		}
		if (is_array($data['replies']))
		{
			foreach($data['replies'] as &$reply)
			{
				$reply['reply_servertime'] = $reply['reply_created'];
				$reply['reply_created'] = egw_time::server2user($reply['reply_created'],$this->timestamp_type);
			}
		}
		// check if item is overdue
		if ($this->overdue_days > 0)
		{
			$modified = $data['tr_modified'] ? $data['tr_modified'] : $data['tr_created'];
			$limit = $this->now - $this->overdue_days * 24*60*60;
			$data['overdue'] = $data['tr_status'] == -100 && 	// only open items can be overdue
				(!$data['tr_modified'] || $data['tr_modifier'] == $data['tr_creator']) && $modified < $limit;
		}
		if (is_numeric($data['tr_completion'])) $data['tr_completion'] .= '%';

		// Keep a copy of the timestamps in server time, so notifications can change them for each user
		foreach($this->timestamps as $field)
		{
			$data[$field . '_servertime'] = $data[$field];
		}

		// will run all regular timestamps ($this->timestamps) trough egw_time::server2user()
		return parent::db2data($intern ? null : $data);	// important to use null, if $intern!
	}

	/**
	 * Changes the data from your work-format to the db-format
	 *
	 * @param array $data if given works on that array and returns result, else works on internal data-array
	 * @return array with changed data
	 */
	function data2db($data=null)
	{
		if (($intern = !is_array($data)))
		{
			$data = &$this->data;
		}
		if (substr($data['tr_completion'],-1) == '%') $data['tr_completion'] = (int) round(substr($data['tr_completion'],0,-1));

		// will run all regular timestamps ($this->timestamps) trough egw_time::user2server()
		return parent::data2db($intern ? null : $data);	// important to use null, if $intern!
	}

	/**
	 * Read a tracker item
	 *
	 * Reimplemented to store the old status
	 *
	 * @param array $keys array with keys in form internalName => value, may be a scalar value if only one key
	 * @param string/array $extra_cols string or array of strings to be added to the SELECT, eg. "count(*) as num"
	 * @param string $join sql to do a join, added as is after the table-name, eg. ", table2 WHERE x=y" or
	 * @return array/boolean data if row could be retrived else False
	*/
	function read($keys,$extra_cols='',$join='')
	{
		if (($ret = parent::read($keys,$extra_cols,$join)))
		{
			$this->data['old_status'] = $this->data['tr_status'];
		}
		return $ret;
	}

	/**
	 * saves the content of data to the db
	 *
	 * @param array $keys if given $keys are copied to data before saveing => allows a save as
	 * @param array $autoreply when called from the mailhandler, contains data for the autoreply
	 * (only for forwarding to tracling)
	 * @return int 0 on success and errno != 0 else
	 */
	function save($keys=null, $autoreply=null)
	{
		if ($keys) $this->data_merge($keys);

		if (!$this->data['tr_id'])	// new entry
		{
			$this->data['tr_created'] = $this->now;
			$this->data['tr_creator'] = $this->user;
			$this->data['tr_status'] = self::STATUS_OPEN;
			$this->data['tr_seen'] = serialize(array($this->user));

			if (!$this->data['tr_group'])
			{
				$this->data['tr_group'] = $GLOBALS['egw']->accounts->data['account_primary_group'];
			}

			if ($this->data['cat_id'] && !$this->data['tr_assigned'])
			{
				$this->autoassign();
			}
		}
		else
		{
			// check if we have a real modification
			// read the old record
			$new =& $this->data;
			unset($this->data);
			$this->read($new['tr_id']);
			$old =& $this->data;
			$this->data =& $new;
			$changed[] = array();
			foreach($old as $name => $value)
			{
				if (isset($new[$name]) && $new[$name] != $value) $changed[] = $name;
			}
			if (!$changed)
			{
				//echo "<p>botracker::save() no change --> no save needed</p>\n";
				return false;
			}
			$this->data['tr_modified'] = $this->now;
			$this->data['tr_modifier'] = $this->user;
			// set close-date if status is closed and not yet set
			if ($this->data['tr_status'] == self::STATUS_CLOSED && is_null($this->data['tr_closed']))
			{
				$this->data['tr_closed'] = $this->now;
			}
			// unset closed date, if item is re-opend
			if ($this->data['tr_status'] != self::STATUS_CLOSED && !is_null($this->data['tr_closed']))
			{
				$this->data['tr_closed'] = null;
			}
			if ($this->data['reply_message'] || $this->data['canned_response'])
			{
				// Replies mark the ticket unseen for everbody but the current
				// user if the ticket wasn't closed at the same time
				if ($this->data['tr_status'] != self::STATUS_CLOSED)
				{
					$this->data['tr_seen'] = serialize(array($this->user));
				}

				if ($this->data['canned_response'])
				{
					$this->data['reply_message'] = $this->get_canned_response($this->data['canned_response']).
						($this->data['reply_message'] ? "\n\n".$this->data['reply_message'] : '');
				}
				$this->data['reply_created'] = $this->now;
				$this->data['reply_creator'] = $this->user;

				// replies set status pending back to open
				if ($this->data['old_status'] == self::STATUS_PENDING && $this->data['old_status'] == $this->data['tr_status'])
				{
					$this->data['tr_status'] = self::STATUS_OPEN;
				}
			}
		}
		if (!($err = parent::save()))
		{
			// so other apps can update eg. their titles and the cached title gets unset
			egw_link::notify_update('tracker',$this->data['tr_id'],$this->data);

			if (!is_object($this->tracking))
			{
				$this->tracking = new tracker_tracking($this);
				if($this->prefs['notify_own_modification'])
				{
					$this->tracking->notify_current_user = true;
				}
				$this->tracking->html_content_allow = true;
			}
			if ($this->customfields)
			{
				$data_custom = $old_custom = array();
				foreach($this->customfields as $name => $custom)
				{
					if (isset($this->data['#'.$name]) && (string)$this->data['#'.$name]!=='') $data_custom[] = $custom['label'].': '.$this->data['#'.$name];
					if (isset($old['#'.$name]) && (string)$old['#'.$name]!=='') $old_custom[] = $custom['label'].': '.$old['#'.$name];
				}
				$this->data['customfields'] = implode("\n",$data_custom);
				$old['customfields'] = implode("\n",$old_custom);
			}
			if (!$this->tracking->track($this->data,$old,$this->user,null,null,$this->data['no_notifications']))
			{
				return implode(', ',$this->tracking->errors);
			}
			if ($autoreply)
			{
				$this->tracking->autoreply($this->data,$autoreply,$old);
			}
		}
		return $err;
	}

	/**
	 * Get a list of all groups
	 *
	 * @param boolean $primary=false, when not ACL to change the group, return primary group only on new tickets
	 * @return array with gid => group-name pairs
	 */
	function &get_groups($primary=false)
	{
		static $groups;
		static $primary_group;

		if($primary)
		{
			if (isset($primary_group))
			{
				return $primary_group;
			}
		}
		else
		{
			if(isset($groups))
			{
				return $groups;
			}
		}

		$groups = array();
		$primary_group = array();
		$group_list = $GLOBALS['egw']->accounts->search(array('type' => 'groups', 'order' => 'account_lid', 'sort' => 'ASC'));
		foreach($group_list as $gid)
		{
			$groups[$gid['account_id']] = $gid['account_lid'];
		}
		$primary_group[$GLOBALS['egw']->accounts->data['account_primary_group']] = $groups[$GLOBALS['egw']->accounts->data['account_primary_group']];

		return ($primary ? $primary_group : $groups);
	}

	/**
	 * Get the staff (technicians or admins) of a tracker
	 *
	 * @param int $tracker tracker-id or 0, 0 = staff of all trackers!
	 * @param int $return_groups=2 0=users, 1=groups+users, 2=users+groups
	 * @param string $what='technicians' technicians=technicians (incl. admins), admins=only admins, users=only users
	 * @return array with uid => user-name pairs
	 */
	function &get_staff($tracker,$return_groups=2,$what='technicians')
	{
		static $staff_cache;

		//echo "botracker::get_staff($tracker,$return_groups,$technicians)";

		// some caching
		if (isset($staff_cache[$tracker]) && isset($staff_cache[$tracker][(int)$return_groups]) &&
			isset($staff_cache[$tracker][(int)$return_groups][$what]))
		{
			//echo "from cache"; _debug_array($staff_cache[$tracker][$return_groups][$what]);
			return $staff_cache[$tracker][(int)$return_groups][$what];
		}
		$staff = array();
		switch($what)
		{
			case 'users':
				foreach($tracker ? array(0,$tracker) : array_keys($this->users) as $t)
				{
					if (is_array($this->users[$t])) $staff = array_merge($staff,$this->users[$t]);
				}
				break;
			case 'technicians':
				foreach($tracker ? array(0,$tracker) : array_keys($this->technicians) as $t)
				{
					if (is_array($this->technicians[$t])) $staff = array_merge($staff,$this->technicians[$t]);
				}
				// fall through, as technicians include admins
			case 'admins':
				foreach($tracker ? array(0,$tracker) : array_keys($this->admins) as $t)
				{
					if (is_array($this->admins[$t])) $staff = array_merge($staff,$this->admins[$t]);
				}
				break;
		}

		// split users and groups and resolve the groups into there users
		$users = $groups = array();
		foreach(array_unique($staff) as $uid)
		{
			if ($GLOBALS['egw']->accounts->get_type($uid) == 'g')
			{
				if ($return_groups) $groups[(string)$uid] = $GLOBALS['egw']->common->grab_owner_name($uid);
				foreach((array)$GLOBALS['egw']->accounts->members($uid,true) as $u)
				{
					if (!isset($users[$u])) $users[$u] = $GLOBALS['egw']->common->grab_owner_name($u);
				}
			}
			else // users
			{
				if (!isset($users[$uid])) $users[$uid] = $GLOBALS['egw']->common->grab_owner_name($uid);
			}
		}
		// sort alphabetic
		natcasesort($users);
		natcasesort($groups);

		// groups or users first
		$staff = $this->allow_assign_groups == 1 ? $groups : $users;

		if ($this->allow_assign_groups)	// do we need a second one
		{
			foreach($this->allow_assign_groups == 1 ? $users : $groups as $uid => $label)
			{
				$staff[$uid] = $label;
			}
		}
		//_debug_array($staff);
		return $staff_cache[$tracker][(int)$return_groups][$what] = $staff;
	}

	/**
	 * Check if a user (default current user) is an admin for the given tracker
	 *
	 * @param int $tracker ID of tracker
	 * @param int $user=null ID of user, default current user $this->user
	 * @return boolean
	 */
	function is_admin($tracker,$user=null)
	{
		if (is_null($user)) $user = $this->user;

		$admins =& $this->get_staff($tracker,0,'admins');

		return isset($admins[$user]);
	}

	/**
	 * Check if a user (default current user) is an tecnichan for the given tracker
	 *
	 * @param int $tracker ID of tracker
	 * @param int $user=null ID of user, default current user $this->user
	 * @return boolean
	 */
	function is_technician($tracker,$user=null,$checkgroups=false)
	{
		if (is_null($user)) $user = $this->user;

		$technicians =& $this->get_staff($tracker,($checkgroups ? 2 : 0),'technicians');

		return isset($technicians[$user]);
	}

	/**
	 * Check if a user (default current user) is an tecnichan for the given tracker
	 *
	 * @param int $tracker ID of tracker
	 * @param int $user=null ID of user, default current user $this->user
	 * @return boolean
	 */
	function is_user($tracker,$user=null)
	{
		if (is_null($user)) $user = $this->user;

		$users =& $this->get_staff($tracker,0,'users');

		return isset($users[$user]);
	}

	/**
	 * Check if a user (default current user) is staff member for the given tracker
	 *
	 * @param int $tracker ID of tracker
	 * @param int $user=null ID of user, default current user $this->user
	 * @return boolean
	 */
	function is_staff($tracker,$user=null)
	{
		if (is_null($user)) $user = $this->user;

		return ($this->is_technician($tracker,$user) || $this->is_admin($tracker,$user));
	}

	/**
	 * Check if current user is anonymous
	 *
	 * @return boolean
	 */
	function is_anonymous()
	{
		static $anonymous;

		if (!is_null($anonymous)) return $anonymous;

		//echo "<p align=right>is_anonymous=".(int)$GLOBALS['egw']->acl->check('anonymous',1,'phpgwapi')."</p>\n";
		return $anonymous = $GLOBALS['egw']->acl->check('anonymous',1,'phpgwapi');
	}

	/**
	 * Check what rights the current user has on the loaded tracker item ($this->data) or a given tracker
	 *
	 * @param int $needed or'ed together: TRACKER_ADMIN|TRACKER_TECHNICIAN|TRACKER_ITEM_CREATOR|TRACKER_ITEM_ASSIGNEE
	 * @param int $check_only_tracker=null should only the given tracker be checked and NO $this->data specific checks be performed, default no
	 */
	function check_rights($needed,$check_only_tracker=null)
	{
		$tracker = $check_only_tracker ? $check_only_tracker : $this->data['tr_tracker'];
		//echo "<p align=right>botracker::check_rights($needed) tracker=$tracker='".$this->trackers[$tracker]."', is_tracker_user=".(int)isset($GLOBALS['egw_info']['user']['apps']['tracker']).", is_anonymous=".(int)$this->is_anonymous()."</p>\n";
		if (!$needed) return false;

		if ($needed & TRACKER_EVERYBODY) return true;

		// item creator
		if (!$check_only_tracker && $needed & TRACKER_ITEM_CREATOR && $this->user == $this->data['tr_creator'])
		{
			return true;
		}
		// item group
		if (!$check_only_tracker && $needed & TRACKER_ITEM_GROUP && in_array($this->data['tr_group'],$GLOBALS['egw']->accounts->memberships($this->user,true)))
		{
			return true;
		}
		// non-anonymous tracker user
		if ($needed & TRACKER_USER && isset($GLOBALS['egw_info']['user']['apps']['tracker']) && !$this->is_anonymous())
		{
			return true;
		}
		// tracker admins and technicians
		if ($tracker)
		{
			if ($needed & TRACKER_ADMIN && $this->is_admin($tracker))
			{
				return true;
			}
			if ($needed & TRACKER_TECHNICIAN && $this->is_technician($tracker))
			{
				return true;
			}
		}
		// new items: everyone is the owner of new items
		if (!$check_only_tracker && !$this->data['tr_id'])
		{
			return !!($needed & (TRACKER_ITEM_CREATOR|TRACKER_ITEM_NEW));
		}
		// assignee
		if (!$check_only_tracker && ($needed & TRACKER_ITEM_ASSIGNEE) && $this->data['tr_assigned'])
		{
			foreach((array)$this->data['tr_assigned'] as $assignee)
			{
				if ($this->user == $assignee) return true;
				// group assinged
				if ($this->allow_assign_groups && $assignee < 0)
				{
					if (($members = $GLOBALS['egw']->accounts->members($assignee,true)) && in_array($this->user,$members)) return true;
				}
			}
		}
		return false;
	}

	/**
	 * Check if users is allowed to vote and has not already voted
	 *
	 * @param int $tr_id=null tracker-id, default current tracker-item ($this->data)
	 * @return int/boolean true for no rights, timestamp voted or null
	 */
	function check_vote($tr_id=null)
	{
		if (is_null($tr_id)) $tr_id = $this->data['tr_id'];

		if (!$tr_id || !$this->check_rights($this->field_acl['vote'])) return true;

		if ($this->is_anonymous())
		{
			$ip = $_SERVER['REMOTE_ADDR'].(isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? ':'.$_SERVER['HTTP_X_FORWARDED_FOR'] : '');
		}
		if (($time = parent::check_vote($tr_id,$this->user,$ip)))
		{
			$time += $this->tz_offset_s;
		}
		return $time;
	}

	/**
	 * Cast vote for given tracker-item
	 *
	 * @param int $tr_id=null tracker-id, default current tracker-item ($this->data)
	 * @return boolean true=vote casted, false=already voted before
	 */
	function cast_vote($tr_id=null)
	{
		if (is_null($tr_id)) $tr_id = $this->data['tr_id'];

		if ($this->check_vote($tr_id)) return false;

		$ip = $_SERVER['REMOTE_ADDR'].(isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? ':'.$_SERVER['HTTP_X_FORWARDED_FOR'] : '');

		return parent::cast_vote($tr_id,$this->user,$ip);
	}

	/**
	 * Get tracker specific labels: tracker, version, categorie
	 *
	 * The labels are saved as categories and can be tracker specific (sub-cat of the tracker) or for all trackers.
	 * The "cat_data" column stores if a tracker-cat is a "tracker", "version", "cat" or empty
	 *
	 * @param string $type='trackers' 'tracker', 'version', 'cat'
	 * @param int $tracker=null tracker to use of null to use $this->data['tr_tracker']
	 */
	function get_tracker_labels($type='tracker',$tracker=null)
	{
		if (is_null($this->all_cats))
		{
			if (!isset($GLOBALS['egw']->categories))
			{
				$GLOBALS['egw']->categories = new categories($this->user,'tracker');
			}
			if (isset($GLOBALS['egw']->categories) && $GLOBALS['egw']->categories->app_name == 'tracker')
			{
				$cats = $GLOBALS['egw']->categories;
			}
			else
			{
				$cats = new categories($this->user,'tracker');
			}
			$this->all_cats = $cats->return_array('all',0,false);
			if (!is_array($this->all_cats)) $this->all_cats = array();
			//_debug_array($this->all_cats);
		}
		if (!$tracker) $tracker = $this->data['tr_tracker'];

		$labels = array();
		foreach($this->all_cats as $cat)
		{
			$cat_data = unserialize($cat['data']);
			$cat_type = isset($cat_data['type']) ? $cat_data['type'] : 'cat';
			if ($cat_type == $type && ($cat['parent'] == 0 || $cat['main'] == $tracker && $cat['id'] != $tracker))
			{
				$labels[$cat['id']] = $cat['name'];
			}
		}
		$this->load_config();
		if ($type == 'tracker' && !$GLOBALS['egw_info']['user']['apps']['admin'] && $this->enabled_queue_acl_access)
		{
			foreach ($labels as $tracker_id => $tracker_name)
			{
				if (!$this->is_user($tracker_id,$this->user) && !$this->is_technician($tracker_id,$this->user) && !$this->is_admin($tracker_id,$this->user))
				{
					unset($labels[$tracker_id]);
				}
			}
		}

		natcasesort($labels);

		//echo "botracker::get_tracker_labels('$type',$tracker)"; _debug_array($labels);
		return $labels;
	}

	/**
	 * Get tracker specific stati
	 *
	 * There's a bunch of pre-defined stati, plus statis stored as labels, which can be per tracker
	 *
	 * @param int $tracker=null tracker to use of null to use $this->data['tr_tracker']
	 */
	function get_tracker_stati($tracker=null)
	{
		return self::$stati + $this->get_tracker_labels('stati',$tracker);
	}

	/**
	 * Get tracker and category specific priorities
	 *
	 * Currently priorities are a fixed list with numeric values from 1 to 9 as keys and customizable labels
	 *
	 * @param int $tracker=null tracker to use or null to use tracker unspecific priorities
	 * @param int $cat_id=null category to use or null to use categorie unspecific priorities
	 * @param boolean $remove_empty=true should empty labels be displayed, default no
	 * @return array
	 */
	function get_tracker_priorities($tracker=null,$cat_id=null,$remove_empty=true)
	{
		if (isset($this->priorities[$tracker.'-'.$cat_id]))
		{
			$prios = $this->priorities[$tracker.'-'.$cat_id];
		}
		elseif (isset($this->priorities[$tracker]))
		{
			$prios = $this->priorities[$tracker];
		}
		elseif (isset($this->priorities['0-'.$cat_id]))
		{
			$prios = $this->priorities['0-'.$cat_id];
		}
		elseif(isset($this->priorities[0]))
		{
			$prios = $this->priorities[0];
		}
		else
		{
			$prios = self::$stock_priorities;
		}
		if ($remove_empty)
		{
			foreach($prios as $key => $val)
			{
				if ($val === '') unset($prios[$key]);
			}
		}
		//echo "<p>".__METHOD__."(tracker=$tracker,$remove_empty) prios=".array2string($prios)."</p>\n";
		return $prios;
	}

	/**
	 * Check if the given tracker uses category specific priorities and eg. need to reload of user changes the cat
	 *
	 * @param int $tracker
	 * @return boolean
	 */
	function tracker_has_cat_specific_priorities($tracker)
	{
		if (!$this->priorities) return false;

		$prefix = (int)$tracker.'-';
		$len = strlen($prefix);
		foreach(array_keys($this->priorities) as $key)
		{
			if (substr($key,0,$len) == $prefix || substr($key,0,2) == '0-') return true;
		}
		return false;
	}

	/**
	 * Reload the labels (tracker, cats, versions, projects)
	 *
	 */
	function reload_labels()
	{
		unset($this->all_cats);
		$this->trackers = $this->get_tracker_labels();
	}

	/**
	 * Get the canned response via it's id
	 *
	 * Canned responses are now saved in the the data array, as the description is limited to 255 chars, which is to small.
	 *
	 * @param int $id
	 * @return string/boolean string with the response or false if id not found
	 */
	function get_canned_response($id)
	{
		foreach($this->all_cats as $cat)
		{
			if (($data = unserialize($cat['data'])) && $data['type'] == 'response' && $cat['id'] == $id)
			{
				return $data['response'] ? $data['response'] : $cat['description'];
			}
		}
		return false;
	}

	/**
	 * Try to autoassign to a new tracker item
	 *
	 * @return int/boolean account_id or false
	 */
	function autoassign()
	{
		foreach($this->all_cats as $cat)
		{
			if ($cat['id'] == $this->data['cat_id'])
			{
				$data = unserialize($cat['data']);
				$user = $data['autoassign'];

				if ($user && $this->is_technician($this->data['tr_tracker'],$user,true))
				{
					return $this->data['tr_assigned'] = $user;
				}
			}
		}
		return false;
	}

	/**
	 * get title for an tracker item identified by $entry
	 *
	 * Is called as hook to participate in the linking
	 *
	 * @param int/array $entry int ts_id or array with tracker item
	 * @return string/boolean string with title, null if tracker item not found, false if no perms to view it
	 */
	function link_title( $entry )
	{
		if (!is_array($entry))
		{
			$entry = $this->read( $entry );
		}
		if (!$entry)
		{
			return $entry;
		}
		return $this->trackers[$entry['tr_tracker']].' #'.$entry['tr_id'].': '.$entry['tr_summary'];
	}

	/**
	 * get titles for multiple tracker items
	 *
	 * Is called as hook to participate in the linking
	 *
	 * @param array $ids array with tracker id's
	 * @return array with titles, see link_title
	 */
	function link_titles( $ids )
	{
		$titles = array();
		if (($tickets = $this->search(array('tr_id' => $ids),'tr_id,tr_tracker,tr_summary')))
		{
			foreach($tickets as $ticket)
			{
				$titles[$ticket['tr_id']] = $this->link_title($ticket);
			}
		}
		// we assume all not returned tickets are not readable by the user, as we notify egw_link about each deleted ticket
		foreach($ids as $id)
		{
			if (!isset($titles[$id])) $titles[$id] = false;
		}
		return $titles;
	}

	/**
	 * query tracker for entries matching $pattern, we search only open entries
	 *
	 * Is called as hook to participate in the linking
	 *
	 * @param string $pattern pattern to search
	 * @return array with ts_id - title pairs of the matching entries
	 */
	function link_query( $pattern )
	{
		$result = array();
		foreach((array) $this->search($pattern,false,'tr_summary ASC','','%',false,'OR',false,array('tr_status != '. self::STATUS_DELETED)) as $item )
		{
			if ($item) $result[$item['tr_id']] = $this->link_title($item);
		}
		return $result;
	}

	/**
	 * query rows for the nextmatch widget
	 *
	 * @param array $query with keys 'start', 'search', 'order', 'sort', 'col_filter'
	 *	For other keys like 'filter', 'cat_id' you have to reimplement this method in a derived class.
	 * @param array &$rows returned rows/competitions
	 * @param array &$readonlys eg. to disable buttons based on acl, not use here, maybe in a derived class
	 * @param string $join='' sql to do a join, added as is after the table-name, eg. ", table2 WHERE x=y" or
	 *	"LEFT JOIN table2 ON (x=y)", Note: there's no quoting done on $join!
	 * @param boolean $need_full_no_count=false If true an unlimited query is run to determine the total number of rows, default false
	 * @return int total number of rows
	 */
	function get_rows($query,&$rows,&$readonlys,$join=true,$need_full_no_count=false)
	{
		return parent::get_rows($query,$rows,$readonlys,$join,$need_full_no_count);
	}

	/**
	 * Add a new tracker
	 *
	 * @param string $name
	 * @return int/boolean integer tracker-id on success or false otherwise
	 */
	function add_tracker($name)
	{
		$cats = new categories(categories::GLOBAL_ACCOUNT,'tracker');	// global cat!
		if ($name && ($id = $cats->add(array(
			'name'   => $name,
			'descr'  => 'tracker',
			'data'   => serialize(array('type' => 'tracker')),
			'access' => 'public',
		))))
		{
			$this->trackers[$id] = $name;
			return $id;
		}
		return false;
	}

	/**
	 * Delete a tracker include all items, categories, staff, ...
	 *
	 * @param int $tracker
	 * @return boolean true on success, false otherwise
	 */
	function delete_tracker($tracker)
	{
		if (!$tracker) return false;

		if (!is_object($this->historylog))
		{
			$this->historylog =& CreateObject('phpgwapi.historylog','tracker');
		}
		$ids = $this->query_list($this->table_name.'.tr_id','',array('tr_tracker' => $tracker));
		if ($ids) $this->historylog->delete($ids);

		$GLOBALS['egw']->categories->delete($tracker,true);
		$this->reload_labels();
		unset($this->admins[$tracker]);
		unset($this->technicians[$tracker]);
		unset($this->users[$tracker]);
		$this->delete(array('tr_tracker' => $tracker));
		$this->save_config();

		return true;
	}

	/**
	 * Save the tracker configuration stored in various class-vars
	 */
	function save_config()
	{
		foreach($this->config_names as $name)
		{
			#echo "<p>calling config::save_value('$name','{$this->$name}','tracker')</p>\n";

			config::save_value($name,$this->$name,'tracker');
		}
		self::set_async_job($this->pending_close_days > 0);

		$mailhandler = new tracker_mailhandler();
		$mailhandler->set_async_job($this->mailhandling[0]['interval']);
	}

	/**
	 * Load the tracker config into various class-vars
	 *
	 */
	function load_config()
	{
		$migrate_config = false;	// update old config-values, can be removed soon
		foreach(config::read('tracker') as $name => $value)
		{
			if (substr($name,0,13) == 'notification_')	// update old config-values, can be removed soon
			{
				$this->notification[0][substr($name,13)] = $value;
				config::save_value($name,null,'tracker');
				$migrate_config = true;
				continue;
			}
			$this->$name = $value;
		}
		if ($migrate_config)	// update old config-values, can be removed soon
		{
			foreach($this->notification as $name => $value)
			{
				config::save_value($name,$value,'tracker');
			}
		}
		if (!$this->notification[0]['lang']) $this->notification[0]['lang'] = $GLOBALS['egw']->preferences->default['common']['lang'];

		foreach(array(
			'tr_summary'     => TRACKER_ITEM_CREATOR|TRACKER_ITEM_ASSIGNEE|TRACKER_ADMIN,
			'tr_tracker'     => TRACKER_ITEM_NEW|TRACKER_ITEM_ASSIGNEE|TRACKER_ADMIN,
			'cat_id'         => TRACKER_ITEM_CREATOR|TRACKER_ITEM_ASSIGNEE|TRACKER_ADMIN,
			'tr_version'     => TRACKER_ITEM_CREATOR|TRACKER_ITEM_ASSIGNEE|TRACKER_ADMIN,
			'tr_status'      => TRACKER_ITEM_CREATOR|TRACKER_ITEM_ASSIGNEE|TRACKER_ADMIN,
			'tr_description' => TRACKER_ITEM_NEW,
			'tr_assigned'    => TRACKER_ITEM_CREATOR|TRACKER_ADMIN,
			'tr_private'     => TRACKER_ITEM_CREATOR|TRACKER_ITEM_ASSIGNEE|TRACKER_ADMIN,
			'tr_budget'      => TRACKER_ITEM_ASSIGNEE|TRACKER_ADMIN,
			'tr_resolution'  => TRACKER_ITEM_ASSIGNEE|TRACKER_ADMIN,
			'tr_completion'  => TRACKER_ITEM_ASSIGNEE|TRACKER_ADMIN,
			'tr_priority'    => TRACKER_ITEM_CREATOR|TRACKER_ITEM_ASSIGNEE|TRACKER_ADMIN,
			'tr_cc'			 => TRACKER_ITEM_CREATOR|TRACKER_ITEM_ASSIGNEE|TRACKER_ADMIN,
			'tr_group'		 => TRACKER_TECHNICIAN|TRACKER_ADMIN,
			'customfields'   => TRACKER_ITEM_CREATOR|TRACKER_ITEM_ASSIGNEE|TRACKER_ADMIN,
			// set automatic by botracker::save()
			'tr_id'          => 0,
			'tr_creator'     => 0,
			'tr_created'     => 0,
			'tr_modifier'    => 0,
			'tr_modified'    => 0,
			'tr_closed'      => 0,
			// pseudo fields used in edit
			'link_to'        => TRACKER_ITEM_CREATOR|TRACKER_ITEM_ASSIGNEE|TRACKER_ADMIN,
			'canned_response' => TRACKER_ITEM_ASSIGNEE|TRACKER_ADMIN,
			'reply_message'  => TRACKER_USER,
			'add'            => TRACKER_USER,
			'vote'           => TRACKER_EVERYBODY,	// TRACKER_USER for NO anon user
			'bounty'         => TRACKER_EVERYBODY,
			'no_notifications'	=> TRACKER_ITEM_ASSIGNEE|TRACKER_TECHNICIAN|TRACKER_ADMIN,
		) as $name => $value)
		{
			if (!isset($this->field_acl[$name])) $this->field_acl[$name] = $value;
		}
	}

	/**
	 * Check if exist and if not start or stop an async job to close pending items
	 *
	 * @param boolean $start=true true=start, false=stop
	 */
	static function set_async_job($start=true)
	{
		//echo '<p>'.__METHOD__.'('.($start?'true':'false').")</p>\n";

		$async = new asyncservice();

		if ($start === !$async->read('tracker-close-pending'))
		{
			if ($start)
			{
				$async->set_timer(array('hour' => '*'),'tracker-close-pending','tracker.tracker_bo.close_pending',null);
			}
			else
			{
				$async->cancel_timer('tracker-close-pending');
			}
		}
	}

	/**
	 * Close pending tracker items, which are not answered withing $this->pending_close_days days
	 */
	function close_pending()
	{
		$this->user = 0;	// we dont want to run under the id of the current or the user created the async job

		if (($ids = $this->query_list('tr_id','tr_id',array(
			'tr_status' => self::STATUS_PENDING,
			'tr_modified < '.(time()-$this->pending_close_days*24*60*60),
		))))
		{
			if ($GLOBALS['egw']->preferences->default['common']['lang'] &&	// load the system default language
				$GLOBALS['egw']->translation->user_lang != $GLOBALS['egw']->preferences->default['common']['lang'])
			{
				$save_lang = $GLOBALS['egw_info']['user']['preferences']['common']['lang'];
				$GLOBALS['egw_info']['user']['preferences']['common']['lang'] = $GLOBALS['egw']->preferences->default['common']['lang'];
				$GLOBALS['egw']->translation->init();
			}
			$GLOBALS['egw']->translation->add_app('tracker');

			foreach($ids as $tr_id)
			{
				if ($this->read($tr_id))
				{
					$this->data['tr_status'] = self::STATUS_CLOSED;
					$this->data['reply_message'] = lang('This Tracker item was closed automatically by the system. It was previously set to a Pending status, and the original submitter did not respond within %1 days.',$this->pending_close_days);
					$this->save();
				}
			}
			if ($save_lang)
			{
				$GLOBALS['egw_info']['user']['preferences']['common']['lang'] = $save_lang;
				$GLOBALS['egw']->translation->init();
			}
		}
	}

	/**
	 * Read bounties specified by the given keys
	 *
	 * Reimplement to convert to user-time
	 *
	 * @param array/int $keys array with key(s) or integer bounty-id
	 * @return array with bounties
	 */
	function read_bounties($keys)
	{
		if (!$this->allow_bounties) return array();

		if (($bounties = parent::read_bounties($keys)))
		{
			foreach($bounties as $n => $bounty)
			{
				foreach(array('bounty_created','bounty_confirmed') as $name)
				{
					if ($bounty[$name]) $bounties[$n][$name] += $this->tz_offset_s;
				}
			}
		}
		return $bounties;
	}

	/**
	 * Save or update a bounty
	 *
	 * @param array &$data
	 * @return int/boolean integer bounty_id or false on error
	 */
	function save_bounty(&$data)
	{
		if (!$this->allow_bounties) return false;

		if (($new = !$data['bounty_id']))	// new bounty
		{
			if (!$data['bounty_amount'] || !$data['bounty_name'] || !$data['bounty_email']) return false;

			$data['bounty_creator'] = $this->user;
			$data['bounty_created'] = $this->now;
			if (!$data['tr_id']) $data['tr_id'] = $this->data['tr_id'];
		}
		else
		{
			if (!$this->is_admin($this->data['tr_tracker']) ||
				!($bounties = $this->read_bounties(array('bounty_id' => $data['bounty_id']))))
			{
				return false;
			}
			$old = $bounties[0];

			$data['bounty_confirmer'] = $this->user;
			$data['bounty_confirmed'] = $this->now;
		}
		// convert to server-time
		foreach(array('bounty_created','bounty_confirmed') as $name)
		{
			if ($data[$name]) $data[$name] -= $this->tz_offset_s;
		}
		if (($data['bounty_id'] = parent::save_bounty($data)))
		{
			$this->_bounty2history($data,$old);
		}
		// convert back to user-time
		foreach(array('bounty_created','bounty_confirmed') as $name)
		{
			if ($data[$name]) $data[$name] += $this->tz_offset_s;
		}
		return $data['bounty_id'];
	}

	/**
	 * Delete a bounty, the bounty must not be confirmed and you must be an tracker-admin!
	 *
	 * @param int $bounty_id
	 * @return boolean true on success or false otherwise
	 */
	function delete_bounty($id)
	{
		//echo "<p>botracker::delete_bounty($id)</p>\n";
		if (!($bounties = $this->read_bounties(array('bounty_id' => $id))) ||
			$bounties[0]['bounty_confirmed'] || !$this->is_admin($this->data['tr_tracker']))
		{
			return false;
		}
		if (parent::delete_bounty($id))
		{
			$this->_bounty2history(null,$bounties[0]);

			return true;
		}
		return false;
	}

	/**
	 * Historylog a bounty
	 *
	 * @internal
	 * @param array $new new value
	 * @param array $old=null old value
	 */
	function _bounty2history($new,$old=null)
	{
		if (!is_object($this->historylog))
		{
			$this->historylog =& CreateObject('phpgwapi.historylog','tracker');
		}
		if (is_null($new) && $old)
		{
			$status = 'xb';	// bounty deleted
		}
		elseif ($new['bounty_confirmed'])
		{
			$status = 'Bo';	// bounty confirmed
		}
		else
		{
			$status = 'bo';	// bounty set
		}
		$this->historylog->add($status,$this->data['tr_id'],$this->_serialize_bounty($new),$this->_serialize_bounty($old));
	}

	/**
	 * Serialize the bounty for the historylog
	 *
	 * @internal
	 * @param array $bounty
	 * @return string
	 */
	function _serialize_bounty($bounty)
	{
		return !is_array($bounty) ? $bounty : '#'.$bounty['bounty_id'].', '.$bounty['bounty_name'].' <'.$bounty['bounty_email'].
			'> ('.$GLOBALS['egw']->accounts->id2name($bounty['bounty_creator']).') '.
			$bounty['bounty_amount'].' '.$this->currency.($bounty['bounty_confirmed'] ? ' Ok' : '');
	}
}
