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

/* $Id: class.Categories_UI.inc.php 33239 2010-12-01 08:20:37Z ralfbecker $ */

class Categories_UI
{
	var $common_ui;
	var $cat_bo;
	var $acl;
	var $isadmin;
	var $t;
	var $sitelanguages;

	var $public_functions = array
	(
		'edit' => True,
		'delete' => True
	);

	function Categories_UI()
	{
		$this->common_ui =& CreateObject('sitemgr.Common_UI',True);
		$this->t = $GLOBALS['egw']->template;
		$this->cat_bo = $GLOBALS['Common_BO']->cats;
		$this->cat =& CreateObject('sitemgr.Category_SO', True);
		$this->acl = $GLOBALS['Common_BO']->acl;
		$this->isadmin = $this->acl->is_admin();
		$this->sitelanguages = $GLOBALS['Common_BO']->sites->current_site['sitelanguages'];
	}

	function edit()
	{
		if (!$this->isadmin)
		{
			$GLOBALS['egw']->redirect($GLOBALS['egw']->link('/index.php','menuaction=sitemgr.Outline_UI.manage'));
			return False;
		}

		$GLOBALS['Common_BO']->globalize(array(
			'inputcatname','inputcatdesc','inputcatid','inputsortorder','inputparent','inputstate',
			'inputindexpage','inputparentold','savelanguage','inputgetparentpermissions','inputapplypermissionstosubs',
			'inputgroupaccessread','inputgroupaccesswrite','inputindividualaccessread','inputindividualaccesswrite'
		));

		global $btnSave, $inputcatid,$inputcatname,$inputcatdesc,$inputsortorder,$inputparent,$inputparentold,$inputindexpage,$inputstate;
		global $inputgroupaccessread, $inputgroupaccesswrite, $inputindividualaccessread, $inputindividualaccesswrite;
		global $savelanguage, $inputgetparentpermissions,$inputapplypermissionstosubs;
		$cat_id = $inputcatid ? $inputcatid : $_GET['cat_id'];

		if ($_POST['btnDelete'])
		{
			return $this->delete($cat_id);
		}
		$focus_reload_close = 'window.focus();';

		if ($_POST['btnSave'] || $_POST['btnApply'])
		{
			if ($inputcatname == '' || $inputcatdesc == '' ||
				!($inputgetparentpermissions && $inputparent ||
				  $inputgroupaccessread || $inputgroupaccesswrite || $inputindividualaccessread || $inputindividualaccesswrite))
			{
				$error = lang('You failed to fill in one or more required fields.');
				$this->t->set_var('message',$error);
			}
			else
			{
				$cat_id =  $cat_id ? $cat_id : $this->cat_bo->addCategory('','');

				$groupaccess = array_merge_recursive((array)$inputgroupaccessread, (array)$inputgroupaccesswrite);
				$individualaccess = array_merge_recursive((array)$inputindividualaccessread, (array)$inputindividualaccesswrite);
				$savelanguage = $savelanguage ? $savelanguage : $this->sitelanguages[0];
				$this->cat_bo->saveCategoryInfo($cat_id, $inputcatname, $inputcatdesc, $savelanguage, $inputsortorder, $inputstate, $inputparent, $inputparentold,$inputindexpage);
				if ($inputgetparentpermissions)
				{
					$this->cat_bo->saveCategoryPermsfromparent($cat_id);
				}
				else
				{
					$this->cat_bo->saveCategoryPerms($cat_id, $groupaccess, $individualaccess);
				}
				if ($inputapplypermissionstosubs)
				{
					$this->cat_bo->applyCategoryPermstosubs($cat_id);
				}
				$this->t->set_var('message',lang('Category saved'));
				$focus_reload_close = 'opener.location.reload();';
				if ($_POST['btnSave'])
				{
					$focus_reload_close .= 'self.close();';
				}
			}
		}

		if(!$savelanguage && in_array($GLOBALS['egw_info']['user']['preferences']['common']['lang'], $this->sitelanguages))
		{
			$savelanguage = $GLOBALS['egw_info']['user']['preferences']['common']['lang'];
		}
		else
		{
			$savelanguage = $savelanguage ? $savelanguage : $this->sitelanguages[0];
		}
		if ($cat_id)
		{
			//we use force here since we might edit an archive category
			$cat = $this->cat_bo->getCategory($cat_id,$savelanguage,True);
		}

		$GLOBALS['egw']->common->egw_header();
		$this->t->set_file('EditCategory', 'edit_category.tpl');
		$this->t->set_block('EditCategory','GroupBlock', 'GBlock');

		if (count($this->sitelanguages) > 1)
		{
			$langs = array();
			foreach ($this->sitelanguages as $lang)
			{
				$langs[$lang] = $GLOBALS['Common_BO']->getlangname($lang);
			}
			$select = html::select('savelanguage',$savelanguage,$langs,false,' onchange="this.form.submit()"');
			$this->t->set_var('savelang',$select);
		}
		$indexpageselect = '';
		$pages = $GLOBALS['Common_BO']->pages->getPageOptionList($cat_id,'Automatic index',$cat->state ? $cat->state : 'Production');
		foreach($pages as $page)
		{
			$indexpageselect .= '<option value="'.$page[value].'"'.
				($page['value'] == $cat->index_page_id ? ' selected="1"' : '').'>'.$page[display]."</option>\n";
		}
		$this->t->set_var(array(
			'action_url' => $GLOBALS['egw']->link('/index.php',array('menuaction'=>'sitemgr.Categories_UI.edit')),
			'focus_reload_close' => $focus_reload_close,
			'add_edit' => ($cat_id ? lang('Edit Category') : lang('Add Category')),
			'cat_id' => $cat_id,
			'catname' => $cat ? $cat->name : $inputcatname,
			'catdesc' => $cat ? $cat->description : $inputcatdesc,
			'indexpageselect' => $indexpageselect,
			'sort_order' => $cat ? $cat->sort_order : $inputsortorder,
			'parent_dropdown' => $this->getParentOptions($_GET['addsub'] ? $_GET['addsub'] : $cat->parent,$cat_id),
			'stateselect' => $GLOBALS['Common_BO']->inputstateselect($cat->state),
			'old_parent' => $cat->parent,
			'lang_basic' => lang('Basic Settings'),
			'lang_catname' => lang('Category Name'),
			'lang_catsort' => lang('Sort Order'),
			'lang_catparent' => lang('Parent'),
			'lang_catdesc' => lang('Category Description'),
			'lang_indexpage' => lang('Index'),
			'lang_groupaccess' => lang('Group Access Permissions'),
			'lang_groupname' => lang('Group Name'),
			'lang_readperm' => lang('Read Permission'),
			'lang_writeperm' => lang('Write Permission'),
			'lang_implies' => lang('implies read permission'),
			'lang_useraccess' => lang('Individual Access Permissions'),
			'lang_username' => lang('User Name'),
			'lang_apply' => lang('Apply'),
			'lang_reload' => lang('Reload'),
			'lang_cancel' => lang('Cancel'),
			'lang_save' => lang('Save'),
			'lang_delete' => lang('Delete'),
			'lang_confirm' => lang('Are you sure you want to delete the category %1 and all of its associated pages?  You cannot retrieve the deleted pages if you continue.',$cat->name),
			'lang_state' => lang('State'),
			'lang_getparentpermissions' => lang('Fill in permissions from parent category? If you check this, below values will be ignored'),
			'lang_applypermissionstosubs' => lang('Apply permissions also to subcategories?'),
			'lang_required' => lang('Required Fields'),
		));

		$acct =& CreateObject('phpgwapi.accounts');
		$grouplist = $this->acl->get_group_list();
		$permissionlist = ($cat_id ? $this->acl->get_group_permission_list($cat_id) : array());
		if($grouplist)
		{
			for($i = 0; $i < count($grouplist); $i++ )
			{
				//$account_name = $acct->id2name($permissionlist[$i]['account_id']);
				//$this->t->set_var('group_id',$permissionlist[$i]['account_id']);
				$account_name = $grouplist[$i]['account_lid'];
				$account_id = $grouplist[$i]['account_id'];
				$this->t->set_var('group_id',$account_id);
				if ($cat_id)
				{
					$permission_id = $permissionlist[$account_id];
				}
				else
				{
					$permission_id = 0;
				}

				$this->t->set_var('groupname', $account_name);
				if ($permission_id & EGW_ACL_READ)
				{
					$this->t->set_var('checkedgroupread','CHECKED="1"');
				}
				else
				{
					$this->t->set_var('checkedgroupread','');
				}
				if ($permission_id & EGW_ACL_ADD)
				{
					$this->t->set_var('checkedgroupwrite','CHECKED="1"');
				}
				else
				{
					$this->t->set_var('checkedgroupwrite','');
				}

				$this->t->parse('GBlock', 'GroupBlock', True);
			}
		}
		else
		{
			$this->t->set_var('groupname',lang("No groups defined."));
		}

		$this->t->set_block('EditCategory','UserBlock', 'UBlock');

		$userlist = $this->acl->get_user_list();
		$userpermissionlist = $this->acl->get_user_permission_list($cat_id);
		if($userlist)
		{
			for($i = 0; $i < count($userlist); $i++ )
			{
				$user_name = $userlist[$i]['account_lid'];
				$user_id = $userlist[$i]['account_id'];
				if ($cat_id)
				{
					$user_permission_id = $userpermissionlist[$user_id];
				}
				else
				{
					$user_permission_id = 0;
				}
				$this->t->set_var('user_id', $user_id);

				$this->t->set_var('username', $user_name);
				if ($user_permission_id & EGW_ACL_READ )
				{
					$this->t->set_var('checkeduserread','CHECKED="1"');
				}
				else
				{
					$this->t->set_var('checkeduserread','');
				}
				if ($user_permission_id & EGW_ACL_ADD )
				{
					$this->t->set_var('checkeduserwrite','CHECKED="1"');
				}
				else
				{
					$this->t->set_var('checkeduserwrite','');
				}
				$this->t->parse('UBlock', 'UserBlock', True);
			}
		}
		else
		{
			$this->t->set_var('username',lang("No users defined."));
		}

		$this->t->pfp('out','EditCategory');

		$this->common_ui->DisplayFooter();
	}

