<?php
	/**************************************************************************\
	* eGroupWare SiteMgr - Web Content Management                              *
	* http://www.egroupware.org                                                *
	* Rewritten with the new db-functions by RalfBecker-AT-outdoor-training.de *
	* --------------------------------------------                             *
	*  This program is free software; you can redistribute it and/or modify it *
	*  under the terms of the GNU General Public License as published by the   *
	*  Free Software Foundation; either version 2 of the License, or (at your  *
	*  option) any later version.                                              *
	\**************************************************************************/

	/* $Id: class.Pages_SO.inc.php 29103 2010-02-04 02:20:55Z ralfbecker $ */

	class Pages_SO
	{
		var $db;
		var $pages_table,$pages_lang_table;

		function Pages_SO()
		{
			$this->db = clone($GLOBALS['egw']->db);
			$this->db->set_app('sitemgr');

			foreach(array('pages','pages_lang') as $name)
			{
				$var = $name.'_table';
				$this->$var = 'egw_sitemgr_'.$name;	// only reference to the db-prefix
			}
		}

		//if $cats is an array, pages from this list are retrieved,
		//is $cats is an int, pages from this cat are retrieved,
		//if $cats is 0 or false, pages from currentcats are retrieved
		function getPageIDList($cats=False,$states=false)
		{
			if (!$states)
			{
				$states = $GLOBALS['Common_BO']->visiblestates;
			}
			if (!$cats)
			{
				$cats = $GLOBALS['Common_BO']->cats->currentcats;
			}

			$page_id_list = array();
			if ($cats)
			{
				$where = array('cat_id' => $cats);
				if ($states)
				{
					$where['state'] = $states;
				}
				$this->db->select($this->pages_table,'page_id',$where,__LINE__,__FILE__,False,
					'ORDER BY cat_id, sort_order ASC'); 

				while ($this->db->next_record())
				{
					$page_id_list[] = $this->db->f('page_id');
				}
			}
			return $page_id_list;
		}

		function addPage($cat_id)
		{
			$this->db->insert($this->pages_table,array('cat_id'=>$cat_id),False, __LINE__,__FILE__);

			return $this->db->get_last_insert_id($this->pages_table,'page_id');
		}

		function removePage($page_id)
		{
			$this->db->delete($this->pages_table,array('page_id' => $page_id), __LINE__,__FILE__);
			$this->db->delete($this->pages_lang_table,array('page_id' => $page_id), __LINE__,__FILE__);
		}

		//this function should be a deprecated function - IMHO - skwashd
		function pageExists($page_name, $exclude_page_id='')
		{
			$page_id = $this->PagetoID($page_name);
			if($page_id)
			{
				return ($page_id != $exclude_page_id ? $page_id : False);
			}
			else
			{
				return False;
			}
		}

		function getlangarrayforpage($page_id)
		{
			$this->db->select($this->pages_lang_table,'lang',array('page_id' => $page_id),__LINE__,__FILE__);
			
			$retval = array();
			while ($this->db->next_record())
			{
				$retval[] = $this->db->f('lang');
			}
			return $retval;
		}

		function PagetoID($page_name)
		{
			$cats = new categories(categories::GLOBAL_ACCOUNT, 'sitemgr');
			$cat_list = $cats->return_sorted_array(0, False, '', '', '', False, CURRENT_SITE_ID);
			
			if($cat_list)
			{
				foreach($cat_list as $val)
				{
					$site_cats[] = $val['id'];
				}
			}
			$where = array('name' => $page_name);
			if($site_cats)
			{
				$where['cat_id'] = $site_cats;
			}
			$this->db->select($this->pages_table,'page_id',$where,__LINE__,__FILE__);

			if ($this->db->next_record())
			{
				return $this->db->f('page_id');
			}
			return false;
		}

		function getcatidforpage($page_id)
		{
			$this->db->select($this->pages_table,'cat_id',array('page_id'=>$page_id),__LINE__,__FILE__);

			if ($this->db->next_record())
 			{
				return $this->db->f('cat_id');
			}
			return false;
		}

		function getPage($page_id,$lang=False)
		{
			$where = array('page_id'=>$page_id);
			$this->db->select($this->pages_table,'*',$where,__LINE__,__FILE__);

			if ($this->db->next_record())
			{
				$page =& CreateObject('sitemgr.Page_SO', True);
				$page->id = $page_id;
				$page->cat_id = $this->db->f('cat_id');
				$page->sort_order = (int) $this->db->f('sort_order');
				$page->name = stripslashes($this->db->f('name'));
				$page->hidden = $this->db->f('hide_page');
				$page->state = $this->db->f('state');
				
				if ($lang)
				{
					$where['lang'] = $lang;
				}
				$this->db->select($this->pages_lang_table,'*',$where,__LINE__,__FILE__);
				
				if ($this->db->next_record())
				{
					$page->title= stripslashes($this->db->f('title'));
					$page->subtitle = stripslashes($this->db->f('subtitle'));
					$page->lang = $lang;
				}
				else
				{
					$page->title = $lang ? lang("not yet translated") :
						"This page has no data in any langugage: this should not happen";
				}
				return $page;
			}
			return false;
		}

		function savePageInfo($pageInfo)
		{
			return $this->db->update($this->pages_table,array(
					'cat_id'	=> $pageInfo->cat_id,
					'name'		=> $pageInfo->name,
					'sort_order'=> $pageInfo->sort_order,
					'hide_page'	=> $pageInfo->hidden,
					'state'		=> $pageInfo->state,
				),array(
					'page_id' 	=> $pageInfo->id			
				), __LINE__,__FILE__);
		}
		
		function savePageLang($pageInfo,$lang)
		{
			return $this->db->insert($this->pages_lang_table,array(
					'title'		=> $pageInfo->title,
					'subtitle'	=> $pageInfo->subtitle,
				),array(
					'page_id' 	=> $pageInfo->id,
					'lang'		=> $lang,			
				), __LINE__,__FILE__);
		}

		function removealllang($lang)
		{
			$this->db->delete($this->pages_lang_table,array('lang'=>$lang), __LINE__,__FILE__);
		}

		function migratealllang($oldlang,$newlang)
		{
			$this->db->update($this->pages_lang_table,array(
					'lang' => $newlang
				),array(
					'lang' => $oldlang
				), __LINE__,__FILE__);
		}

		function commit($page_id)
		{
			foreach(array(
				SITEMGR_STATE_PREPUBLISH => SITEMGR_STATE_PUBLISH,
				SITEMGR_STATE_PREUNPUBLISH => SITEMGR_STATE_ARCHIVE
			) as $from => $to)
			{
				$this->db->update($this->pages_table,array(
						'state' 	=> $to
					),array(
						'state' 	=> $from,
						'page_id' 	=> $page_id,
					),__LINE__,__FILE__);
			}
		}

		function reactivate($page_id)
		{
			$this->db->update($this->pages_table,array(
					'state' 	=> SITEMGR_STATE_DRAFT
				),array(
					'state'		=> SITEMGR_STATE_ARCHIVE,
					'page_id' 	=> $page_id,
				),__LINE__,__FILE__);
		}
	}
?>
