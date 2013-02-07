<?php
/**
 * EGroupware news_admin - business object
 *
 * @link http://www.egroupware.org
 * @author Ralf Becker <RalfBecker-AT-outdoor-training.de>
 * @package news_admin
 * @copyright (c) 2006-12 by Ralf Becker <RalfBecker-AT-outdoor-training.de>
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @version $Id: class.bonews.inc.php 38195 2012-02-28 08:55:42Z ralfbecker $
 */

/**
 * Business object of the news_admin
 */
class bonews extends so_sql
{
	/**
	 * Instance of the news_admin acl class
	 *
	 * @var boacl
	 */
	var $acl;
	/**
	 * Reference to the categories class
	 *
	 * @var categories
	 */
	var $cats;
	/**
	 * Timestamps which need to be converted to user-time and back
	 *
	 * @var array
	 */
	var $timestamps = array('news_date','news_begin','news_end');
	/**
	 * offset in secconds between user and server-time,
	 *	it need to be add to a server-time to get the user-time or substracted from a user-time to get the server-time
	 *
	 * @var int
	 */
	var $tz_offset_s;
	/**
	 * Timestamp with actual user-time
	 *
	 * @var int
	 */
	var $now;
	/**
	 * Current user
	 *
	 * @var int
	 */
	var $user;
	/**
	 * Labels for the visibility
	 *
	 * @var array
	 */
	var	$visiblity = array(
		'always' => 'Always',
		'never'  => 'Never',
		'date'   => 'By date',
	);
	/**
	 * Language of the user
	 *
	 * @var string
	 */
	var $lang;

	/**
	 * Constructor
	 */
	function __construct()
	{
		parent::__construct('news_admin','egw_news');

		$this->acl =& CreateObject('news_admin.boacl');

		$this->tz_offset_s = $GLOBALS['egw']->datetime->tz_offset;
		$this->now = time() + $this->tz_offset_s;	// time() is server-time and we need a user-time

		$this->user =& $GLOBALS['egw_info']['user']['account_id'];
		$this->lang =& $GLOBALS['egw_info']['user']['preferences']['common']['lang'];

		$this->cats = new categories('','news_admin');
	}

	/**
	 * PHP4 constructor
	 *
	 * @deprecated use __construct()
	 */
	function bonews()
	{
		$this->__construct();
	}

	/**
	 * changes the data from the db-format to your work-format
	 *
	 * reimplemented to adjust the timezone of the timestamps (adding $this->tz_offset_s to get user-time)
	 * Please note, we do NOT call the method of the parent so_sql !!!
	 *
	 * @param array $data if given works on that array and returns result, else works on internal data-array
	 * @return array with changed data
	 */
	function db2data($data=null)
	{
		if (!is_array($data))
		{
			$data = &$this->data;
		}
		foreach($this->timestamps as $name)
		{
			if (isset($data[$name]) && $data[$name]) $data[$name] += $this->tz_offset_s;
		}
		if (!$data['news_begin'])
		{
			$data['visible'] = (string) $data['news_end'] == '0' ? 'never' : 'always';
		}
		else
		{
			$data['visible'] = 'date';
		}
		switch($data['news_is_html'])
		{
			case -1:
				$data['link'] = $data['news_content'];
				unset($data['news_content']);
				break;

			case -2:
				$data['link'] = $data['news_teaser'];
				unset($data['news_teaser']);
				break;
		}
		return $data;
	}

	/**
	 * changes the data from your work-format to the db-format
	 *
	 * reimplemented to adjust the timezone of the timestamps (subtraction $this->tz_offset_s to get server-time)
	 * Please note, we do NOT call the method of the parent so_sql !!!
	 *
	 * @param array $data if given works on that array and returns result, else works on internal data-array
	 * @return array with changed data
	 */
	function data2db($data=null)
	{
		if ($intern = !is_array($data))
		{
			$data = &$this->data;
		}
		switch($data['visible'])
		{
			case 'always':
				$data['news_begin'] = 0;
				$data['news_end'] = null;
				break;
			case 'never':
				$data['news_begin'] = $data['news_end'] = 0;
				break;
			case 'date':
				if (!$data['news_end']) $data['news_end'] = null;
				break;
		}
		if (isset($data['news_lang']) && !$data['news_lang'])
		{
			$data['news_lang'] = null;
		}
		foreach($this->timestamps as $name)
		{
			if (isset($data[$name]) && $data[$name]) $data[$name] -= $this->tz_offset_s;
		}
		return $data;
	}

