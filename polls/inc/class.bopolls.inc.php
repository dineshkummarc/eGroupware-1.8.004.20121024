<?php
/**
 * eGroupWare - Polls
 * http://www.egroupware.org 
 *
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @package polls
 * @copyright 1999 by Till Gerken <tig@skv.org>
 * @author Till Gerken <tig@skv.org>
 * @author Greg Haygood <shrykedude@bellsouth.net>
 * @author Ralf Becker <RalfBecker-AT-outdoor-training.de>
 * @version $Id: class.bopolls.inc.php 23899 2007-05-20 15:01:21Z ralfbecker $ 
 */

require_once(EGW_INCLUDE_ROOT.'/polls/inc/class.sopolls.inc.php');

class bopolls
{
	/**
	 * Instance of the polls SO object
	 *
	 * @var sopolls
	 */
	var $so;
	var $debug = false;

	var $start  = 0;
	var $query  = '';
	var $sort   = '';
	var $order  = '';
	var $filter = 0;
	var $limit  = 0;
	var $total  = 0;

	function bopolls($session=False)
	{
		$this->so = new sopolls;

		$this->load_settings();

		if($session)
		{
			$this->read_sessiondata();
			$this->use_session = True;
		}

		$_start  = get_var('start',array('POST','GET'));
		$_query  = get_var('query',array('POST','GET'));
		$_sort   = get_var('sort',array('POST','GET'));
		$_order  = get_var('order',array('POST','GET'));
		$_limit  = get_var('limit',array('POST','GET'));
		$_filter = get_var('filter',array('POST','GET'));

		if(isset($_start))
		{
			if($this->debug) { echo '<br>overriding $start: "' . $this->start . '" now "' . $_start . '"'; }
			$this->start = $_start;
		}

		if($_limit)
		{
			$this->limit = $_limit;
		}
		else
		{
			$this->limit = $GLOBALS['egw_info']['user']['preferences']['common']['maxmatchs'];
		}

		if((empty($_query) && !empty($this->query)) || !empty($_query))
		{
			$this->query = $_query;
		}

		if(!empty($_sort))
		{
			if($this->debug) { echo '<br>overriding $sort: "' . $this->sort . '" now "' . $_sort . '"'; }
			$this->sort   = $_sort;
		}
		else
		{
			$this->sort = 'ASC';
		}

		if(!empty($_order))
		{
			if($this->debug) { echo '<br>overriding $order: "' . $this->order . '" now "' . $_order . '"'; }
			$this->order = $_order;
		}
		else
		{
			$this->order = 'poll_title';
		}

		if(!empty($_filter))
		{
			if($this->debug) { echo '<br>overriding $filter: "' . $this->filter . '" now "' . $_filter . '"'; }
			$this->filter = $_filter;
		}

	}

	function load_settings()
	{
		$this->so->load_settings();
	}

	function save_settings($data)
	{
		if(isset($data) && is_array($data))
		{
			$this->so->save_settings($data);
		}
	}

	function save_sessiondata($data = '')
	{
		if($this->use_session)
		{
			if(empty($data) || !is_array($data))
			{
				$data = array();
			}
			$data += array(
				'start'  => $this->start,
				'order'  => $this->order,
				'limit'  => $this->limit,
				'query'  => $this->query,
				'sort'   => $this->sort,
				'filter' => $this->filter
			);
			if($this->debug) { echo '<br>Save:'; _debug_array($data); }
			$GLOBALS['egw']->session->appsession('session_data','polls_list',$data);
		}
	}

	function read_sessiondata()
	{
		$data = $GLOBALS['egw']->session->appsession('session_data','polls_list');
		if($this->debug) { echo '<br>Read:'; _debug_array($data); }

		$this->start  = $data['start'];
		$this->limit  = $data['limit'];
		$this->query  = $data['query'];
		$this->sort   = $data['sort'];
		$this->order  = $data['order'];
		$this->filter = $data['filter'];
	}

	function add_vote($poll_id,$vote_id)
	{
		if(isset($poll_id) && isset($vote_id) &&
			(int)$poll_id >= 0 && (int)$vote_id >= 0 &&
			$this->user_can_vote($poll_id))
		{
			$this->so->add_vote($poll_id,$vote_id);
		}
	}

	function add_answer($poll_id,$answer)
	{
		$this->so->add_answer($poll_id,$answer);
	}

