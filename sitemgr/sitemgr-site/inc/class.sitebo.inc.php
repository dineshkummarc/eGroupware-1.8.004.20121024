<?php
	/**************************************************************************\
	* eGroupWare SiteMgr - Web Content Management                              *
	* http://www.egroupware.org                                                *
	* --------------------------------------------                             *
	*  This program is free software; you can redistribute it and/or modify it *
	*  under the terms of the GNU General Public License as published by the   *
	*  Free Software Foundation; either version 2 of the License, or (at your  *
	*  option) any later version.                                              *
	\**************************************************************************/

	/* $Id: class.sitebo.inc.php 26337 2008-11-11 09:40:53Z ralfbecker $ */

	class sitebo
	{
		var $pages_bo;
		var $catbo;
		var $acl;

		function sitebo()
		{
			$this->catbo = &$GLOBALS['Common_BO']->cats;
			$this->pages_bo = &$GLOBALS['Common_BO']->pages;
			$this->acl = &$GLOBALS['Common_BO']->acl;
			//$anonymous_user is globally set in config.inc.php
			$this->isuser = ($GLOBALS['egw_info']['user']['account_lid'] != $GLOBALS['sitemgr_info']['anonymous_user']);
		}

		function is_admin()
		{
			return $this->acl->is_admin();
		}

		function getcatwrapper($cat_id)
		{
			$availablelangsforcat = $this->catbo->getlangarrayforcategory($cat_id);
			if (in_array($GLOBALS['sitemgr_info']['userlang'],$availablelangsforcat))
			{
				return $this->catbo->getCategory($cat_id,$GLOBALS['sitemgr_info']['userlang']);
			}
			else
			{
				foreach ($GLOBALS['sitemgr_info']['sitelanguages'] as $lang)
				{
					if (in_array($lang,$availablelangsforcat))
					{
						return $this->catbo->getCategory($cat_id,$lang);
					}
				}
			}
			//fall back to a category in "default" lang
			return $this->catbo->getCategory($cat_id);
		}

		function getpagewrapper($page_id)
		{
			$availablelangsforpage = $this->pages_bo->getlangarrayforpage($page_id);
			if (in_array($GLOBALS['sitemgr_info']['userlang'],$availablelangsforpage))
			{
				return $this->pages_bo->GetPage($page_id,$GLOBALS['sitemgr_info']['userlang']);
			}
			else
			{
				foreach ($GLOBALS['sitemgr_info']['sitelanguages'] as $lang)
				{
					if (in_array($lang,$availablelangsforpage))
					{
						return $this->pages_bo->GetPage($page_id,$lang);
					}
				}
			}
			//fall back to a page in "default" lang
			return $this->pages_bo->GetPage($page_id);
		}

		function loadPage($page_id)
		{
			global $page;
			$page = $this->getpagewrapper($page_id);
		}

		function loadIndex()
		{
			global $page;
			$page->title = lang('Site Index');
			$page->subtitle = '';
			$page->index = True;
			$page->block =& CreateObject('sitemgr.Block_SO',True);
			$page->block->module_name = 'navigation';
			$page->block->arguments = array('nav_type' => 2);		// former index
			$page->block->module_id = $GLOBALS['Common_BO']->modules->getmoduleid('navigation');
			$page->block->view = SITEMGR_VIEWABLE_EVERBODY;
			$page->block->status = SITEMGR_STATE_PUBLISH;
			$page->cat_id = CURRENT_SITE_ID;
			return true;
		}

		function loadSearchResult($search_result,$lang,$mode,$options)
		{
			global $page;
			$page->title = lang('search');
			$page->subtitle = '';
			$page->index = True;
			$page->block =& CreateObject('sitemgr.Block_SO',True);
			$page->block->module_name = 'search';
			$page->block->arguments = array(
					'search_result'=> $search_result,
					'show_results' => true,
					'lang'         => $lang,
					'mode'         => $mode,
					'options'      => $options,
					);
			$page->block->module_id = $GLOBALS['Common_BO']->modules->getmoduleid('search');
			$page->block->view = SITEMGR_VIEWABLE_EVERBODY;
			$page->block->status = SITEMGR_STATE_PUBLISH;
			$page->cat_id = CURRENT_SITE_ID;
			return true;
		}

		function getIndex($showhidden=true,$rootonly=false,$subtitles=False)
		{
			$cats = $this->getCatLinks(0,!$rootonly,$subtitles);
			$index = array();

			if (count($cats)>0)
			{
				$content = "\n".'<ul>';
				foreach($cats as $cat_id => $cat)
				{
					$pages = $this->getPageLinks($cat_id,$showhidden,$subtitles);
					if (count($pages)>0)
					{
						foreach($pages as $page_id => $link)
						{
							$index[] = array(
								'cat_id'=>$cat_id,
								'catname'=>$cat['name'],
								'catdepth'=>$cat['depth'],
								'catlink'=>$cat['link'],
								'catdescrip'=>$cat['description'],
								'page_id' => $page_id,
								'pagename'=>$link['name'],
								'pagelink'=>$link['link'],
								'pagetitle'=>$link['title'],
								'pagesubtitle'=>$link['subtitle'],
								'cat_url'=>$cat['cat_url'],
								'page_url'=>$link['page_url'],
							);
						}
					}
					else
					{
						$index[] = array(
							'cat_id'=>$cat_id,
							'catname'=>$cat['name'],
							'catdepth'=>$cat['depth'],
							'catdescrip'=>$cat['description'],
							'catlink'=>$cat['link'],
							'pagelink'=>lang('No pages available'),
							'cat_url'=> $cat['cat_url'],
						);
					}
				}
			}
			return $index;
		}

		function loadTOC($category_id=false)
		{
			global $page;

			$page->title = lang('Table of Contents');
			$page->subtitle = '';
			$page->toc = True;
			$page->cat_id = $category_id ? $category_id : CURRENT_SITE_ID;
			$page->block =& CreateObject('sitemgr.Block_SO',True);
			$page->block->module_name = 'navigation';
			$page->block->arguments = array('category_id' => $category_id,'nav_type' => 6);		// former toc
			$page->block->module_id = $GLOBALS['Common_BO']->modules->getmoduleid('navigation');
			$page->block->view = SITEMGR_VIEWABLE_EVERBODY;
			$page->block->state = SITEMGR_STATE_PUBLISH;
			return true;
		}

		function getPageLinks($category_id, $showhidden=true,$subtitle=False)
		{
			$pages=$this->pages_bo->getPageIDList($category_id);
			foreach($pages as $page_id)
			{
				$page=$this->getpagewrapper($page_id);
				if ($showhidden || !$page->hidden)
				{
					$pglinks[$page_id] = array(
						'name'=>$page->name,
						'link'=>'<a href="'.sitemgr_link('page_name='.$page->name).
							($subtitle ? '" title="'.$page->subtitle : '').'">'.$page->title.'</a>',
						'title'=>$page->title,
						'subtitle'=>$page->subtitle,
						'page_url'=> sitemgr_link('page_name='.$page->name),

					);
				}
			}
			return $pglinks;
		}

		function getCatLinks($cat_id=0,$recurse=true,$description=False)
		{
			$catlinks = array();
			$cat_list = $this->catbo->getpermittedcatsRead($cat_id,$recurse);
			foreach($cat_list as $cat_id)
			{
				$category = $this->getcatwrapper($cat_id);
				$catlinks[$cat_id] = array(
					'name'=>$category->name,
					'link'=>'<a href="'.sitemgr_link('category_id='.$cat_id).
						($description ? '" title="'.$category->description : '').'">'.$category->name.'</a>',
					'description'=>$category->description,
					'depth'=>$category->depth,
					'cat_url'=>sitemgr_link('category_id='.$cat_id),
				);
			}
			return $catlinks;
		}

		function check_load_translations($lang)
		{
			$GLOBALS['egw_info']['user']['preferences']['common']['lang'] = $GLOBALS['sitemgr_info']['userlang'] = $lang;

			//since there are lang calls in the API, and the first lang call builds $GLOBAL['lang'], we have to re-initialise
			if ($GLOBALS['egw']->translation->userlang != $lang)
			{
				$GLOBALS['egw']->translation->init();		// unset $GLOBALS[lang] and re-reads
			}
			$GLOBALS['egw']->translation->add_app('sitemgr');		// as we run as sitemgr-site
		}

		//like $GLOBALS['egw']->common->getPreferredLanguage,
		//but compares languages accepted by the user
		//to the languages the website is configured for
		//instead of the languages installed in egroupware
		function setsitemgrPreferredLanguage()
		{
			$supportedLanguages = $GLOBALS['sitemgr_info']['sitelanguages'] ? $GLOBALS['sitemgr_info']['sitelanguages'] : array('en');
			$GLOBALS['sitemgr_info']['userlanguage'] = $postlang = $_GET['lang'];
			if ($postlang && in_array($postlang,$supportedLanguages))
			{
				$GLOBALS['egw']->session->appsession('language','sitemgr-site',$postlang);
				$this->check_load_translations($postlang);
				return;
			}

			$GLOBALS['sitemgr_info']['userlanguage'] = $sessionlang = $GLOBALS['egw']->session->appsession('language','sitemgr-site');
			if ($sessionlang)
			{
				$this->check_load_translations($sessionlang);
				return;
			}

			if ($this->isuser)
			{
				$GLOBALS['sitemgr_info']['userlanguage'] = $userlang = $GLOBALS['egw_info']['user']['preferences']['common']['lang'];
				if (in_array($userlang,$supportedLanguages))
				{
					//we do not touch $GLOBALS['egw_info']['user']['preferences']['common']['lang'] if
					//the user is registered and his lang preference is supported by the website,
					//but save it to the appsession for quicker retrieval
					$GLOBALS['egw']->session->appsession('language','sitemgr-site',$userlang);
					$this->check_load_translations($userlang);
					return;
				}
			}

			// create a array of languages the user is accepting
			$userLanguages = explode(',',$_SERVER['HTTP_ACCEPT_LANGUAGE']);

			// find usersupported language
			while (list($key,$value) = each($userLanguages))
			{
				// remove everything behind '-' example: de-de
				$value = trim($value);
				$pieces = explode('-', $value);
				$value = $pieces[0];
				//print "current lang $value<br>";
				if (in_array($value,$supportedLanguages))
				{
					$GLOBALS['sitemgr_info']['userlanguage'] = $browserlang = $value;
					break;
				}
			}

			// no usersupported language found -> return the first entry of sitelanguages
			if (empty($browserlang))
			{
				$GLOBALS['sitemgr_info']['userlanguage'] = $browserlang = $supportedLanguages[0];
			}

			$GLOBALS['egw']->session->appsession('language','sitemgr-site',$browserlang);
			$this->check_load_translations($browserlang);
		}

		function getmode()
		{
			static $allowed_modes = array('Production'=>True,'Draft'=>True,'Edit'=>True);
			if ($this->isuser)
			{
				$postmode = $_GET['mode'];
				if (isset($allowed_modes[$postmode]))
				{
					$GLOBALS['egw']->session->appsession('mode','sitemgr-site',$postmode);
					return $postmode;
				}
				$sessionmode = $GLOBALS['egw']->session->appsession('mode','sitemgr-site');
				if(isset($allowed_modes[$sessionmode]))
				{
					return $sessionmode;
				}
			}
			return 'Production';
		}


	function get_icons($icon_data,$link_data=array())
	{
		$content = '';

		foreach($icon_data as $name => $data)
		{
			$label = array_shift($data);
			if ($data['adminonly'])
			{
				if (!$this->acl->is_admin())
				{
					continue;	// only admin is allowed to eg. add/delete cats
				}
				unset($data['adminonly']);
			}
			$onclick = $open = "if (this != '') { window.open(this,this.target,'width=800,height=600,scrollbars=yes,resizable=yes'); return false; } else { return true; }";
			if ($data['confirm'])
			{
				$onclick = "if (confirm('".addslashes($data['confirm'])."')) { $open } else { return false; }";
				unset($data['confirm']);
			}
			$content .= $GLOBALS['egw']->html->a_href(
				$GLOBALS['egw']->html->image('sitemgr',$name,$label,'border="0"'),
				array_merge($link_data,$data),False,'target="editwindow" onclick="'.$onclick.'"');
		}
		return $content;
	}

	// get the icons with links to be added to a cat in edit-mode
	function getEditIconsCat($cat_id)
	{
		if ($GLOBALS['sitemgr_info']['mode'] == 'Edit' && $GLOBALS['Common_BO']->acl->can_write_category($cat_id))
		{
			$cat = $GLOBALS['Common_BO']->cats->getCategory($cat_id,$GLOBALS['Common_BO']->sites->current_site['sitelanguages'][0]);

			return $this->get_icons(array(
				'new_page' => array(lang('Add page to category'),'menuaction'=>'sitemgr.Pages_UI.edit'),
				'new' => array(lang('Add a category'),'adminonly'=>True,'cat_id'=>0,'addsub'=>$cat_id,'menuaction'=>'sitemgr.Categories_UI.edit'),
				'edit' => array(lang('Edit category'),'adminonly'=>True,'menuaction'=>'sitemgr.Categories_UI.edit'),
				'delete' => array(lang('Delete category'),'adminonly'=>True,'confirm'=>lang('Are you sure you want to delete the category %1 and all of its associated pages?  You cannot retrieve the deleted pages if you continue.',$cat->name),'menuaction'=>'sitemgr.Categories_UI.delete','standalone'=>1),
			),array(
				'page_id' => 0,
				'cat_id'  => $cat_id
			));
		}
		return '';
	}

	// get the icons with links to be added to a page in edit-mode
	function getEditIconsPage($page_id,$cat_id)
	{
		if ($GLOBALS['sitemgr_info']['mode'] == 'Edit' && $GLOBALS['Common_BO']->acl->can_write_category($cat_id))
		{
			return $this->get_icons(array(
				'edit' => array(lang('Edit page'),'menuaction'=>'sitemgr.Pages_UI.edit'),
				'delete' => array(lang('Delete page'),'confirm'=>lang('Do you realy want to delete this page?'),'menuaction'=>'sitemgr.Pages_UI.delete'),
			),array(
				'page_id' => $page_id,
				'cat_id'  => $cat_id
			));
		}
		return '';
	}

	function getEditIconsTop()
	{
		if ($GLOBALS['sitemgr_info']['mode'] == 'Edit' && $GLOBALS['Common_BO']->acl->can_write_category(CURRENT_SITE_ID))
		{
			return $this->get_icons(array(
				'new' => array(lang('Add a category'),'adminonly'=>True,'menuaction'=>'sitemgr.Categories_UI.edit')
			));
		}
		return '';
	}
}
?>