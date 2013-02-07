<?php
	/**************************************************************************\
	* eGroupWare - Bookmarks                                                   *
	* http://www.egroupware.org                                                *
	* --------------------------------------------                             *
	*  This program is free software; you can redistribute it and/or modify it *
	*  under the terms of the GNU General Public License as published by the   *
	*  Free Software Foundation; either version 2 of the License, or (at your  *
	*  option) any later version.                                              *
	\**************************************************************************/
	/* $Id: hook_preferences.inc.php 19419 2005-10-14 16:24:39Z ralfbecker $ */

	$file = array(
		'Import Bookmarks' => $GLOBALS['egw']->link('/index.php','menuaction=bookmarks.ui.import'),
		'Export Bookmarks' => $GLOBALS['egw']->link('/index.php','menuaction=bookmarks.ui.export'),
		'Grant Access'  => $GLOBALS['egw']->link('/index.php','menuaction=preferences.uiaclprefs.index&acl_app='.$appname),
		'Edit Categories' => $GLOBALS['egw']->link('/index.php','menuaction=preferences.uicategories.index&cats_app=' . $appname . '&cats_level=True&global_cats=True')
	);
	display_section('bookmarks','Bookmarks',$file);
?>
