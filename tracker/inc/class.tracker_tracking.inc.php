<?php
/**
 * Tracker - history and notifications
 *
 * @link http://www.egroupware.org
 * @author Ralf Becker <RalfBecker-AT-outdoor-training.de>
 * @package tracker
 * @copyright (c) 2006-10 by Ralf Becker <RalfBecker-AT-outdoor-training.de>
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @version $Id: class.tracker_tracking.inc.php 40098 2012-08-09 13:07:07Z ralfbecker $
 */

/**
 * Tracker - tracking object for the tracker
 */
class tracker_tracking extends bo_tracking
{
	/**
	 * Application we are tracking (required!)
	 *
	 * @var string
	 */
	var $app = 'tracker';
	/**
	 * Name of the id-field, used as id in the history log (required!)
	 *
	 * @var string
	 */
	var $id_field = 'tr_id';
	/**
	 * Name of the field with the creator id, if the creator of an entry should be notified
	 *
	 * @var string
	 */
	var $creator_field = 'tr_creator';
	/**
	 * Name of the field with the id(s) of assinged users, if they should be notified
	 *
	 * @var string
	 */
	var $assigned_field = 'tr_assigned';
	/**
	 * Translate field-name to 2-char history status
	 *
	 * @var array
	 */
	var $field2history = array();
	/**
	 * Should the user (passed to the track method or current user if not passed) be used as sender or get_config('sender')
	 *
	 * @var boolean
	 */
	var $prefer_user_as_sender = false;
	/**
	 * Instance of the botracker class calling us
	 *
	 * @access private
	 * @var tracker_bo
	 */
	var $tracker;

	/**
	 * Constructor
	 *
	 * @param tracker_bo $botracker
	 * @return tracker_tracking
	 */
	function __construct(&$botracker)
	{
		parent::__construct();	// calling the constructor of the extended class

		$this->tracker =& $botracker;
		$this->field2history =& $botracker->field2history;
	}

	/**
	 * Send an autoreply to the ticket creator or replier by the mailhandler
	 *
	 * @param array $data current entry
	 * @param array $autoreply values for:
	 *			'reply_text' => Texline to add to the mail message
	 *			'reply_to' => UserID or email address
	 * @param array $old=null old/last state of the entry or null for a new entry
	 */
	function autoreply($data,$autoreply,$old=null)
	{
		if (is_integer($autoreply['reply_to'])) // Mail from a known user
		{
			if ($this->notify_current_user)
			{
				return; // Already notified while saving
			}
			else
			{
				$this->notify_current_user = true; // Ensure send_notification() doesn't fail this check
			}
			$email = $GLOBALS['egw']->accounts->id2name($this->user,'account_email');
		}
		else
		{
			$email = $autoreply['reply_to']; // mail from an unknown user (set here, so we need to send a notification)
		}
		if ($autoreply['reply_text'])
		{
			$data['reply_text'] = $autoreply['reply_text'];
		}
		// Send notification to the creator only; assignee, CC etc have been notified already
		$this->send_notification($data,$old,$email,$data[$this->creator_field]);
	}
	
	/**
	 * Get a notification-config value
	 *
	 * @param string $what
	 * 	- 'copy' array of email addresses notifications should be copied too, can depend on $data
	 *  - 'lang' string lang code for copy mail
	 *  - 'sender' string send email address
	 * @param array $data current entry
	 * @param array $old=null old/last state of the entry or null for a new entry
	 * @return mixed
	 */
	function get_config($name,$data,$old=null)
	{
		$tracker = $data['tr_tracker'];

		$config = $this->tracker->notification[$tracker][$name] ? $this->tracker->notification[$tracker][$name] : $this->tracker->notification[0][$name];

		switch($name)
		{
			case 'copy':	// include the tr_cc addresses
				if ($data['tr_private']) return array();	// no copies for private entries
				$config = $config ? preg_split('/, ?/',$config) : array();
				if ($data['tr_cc'])
				{
					$config = array_merge($config,preg_split('/, ?/',$data['tr_cc']));
				}
				break;
		}
		return $config;
	}

