<?php
	/**************************************************************************\
	* eGroupWare SiteMgr - Web Content Management                              *
	* http://www.egroupware.org                                                *
	* SQL reworked by RalfBecker@outdoor-training.de to get everything quoted  *
	* --------------------------------------------                             *
	*  This program is free software; you can redistribute it and/or modify it *
	*  under the terms of the GNU General Public License as published by the   *
	*  Free Software Foundation; either version 2 of the License, or (at your  *
	*  option) any later version.                                              *
	\**************************************************************************/

	/* $Id: class.Categories_SO.inc.php 33188 2010-11-28 20:27:05Z ralfbecker $ */

	class Categories_SO
	{
		/**
		 * Instance of the categories class
		 *
		 * @var categories
		 */
		var $cats;
		/**
		 * Instance of the db-class
		 *
		 * @var egw_db
		 */
		var $db;
		var $site_id;

		function Categories_SO()
		{
			$this->cats = new categories(categories::GLOBAL_ACCOUNT,'sitemgr');
			$this->db = clone($GLOBALS['egw']->db);
			$this->db->set_app('sitemgr');
			$this->state_table = 'egw_sitemgr_categories_state';
			$this->lang_table = 'egw_sitemgr_categories_lang';
		}

		function isactive($cat_id,$states=false)
		{
			if (!$states)
			{
				$states = $GLOBALS['Common_BO']->visiblestates;
			}
			$this->db->select($this->state_table,'cat_id',array(
				'cat_id' => $cat_id,
				'state'  => $states,
			),__LINE__,__FILE__);

			return $this->db->num_rows();
		}

		function getChildrenIDList($parent)
		{
			return $this->cats->return_array('all','',False,'','','cat_data',False,$parent,-1,'id');
		}

		function addCategory($name, $description, $parent = False)
		{
			$cat_id = (int) $this->cats->add(array(
				'name'    => $name,
				'descr'   => $description,
				'access'  => 'public',
				'parent'  => $parent,
				'old_parent' => $parent
			));
			$this->db->insert($this->state_table,array('cat_id'=>$cat_id),False, __LINE__,__FILE__);

			return $cat_id;
		}

		function removeCategory($cat_id)
		{
			$this->cats->delete($cat_id,False,True);

			$this->db->delete($this->lang_table,array('cat_id'=>$cat_id),__LINE__,__FILE__);
			$this->db->delete($this->state_table,array('cat_id'=>$cat_id),__LINE__,__FILE__);

			return True;
		}

		function saveCategory($cat_info)
		{
			$data = array
			(
				'name'    => $cat_info->name,
				'descr'   => $cat_info->description,
				'data'    => sprintf('%04d',$cat_info->sort_order),
				'access'  => 'public',
				'id'    => (int) $cat_info->id,
				'parent'  => (int) $cat_info->parent,
				'old_parent' => (int) $cat_info->old_parent,
			);
			$this->cats->edit($data);

			return $this->db->update($this->state_table,array(
				'state' => $cat_info->state,
				'index_page_id' => $cat_info->index_page_id,
			),array('cat_id'=>$cat_info->id),__LINE__,__FILE__);
		}

		/**
		 * Save named page as category index
		 *
		 * @param int $cat_id
		 * @param int $page
		 */
		function saveCategoryIndex($cat_id,$page)
		{
			$this->db->update($this->state_table,array(
					'index_page_id' => $page,
				),array('cat_id' => $cat_id),__LINE__,__FILE__);
		}

		function saveCategoryLang($cat_id, $cat_name, $cat_description, $lang)
		{
			return $this->db->insert($this->lang_table,array(
				'name'   => $cat_name,
				'description' => $cat_description,
			),array(
				'cat_id' => $cat_id,
				'lang'   => $lang,
			),__LINE__,__FILE__);
		}

		function getlangarrayforcategory($cat_id)
		{
			$retval = array();
			foreach($this->db->select($this->lang_table,'lang',array('cat_id'=>$cat_id),__LINE__,__FILE__) as $row)
			{
				$retval[] = $row['lang'];
			}
			return $retval;
		}

		function getCategory($cat_id,$lang=False)
		{
			$cat = categories::read($cat_id);

			if (is_array($cat))
			{
				$cat_info       =& CreateObject('sitemgr.Category_SO', True);
				$cat_info->id     = $cat['id'];
				$cat_info->sort_order = (int) $cat['data'];
				$cat_info->parent   = $cat['parent'];
				$cat_info->depth    = $cat['level'];
				$cat_info->root     = $cat['main'];

				$this->db->select($this->state_table,array('state','index_page_id'),array('cat_id'=>$cat_id),__LINE__,__FILE__);
				if ($this->db->next_record())
				{
					$cat_info->state = $this->db->f('state');
					$cat_info->index_page_id = (int) $this->db->f('index_page_id');
				}
				if ($lang)
				{
					$this->db->select($this->lang_table,'*',array(
						'cat_id' => $cat_id,
						'lang'   => $lang,
					),__LINE__,__FILE__);
					if ($this->db->next_record())
					{
						$cat_info->name = stripslashes($this->db->f('name'));
						$cat_info->description = stripslashes($this->db->f('description'));
					}
				}
				else  //if there is no lang argument we return the content in whatever languages turns up first
				{
					$this->db->select($this->lang_table,'*',array('cat_id' => $cat_id,),__LINE__,__FILE__);
					if ($this->db->next_record())
					{
						$cat_info->name = stripslashes($this->db->f('name'));
						$cat_info->description = stripslashes($this->db->f('description'));
						$cat_info->lang = $this->db->f('lang');
					}
					else
					{
						$cat_info->name = "This category has no data in any langugage: this should not happen";
					}
				}
				return $cat_info;
			}
			return False;
		}

		function removealllang($lang)
		{
			$this->db->delete($this->lang_table,array('lang'=>$lang), __LINE__,__FILE__);
		}

		function migratealllang($oldlang,$newlang)
		{
			$this->db->update($this->lang_table,array('lang'=>$newlang),array('lang'=>$old_lang), __LINE__,__FILE__);
		}

		function commit($cat_id)
		{
			$this->db->update($this->state_table,array('state'=>SITEMGR_STATE_PUBLISH),array('state'=>SITEMGR_STATE_PREPUBLISH), __LINE__,__FILE__);
			$this->db->update($this->state_table,array('state'=>SITEMGR_STATE_ARCHIVE),array('state'=>SITEMGR_STATE_PREUNPUBLISH), __LINE__,__FILE__);
		}

		function reactivate($cat_id)
		{
			$this->db->update($this->state_table,array('state'=>SITEMGR_STATE_DRAFT),array('state'=>SITEMGR_STATE_ARCHIVE), __LINE__,__FILE__);
		}
	}
