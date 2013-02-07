<?php
	/**************************************************************************\
	* eGroupWare - News                                                        *
	* http://www.egroupware.org                                                *
	* --------------------------------------------                             *
	*  This program is free software; you can redistribute it and/or modify it *
	*  under the terms of the GNU General Public License as published by the   *
	*  Free Software Foundation; either version 2 of the License, or (at your  *
	*  option) any later version.                                              *
	* --------------------------------------------                             *
	\**************************************************************************/

	/* $Id: class.boacl.inc.php 26511 2009-01-13 11:21:48Z leithoff $ */

	class boacl
	{
		var $acl;
		var $start = 0;
		var $query = '';
		var $sort  = '';
		var $total = 0;
		var $accounts;
		var $cats;

		var $debug;
		var $use_session = False;

		function boacl($session=False)
		{
			//error_log(__METHOD__."($name)".function_backtrace());
			#$this->accounts = $GLOBALS['egw']->accounts->get_list();
			$this->debug = False;
			//all this is only needed when called from uiacl. not from ui,
			if($session)
			{
				$this->read_sessiondata();
				if (!is_array($this->accounts) || !isset($this->accounts)) $this->accounts = $GLOBALS['egw']->accounts->get_list();
				$this->use_session = True;
				foreach(array('start','query','sort','order') as $var)
				{
					if (isset($_POST[$var]))
					{
						$this->$var = $_POST[$var];
					}
					elseif (isset($_GET[$var]))
					{
						$this->$var = $_GET[$var];
					}
				}
				$this->catbo =& CreateObject('phpgwapi.categories');
//				$main_cat = array(array('id' => 0, 'name' => lang('Global news')));
//				$this->cats = array_merge($main_cat,$this->catbo->return_array('all',$this->start,True,$this->query,$this->sort,'cat_name',True));
				$this->cats = $this->catbo->return_array('all',$this->start,True,$this->query,$this->sort,'cat_name',True);
			}
			$this->permissions = $this->get_permissions(True);
		}

		function save_sessiondata()
		{
			$data = array(
				'start' => $this->start,
				'query' => $this->query,
				'sort'  => $this->sort,
				'order' => $this->order,
				'limit' => $this->limit,
				'accounts' => $this->accounts,
			);
			if($this->debug) { echo '<br>Read:'; _debug_array($data); }
			$GLOBALS['egw']->session->appsession('session_data','news_admin_acl',$data);
		}

		function read_sessiondata()
		{
			$data = $GLOBALS['egw']->session->appsession('session_data','news_admin_acl');
			if($this->debug) { echo '<br>Read:'; _debug_array($data); }

			$this->start  = $data['start'];
			$this->query  = $data['query'];
			$this->sort   = $data['sort'];
			$this->order  = $data['order'];
			$this->limit = $data['limit'];
			$this->accounts = $data['accounts'];
		}

		function get_rights($cat_id)
		{
			return $GLOBALS['egw']->acl->get_all_rights('L'.$cat_id,'news_admin');
		}

		function is_permitted($cat_id,$right)
		{
			return $this->permissions['L'.$cat_id] & $right;
		}

		function is_readable($cat_id)
		{
			return $this->is_permitted($cat_id,EGW_ACL_READ);
		}

		function is_writeable($cat_id)
		{
			return $this->is_permitted($cat_id,EGW_ACL_ADD);
		}

		function set_rights($cat_id,$read,$write)
		{
			// fetch it if not existing
			if (!is_array($this->accounts) || !isset($this->accounts)) $this->accounts = $GLOBALS['egw']->accounts->get_list();
			$readcat = $read ? $read : array();
			$writecat = $write ? $write : array();

			$GLOBALS['egw']->acl->delete_repository('news_admin','L' . $cat_id,false);

			foreach($this->accounts as $account)
			{
				$account_id = $account['account_id'];
				//write implies read
				$rights = in_array($account_id,$writecat) ?
					(EGW_ACL_READ | EGW_ACL_ADD) :
					(in_array($account_id,$readcat) ? EGW_ACL_READ : False);

				if ($rights)
				{
					$GLOBALS['egw']->acl->add_repository('news_admin','L'.$cat_id,$account_id,$rights);
				}
			}
		}

		//access permissions for current user
		function get_permissions($inc_groups = False)
		{
			return $GLOBALS['egw']->acl->get_all_location_rights($GLOBALS['egw_info']['user']['account_id'],'news_admin',$inc_groups);
		}
	}
