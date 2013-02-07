<?php
/**
 * Tracker - Universal tracker (bugs, feature requests, ...) with voting and bounties
 *
 * @link http://www.egroupware.org
 * @author Ralf Becker <RalfBecker-AT-outdoor-training.de>
 * @package tracker
 * @copyright (c) 2006-10 by Ralf Becker <RalfBecker-AT-outdoor-training.de>
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @version $Id: class.tracker_ui.inc.php 38113 2012-02-20 17:08:38Z nathangray $
 */

/**
 * User Interface of the tracker
 */
class tracker_ui extends tracker_bo
{
	/**
	 * Functions callable via menuaction
	 *
	 * @var array
	 */
	var $public_functions = array(
		'edit'  => true,
		'index' => true,
		'tprint'=> true,
	);
	/**
	 * Displayed instead of the '@' in email-addresses
	 *
	 * @var string
	 */
	var $mangle_at = ' -at- ';
	/**
	 * reference to the preferences of the user
	 *
	 * @var array
	 */
	var $prefs;

	/**
	 * allowed units and hours per day, can be overwritten by the projectmanager configuration, default all units, 8h
	 *
	 * @var string
	 */
	var $duration_format = ',';	// comma is necessary!

	/**
	 * Constructor
	 *
	 * @return tracker_ui
	 */
	function __construct()
	{
		parent::__construct();
		$this->prefs =& $GLOBALS['egw_info']['user']['preferences']['tracker'];

		// read the duration format from project-manager
		if ($GLOBALS['egw_info']['apps']['projectmanager'])
		{
			$pm_config = config::read('projectmanager');
			$this->duration_format = str_replace(',','',$pm_config['duration_units']).','.$pm_config['hours_per_workday'];
			unset($pm_config);
		}
	}

	/**
	 * Print a tracker item
	 *
	 * @param array $content=null eTemplate content
	 * @return string html-content, if sitemgr otherwise null
	 */
	function tprint($content=null)
	{
		// Check if exists
		if ((int)$_GET['tr_id'])
		{
			if (!$this->read($_GET['tr_id']))
			{
				return lang('Tracker item not found !!!');
			}
		}
		else	// new item
		{
			return lang('Tracker item not found !!!');
		}
		if (!is_object($this->tracking))
		{
			$this->tracking = new tracker_tracking($this);
		}

		if ($this->data['tr_edit_mode'] == 'html')
		{
			$this->tracking->html_content_allow = true;
		}

		$details = $this->tracking->get_body(true,$this->data,$this->data);
		if (!$details)
		{
			return implode(', ',$this->tracking->errors);
		}
		// Adding Automatic print func
		$GLOBALS['egw']->js->set_onload('window.print();');
		$GLOBALS['egw']->framework->render($details,'',false);
	}