	function getParentOptions($selected_id=0,$skip_id=0)
	{
		$option_list=$this->cat_bo->getCategoryOptionList();
		if (!$skip_id)
		{
			$skip_id = -1;
		}
		$retval="\n".'<SELECT NAME="inputparent">'."\n";
		foreach($option_list as $option)
		{
			if ($option['value']!=$skip_id)
			{
				$selected='';
				if ($option['value']==$selected_id)
				{
					$selected=' SELECTED="1"';
				}
				$retval.='<OPTION VALUE="'.$option['value'].'"'.$selected.'>'.
				$option['display'].'</OPTION>'."\n";
			}
		}
		$retval.='</SELECT>';
		return $retval;
	}

	function delete($cat_id = 0)
	{
		if (!$this->isadmin)
		{
			$GLOBALS['egw']->redirect($GLOBALS['egw']->link('/index.php','menuaction=sitemgr.Outline_UI.manage'));
			return;
		}

		$standalone = $_GET['standalone'] || $cat_id; // standalone => close this window after deleting

		if (!$cat_id) $cat_id = $_GET['cat_id'];

		if ($_POST['btnDelete'] || $_POST['btnCancel'] || $standalone)
		{
			if ($_POST['btnDelete'] || $standalone)
			{
				$cat = $this->cat_bo->getCategory($cat_id,False,True);
				$this->cat_bo->removeCategory($cat_id);
				$parent_url = $GLOBALS['Common_BO']->sites->current_site['site_url'].'?category_id='.$cat->parent;
				if (!isset($GLOBALS['egw_info']['server']['usecookies']) || !$GLOBALS['egw_info']['server']['usecookies'])
				{
					$parent_url .= '&sessionid='. @$GLOBALS['egw_info']['user']['sessionid'];
					$parent_url .= '&kp3='.($_GET['kp3'] ? $_GET['kp3'] : $GLOBALS['egw_info']['user']['kp3']);
					$parent_url .= '&domain='.@$GLOBALS['egw_info']['user']['domain'];
				}
				$reload = "opener.location.href='$parent_url';";
			}
			if ($standalone)
			{
				echo '<html><head></head><body onload="'.$reload.'self.close()"></body></html>';
				return;
			}
			$GLOBALS['egw']->redirect_link('/index.php','menuaction=sitemgr.Outline_UI.manage');
		}

		$this->common_ui->DisplayHeader();

		$cat = $this->cat_bo->getCategory($cat_id,$this->sitelanguages[0]);
		$this->t->set_file('ConfirmDelete','confirmdelete.tpl');
		$this->t->set_var('deleteheader',lang('Are you sure you want to delete the category %1 and all of its associated pages?  You cannot retrieve the deleted pages if you continue.',$cat->name));
		$this->t->set_var('cat_id',$cat_id);
		$this->t->set_var('lang_yes',lang('Yes, please delete it'));
		$this->t->set_var('lang_no',lang('Cancel the delete'));
		$this->t->set_var('standalone',$_GET['standalone']);
		$this->t->set_var('action_url',$GLOBALS['egw']->link('/index.php',array('menuaction'=>'sitemgr.Categories_UI.delete','cat_id'=>$cat_id)));
		$this->t->pfp('out','ConfirmDelete');
	}
}
?>
