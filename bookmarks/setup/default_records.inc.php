<?php
	/**************************************************************************\
	* eGroupWare                                                               *
	* http://www.egroupware.org                                                *
	* --------------------------------------------                             *
	*  This program is free software; you can redistribute it and/or modify it *
	*  under the terms of the GNU General Public License as published by the   *
	*  Free Software Foundation; either version 2 of the License, or (at your  *
	*  option) any later version.                                              *
	\**************************************************************************/

	/* $Id: default_records.inc.php 20014 2005-11-27 01:17:28Z milosch $ */

	foreach(array(
		'mail_footer' => '\n\n--\nThis was sent from eGroupWare\nhttp://www.egroupware.org\n',
	) as $name => $value)
	{
		$oProc->insert($GLOBALS['egw_setup']->config_table,array(
			'config_value' => $value,
		),array(
			'config_app' => 'bookmarks',
			'config_name' => $name,
		),__FILE__,__LINE__);
	}