	/**
	 * Edit a tracker item in a popup
	 *
	 * @param array $content=null eTemplate content
	 * @param string $msg=''
	 * @param boolean $popup=true use or not use a popup
	 * @return string html-content, if sitemgr otherwise null
	 */
	function edit($content=null,$msg='',$popup=true)
	{
		if ($this->htmledit)
		{
			$tr_description_options = 'simple,240px,100%,false';
			$tr_reply_options = 'simple,215px,100%,false';
		}
		else
		{
			$tr_description_options = 'ascii,230px,540px';
			$tr_reply_options = 'ascii,205px,540px';
		}

		//_debug_array($content);
		if (!is_array($content))
		{
			if ($_GET['msg']) $msg = strip_tags($_GET['msg']);

			// edit or new?
			if ((int)$_GET['tr_id'])
			{
				$own_referer = $GLOBALS['egw']->common->get_referer();
				if (!$this->read($_GET['tr_id']))
				{
					$msg = lang('Tracker item not found !!!');
					$this->init();
				}
				else
				{
					// Set the ticket as seen by this user
					$seen = self::seen($this->data, true);

					// editing, preventing/fixing mixed ascii-html
					if ($this->data['tr_edit_mode'] == 'ascii' && $this->htmledit)
					{
						// non html items edited by html (add nl2br)
						$tr_description_options = 'simple,240px,100%,false,,1';
					}
					if ($this->data['tr_edit_mode'] == 'html' && !$this->htmledit)
					{
						// html items edited in ascii mode (prevent changing to html)
						$tr_description_options = 'simple,240px,100%,false';
						$tr_reply_options = 'simple,215px,100%,false';
					}
					//echo "<p>data[tr_edit_mode]={$this->data['tr_edit_mode']}, this->htmledit=".array2string($this->htmledit)."</p>\n";
					// Ascii Replies are converted to html, if htmledit is disabled (default), we allways convert, as this detection is weak
					foreach ($this->data['replies'] as &$reply)
					{
						if (!$this->htmledit || stripos($reply['reply_message'], '<br') === false && stripos($reply['reply_message'], '<p>') === false)
						{
							$reply['reply_message'] = nl2br(html::htmlspecialchars($reply['reply_message']));
						}
					}
				}
			}
			else	// new item
			{
				$this->init();
			}
			// for new items we use the session-state or $_GET['tracker']
			if (!$this->data['tr_id'])
			{
				if (($state = egw_session::appsession('index','tracker'.
					(isset($this->trackers[(int)$_GET['only_tracker']]) ? '-'.$_GET['only_tracker'] : ''))))
				{
					$this->data['tr_tracker'] = $state['col_filter']['tr_tracker'];
					$this->data['cat_id']     = $state['filter'];
					$this->data['tr_version'] = $state['filter2'];
				}
				if (isset($this->trackers[(int)$_GET['tracker']]))
				{
					$this->data['tr_tracker'] = (int)$_GET['tracker'];
				}
				$this->data['tr_priority'] = 5;
			}
			if ($_GET['no_popup'] || $_GET['nopopup']) $popup = false;

			if ($popup)
			{
				$GLOBALS['egw_info']['flags']['java_script'] .= "<script>\nwindow.focus();\n</script>\n";
			}
			// check if user has rights to create new entries and fail if not
			if (!$this->data['tr_id'] && !$this->check_rights($this->field_acl['add']))
			{
				$msg = lang('Permission denied !!!');
				if ($popup)
				{
					$GLOBALS['egw']->framework->render('<h1 style="color: red;">'.$msg."</h1>\n",null,true);
					$GLOBALS['egw']->common->egw_exit();
				}
				else
				{
					unset($_GET['tr_id']);	// in case it's still set
					return $this->index(null,$this->data['tr_tracker'],$msg);
				}
				break;
			}
			// on resticted trackers, check if the user has read access, OvE, 20071012
			$restrict = false;
			if($this->data['tr_id'])
			{
				if(!$this->is_staff($this->data['tr_tracker']))
				{
					if($this->restrictions[($this->data['tr_tracker'])]['creator']
						&& $this->data['tr_creator'] != $this->user)
					{
						$restrict = true;
					}
					if($this->restrictions[($this->data['tr_tracker'])]['group']
						&& !(in_array($this->data['tr_group'], $GLOBALS['egw']->accounts->memberships($this->user,true))))
					{
						$restrict = true;
					}
					// Check queue access if enabled and that no has access to queue 0 (All)
					if ($this->enabled_queue_acl_access && !$this->trackers[$this->data['tr_tracker']] && !$this->is_user(0,$this->user))
					{
						$restrict = true;
					}
				}
			}
			if ($restrict)
			{
				$msg = lang('Permission denied !!!');
				if ($popup)
				{
					$GLOBALS['egw']->framework->render('<h1 style="color: red;">'.$msg."</h1>\n",null,true);
					$GLOBALS['egw']->common->egw_exit();
				}
				else
				{
					unset($_GET['tr_id']);	// in case it's still set
					return $this->index(null,$this->data['tr_tracker'],$msg);
				}
				break;
			}
		}
		else	// submitted form
		{
			//_debug_array($content);
			list($button) = @each($content['button']); unset($content['button']);
			if ($content['bounties']['bounty']) $button = 'bounty'; unset($content['bounties']['bounty']);
			$popup = $content['popup']; unset($content['popup']);
			$own_referer = $content['own_referer']; unset($content['own_referer']);

			$this->data = $content;
			unset($this->data['bounties']['new']);
			switch($button)
			{
				case 'save':
					if (!$this->data['tr_id'] && !$this->check_rights($this->field_acl['add']))
					{
						$msg = lang('Permission denied !!!');
						break;
					}

					$readonlys = $this->readonlys_from_acl();

					// Save Current edition mode preventing mixed types
					if ($this->data['tr_edit_mode'] == 'html' && !$this->htmledit)
					{
						$this->data['tr_edit_mode'] = 'html';
					}
					elseif ($this->data['tr_edit_mode'] == 'ascii' && $this->htmledit && $readonlys['tr_description'])
					{
						$this->data['tr_edit_mode'] = 'ascii';
					}
					else
					{
						$this->htmledit ? $this->data['tr_edit_mode'] = 'html' : $this->data['tr_edit_mode'] = 'ascii';
					}
					if ($this->save() == 0)
					{
						$msg = lang('Entry saved');

						//apply defaultlinks
						usort($this->all_cats,create_function('$a,$b','return strcasecmp($a["name"],$b["name"]);'));
						foreach($this->all_cats as $cat)
						{
							if (!is_array($data = unserialize($cat['data']))) $data = array('type' => $data);
							//echo "<p>".$this->data['tr_tracker'].": $cat[name] ($cat[id]/$cat[parent]/$cat[main]): ".print_r($data,true)."</p>\n";

							if ($cat['parent'] == $this->data['tr_tracker'] && $data['type'] != 'tracker' && $data['type']=='project')
							{
								if (!egw_link::get_link('tracker',$this->data['tr_id'],'projectmanager',$data['projectlist']))
								{
									egw_link::link('tracker',$this->data['tr_id'],'projectmanager',$data['projectlist']);
								}
							}
						}
						if (is_array($content['link_to']['to_id']) && count($content['link_to']['to_id']))
						{
							egw_link::link('tracker',$this->data['tr_id'],$content['link_to']['to_id']);
						}
						$state = egw_session::appsession('index','tracker'.($only_tracker ? '-'.$only_tracker : ''));
						$js = "opener.location.href=opener.location.href.replace(/&tr_id=[0-9]+/,'')+(opener.location.href.indexOf('?')<0?'?':'&')+'msg=".addslashes(urlencode($msg)).
							// only change to current tracker, if not all trackers displayed
							($state['col_filter']['tr_tracker'] ? '&tracker='.$this->data['tr_tracker'] : '')."';";
					}
					else
					{
						$msg = lang('Error saving the entry!!!');
						break;
					}
					// fall-through for save
				case 'cancel':
					if ($popup)
					{
						$js .= 'window.close();';
						echo "<html>\n<body>\n<script>\n$js\n</script>\n</body>\n</html>\n";
						$GLOBALS['egw']->common->egw_exit();
					}
					else
					{
						unset($_GET['tr_id']);	// in case it's still set
						if($own_referer) {
							// Go back to where you came from
							egw::redirect_link($own_referer);
						} else {
							return $this->index(null,$this->data['tr_tracker'],$msg);
						}
					}
					break;

				case 'vote':
					if ($this->cast_vote())
					{
						$msg = lang('Thank you for voting.');
						if ($popup)
						{
							$js = "opener.location.href=opener.location.href.replace(/&tr_id=[0-9]+/,'')+'&msg=".addslashes(urlencode($msg))."';";
							$GLOBALS['egw_info']['flags']['java_script'] .= "<script>\n$js\n</script>\n";
						}
					}
					break;

				case 'bounty':
					if (!$this->allow_bounties) break;
					$bounty = $content['bounties']['new'];
					if (!$this->is_anonymous())
					{
						if (!$bounty['bounty_name']) $bounty['bounty_name'] = $GLOBALS['egw_info']['user']['account_fullname'];
						if (!$bounty['bounty_email']) $bounty['bounty_email'] = $GLOBALS['egw_info']['user']['account_email'];
					}
					if (!$bounty['bounty_amount'] || !$bounty['bounty_name'] || !$bounty['bounty_email'])
					{
						$msg = lang('You need to specify amount, donators name AND email address!');
					}
					elseif ($this->save_bounty($bounty))
					{
						$msg = lang('Thank you for setting this bounty.').
							' '.lang('The bounty will NOT be shown, until the money is received.');
						array_unshift($this->data['bounties'],$bounty);
						unset($content['bounties']['new']);
					}
					break;

				default:
					if (!$this->allow_bounties) break;
					// check delete bounty
					list($id) = @each($this->data['bounties']['delete']);
					if ($id)
					{
						unset($this->data['bounties']['delete']);
						if ($this->delete_bounty($id))
						{
							$msg = lang('Bounty deleted');
							foreach($this->data['bounties'] as $n => $bounty)
							{
								if ($bounty['bounty_id'] == $id)
								{
									unset($this->data['bounties'][$n]);
									break;
								}
							}
						}
						else
						{
							$msg = lang('Permission denied !!!');
						}
					}
					else
					{
						// check confirm bounty
						list($id) = @each($this->data['bounties']['confirm']);
						if ($id)
						{
							unset($this->data['bounties']['confirm']);
							foreach($this->data['bounties'] as $n => $bounty)
							{
								if ($bounty['bounty_id'] == $id)
								{
									if ($this->save_bounty($this->data['bounties'][$n]))
									{
										$msg = lang('Bounty confirmed');
										$js = "opener.location.href=opener.location.href.replace(/&tr_id=[0-9]+/,'')+'&msg=".addslashes(urlencode($msg))."';";
										$GLOBALS['egw_info']['flags']['java_script'] .= "<script>\n$js\n</script>\n";
									}
									else
									{
										$msg = lang('Permission denied !!!');
									}
									break;
								}
							}
						}
					}
					break;
			}
		}
		$tr_id = $this->data['tr_id'];
		if (!($tracker = $this->data['tr_tracker']))
		{
			reset($this->trackers);
			list($tracker) = @each($this->trackers);
		}
		if (!$readonlys) $readonlys = $this->readonlys_from_acl();

		if ($this->data['tr_edit_mode'] == 'ascii' && $this->data['tr_description'] && $readonlys['tr_description'])
		{
			// non html view in a readonly htmlarea (div) needs nl2br
			$tr_description_options = 'simple,240px,100%,false,,1';
		}

		$preserv = $content = $this->data;
		if ($content['num_replies']) array_unshift($content['replies'],false);	// need array index starting with 1!
		if ($this->allow_bounties)
		{
			if (is_array($content['bounties']))
			{
				$total = 0;
				foreach($content['bounties'] as $bounty)
				{
					$total += $bounty['bounty_amount'];
					// confirmed bounties cant be deleted and need no confirm button
					$readonlys['delete['.$bounty['bounty_id'].']'] =
						$readonlys['confirm['.$bounty['bounty_id'].']'] = !$this->is_admin($tracker) || $bounty['bounty_confirmed'];
				}
				$content['bounties']['num_bounties'] = count($content['bounties']);
				array_unshift($content['bounties'],false);	// we need the array index to start with 2!
				array_unshift($content['bounties'],false);
				$content['bounties']['total'] = $total ? sprintf('%4.2lf',$total) : '';
			}
			$content['bounties']['currency'] = $this->currency;
			$content['bounties']['is_admin'] = $this->is_admin($tracker);
		}
		$statis = $this->get_tracker_stati($tracker);
		$content += array(
			'msg' => $msg,
			'tr_description_options' => $tr_description_options,
			'tr_description_mode'    => $readonlys['tr_description'],
			'tr_reply_options' => $tr_reply_options,
			'on_cancel' => $popup ? 'window.close();' : '',
			'no_vote' => '',
			'link_to' => array(
				'to_id' => $tr_id,
				'to_app' => 'tracker',
			),
			'status_help' => !$this->pending_close_days ? lang('Pending items never get close automatic.') :
				lang('Pending items will be closed automatic after %1 days without response.',$this->pending_close_days),
			'history' => array(
				'id'  => $tr_id,
				'app' => 'tracker',
				'status-widgets' => array(
					'Co' => 'select-percent',
					'St' => &$statis,
					'Ca' => 'select-cat',
					'Tr' => 'select-cat',
					'Ve' => 'select-cat',
					'As' => 'select-account',
					'pr' => array('Public','Private'),
					'Cl' => 'date-time',
					'Re' => self::$resolutions,
					'Gr' => 'select-account',
				),
			),
		);
		if ($this->allow_bounties && !$this->is_anonymous())
		{
			$content['bounties']['user_name'] = $GLOBALS['egw_info']['user']['account_fullname'];
			$content['bounties']['user_email'] = $GLOBALS['egw_info']['user']['account_email'];
		}
		$preserv['popup'] = $popup;
		$preserv['own_referer'] = $own_referer;

		if (!$tr_id && isset($_REQUEST['link_app']) && isset($_REQUEST['link_id']) && !is_array($content['link_to']['to_id']))
		{
			$link_ids = is_array($_REQUEST['link_id']) ? $_REQUEST['link_id'] : array($_REQUEST['link_id']);
			foreach(is_array($_REQUEST['link_app']) ? $_REQUEST['link_app'] : array($_REQUEST['link_app']) as $n => $link_app)
			{
				$link_id = $link_ids[$n];
				if (preg_match('/^[a-z_0-9-]+:[:a-z_0-9-]+$/i',$link_app.':'.$link_id))	// gard against XSS
				{
					egw_link::link('tracker',$content['link_to']['to_id'],$link_app,$link_id);
				}
			}
		}
		$sel_options = array(
			'tr_tracker'  => &$this->trackers,
			'cat_id'      => $this->get_tracker_labels('cat',$tracker),
			'tr_version'  => $this->get_tracker_labels('version',$tracker),
			'tr_priority' => $this->get_tracker_priorities($tracker,$content['cat_id']),
			'tr_status'   => &$statis,
			'tr_resolution' => self::$resolutions,
			'tr_assigned' => $this->get_staff($tracker,$this->allow_assign_groups),
			// New items default to primary group is no right to change the group
			'tr_group' => $this->get_groups(!$this->check_rights($this->field_acl['tr_group'],$tracker) && !$this->data['tr_id']),
			'canned_response' => $this->get_tracker_labels('response'),
		);
		foreach($this->field2history as $field => $status)
		{
			$sel_options['status'][$status] = $this->field2label[$field];
		}
		$sel_options['status']['xb'] = 'Bounty deleted';
		$sel_options['status']['bo'] = 'Bounty set';
		$sel_options['status']['Bo'] = 'Bounty confirmed';

		$readonlys['tabs'] = array(
			'comments' => !$tr_id || !$content['num_replies'],
			'add_comment' => !$tr_id || $readonlys['reply_message'],
			'history'  => !$tr_id,
			'bounties' => !$this->allow_bounties,
			'custom'   => !$this->customfields,
		);
		if ($tr_id && $readonlys['reply_message'])
		{
			$readonlys['button[save]'] = true;
		}
		if (!$tr_id && $readonlys['add'])
		{
			$msg = lang('Permission denied !!!');
			$readonlys['button[save]'] = true;
		}
		if (!$this->allow_voting || !$tr_id || $readonlys['vote'] || ($voted = $this->check_vote()))
		{
			$readonlys['button[vote]'] = true;
			if ($tr_id && $this->allow_voting)
			{
				$content['no_vote'] = is_int($voted) ? lang('You voted %1.',
					date($GLOBALS['egw_info']['user']['preferences']['common']['dateformat'].
					($GLOBALS['egw_info']['user']['preferences']['common']['timeformat']==12?' h:i a':' H:i'),$voted)) :
					lang('You need to login to vote!');
			}
		}
		if ($readonlys['canned_response'])
		{
			$content['no_canned'] = true;
		}
		$content['no_links'] = $readonlys['link_to'];
		$content['bounties']['no_set_bounties'] = $readonlys['bounty'];

		$what = $tracker ? $this->trackers[$tracker] : lang('Tracker');
		$GLOBALS['egw_info']['flags']['app_header'] = $tr_id ? lang('Edit %1',$what) : lang('New %1',$what);

		$tpl = new etemplate('tracker.edit');
		if ($this->tracker_has_cat_specific_priorities($tracker))
		{
			$tpl->set_cell_attribute('cat_id','onchange',true);
		}
		// No notifications needs label hidden too
		if($readonlys['no_notifications'])
		{
			$tpl->set_cell_attribute('no_notifications', 'disabled', true);
		}

		if ($content['tr_assigned'] && !is_array($content['tr_assigned']))
		{
			$content['tr_assigned'] = explode(',',$content['tr_assigned']);
		}
		if (count($content['tr_assigned']) > 1)
		{
			$tpl->set_cell_attribute('tr_assigned','size','3+');
		}
		return $tpl->exec('tracker.tracker_ui.edit',$content,$sel_options,$readonlys,$preserv,$popup ? 2 : 0);
	}

