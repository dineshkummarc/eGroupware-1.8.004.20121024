<?php
/**
 * eGroupWare - Polls
 * http://www.egroupware.org 
 *
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @package polls
 * @author Ralf Becker <RalfBecker-AT-outdoor-training.de>
 * @version $Id: class.sopolls.inc.php 23996 2007-06-03 16:26:58Z ralfbecker $ 
 */

require_once(EGW_API_INC.'/class.config.inc.php');

class sopolls
{
	var $debug = False;
	/**
	 * Own instance for the db-class
	 *
	 * @var egw_db
	 */
	var $db;
	var $polls_table = 'egw_polls';
	var $answers_table = 'egw_polls_answers';
	var $votes_table = 'egw_polls_votes';
	/**
	 * Instance of the config-object for polls
	 *
	 * @var config
	 */
	var $config;
	/**
	 * account_id of current user
	 *
	 * @var int
	 */
	var $user;

	var $total = 0;

	function sopolls()
	{
		$this->db = clone($GLOBALS['egw']->db);
		$this->db->set_app('polls');
		$this->config = new config('polls');
		$this->user = $GLOBALS['egw_info']['user']['account_id'];
	}

	/**
	 * Loading the polls configuration via config class from egw_config table
	 *
	 * @return array
	 */
	function load_settings()
	{
		$this->config->read_repository();
		
		if (!is_array($this->config->config_data)) $this->config->config_data = array();
		
		return $GLOBALS['poll_settings'] = $this->config->config_data;
	}

	/**
	 * Saving polls configuration via config class from egw_config table
	 *
	 * @param array $data
	 */
	function save_settings($data)
	{
		$this->config->config_data = $data;
		$this->config->save_repository();
	}

	/**
	 * Get the number of votes of the current user for a given poll
	 *
	 * @param int $poll_id
	 * @param int $uid=null
	 * @param boolean $use_ip=null should the ip be checked too, default null = for anon users
	 * @param int $answer_id=null default null = dont care for the specific answer
	 * @return int
	 */
	function get_user_votecount($poll_id,$uid=null,$use_ip = null,$answer_id=null)
	{
		if (is_null($uid)) $uid = $this->user;
		if (is_null($use_ip)) $use_ip = $this->is_anonymous();

		$where = array(
			'poll_id'  => $poll_id,
			'vote_uid' => $uid,
		);
		if ($use_ip) $where['vote_ip'] = $this->get_ip();
		if (!is_null($answer_id)) $where['answer_id'] = $answer_id;

		$this->db->select($this->votes_table,'COUNT(*)',$where,__LINE__,__FILE__);
		$this->db->next_record();
		
		//echo "<p>sopolls::get_user_votecount($poll_id,$uid,$use_ip) = ".(int)$this->db->f(0);
		return (int) $this->db->f(0);
	}
	
	/**
	 * Get content of a poll
	 *
	 * @param int $poll_id
	 * @return array/boolean array with poll or false if not found
	 */
	function get_poll($poll_id)
	{
		$this->db->select($this->polls_table,'*',array('poll_id' => $poll_id),__LINE__,__FILE__);
		
		return $this->db->row(true);
	}

	/**
	 * Get total votes for one poll
	 *
	 * @param int $poll_id
	 * @return int
	 */
	function get_poll_total($poll_id)
	{
		$this->db->select($this->answers_table,'SUM(answer_votes)',array('poll_id' => $poll_id),__LINE__,__FILE__);

		return $this->db->next_record() ? (int) $this->db->f(0) : 0;
	}

	/**
	 * Get the answers of a poll
	 *
	 * @param int $poll_id
	 * @param int $answer_id=null
	 * @return array
	 */
	function get_poll_data($poll_id,$answer_id=null)
	{
		$where = array('poll_id' => $poll_id);
		if (!is_null($answer_id) && $answer_id >= 0) $where['answer_id'] = $answer_id;
		
		$this->db->select($this->answers_table,'*',$where,__LINE__,__FILE__,false,'ORDER BY LOWER(answer_text)');

		$options = array();
		while(($row = $this->db->row(true,'answer_')))
		{
			$row['vote_id'] = $row['id'];	// compatibility
			$options[] = $row;
		}
		return $options;
	}

	/**
	 * Get the id of the latest poll
	 *
	 * @return int/boolean false if none found
	 */
	function get_latest_poll()
	{
		$this->db->select($this->polls_table,'MAX(poll_id)',false,__LINE__,__FILE__);
		
		return $this->db->next_record() ? $this->db->f(0) : false;
	}

	/**
	 * Add an answer to a poll
	 *
	 * @param int $poll_id
	 * @param string $answer
	 * @return int answer_id
	 */
	function add_answer($poll_id,$answer)
	{
		$this->db->insert($this->answers_table,array(
			'poll_id' => $poll_id,
			'answer_text' => $answer,
		),false,__LINE__,__FILE__);
		
		return $this->db->get_last_insert_id($this->answers_table,'answer_id');
	}

	/**
	 * Add a question / poll
	 *
	 * @param string $title
	 * @param int $visible=0 default 0 = by everyone
	 * @param int $votable=0 default 0 = by everyone
	 * @return int poll_id
	 */
	function add_question($title,$visible=0,$votable=0)
	{
		$this->db->insert($this->polls_table,array(
			'poll_title' => $title,
			'poll_visible' => $visible,
			'poll_votable' => $votable,
			'poll_timestamp' => time(),
		),false,__LINE__,__FILE__);
		
		return $this->db->get_last_insert_id('egw_polls','poll_id');
	}