	/**
	 * saves the content of data to the db
	 *
	 * @param array $keys if given $keys are copied to data before saveing => allows a save as
	 * @param boolean $ignore_acl=false
	 * @return int/boolean 0 on success, true on ACL error and errno != 0 else
	 */
	function save($keys=null,$ignore_acl=false)
	{
		if ($keys) $this->data_merge($keys);

		if (!$this->data['cat_id'] || !$ignore_acl && !$this->check_acl($this->data['news_id'] ? EGW_ACL_EDIT : EGW_ACL_ADD))
		{
			return true;
		}
		if (!$this->data['news_id'])	// new entry
		{
			if (!$this->data['news_date']) $this->data['news_date'] = $this->now;
			if (!isset($this->data['news_submittedby'])) $this->data['news_submittedby'] = $this->user;
		}
		if (!isset($this->data['news_is_html']))
		{
			$this->data['news_is_html'] = 1;
		}
		return parent::save();
	}

	/**
	 * Search / list news
	 *
	 * Reimplemented for different defaults and the "visibile" filter:
	 * - "now" (default if not set): currently active news
	 * - "always": always active news
	 * - "never": deactivated news
	 * - "date": news active by date
	 *
	 * @param array/string $criteria array of key and data cols, OR a SQL query (content for WHERE), fully quoted (!)
	 * @param boolean/string/array $only_keys=false True returns only keys, False returns all cols. or
	 *	comma seperated list or array of columns to return
	 * @param string $order_by='news_date DESC' fieldnames + {ASC|DESC} separated by colons ',', can also contain a GROUP BY (if it contains ORDER BY)
	 * @param string/array $extra_cols='' string or array of strings to be added to the SELECT, eg. "count(*) as num"
	 * @param string $wildcard='' appended befor and after each criteria
	 * @param boolean $empty=false False=empty criteria are ignored in query, True=empty have to be empty in row
	 * @param string $op='AND' defaults to 'AND', can be set to 'OR' too, then criteria's are OR'ed together
	 * @param mixed $start=false if != false, return only maxmatch rows begining with start, or array($start,$num), or 'UNION' for a part of a union query
	 * @param array $filter=null if set (!=null) col-data pairs, to be and-ed (!) into the query without wildcards
	 * @return boolean/array of matching rows (the row is an array of the cols) or False
	 */
	function &search($criteria,$only_keys=false,$order_by='news_date DESC',$extra_cols='',$wildcard='',$empty=False,$op='AND',$start=false,$filter=null,$join=null)
	{
		//error_log(__METHOD__."(".print_r($criteria,true).",$only_keys,$order_by,".print_r($extra_cols,true).",$wildcard,$empty,$op,".print_r($start,true).",".print_r($filter,true).",$join)");
		if (!$join)
		{
			if (is_array($filter) && isset($filter['cat_id']))
			{
				$cats = $filter['cat_id'];
				unset($filter['cat_id']);
			}
			elseif(is_array($criteria) && isset($criteria['cat_id']) && $op == 'AND')
			{
				$cats = $criteria['cat_id'];
				unset($criteria['cat_id']);
			}
			// return only an intersection of the requested cats and the (by ACL) permitted cats
			$permitted_cats = array_keys($this->rights2cats(EGW_ACL_READ));
			if ($cats)
			{
				if (!is_array($cats)) $cats = $this->cats->return_all_children($cats);
				$permitted_cats = array_intersect($cats,$permitted_cats);
			}
			if (!$permitted_cats) return array();	// no rights to any (requested) cat
			$filter['cat_id'] = count($permitted_cats) == 1 ? $permitted_cats[0] : $permitted_cats;

			// if no lang filter set, use the users lang from his prefs
			if (!array_key_exists('news_lang',$filter))
			{
				//echo "<p>no news_lang set in filter --> setting default</p>\n";
				$filter['news_lang'] = $this->lang;
			}
		}
		if (is_array($filter) && isset($filter['visible']))
		{
			$visible = $filter['visible'];
			unset($filter['visible']);
		}
		elseif(is_array($criteria) && isset($criteria['visible']))
		{
			$visible = $criteria['visible'];
			unset($criteria['visible']);
		}
		$today = mktime(0,0,0,date('m'),date('d'),date('Y'));
		//echo "<p align=right>today=$today</p>\n";
		switch($visible)
		{
			case 'all':
				break;

			default:
			case 'now':
				$filter[] = "(news_begin=0 AND news_end IS NULL OR news_begin <= $today AND ($today <= news_end OR news_end IS NULL))";
				break;

			case 'future':
				$filter[] = "news_begin > $today";
				break;

			case 'old':
				$filter[] = "news_end < $today";
				$filter[] = 'news_end != 0';
				break;

			case 'always':
				$filter['news_begin'] = 0;
				$filter[] = 'news_end IS NULL';
				break;

			case 'never':
				$filter['news_end'] = 0;
				break;

			case 'date':
				$filter[] = 'news_begin > 0';
				break;
		}
		// show only the selected language or the default language, if no translation exists
		if (isset($filter['news_lang']))
		{
			$filter[] = '(news_lang='.$this->db->quote($filter['news_lang']).' OR news_lang IS NULL AND '.
				 "NOT EXISTS (SELECT news_id FROM $this->table_name translation WHERE $this->table_name.news_id = translation.news_source_id AND translation.news_lang = ".$this->db->quote($filter['news_lang']).'))';
			unset($filter['news_lang']);
		}
		return parent::search($criteria,$only_keys,$order_by,$extra_cols,$wildcard,$empty,$op,$start,$filter,$join);
	}

