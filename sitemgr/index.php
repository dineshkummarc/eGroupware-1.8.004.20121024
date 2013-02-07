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

	/* $Id: index.php 33188 2010-11-28 20:27:05Z ralfbecker $ */

	$GLOBALS['egw_info'] = array(
		'flags' => array(
			'currentapp' => 'sitemgr',
			'noheader'   => True,
			'nonavbar'   => True,
			'noapi'      => False
	));
	include('../header.inc.php');
	
	$CommonUI =& CreateObject('sitemgr.Common_UI');

	if (!$CommonUI->do_sites_exist && $GLOBALS['egw_info']['user']['apps']['admin'])
	{
		egw::redirect_link('/index.php',array('menuaction'=>'sitemgr.Sites_UI.edit'));
	}
	$CommonUI->DisplayIFrame();

	common::egw_footer();
