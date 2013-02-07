<?php
/**
 * eGroupWare Tracker - Escalation of tickets
 *
 * Sponsored by Hexagon Metrolegy (www.hexagonmetrology.net)
 *
 * @link http://www.egroupware.org
 * @author Ralf Becker <RalfBecker-AT-outdoor-training.de>
 * @package tracker
 * @copyright (c) 2008 by Ralf Becker <RalfBecker-AT-outdoor-training.de>
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @version $Id: class.tracker_escalations.inc.php 27222 2009-06-08 16:21:14Z ralfbecker $
 */

/**
 * Escalation of tickets
 */
class tracker_escalations extends so_sql2
{
	/**
	 * Name of escalations table
	 */
	const ESCALATIONS_TABLE = 'egw_tracker_escalations';
	/**
	 * Values for esc_type column
	 */
	const CREATION = 0;
	const MODIFICATION = 1;
	const REPLIED = 2;
	/**
	 * Fields not in the main table, which need to be merged or set
	 *
	 * @var array
	 */
	var $non_db_cols = array('set');

	/**
	 * Constructor
	 *
	 * @return tracker_ui
	 */
	function __construct($id = null)
	{
		parent::__construct('tracker',self::ESCALATIONS_TABLE,null,'',true);

		if (!is_null($id) && !$this->read($id))
		{
			throw new egw_exception_not_found();
		}
	}

	/**
	 * initializes data with the content of key
	 *
	 * @param array $keys array with keys in form internalName => value
	 * @return array internal data after init
	 */
	function init($keys=array())
	{
		$this->data = array(
			'tr_status' => -100,	// offen
		);
		$this->data_merge($keys);

		if (isset($keys['set']))
		{
			$this->data['set'] = $keys['set'];
		}
		return $this->data;
	}

	/**
	 * changes the data from the db-format to your work-format
	 *
	 * It gets called everytime when data is read from the db.
	 * This default implementation only converts the timestamps mentioned in $this->timestampfs from server to user time.
	 * You can reimplement it in a derived class
	 *
	 * @param array $data if given works on that array and returns result, else works on internal data-array
	 */
	function db2data($data=null)
	{
		if (!is_array($data))
		{
			$data = &$this->data;
		}
		if (isset($data['tr_status']) && strpos($data['tr_status'],',') !== false)
		{
			$data['tr_status'] = explode(',',$data['tr_status']);
		}
		foreach($data as $key => &$value)
		{
			if (substr($key,0,4) == 'esc_' && !in_array($key,array('esc_id','esc_title','esc_time','esc_type')))
			{
				if ($key == 'esc_tr_assigned')
				{
					$value = $value ? explode(',',$value) : array();
				}
				$data['set'][substr($key,4)] = $value;
				if (!is_null($value))
				{
					static $col2action;
					if (is_null($col2action))
					{
						$col2action = array(
							'esc_tr_priority' => lang('priority'),
							'esc_tr_tracker'  => lang('queue'),
							'esc_tr_status'   => lang('status'),
							'esc_cat_id'      => lang('category'),
							'esc_tr_version'  => lang('version'),
							'esc_tr_assigned' => lang('assigned to'),
						);
					}
					$action = lang('Set %1',$col2action[$key]).': ';
					switch($key)
					{
						case 'esc_tr_assigned':
							if ($data['esc_add_assigned']) $action = lang('Add assigned').': ';
							$users = array();
							foreach((array)$value as $uid)
							{
								$users[] = $GLOBALS['egw']->common->grab_owner_name($uid);
							}
							$action .= implode(', ',$users);
							break;
						case 'esc_add_assigned':
							continue 2;
						case 'esc_tr_priority':
							$priorities = ExecMethod('tracker.tracker_bo.get_tracker_priorities',$data['tr_tracker']);
							$action .= $priorities[$value];
							break;
						case 'esc_tr_status':
							if ($value < 0)
							{
								$action .= lang(tracker_bo::$stati[$value]);
								break;
							}
							// fall through for category labels
						case 'esc_cat_id':
						case 'esc_tr_version':
						case 'esc_tr_tracker':
							$action .= $GLOBALS['egw']->categories->id2name($cat_id);
							break;
						case 'esc_reply_message':
							$action = lang('Add comment').":\n".$value;
							break;
					}
					$actions[] = $action;
				}
				unset($data[$key]);
			}
		}
		if ($actions)
		{
			$data['esc_action_label'] = implode("\n",$actions);
		}
		return parent::db2data($data);
	}

	/**
	 * changes the data from your work-format to the db-format
	 *
	 * It gets called everytime when data gets writen into db or on keys for db-searches.
	 * This default implementation only converts the timestamps mentioned in $this->timestampfs from user to server time.
	 * You can reimplement it in a derived class
	 *
	 * @param array $data if given works on that array and returns result, else works on internal data-array
	 */
	function data2db($data=null)
	{
		if (!is_array($data))
		{
			$data = &$this->data;
		}
		if (isset($data['set']))
		{
			foreach($data['set'] as $key => $value)
			{
				$data['esc_'.$key] = is_array($value) ? implode(',',$value) : $value;
			}
			unset($data['set']);
		}
		if (is_array($data['tr_status']))
		{
			$data['tr_status'] = implode(',',$data['tr_status']);
		}
		return parent::db2data($data);
	}

