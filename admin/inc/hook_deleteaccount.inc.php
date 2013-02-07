<?php
	/**************************************************************************\
	* eGroupWare                                                               *
	* http://www.egroupware.org                                                *
	* Written by Mark Peters <skeeter@phpgroupware.org>                        *
	* --------------------------------------------                             *
	*  This program is free software; you can redistribute it and/or modify it *
	*  under the terms of the GNU General Public License as published by the   *
	*  Free Software Foundation; either version 2 of the License, or (at your  *
	*  option) any later version.                                              *
	\**************************************************************************/

	/* $Id: hook_deleteaccount.inc.php 19547 2005-11-02 11:45:52Z ralfbecker $ */

	if((int)$GLOBALS['hook_values']['account_id'] > 0)
	{
		$GLOBALS['egw']->accounts->delete((int)$GLOBALS['hook_values']['account_id']);
		$GLOBALS['egw']->acl->delete_account((int)$GLOBALS['hook_values']['account_id']);
	}