	/**
	 * set fields readonly, depending on the rights the current user has on the actual tracker item
	 *
	 * @return array
	 */
	function readonlys_from_acl()
	{
		//echo "<p>uitracker::get_readonlys() is_admin(tracker={$this->data['tr_tracker']})=".$this->is_admin($this->data['tr_tracker']).", id={$this->data['tr_id']}, creator={$this->data['tr_creator']}, assigned={$this->data['tr_assigned']}, user=$this->user</p>\n";
		$readonlys = array();
		foreach($this->field_acl as $name => $rigths)
		{
			$readonlys[$name] = !$this->check_rights($rigths);
		}
		if ($this->customfields && $readonlys['customfields'])
		{
			foreach($this->customfields as $name => $data)
			{
				$readonlys['#'.$name] = $readonlys['customfields'];
			}
		}
		return $readonlys;
	}

	/**
	 * query rows for the nextmatch widget
	 *
	 * @param array $query with keys 'start', 'search', 'order', 'sort', 'col_filter'
	 *	For other keys like 'filter', 'cat_id' you have to reimplement this method in a derived class.
	 * @param array &$rows returned rows/competitions
	 * @param array &$readonlys eg. to disable buttons based on acl
	 * @return int total number of rows
	 */
	function get_rows(&$query_in,&$rows,&$readonlys)
	{
		if (!$this->allow_voting && $query_in['order'] == 'votes' ||	// in case the tracker-config changed in that session
			!$this->allow_bounties && $query_in['order'] == 'bounties') $query_in['order'] = 'tr_id';

		egw_session::appsession('index','tracker'.($query_in['only_tracker'] ? '-'.$query_in['only_tracker'] : ''),$query=$query_in);
		$tracker = $query['col_filter']['tr_tracker'];
		if (!($query['col_filter']['cat_id'] = $query['filter'])) unset($query['col_filter']['cat_id']);
		if (!($query['col_filter']['tr_version'] = $query['filter2'])) unset($query['col_filter']['tr_version']);

		if (!($query['col_filter']['tr_creator'])) unset($query['col_filter']['tr_creator']);

		if ($query['col_filter']['tr_assigned'] < 0)	// resolve groups with it's members
		{
			$query['col_filter']['tr_assigned'] = $GLOBALS['egw']->accounts->members($query['col_filter']['tr_assigned'],true);
			$query['col_filter']['tr_assigned'][] = $query_in['col_filter']['tr_assigned'];
		}
		elseif($query['col_filter']['tr_assigned'] === 'not')
		{
			//$query['col_filter'][] = 'tr_assigned IS NULL';
			//unset($query['col_filter']['tr_assigned']);
			$query['col_filter']['tr_assigned'] = null;
		}
		elseif(!$query['col_filter']['tr_assigned'])
		{
			unset($query['col_filter']['tr_assigned']);
		}
		// save the state of the index page (filters) in the user prefs
		$state = serialize(array(
			'filter'     => $query['filter'],	// cat
			'filter2'    => $query['filter2'],	// version
			'order'      => $query['order'],
			'sort'       => $query['sort'],
			'num_rows'   => $query['num_rows'],
			'col_filter' => array(
				'tr_tracker'  => $query['col_filter']['tr_tracker'],
				'tr_creator'  => $query['col_filter']['tr_creator'],
				'tr_assigned' => $query['col_filter']['tr_assigned'],
				'tr_status'   => $query['col_filter']['tr_status'],
			),
		));
		if ($GLOBALS['egw']->session->session_flags != 'A' &&	// store the current state of non-anonymous users in the prefs
			$state != $GLOBALS['egw_info']['user']['preferences']['tracker']['index_state'])
		{
			//$msg .= "save the index state <br>";
			$GLOBALS['egw']->preferences->add('tracker','index_state',$state);
			// save prefs, but do NOT invalid the cache (unnecessary)
			$GLOBALS['egw']->preferences->save_repository(false,'user',false);
		}
		//echo "<p align=right>uitracker::get_rows() order='$query[order]', sort='$query[sort]', search='$query[search]', start=$query[start], num_rows=$query[num_rows], col_filter=".print_r($query['col_filter'],true)."</p>\n";
		$total = parent::get_rows($query,$rows,$readonlys,$this->allow_voting||$this->allow_bounties);	// true = count votes and/or bounties
		foreach($rows as $n => $row)
		{
			// Check if this is a new (unseen) ticket for the current user
			if (self::seen($row, false))
			{
				$rows[$n]['seen_class'] = 'seen';
			}
			else
			{
				$rows[$n]['seen_class'] = 'unseen';
			}

			// show the right tracker and/or cat specific priority label
			if ($row['tr_priority'])
			{
				if (is_null($prio_labels) || $this->priorities && ($row['tr_tracker'] != $prio_tracker || $row['cat_id'] != $prio_cat))
				{
					$prio_labels = $this->get_tracker_priorities($prio_tracker=$row['tr_tracker'],$prio_cat = $row['cat_id']);
					if ($prio_labels === self::$stock_priorities)	// show only the numbers for the stock priorities
					{
						$prio_labels = array_combine(array_keys(self::$stock_priorities),array_keys(self::$stock_priorities));
					}
				}
				$rows[$n]['prio_label'] = $prio_labels[$row['tr_priority']];
			}
			if ($row['overdue']) $rows[$n]['overdue_class'] = 'overdue';
			if ($row['bounties']) $rows[$n]['currency'] = $this->currency;
			if (isset($GLOBALS['egw_info']['user']['apps']['timesheet']) && $this->prefs['show_sum_timesheet'])
			{
				unset($links);
				if (($links = egw_link::get_links('tracker',$row['tr_id'])) &&
					isset($GLOBALS['egw_info']['user']['apps']['timesheet']))
				{
					// loop through all links of the entries
					$timesheets = array();
					foreach ($links as $link)
					{
						if ($link['app'] == 'projectmanager')
						{
							//$info['pm_id'] = $link['id'];
						}
						if ($link['app'] == 'timesheet') $timesheets[] = $link['id'];
						if ($link['app'] != 'timesheet' && $link['app'] != egw_link::VFS_APPNAME)
						{
							$rows[$n]['extra_links'] .= '&link_app[]='.$link['app'].'&link_id[]='.$link['id'];
						}
					}
					if (isset($GLOBALS['egw_info']['user']['apps']['timesheet']) && $timesheets && $this->prefs['show_sum_timesheet'])
					{
						$sum = ExecMethod('timesheet.timesheet_bo.sum',$timesheets);
						$rows[$n]['tr_sum_timesheets'] = $sum['duration'];
					}
				}
			}
			//_debug_array($rows[$n]);
			//echo "<p>".$this->trackers[$row['tr_tracker']]."</p>";
			$id=$row['tr_id'];
			$readonlys["timesheet[$id]"]= !(isset($GLOBALS['egw_info']['user']['apps']['timesheet']) && ($this->is_admin($row['tr_tracker']) or ($this->is_technician($row['tr_tracker']))));
			$readonlys['checked']=!($this->is_admin($row['tr_tracker'])) or ($this->is_technician($row['tr_tracker']));
		}

		$rows['duration_format'] = ','.$this->duration_format.',,1';
		$rows['sel_options']['tr_assigned'] = array('not' => lang('Not assigned'))+$this->get_staff($tracker);

		$versions = $this->get_tracker_labels('version',$tracker);
		$cats = $this->get_tracker_labels('cat',$tracker);
		$statis = $this->get_tracker_stati($tracker);

		$rows['sel_options']['tr_status'] = $this->filters+$statis;
		$rows['sel_options']['filter'] = array(lang('All'))+$cats;
		$rows['sel_options']['filter2'] = array(lang('All'))+$versions;
		$rows['sel_options']['tr_version'] =& $versions;
		if ($this->is_admin($tracker))
		{
			$rows['sel_options']['canned_response'] = $this->get_tracker_labels('response',$tracker);
			$rows['sel_options']['cat_id'] =& $cats;
			$rows['sel_options']['tr_status_admin'] =& $statis;
			$rows['is_admin'] = true;
		}
		if (!$this->allow_voting)
		{
			$rows['no_votes'] = true;
			$query_in['options-selectcols']['votes'] = false;
		}
		if (!$this->allow_bounties)
		{
			$rows['no_bounties'] = true;
			$query_in['options-selectcols']['bounties'] = false;
		}
		if (!$this->prefs['show_sum_timesheet'] || !isset($GLOBALS['egw_info']['user']['apps']['timesheet']))
		{
			$query_in['options-selectcols']['tr_sum_timesheets'] = false;
			$rows['allow_sum_timesheet'] = false;
		}
		else
		{
			// Disable column if turned off in the column list
			$rows['allow_sum_timesheet'] = (strpos($query_in['selectcols'], 'tr_sum_timesheets') !== false);
		}

		if ($query['col_filter']['cat_id']) $rows['no_cat_id'] = true;
		// enable the Actions column
		$this->prefs['show_actions'] ? $rows['allow_actions'] = $this->prefs['show_actions'] : $rows['allow_actions'] = null;

		// enable tracker column if all trackers are shown
		if ($tracker) $rows['no_tr_tracker'] = true;
		$GLOBALS['egw_info']['flags']['app_header'] = lang('Tracker').': '.($tracker ? $this->trackers[$tracker] : lang('All'));
		return $total;
	}