	/**
	 * Get an SQL filter to include in a tracker search returning only matches of a given escalation
	 *
	 * @param boolean $due=false true = return only tickets due to escalate, default false = return all tickets matching the escalation filter
	 * @return array|boolean array with filter or false if escalation not found
	 */
	function get_filter($due=false)
	{
		$filter = array();

		if ($this->tr_tracker)  $filter['tr_tracker'] = $this->tr_tracker;
		if ($this->tr_status)   $filter['tr_status'] = $this->tr_status;
		if ($this->tr_priority) $filter['tr_priority'] = $this->tr_priority;
		if ($this->cat_id)      $filter['cat_id'] = $this->cat_id;
		if ($this->tr_version)  $filter['tr_version'] = $this->tr_version;

		if ($due)
		{
			//echo "<p>time=".time()."=".date('Y-m-d H:i:s').", esc_time=$this->esc_time, time()-esc_time*60=".(time()-$this->esc_time*60).'='.date('Y-m-d H:i:s',time()-$this->esc_time*60)."</p>\n";
			$filter[] = $this->get_time_col().' < '.(time()-$this->esc_time*60);
		}

		return $filter;
	}

	/**
	 * Get SQL (usable as extra column) of time relevant for the escalation
	 *
	 * @return string
	 */
	function get_time_col()
	{
		switch($this->esc_type)
		{
			default:
			case self::CREATION:
				return 'tr_created';
			case self::MODIFICATION:
				return 'tr_modified';
			case self::REPLIED:
				return "(SELECT MAX(reply_created) FROM egw_tracker_replies r WHERE r.tr_id = egw_tracker.tr_id)";
		}
	}

	/**
	 * Private tracker_bo instance to run the escalations
	 *
	 * @var tracker_bo
	 */
	private static $tracker;

	/**
	 * Escalate a given ticket, using this escalation
	 *
	 * @param int|array $ticket
	 */
	function escalate_ticket($ticket)
	{
		if (is_null(self::$tracker))
		{
			self::$tracker = new tracker_bo();
			self::$tracker->user = 0;
		}
		if (!is_array($ticket) && !($ticket = self::$tracker->read($ticket)))
		{
			return false;
		}
		foreach($this->set as $name => $value)
		{
			if (!is_null($value) && $value)
			{
				switch($name)
				{
					case 'add_assigned':
						break;
					case 'tr_assigned':
						if ($this->set['add_assigned'])
						{
							$ticket['tr_assigned'] = array_unique(array_merge($ticket['tr_assigned'],(array)$value));
							break;
						}
						// fall through for SET assigned
					default:
						$ticket[$name] = $value;
						break;
				}
			}
		}
		self::$tracker->init($ticket);

		if (self::$tracker->save() != 0)
		{
			return false;	// error saving the ticket
		}
		$this->db->insert(tracker_so::ESCALATED_TABLE,array(),array(
			'tr_id' =>  $ticket['tr_id'],
			'esc_id' => $this->id,
		),__LINE__,__FILE__,'tracker');

		return true;
	}

	/**
	 * Test and escalate all due tickets for this escalation
	 *
	 */
	function do_escalation()
	{
		if (is_null(self::$tracker))
		{
			self::$tracker = new tracker_bo();
			self::$tracker->user = 0;
		}
		// filter only due tickets
		$filter = $this->get_filter(true);
		// not having this escalation already done
		$filter[] = tracker_bo::escalated_filter($this->id,$join,false);

		if (($due_tickets = self::$tracker->search(array(),false,'esc_start',$this->get_time_col().' AS esc_start',
			'',false,'AND',false,$filter,$join)))
		{
			foreach($due_tickets as $ticket)
			{
				$this->escalate_ticket($ticket);
			}
		}
	}

	/**
	 * Async job running all escalations
	 *
	 */
	function do_all_escalations()
	{
		if (($escalations = $this->search(array(),false)))
		{
			foreach($escalations as $escalation)
			{
				$this->init($escalation);
				$this->do_escalation();
			}
		}
		else	// no escalations (any more) --> delete async job
		{
			self::set_async_job(false);
		}
	}

	const ASYNC_JOB_NAME = 'tracker-escalations';

	/**
	 * Check if exist and if not start or stop an async job to close pending items
	 *
	 * @param boolean $start=true true=start, false=stop
	 */
	static function set_async_job($start=true)
	{
		//echo '<p>'.__METHOD__.'('.($start?'true':'false').")</p>\n";

		$async = new asyncservice();

		if ($start === !$async->read(self::ASYNC_JOB_NAME))
		{
			if ($start)
			{
				$async->set_timer(array('min' => '*/5'),self::ASYNC_JOB_NAME,'tracker.tracker_escalations.do_all_escalations',null);
			}
			else
			{
				$async->cancel_timer(self::ASYNC_JOB_NAME);
			}
		}
	}

	/**
	 * Reimplemented save to start the async job
	 *
	 * @param array $keys
	 * @param array $extra_where
	 * @return int
	 */
	function save($keys=null,$extra_where=null)
	{
		self::set_async_job(true);

		return parent::save($keys,$extra_where);
	}
}
