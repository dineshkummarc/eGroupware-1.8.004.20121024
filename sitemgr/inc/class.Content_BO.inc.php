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

	/* $Id: class.Content_BO.inc.php 25656 2008-06-24 14:20:40Z leithoff $ */

require_once(EGW_INCLUDE_ROOT . SEP . 'sitemgr' . SEP . 'inc' . SEP . 'class.module.inc.php');

define('SITEMGR_STATE_DRAFT',0);
define('SITEMGR_STATE_PREPUBLISH',1);
define('SITEMGR_STATE_PUBLISH',2);
define('SITEMGR_STATE_PREUNPUBLISH',3);
define('SITEMGR_STATE_ARCHIVE',4);

define('SITEMGR_VIEWABLE_EVERBODY',0);
define('SITEMGR_VIEWABLE_USER',1);
define('SITEMGR_VIEWABLE_ADMIN',2);
define('SITEMGR_VIEWABLE_ANONYMOUS',3);

	class Content_BO
	{
		var $so;
		var $blockcach;
		var $blockdefcach;
		
		function Content_BO()
		{
			$this->so =& CreateObject('sitemgr.Content_SO', true);
		}

		function getContentAreas()
		{
			$templatedir =  $GLOBALS['Common_BO']->sites->current_site['site_dir'] .  SEP . 'templates' .
				SEP . $GLOBALS['Common_BO']->sites->current_site['themesel'] . SEP;

			if (file_exists($templatefile = $templatedir . 'main.tpl'))
			{
				$str = implode('', @file($templatefile));
				if (preg_match_all("/\{contentarea:([^{ ]+)\}/",$str,$matches))
				{
					return $matches[1];
				}
				else
				{
					return lang('No content areas found in selected template');
				}
			}
			elseif (file_exists($templatefile = $templatedir . 'index.php'))  // mambo open source template
			{
				$str = implode('', @file($templatefile));
				if (preg_match_all("/(mosLoadModules|include|include_once|require|require_once)[ (\"']+([^\"']+)/",$str,$matches))
				{
					$matches = $matches[2];
					$conversation = array('includes/footer.php' => 'footer','mainbody.php'=>'center','mainmenu.php'=>'mainmenu');
					foreach($matches as $n => $name)
					{
						if (substr($name,-4) == '.php')
						{
							if (!isset($conversation[$name]))
							{
								unset($matches[$n]);
							}
							else
							{
								$matches[$n] = $conversation[$name];
							}
						}
					}
					//echo "<p>getContentAreas($templatefile)=".implode(',',$matches)."</p>";
					return $matches;
				}
				else
				{
					return lang('No content areas found in selected template');
				}
			}
			else
			{
				return lang('No template file found.');
			}
		}


		function addblock($block)
		{
			$permittedmoduleids = array_keys($GLOBALS['Common_BO']->modules->getcascadingmodulepermissions($block->area,$block->cat_id));
			$module = $GLOBALS['Common_BO']->modules->getmodule($block->module_id);

			if ($GLOBALS['Common_BO']->acl->can_write_category($block->cat_id) &&
				in_array($block->module_id,$permittedmoduleids) &&
				$GLOBALS['Common_BO']->modules->createmodule($module['module_name']))
			{
				return $this->so->addblock($block);
			}
			else
			{
				return false;
			}
		}

		function createversion($blockid)
		{
			return $this->so->createversion($blockid);
		}

		function deleteversion($versionid,$force=False)
		{
			if (!$force)
			{
				$blockid = $this->so->getblockidforversion($versionid);
				if (!$blockid)
				{
					return false;
				}
				$block = $this->so->getblockdef($blockid);
				if (!($block && $GLOBALS['Common_BO']->acl->can_write_category($block->cat_id)))
				{
					return false;
				}
			}
			return $this->so->deleteversion($versionid);
		}

		function removeBlocksInPageOrCat($cat_id,$page_id,$force=False)
		{
			if (!($force || $GLOBALS['Common_BO']->acl->can_write_category($cat_id)))
			{
				return false;
			}
			$blocks = $this->so->getblocksforscope($cat_id,$page_id);
			while(list($blockid,) = each($blocks))
			{
				$this->removeblock($blockid,True);
			}
		}

		function removeblock($blockid,$force=False)
		{
			if (!$force)
			{
				$block = $this->so->getblockdef($blockid);
				if (!($block && $GLOBALS['Common_BO']->acl->can_write_category($block->cat_id)))
				{
					return false;
				}
			}
			if ($this->so->removeblock($blockid))
			{
				$versions = $this->so->getversionidsforblock($blockid);
				while(list(,$versionid) = @each($versions))
				{
					//since we already did the ACL we force
					$this->deleteversion($versionid,True);
				}
				return true;
			}
			return false;
		}

		//the next two functions retrieves all blocks for a certain area, if (cat_id = $site_id and page_id = 0), only site-wide blocks are retrieved.
		//if (cat_id != $site_id and page_id is 0), site-wide blocks and all blocks for the category and all its ancestor categories are retrieved.
		//if page_id is non zero, cat_id should be the page's category. Page blocks + category blocks + site blocks are retrieved.
		//there is no ACL, since these functions are called in a context where getcategory and getpage have been called before and would have intercepted a breach
		function &getvisibleblockdefsforarea($area,$cat_id,$page_id,$isadmin,$isuser)
		{
			$cat_ancestorlist = ($cat_id != CURRENT_SITE_ID) ? 
				$GLOBALS['Common_BO']->cats->getCategoryancestorids($cat_id,True) : 
				False;
			return !isset($this->blockdefcach['all'][$area][$cat_id][$page_id][$isadmin][$isuser]) ?
				($this->blockdefcach['all'][$area][$cat_id][$page_id][$isadmin][$isuser] = $this->so->getvisibleblockdefsforarea($area,$cat_ancestorlist,$page_id,$isadmin,$isuser)) :
				$this->blockdefcach['all'][$area][$cat_id][$page_id][$isadmin][$isuser];
		}

		function &getallblocksforarea($area,$cat_id,$page_id,$lang)
		{
			$cat_ancestorlist = ($cat_id != CURRENT_SITE_ID) ? 
				$GLOBALS['Common_BO']->cats->getCategoryancestorids($cat_id,True) : 
				False;
			return !isset($this->blockcach['all'][$area][$cat_id][$page_id][$lang]) ? 
				($this->blockcach['all'][$area][$cat_id][$page_id][$lang] = $this->so->getallblocksforarea($area,$cat_ancestorlist,$page_id,$lang)) : 
				$this->blockcach['all'][$area][$cat_id][$page_id][$lang];
		}

		function &getcommitableblocks()
		{
			return $this->so->getallblocks($GLOBALS['Common_BO']->cats->getpermittedcatsWrite(),$GLOBALS['Common_BO']->getstates('Commit'));
		}

		function &getarchivedblocks()
		{
			return $this->so->getallblocks($GLOBALS['Common_BO']->cats->getpermittedcatsWrite(),$GLOBALS['Common_BO']->getstates('Archive'));
		}
		function getallversionsforblock($blockid,$lang)
		{
			return $this->so->getallversionsforblock($blockid,$lang);
		}

		function &getblock($id,$lang)
		{
			//do we need ACL here, since we have ACL when getting the block lists, we could do without it here?
			return $this->so->getblock($id,$lang);
		}

		function getlangblocktitle($block_id,$lang=false)
		{
				return $this->so->getlangblocktitle($block_id,$lang);
		}

		function getlangarrayforblocktitle($block_id)
		{
			return $this->so->getlangarrayforblocktitle($block_id);
		}

		function getlangarrayforversion($version_id)
		{
			return $this->so->getlangarrayforversion($version_id);
		}

		//this function retrieves blocks only for a certain scope (site-wide, specific to one category or specific to one page), 
		//but for all areas.
		function &getblocksforscope($cat_id,$page_id)
		{
			if ($cat_id && !$GLOBALS['Common_BO']->acl->can_read_category($cat_id))
			{
				return array();
			}
			else
			{
				return $this->so->getblocksforscope($cat_id,$page_id);
			}
		}

		function getversion($version_id,$lang=False)
		{
			//TODO: add ACL ?
			return $this->so->getversion($version_id,$lang);
		}

		function saveblockdata($block,$data,$state,$lang,$scope=False)
		{
			$oldblock = $this->so->getblockdef($block->id);
			if (!($oldblock && $GLOBALS['Common_BO']->acl->can_write_category($oldblock->cat_id)))
			{
				return array(lang("You are not entitled to edit block %1",$block->id));
			}

			$this->so->saveblockdata($block);
			$this->so->saveblockdatalang($block->id,$block->title,$lang);
			$this->NotifyUsers($lang, $oldblock->page_id,$oldblock->cat_id,$state,$block->id,$block->title);
			if ($scope)
			{
				list($cat_id,$page_id) = explode(',',$scope);
				if ($oldblock->cat_id != $cat_id || $oldblock->page_id != $page_id)
				{
					if ($GLOBALS['Common_BO']->acl->can_write_category($cat_id))
					{
						$this->so->updatescope($block->id,$cat_id,$page_id);
					}
					else
					{
						$validationerrors[] = lang("You are not entitled to change the scope of block %1 to Cat %2 and Page %3",$block->id,$cat_id,$page_id);
					}
				}
			}
			if (is_array($state) && !$this->saveversionstate($block->id,$state))
			{
				$validationerrors[] = lang('There can only be one version in (pre(un))published state, with the one exeption that one prepublished version can coexist with one preunpublished version');
			}
			$moduleobject =& $this->getblockmodule($block->id);

			// we need to loop over the stats (and not the data) as empty checkboxes dont return any data !!!
			// so the empty state would not get saved, if there are only empty checkboxes.
			if (!is_array($state)) $validationerrors[] = lang('No states defined');
			foreach($state as $versionid => $s)
			{
				$versiondata = isset($data[$versionid]) ? $data[$versionid] : array();

				if ($moduleobject->validate($versiondata))
				{
					if ($this->saveversiondatalang($block->id,$versionid,$versiondata['i18n'],$lang))
					{
						unset($versiondata['i18n']);
						$this->so->saveversiondata($block->id,$versionid,$versiondata);
					}
				}
				if ($moduleobject->validation_error)
				{
					$validationerrors[] = $moduleobject->validation_error;
				}
			}
			return $validationerrors ? $validationerrors : True;
		}

		function saveblockdatalang($block,$data,$lang)
		{
			$oldblock = $this->so->getblockdef($block->id);
			if (!($oldblock && $GLOBALS['Common_BO']->acl->can_write_category($oldblock->cat_id)))
			{
				return lang("You are not entitled to edit block %1",$block->id);
			}
			$this->so->saveblockdatalang($block->id,$block->title,$lang);

			$this->NotifyUsers($lang, $oldblock->page_id, $oldblock->cat_id,-1,$block->id,$block->title);
			$moduleobject =& $this->getblockmodule($block->id);
			while (list($versionid,$versiondata) = @@each($data))
			//TODO: check if version really belongs to block
			{
				if ($moduleobject->validate($versiondata))
				{
					$this->saveversiondatalang($block->id,$versionid,$versiondata['i18n'],$lang);
				}
				else
				{
					$validationerrors[] = $moduleobject->validation_error;
				}
			}
			return $validationerrors ? $validationerrors : True;
		}

		//takes the array (version_id => version_state) posted from the UI as argument
		//and checks if there is only one in (pre(un))published state 
		//(exeption one prepublished, and one preunpublished can coexsit)
		function saveversionstate($block_id,$state)
		{
			$count_array = array_count_values($state);
			$active_versions = $count_array[SITEMGR_STATE_PREPUBLISH] + 
				$count_array[SITEMGR_STATE_PUBLISH] + 
				$count_array[SITEMGR_STATE_PREUNPUBLISH];
			if (($active_versions < 2) || (($active_versions == 2) && ($count_array[SITEMGR_STATE_PUBLISH] == 0)))
			{
				while (list($versionid,$versionstate) = each($state))
				{
					$this->so->saveversionstate($block_id,$versionid,$versionstate);
				}
				return true;
			}
			else
			{
				return false;
			}
		}

		function saveversiondatalang($block_id,$version_id,$data,$lang)
		{
			return ($this->so->getblockidforversion($version_id) == $block_id) ?
				$this->so->saveversiondatalang($version_id,$data,$lang) :
				false;
		}

		//this function can be called from a block's get_content function. It stores modification to the 
		//blocks arguments in the database
		function savepublicdata(&$block)
		{
			//TODO: check if argument is public, disentangle session data from arguments
			$this->so->saveversiondata($block->id,$block->version,$block->arguments);
		}

		function &getblockmodule($blockid)
		{
			$block = $this->so->getblockdef($blockid);
			return $GLOBALS['Common_BO']->modules->createmodule($block->module_name);
		}

		function commit($block_id)
		{
			$block = $this->so->getblockdef($block_id);
			if ($GLOBALS['Common_BO']->acl->can_write_category($block->cat_id))
			{
				$this->so->commit($block_id);
			}
		}

		function reactivate($block_id)
		{
			$block = $this->so->getblockdef($block_id);
			if ($GLOBALS['Common_BO']->acl->can_write_category($block->cat_id))
			{
				$this->so->reactivate($block_id);
			}
		}
		function NotifyUsers($lang, $page_id,$cat_id,$state,$block_id,$block_name)
		{
			if (empty($cat_id)) {
				$bl=$this->so->getblockdef($block_id);
				$cat_id=$bl->cat_id;
			}
			if ($state<0) {
				$state=$this->so->getblockstate($block_id,$lang);
			}

			$bo=CreateObject("sitemgr.bonotifications");
			$bo->notify_users($GLOBALS['Common_BO']->sites->current_site['site_id'],
				$cat_id,
				$state,
				$lang,
				$GLOBALS['Common_BO']->sites->current_site['sitelanguages']['0'],
				array('page_id'=>$page_id,'lang'=>$lang),
				array(
					array('text'=>"Block name:",'translate'=>True),
					" ",$block_name
				)
			);
		}
	}
?>
