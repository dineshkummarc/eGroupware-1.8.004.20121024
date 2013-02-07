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

	/* $Id: class.Notifications_BO.inc.php 18221 2005-05-02 09:48:28Z ralfbecker $ */

include_once(EGW_INCLUDE_ROOT . '/sitemgr/inc/class.generic_list_bo.inc.php');
$GLOBALS['egw_info']['flags']['included_classes']['generic_list_bo'] = True;

define("MAX_STR_LENGTH",200);

class Notifications_BO extends generic_list_bo
{

	var $site_bo;
	var $site;

	function Notifications_BO()
	{
		$this->site_bo=CreateObject("sitemgr.Sites_BO");
		$this->site=False;
		$this->so=CreateObject("sitemgr.Notifications_SO");
		
	}
	
	function get_site_langs()
	{

		if (empty($this->site))
			$this->site=$this->site_bo->read($this->get_master_id());

		$res=array();
		foreach ($this->site['sitelanguages'] as $lang)
		{
			$res[$lang]=$GLOBALS['Common_BO']->getlangname($lang);
		}
		return $res;
	}
	
}
