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
	
	/* $Id: class.NtfMessages_SO.inc.php 19638 2005-11-06 11:16:31Z ralfbecker $ */
	
	require_once(EGW_INCLUDE_ROOT . '/sitemgr/inc/class.generic_list_so.inc.php');
	
	class NtfMessages_SO extends generic_list_so
	{
		function NtfMessages_SO($site_id='')
		{
			$this->generic_list_so('sitemgr', 'egw_sitemgr_notify_messages', 'NtfMessages_SO', 'message_id', 'site_id',$site_id);
		}
		
		function list_languages()
		{
			$this->db->select($this->table,'language',array('site_id' => $this->master_id),__LINE__,__FILE__);
			while($this->db->next_record())
			{
				$lang = $this->db->f('language');

				$result[$lang] = $GLOBALS['Common_BO']->getlangname($lang);
			}
			return $result;
		}
	}