	/**
	 * Read one news
	 *
	 * reimplemented to check ACL
	 *
	 * @param array/int $keys array with keys or integer news_id
	 * @return array/boolean the news or false on error or not found
	 */
	function read($keys)
	{
		if (!is_array($keys) && (int)$keys) $keys = array('news_id' => (int)$keys);

		if ($keys['news_lang'])
		{
			// (news_id=$id AND news_lang IS NULL) OR (news_source_id=$id AND news_lang='$lang'
			if (!(list($this->data) = self::search(array(),false,'','','',false,'AND',false,array(
				'news_source_id'=> $keys['news_id'],
				'news_lang' => $keys['news_lang'] ? $keys['news_lang'] : null,
			))))
			{
				return false;
			}
		}
		elseif (!parent::read($keys) || !$this->check_acl(EGW_ACL_READ))
		{
			return false;
		}
		return $this->data;
	}

	/**
	 * Set new default entry for all existing translations
	 *
	 * @param int $old_id=null old news_source_id, default content of $this->data['news_source_id']
	 * @param int $new_id=null new news_source_id, default content of $this->data['news_id']
	 */
	function set_default($old_id=null,$new_id=null)
	{
		if (!$old_id) $old_id = $this->data['news_source_id'];
		if (!$new_id) $new_id = $this->data['news_id'];

		// set default on all existing ones
		$this->db->update($this->table_name,array(
			'news_source_id' => $new_id,
		),$this->db->expression($this->table_name,array('news_source_id' => $old_id),' OR ',array('news_id' => $old_id)),
		__LINE__,__FILE__);

		// remove the default from the new default
		$this->db->update($this->table_name,array(
			'news_source_id' => null,
		),array(
			'news_id' => $new_id
		),__LINE__,__FILE__);
	}

