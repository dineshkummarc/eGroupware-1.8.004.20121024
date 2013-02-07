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

	/* $Id: hook_admin.inc.php 18221 2005-05-02 09:48:28Z ralfbecker $ */

	{
// Only Modify the $file variable.....

		$file = Array
		(
			'Define Websites' => $GLOBALS['egw']->link('/index.php','menuaction=sitemgr.Sites_UI.list_sites'),
		);

//Do not modify below this line
		if (method_exists($GLOBALS['egw']->common,'display_mainscreen'))
		{
			$GLOBALS['egw']->common->display_mainscreen($appname,$file);
		}
		else
		{
			display_section($appname,$title,$file);
		}
	}
?>
