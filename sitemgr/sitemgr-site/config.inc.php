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

	/* $Id: config.inc.php 22966 2006-12-10 00:45:57Z omgs $ */

	/**************************************************************\
	* Edit the values in the following array to configure SiteMgr, *
	* to run in a differen directory/URL as sitemgr/sitemgr-site.  *
	\**************************************************************/

	$GLOBALS['sitemgr_info'] = array(
		// add trailing slash
		'egw_path'         => '../../',
		'htaccess_rewrite' => False,
	);
	// uncomment the next line if sitemgr should use a eGW domain different from the first one defined in your header.inc.php
	// and of cause change the name accordingly ;-)
	//$GLOBALS['egw_info']['user']['domain'] = $GLOBALS['egw_info']['server']['default_domain'] = 'other';