	/**
	 * Check if user has the necessary rights for a given operation
	 *
	 * @param int $rights=EGW_ACL_READ
	 * @param array $data=null array with news or null to use $this->data
	 * @return boolean true if use has the necessary rights, false otherwise
	 */
	function check_acl($rights=EGW_ACL_READ,$data=null)
	{
		if ($rights == EGW_ACL_EDIT || $rights == EGW_ACL_DELETE) $rights = EGW_ACL_ADD;	// no edit or delete rights at the moment

		if (is_null($data)) $data =& $this->data;

		if (is_array($data))
		{
			if (!$data['news_id'] && $rights != EGW_ACL_ADD)	// new items can only be added
			{
				return false;
			}
			$cat_id = $data['cat_id'];
		}
		else
		{
			$cat_id = (int) $data;
		}
		return $this->acl->is_permitted($cat_id,$rights);
	}

	/**
	 * Returns the cats the user has certain rights to
	 *
	 * @param int $rights
	 * @return array with cat_id => name pairs
	 */
	function rights2cats($rights=EGW_ACL_READ)
	{
		static $all_cats;
		if (!is_array($all_cats))
		{
			if (!($all_cats = $this->cats->return_array('all',0,False,'','','cat_name',True))) $all_cats = array();
		}
		if ($rights == EGW_ACL_EDIT) $rights = EGW_ACL_ADD;	// no edit rights at the moment

		$cats = array();
		foreach($all_cats as $cat)
		{
			if ($this->acl->is_permitted($cat['id'],$rights))
			{
				$cats[$cat['id']] = str_repeat('&nbsp;',$cat['level']).stripslashes($cat['name']).
					(categories::is_global($cat) ? ' &#9830;' : '');
			}
		}
		return $cats;
	}

	/**
	 * List news categories
	 *
	 * @param array $query
	 * @param array &$cats returned rows
	 * @return int total number of entries
	 */
	function get_cats($query,&$cats,&$readonlys=null,$ignore_acl=false)
	{
		$criteria = array();
		if ($query['search'])
		{
			foreach(array('cat_name','cat_description') as $name)
			{
				$criteria[$name] = $query['search'];
			}
		}

		$order = "GROUP BY {$this->cats->table}.cat_id, cat_name,cat_description,cat_data,cat_parent,cat_owner,cat_appname ORDER BY ".($query['order'] ? $query['order'] : 'cat_name').' '.$query['sort'];
		$join  = "RIGHT JOIN {$this->cats->table} ON $this->table_name.cat_id={$this->cats->table}.cat_id";
		$filter = array(
			'visible'=>'all',
			"cat_appname='news_admin'",
		);
		if (is_array($query['col_filter'])) $filter += $query['col_filter'];
		if (!$ignore_acl && !isset($GLOBALS['egw_info']['user']['apps']['admin'])) $filter[] = "(cat_owner=$this->user OR cat_owner=".categories::GLOBAL_ACCOUNT.")";
		$cats = $this->search($criteria,'1',$order,array(
			'cat_name','cat_description','cat_data','cat_parent','cat_owner','cat_appname',
			'count(news_content) AS num_news','MAX(news_date) AS news_date',$this->cats->table.'.cat_id AS cat_id',
		),'%',false,'OR',array($query['start'],$query['num_rows']),$filter,$join);
		if (!$cats) $cats = array();

		foreach($cats as $k => $cat)
		{
			$cats[$k] += $this->_cat_rights($cat['cat_id']);
			if ($cat['cat_data'] && is_array($data = unserialize($cat['cat_data']))) $cats[$k] += $data;
			if ($cats[$k]['import_url']) $cats[$k]['import_host'] = parse_url($cats[$k]['import_url'],PHP_URL_HOST);
		}
		//_debug_array($cats);
		return $this->total;
	}

	/**
	 * Read one category plus extra data
	 *
	 * @param int $cat_id
	 * @return array/boolean category data (with 'cat_' prefix) or false
	 */
	function read_cat($cat_id)
	{
		if (!($cat = categories::read($cat_id)))
		{
			return false;
		}
		$data = $this->_cat_rights($cat_id);
		foreach($cat as $name => $value)
		{
			$data['cat_'.$name] = $value;
			if ($name == 'data' && $value) $data += $value;
		}
		$data['old_parent'] = $data['cat_parent'];	// to determine it got modified

		return $data;
	}

