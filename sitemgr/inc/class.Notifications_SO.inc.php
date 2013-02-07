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
	
	/* $Id: class.Notifications_SO.inc.php 19638 2005-11-06 11:16:31Z ralfbecker $ */
	
	require_once(EGW_INCLUDE_ROOT . '/sitemgr/inc/class.generic_list_so.inc.php');
	
	class Notifications_SO extends generic_list_so
	{
		function Notifications_SO($site_id='')
		{
			$this->generic_list_so('sitemgr', 'egw_sitemgr_notifications','Notification_SO', 'notification_id', 'site_id',$site_id);
		}
	}
