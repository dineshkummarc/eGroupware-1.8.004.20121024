<?php
	/**************************************************************************\
	* eGroupWare - SambaAdmin                                                  *
	* http://www.egroupware.org                                                *
	* http://www.linux-at-work.de                                              *
	* Written by : Lars Kneschke [lkneschke@linux-at-work.de]                  *
	* -----------------------------------------------                          *
	*  This program is free software; you can redistribute it and/or modify it *
	*  under the terms of the GNU General Public License as published by the   *
	*  Free Software Foundation; either version 2 of the License, or (at your  *
	*  option) any later version.                                              *
	\**************************************************************************/
	/* $Id: index.php 19542 2005-11-01 16:22:03Z ralfbecker $ */
	
	// this is to get css inclusion working
	$_GET['menuaction']     = 'sambaadmin.uisambaadmin.listWorkstations';
	                
	$GLOBALS['egw_info'] = array(
		'flags' => array(
			'currentapp' => 'sambaadmin',
			'noheader'   => True,
			'nonavbar'   => True
		),
	);
	include('../header.inc.php');
	
	ExecMethod('sambaadmin.uisambaadmin.listWorkstations');