	/**
	 * Check if the current user has rights to administrate a category
	 *
	 * @param array $cat
	 * @return boolean
	 */
	function admin_cat($cat)
	{
		if (!$cat) return false;

		if (!is_array($cat)) $cat = $this->read_cat($cat);

		return $cat && ($cat['cat_owner'] == $this->user || isset($GLOBALS['egw_info']['user']['apps']['admin']));
	}

	/**
	 * Save the category data
	 *
	 * @param array $cat
	 * @return boolean/int cat_id on success, false otherwise
	 */
	function save_cat($cat)
	{
		if (!is_array($cat) || !$this->admin_cat($cat)) return false;

		$cat['cat_data'] = $cat['cat_data'] ? unserialize($cat['cat_data']) : array();
		foreach(array('import_url','import_frequency') as $name)
		{
			$cat['cat_data'][$name] = $cat[$name];
		}
		$cat['cat_data'] = serialize($cat['cat_data']);
		if (!$cat['cat_access']) $cat['cat_access'] = 'public';

		foreach($cat as $name => $value)
		{
			if ($name == 'cat_description')
			{
				$name = 'descr';
			}
			elseif (substr($name,0,4) == 'cat_')
			{
				$name = substr($name,4);
			}
			$cat[$name] = $value;
		}
		if ($cat['cat_id'])
		{
			$cat['cat_id'] = $this->cats->edit($cat);
		}
		else
		{
			if (empty($cat['cat_owner'])) $cat['cat_owner'] =  categories::GLOBAL_ACCOUNT;
			// cat owner can only be set for new cats!
			if ($cat['cat_owner'] == categories::GLOBAL_ACCOUNT)
			{
				$this->cats->account_id = categories::GLOBAL_ACCOUNT;	// othwerwise the current use get set
			}
			$cat['cat_id'] = $this->cats->add($cat);
		}
		if ($cat['cat_id'])
		{
			$this->acl->set_rights($cat['cat_id'],$cat['cat_readable'],$cat['cat_writable']);

			if ($cat['import_url']) $this->_setup_async_job();
		}
		return $cat['cat_id'];
	}

	/**
	 * Install an async job once per hour to import the feeds
	 *
	 */
	function _setup_async_job()
	{
		require_once(EGW_API_INC.'/class.asyncservice.inc.php');

		$async = new asyncservice();
		//$async->cancel_timer('news_admin-import');

		if (!$async->read('news_admin-import'))
		{
			$async->set_timer(array('hour' => '*'),'news_admin-import','news_admin.news_admin_import.async_import',null);
		}
	}

	/**
	 * Delete a category include the posts
	 *
	 * @param array/inc $cat array or integer cat_id
	 * @return boolean true on success false otherwise
	 */
	function delete_cat($cat)
	{
		$cat_id = is_array($cat) ? $cat['cat_id'] : $cat;

		if (!$cat_id || !$this->admin_cat($cat)) return false;

		$this->delete(array('cat_id' => $cat_id));

		$this->cats->delete($cat_id,false,true);	// reparent the subs to our parent

		return true;
	}

	/**
	 * Read the rights of one cat from the ACL
	 *
	 * @param int $cat_id
	 * @return array of 2 arrays with account_id's for keys 'cat_readable' and 'cat_writable'
	 */
	function _cat_rights($cat_id)
	{
		$cat = array();
		if (($rights = $GLOBALS['egw']->acl->get_all_rights('L'.$cat_id,'news_admin')))
		{
			foreach($rights as $user => $right)
			{
				if ($right & EGW_ACL_ADD)  $cat['cat_writable'][] = $user;
				if ($right & EGW_ACL_READ) $cat['cat_readable'][] = $user;
			}
		}
		return $cat;
	}

	/**
	 * Check if the XML_Feed_Parser class is available
	 *
	 * @return boolean
	 */
	function import_available()
	{
		return PHP_VERSION >= 5 && include_once('XML/Feed/Parser.php');
	}
}