	function add_question($question)
	{
		if(empty($question)) return false;
		
		return $this->so->add_question($question);
	}

	function delete_answer($poll_id,$vote_id)
	{
		if(!empty($poll_id) && !empty($vote_id))
		{
			$this->so->delete_answer($poll_id,$vote_id);
		}
	}

	function delete_question($poll_id)
	{
		if(!empty($poll_id))
		{
			$this->so->delete_question($poll_id);
		}
	}

	function update_answer($poll_id,$vote_id,$answer)
	{
		if(!empty($poll_id) && !empty($vote_id) && isset($answer))
		{
			$this->so->update_answer($poll_id,$vote_id,$answer);
		}
	}

	function update_question($poll_id,$question,$visible=null,$votable=null)
	{
		if(!empty($poll_id) && $question)
		{
			$this->so->update_question($poll_id,$question,$visible,$votable);
		}
	}

	function get_latest_poll()
	{
		return $this->so->get_latest_poll();
	}

	function makelink($action,$args)
	{
		$menuaction = 'polls.uiadmin.'.$action;
		return $GLOBALS['egw']->link('/index.php',array('menuaction'=>$menuaction,$args));
	}

	/**
	 * Check if current user has right to see the result ('visible') or vote ('votable')
	 *
	 * @param int $poll_id
	 * @param string $type='votable' 'votable' or 'visible', default 'votable'
	 * @return boolean true if access is granted, false otherwise
	 */
	function check_acl($poll_id,$type='votable')
	{
		if (!$poll_id || !($poll=$this->get_poll($poll_id)) || !in_array($type,array('visible','votable'))) return false;
		
		$acl_setting = $poll['poll_'.$type];
		//echo "<p>check_acl($poll_id,$type) acl_setting=$acl_setting</p>\n";

		if ($acl_setting < 0)	// a certain group
		{
			$members = $GLOBALS['egw']->accounts->members($acl_setting,true);
			
			return in_array($this->so->user,$members);
		}
		switch($acl_setting)
		{
			case 0:		// everyone incl. anonymous
				return true;
				
			case 1:		// non-anonymous eGW users
				return !$this->so->is_anonymous();
				
			case 2: 	// admins
				return isset($GLOBALS['egw_info']['user']['apps']['admin']);
				
			case 3:
				return false;
		}
		return false;
	}

	/**
	 * Checks if the current user is allowed to vote for $poll_id
	 * 
	 * Check if he has the necessary ACL AND has not yet voted
	 *
	 * @param int $poll_id
	 * @return boolean
	 */
	function user_can_vote($poll_id)
	{
		//echo "<p>user_can_vote($poll_id) check_acl($poll_id,'votable')=".(int)$this->check_acl($poll_id,'votable').", get_user_votecount($poll_id)=".$this->so->get_user_votecount($poll_id)."</p>\n";
		return $this->check_acl($poll_id,'votable') && !$this->so->get_user_votecount($poll_id);
	}

	/**
	 * Get content of a poll
	 *
	 * @param int $poll_id
	 * @return array/boolean array with poll or false if not found
	 */
	function get_poll($poll_id)
	{
		static $poll_cache;
		
		if (!$poll_id) return false;
		
		if (is_null($poll_cache) || !is_array($poll_cache) || $poll_cache['poll_id'] != $poll_id)
		{
			$poll_cache = $this->so->get_poll($poll_id);
		}
		return $poll_cache;
	}

	function get_poll_title($poll_id)
	{
		return ($poll = $this->get_poll($poll_id)) ? $poll['poll_title'] : false;
	}
	
	function get_poll_total($poll_id)
	{
		$sum = (int)$this->so->get_poll_total($poll_id);
		return $sum >= 0 ? $sum : false;
	}

	function get_poll_data($poll_id,$vote_id = -1)
	{
		return $this->so->get_poll_data($poll_id,$vote_id);
	}

	function get_list($type = 'question', $returnall=false)
	{
		$ret = '';
		$options = array(
			'start' => $this->start,
			'query' => $this->query,
			'sort'  => $this->sort,
			'order' => $this->order
		);
		if(!$returnall)
		{
			$options['limit'] = $this->limit;
		}
		$ret = $this->so->get_list($options,$type);
		$this->total = $this->so->total;

		return $ret;
	}
}