	/**
	 * Delete one or more answers
	 *
	 * @param int $poll_id
	 * @param int $answer_id=null
	 * @return int affected rows
	 */
	function delete_answer($poll_id,$answer_id=null)
	{
		$where = array('poll_id' => $poll_id);
		if ($answer_id) $where['answer_id'] = $answer_id;
		$this->db->delete($this->answers_table,$where,__LINE__,__FILE__);
		
		return $this->db->affected_rows();
	}

	/**
	 * Delete a question / poll
	 *
	 * @param int $poll_id
	 * @return int affected rows
	 */
	function delete_question($poll_id)
	{
		$this->delete_answer($poll_id);
		$this->db->delete($this->votes_table,array('poll_id' => $poll_id),__LINE__,__FILE__);
		$this->db->delete($this->polls_table,array('poll_id' => $poll_id),__LINE__,__FILE__);
		$ret = $this->db->affected_rows();

		if($GLOBALS['currentpoll'] == $poll_id)
		{
			$this->config->save_value('currentpoll',$this->get_latest_poll());
		}
		return $ret;
	}

	/**
	 * Add a vote
	 *
	 * @param int $poll_id
	 * @param int $answer_id
	 * @param int $uid=null default null use current user
	 * @param boolean $use_ip=null default use ip for anonymous users
	 * @return boolean true if vote valid and added, false otherwise
	 */
	function add_vote($poll_id,$answer_id,$uid=null,$use_ip=null)
	{
		if (is_null($uid)) $uid = $this->user;
		if (is_null($use_ip)) $use_ip = $this->is_anonymous();

		if ($this->get_user_votecount($poll_id,$uid,$use_ip)) return false;	// already voted

		// verify that we're adding a valid vote before update
		$this->db->update($this->answers_table,'answer_votes=answer_votes+1',array(
			'poll_id' => $poll_id,
			'answer_id' => $answer_id,
			//'answer_votable' => memberships($uid),
		),__LINE__,__FILE__);
		
		if (!$this->db->affected_rows()) return false;	// not existing vote, answer or not allowed to vote
		
		$data = array(
			'poll_id' => $poll_id,
			'answer_id' => $answer_id,
			'vote_uid' => $uid,
			'vote_timestamp' => time(),
			'vote_ip' => $this->get_ip(),
		);
		return $this->db->insert($this->votes_table,$data,false,__LINE__,__FILE__);
	}

	/**
	 * Update an answer
	 *
	 * @param int $poll_id
	 * @param int $answer_id
	 * @param string $answer
	 * @return int affected rows
	 */
	function update_answer($poll_id,$answer_id,$answer)
	{
		$this->db->update($this->answers_table,array('answer_text'=>$answer),array(
			'poll_id' => $poll_id,
			'answer_id' => $answer_id,
		),__LINE__,__FILE__);
		
		return $this->db->affected_rows();
	}

	/**
	 * Enter description here...
	 *
	 * @param int $poll_id
	 * @param string $title
	 * @param int $visible=null
	 * @param int $votable=null
	 * @return int affected rows
	 */
	function update_question($poll_id,$title,$visible=null,$votable=null)
	{
		$data = array('poll_title' => $title);
		if (!is_null($visible)) $data['poll_visible'] = $visible;
		if (!is_null($votable)) $data['poll_votable'] = $votable;

		$this->db->update($this->polls_table,$data,array('poll_id' => $poll_id),__LINE__,__FILE__);
		
		return $this->db->affected_rows();
	}

	/**
	 * List polls/questions or answers
	 *
	 * @param array $args keys limit, start, order, sort
	 * @param string $type 'question' or 'answer'
	 * @return array
	 */
	function get_list($args,$type='question')
	{
		if ($type == 'question')
		{
			$cols = '*';
		}
		else
		{
			$cols = "$this->answers_table.*,poll_title";
			$join = "JOIN $this->answers_table ON $this->polls_table.poll_id=$this->answers_table.poll_id";
		}
		if (preg_match('/^[a-z_0-9]+ (asc|desc)?$/i',$args['order'].' '.$args['sort']))
		{
			$order = 'ORDER BY '.$args['order'].' '.$args['sort'];
		}
		$this->db->select($this->polls_table,$cols,false,__LINE__,__FILE__,
			isset($args['limit']) ? (int)$args['start'] : false,$order,false,(int)$args['limit'],$join);
		
		$data = array();
		while (($row = $this->db->row(true)))
		{
			$data[] = $row;
		}
		if (isset($args['limit']))
		{
			$this->db->select($this->polls_table,'COUNT(*)',false,__LINE__,__FILE__,false,'',false,0,$join);
			$this->total = $this->db->next_record() ? $this->db->f(0) : 0;
		}
		return $data;
	}

	/**
	 * Get the ip of the current user
	 *
	 * @return string
	 */
	function get_ip()
	{
		return $_SERVER['REMOTE_ADDR'].(isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? ':'.$_SERVER['HTTP_X_FORWARDED_FOR'] : '');
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

}