	/**
	 * Get the subject for a given entry, reimplementation for get_subject in bo_tracking
	 *
	 * Default implementation uses the link-title
	 *
	 * @param array $data
	 * @param array $old
	 * @return string
	 */
	function get_subject($data,$old)
	{
		return $this->tracker->trackers[$data['tr_tracker']].' #'.$data['tr_id'].': '.$data['tr_summary'];
	}

	/**
	 * Get the modified / new message (1. line of mail body) for a given entry, can be reimplemented
	 *
	 * @param array $data
	 * @param array $old
	 * @return string
	 */
	function get_message($data,$old)
	{
		if (!$data['tr_modified'] || !$old)
		{
			return lang('New ticket submitted by %1 at %2',
				common::grab_owner_name($data['tr_creator']),
				$this->datetime($data['tr_created_servertime']));
		}
		return lang('Ticket modified by %1 at %2',
			$data['tr_modifier'] ? common::grab_owner_name($data['tr_modifier']) : lang('Tracker'),
			$this->datetime($data['tr_modified_servertime']));
	}

	/**
	 * Get the details of an entry
	 *
	 * @param array $data
	 * @param string $datetime_format of user to notify, eg. 'Y-m-d H:i'
	 * @param int $tz_offset_s offset in sec to be add to server-time to get the user-time of the user to notify
	 * @return array of details as array with values for keys 'label','value','type'
	 */
	function get_details($data)
	{
		static $cats,$versions,$statis,$priorities;
		if (!$cats)
		{
			$cats = $this->tracker->get_tracker_labels('cat',$data['tr_tracker']);
			$versions = $this->tracker->get_tracker_labels('version',$data['tr_tracker']);
			$statis = $this->tracker->get_tracker_stati($data['tr_tracker']);
			$priorities = $this->tracker->get_tracker_priorities($data['tr_tracker']);
		}
		if ($data['tr_assigned'])
		{
			foreach($data['tr_assigned'] as $uid)
			{
				$assigned[] = $GLOBALS['egw']->common->grab_owner_name($uid);
			}
			$assigned = implode(', ',$assigned);
		}
		if ($data['reply_text'])
		{
			$details['reply_text'] = array(
				'value' => $data['reply_text'],
				'type' => 'message',
			);
		}
		foreach(array(
			'tr_tracker'     => $this->tracker->trackers[$data['tr_tracker']],
			'cat_id'         => $cats[$data['cat_id']],
			'tr_version'     => $versions[$data['tr_version']],
			'tr_status'      => lang($statis[$data['tr_status']]),
			'tr_resolution'  => lang(tracker_bo::$resolutions[$data['tr_resolution']]),
			'tr_completion'  => (int)$data['tr_completion'].'%',
			'tr_priority'    => lang($priorities[$data['tr_priority']]),
			'tr_creator'     => $GLOBALS['egw']->common->grab_owner_name($data['tr_creator']),
			'tr_created'     => $this->datetime($data['tr_created']),
			'tr_assigned'	 => !$data['tr_assigned'] ? lang('Not assigned') : $assigned,
			'tr_cc'			 => $data['tr_cc'],
			// The layout of tr_summary should NOT be changed in order for
			// tracker.tracker_mailhandler.get_ticketId() to work!
			'tr_summary'     => '#'.$data['tr_id'].' - '.$data['tr_summary'],
		) as $name => $value)
		{
			$details[$name] = array(
				'label' => lang($this->tracker->field2label[$name]),
				'value' => $value,
			);
			if ($name == 'tr_summary') $details[$name]['type'] = 'summary';
		}
		// add custom fields for given type
		$details += $this->get_customfields($data, $data['tr_tracker']);

		$details['tr_description'] = array(
			'value' => $data['tr_description'],
			'type'  => 'multiline',
		);
		if ($data['replies'])
		{
			foreach($data['replies'] as $n => $reply)
			{
				$details[$n ? 2*$n : 'replies'] = array(	// first reply need to be checked against old to marked modified for new
					'value' => lang('Comment by %1 at %2:',$reply['reply_creator'] ? common::grab_owner_name($reply['reply_creator']) : lang('Tracker'),
						$this->datetime($reply['reply_servertime'])),
					'type'  => 'reply',
				);
				$details[2*$n+1] = array(
					'value' => $reply['reply_message'],
					'type'  => 'multiline',
				);
			}
		}
		return $details;
	}
}