	/**
	 * Check if a ticket has already been seen
	 *
	 * @param array $data=null Ticket data
	 * @param boolean $update=false Set ticket as seen when true
	 * @return boolean true=seen before false=new ticket
	 */
	function seen (&$data, $update=false)
	{
		$seen = array();
		if ($data['tr_seen']) $seen = unserialize($data['tr_seen']);
		if (in_array($this->user, $seen))
		{
			return true;
		}
		if ($update === false)
		{
			return false;
		}
		$seen[] = $this->user;
		$this->db->update('egw_tracker', array('tr_seen' => serialize($seen)),
			array('tr_id' => $data['tr_id']),__LINE__,__FILE__,'tracker');
		return false; // This time still false...
	}

	/**
	 * Show a tracker
	 *
	 * @param array $content=null eTemplate content
	 * @param int $tracker=null id of tracker
	 * @param string $msg=''
	 * @param int $only_tracker=null show only the given tracker and not tracker-selection
	 * @return string html-content, if sitemgr otherwise null
	 */
	function index($content=null,$tracker=null,$msg='',$only_tracker=null)
	{
		//_debug_array($this->trackers);
		if (!is_array($content))
		{
			if ($_GET['tr_id'])
			{
				if (!$this->read($_GET['tr_id']))
				{
					$msg = lang('Tracker item not found !!!');
				}
				else
				{
					return $this->edit(null,'',false);	// false = use no popup
				}
			}
			if (!$msg && $_GET['msg']) $msg = $_GET['msg'];
			if ($only_tracker && isset($this->trackers[$only_tracker]))
			{
				$tracker = $only_tracker;
			}
			else
			{
				$only_tracker = null;
			}
			// if there is no tracker specified, try the tracker submitted
			if (!$tracker && (int)$_GET['tracker']) $tracker = $_GET['tracker'];
			// if there is still no tracker, use the last tracker that was applied and saved to/with the view with the appsession
			if (!$tracker && ($state=egw_session::appsession('index','tracker'.($only_tracker ? '-'.$only_tracker : ''))))
			{
			      $tracker=$state['col_filter']['tr_tracker'];
			}

		}
		else
		{
			$only_tracker = $content['only_tracker']; unset($content['only_tracker']);
			$tracker = $content['nm']['col_filter']['tr_tracker'];

			if ($content['update'])
			{
				unset($content['update']);
				$checked = $content['nm']['rows']['checked']; unset($content['nm']);
				// remove all 'No change'
				foreach($content as $name => $value) if ($value === '') unset($content[$name]);

				if (!count($checked) || !count($content))
				{
					$msg = lang('You need to select something to change AND some tracker items!');
				}
				else
				{
					$n = 0;
					foreach($checked as $tr_id)
					{
						if (!$this->read($tr_id)) continue;
						foreach($content as $name => $value)
						{
							if ($name == 'tr_status_admin') $name = 'tr_status';
							if ($value !== '') $this->data[$name] = $name == 'tr_assigned' && $value === 'not' ? NULL : $value;
						}
						if (!$this->save()) $n++;
					}
					$msg = lang('%1 entries updated.',$n);
				}
			}
		}
		if (!$tracker) $tracker = $content['nm']['col_filter']['tr_tracker'];
		$sel_options = array(
			'tr_tracker'  => &$this->trackers,
			'tr_status'   => $this->filters + $this->get_tracker_stati($tracker),
			'tr_resolution' => self::$resolutions,
			'tr_priority' => $this->get_tracker_priorities($tracker,$content['cat_id']),
		);
		if (($escalations = ExecMethod2('tracker.tracker_escalations.query_list','esc_title','esc_id')))
		{
			$sel_options['esc_id']['already escalated'] = $escalations;
			foreach($escalations as $esc_id => $label)
			{
				$sel_options['esc_id']['matching filter']['-'.$esc_id] = $label;
			}
		}
		if (!is_array($content)) $content = array();
		$content = array_merge($content,array(
			'nm' => egw_session::appsession('index','tracker'.($only_tracker ? '-'.$only_tracker : '')),
			'msg' => $msg,
			'status_help' => !$this->pending_close_days ? lang('Pending items never get close automatic.') :
				lang('Pending items will be closed automatic after %1 days without response.',$this->pending_close_days),
		));

		if (!is_array($content['nm']))
		{
			$content['nm'] = array(
				'get_rows'       =>	'tracker.tracker_ui.get_rows',
				'no_cat'         => true,
				'filter2'        => 0,	// all
				'filter2_label'  => lang('Version'),
				'filter2_no_lang'=> true,
				'filter'         => 0, // all
				'filter_label'   => lang('Category'),
				'filter_no_lang' => true,
				'order'          =>	$this->allow_bounties ? 'bounties' : ($this->allow_voting ? 'votes' : 'tr_id'),// IO name of the column to sort after (optional for the sortheaders)
				'sort'           =>	'DESC',// IO direction of the sort: 'ASC' or 'DESC'
				'options-tr_assigned' => array('not' => lang('Noone')),
				'col_filter'     => array(
					'tr_status'  => 'not-closed',	// default filter: not closed
				),
	 			'header_left'    =>	$only_tracker ? null : 'tracker.index.left', // I  template to show left of the range-value, left-aligned (optional)
	 			'only_tracker'   => $only_tracker,
	 			'header_right'   =>	'tracker.index.right', // I  template to show right of the range-value, left-aligned (optional)
	 			'default_cols'   => '!esc_id',
			);
			// use the state of the last session stored in the user prefs
			if (($state = @unserialize($GLOBALS['egw_info']['user']['preferences']['tracker']['index_state'])))
			{
				$content['nm'] = array_merge($content['nm'],$state);
				$tracker = $content['nm']['col_filter']['tr_tracker'];
			}
			elseif (!$tracker)
			{
				reset($this->trackers);
				list($tracker) = @each($this->trackers);
			}
		}
		// if there is only one tracker, use that one and do NOT show the selectbox
		if (count($this->trackers) == 1)
		{
			reset($this->trackers);
			list($tracker) = @each($this->trackers);
			$readonlys['nm']['col_filter[tr_tracker]'] = true;
		}
		if (!$tracker)
		{
			$tracker = $content['nm']['col_filter']['tr_tracker'] = '';
		}
		else
		{
			$content['nm']['col_filter']['tr_tracker'] = $tracker;
		}
		$content['is_admin'] = $this->is_admin($tracker);
		//_debug_array($content);
		$readonlys['add'] = $readonlys['nm']['add'] = !$this->check_rights($this->field_acl['add'],$tracker);
		$tpl = new etemplate();
		if (!$tpl->sitemgr || !$tpl->read('tracker.index.sitemgr'))
		{
			$tpl->read('tracker.index');
		}

		return $tpl->exec('tracker.tracker_ui.index',$content,$sel_options,$readonlys,array('only_tracker' => $only_tracker));
	}
}
