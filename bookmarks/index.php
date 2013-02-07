<?php
	/**************************************************************************\
	* eGroupWare - Bookmarks                                                   *
	* http://www.egroupware.org                                                *
	* Based on Bookmarker Copyright (C) 1998  Padraic Renaghan                 *
	*                     http://www.renaghan.com/bookmarker                   *
	* --------------------------------------------                             *
	*  This program is free software; you can redistribute it and/or modify it *
	*  under the terms of the GNU General Public License as published by the   *
	*  Free Software Foundation; either version 2 of the License, or (at your  *
	*  option) any later version.                                              *
	\**************************************************************************/

	/* $Id: index.php 20014 2005-11-27 01:17:28Z milosch $ */

	$GLOBALS['egw_info'] = array(
		'flags' => array(
			'currentapp' => 'bookmarks',
			'nonavbar'   => True,
			'noheader'   => True
		)
	);
	include('../header.inc.php');

	$obj =& CreateObject('bookmarks.ui');
	$obj->init();
	$GLOBALS['egw']->common->egw_footer();
