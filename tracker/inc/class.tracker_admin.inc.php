<?php
/**
 * Tracker - Universal tracker (bugs, feature requests, ...) - Admin Interface
 *
 * @link http://www.egroupware.org
 * @author Ralf Becker <RalfBecker-AT-outdoor-training.de>
 * @package tracker
 * @copyright (c) 2006-10 by Ralf Becker <RalfBecker-AT-outdoor-training.de>
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @version $Id: class.tracker_admin.inc.php 31661 2010-08-12 06:26:02Z ralfbecker $
 */

/**
 * Admin User Interface of the tracker
 */
class tracker_admin extends tracker_bo
{
	/**
	 * Functions callable via menuaction
	 *
	 * @var array
	 */
	var $public_functions = array(
		'admin' => true,
		'escalations' => true,
	);
	/**
	 * reference to the preferences of the user
	 *
	 * @var array
	 */
	var $prefs;

	/**
	 * Constructor
	 *
	 * @return tracker_admin
	 */
	function __construct()
	{
		// check if user has admin rights and bail out if not
		if (!$GLOBALS['egw_info']['user']['apps']['admin'])
		{
			$GLOBALS['egw']->framework->render('<h1 style="color: red;">'.lang('Permission denied !!!')."</h1>\n",null,true);
			return;
		}
		parent::__construct();

		$this->prefs =& $GLOBALS['egw_info']['user']['preferences']['tracker'];
	}

