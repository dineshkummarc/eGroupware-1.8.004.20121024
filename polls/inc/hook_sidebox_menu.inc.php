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

	/* $Id: hook_sidebox_menu.inc.php 23899 2007-05-20 15:01:21Z ralfbecker $ */
{

 /*
	This hookfile is for generating an app-specific side menu used in the idots
	template set.

	$menu_title speaks for itself
	$file is the array with link to app functions

	display_sidebox can be called as much as you like
 */

	$menu_title = 'Polls Menu';
	$file = Array(
		'Current Poll'
			=> $GLOBALS['egw']->link('/index.php', array('menuaction'=>'polls.uipolls.index')),
		'View Results' 
			=> $GLOBALS['egw']->link('/index.php', array('menuaction'=>'polls.uipolls.vote','show_results'=>$GLOBALS['poll_settings']['currentpoll']))
	);
	display_sidebox($appname,$menu_title,$file);

/*
	$menu_title = 'Preferences';
	$file = Array(

	);
	display_sidebox($appname,$menu_title,$file);
*/

	if($GLOBALS['egw_info']['user']['apps']['admin'])
	{
		$menu_title = 'Administration';
		$file = Array(
			'Site configuration'
				=> $GLOBALS['egw']->link('/index.php', array('menuaction'=>'polls.uipolls.admin','action'=>'settings')),
			'Show Questions'
				=> $GLOBALS['egw']->link('/index.php', array('menuaction'=>'polls.uipolls.admin','action'=>'show','type'=>'question')),
			'Add Questions'
				=> $GLOBALS['egw']->link('/index.php', array('menuaction'=>'polls.uipolls.admin','action'=>'add','type'=>'question')),
			'Show Answers'
				=> $GLOBALS['egw']->link('/index.php', array('menuaction'=>'polls.uipolls.admin','action'=>'show','type'=>'answer')),
			'Add Answers'
				=> $GLOBALS['egw']->link('/index.php', array('menuaction'=>'polls.uipolls.admin','action'=>'add','type'=>'answer')),
		);

		display_sidebox($appname,$menu_title,$file);
	}
}
?>
