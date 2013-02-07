<?php
	/**************************************************************************\
	* eGroupWare - Polls                                                       *
	* http://www.egroupware.org                                                *
	* Copyright (c) 1999 Till Gerken (tig@skv.org)                             *
	* Modified by Greg Haygood (shrykedude@bellsouth.net)                      *
	* -----------------------------------------------                          *
	*  This program is free software; you can redistribute it and/or modify it *
	*  under the terms of the GNU General Public License as published by the   *
	*  Free Software Foundation; either version 2 of the License, or (at your  *
	*  option) any later version.                                              *
	\**************************************************************************/

	/* $Id: hook_home.inc.php 23899 2007-05-20 15:01:21Z ralfbecker $ */

	$hp_display = (int)$GLOBALS['egw_info']['user']['preferences']['polls']['homepage_display'];
	if($hp_display > 0)
	{
		$obj =& CreateObject('polls.uipolls');
		$obj->view();
	}
?>