	/**
	 * Site configuration
	 *
	 * @param array $content=null
	 * @return string
	 */
	function admin($content=null,$msg='')
	{
		//_debug_array($content);
		$tabs = 'cats|priorities|staff|config|mail';

		$tracker = (int) $content['tracker'];

		// apply preferences for assigning of defaultprojects, and provide the project list
		if ($this->prefs['allow_defaultproject'] && $tracker)
		{
			$allow_defaultproject = $this->prefs['allow_defaultproject'];
		}

		if (is_array($content))
		{
			list($button) = @each($content['button']);

			switch($button)
			{
				case 'add':
					if (!$content['add_name'])
					{
						$msg = lang('You need to enter a name');
					}
					elseif (($id = $this->add_tracker($content['add_name'])))
					{
						$tracker = $id;
						$msg = lang('Tracker added');
					}
					else
					{
						$msg = lang('Error adding the new tracker!');
					}
					break;

				case 'delete':
					if ($tracker && isset($this->trackers[$tracker]))
					{
						$this->delete_tracker($tracker);
						$tracker = 0;
						$msg = lang('Tracker deleted');
					}
					break;

				case 'apply':
				case 'save':
					$need_update = false;
					if (!$tracker)	// tracker unspecific config
					{
						foreach(array_diff($this->config_names,array('field_acl','technicians','admins','users','restrictions','notification','mailhandling','priorities')) as $name)
						{
							if (in_array($name,array('overdue_days','pending_close_days')) &&
								$content[$name] === '')
							{
								$content[$name] = '0';	// otherwise it does NOT get stored
							}
							if ((string) $this->$name !== $content[$name])
							{
								$this->$name = $content[$name];
								$need_update = true;
							}
						}
						// field_acl
						foreach($content['field_acl'] as $row)
						{
							$rights = 0;
							foreach(array(
								'TRACKER_ADMIN'         => TRACKER_ADMIN,
								'TRACKER_TECHNICIAN'    => TRACKER_TECHNICIAN,
								'TRACKER_USER'          => TRACKER_USER,
								'TRACKER_EVERYBODY'     => TRACKER_EVERYBODY,
								'TRACKER_ITEM_CREATOR'  => TRACKER_ITEM_CREATOR,
								'TRACKER_ITEM_ASSIGNEE' => TRACKER_ITEM_ASSIGNEE,
								'TRACKER_ITEM_NEW'      => TRACKER_ITEM_NEW,
								'TRACKER_ITEM_GROUP'    => TRACKER_ITEM_GROUP,
							) as $name => $right)
							{
								if ($row[$name]) $rights |= $right;
							}
							if ($this->field_acl[$row['name']] != $rights)
							{
								//echo "<p>$row[name] / $row[label]: rights: ".$this->field_acl[$row['name']]." => $rights</p>\n";
								$this->field_acl[$row['name']] = $rights;
								$need_update = true;
							}
						}
					}
					// tracker specific config and mail handling
					foreach(array('technicians','admins','users','notification','restrictions','mailhandling') as $name)
					{
						$staff =& $this->$name;
						if (!isset($staff[$tracker])) $staff[$tracker] = array();
						if (!isset($content[$name])) $content[$name] = array();

						if ($staff[$tracker] != $content[$name])
						{
							$staff[$tracker] = $content[$name];
							$need_update = true;
						}
					}
					// build the (normalized!) priority array
					$prios = array();
					foreach($content['priorities'] as $value => $data)
					{
						if ($value == 'cat_id')
						{
							$cat_id = $data;
							continue;
						}
						$value = (int) $data['value'];
						$prios[(int)$value] = (string)$data['label'];
					}
					if(!array_diff($prios,array('')))	// user deleted all label --> use the one from the next level above
					{
						$prios = null;
					}
					// priorities are only stored if they differ from the stock-priorities or the default chain of get_tracker_priorities()
					if ($prios !== $this->get_tracker_priorities($tracker,$cat_id,false))
					{
						$key = (int)$tracker;
						if ($cat_id) $key .= '-'.$cat_id;
						if (is_null($prios))
						{
							unset($this->priorities[$key]);
						}
						else
						{
							$this->priorities[$key] = $prios;
						}
						$need_update = true;
					}
					if ($need_update)
					{
						$this->save_config();
						$msg = lang('Configuration updated.').' ';
					}
					$need_update = false;
					foreach(array(
						'cats'      => lang('Category'),
						'versions'  => lang('Version'),
						'projects'  => lang('Projects'),
						'statis'    => lang('Stati'),
						'responses' => lang('Canned response'),
					) as $name => $what)
					{
						foreach($content[$name] as $cat)
						{
							//_debug_array($cat);
							if (!$cat['name']) continue;	// ignore empty (new) cats

							$new_cat_descr = 'tracker-';
							switch($name)
							{
								case 'cats':
									$new_cat_descr .= 'cat';
									break;
								case 'versions':
									$new_cat_descr .= 'version';
									break;
								case 'statis':
									$new_cat_descr .= 'stati';
									break;
								case 'projects':
									$new_cat_descr .= 'project';
									break;
							}
							$old_cat = array(	// some defaults for new cats
								'main'   => $tracker,
								'parent' => $tracker,
								'access' => 'public',
								'data'   => array('type' => substr($name,0,-1)),
								'description'  => $new_cat_descr,
							);
							// search cat in existing ones
							foreach($this->all_cats as $c)
							{
								if ($cat['id'] == $c['id'])
								{
									$old_cat = $c;
									$old_cat['data'] = unserialize($old_cat['data']);
									break;
								}
							}
							// check if new cat or changed, in case of projects the id and a free name is stored
							if (!$old_cat || $cat['name'] != $old_cat['name'] ||
								$name == 'cats' && (int)$cat['autoassign'] != (int)$old_cat['data']['autoassign'] ||
								$name == 'projects' && (int)$cat['projectlist'] != (int)$old_cat['data']['projectlist'] ||
								$name == 'responses' && $cat['description'] != $old_cat['data']['response'])
							{
								$old_cat['name'] = $cat['name'];
								switch($name)
								{
									case 'cats':
										$old_cat['data']['autoassign'] = $cat['autoassign'];
										break;
									case 'projects':
										$old_cat['data']['projectlist'] = $cat['projectlist'];
										break;
									case 'responses':
										$old_cat['data']['response'] = $cat['description'];
										break;
								}
								//echo "update to"; _debug_array($old_cat);
								if (!isset($cats))
								{
									$cats = new categories(categories::GLOBAL_ACCOUNT,'tracker');
								}
								if (($id = $cats->add($old_cat)))
								{
									$msg .= $old_cat['id'] ? lang("Tracker-%1 '%2' updated.",$what,$cat['name']) : lang("Tracker-%1 '%2' added.",$what,$cat['name']);
									$need_update = true;
								}
							}
						}
					}
					if ($need_update)
					{
						$this->reload_labels();
					}
					if ($button == 'apply') break;
					// fall-through for save
				case 'cancel':
					$GLOBALS['egw']->redirect_link('/index.php',array(
						'menuaction' => 'tracker.tracker_ui.index',
						'msg' => $msg,
					));
					break;

				default:

					foreach(array(
						'cats'      => lang('Category'),
						'versions'  => lang('Version'),
						'projects'  => lang('Projects'),
						'statis'    => lang('State'),
						'responses' => lang('Canned response'),
					) as $name => $what)
					{
						if (isset($content[$name]['delete']))
						{
							list($id) = each($content[$name]['delete']);
							if ((int)$id)
							{
								$GLOBALS['egw']->categories->delete($id);
								$msg = lang('Tracker-%1 deleted.',$what);
								$this->reload_labels();
							}
						}
					}
					break;
			}

		}
		$content = array(
			'msg' => $msg,
			'tracker' => $tracker,
			'admins' => $this->admins[$tracker],
			'technicians' => $this->technicians[$tracker],
			'users' => $this->users[$tracker],
			'notification' => $this->notification[$tracker],
			'restrictions' => $this->restrictions[$tracker],
			'mailhandling' => $this->mailhandling[$tracker],
			$tabs => $content[$tabs],
			// keep priority cat only if tracker is unchanged, otherwise reset it
			'priorities' => $tracker == $content['tracker'] ? array('cat_id' => $content['priorities']['cat_id']) : array(),
		);

		foreach(array_diff($this->config_names,array('admins','technicians','users','notification','restrictions','mailhandling','priorities')) as $name)
		{
			$content[$name] = $this->$name;
		}
		// cats & versions & responses & projects
		$v = $c = $r = $s = $p = 1;
		usort($this->all_cats,create_function('$a,$b','return strcasecmp($a["name"],$b["name"]);'));
		foreach($this->all_cats as $cat)
		{
			if (!is_array($data = unserialize($cat['data']))) $data = array('type' => $data);
			//echo "<p>$cat[name] ($cat[id]/$cat[parent]/$cat[main]): ".print_r($data,true)."</p>\n";

			if ($cat['parent'] == $tracker && $data['type'] != 'tracker')
			{
				switch ($data['type'])
				{
					case 'version':
						$content['versions'][$v++] = $cat + $data;
						break;
					case 'response':
						if ($data['response']) $cat['description'] = $data['response'];
						$content['responses'][$r++] = $cat;
						break;
					case 'project':
						$content['projects'][$p++] = $cat + $data;
						break;
					case 'stati':
						$content['statis'][$s++] = $cat + $data;
						break;
					default:	// cat
						$data['type'] = 'cat';
						$content['cats'][$c++] = $cat + $data;
						break;
				}
			}
		}
		$content['versions'][$v++] = $content['cats'][$c++] = $content['responses'][$r++] = $content['projects'][$p++] = $content['statis'][$s++] =
			array('id' => 0,'name' => '');	// one empty line for adding
		// field_acl
		$f = 1;
		foreach($this->field2label as $name => $label)
		{
			if (in_array($name,array('tr_creator','num_replies'))) continue;

			$rights = $this->field_acl[$name];
			$content['field_acl'][$f++] = array(
				'label'                 => $label,
				'name'                  => $name,
				'TRACKER_ADMIN'         => !!($rights & TRACKER_ADMIN),
				'TRACKER_TECHNICIAN'    => !!($rights & TRACKER_TECHNICIAN),
				'TRACKER_USER'          => !!($rights & TRACKER_USER),
				'TRACKER_EVERYBODY'     => !!($rights & TRACKER_EVERYBODY),
				'TRACKER_ITEM_CREATOR'  => !!($rights & TRACKER_ITEM_CREATOR),
				'TRACKER_ITEM_ASSIGNEE' => !!($rights & TRACKER_ITEM_ASSIGNEE),
				'TRACKER_ITEM_NEW'      => !!($rights & TRACKER_ITEM_NEW),
				'TRACKER_ITEM_GROUP'    => !!($rights & TRACKER_ITEM_GROUP),
			);
		}
		$this->enabled_queue_acl_access ? $queue_access_enabled_label = lang("Enabled") : $queue_access_enabled_label = lang("Disabled");
		$content['queue_access_enabled_label'] = lang('Users').': '.lang('Restriction')." ".$queue_access_enabled_label;
		$content['queue_access_enabled_label_help'] = lang('You can enable/disable the queue access restrictions in the configuration tab (for all queues)');

		$n = 2;	// cat selection + table header
		foreach($this->get_tracker_priorities($tracker,$content['priorities']['cat_id'],false) as $value => $label)
		{
			$content['priorities'][$n++] = array(
				'value' => self::$stock_priorities[$value],
				'label' => $label,
			);
		}
		//_debug_array($content);
		if ($allow_defaultproject)	$content['allow_defaultproject'] = $this->prefs['allow_defaultproject'];
		$sel_options = array(
			'tracker' => &$this->trackers,
			'allow_assign_groups' => array(
				0 => lang('No'),
				1 => lang('Yes, display groups first'),
				2 => lang('Yes, display users first'),
			),
			'allow_voting' => array('No','Yes'),
			'allow_bounties' => array('No','Yes'),
			'autoassign' => $this->get_staff($tracker),
			'lang' => $GLOBALS['egw']->translation->get_installed_langs(),
			'cat_id' => $this->get_tracker_labels('cat',$tracker),
			// Mail handling
			'servertype' => array(),
			'default_tracker' => &$this->trackers,
			// TODO; enable the default_trackers onChange() to reload categories
			'default_cat' => $this->get_tracker_labels('cat',$content['mailhandling']['default_tracker']),
			'unrec_reply' => array(
				0 => 'Creator',
				1 => 'Nobody',
			),
			'auto_reply' => array(
				0 => lang('Never'),
				1 => lang('Yes, new tickets only'),
				2 => lang('Yes, always'),
			),
			'reply_unknown' => array(
				0 => 'Creator',
				1 => 'Nobody',
			),
		);
		foreach($this->mailservertypes as $ind => $typ)
		{
			$sel_options['servertype'][] = $typ[1];
		}
		$readonlys = array(
			'button[delete]' => !$tracker,
			'delete[0]' => true,
		);
		$GLOBALS['egw_info']['flags']['app_header'] = lang('Tracker configuration').($tracker ? ': '.$this->trackers[$tracker] : '');
		$tpl = new etemplate('tracker.admin');
		return $tpl->exec('tracker.tracker_admin.admin',$content,$sel_options,$readonlys,$content);
	}

