<?php
/**************************************************************************\
* eGroupWare Wiki - UserInterface                                       *
* http://www.egroupware.org                                                *
* -------------------------------------------------                        *
* Copyright (C) 2004 RalfBecker@outdoor-training.de                        *
* --------------------------------------------                             *
*  This program is free software; you can redistribute it and/or modify it *
*  under the terms of the GNU General Public License as published by the   *
*  Free Software Foundation; either version 2 of the License, or (at your  *
*  option) any later version.                                              *
\**************************************************************************/

/* $Id: hook_config_validate.inc.php 23595 2007-04-14 17:01:43Z ralfbecker $ */

$GLOBALS['egw_info']['server']['found_validation_hook'] = True;

function final_validation($settings)
{
	//echo "final_validation()"; _debug_array($settings);
	if ($settings['allow_anonymous'])
	{
		// check if anon user set and exists
		if (!$settings['anonymous_username'] || !($anon_user = $GLOBALS['egw']->accounts->name2id($settings['anonymous_username'])))
		{
			$GLOBALS['config_error'] = 'Anonymous user does NOT exist!';
		}
		else	// check if anon user has run-rights for manual
		{
			$locations = $GLOBALS['egw']->acl->get_all_location_rights($anon_user,'wiki');
			if (!$locations['run'])
			{
				$GLOBALS['config_error'] = 'Anonymous user has NO run-rights for the application!';
			}
		}
	}
	if ($settings['upload_dir'])
	{
		if (!is_dir($_SERVER['DOCUMENT_ROOT'].$settings['upload_dir']) || !file_exists($_SERVER['DOCUMENT_ROOT'].$settings['upload_dir'].'/.'))
		{
			$GLOBALS['config_error'] = 'Directory does not exist, is not readable by the webserver or is not relative to the document root!';
		}
	}
}
