<?php
/**************************************************************************\
* eGroupWare Wiki - preferences                                            *
* http://www.egroupware.org                                                *
* -------------------------------------------------                        *
* Copyright (C) 2007 RalfBecker@outdoor-training.de                        *
* --------------------------------------------                             *
*  This program is free software; you can redistribute it and/or modify it *
*  under the terms of the GNU General Public License as published by the   *
*  Free Software Foundation; either version 2 of the License, or (at your  *
*  option) any later version.                                              *
\**************************************************************************/

/* $Id: hook_config_validate.inc.php 20978 2006-04-06 10:56:20Z ralfbecker $ */

$GLOBALS['settings'] = array(
	'rtfEditorFeatures' => array(
		'type'   => 'select',
		'label'  => 'Features of the editor?',
		'name'   => 'rtfEditorFeatures',
		'values' => array(
			'simple'   => lang('Simple'),
			'extended' => lang('Regular'),
			'advanced' => lang('Everything'),
		),
		'help'   => 'You can customize how many icons and toolbars the editor shows.',
		'xmlrpc' => True,
		'admin'  => False,
		'default'=> 'extended',
	),
);
