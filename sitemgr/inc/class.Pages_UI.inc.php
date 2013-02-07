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

	/* $Id: class.Pages_UI.inc.php 33239 2010-12-01 08:20:37Z ralfbecker $ */

	class Pages_UI
	{
		var $common_ui;
		var $t;
		var $pagebo;
		var $categorybo;
		var $pageso; // page class
		var $sitelanguages;
		
		var $public_functions=array
		(
			'edit' => True,
			'delete' => True,
		 );
		
		function Pages_UI()     
		{
			$this->common_ui =& CreateObject('sitemgr.Common_UI',True);
			$this->t = $GLOBALS['egw']->template;
			$this->pagebo = &$GLOBALS['Common_BO']->pages;
			$this->categorybo = &$GLOBALS['Common_BO']->cats;
			$this->sitelanguages = $GLOBALS['Common_BO']->sites->current_site['sitelanguages'];
		}
	
		function delete($page_id = 0)
		{
			if (!$page_id) $page_id = $_GET['page_id'];
			$this->pagebo->removePage($page_id);
			if ($_GET['menuaction'] == 'sitemgr.Outline_UI.manage')
			{
				$GLOBALS['egw']->redirect_link('/index.php','menuaction=sitemgr.Outline_UI.manage');
			}
			echo '<html><head></head><body onload="opener.location.reload();self.close()"></body></html>';
		}

		function edit()
		{
			$GLOBALS['Common_BO']->globalize(array(
				'inputhidden','inputsort','inputstate',
				'inputtitle','inputname','inputsubtitle','savelanguage','inputpageid','inputcategoryid'));

			global $inputpageid,$inputcategoryid, $inputhidden, $inputstate;
			global $inputsort,$inputtitle, $inputname, $inputsubtitle;
			global $savelanguage;

			$page_id = $inputpageid ? $inputpageid : $_GET['page_id'];
			$category_id = $inputcategoryid ? $inputcategoryid : $_GET['cat_id'];

			$GLOBALS['egw']->common->egw_header();
			$this->t->set_file('EditPage', 'edit_page.tpl');

			if ($_POST['btnDelete'])
			{
				return $this->delete($page_id);
			}
			$focus_reload_close = 'window.focus();';

			if($_POST['btnSave'] || $_POST['btnApply'])
			{
				if ($inputname == '' || $inputtitle == '')
				{
					$error = lang('You failed to fill in one or more required fields.');
					$this->t->set_var('message',$error);
				}
				else
				{
					if(!$page_id)
					{   
						$page_id = $this->pagebo->addPage($inputcategoryid);
						if(!$page_id)
						{
	//            echo lang("You don't have permission to write in the category");
							$GLOBALS['egw']->redirect($GLOBALS['egw']->link('/index.php','menuaction=sitemgr.Outline_UI.manage'));
							return;
						}
					}
					$page->id = $page_id;
					$page->title = $inputtitle;
					$page->name = $inputname;
					$page->subtitle = $inputsubtitle;
					$page->sort_order = $inputsort;
					$page->cat_id = $category_id;
					$page->hidden = $inputhidden ? 1: 0;
					$page->state = $inputstate;
					$savelanguage = $savelanguage ? $savelanguage : ($GLOBALS['sitemgr_info']['userlang']?$GLOBALS['sitemgr_info']['userlang']:$this->sitelanguages[0]);
					$save_msg = $this->pagebo->savePageInfo($page,$savelanguage);
					if (!is_string($save_msg))
					{
						$this->t->set_var('message',lang('Page saved'));

						$focus_reload_close = 'opener.location.reload();';
						if ($_POST['btnSave'])
						{
							$focus_reload_close .= 'self.close();';
						}
					}
					else
					{
						$this->t->set_var('message',$save_msg);
					}
				}
			}

			$openlanguage = $savelanguage ? $savelanguage : 
				($GLOBALS['sitemgr_info']['userlang']?$GLOBALS['sitemgr_info']['userlang']:
					$this->sitelanguages[0]);
			
			if($page_id)
			{
				$page = $this->pagebo->getPage($page_id,$openlanguage,True);
				if (!$GLOBALS['Common_BO']->acl->can_write_category($page->cat_id))
				{
					$GLOBALS['egw']->redirect($GLOBALS['egw']->link('/index.php','menuaction=sitemgr.Outline_UI.manage'));
					return;
				}
				$this->t->set_var(array(
					'add_edit' => lang('Edit Page'),
					'catselect' => $this->getParentOptions($page->cat_id)
				));
			}
			else
			{
				$this->t->set_var(array(
					'add_edit' => lang('Add Page'),
					'catselect' => $this->getParentOptions($category_id)
				));
			}

			if (count($this->sitelanguages) > 1)
			{
				$langs = array();
				foreach ($this->sitelanguages as $lang)
				{
					$langs[$lang] = $GLOBALS['Common_BO']->getlangname($lang);
				}
				$select = html::select('savelanguage',$openlanguage,$langs,false,' onchange="this.form.submit()"');
				$this->t->set_var('savelang',$select);
			}

			$link_data['page_id'] = $page_id;
			$link_data['category_id'] = $inputcategoryid;
			$this->t->set_var(array(
				'action_url' => $GLOBALS['egw']->link('/index.php',array('menuaction'=>'sitemgr.Pages_UI.edit')),
				'focus_reload_close' => $focus_reload_close,
				'title' =>$page->title,
				'subtitle' => $page->subtitle,
				'name'=>$page->name,
				'sort_order'=>$page->sort_order,
				'page_id'=>$page_id,
				'hidden' => $page->hidden ? 'CHECKED' : '',
				'stateselect' => $GLOBALS['Common_BO']->inputstateselect($page->state),
				'lang_name' => lang('Name'),
				'lang_title' => lang('Title'),
				'lang_subtitle' => lang('Subtitle'),
				'lang_sort' => lang('Sort order'),
				'lang_category' => lang('Category'),
				'lang_hide' => lang('Check to hide from condensed site index.'),
				'lang_required' => lang('Required Fields'),
				'lang_apply' => lang('Apply'),
				'lang_cancel' => lang('Cancel'),
				'lang_reload' => lang('Reload'),
				'lang_save' => lang('Save'),
				'lang_delete' => lang('Delete'),
				'lang_confirm' => lang('Do you realy want to delete this page?'),
				'lang_state' => lang('State'),
				'lang_nameinfo' => lang('(Do not put spaces or punctuation in the Name field.)'),
			));

			$this->t->pfp('out','EditPage');
		}


		function getParentOptions($selected_id=0)
		{
			$option_list=$this->categorybo->getCategoryOptionList();
			if (!$selected_id)
			{
				$selected=' SELECTED'; 
			}
			$retval="\n".'<SELECT NAME="inputcategoryid">'."\n";
			foreach($option_list as $option)
			{
				if ((int) $option['value']!=0)
				{
					$selected='';
					if ($option['value']==$selected_id)
					{
						$selected=' SELECTED';
					}
					$retval.='<OPTION VALUE="'.$option['value'].'"'.$selected.'>'.
					$option['display'].'</OPTION>'."\n";
				}
			}
			$retval.='</SELECT>';
			return $retval;
		}
	} 
?>