	/**
	 * Get escalation rows
	 *
	 * @param array $query
	 * @param array &$rows
	 * @param array &$readonlys
	 * @return int|boolean
	 */
	function get_rows($query,&$rows,&$readonlys)
	{
		$escalations = new tracker_escalations();
		$Ok = $escalations->get_rows($query,$rows,$readonlys);

		if ($rows)
		{
			foreach($rows as &$row)
			{
				// show the right tracker and/or cat specific priority label
				if ($row['tr_priority'])
				{
					if (is_null($prio_labels) || $row['tr_tracker'] != $prio_tracker || $row['cat_id'] != $prio_cat)
					{
						$prio_labels = $this->get_tracker_priorities($prio_tracker=$row['tr_tracker'],$prio_cat = $row['cat_id']);
					}
					$row['prio_label'] = $prio_labels[$row['tr_priority']];
				}
			}
		}
		return $Ok;
	}

	/**
	 * Define escalations
	 *
	 * @param array $content
	 * @param string $msg
	 */
	function escalations(array $content=null,$msg='')
	{
		$escalations = new tracker_escalations();

		if (!is_array($content))
		{
			$content['nm'] = array(
				'get_rows'       =>	'tracker.tracker_admin.get_rows',
				'no_cat'         => true,
				'no_filter2'=> true,
				'no_filter' => true,
				'order'          =>	'esc_time',
				'sort'           =>	'ASC',// IO direction of the sort: 'ASC' or 'DESC'
			);
		}
		else
		{
			//_debug_array($content);
			list($button) = @each($content['button']);
			unset($content['button']);
			$escalations->init($content);

			switch($button)
			{
				case 'save':
				case 'apply':
					if (($err = $escalations->not_unique()))
					{
						$msg = lang('There already an escalation for that filter!');
						$button = '';
					}
					elseif (($err = $escalations->save()) == 0)
					{
						$msg = $content['esc_id'] ? lang('Escalation saved.') : lang('Escalation added.');
					}
					if ($button == 'apply' || $err) break;
					// fall-through
				case 'cancel':
					$escalations->init();
					break;
			}
			if ($content['nm']['rows']['edit'])
			{
				list($id) = each($content['nm']['rows']['edit']);
				unset($content['nm']['rows']);
				if (!$escalations->read($id))
				{
					$msg = lang('Escalation not found!');
					$escalations->init();
				}
			}
			elseif($content['nm']['rows']['delete'])
			{
				list($id) = each($content['nm']['rows']['delete']);
				unset($content['nm']['rows']);
				if (!$escalations->delete(array('esc_id' => $id)))
				{
					$msg = lang('Error deleting escalation!');
				}
				else
				{
					$msg = lang('Escalation deleted.');
				}
			}
		}
		$content = $escalations->data + array(
			'nm' => $content['nm'],
			'msg' => $msg,
		);
		$preserv['esc_id'] = $content['esc_id'];
		$preserv['nm'] = $content['nm'];

		$tracker = $content['tr_tracker'];
		$sel_options = array(
			'tr_tracker'  => &$this->trackers,
			'cat_id'      => $this->get_tracker_labels('cat',$tracker),
			'tr_version'  => $this->get_tracker_labels('version',$tracker),
			'tr_priority' => $this->get_tracker_priorities($tracker,$content['cat_id']),
			'tr_status'   => $this->get_tracker_stati($tracker),
			'tr_assigned' => $this->get_staff($tracker,$this->allow_assign_groups),
			'esc_type'    => array(
				tracker_escalations::CREATION => lang('creation date'),
				tracker_escalations::MODIFICATION => lang('last modified'),
				tracker_escalations::REPLIED => lang('last reply'),
			),
		);
		$tpl = new etemplate('tracker.escalations');
		if ($content['set']['tr_assigned'] && !is_array($content['set']['tr_assigned']))
		{
			$content['set']['tr_assigned'] = explode(',',$content['set']['tr_assigned']);
		}
		if (count($content['set']['tr_assigned']) > 1)
		{
			$widget =& $tpl->get_widget_by_name('tr_assigned');	//$tpl->set_cell_attribute() sets all widgets with this name, so the action too!
			$widget['size'] = '3+';
		}
		if ($content['tr_status'] && !is_array($content['tr_status']))
		{
			$content['tr_status'] = explode(',',$content['tr_status']);
		}
		if (count($content['tr_status']) > 1)
		{
			$widget =& $tpl->get_widget_by_name('tr_status');
			$widget['size'] = '3+';
		}
		if ($this->tracker_has_cat_specific_priorities($tracker))
		{
			$widget =& $tpl->get_widget_by_name('cat_id');
			$widget['onchange'] = true;
		}
		$GLOBALS['egw_info']['flags']['app_header'] = lang('Tracker').' - '.lang('Define escalations');
		//_debug_array($content);
		return $tpl->exec('tracker.tracker_admin.escalations',$content,$sel_options,$readonlys,$preserv);
	}
}
